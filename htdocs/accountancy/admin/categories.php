<?php
/* Copyright (C) 2016       Jamal Elbaz         <jamelbaz@gmail.pro>
 * Copyright (C) 2017-2024  Alexandre Spangaro  <aspangaro@easya.solutions>
 * Copyright (C) 2022       Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

$error = 0;

// Load translation files required by the page
$langs->loadLangs(array("bills", "accountancy", "compta"));

$id = GETPOSTINT('id');
$cancel = GETPOST('cancel', 'alpha');
$action = GETPOST('action', 'aZ09');
$cat_id = GETPOSTINT('account_category');
$selectcpt = GETPOST('cpt_bk', 'array');
$cpt_id = GETPOSTINT('cptid');

if ($cat_id == 0) {
	$cat_id = null;
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortfield)) {
	$sortfield = 'account_number';
}
if (empty($sortorder)) {
	$sortorder = 'ASC';
}

// Security check
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

$accountingcategory = new AccountancyCategory($db);


/*
 * Actions
 */

// If we add account
if (!empty($selectcpt)) {
	$cpts = array();
	foreach ($selectcpt as $selectedoption) {
		if (!array_key_exists($selectedoption, $cpts)) {
			$cpts[$selectedoption] = "'".$selectedoption."'";
		}
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

$title= $langs->trans('AccountingCategory');
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/categories_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
$titlepicto = 'setup';

print load_fiche_titre($langs->trans('AccountingCategory'), $linkback, $titlepicto);

print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="display">';

print dol_get_fiche_head();

print '<table class="border centpercent">';

// Select the category
print '<tr><td class="titlefield">'.$langs->trans("AccountingCategory").'</td>';
print '<td>';
$s = $formaccounting->select_accounting_category($cat_id, 'account_category', 1, 0, 0, 0);
if ($formaccounting->nbaccounts_category <= 0) {
	print '<span class="opacitymedium">'.$s.'</span>';
} else {
	print $s;
	print '<input type="submit" class="button small" value="'.$langs->trans("Select").'">';
}
print '</td></tr>';

print '</table>';

print dol_get_fiche_end();


// Select the accounts
if (!empty($cat_id)) {
	$return = $accountingcategory->getAccountsWithNoCategory($cat_id);
	if ($return < 0) {
		setEventMessages(null, $accountingcategory->errors, 'errors');
	}
	print '<br>';

	$arraykeyvalue = array();
	foreach ($accountingcategory->lines_cptbk as $key => $val) {
		$doc_ref = !empty($val->doc_ref) ? $val->doc_ref : '';
		$arraykeyvalue[length_accountg($val->numero_compte)] = length_accountg($val->numero_compte) . ' - ' . $val->label_compte . ($doc_ref ? ' '.$doc_ref : '');
	}

	if (is_array($accountingcategory->lines_cptbk) && count($accountingcategory->lines_cptbk) > 0) {
		print img_picto($langs->trans("AccountingAccount"), 'accounting_account', 'class="pictofixedwith"');
		print $form->multiselectarray('cpt_bk', $arraykeyvalue, GETPOST('cpt_bk', 'array'), 0, 0, '', 0, "80%", '', '', $langs->transnoentitiesnoconv("AddAccountFromBookKeepingWithNoCategories"));
		print '<input type="submit" class="button button-add small" id="" class="action-delete" value="'.$langs->trans("Add").'"> ';
	}
}

print '</form>';


if ((empty($action) || $action == 'display' || $action == 'delete') && $cat_id > 0) {
	$param = 'account_category='.((int) $cat_id);

	print '<br>';
	print '<table class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">';
	print getTitleFieldOfList('AccountAccounting', 0, $_SERVER['PHP_SELF'], 'account_number', '', $param, '', $sortfield, $sortorder, '')."\n";
	print getTitleFieldOfList('Label', 0, $_SERVER['PHP_SELF'], 'label', '', $param, '', $sortfield, $sortorder, '')."\n";
	print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '')."\n";
	print '</tr>'."\n";

	if (!empty($cat_id)) {
		$return = $accountingcategory->display($cat_id); // This load ->lines_display
		if ($return < 0) {
			setEventMessages(null, $accountingcategory->errors, 'errors');
		}

		if (is_array($accountingcategory->lines_display) && count($accountingcategory->lines_display) > 0) {
			$accountingcategory->lines_display = dol_sort_array($accountingcategory->lines_display, $sortfield, $sortorder, -1, 0, 1);

			foreach ($accountingcategory->lines_display as $cpt) {
				print '<tr class="oddeven">';
				print '<td>'.length_accountg($cpt->account_number).'</td>';
				print '<td>'.$cpt->label.'</td>';
				print '<td class="right">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&account_category='.$cat_id.'&cptid='.$cpt->rowid.'">';
				print $langs->trans("DeleteFromCat");
				print img_picto($langs->trans("DeleteFromCat"), 'unlink', 'class="paddingleft"');
				print "</a>";
				print "</td>";
				print "</tr>\n";
			}
		} else {
			print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
		}
	}

	print "</table>";
}

// End of page
llxFooter();
$db->close();
