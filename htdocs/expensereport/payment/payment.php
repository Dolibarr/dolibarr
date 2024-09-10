<?php
/* Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/expensereport/payment/payment.php
 *  \ingroup    Expense Report
 *  \brief      Page to add payment of an expense report
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'trips'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$amounts = array();
$accountid = GETPOSTINT('accountid');
$cancel = GETPOST('cancel');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$form = new Form($db);

/*
 * Actions
 */

if ($action === 'add_payment' || ($action === 'confirm_payment' && $confirm === 'yes')) {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$id;
		header("Location: ".$loc);
		exit;
	}

	$expensereport = new ExpenseReport($db);
	$result = $expensereport->fetch($id, $ref);
	if (!$result) {
		$error++;
		setEventMessages($expensereport->error, $expensereport->errors, 'errors');
	}

	$datepaid = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));

	if (!(GETPOSTINT("fk_typepayment") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
	}

	if (isModEnabled("bank") && !($accountid > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToDebit")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$paymentid = 0;
		// $total = 0;

		// Read possible payments
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 7) == 'amount_') {
				$cursorexpid = substr($key, 7);
				if (GETPOST($key)) {
					$amounts[$cursorexpid] = price2num(GETPOST($key));
					$totalpayment += price2num(GETPOST($key));
				}
			}
		}

		if (count($amounts) <= 0) {
			$error++;
			setEventMessages('ErrorNoPaymentDefined', null, 'errors');
		}

		if (!$error && $action === 'confirm_payment' && $confirm === 'yes') {
			$db->begin();

			// Create a line of payments
			$payment = new PaymentExpenseReport($db);
			$payment->fk_expensereport = $expensereport->id;
			$payment->datep       	 = $datepaid;
			$payment->amounts		 = $amounts; // array of amounts
			// total is calculated in class
			// $payment->total          = $total;
			$payment->fk_typepayment = GETPOSTINT("fk_typepayment");
			$payment->num_payment    = GETPOST("num_payment", 'alphanohtml');
			$payment->note_public    = GETPOST("note_public", 'restricthtml');
			$payment->fk_bank        = $accountid;

			if (!$error) {
				$paymentid = $payment->create($user, (GETPOST('closepaidexpensereports', 'alpha') == 'on' ? 1 : 0));
				if ($paymentid < 0) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$result = $payment->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $accountid, '', '');
				if ($result <= 0) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$id;
				header('Location: '.$loc);
				exit;
			} else {
				$db->rollback();
			}
		}
	}
} elseif ($action === 'confirm_payment') {
	$action = 'create';
}


/*
 * View
 */

llxHeader();

$form = new Form($db);


// Form to create expense report payment
if ($action == 'create' || $action == 'add_payment') {
	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($id, $ref);

//	$total = $expensereport->total_ttc;

	// autofill remainder amount
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		//Add js for AutoFill
		print ' $(document).ready(function () {';
		print ' 	$(".AutoFillAmount").on(\'click touchstart\', function(){
                        var amount = $(this).data("value");
						document.getElementById($(this).data(\'rowid\')).value = amount ;
					});';
		print "\t});\n";
		print "</script>\n";
	}

	print load_fiche_titre($langs->trans("DoPayment"));

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$expensereport->id.'">';
	print '<input type="hidden" name="chid" value="'.$expensereport->id.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head(null, '0', '', -1);

	print '<table class="centpercent">'."\n";

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
	$datepayment = ($datepaid == '' ? (!getDolGlobalString('MAIN_AUTOFILL_DATE') ? -1 : '') : $datepaid);
	print $form->selectDate($datepayment, '', 0, 0, 0, "add_payment", 1, 1);
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	$form->select_types_paiements(GETPOSTISSET("fk_typepayment") ? GETPOST("fk_typepayment", 'alpha') : $expensereport->fk_c_paiement, "fk_typepayment");
	print "</td>\n";
	print '</tr>';

	if (isModEnabled("bank")) {
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
		print '<td colspan="2">';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		$form->select_comptes(GETPOSTISSET("accountid") ? GETPOSTINT("accountid") : 0, "accountid", 0, '', 2); // Show open bank account list
		print '</td></tr>';
	}

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_3.'">'.GETPOST("note_public", 'restricthtml').'</textarea></td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<br>';

	// List of expenses ereport not already paid completely
	$num = 1;
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ExpenseReport").'</td>';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$total_ttc = 0;
	$totalrecu = 0;

	$sortorder = 'DESC';
	$sortfield = 'pe.datep';

	$sql = 'SELECT e.rowid, e.total_ttc, e.ref, SUM(pe.amount) as total_amount';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'expensereport as e';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paymentexpensereport_expensereport as pe ON pe.fk_expensereport = e.rowid';
	$sql.= ' WHERE fk_user_author = '.((int) $expensereport->fk_user_author);
	$sql .= ' AND e.entity IN ('.getEntity('expensereport').')';
	$sql .= ' AND e.fk_statut = '.ExpenseReport::STATUS_APPROVED;
	$sql .= ' AND e.paid = 0';
	$sql.= ' GROUP BY e.rowid, e.ref, e.total_ttc';
	$resql = $db->query($sql);

	$fk_user_author = $expensereport->fk_user_author;

	if (!empty($resql)) {
		$u_author = new User($db);
		while ($objp = $db->fetch_object($resql)) {
			$expensereport = new ExpenseReport($db);
			$expensereport->id = $objp->rowid;
			$expensereport->ref = $objp->ref;
			$sumpaid = $objp->total_amount;

			if (empty($u_author->id)) $u_author->fetch($fk_user_author);

			print '<tr class="oddeven">';

			print '<td>' . $expensereport->getNomUrl(1) . "</td>";
			print '<td>' . $u_author->getNomUrl(1) . "</td>";
			print '<td class="right">' . price($objp->total_ttc) . "</td>";
			print '<td class="right">' . price($sumpaid) . "</td>";
			print '<td class="right">' . price($objp->total_ttc - $sumpaid) . "</td>";
			print '<td class="center">';
			if ($sumpaid < $objp->total_ttc) {
				$namef = "amount_" . $objp->rowid;
				$nameRemain = "remain_" . $objp->rowid; // autofill remainder amount
				if (!empty($conf->use_javascript_ajax)) { // autofill remainder amount
					print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' data-rowid='" . $namef . "' data-value='" . ($objp->total_ttc - $sumpaid) . "'"); // autofill remainder amount
				}
				$remaintopay = $objp->total_ttc - $sumpaid; // autofill remainder amount
				print '<input type=hidden class="sum_remain" name="' . $nameRemain . '" value="' . $remaintopay . '">'; // autofill remainder amount
				print '<input type="text" class="width75" name="' . $namef . '" id="' . $namef . '" value="' . GETPOST($namef) . '">';
			} else {
				print '-';
			}
			print "</td>";

			print "</tr>\n";

			$total_ttc += $objp->total_ttc;
			$totalrecu += $sumpaid;
			$i++;
		}
	}
	if ($i > 1) {
		// Print total
		print '<tr class="oddeven">';
		print '<td class="left" colspan="2">'.$langs->trans("Total").':</td>';
		print '<td class="right"><b>'.price($total_ttc).'</b></td>';
		print '<td class="right"><b>'.price($totalrecu).'</b></td>';
		print '<td class="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
		print '<td class="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";

	$langs->load('expensereports');
	if ($action == 'add_payment' && empty($error)) {
		print '<br>';
		if (!empty($totalpayment)) {
			$text = $langs->trans('ConfirmUserPayment', $totalpayment, $langs->trans("Currency".$conf->currency));
		}
		if (GETPOST('closepaidexpensereports')) {
			$text .= '<br>'.$langs->trans("AllCompletelyPayedExpenseReportWillBeClosed");
			print '<input type="hidden" name="closepaidexpensereports" value="'.GETPOST('closepaidexpensereports').'">';
		}
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$id, $langs->trans('PayedUsersPayments'), $text, 'confirm_payment', $formquestion, 'yes');

		// Print form confirm
		print $formconfirm;
	} else {
		$checkboxlabel = $langs->trans("ClosePaidExpenseReportsAutomatically");
		print '<div class="center">';
		print '<input type="checkbox" checked name="closepaidexpensereports"> ' . $checkboxlabel;
		print '</div>';

		print $form->buttonsSaveCancel('Save', '');
	}

	print "</form>\n";
}

// End of page
llxFooter();
$db->close();
