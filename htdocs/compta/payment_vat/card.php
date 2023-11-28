<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2021      Gauthier VERDOL       <gauthier.verdol@atm-consulting.fr>
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
 *	    \file       htdocs/compta/payment_vat/card.php
 *		\ingroup    facture
 *		\brief      Onglet payment of a social contribution
 *		\remarks	Fichier presque identique a fournisseur/paiement/card.php
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
if (isModEnabled("banque")) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'companies'));

// Security check
$id = GETPOST("id", 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
if ($user->socid) {
	$socid = $user->socid;
}
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$object = new PaymentVAT($db);
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
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('tax', 'charges', 'supprimer')) {
	$db->begin();

	$result = $object->delete($user);
	if ($result > 0) {
		$db->commit();
		header("Location: ".DOL_URL_ROOT."/compta/tva/payments.php?mode=tvaonly");
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

// Validate social contribution
/*
if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->tax->charges->creer)
{
	$db->begin();

	$result=$object->valide();

	if ($result > 0)
	{
		$db->commit();

		$factures=array();	// TODO Get all id of invoices linked to this payment
		foreach($factures as $id)
		{
			$fac = new Facture($db);
			$fac->fetch($id);

			$outputlangs = $langs;
			if (!empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$fac->generateDocument($fac->model_pdf, $outputlangs);
			}
		}

		header('Location: card.php?id='.$object->id);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}
*/


/*
 * View
 */

llxHeader();

$tva = new Tva($db);
$form = new Form($db);

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/compta/payment_vat/card.php?id='.$id;
$head[$h][1] = $langs->trans("VATPayment");
$hselected = $h;
$h++;

/*$head[$h][0] = DOL_URL_ROOT.'/compta/payment_sc/info.php?id='.$id;
$head[$h][1] = $langs->trans("Info");
$h++;
*/


print dol_get_fiche_head($head, $hselected, $langs->trans("VATPayment"), -1, 'payment');

/*
 * Deletion confirmation of payment
 */
if ($action == 'delete') {
	print $form->formconfirm('card.php?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete', '', 0, 2);
}

/*
 * Validation confirmation of payment
 */
/*
if ($action == 'valide')
{
	$facid = $_GET['facid'];
	print $form->formconfirm('card.php?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);

}
*/


$linkback = '<a href="'.DOL_URL_ROOT.'/compta/tva/payments.php">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'id', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Date
print '<tr><td>'.$langs->trans('Date').'</td><td>'.dol_print_date($object->datep, 'day').'</td></tr>';

// Mode
print '<tr><td>'.$langs->trans('Mode').'</td><td>'.$langs->trans("PaymentType".$object->type_code).'</td></tr>';

// Numero
print '<tr><td>'.$langs->trans('Numero').'</td><td>'.dol_escape_htmltag($object->num_payment).'</td></tr>';

// Montant
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note
print '<tr><td>'.$langs->trans('Note').'</td><td class="wordbreak sensiblehtmlcontent">'.dol_string_onlythesehtmltags(dol_htmlcleanlastbr($object->note_private)).'</td></tr>';

// Bank account
if (isModEnabled("banque")) {
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

print '</div>';

print dol_get_fiche_end();


/*
 * List of social contributions payed
 */

$disable_delete = 0;
$sql = 'SELECT f.rowid as scid, f.label as label, f.paye, f.amount as tva_amount, pf.amount';
$sql .= ' FROM '.MAIN_DB_PREFIX.'payment_vat as pf,'.MAIN_DB_PREFIX.'tva as f';
$sql .= ' WHERE pf.fk_tva = f.rowid';
$sql .= ' AND f.entity = '.$conf->entity;
$sql .= ' AND pf.rowid = '.((int) $object->id);

dol_syslog("compta/payment_vat/card.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('VATDeclaration').'</td>';
	//print '<td>'.$langs->trans('Type').'</td>';
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
			$tva->fetch($objp->scid);
			print $tva->getNomUrl(1);
			print "</td>\n";
			// Type
			/* print '<td>';
			print $tva->type_label;4
			print "</td>\n";*/
			// Label
			print '<td>'.$objp->label.'</td>';
			// Expected to pay
			print '<td class="right"><span class="amount">'.price($objp->tva_amount).'</span></td>';
			// Status
			print '<td class="center">'.$tva->getLibStatut(4, $objp->amount).'</td>';
			// Amount payed
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
 * Boutons Actions
 */
print '<div class="tabsAction">';

/*
if (!empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
{
	if ($user->socid == 0 && $object->statut == 0 && $_GET['action'] == '')
	{
		if ($user->hasRight('facture', 'paiement')) {
			print '<a class="butAction" href="card.php?id='.GETPOST('id', 'int').'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}
*/

if ($action == '') {
	if ($user->hasRight('tax', 'charges', 'supprimer')) {
		if (!$disable_delete) {
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', 1);
		} else {
			print dolGetButtonAction($langs->trans("CantRemovePaymentVATPaid"), $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', 0);
		}
	}
}

print '</div>';

// End of page
llxFooter();
$db->close();
