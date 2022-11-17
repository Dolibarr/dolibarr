<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2022       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	    \file       htdocs/compta/payment_sc/card.php
 *		\ingroup    tax
 *		\brief      Tab with payment of a social contribution
 *		\remarks	File similar to fourn/paiement/card.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
if (isModEnabled('banque')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'companies'));

// Security check
$id = GETPOST("id", 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'aZ09');
if ($user->socid) {
	$socid = $user->socid;
}
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$object = new PaymentSocialContribution($db);
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
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->tax->charges->supprimer) {
	$db->begin();

	$result = $object->delete($user);
	if ($result > 0) {
		$db->commit();
		header("Location: ".DOL_URL_ROOT."/compta/sociales/payments.php");
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

$socialcontrib = new ChargeSociales($db);

$form = new Form($db);

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/compta/payment_sc/card.php?id='.$id;
$head[$h][1] = $langs->trans("PaymentSocialContribution");
$hselected = $h;
$h++;

/*$head[$h][0] = DOL_URL_ROOT.'/compta/payment_sc/info.php?id='.$id;
$head[$h][1] = $langs->trans("Info");
$h++;
*/


print dol_get_fiche_head($head, $hselected, $langs->trans("PaymentSocialContribution"), -1, 'payment');

/*
 * Deletion confirmation of payment
 */
if ($action == 'delete') {
	print $form->formconfirm('card.php?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete', '', 0, 2);
}

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/sociales/payments.php">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'id', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Ref
/*print '<tr><td class="titlefield">'.$langs->trans('Ref').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'id','',1,'rowid','id');
print '</td></tr>';*/

// Date
print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">'.dol_print_date($object->datep, 'day').'</td></tr>';

// Mode
print '<tr><td>'.$langs->trans('Mode').'</td><td colspan="3">'.$langs->trans("PaymentType".$object->type_code).'</td></tr>';

// Numero
print '<tr><td>'.$langs->trans('Numero').'</td><td colspan="3">'.$object->num_payment.'</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($object->amount, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note
print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3">'.nl2br($object->note).'</td></tr>';

// Bank account
if (isModEnabled('banque')) {
	if ($object->bank_account) {
		$bankline = new AccountLine($db);
		$bankline->fetch($object->bank_line);

		print '<tr>';
		print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td colspan="3">';
		print $bankline->getNomUrl(1, 0, 'showall');
		print '</td>';
		print '</tr>';
	}
}

print '</table>';

print '</div>';

print dol_get_fiche_end();


/*
 * List of social contributions paid
 */

$disable_delete = 0;
$sql = 'SELECT f.rowid as scid, f.libelle as label, f.paye, f.amount as sc_amount, pf.amount, pc.libelle as sc_type';
$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementcharge as pf,'.MAIN_DB_PREFIX.'chargesociales as f, '.MAIN_DB_PREFIX.'c_chargesociales as pc';
$sql .= ' WHERE pf.fk_charge = f.rowid AND f.fk_type = pc.id';
$sql .= ' AND f.entity = '.$conf->entity;
$sql .= ' AND pf.rowid = '.((int) $object->id);

dol_syslog("compta/payment_sc/card.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('SocialContribution').'</td>';
	print '<td>'.$langs->trans('Type').'</td>';
	print '<td>'.$langs->trans('Label').'</td>';
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
			$socialcontrib->fetch($objp->scid);
			print $socialcontrib->getNomUrl(1);
			print "</td>\n";
			// Type
			print '<td>';
			print $socialcontrib->type_label;
			/*print $socialcontrib->type;*/
			print "</td>\n";
			// Label
			print '<td>'.$objp->label.'</td>';
			// Expected to pay
			print '<td class="right"><span class="amount">'.price($objp->sc_amount).'</span></td>';
			// Status
			print '<td class="center">'.$socialcontrib->getLibStatut(4, $objp->amount).'</td>';
			// Amount paid
			print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';
			print "</tr>\n";
			if ($objp->paye == 1) {	// If at least one invoice is paid, disable delete
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



/*
 * Actions Buttons
 */
print '<div class="tabsAction">';

/*
if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
{
	if ($user->socid == 0 && $object->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="card.php?id='.GETPOST('id', 'int').'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}
*/

if ($action == '') {
	if ($user->rights->tax->charges->supprimer) {
		if (!$disable_delete) {
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.GETPOST('id', 'int').'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("CantRemovePaymentWithOneInvoicePaid")).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';

// End of page
llxFooter();
$db->close();
