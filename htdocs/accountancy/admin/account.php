<?php
/* Copyright (C) 2013-2016 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file 		htdocs/accountancy/admin/account.php
 * \ingroup     Accountancy (Double entries)
 * \brief		List accounting account
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "salaries"));

$mesg = '';
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$massaction = GETPOST('massaction', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'accountingaccountlist'; // To manage different context of search

$search_account = GETPOST('search_account', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_accountparent = GETPOST('search_accountparent', 'alpha');
$search_pcgtype = GETPOST('search_pcgtype', 'alpha');
$search_pcgsubtype = GETPOST('search_pcgsubtype', 'alpha');

$chartofaccounts = GETPOST('chartofaccounts', 'int');

// Security check
if ($user->socid > 0) accessforbidden();
if (! $user->rights->accounting->chartofaccount) accessforbidden();

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "aa.account_number";
if (!$sortorder) $sortorder = "ASC";

$arrayfields = array(
    'aa.account_number'=>array('label'=>$langs->trans("AccountNumber"), 'checked'=>1),
    'aa.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	'aa.account_parent'=>array('label'=>$langs->trans("Accountparent"), 'checked'=>1),
    'aa.pcg_type'=>array('label'=>$langs->trans("Pcgtype"), 'checked'=>1, 'help'=>'PcgtypeDesc'),
    'aa.pcg_subtype'=>array('label'=>$langs->trans("Pcgsubtype"), 'checked'=>0, 'help'=>'PcgtypeDesc'),
	'aa.active'=>array('label'=>$langs->trans("Activated"), 'checked'=>1)
);

$accounting = new AccountingAccount($db);



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha')) { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if (!empty($cancel)) $action = '';

    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All test are required to be compatible with all browsers
    {
    	$search_account = "";
    	$search_label = "";
    	$search_accountparent = "";
    	$search_pcgtype = "";
    	$search_pcgsubtype = "";
		$search_array_options = array();
    }
    if ((GETPOST('valid_change_chart', 'alpha') && GETPOST('chartofaccounts', 'int') > 0)	// explicit click on button 'Change and load' with js on
    	|| (GETPOST('chartofaccounts', 'int') > 0 && GETPOST('chartofaccounts', 'int') != $conf->global->CHARTOFACCOUNTS))	// a submit of form is done and chartofaccounts combo has been modified
    {
        if ($chartofaccounts > 0)
        {
			// Get language code for this $chartofaccounts
			$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'c_country as c, '.MAIN_DB_PREFIX.'accounting_system as a';
			$sql .= ' WHERE c.rowid = a.fk_country AND a.rowid = '.(int) $chartofaccounts;
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

				$offsetforchartofaccount = 0;
				// Get the comment line '-- ADD CCCNNNNN to rowid...' to find CCCNNNNN (CCC is country num, NNNNN is id of accounting account)
				// and pass CCCNNNNN + (num of company * 100 000 000) as offset to the run_sql as a new parameter to say to update sql on the fly to add offset to rowid and account_parent value.
				// This is to be sure there is no conflict for each chart of account, whatever is country, whatever is company when multicompany is used.
				$tmp = file_get_contents($sqlfile);
				if (preg_match('/-- ADD (\d+) to rowid/ims', $tmp, $reg))
				{
					$offsetforchartofaccount += $reg[1];
				}
				$offsetforchartofaccount += ($conf->entity * 100000000);

				$result = run_sql($sqlfile, 1, $conf->entity, 1, '', 'default', 32768, 0, $offsetforchartofaccount);

				if ($result > 0)
				{
					setEventMessages($langs->trans("ChartLoaded"), null, 'mesgs');
				}
				else
				{
					setEventMessages($langs->trans("ErrorDuringChartLoad"), null, 'warnings');
				}
			}

            if (!dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
                $error++;
            }
        } else {
            $error++;
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
    } elseif ($action == 'enable') {
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

$form = new Form($db);
$formaccounting = new FormAccounting($db);

llxHeader('', $langs->trans("ListAccounts"));

if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

$pcgver = $conf->global->CHARTOFACCOUNTS;

$sql = "SELECT aa.rowid, aa.fk_pcg_version, aa.pcg_type, aa.pcg_subtype, aa.account_number, aa.account_parent , aa.label, aa.active, ";
$sql .= " a2.rowid as rowid2, a2.label as label2, a2.account_number as account_number2";
$sql .= " FROM ".MAIN_DB_PREFIX."accounting_account as aa";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version AND aa.entity = ".$conf->entity;
if ($db->type == 'pgsql') $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as a2 ON a2.rowid = aa.account_parent AND a2.entity = ".$conf->entity;
else $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as a2 ON a2.rowid = aa.account_parent AND a2.entity = ".$conf->entity;
$sql .= " WHERE asy.rowid = ".$pcgver;
//print $sql;
if (strlen(trim($search_account))) {
	$lengthpaddingaccount = 0;
	if ($conf->global->ACCOUNTING_LENGTH_GACCOUNT || $conf->global->ACCOUNTING_LENGTH_AACCOUNT) {
		$lengthpaddingaccount = max($conf->global->ACCOUNTING_LENGTH_GACCOUNT, $conf->global->ACCOUNTING_LENGTH_AACCOUNT);
	}
	$search_account_tmp = $search_account;
	$weremovedsomezero = 0;
	if (strlen($search_account_tmp) <= $lengthpaddingaccount) {
		for($i = 0; $i < $lengthpaddingaccount; $i++) {
			if (preg_match('/0$/', $search_account_tmp)) {
				$weremovedsomezero++;
				$search_account_tmp = preg_replace('/0$/', '', $search_account_tmp);
			}
		}
	}

	//var_dump($search_account); exit;
	if ($search_account_tmp) {
		if ($weremovedsomezero) {
			$search_account_tmp_clean = $search_account_tmp;
			$search_account_clean = $search_account;
			$startchar = '%';
			if (strpos($search_account_tmp, '^') === 0)
			{
				$startchar = '';
				$search_account_tmp_clean = preg_replace('/^\^/', '', $search_account_tmp);
				$search_account_clean = preg_replace('/^\^/', '', $search_account);
			}
			$sql .= " AND (aa.account_number LIKE '".$startchar.$search_account_tmp_clean."'";
			$sql .= " OR aa.account_number LIKE '".$startchar.$search_account_clean."%')";
		}
		else $sql .= natural_search("aa.account_number", $search_account_tmp);
	}
}
if (strlen(trim($search_label)))			$sql .= natural_search("aa.label", $search_label);
if (strlen(trim($search_accountparent)) && $search_accountparent != '-1')	$sql .= natural_search("aa.account_parent", $search_accountparent, 2);
if (strlen(trim($search_pcgtype)))			$sql .= natural_search("aa.pcg_type", $search_pcgtype);
if (strlen(trim($search_pcgsubtype)))		$sql .= natural_search("aa.pcg_subtype", $search_pcgsubtype);
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/account.php:: $sql='.$sql);
$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);

    $param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
	if ($search_account) $param .= '&search_account='.urlencode($search_account);
	if ($search_label) $param .= '&search_label='.urlencode($search_label);
	if ($search_accountparent > 0 || $search_accountparent == '0') $param .= '&search_accountparent='.urlencode($search_accountparent);
	if ($search_pcgtype) $param .= '&search_pcgtype='.urlencode($search_pcgtype);
	if ($search_pcgsubtype) $param .= '&search_pcgsubtype='.urlencode($search_pcgsubtype);
    if ($optioncss != '') $param .= '&optioncss='.$optioncss;

    if (!empty($conf->use_javascript_ajax))
    {
	    print '<!-- Add javascript to reload page when we click "Change plan" -->
			<script type="text/javascript">
			$(document).ready(function () {
		    	$("#change_chart").on("click", function (e) {
					console.log("chartofaccounts seleted = "+$("#chartofaccounts").val());
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?valid_change_chart=1&chartofaccounts="+$("#chartofaccounts").val();
			    });
			});
	    	</script>';
    }

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

    $newcardbutton .= dolGetButtonTitle($langs->trans("New"), $langs->trans("Addanaccount"), 'fa fa-plus-circle', './card.php?action=create');


    print_barre_liste($langs->trans('ListAccounts'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit);

	// Box to select active chart of account
    print $langs->trans("Selectchartofaccounts")." : ";
    print '<select class="flat" name="chartofaccounts" id="chartofaccounts">';
    $sql = "SELECT a.rowid, a.pcg_version, a.label, a.active, c.code as country_code";
    $sql .= " FROM ".MAIN_DB_PREFIX."accounting_system as a";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON a.fk_country = c.rowid AND c.active = 1";
    $sql .= " WHERE a.active = 1";
    dol_syslog('accountancy/admin/account.php $sql='.$sql);
    print $sql;
    $resqlchart = $db->query($sql);
    if ($resqlchart) {
        $numbis = $db->num_rows($resqlchart);
        $i = 0;
        while ($i < $numbis) {
            $obj = $db->fetch_object($resqlchart);

            print '<option value="'.$obj->rowid.'"';
            print ($pcgver == $obj->rowid) ? ' selected' : '';
            print '>'.$obj->pcg_version.' - '.$obj->label.' - ('.$obj->country_code.')</option>';

            $i++;
        }
    }
    else dol_print_error($db);
    print "</select>";
    print ajax_combobox("chartofaccounts");
    print '<input type="'.(empty($conf->use_javascript_ajax)?'submit':'button').'" class="button" name="change_chart" id="change_chart" value="'.dol_escape_htmltag($langs->trans("ChangeAndLoad")).'">';

    print '<br>';
	print '<br>';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

    $moreforfilter = '';
    $massactionbutton = '';

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Line for search fields
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['aa.account_number']['checked']))	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_account" value="'.$search_account.'"></td>';
	if (!empty($arrayfields['aa.label']['checked']))			print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="'.$search_label.'"></td>';
	if (!empty($arrayfields['aa.account_parent']['checked'])) {
		print '<td class="liste_titre">';
		print $formaccounting->select_account($search_accountparent, 'search_accountparent', 2);
		print '</td>';
	}
	if (!empty($arrayfields['aa.pcg_type']['checked']))		    print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_pcgtype" value="'.$search_pcgtype.'"></td>';
	if (!empty($arrayfields['aa.pcg_subtype']['checked']))		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_pcgsubtype" value="'.$search_pcgsubtype.'"></td>';
	if (!empty($arrayfields['aa.active']['checked']))			print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print '</tr>';

    print '<tr class="liste_titre">';
	if (!empty($arrayfields['aa.account_number']['checked']))	print_liste_field_titre($arrayfields['aa.account_number']['label'], $_SERVER["PHP_SELF"], "aa.account_number", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['aa.label']['checked']))			print_liste_field_titre($arrayfields['aa.label']['label'], $_SERVER["PHP_SELF"], "aa.label", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['aa.account_parent']['checked']))	print_liste_field_titre($arrayfields['aa.account_parent']['label'], $_SERVER["PHP_SELF"], "aa.account_parent", "", $param, '', $sortfield, $sortorder, 'left ');
	if (!empty($arrayfields['aa.pcg_type']['checked']))		print_liste_field_titre($arrayfields['aa.pcg_type']['label'], $_SERVER["PHP_SELF"], 'aa.pcg_type', '', $param, '', $sortfield, $sortorder, '', $arrayfields['aa.pcg_type']['help']);
	if (!empty($arrayfields['aa.pcg_subtype']['checked']))		print_liste_field_titre($arrayfields['aa.pcg_subtype']['label'], $_SERVER["PHP_SELF"], 'aa.pcg_subtype', '', $param, '', $sortfield, $sortorder, '', $arrayfields['aa.pcg_subtype']['help']);
	if (!empty($arrayfields['aa.active']['checked']))			print_liste_field_titre($arrayfields['aa.active']['label'], $_SERVER["PHP_SELF"], 'aa.active', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";

	$accountstatic = new AccountingAccount($db);
	$accountparent = new AccountingAccount($db);

	$totalarray = array();
	$i = 0;
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$accountstatic->id = $obj->rowid;
		$accountstatic->label = $obj->label;
		$accountstatic->account_number = $obj->account_number;

		print '<tr class="oddeven">';

		// Account number
		if (!empty($arrayfields['aa.account_number']['checked']))
		{
			print "<td>";
			print $accountstatic->getNomUrl(1, 0, 0, '', 0, 1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Account label
		if (!empty($arrayfields['aa.label']['checked']))
		{
			print "<td>";
			print $obj->label;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Account parent
		if (!empty($arrayfields['aa.account_parent']['checked']))
		{
			if (!empty($obj->account_parent))
			{
				$accountparent->id = $obj->rowid2;
				$accountparent->label = $obj->label2;
				$accountparent->account_number = $obj->account_number2;

				print "<td>";
				print $accountparent->getNomUrl(1);
				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			else
			{
				print '<td>&nbsp;</td>';
				if (!$i) $totalarray['nbfield']++;
			}
		}

		// Chart of accounts type
		if (!empty($arrayfields['aa.pcg_type']['checked']))
		{
			print "<td>";
			print $obj->pcg_type;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Chart of accounts subtype
		if (!empty($arrayfields['aa.pcg_subtype']['checked']))
		{
			print "<td>";
			print $obj->pcg_subtype;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Activated or not
		if (!empty($arrayfields['aa.active']['checked']))
		{
			print '<td>';
			if (empty($obj->active)) {
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=enable">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a>';
			} else {
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=disable">';
				print img_picto($langs->trans("Activated"), 'switch_on');
				print '</a>';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action
		print '<td class="center">';
		if ($user->rights->accounting->chartofaccount) {
			print '<a href="./card.php?action=update&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?chartofaccounts='.$object->id).'">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a href="./card.php?action=delete&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?chartofaccounts='.$object->id).'">';
			print img_delete();
			print '</a>';
		}
		print '</td>'."\n";
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	print "</table>";
	print "</div>";
	print '</form>';
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
