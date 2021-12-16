<?php
/* Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *	    \file       htdocs/don/payment/card.php
 *		\ingroup    donations
 *		\brief      Tab payment of a donation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
if (!empty($conf->banque->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("bills", "banks", "companies", "donations"));

// Security check
$id = GETPOST('rowid') ? GETPOST('rowid', 'int') : GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
if ($user->socid) {
	$socid = $user->socid;
}
// TODO Add rule to restrict access payment
//$result = restrictedArea($user, 'facture', $id,'');

$object = new PaymentDonation($db);
if ($id > 0) {
	$result = $object->fetch($id);
	if (!$result) {
		dol_print_error($db, 'Failed to get payment id '.$id);
	}
}


/*
 * Actions
 */

// Delete payment
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->don->supprimer) {
	$db->begin();

	$result = $object->delete($user);
	if ($result > 0) {
		$db->commit();
		header("Location: ".DOL_URL_ROOT."/don/index.php");
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}



/*
 * View
 */

llxHeader();

$don = new Don($db);
$form = new Form($db);

$h = 0;

$head = array();
$head[$h][0] = DOL_URL_ROOT.'/don/payment/card.php?id='.$id;
$head[$h][1] = $langs->trans("DonationPayment");
$hselected = $h;
$h++;

print dol_get_fiche_head($head, $hselected, $langs->trans("DonationPayment"), -1, 'payment');

/*
 * Confirm deleting of the payment
 */
if ($action == 'delete') {
	print $form->formconfirm('card.php?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete', '', 0, 2);
}


dol_banner_tab($object, 'id', '', 1, 'rowid', 'id');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Date
print '<tr><td class="titlefield">'.$langs->trans('Date').'</td><td>'.dol_print_date($object->datep, 'day').'</td></tr>';

// Mode
print '<tr><td>'.$langs->trans('Mode').'</td><td>'.$langs->trans("PaymentType".$object->type_code).'</td></tr>';

// Number
print '<tr><td>'.$langs->trans('Numero').'</td><td>'.$object->num_payment.'</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note public
print '<tr><td>'.$langs->trans('Note').'</td><td>'.nl2br($object->note_public).'</td></tr>';

// Bank account
if (!empty($conf->banque->enabled)) {
	if ($object->bank_account) {
		$bankline = new AccountLine($db);
		$bankline->fetch($object->bank_line);

		print '<tr>';
		print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td>';
		print $bankline->getNomUrl(1, 0, 'showall');
		print '</td>';
		print '</tr>';
	}
}

print '</table>';


/*
 * List of donations paid
 */

$disable_delete = 0;
$sql = 'SELECT d.rowid as did, d.paid, d.amount as d_amount, pd.amount';
$sql .= ' FROM '.MAIN_DB_PREFIX.'payment_donation as pd,'.MAIN_DB_PREFIX.'don as d';
$sql .= ' WHERE pd.fk_donation = d.rowid';
$sql .= ' AND d.entity = '.$conf->entity;
$sql .= ' AND pd.rowid = '.((int) $id);

dol_syslog("don/payment/card.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Donation').'</td>';
	print '<td class="right">'.$langs->trans('ExpectedToPay').'</td>';
	print '<td class="center">'.$langs->trans('Status').'</td>';
	print '<td class="right">'.$langs->trans('PayedByThisPayment').'</td>';
	print "</tr>\n";

	if ($num > 0) {
		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			// Ref
			print '<td>';
			$don->fetch($objp->did);
			print $don->getNomUrl(1);
			print "</td>\n";
			// Expected to pay
			print '<td class="right">'.price($objp->d_amount).'</td>';
			// Status
			print '<td class="center">'.$don->getLibStatut(4, $objp->amount).'</td>';
			// Amount paid
			print '<td class="right">'.price($objp->amount).'</td>';
			print "</tr>\n";
			if ($objp->paid == 1) {
				// If at least one invoice is paid, disable delete
				$disable_delete = 1;
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}


	print "</table>\n";
	$db->free($resql);
} else {
	dol_print_error($db);
}

print '</div>';

print dol_get_fiche_end();


/*
 * Actions buttons
 */
print '<div class="tabsAction">';

if (empty($action)) {
	if ($user->rights->don->supprimer) {
		if (!$disable_delete) {
			print '<a class="butActionDelete" href="card.php?id='.$object->id.'&amp;action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("CantRemovePaymentWithOneInvoicePaid")).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';



llxFooter();

$db->close();
