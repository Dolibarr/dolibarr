<?php
/* Copyright (C) 2013-2016  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2024  Alexandre Spangaro  <aspangaro@easya.solutions>
 * Copyright (C) 2016-2018  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array('accountancy', 'admin', 'bills', 'compta', 'salaries'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$id = GETPOSTINT('id');
$rowid = GETPOSTINT('rowid');
$massaction = GETPOST('massaction', 'aZ09');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountingaccountlist'; // To manage different context of search
$mode = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$search_account = GETPOST('search_account', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_labelshort = GETPOST('search_labelshort', 'alpha');
$search_accountparent = GETPOST('search_accountparent', 'alpha');
$search_pcgtype = GETPOST('search_pcgtype', 'alpha');
$search_import_key = GETPOST('search_import_key', 'alpha');
$toselect = GETPOST('toselect', 'array');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$confirm = GETPOST('confirm', 'alpha');

$chartofaccounts = GETPOSTINT('chartofaccounts');

$permissiontoadd = $user->hasRight('accounting', 'chartofaccount');
$permissiontodelete = $user->hasRight('accounting', 'chartofaccount');

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "aa.account_number";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$arrayfields = array(
	'aa.account_number' => array('label' => "AccountNumber", 'checked' => 1),
	'aa.label' => array('label' => "Label", 'checked' => 1),
	'aa.labelshort' => array('label' => "LabelToShow", 'checked' => 1),
	'aa.account_parent' => array('label' => "Accountparent", 'checked' => 1),
	'aa.pcg_type' => array('label' => "Pcgtype", 'checked' => 1, 'help' => 'PcgtypeDesc'),
	'categories' => array('label' => "AccountingCategories", 'checked' => -1, 'help' => 'AccountingCategoriesDesc'),
	'aa.reconcilable' => array('label' => "Reconcilable", 'checked' => 1),
	'aa.import_key' => array('label' => "ImportId", 'checked' => -1, 'help' => ''),
	'aa.active' => array('label' => "Activated", 'checked' => 1)
);

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
	unset($arrayfields['categories']);
	unset($arrayfields['aa.reconcilable']);
}

$accounting = new AccountingAccount($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('accountancyadminaccount'));


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha')) {
	$massaction = '';
}

$parameters = array('chartofaccounts' => $chartofaccounts, 'permissiontoadd' => $permissiontoadd, 'permissiontodelete' => $permissiontodelete);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $accounting, $action); // Note that $action and $object may have been monowraponalldified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (!empty($cancel)) {
		$action = '';
	}

	$objectclass = 'AccountingAccount';
	$uploaddir = $conf->accounting->multidir_output[$conf->entity];
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($action == "delete") {
		$action = "";
	}
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$search_account = "";
		$search_label = "";
		$search_labelshort = "";
		$search_accountparent = "";
		$search_pcgtype = "";
		$search_import_key = "";
		$search_array_options = array();
	}
	if ((GETPOSTINT('valid_change_chart') && GETPOSTINT('chartofaccounts') > 0)	// explicit click on button 'Change and load' with js on
		|| (GETPOSTINT('chartofaccounts') > 0 && GETPOSTINT('chartofaccounts') != getDolGlobalInt('CHARTOFACCOUNTS'))) {	// a submit of form is done and chartofaccounts combo has been modified
		$error = 0;

		if ($chartofaccounts > 0 && $permissiontoadd) {
			$country_code = '';
			// Get language code for this $chartofaccounts
			$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'c_country as c, '.MAIN_DB_PREFIX.'accounting_system as a';
			$sql .= ' WHERE c.rowid = a.fk_country AND a.rowid = '.(int) $chartofaccounts;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$country_code = $obj->code;
				}
			} else {
				dol_print_error($db);
			}

			// Try to load sql file
			if ($country_code) {
				$sqlfile = DOL_DOCUMENT_ROOT.'/install/mysql/data/llx_accounting_account_'.strtolower($country_code).'.sql';

				$offsetforchartofaccount = 0;
				// Get the comment line '-- ADD CCCNNNNN to rowid...' to find CCCNNNNN (CCC is country num, NNNNN is id of accounting account)
				// and pass CCCNNNNN + (num of company * 100 000 000) as offset to the run_sql as a new parameter to say to update sql on the fly to add offset to rowid and account_parent value.
				// This is to be sure there is no conflict for each chart of account, whatever is country, whatever is company when multicompany is used.
				$tmp = file_get_contents($sqlfile);
				$reg = array();
				if (preg_match('/-- ADD (\d+) to rowid/ims', $tmp, $reg)) {
					$offsetforchartofaccount += $reg[1];
				}
				$offsetforchartofaccount += ($conf->entity * 100000000);

				$result = run_sql($sqlfile, 1, $conf->entity, 1, '', 'default', 32768, 0, $offsetforchartofaccount);

				if ($result > 0) {
					setEventMessages($langs->trans("ChartLoaded"), null, 'mesgs');
				} else {
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

	if ($action == 'disable' && $permissiontoadd) {
		if ($accounting->fetch($id)) {
			$mode = GETPOSTINT('mode');
			$result = $accounting->accountDeactivate($id, $mode);
			if ($result < 0) {
				setEventMessages($accounting->error, $accounting->errors, 'errors');
			}
		}

		$action = 'update';
	} elseif ($action == 'enable' && $permissiontoadd) {
		if ($accounting->fetch($id)) {
			$mode = GETPOSTINT('mode');
			$result = $accounting->accountActivate($id, $mode);
			if ($result < 0) {
				setEventMessages($accounting->error, $accounting->errors, 'errors');
			}
		}
		$action = 'update';
	}
}


/*
 * View
 */
$form = new Form($db);
$formaccounting = new FormAccounting($db);

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $langs->trans("ListAccounts"), $help_url);

if ($action == 'delete') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

$pcgver = getDolGlobalInt('CHARTOFACCOUNTS');

$sql = "SELECT aa.rowid, aa.fk_pcg_version, aa.pcg_type, aa.account_number, aa.account_parent, aa.label, aa.labelshort, aa.fk_accounting_category,";
$sql .= " aa.reconcilable, aa.active, aa.import_key,";
$sql .= " a2.rowid as rowid2, a2.label as label2, a2.account_number as account_number2";

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sql .= " FROM ".MAIN_DB_PREFIX."accounting_account as aa";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version AND aa.entity = ".((int) $conf->entity);
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as a2 ON a2.rowid = aa.account_parent AND a2.entity = ".((int) $conf->entity);

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " WHERE asy.rowid = ".((int) $pcgver);

if (strlen(trim($search_account))) {
	$lengthpaddingaccount = 0;
	if (getDolGlobalInt('ACCOUNTING_LENGTH_GACCOUNT') || getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
		$lengthpaddingaccount = max(getDolGlobalInt('ACCOUNTING_LENGTH_GACCOUNT'), getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT'));
	}
	$search_account_tmp = $search_account;
	$weremovedsomezero = 0;
	if (strlen($search_account_tmp) <= $lengthpaddingaccount) {
		for ($i = 0; $i < $lengthpaddingaccount; $i++) {
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
			if (substr($search_account_tmp, 0, 1) === '^') {
				$startchar = '';
				$search_account_tmp_clean = preg_replace('/^\^/', '', $search_account_tmp);
				$search_account_clean = preg_replace('/^\^/', '', $search_account);
			}
			$sql .= " AND (aa.account_number LIKE '".$db->escape($startchar.$search_account_tmp_clean)."'";
			$sql .= " OR aa.account_number LIKE '".$db->escape($startchar.$search_account_clean)."%')";
		} else {
			$sql .= natural_search("aa.account_number", $search_account_tmp);
		}
	}
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("aa.label", $search_label);
}
if (strlen(trim($search_labelshort))) {
	$sql .= natural_search("aa.labelshort", $search_labelshort);
}
if (strlen(trim($search_accountparent)) && $search_accountparent != '-1') {
	$sql .= natural_search("aa.account_parent", $search_accountparent, 2);
}
if (strlen(trim($search_pcgtype))) {
	$sql .= natural_search("aa.pcg_type", $search_pcgtype);
}
if (strlen(trim($search_import_key))) {
	$sql .= natural_search("aa.import_key", $search_import_key);
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);
//print $sql;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("accountancy/admin/account.php:: sql=".$sql);
$resql = $db->query($sql);

if ($resql) {
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($search_account) {
		$param .= '&search_account='.urlencode($search_account);
	}
	if ($search_label) {
		$param .= '&search_label='.urlencode($search_label);
	}
	if ($search_labelshort) {
		$param .= '&search_labelshort='.urlencode($search_labelshort);
	}
	if ($search_accountparent > 0 || $search_accountparent == '0') {
		$param .= '&search_accountparent='.urlencode($search_accountparent);
	}
	if ($search_pcgtype) {
		$param .= '&search_pcgtype='.urlencode($search_pcgtype);
	}
	if ($search_import_key) {
		$param .= '&search_import_key='.urlencode($search_import_key);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	// Add $param from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	$param .= $hookmanager->resPrint;

	if (!empty($conf->use_javascript_ajax)) {
		print '<!-- Add javascript to reload page when we click "Change plan" -->
			<script type="text/javascript">
			$(document).ready(function () {
		    	$("#change_chart").on("click", function (e) {
					console.log("chartofaccounts selected = "+$("#chartofaccounts").val());
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?valid_change_chart=1&chartofaccounts="+$("#chartofaccounts").val();
			    });
			});
	    	</script>';
	}

	// List of mass actions available
	$arrayofmassactions = array();
	if ($user->hasRight('accounting', 'chartofaccount')) {
		$arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	}
	if (in_array($massaction, array('presend', 'predelete', 'closed'))) {
		$arrayofmassactions = array();
	}

	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	$newcardbutton = dolGetButtonTitle($langs->trans('Addanaccount'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/accountancy/admin/card.php?action=create', '', $permissiontoadd);


	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans('ListAccounts'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'accounting_account', 0, $newcardbutton, '', $limit, 0, 0, 1);

	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	// Box to select active chart of account
	print $langs->trans("Selectchartofaccounts")." : ";
	print '<select class="flat minwidth200" name="chartofaccounts" id="chartofaccounts">';
	$sql = "SELECT a.rowid, a.pcg_version, a.label, a.active, c.code as country_code";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_system as a";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON a.fk_country = c.rowid AND c.active = 1";
	$sql .= " WHERE a.active = 1";

	dol_syslog("accountancy/admin/account.php sql=".$sql);

	$resqlchart = $db->query($sql);
	if ($resqlchart) {
		$numbis = $db->num_rows($resqlchart);
		$i = 0;
		print '<option value="-1">&nbsp;</option>';
		while ($i < $numbis) {
			$obj = $db->fetch_object($resqlchart);
			if ($obj) {
				print '<option value="'.$obj->rowid.'"';
				print ($pcgver == $obj->rowid) ? ' selected' : '';
				print '>'.$obj->pcg_version.' - '.$obj->label.' - ('.$obj->country_code.')</option>';
			}
			$i++;
		}
	} else {
		dol_print_error($db);
	}
	print "</select>";
	print ajax_combobox("chartofaccounts");
	print '<input type="'.(empty($conf->use_javascript_ajax) ? 'submit' : 'button').'" class="button button-edit small" name="change_chart" id="change_chart" value="'.dol_escape_htmltag($langs->trans("ChangeAndLoad")).'">';

	print '<br>';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '<br>';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
	$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	$moreforfilter = '';
	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$accountstatic = new AccountingAccount($db);
	$accountparent = new AccountingAccount($db);
	$totalarray = array();
	$totalarray['nbfield'] = 0;

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre_filter">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center maxwidthsearch">';
		$searchpicto = $form->showFilterButtons('left');
		print $searchpicto;
		print '</td>';
	}
	if (!empty($arrayfields['aa.account_number']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat width100" name="search_account" value="'.$search_account.'"></td>';
	}
	if (!empty($arrayfields['aa.label']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat width150" name="search_label" value="'.$search_label.'"></td>';
	}
	if (!empty($arrayfields['aa.labelshort']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat width100" name="search_labelshort" value="'.$search_labelshort.'"></td>';
	}
	if (!empty($arrayfields['aa.account_parent']['checked'])) {
		print '<td class="liste_titre">';
		print $formaccounting->select_account($search_accountparent, 'search_accountparent', 2, array(), 0, 0, 'maxwidth150');
		print '</td>';
	}
	// Predefined group
	if (!empty($arrayfields['aa.pcg_type']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat width75" name="search_pcgtype" value="'.$search_pcgtype.'"></td>';
	}
	// Custom groups
	if (!empty($arrayfields['categories']['checked'])) {
		print '<td class="liste_titre"></td>';
	}

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Import key
	if (!empty($arrayfields['aa.import_key']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat width75" name="search_import_key" value="'.$search_import_key.'"></td>';
	}
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		if (!empty($arrayfields['aa.reconcilable']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
	}
	if (!empty($arrayfields['aa.active']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}
	print '</tr>'."\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['aa.account_number']['checked'])) {
		print_liste_field_titre($arrayfields['aa.account_number']['label'], $_SERVER["PHP_SELF"], "aa.account_number", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['aa.label']['checked'])) {
		print_liste_field_titre($arrayfields['aa.label']['label'], $_SERVER["PHP_SELF"], "aa.label", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['aa.labelshort']['checked'])) {
		print_liste_field_titre($arrayfields['aa.labelshort']['label'], $_SERVER["PHP_SELF"], "aa.labelshort", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['aa.account_parent']['checked'])) {
		print_liste_field_titre($arrayfields['aa.account_parent']['label'], $_SERVER["PHP_SELF"], "aa.account_parent", "", $param, '', $sortfield, $sortorder, 'left ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['aa.pcg_type']['checked'])) {
		print_liste_field_titre($arrayfields['aa.pcg_type']['label'], $_SERVER["PHP_SELF"], 'aa.pcg_type,aa.account_number', '', $param, '', $sortfield, $sortorder, '', $arrayfields['aa.pcg_type']['help'], 1);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['categories']['checked'])) {
		print_liste_field_titre($arrayfields['categories']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '', $arrayfields['categories']['help'], 1);
		$totalarray['nbfield']++;
	}

	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (!empty($arrayfields['aa.import_key']['checked'])) {
		print_liste_field_titre($arrayfields['aa.import_key']['label'], $_SERVER["PHP_SELF"], 'aa.import_key', '', $param, '', $sortfield, $sortorder, '', $arrayfields['aa.import_key']['help'], 1);
		$totalarray['nbfield']++;
	}
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		if (!empty($arrayfields['aa.reconcilable']['checked'])) {
			print_liste_field_titre($arrayfields['aa.reconcilable']['label'], $_SERVER["PHP_SELF"], 'aa.reconcilable', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['aa.active']['checked'])) {
		print_liste_field_titre($arrayfields['aa.active']['label'], $_SERVER["PHP_SELF"], 'aa.active', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	print "</tr>\n";

	// Loop on record
	// --------------------------------------------------------------------
	$i = 0;
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		$accountstatic->id = $obj->rowid;
		$accountstatic->label = $obj->label;
		$accountstatic->account_number = $obj->account_number;

		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center nowraponall">';
			if ($user->hasRight('accounting', 'chartofaccount')) {
				print '<a class="editfielda" href="./card.php?action=update&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'">';
				print img_edit();
				print '</a>';
				print '&nbsp;';
				print '<a class="marginleftonly" href="./card.php?action=delete&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'">';
				print img_delete();
				print '</a>';
				print '&nbsp;';
				if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($obj->rowid, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->rowid.'" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
				}
			}
			print '</td>'."\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Account number
		if (!empty($arrayfields['aa.account_number']['checked'])) {
			print "<td>";
			print $accountstatic->getNomUrl(1, 0, 0, '', 0, 1, 0, 'accountcard');
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Account label
		if (!empty($arrayfields['aa.label']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->label).'">';
			print dol_escape_htmltag($obj->label);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Account label to show (label short)
		if (!empty($arrayfields['aa.labelshort']['checked'])) {
			print "<td>";
			print dol_escape_htmltag($obj->labelshort);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Account parent
		if (!empty($arrayfields['aa.account_parent']['checked'])) {
			// Note: obj->account_parent is a foreign key to a rowid. It is field in child table and obj->rowid2 is same, but in parent table.
			// So for orphans, obj->account_parent is set but not obj->rowid2
			if (!empty($obj->account_parent) && !empty($obj->rowid2)) {
				print "<td>";
				print '<!-- obj->account_parent = '.$obj->account_parent.' obj->rowid2 = '.$obj->rowid2.' -->';
				$accountparent->id = $obj->rowid2;
				$accountparent->label = $obj->label2;
				$accountparent->account_number = $obj->account_number2; // Store an account number for output
				print $accountparent->getNomUrl(1);
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			} else {
				print '<td>';
				if (!empty($obj->account_parent)) {
					print '<!-- Bad value for obj->account_parent = '.$obj->account_parent.': is a rowid that does not exists -->';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
		}

		// Predefined group (deprecated)
		if (!empty($arrayfields['aa.pcg_type']['checked'])) {
			print "<td>";
			print dol_escape_htmltag($obj->pcg_type);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Custom accounts
		if (!empty($arrayfields['categories']['checked'])) {
			print "<td>";
			// TODO Get all custom groups labels the account is in
			print dol_escape_htmltag($obj->fk_accounting_category);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Import id
		if (!empty($arrayfields['aa.import_key']['checked'])) {
			print "<td>";
			print dol_escape_htmltag($obj->import_key);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
			// Activated or not reconciliation on an accounting account
			if (!empty($arrayfields['aa.reconcilable']['checked'])) {
				print '<td class="center">';
				if (empty($obj->reconcilable)) {
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=enable&mode=1&token='.newToken().'">';
					print img_picto($langs->trans("Disabled"), 'switch_off');
					print '</a>';
				} else {
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=disable&mode=1&token='.newToken().'">';
					print img_picto($langs->trans("Activated"), 'switch_on');
					print '</a>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
		}

		// Activated or not
		if (!empty($arrayfields['aa.active']['checked'])) {
			print '<td class="center">';
			if (empty($obj->active)) {
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=enable&mode=0&token='.newToken().'">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a>';
			} else {
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=disable&mode=0&token='.newToken().'">';
				print img_picto($langs->trans("Activated"), 'switch_on');
				print '</a>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center nowraponall">';
			if ($user->hasRight('accounting', 'chartofaccount')) {
				print '<a class="editfielda" href="./card.php?action=update&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'">';
				print img_edit();
				print '</a>';
				print '&nbsp;';
				print '<a class="marginleftonly" href="./card.php?action=delete&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?'.$param).'">';
				print img_delete();
				print '</a>';
				print '&nbsp;';
				if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($obj->rowid, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->rowid.'" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
				}
			}
			print '</td>'."\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print "</tr>\n";
		$i++;
	}

	if ($num == 0) {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	$db->free($resql);

	$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
