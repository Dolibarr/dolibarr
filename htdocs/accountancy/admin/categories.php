<?php
/* Copyright (C) 2013-2016  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2026  Alexandre Spangaro  <alexandre@inovea-conseil.com>
 * Copyright (C) 2014-2015  Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2019       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2024-2026  MDW                 <mdeweerd@users.noreply.github.com>
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

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

$error = 0;

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "admin", "bills", "compta"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOSTINT('id');
$catid = GETPOSTINT('catid'); // Alternative parameter name for consistency
if (empty($id) && !empty($catid)) {
	$id = $catid;
}
$account_id = GETPOSTINT('account_id');
$cpt_id = GETPOSTINT('cpt'); // For compatibility with old system

// Security check
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

// Initialize objects
$accountingcategory = new AccountancyCategory($db);
$form = new Form($db);
$formaccounting = new FormAccounting($db);

// Load category
if ($id > 0) {
	$result = $accountingcategory->fetch($id);
	if ($result < 0) {
		setEventMessages($accountingcategory->error, $accountingcategory->errors, 'errors');
	}
}

// Parameters for list (new system only)
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;

// Search filters (new system only)
$search_account = GETPOST('search_account', 'alpha');
$search_label = GETPOST('search_label', 'alpha');

if (empty($sortfield)) {
	$sortfield = "aa.account_number";
}
if (empty($sortorder)) {
	$sortorder = "ASC";
}

// Use new multi report system
$useNewSystem = (getDolGlobalInt('ACCOUNTING_ENABLE_MULTI_REPORT'));

/*
 * Actions
 */

if ($useNewSystem) {
	// Remove filter
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_account = '';
		$search_label = '';
	}

	// Add account to category
	if ($action == 'add_account' && !empty($account_id)) {
		$result = $accountingcategory->addAccountToCategory($account_id);

		if ($result > 0) {
			setEventMessages($langs->trans("AccountAddedToCategory"), null, 'mesgs');
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		} elseif ($result == 0) {
			setEventMessages($langs->trans("AccountAlreadyInCategory"), null, 'warnings');
		} else {
			setEventMessages($accountingcategory->error, $accountingcategory->errors, 'errors');
		}
	}

	// Remove account from category
	if ($action == 'confirm_delete' && $confirm == 'yes' && !empty($account_id)) {
		$result = $accountingcategory->deleteAccountFromCategory($account_id);

		if ($result > 0) {
			setEventMessages($langs->trans("AccountRemovedFromCategory"), null, 'mesgs');
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		} else {
			setEventMessages($accountingcategory->error, $accountingcategory->errors, 'errors');
		}
	}

	// Add multiple accounts
	if ($action == 'add_multiple' && GETPOST('accounts_to_add', 'array')) {
		$accounts_to_add = GETPOST('accounts_to_add', 'array');

		$result = $accountingcategory->addMultipleAccountsToCategory($accounts_to_add);

		if ($result > 0) {
			setEventMessages($langs->trans("XAccountsAdded", $result), null, 'mesgs');
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		} elseif ($result < 0) {
			setEventMessages($accountingcategory->error, $accountingcategory->errors, 'errors');
		} else {
			setEventMessages($langs->trans("NoAccountAdded"), null, 'warnings');
		}
	}

	// Remove all accounts
	if ($action == 'confirm_remove_all' && $confirm == 'yes') {
		$result = $accountingcategory->removeAllAccountsFromCategory();

		if ($result >= 0) {
			setEventMessages($langs->trans("XAccountsRemoved", $result), null, 'mesgs');
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		} else {
			setEventMessages($accountingcategory->error, $accountingcategory->errors, 'errors');
		}
	}
} else {
	// OLD SYSTEM: One-to-many via fk_accounting_category field
	if ($action == 'clean') {
		$result = $accountingcategory->deleteCptCat($cpt_id);
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		} else {
			setEventMessages($accountingcategory->error, $accountingcategory->errors, 'errors');
		}
	}
}

/*
 * View
 */

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilité_en_Partie_Double#Configuration';
llxHeader('', $langs->trans("AccountingCategory"), $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-admin-categories');

if ($useNewSystem) {
	// ============================================================================
	// NEW SYSTEM VIEW
	// ============================================================================

	// Confirmation dialogs
	if ($action == 'delete') {
		print $form->formconfirm(
			$_SERVER["PHP_SELF"].'?id='.$id.'&account_id='.$account_id,
			$langs->trans('RemoveAccountFromCategory'),
			$langs->trans('ConfirmRemoveAccountFromCategory'),
			'confirm_delete',
			'',
			0,
			1
		);
	}

	if ($action == 'remove_all') {
		print $form->formconfirm(
			$_SERVER["PHP_SELF"].'?id='.$id,
			$langs->trans('RemoveAllAccountsFromCategory'),
			$langs->trans('ConfirmRemoveAllAccountsFromCategory'),
			'confirm_remove_all',
			'',
			0,
			1
		);
	}

	// Page title
	$title = $langs->trans("AccountingCategory").': '.$accountingcategory->label;
	print load_fiche_titre($title, '', 'title_accountancy');

	// Category information card
	print dol_get_fiche_head();

	print '<table class="border centpercent">';
	print '<tr><td class="titlefield">'.$langs->trans("Code").'</td><td>'.$accountingcategory->code.'</td></tr>';
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$accountingcategory->label.'</td></tr>';
	if (!empty($accountingcategory->range_account)) {
		print '<tr><td>'.$langs->trans("Comment").'</td><td>'.$accountingcategory->range_account.'</td></tr>';
	}
	print '<tr><td>'.$langs->trans("AccountsLinked").'</td><td><strong>'.$accountingcategory->countAccountsInCategory().'</strong></td></tr>';
	print '</table>';

	print dol_get_fiche_end();

	// Build SQL for linked accounts
	$sql = "SELECT aa.rowid, aa.account_number, aa.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_category_account as aca";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = aca.fk_accounting_account";
	$sql .= " WHERE aca.fk_accounting_category = ".((int) $id);
	$sql .= " AND aa.entity = ".$conf->entity;

	// Search filters
	if (!empty($search_account)) {
		$sql .= natural_search("aa.account_number", $search_account);
	}
	if (!empty($search_label)) {
		$sql .= natural_search("aa.label", $search_label);
	}

	// Count total
	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		$resql = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($resql);
		if (($page * $limit) > $nbtotalofrecords) {
			$page = 0;
			$offset = 0;
		}
	}

	// Add sort and limit
	$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);

	// Parameters
	$param = '&id='.$id;
	if (!empty($search_account)) {
		$param .= '&search_account='.urlencode($search_account);
	}
	if (!empty($search_label)) {
		$param .= '&search_label='.urlencode($search_label);
	}

	// Form to add accounts
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add_multiple">';
	print '<input type="hidden" name="id" value="'.$id.'">';

	// Get available accounts
	$availableAccounts = $accountingcategory->getAvailableAccountsForCategory();

	if (count($availableAccounts) > 0) {
		print '<table class="border centpercent">';
		print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("AddAccountsToCategory").": ".'</td><td>';

		// Prepare array for multiselectarray
		$arrayofaccounts = array();
		foreach ($availableAccounts as $account) {
			$arrayofaccounts[$account['id']] = $account['account_number'].' - '.$account['label'];
		}

		// Use Dolibarr native multiselect with search
		print $form->multiselectarray('accounts_to_add', $arrayofaccounts, array(), 0, 0, '', 0, '100%', '', '', $langs->trans("SelectAccountsToAdd"));

		print '</td></tr>';
		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button button-save" value="'.$langs->trans("Add").'">';
		print ' &nbsp; ';
		print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
		print '</div>';
	} else {
		print '<div class="info">'.$langs->trans("AllAccountsAlreadyLinked").'</div>';
	}

	print '</form>';

	// List of linked accounts
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	$massactionbutton = '';
	$title = $langs->trans("AccountsLinked");

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'accountancy', 0, '', '', $limit);
	print '<div class="div-table-responsive">';
	print '<table class="tagtable nobottomiftotal liste">';

	// Search filters row
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_account" value="'.dol_escape_htmltag($search_account).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth200" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	// Header row
	print '<tr class="liste_titre">';
	print_liste_field_titre("AccountNumber", $_SERVER["PHP_SELF"], "aa.account_number", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "aa.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center ');
	print '</tr>';

	// Data rows
	$i = 0;
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		// Account number
		print '<td>';
		print '<a href="'.DOL_URL_ROOT.'/accountancy/admin/card.php?id='.$obj->rowid.'">';
		print $obj->account_number;
		print '</a>';
		print '</td>';

		// Label
		print '<td>';
		print dol_escape_htmltag($obj->label);
		print '</td>';

		// Actions
		print '<td class="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$id.'&account_id='.$obj->rowid.'" class="reposition">';
		print img_delete();
		print '</a>';
		print '</td>';

		print '</tr>';
		$i++;
	}

	if ($num == 0) {
		print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoAccountLinked").'</span></td></tr>';
	}

	print '</table>';
	print '</div>';
	print '</form>';

	// Button to remove all
	if ($num > 0) {
		print '<div class="tabsAction">';
		print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=remove_all&token='.newToken().'&id='.$id.'">'.$langs->trans("RemoveAllAccountsFromCategory").'</a>';
		print '</div>';
	}
} else {
	// ============================================================================
	// OLD SYSTEM VIEW (compatibility)
	// ============================================================================

	$listcpt = $accountingcategory->display($id);
	$listcptNotIn = $accountingcategory->getAccountsWithNoCategory($id);

	print load_fiche_titre($langs->trans('AccountingCategory')." : ".$accountingcategory->label);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="clean">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("AccountNumber").'</td>';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td></td>';
	print "</tr>\n";

	if (is_array($listcpt) && count($listcpt) > 0) {
		foreach ($listcpt as $cpt) {
			print '<tr class="oddeven">';
			print '<td>'.length_accountg($cpt->account_number).'</td>';
			print '<td>'.$cpt->label.'</td>';
			print '<td>';
			print '<input type="submit" class="button smallpaddingimp" name="cpt" value="'.$cpt->rowid.'" title="'.$langs->trans("DeleteFromCat").'">';
			print img_picto($langs->trans("DeleteFromCat"), 'unlink', 'class="paddingleft"');
			print "</td>";
			print "</tr>\n";
		}
	} else {
		print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print '</table>';
	print '</form>';
}

// End of page
llxFooter();
$db->close();
