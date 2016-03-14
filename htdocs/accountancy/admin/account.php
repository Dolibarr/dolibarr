<?php
/* Copyright (C) 2013-2016 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 * \file 		htdocs/accountancy/admin/account.php
 * \ingroup		Advanced accountancy
 * \brief		List accounting account
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';

// Langs
$langs->load("compta");
$langs->load("accountancy");

$mesg = '';
$action = GETPOST('action');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$search_account = GETPOST("search_account");
$search_label = GETPOST("search_label");
$search_accountparent = GETPOST("search_accountparent");
$search_pcgtype = GETPOST("search_pcgtype");
$search_pcgsubtype = GETPOST("search_pcgsubtype");

// Security check
if (! $user->admin)
	accessforbidden();

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'sortorder');
$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOST("page", 'int');
if ($page == - 1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield)
	$sortfield = "aa.account_number";
if (! $sortorder)
	$sortorder = "ASC";

if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

$accounting = new AccountingAccount($db);

if ($action == 'disable') {
	if ($accounting->fetch($id)) {
		$result = $accounting->account_desactivate($id);
	}
	
	$action = 'update';
	if ($result < 0) {
		setEventMessages($accounting->error, $accounting->errors, 'errors');
	}
} else if ($action == 'enable') {
	if ($accounting->fetch($id)) {
		$result = $accounting->account_activate($id);
	}
	$action = 'update';
	if ($result < 0) {
		setEventMessages($accounting->error, $accounting->errors, 'errors');
	}
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_account = "";
	$search_label = "";
	$search_accountparent = "";
	$search_pcgtype = "";
	$search_pcgsubtype = "";
}

/*
 * View
 *
 */
llxHeader('', $langs->trans("ListAccounts"));

$pcgver = $conf->global->CHARTOFACCOUNTS;

$sql = "SELECT aa.rowid, aa.fk_pcg_version, aa.pcg_type, aa.pcg_subtype, aa.account_number, aa.account_parent , aa.label, aa.active, ";
$sql .= " a2.rowid as rowid2, a2.label as label2, a2.account_number as account_number2";
$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as a2 ON aa.account_parent = a2.rowid";
$sql .= " WHERE asy.rowid = " . $pcgver;

if (strlen(trim($search_account))) {
	$sql .= " AND aa.account_number like '%" . $search_account . "%'";
}
if (strlen(trim($search_label))) {
	$sql .= " AND aa.label like '%" . $search_label . "%'";
}
if (strlen(trim($search_accountparent))) {
	$sql .= " AND aa.account_parent like '%" . $search_accountparent . "%'";
}
if (strlen(trim($search_pcgtype))) {
	$sql .= " AND aa.pcg_type like '%" . $search_pcgtype . "%'";
}
if (strlen(trim($search_pcgsubtype))) {
	$sql .= " AND aa.pcg_subtype like '%" . $search_pcgsubtype . "%'";
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/account.php:: $sql=' . $sql);
$result = $db->query($sql);

if ($result) {
	$num = $db->num_rows($result);
	
	print_barre_liste($langs->trans('ListAccounts'), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num);
	
	$i = 0;
	
	print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';
	
	print '<br/>';
	
	print '<a class="butAction" href="./card.php?action=create">' . $langs->trans("Addanaccount") . '</a>';
	print '<a class="butAction" href="./importaccounts.php">' . $langs->trans("ImportAccount") . '</a>';
	print '<a class="butAction" href="./productaccount.php">' . $langs->trans("CheckProductAccountancyCode") . '</a>';
	print '<br/><br/>';
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("AccountNumber"), $_SERVER["PHP_SELF"], "aa.account_number", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "aa.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Accountparent"), $_SERVER["PHP_SELF"], "aa.account_parent", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Pcgtype"), $_SERVER["PHP_SELF"], "aa.pcg_type", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Pcgsubtype"), $_SERVER["PHP_SELF"], "aa.pcg_subtype", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Activated"), $_SERVER["PHP_SELF"], "aa.active", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Action"), $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
	print '</tr>';
	
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_account" value="' . $search_account . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_label" value="' . $search_label . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_accountparent" value="' . $search_accountparent . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_pcgtype" value="' . $search_pcgtype . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_pcgsubtype" value="' . $search_pcgsubtype . '"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td align="right" colspan="2" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp;';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" name="button_removefilter" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';
	print '</tr>';
	
	$var = false;
	
	$accountstatic = new AccountingAccount($db);
	$accountparent = new AccountingAccount($db);
	
	while ( $i < min($num, $limit) ) {
		$obj = $db->fetch_object($resql);
		
		$accountstatic->id = $obj->rowid;
		$accountstatic->label = $obj->label;
		$accountstatic->account_number = $obj->account_number;
		
		print '<tr ' . $bc[$var] . '>';
		print '<td>' . $accountstatic->getNomUrl(1) . '</td>';
		print '<td>' . $obj->label . '</td>';

		if ($obj->account_parent)
        {
			$accountparent->id = $obj->rowid2;
			$accountparent->label = $obj->label2;
			$accountparent->account_number = $obj->account_number2;
		
			print '<td>' . $accountparent->getNomUrl(1) . '</td>';
		}
		else
		{
			print '<td>&nbsp;</td>';
		}
		print '<td>' . $obj->pcg_type . '</td>';
		print '<td>' . $obj->pcg_subtype . '</td>';
		print '<td>';
		if (empty($obj->active)) {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $obj->rowid . '&action=enable">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		} else {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $obj->rowid . '&action=disable">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a>';
		}
		print '</td>';
		
		// Action
		print '<td align="center">';
		if ($user->admin) {
			print '<a href="./card.php?action=update&id=' . $obj->rowid . '">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a href="./card.php?action=delete&id=' . $obj->rowid . '">';
			print img_delete();
			print '</a>';
		}
		print '</td>' . "\n";
		
		print "</tr>\n";
		$var = ! $var;
		$i ++;
	}
	
	print "</table>";
	print '</form>';
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
