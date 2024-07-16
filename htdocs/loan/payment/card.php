<?php
/* Copyright (C) 2014-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *  \file       htdocs/loan/payment/card.php
 *  \ingroup    loan
 *  \brief      Payment's card of loan
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
if (isModEnabled("bank")) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("bills", "banks", "companies", "loan"));

// Security check
$id = GETPOSTINT("id");
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
if ($user->socid) {
	$socid = $user->socid;
}
// TODO ajouter regle pour restreindre access paiement
//$result = restrictedArea($user, 'facture', $id,'');

$payment = new PaymentLoan($db);
if ($id > 0) {
	$result = $payment->fetch($id);
	if (!$result) {
		dol_print_error($db, 'Failed to get payment id '.$id);
	}
}


/*
 * Actions
 */

// Delete payment
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('loan', 'delete')) {
	$db->begin();

	$sql = "UPDATE ".MAIN_DB_PREFIX."loan_schedule SET fk_bank = 0 WHERE fk_bank = ".((int) $payment->fk_bank);
	$db->query($sql);

	$fk_loan = $payment->fk_loan;

	$result = $payment->delete($user);
	if ($result > 0) {
		$db->commit();
		header("Location: ".DOL_URL_ROOT."/loan/card.php?id=".urlencode((string) ($fk_loan)));
		exit;
	} else {
		setEventMessages($payment->error, $payment->errors, 'errors');
		$db->rollback();
	}
}


/*
 * View
 */
$loan = new Loan($db);
$form = new Form($db);

$title = $langs->trans('Loans');
$help_url = "EN:Module_Loan|FR:Module_Emprunt";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-loan page-payment-card');

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/loan/payment/card.php?id='.$id;
$head[$h][1] = $langs->trans("PaymentLoan");
$hselected = $h;
$h++;

print dol_get_fiche_head($head, $hselected, $langs->trans("PaymentLoan"), -1, 'payment');

/*
 * Confirm deletion of the payment
 */
if ($action == 'delete') {
	print $form->formconfirm('card.php?id='.$payment->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete', '', 0, 2);
}

$linkback = '';
$morehtmlref = '';
$morehtmlstatus = '';

dol_banner_tab($payment, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Date
print '<tr><td>'.$langs->trans('Date').'</td><td>'.dol_print_date($payment->datep, 'day').'</td></tr>';

// Mode
print '<tr><td>'.$langs->trans('Mode').'</td><td>'.$langs->trans("PaymentType".$payment->type_code).'</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('LoanCapital').'</td><td>'.price($payment->amount_capital, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
print '<tr><td>'.$langs->trans('Insurance').'</td><td>'.price($payment->amount_insurance, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
print '<tr><td>'.$langs->trans('Interest').'</td><td>'.price($payment->amount_interest, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note Private
print '<tr><td>'.$langs->trans('NotePrivate').'</td><td>'.nl2br($payment->note_private).'</td></tr>';

// Note Public
print '<tr><td>'.$langs->trans('NotePublic').'</td><td>'.nl2br($payment->note_public).'</td></tr>';

// Bank account
if (isModEnabled("bank")) {
	if ($payment->bank_account) {
		$bankline = new AccountLine($db);
		$bankline->fetch($payment->bank_line);

		print '<tr>';
		print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td>';
		print $bankline->getNomUrl(1, 0, 'showall');
		print '</td>';
		print '</tr>';
	}
}

print '</table>';

print '</div>';


/*
 * List of loans paid
 */

$disable_delete = 0;
$sql = 'SELECT l.rowid as id, l.label, l.paid, l.capital as capital, pl.amount_capital, pl.amount_insurance, pl.amount_interest';
$sql .= ' FROM '.MAIN_DB_PREFIX.'payment_loan as pl,'.MAIN_DB_PREFIX.'loan as l';
$sql .= ' WHERE pl.fk_loan = l.rowid';
$sql .= ' AND l.entity = '.((int) $conf->entity);
$sql .= ' AND pl.rowid = '.((int) $payment->id);

dol_syslog("loan/payment/card.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Loan').'</td>';
	print '<td>'.$langs->trans('Label').'</td>';
	// print '<td class="right">'.$langs->trans('ExpectedToPay').'</td>';
	print '<td class="center">'.$langs->trans('Status').'</td>';
	print '<td class="right">'.$langs->trans('PayedByThisPayment').'</td>';
	print "</tr>\n";

	if ($num > 0) {
		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			// Ref
			print '<td>';
			$loan->fetch($objp->id);
			print $loan->getNomUrl(1);
			print "</td>\n";
			// Label
			print '<td>'.$objp->label.'</td>';
			// Expected to pay
			// print '<td class="right">'.price($objp->capital).'</td>';
			// Status
			print '<td class="center">'.$loan->getLibStatut(4, $objp->amount_capital).'</td>';
			// Amount paid
			$amount_payed = $objp->amount_capital + $objp->amount_insurance + $objp->amount_interest;

			print '<td class="right">'.price($amount_payed).'</td>';
			print "</tr>\n";
			if ($objp->paid == 1) {	// If at least one invoice is paid, disable delete
				$disable_delete = 1;
			}
			$total = $total + $objp->amount_capital;
			$i++;
		}
	}


	print "</table>\n";
	$db->free($resql);
} else {
	dol_print_error($db);
}

print '</div>';


/*
 * Actions buttons
 */

print '<div class="tabsAction">';

if (empty($action) && $user->hasRight('loan', 'delete')) {
	if (!$disable_delete) {
		print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$id.'&action=delete&token='.newToken(), 'delete', 1);
	} else {
		print dolGetButtonAction($langs->trans("CantRemovePaymentWithOneInvoicePaid"), $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', 0);
	}
}

print '</div>';

// End of page
llxFooter();
$db->close();
