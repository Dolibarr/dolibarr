<?php
/* Copyright (C) 2016	   Jamal Elbaz		<jamelbaz@gmail.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/accountancy/admin/categories.php
 * \ingroup Advanced accountancy
 * \brief Page to assign mass categories to accounts
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

$error = 0;

// Langs
$langs->load("bills");
$langs->load("accountancy");

$mesg = '';
$action = GETPOST('action');
$cat_id = GETPOST('account_category');
$selectcpt = GETPOST('cpt_bk', 'array');
$cpt_id = GETPOST('cptid');

if ($cat_id == 0) {
	$cat_id = null;
}

$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel');

// Security check
if (! empty($user->rights->accountancy->chartofaccount))
{
	accessforbidden();
}

$AccCat = new AccountancyCategory($db);

// si ajout de comptes
if (! empty($selectcpt)) {
	$cpts = array ();
	foreach ( $selectcpt as $selectedOption ) {
		if (! array_key_exists($selectedOption, $cpts))
			$cpts[$selectedOption] = "'" . $selectedOption . "'";
	}

	$return= $AccCat->updateAccAcc($cat_id, $cpts);

	if ($return<0) {
		setEventMessages($langs->trans('errors'), $AccCat->errors, 'errors');
	} else {
		setEventMessages($langs->trans('Saved'), null, 'mesgs');
	}
}
if ($action == 'delete') {
	if ($cpt_id) {
		if ($AccCat->deleteCptCat($cpt_id)) {
			setEventMessages($langs->trans('Deleted'), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('errors'), null, 'errors');
		}
	}
}


/*
 * View
 */

llxheader('', $langs->trans('AccountAccounting'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

print load_fiche_titre($langs->trans('AccountingCategory'));

print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="display">';

dol_fiche_head();

print '<table class="border" width="100%">';
// Category
print '<tr><td>' . $langs->trans("AccountingCategory") . '</td>';
print '<td>';
$formaccounting->select_accounting_category($cat_id, 'account_category', 1, 0, 0, 1);
print '<input class="button" type="submit" value="' . $langs->trans("Select") . '">';
print '</td></tr>';

if (! empty($cat_id)) 
{
	$return = $AccCat->getAccountsWithNoCategory($cat_id);
	if ($return < 0) {
		setEventMessages(null, $AccCat->errors, 'errors');
	}
	print '<tr><td>' . $langs->trans("AddCompteFromBK") . '</td>';
	print '<td>';
	if (is_array($AccCat->lines_cptbk) && count($AccCat->lines_cptbk) > 0) {
		print '<select class="flat minwidth200" size="' . count($obj) . '" name="cpt_bk[]" multiple>';
		foreach ( $AccCat->lines_cptbk as $cpt ) {
			print '<option value="' . length_accountg($cpt->numero_compte) . '">' . length_accountg($cpt->numero_compte) . ' (' . $cpt->label_compte . ' ' . $cpt->doc_ref . ')</option>';
		}
		print '</select><br><input class="button" type="submit" id="" class="action-delete" value="' . $langs->trans("Add") . '"> ';
	}
	print '</td></tr>';
}

print '</table>';

dol_fiche_end();

print '</form>';


if ($action == 'display' || $action == 'delete') {

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><th class="liste_titre">' . $langs->trans("AccountAccounting") . '</th><th class="liste_titre">' . $langs->trans("Description") . '</th><th class="liste_titre" width="60" align="center">Action</th></tr>';

	if (! empty($cat_id)) {
		$return = $AccCat->display($cat_id);
		if ($return < 0) {
			setEventMessages(null, $AccCat->errors, 'errors');
		}
		$j = 1;
		if (is_array($AccCat->lines_display) && count($AccCat->lines_display) > 0) {
			foreach ( $AccCat->lines_display as $cpt ) {
				$var = ! $var;
				print '<tr ' . $bc[$var] . '>';
				print '<td>' . length_accountg($cpt->account_number) . '</td>';
				print '<td>' . $cpt->label . '</td>';
				print $form->formconfirm($_SERVER["PHP_SELF"] . "?account_category=$cat_id&cptid=" . $cpt->rowid, $langs->trans("DeleteCptCategory"), $langs->trans("ConfirmDeleteCptCategory"), "delete", '', 0, "action-delete" . $j);
				print '<td>';
				//print img_delete();
				print '<input class="button" type="button" id="action-delete' . $j . '" value="' . $langs->trans("Delete") . '">';
				print '</td>';
				print "</tr>\n";
				$j ++;
			}
		}
	}

	print "</table>";
}

llxFooter();

$db->close();