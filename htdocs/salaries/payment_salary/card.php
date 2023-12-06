<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *	    \file       htdocs/salaries/payment_salary/card.php
 *		\ingroup    salary
 *		\brief      Tab to pay a salary
 *		\remarks	File very similar with fournisseur/paiement/card.php
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'companies', 'salaries'));

// Security check
$id = GETPOST("id", 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
if ($user->socid) {
	$socid = $user->socid;
}

$salary = new Salary($db);

$object = new PaymentSalary($db);
if ($id > 0) {
	$result = $object->fetch($id);
	if (!$result) {
		dol_print_error($db, 'Failed to get payment id '.$id);
	}
}
restrictedArea($user, 'salaries', $object->fk_salary, 'salary', '');	// $object is payment of salary


/*
 * Actions
 */

// Delete payment
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('salaries', 'delete')) {
	$db->begin();

	$result = $object->delete($user);
	if ($result > 0) {
		$db->commit();
		header("Location: ".DOL_URL_ROOT."/salaries/payments.php");
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'setdatep' && GETPOST('datepday') && $user->hasRight('salaries', 'write')) {
	$datepaye = dol_mktime(GETPOST('datephour', 'int'), GETPOST('datepmin', 'int'), GETPOST('datepsec', 'int'), GETPOST('datepmonth', 'int'), GETPOST('datepday', 'int'), GETPOST('datepyear', 'int'), 'tzuserrel');
	$res = $object->updatePaymentDate($datepaye);
	if ($res === 0) {
		setEventMessages($langs->trans('PaymentDateUpdateSucceeded'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('PaymentDateUpdateFailed'), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("SalaryPayment"));

$h = 0;

$head = array();

$head[$h][0] = DOL_URL_ROOT.'/salaries/payment_salary/card.php?id='.$id;
$head[$h][1] = $langs->trans("SalaryPayment");
$hselected = $h;
$h++;

/*
$head[$h][0] = DOL_URL_ROOT.'/compta/payment_sc/info.php?id='.$id;
$head[$h][1] = $langs->trans("Info");
$h++;
*/


print dol_get_fiche_head($head, $hselected, $langs->trans("SalaryPayment"), -1, 'payment');

/*
 * Deletion confirmation of payment
 */
if ($action == 'delete') {
	print $form->formconfirm('card.php?id='.$object->id, $langs->trans("DeleteSalary"), $langs->trans("ConfirmDeleteSalaryPayment"), 'confirm_delete', '', 0, 2);
}

/*
 * Validation confirmation of payment
 */
/*
if ($action == 'valide')
{
	$facid = GETPOST('facid', 'int');
	print $form->formconfirm('card.php?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);

}
*/


$linkback = '<a href="'.DOL_URL_ROOT.'/salaries/payments.php">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'id', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

// Ref
/*print '<tr><td class="titlefield">'.$langs->trans('Ref').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'id','',1,'rowid','id');
print '</td></tr>';*/

// Date
print '<tr><td>';
print $form->editfieldkey("Date", 'datep', $object->datep, $object, 1, 'datehourpicker');
print '</td><td>';
print $form->editfieldval("Date", 'datep', $object->datep, $object, 1, 'datehourpicker', '', null, null, '', 0, '', 'id', 'tzuserrel', array('addnowlink'=>1));
print "</td>";
print '</tr>';

// Mode
print '<tr><td>'.$langs->trans('Mode').'</td><td>';
print $langs->trans("PaymentType".$object->type_code);
print '</td></tr>';

// Numero
print '<tr><td>'.$langs->trans('Numero').'</td><td>'.dol_escape_htmltag($object->num_payment).'</td></tr>';

// Montant
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note
print '<tr><td>'.$langs->trans('Note').'</td><td class="valeur sensiblehtmlcontent">'.dol_string_onlythesehtmltags(dol_htmlcleanlastbr($object->note_private)).'</td></tr>';

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
 * List of salaries payed
 */

$disable_delete = 0;
$sql = 'SELECT f.rowid as scid, f.label, f.paye, f.amount as sc_amount, ps.amount';
$sql .= ' FROM '.MAIN_DB_PREFIX.'payment_salary as ps,'.MAIN_DB_PREFIX.'salary as f';
$sql .= ' WHERE ps.fk_salary = f.rowid';
$sql .= ' AND f.entity = '.$conf->entity;
$sql .= ' AND ps.rowid = '.((int) $object->id);

dol_syslog("payment_salary/card.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br>';

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Salary').'</td>';
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
			$salary->fetch($objp->scid);
			print $salary->getNomUrl(1);
			print "</td>\n";
			// Label
			print '<td>'.$objp->label.'</td>';
			// Expected to pay
			print '<td class="right">'.price($objp->sc_amount).'</td>';
			// Status
			print '<td class="center">'.$salary->getLibStatut(4, $objp->amount).'</td>';
			// Amount payed
			print '<td class="right">'.price($objp->amount).'</td>';
			print "</tr>\n";
			if ($objp->paye == 1) {
				// If at least one invoice is paid, disable delete
				$disable_delete = 1;
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}

	print "</table>\n";
	print "</div>";

	$db->free($resql);
} else {
	dol_print_error($db);
}



/*
 * Button actions
 */

print '<div class="tabsAction">';

if ($action == '') {
	if ($user->hasRight('salaries', 'delete')) {
		if (!$disable_delete) {
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', 1);
		} else {
			print dolGetButtonAction($langs->trans("CantRemovePaymentSalaryPaid"), $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', 0);
		}
	}
}

print '</div>';

// End of page
llxFooter();
$db->close();
