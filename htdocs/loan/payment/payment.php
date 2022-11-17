<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Maxime DEMAREST         <maxime@indelog.fr>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';

$langs->loadLangs(array("bills", "loan"));

$chid = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$datepaid = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
} elseif (GETPOSTISSET('socid')) {
	$socid = GETPOST('socid', 'int');
}
if (empty($user->rights->loan->write)) {
	accessforbidden();
}

$loan = new Loan($db);
$loan->fetch($chid);

$echance = 0;
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

// Set current line with last unpaid line (only if shedule is used)
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


/*
 * Actions
 */

if ($action == 'add_payment') {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/loan/card.php?id='.$chid;
		header("Location: ".$loc);
		exit;
	}

	if (!GETPOST('paymenttype', 'int') > 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
	}
	if (!empty($conf->banque->enabled) && !GETPOST('accountid', 'int') > 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToCredit")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$paymentid = 0;

		$pay_amount_capital = price2num(GETPOST('amount_capital'));
		$pay_amount_insurance = price2num(GETPOST('amount_insurance'));
		// User can't set interest him self if schedule is set (else value in schedule can be incoherent)
		if (!empty($line)) {
			$pay_amount_interest = $line->amount_interest;
		} else {
			$pay_amount_interest = price2num(GETPOST('amount_interest'));
		}
		$remaindertopay = price2num(GETPOST('remaindertopay'));
		$amount = $pay_amount_capital + $pay_amount_insurance + $pay_amount_interest;

		// This term is allready paid
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
			$payment->fk_bank           = GETPOST('accountid', 'int');
			$payment->paymenttype       = GETPOST('paymenttype', 'int');
			$payment->num_payment		= GETPOST('num_payment');
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
				$result = $payment->addPaymentToBank($user, $chid, 'payment_loan', '(LoanPayment)', $payment->fk_bank, '', '');
				if (!($result > 0)) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			// Update loan schedule with payment value
			if (!$error && !empty($line)) {
				// If payment values are modified, recalculate schedule
				if (($line->amount_capital <> $pay_amount_capital) || ($line->amount_insurance <> $pay_amount_insurance) || ($line->amount_interest <> $pay_amount_interest)) {
					$arr_term = loanCalcMonthlyPayment(($pay_amount_capital + $pay_amount_interest), $remaindertopay, ($loan->rate / 100), $echance, $loan->nbterm);
					foreach ($arr_term as $k => $v) {
						// Update fk_bank for current line
						if ($k == $echance) {
							$ls->lines[$k - 1]->fk_bank = $payment->fk_bank;
							$ls->lines[$k - 1]->fk_payment_loan = $payment->id;
						}
						$ls->lines[$k - 1]->amount_capital = $v['mens'] - $v['interet'];
						$ls->lines[$k - 1]->amount_interest = $v['interet'];
						$ls->lines[$k - 1]->tms = dol_now();
						$ls->lines[$k - 1]->fk_user_modif = $user->id;
						$result = $ls->lines[$k - 1]->update($user, 0);
						if ($result < 1) {
							setEventMessages(null, $ls->errors, 'errors');
							$error++;
							break;
						}
					}
				} else // Only add fk_bank bank to schedule line (mark as paid)
				{
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

llxHeader();

$form = new Form($db);


// Form to create loan's payment
if ($action == 'create') {
	$total = $loan->capital;

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
			$datepayment = empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : dol_now();
		} else {
			$datepayment = $ts_temppaid;
		}
	} else {
		$datepayment = $datepaid;
	}
		print $form->selectDate($datepayment, '', '', '', '', "add_payment", 1, 1);
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
		$form->select_comptes(GETPOSTISSET("accountid") ? GETPOST("accountid", 'int') : $loan->accountid, "accountid", 0, 'courant = '.Account::TYPE_CURRENT, 1); // Show opend bank account list
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
		print $langs->trans("LoanCapital").': <input type="text" size="8" name="amount_capital" value="'.(GETPOSTISSET('amount_capital') ?GETPOST('amount_capital') : $amount_capital).'">';
	} else {
		print '-';
	}
		print '<br>';
	if ($sumpaid < $loan->capital) {
		print $langs->trans("Insurance").': <input type="text" size="8" name="amount_insurance" value="'.(GETPOSTISSET('amount_insurance') ?GETPOST('amount_insurance') : $amount_insurance).'">';
	} else {
		print '-';
	}
		print '<br>';
	if ($sumpaid < $loan->capital) {
		print $langs->trans("Interest").': <input type="text" size="8" name="amount_interest" value="'.(GETPOSTISSET('amount_interest') ?GETPOST('amount_interest') : $amount_interest).'" '.(!empty($line) ? 'disabled title="'.$langs->trans('CantModifyInterestIfScheduleIsUsed').'"' : '').'>';
	} else {
		print '-';
	}
		print "</td>";

		print "</tr>\n";

		print '</table>';

		print $form->buttonsSaveCancel();

		print "</form>\n";
}

llxFooter();
$db->close();
