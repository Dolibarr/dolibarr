<?php
/* Copyright (C) 2016		Jamal Elbaz			<jamelbaz@gmail.pro>
 * Copyright (C) 2017		Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 * \file	htdocs/accountancy/admin/categories.php
 * \ingroup Accountancy (Double entries)
 * \brief	Page to assign mass categories to accounts
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

$error = 0;

// Load translation files required by the page
$langs->loadLangs(array("bills", "accountancy"));

$mesg = '';
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel', 'alpha');
$action = GETPOST('action', 'aZ09');
$cat_id = GETPOST('account_category', 'int');
$selectcpt = GETPOST('cpt_bk', 'array');
$cpt_id = GETPOST('cptid', 'int');

if ($cat_id == 0) {
	$cat_id = null;
}

// Security check
if (empty($user->rights->accounting->chartofaccount))
{
	accessforbidden();
}

$accountingcategory = new AccountancyCategory($db);


/*
 * Actions
 */

// si ajout de comptes
if (!empty($selectcpt)) {
	$cpts = array();
	foreach ($selectcpt as $selectedoption) {
		if (!array_key_exists($selectedoption, $cpts))
			$cpts[$selectedoption] = "'".$selectedoption."'";
	}

	$return = $accountingcategory->updateAccAcc($cat_id, $cpts);

	if ($return < 0) {
		setEventMessages($langs->trans('errors'), $accountingcategory->errors, 'errors');
	} else {
		setEventMessages($langs->trans('RecordModifiedSuccessfully'), null, 'mesgs');
	}
}
if ($action == 'delete') {
	if ($cpt_id) {
		if ($accountingcategory->deleteCptCat($cpt_id)) {
			setEventMessages($langs->trans('AccountRemovedFromGroup'), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('errors'), null, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

llxheader('', $langs->trans('AccountingCategory'));

$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/categories_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

print load_fiche_titre($langs->trans('AccountingCategory'), $linkback);

print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="display">';

dol_fiche_head();

print '<table class="border centpercent">';

// Select the category
print '<tr><td class="titlefield">'.$langs->trans("AccountingCategory").'</td>';
print '<td>';
$formaccounting->select_accounting_category($cat_id, 'account_category', 1, 0, 0, 1);
print '<input class="button" type="submit" value="'.$langs->trans("Select").'">';
print '</td></tr>';

// Select the accounts
if (!empty($cat_id))
{
	$return = $accountingcategory->getAccountsWithNoCategory($cat_id);
	if ($return < 0) {
		setEventMessages(null, $accountingcategory->errors, 'errors');
	}
	print '<tr><td>'.$langs->trans("AddAccountFromBookKeepingWithNoCategories").'</td>';
	print '<td>';

	$arraykeyvalue = array();
	foreach ($accountingcategory->lines_cptbk as $key => $val)
	{
		$arraykeyvalue[length_accountg($val->numero_compte)] = length_accountg($val->numero_compte).' ('.$val->label_compte.($val->doc_ref ? ' '.$val->doc_ref : '').')';
	}

	if (is_array($accountingcategory->lines_cptbk) && count($accountingcategory->lines_cptbk) > 0) {
		print $form->multiselectarray('cpt_bk', $arraykeyvalue, GETPOST('cpt_bk', 'array'), null, null, null, null, "90%");
		print '<br>';
		/*print '<select class="flat minwidth200" size="8" name="cpt_bk[]" multiple>';
		foreach ( $accountingcategory->lines_cptbk as $cpt ) {
			print '<option value="' . length_accountg($cpt->numero_compte) . '">' . length_accountg($cpt->numero_compte) . ' (' . $cpt->label_compte . ' ' . $cpt->doc_ref . ')</option>';
		}
		print '</select><br>';
		print ajax_combobox('cpt_bk');
		*/
		print '<input class="button" type="submit" id="" class="action-delete" value="'.$langs->trans("Add").'"> ';
	}
	print '</td></tr>';
}

print '</table>';

dol_fiche_end();

print '</form>';


if ($action == 'display' || $action == 'delete') {
    print "<table class='noborder' width='100%'>\n";
    print '<tr class="liste_titre">';
	print '<td class="liste_titre">'.$langs->trans("AccountAccounting")."</td>";
	print '<td class="liste_titre" colspan="2">'.$langs->trans("Label")."</td>";
	print "</tr>\n";

	if (!empty($cat_id)) {
		$return = $accountingcategory->display($cat_id); // This load ->lines_display
		if ($return < 0) {
			setEventMessages(null, $accountingcategory->errors, 'errors');
		}

		if (is_array($accountingcategory->lines_display) && count($accountingcategory->lines_display) > 0) {
			foreach ($accountingcategory->lines_display as $cpt) {
				print '<tr class="oddeven">';
				print '<td>'.length_accountg($cpt->account_number).'</td>';
				print '<td>'.$cpt->label.'</td>';
				print '<td class="right">';
				print "<a href= '".$_SERVER['PHP_SELF']."?action=delete&account_category=".$cat_id."&cptid=".$cpt->rowid."'>";
				print $langs->trans("DeleteFromCat");
				print img_picto($langs->trans("DeleteFromCat"), 'unlink');
				print "</a>";
				print "</td>";
				print "</tr>\n";
			}
		}
	}

	print "</table>";
}

// End of page
llxFooter();
$db->close();
