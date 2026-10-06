<?php
/* Copyright (C) 2014-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2015-2026  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2020		Maxime DEMAREST				<maxime@indelog.fr>
 * Copyright (C) 2025		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/loan/payment/payment.php
 *		\ingroup    Loan
 *		\brief      Page to add payment of a loan
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';

$langs->loadLangs(array("bills", "loan"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$chid = GETPOSTINT('id');
$datepaid = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
} elseif (GETPOSTISSET('socid')) {
	$socid = GETPOSTINT('socid');
}
if (!$user->hasRight('loan', 'write')) {
	accessforbidden();
}

$loan = new Loan($db);
$loan->fetch($chid);

$line_id = 0;
$echance = 0;
$amount_capital = 0;
$amount_insurance = 0;
$amount_interest = 0;

$ls = new LoanSchedule($db);
// grab all loanschedule
$res = $ls->fetchAll($chid);
if ($res > 0) {
	foreach ($ls->lines as $l) {
		$echance++; // Count term pos
		// last unpaid term
		if (empty($l->fk_bank)) {
			$line_id = $l->id;
			break;
		} elseif ($line_id == $l->id) {
			// If line_id provided, only count temp pos
			break;
		}
	}
}

// Set current line with last unpaid line (only if schedule is used)
if (!empty($line_id)) {
	$line = new LoanSchedule($db);
	$res = $line->fetch($line_id);
	if ($res > 0) {
		$amount_capital = price($line->amount_capital);
		$amount_insurance = price($line->amount_insurance);
		$amount_interest = price($line->amount_interest);
		if (empty($datepaid)) {
			$ts_temppaid = $line->datep;
		}
	}
}

// Without a schedule: the next payment worked out from the loan and the payments already made
$nextpayment = null;
if (empty($line) && $loan->paid != Loan::STATUS_PAID) {
	$nextpayment = loanNextPayment($db, $loan);
	if ($nextpayment) {
		$amount_capital = price($nextpayment['capital']);
		$amount_insurance = price($nextpayment['charge']);
		$amount_interest = price($nextpayment['interest']);
		if (empty($datepaid)) {
			$ts_temppaid = $nextpayment['date'];
		}
	}
}

$permissiontoadd = $user->hasRight('loan', 'write');


/*
 * Actions
 */

if ($action == 'add_payment' && $permissiontoadd) {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/loan/card.php?id='.$chid;
		header("Location: ".$loc);
		exit;
	}

	if (!GETPOSTINT('paymenttype') > 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
	}
	if (isModEnabled("bank") && !GETPOSTINT('accountid') > 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToCredit")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$paymentid = 0;

		$pay_amount_capital = (float) price2num(GETPOST('amount_capital'));
		$pay_amount_insurance = (float) price2num(GETPOST('amount_insurance'));
		// Interest as entered (pre-filled with the schedule's); if it differs from the schedule, the following payments are recalculated
		if (!empty($line) && !GETPOSTISSET('amount_interest')) {
			$pay_amount_interest = $line->amount_interest;
		} else {
			$pay_amount_interest = (float) price2num(GETPOST('amount_interest'));
		}
		$remaindertopay = (float) price2num(GETPOST('remaindertopay'));
		$amount = (float) price2num($pay_amount_capital + $pay_amount_insurance + $pay_amount_interest, 'MT');

		// This term is already paid
		if (!empty($line) && !empty($line->fk_bank)) {
			setEventMessages($langs->trans('TermPaidAllreadyPaid'), null, 'errors');
			$error++;
		}

		if (empty($remaindertopay)) {
			setEventMessages('Empty sumpaid', null, 'errors');
			$error++;
		}

		if ($amount == 0) {
			setEventMessages($langs->trans('ErrorNoPaymentDefined'), null, 'errors');
			$error++;
		}

		if (!$error) {
			$db->begin();

			// Create a line of payments
			$payment = new PaymentLoan($db);
			$payment->chid				= $chid;
			$payment->datep             = $datepaid;
			$payment->label             = $loan->label;
			$payment->amount_capital	= $pay_amount_capital;
			$payment->amount_insurance	= $pay_amount_insurance;
			$payment->amount_interest	= $pay_amount_interest;
			$payment->fk_bank           = GETPOSTINT('accountid');
			$payment->paymenttype       = GETPOSTINT('paymenttype');
			$payment->num_payment		= GETPOST('num_payment', 'alphanohtml');
			$payment->note_private      = GETPOST('note_private', 'restricthtml');
			$payment->note_public       = GETPOST('note_public', 'restricthtml');

			if (!$error) {
				$paymentid = $payment->create($user);
				if ($paymentid < 0) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$result = $payment->addPaymentToBank($user, $chid, 'payment_loan', '(LoanPayment)', $payment->fk_bank, '', '');
				if (!($result > 0)) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			// Update loan schedule with payment value
			if (!$error && !empty($line)) {
				if (($line->amount_capital != $pay_amount_capital) || ($line->amount_insurance != $pay_amount_insurance) || ($line->amount_interest != $pay_amount_interest)) {
					// This payment as entered, then the following unpaid payments recalculated (each with the rate in force on
					// its date), keeping either the number of payments or the repayment
					$keep = (GETPOST('recalc_keep', 'aZ09') === 'payment' ? 'payment' : 'term');
					$nbtermold = (float) $loan->nbterm;
					$line->fk_bank = $payment->fk_bank;
					$line->fk_payment_loan = $payment->id;
					$line->amount_capital = $pay_amount_capital;
					$line->amount_insurance = $pay_amount_insurance;
					$line->amount_interest = $pay_amount_interest;
					$line->fk_user_modif = $user->id;
					if ($line->update($user, 0) < 1) {
						setEventMessages(null, $line->errors, 'errors');
						$error++;
					}
					if (!$error && isset($ls->lines[$echance])) {
						$lines = $ls->lines;
						$lines[$echance - 1] = $line;
						$res = loanRecalculateSchedule($db, $user, $loan, $lines, $echance, (float) $remaindertopay - (float) $pay_amount_capital, null, $keep);
						if ($res['error']) {
							setEventMessages($langs->trans($res['error']), null, 'errors');
							$error++;
						} elseif (loanRecordChange($db, $user, $loan->id, 'payment', $datepaid, (float) $loan->rate, (float) $loan->rate, $keep, $res['payment_old'], $res['payment_new'], $nbtermold, $res['nbterm_new'], $payment->id) < 0) {
							setEventMessages($db->lasterror(), null, 'errors');
							$error++;
						}
					}
				} else { // Only add fk_bank bank to schedule line (mark as paid)
					$line->fk_bank = $payment->fk_bank;
					$line->fk_payment_loan = $payment->id;
					$result = $line->update($user, 0);
					if ($result < 1) {
						setEventMessages(null, $line->errors, 'errors');
						$error++;
					}
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/loan/card.php?id='.$chid;
				header('Location: '.$loc);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	$action = 'create';
}


/*
 * View
 */
$form = new Form($db);

$title = $langs->trans('Loans');
$help_url = "EN:Module_Loan|FR:Module_Emprunt";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-loan page-payment-list');


// Form to create loan's payment
if ($action == 'create') {
	$total = $loan->capital;
	$sumpaid = 0;

	print load_fiche_titre($langs->trans("DoPayment"));

	$sql = "SELECT SUM(amount_capital) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_loan";
	$sql .= " WHERE fk_loan = ".((int) $chid);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free($resql);
	}

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="line_id" value="'.$line_id.'">';
	print '<input type="hidden" name="remaindertopay" value="'.($total - $sumpaid).'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head();

	/*
	 print '<table class="border centpercent">';

	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/loan/card.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	if ($echance > 0)
	{
		print '<tr><td>'.$langs->trans("Term").'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/loan/schedule.php?loanid='.$chid.'#n'.$echance.'">'.$echance.'</a></td></tr>'."\n";
	}
	print '<tr><td>'.$langs->trans("DateStart").'</td><td colspan="2">'.dol_print_date($loan->datestart, 'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$loan->label."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">'.price($loan->capital, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';

	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2">'.price($sumpaid, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td colspan="2">'.price($total - $sumpaid, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
	print '</tr>';

	print '</table>';
	*/

	print '<table class="border centpercent">';

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	if (empty($datepaid)) {
		if (empty($ts_temppaid)) {
			$datepayment = (getDolGlobalString('MAIN_AUTOFILL_DATE') ? dol_now() : -1);
		} else {
			$datepayment = $ts_temppaid;
		}
	} else {
		$datepayment = $datepaid;
	}
	print $form->selectDate($datepayment, '', 0, 0, 0, "add_payment", 1, 1);
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	print img_picto('', 'money-bill-alt', 'class="pictofixedwidth"');
	$form->select_types_paiements(GETPOSTISSET("paymenttype") ? GETPOST("paymenttype", 'alphanohtml') : $loan->fk_typepayment, "paymenttype");
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
	print '<td colspan="2">';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	print $form->select_comptes(GETPOSTISSET("accountid") ? GETPOSTINT("accountid") : $loan->fk_bank, "accountid", 0, '(courant:=:'.Account::TYPE_CURRENT.')', 1, '', 0, '', 1); // Show open bank account list
	print '</td></tr>';

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment', 'alphanohtml').'"></td>'."\n";
	print "</tr>";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("NotePrivate").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note_private" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("NotePublic").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();


	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td class="left">'.$langs->trans("DateDue").'</td>';
	print '<td class="right">'.$langs->trans("LoanCapital").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	print '<tr class="oddeven">';

	if ($loan->datestart > 0) {
		print '<td class="left" valign="center">'.dol_print_date($loan->datestart, 'day').'</td>';
	} else {
		print '<td class="center" valign="center"><b>!!!</b></td>';
	}

	print '<td class="right" valign="center">'.price($loan->capital)."</td>";

	print '<td class="right" valign="center">'.price($sumpaid)."</td>";

	print '<td class="right" valign="center">'.price($loan->capital - $sumpaid)."</td>";

	print '<td class="right">';
	if ($sumpaid < $loan->capital) {
		print $langs->trans("LoanCapital").': <input type="text" size="8" name="amount_capital" value="'.(GETPOSTISSET('amount_capital') ? GETPOST('amount_capital') : $amount_capital).'">';
	} else {
		print '-';
	}
	print '<br>';
	if ($sumpaid < $loan->capital) {
		print loanChargeLabel($loan->charge_type, $langs).': <input type="text" size="8" name="amount_insurance" value="'.(GETPOSTISSET('amount_insurance') ? GETPOST('amount_insurance') : $amount_insurance).'">';
	} else {
		print '-';
	}
	print '<br>';
	if ($sumpaid < $loan->capital) {
		print $langs->trans("Interest").': <input type="text" size="8" name="amount_interest" value="'.(GETPOSTISSET('amount_interest') ? GETPOST('amount_interest') : $amount_interest).'">';
	} else {
		print '-';
	}
	print "</td>";

	print "</tr>\n";

	print '</table>';

	if (!empty($nextpayment)) {
		print '<div class="opacitymedium margintoponly">'.$langs->trans("LoanNextPaymentPrefilled", $nextpayment['term'], (int) $loan->nbterm).'</div>';
	}

	// With a schedule, how to recalculate the following payments if this payment differs from it
	if (!empty($line)) {
		print '<div class="margintoponly">'.$langs->trans("LoanRecalcIfDifferent").' ';
		print $form->selectarray('recalc_keep', array('term' => $langs->trans("LoanRecalcKeepTerm"), 'payment' => $langs->trans("LoanRecalcKeepPayment")), GETPOST('recalc_keep', 'aZ09') ? GETPOST('recalc_keep', 'aZ09') : 'term');
		print '</div>';
	}

	print $form->buttonsSaveCancel();

	print "</form>\n";
}

llxFooter();
$db->close();
