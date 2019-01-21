<?php
/* Copyright (C) 2013-2016 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2016-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \ingroup     Advanced accountancy
 * \brief		List accounting account
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("admin");
$langs->load("accountancy");
$langs->load("salaries");

$mesg = '';
$action = GETPOST('action','aZ09');
$cancel = GETPOST('cancel','alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');

$search_account = GETPOST("search_account");
$search_label = GETPOST("search_label");
$search_accountparent = GETPOST("search_accountparent");
$search_pcgtype = GETPOST("search_pcgtype");
$search_pcgsubtype = GETPOST("search_pcgsubtype");

// Security check
if ($user->societe_id > 0) accessforbidden();
if (! $user->rights->accounting->chartofaccount) accessforbidden();

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield = "aa.account_number";
if (! $sortorder) $sortorder = "ASC";

$arrayfields=array(
    'aa.account_number'=>array('label'=>$langs->trans("AccountNumber"), 'checked'=>1),
    'aa.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	'aa.account_parent'=>array('label'=>$langs->trans("Accountparent"), 'checked'=>0),
    'aa.pcg_type'=>array('label'=>$langs->trans("Pcgtype"), 'checked'=>1, 'help'=>'PcgtypeDesc'),
    'aa.pcg_subtype'=>array('label'=>$langs->trans("Pcgsubtype"), 'checked'=>1, 'help'=>'PcgtypeDesc'),
	'aa.active'=>array('label'=>$langs->trans("Activated"), 'checked'=>1)
);

$accounting = new AccountingAccount($db);

// Initialize technical object to manage context to save list fields
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'accountingaccountlist';


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha')) { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if (! empty($cancel)) $action = '';

    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
    {
    	$search_account = "";
    	$search_label = "";
    	$search_accountparent = "";
    	$search_pcgtype = "";
    	$search_pcgsubtype = "";
		$search_array_options=array();
    }

    if (GETPOST('change_chart','alpha'))
    {
        $chartofaccounts = GETPOST('chartofaccounts', 'int');

        if ($chartofaccounts > 0)
        {
			// Get language code for this $chartofaccounts
			$sql ='SELECT code FROM '.MAIN_DB_PREFIX.'c_country as c, '.MAIN_DB_PREFIX.'accounting_system as a';
			$sql.=' WHERE c.rowid = a.fk_country AND a.rowid = '.(int) $chartofaccounts;
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				$country_code = $obj->code;
			}
			else dol_print_error($db);

			// Try to load sql file
			if ($country_code)
			{
				$sqlfile = DOL_DOCUMENT_ROOT.'/install/mysql/data/llx_accounting_account_'.strtolower($country_code).'.sql';
				$result = run_sql($sqlfile, 1, 0, 1);
			}

            if (! dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
                $error++;
            }
        } else {
            $error ++;
        }
    }

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
}


/*
 * View
 */

$form=new Form($db);

llxHeader('', $langs->trans("ListAccounts"));

if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

$pcgver = $conf->global->CHARTOFACCOUNTS;

$sql = "SELECT aa.rowid, aa.fk_pcg_version, aa.pcg_type, aa.pcg_subtype, aa.account_number, aa.account_parent , aa.label, aa.active, ";
$sql .= " a2.rowid as rowid2, a2.label as label2, a2.account_number as account_number2";
$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version AND aa.entity = " . $conf->entity;
// Dirty hack wainting that foreign key account_parent is an integer to be compared correctly with rowid
if ($db->type == 'pgsql') $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as a2 ON a2.rowid = CAST(aa.account_parent AS INTEGER) AND a2.entity = " . $conf->entity;
else $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as a2 ON a2.rowid = CAST(aa.account_parent AS UNSIGNED) AND a2.entity = " . $conf->entity;
$sql .= " WHERE asy.rowid = " . $pcgver;

if (strlen(trim($search_account)))			$sql .= natural_search("aa.account_number", $search_account);
if (strlen(trim($search_label)))			$sql .= natural_search("aa.label", $search_label);
if (strlen(trim($search_accountparent)))	$sql .= natural_search("aa.account_parent", $search_accountparent);
if (strlen(trim($search_pcgtype)))			$sql .= natural_search("aa.pcg_type", $search_pcgtype);
if (strlen(trim($search_pcgsubtype)))		$sql .= natural_search("aa.pcg_subtype", $search_pcgsubtype);
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/account.php:: $sql=' . $sql);
$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);

    $param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($search_account) $param.= '&search_account='.urlencode($search_account);
	if ($search_label) $param.= '&search_label='.urlencode($search_label);
	if ($search_accountparent) $param.= '&search_accountparent='.urlencode($search_accountparent);
	if ($search_pcgtype) $param.= '&search_pcgtype='.urlencode($search_pcgtype);
	if ($search_pcgsubtype) $param.= '&search_pcgsubtype='.urlencode($search_pcgsubtype);
    if ($optioncss != '') $param.='&optioncss='.$optioncss;


	print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$htmlbuttonadd = '<a class="butAction" href="./card.php?action=create">' . $langs->trans("Addanaccount") . '</a>';

	print_barre_liste($langs->trans('ListAccounts'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, $htmlbuttonadd, '', $limit);

	// Box to select active chart of account
    print $langs->trans("Selectchartofaccounts") . " : ";
    print '<select class="flat" name="chartofaccounts" id="chartofaccounts">';
    $sql = "SELECT a.rowid, a.pcg_version, a.label, a.active, c.code as country_code";
    $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_system as a";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as c ON a.fk_country = c.rowid";
    $sql .= " WHERE a.active = 1";
    dol_syslog('accountancy/admin/account.php $sql='.$sql);
    print $sql;
    $resqlchart = $db->query($sql);
    if ($resqlchart) {
        $numbis = $db->num_rows($resqlchart);
        $i = 0;
        while ($i < $numbis) {
            $obj = $db->fetch_object($resqlchart);

            print '<option value="' . $obj->rowid . '"';
            print ($pcgver == $obj->rowid) ? ' selected' : '';
            print '>' . $obj->pcg_version . ' - ' . $obj->label . ' - (' . $obj->country_code . ')</option>';

            $i++;
        }
    }
    else dol_print_error($db);
    print "</select>";
    print ajax_combobox("chartofaccounts");
    print '<input type="submit" class="button" name="change_chart" value="'.dol_escape_htmltag($langs->trans("ChangeAndLoad")).'">';

    print '<br>';
	print '<br>';

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Line for search fields
	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['aa.account_number']['checked']))	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_account" value="' . $search_account . '"></td>';
	if (! empty($arrayfields['aa.label']['checked']))			print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="' . $search_label . '"></td>';
	if (! empty($arrayfields['aa.account_parent']['checked']))	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_accountparent" value="' . $search_accountparent . '"></td>';
	if (! empty($arrayfields['aa.pcg_type']['checked']))		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_pcgtype" value="' . $search_pcgtype . '"></td>';
	if (! empty($arrayfields['aa.pcg_subtype']['checked']))		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_pcgsubtype" value="' . $search_pcgsubtype . '"></td>';
	if (! empty($arrayfields['aa.active']['checked']))			print '<td class="liste_titre">&nbsp;</td>';
	print '<td align="right" class="liste_titre">';
	$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print '</tr>';

    print '<tr class="liste_titre">';
	if (! empty($arrayfields['aa.account_number']['checked']))	print_liste_field_titre($arrayfields['aa.account_number']['label'], $_SERVER["PHP_SELF"],"aa.account_number","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['aa.label']['checked']))			print_liste_field_titre($arrayfields['aa.label']['label'], $_SERVER["PHP_SELF"],"aa.label","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['aa.account_parent']['checked']))	print_liste_field_titre($arrayfields['aa.account_parent']['label'], $_SERVER["PHP_SELF"],"aa.account_parent", "", $param,'align="left"',$sortfield,$sortorder);
	if (! empty($arrayfields['aa.pcg_type']['checked']))		print_liste_field_titre($arrayfields['aa.pcg_type']['label'],$_SERVER["PHP_SELF"],'aa.pcg_type','',$param,'',$sortfield,$sortorder,'',$arrayfields['aa.pcg_type']['help']);
	if (! empty($arrayfields['aa.pcg_subtype']['checked']))		print_liste_field_titre($arrayfields['aa.pcg_subtype']['label'],$_SERVER["PHP_SELF"],'aa.pcg_subtype','',$param,'',$sortfield,$sortorder,'',$arrayfields['aa.pcg_subtype']['help']);
	if (! empty($arrayfields['aa.active']['checked']))			print_liste_field_titre($arrayfields['aa.active']['label'],$_SERVER["PHP_SELF"],'aa.active','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	$accountstatic = new AccountingAccount($db);
	$accountparent = new AccountingAccount($db);

	$i=0;
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$accountstatic->id = $obj->rowid;
		$accountstatic->label = $obj->label;
		$accountstatic->account_number = $obj->account_number;

		print '<tr class="oddeven">';

		// Account number
		if (! empty($arrayfields['aa.account_number']['checked']))
		{
			print "<td>";
			print $accountstatic->getNomUrl(1);
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Account label
		if (! empty($arrayfields['aa.label']['checked']))
		{
			print "<td>";
			print $obj->label;
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Account parent
		if (! empty($arrayfields['aa.account_parent']['checked']))
		{
			if (! empty($obj->account_parent))
			{
				$accountparent->id = $obj->rowid2;
				$accountparent->label = $obj->label2;
				$accountparent->account_number = $obj->account_number2;

				print "<td>";
				print $accountparent->getNomUrl(1);
				print "</td>\n";
				if (! $i) $totalarray['nbfield']++;
			}
			else
			{
				print '<td>&nbsp;</td>';
				if (! $i) $totalarray['nbfield']++;
			}
		}

		// Chart of accounts type
		if (! empty($arrayfields['aa.pcg_type']['checked']))
		{
			print "<td>";
			print $obj->pcg_type;
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Chart of accounts subtype
		if (! empty($arrayfields['aa.pcg_subtype']['checked']))
		{
			print "<td>";
			print $obj->pcg_subtype;
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Activated or not
		if (! empty($arrayfields['aa.active']['checked']))
		{
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
			if (! $i) $totalarray['nbfield']++;
		}

		// Action
		print '<td align="center">';
		if ($user->rights->accounting->chartofaccount) {
			print '<a href="./card.php?action=update&id=' . $obj->rowid . '&backtopage='.urlencode($_SERVER["PHP_SELF"].'?chartofaccounts='.$object->id).'">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a href="./card.php?action=delete&id=' . $obj->rowid . '&backtopage='.urlencode($_SERVER["PHP_SELF"].'?chartofaccounts='.$object->id). '">';
			print img_delete();
			print '</a>';
		}
		print '</td>' . "\n";
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	print "</table>";
	print "</div>";
	print '</form>';
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
