<?php
/* Copyright (C) 2017-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2017       Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2020       Tobias Sekan				<tobias.sekan@startmail.com>
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
 *  \file       htdocs/compta/bank/various_payment/list.php
 *  \ingroup    bank
 *  \brief      List of various payments
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "accountancy"));

$optioncss = GETPOST('optioncss', 'alpha');
$mode      = GETPOST('mode', 'alpha');
$massaction = GETPOST('massaction', 'aZ09');
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'directdebitcredittransferlist'; // To manage different context of search

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$search_ref = GETPOST('search_ref', 'alpha');
$search_user = GETPOST('search_user', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_datep_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
$search_datep_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));
$search_datev_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_value_startmonth'), GETPOSTINT('search_date_value_startday'), GETPOSTINT('search_date_value_startyear'));
$search_datev_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_value_endmonth'), GETPOSTINT('search_date_value_endday'), GETPOSTINT('search_date_value_endyear'));
$search_amount_deb = GETPOST('search_amount_deb', 'alpha');
$search_amount_cred = GETPOST('search_amount_cred', 'alpha');
$search_bank_account = GETPOST('search_account', "intcomma");
$search_bank_entry = GETPOST('search_bank_entry', 'alpha');
$search_accountancy_account = GETPOST("search_accountancy_account");
if ($search_accountancy_account == - 1) {
	$search_accountancy_account = '';
}
$search_accountancy_subledger = GETPOST("search_accountancy_subledger");
if ($search_accountancy_subledger == - 1) {
	$search_accountancy_subledger = '';
}
if (empty($search_datep_start)) {
	$search_datep_start = GETPOSTINT("search_datep_start");
}
if (empty($search_datep_end)) {
	$search_datep_end = GETPOSTINT("search_datep_end");
}
if (empty($search_datev_start)) {
	$search_datev_start = GETPOSTINT("search_datev_start");
}
if (empty($search_datev_end)) {
	$search_datev_end = GETPOSTINT("search_datev_end");
}
$search_type_id = GETPOST('search_type_id', 'int');

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

// Initialize a technical objects
$object = new PaymentVarious($db);
$extrafields = new ExtraFields($db);
//$diroutputmassaction = $conf->mymodule->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($contextpage)); 	// Note that conf->hooks_modules contains array of activated contexes

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	$sortfield = "v.datep,v.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC,DESC";
}

$filtre = GETPOST("filtre", 'alpha');

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$search_ref = '';
	$search_label = '';
	$search_datep_start = '';
	$search_datep_end = '';
	$search_datev_start = '';
	$search_datev_end = '';
	$search_amount_deb = '';
	$search_amount_cred = '';
	$search_bank_account = '';
	$search_bank_entry = '';
	$search_accountancy_account = '';
	$search_accountancy_subledger = '';
	$search_type_id = '';
}

$search_all = trim(GETPOST('search_all', 'alphanohtml'));

/*
* TODO: fill array "$fields" in "/compta/bank/class/paymentvarious.class.php" and use
*
*
* $object = new PaymentVarious($db);
*
* $search = array();
* foreach ($object->fields as $key => $val)
* {
*	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
* }

* $fieldstosearchall = array();
* foreach ($object->fields as $key => $val)
* {
*	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
* }
*
*/

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'v.rowid' => "Ref",
	'v.label' => "Label",
	'v.datep' => "DatePayment",
	'v.datev' => "DateValue",
	'v.amount' => $langs->trans("Debit").", ".$langs->trans("Credit"),
);

// Definition of fields for lists
$arrayfields = array(
	'ref'			=> array('label' => "Ref", 'checked' => 1, 'position' => 100),
	'label'			=> array('label' => "Label", 'checked' => 1, 'position' => 110),
	'datep'			=> array('label' => "DatePayment", 'checked' => 1, 'position' => 120),
	'datev'			=> array('label' => "DateValue", 'checked' => -1, 'position' => 130),
	'type'			=> array('label' => "PaymentMode", 'checked' => 1, 'position' => 140),
	'project'		=> array('label' => "Project", 'checked' => -1, 'position' => 200, "enabled" => isModEnabled('project')),
	'bank'			=> array('label' => "BankAccount", 'checked' => 1, 'position' => 300, "enabled" => isModEnabled("bank")),
	'entry'			=> array('label' => "BankTransactionLine", 'checked' => 1, 'position' => 310, "enabled" => isModEnabled("bank")),
	'account'		=> array('label' => "AccountAccountingShort", 'checked' => 1, 'position' => 400, "enabled" => isModEnabled('accounting')),
	'subledger'		=> array('label' => "SubledgerAccount", 'checked' => 1, 'position' => 410, "enabled" => isModEnabled('accounting')),
	'debit'			=> array('label' => "Debit", 'checked' => 1, 'position' => 500),
	'credit'		=> array('label' => "Credit", 'checked' => 1, 'position' => 510),
);

$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

// Security check
$socid = GETPOSTINT("socid");
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'banque', '', '', '');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($object->fields as $key => $val) {
			$search[$key] = '';
			if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$search[$key.'_dtstart'] = '';
				$search[$key.'_dtend'] = '';
			}
		}
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}
}

/*
 * View
 */

$form = new Form($db);
$proj = null;
$accountingaccount = new AccountingAccount($db);
$bankline = new AccountLine($db);
$variousstatic = new PaymentVarious($db);
$accountstatic = null;
$accountingjournal = null;
if ($arrayfields['account']['checked'] || $arrayfields['subledger']['checked']) {
	$formaccounting = new FormAccounting($db);
}
if ($arrayfields['bank']['checked'] && isModEnabled('accounting')) {
	$accountingjournal = new AccountingJournal($db);
}
if ($arrayfields['bank']['checked']) {
	$accountstatic = new Account($db);
}
if (isModEnabled('project') && $arrayfields['project']['checked']) {
	$proj = new Project($db);
}

$title = $langs->trans("VariousPayments");
//$help_url = "EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url = '';


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT v.rowid, v.sens, v.amount, v.label, v.datep as datep, v.datev as datev, v.fk_typepayment as type, v.num_payment, v.fk_bank, v.accountancy_code, v.subledger_account, v.fk_projet as fk_project,";
$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number as bank_account_number, ba.fk_accountancy_journal as accountancy_journal, ba.label as blabel,";
$sql .= " pst.code as payment_code";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."payment_various as v";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON v.fk_typepayment = pst.id";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON v.fk_bank = b.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql .= " WHERE v.entity IN (".getEntity('payment_various').")";

// Search criteria
if ($search_ref) {
	$sql .= " AND v.rowid = ".((int) $search_ref);
}
if ($search_label) {
	$sql .= natural_search(array('v.label'), $search_label);
}
if ($search_datep_start) {
	$sql .= " AND v.datep >= '".$db->idate($search_datep_start)."'";
}
if ($search_datep_end) {
	$sql .= " AND v.datep <= '".$db->idate($search_datep_end)."'";
}
if ($search_datev_start) {
	$sql .= " AND v.datev >= '".$db->idate($search_datev_start)."'";
}
if ($search_datev_end) {
	$sql .= " AND v.datev <= '".$db->idate($search_datev_end)."'";
}
if ($search_amount_deb) {
	$sql .= natural_search("v.amount", $search_amount_deb, 1);
}
if ($search_amount_cred) {
	$sql .= natural_search("v.amount", $search_amount_cred, 1);
}
if ($search_bank_account > 0) {
	$sql .= " AND b.fk_account = ".((int) $search_bank_account);
}
if ($search_bank_entry > 0) {
	$sql .= " AND b.fk_account = ".((int) $search_bank_account);
}
if ($search_accountancy_account > 0) {
	$sql .= " AND v.accountancy_code = ".((int) $search_accountancy_account);
}
if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
	$sql .= " AND v.subledger_account = '".$db->escape($search_accountancy_subledger)."'";
} else {
	if ($search_accountancy_subledger != '' && $search_accountancy_subledger != '-1') {
		$sql .= natural_search("v.subledger_account", $search_accountancy_subledger);
	}
}
if ($search_type_id > 0) {
	$sql .= " AND v.fk_typepayment=".((int) $search_type_id);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist');

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_label) {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_datep_start) {
	$param .= '&search_datep_start='.urlencode($search_datep_start);
}
if ($search_datep_end) {
	$param .= '&search_datep_end='.urlencode($search_datep_end);
}
if ($search_datev_start) {
	$param .= '&search_datev_start='.urlencode($search_datev_start);
}
if ($search_datev_end) {
	$param .= '&search_datev_end='.urlencode($search_datev_end);
}
if ($search_type_id > 0) {
	$param .= '&search_type_id='.urlencode((string) ($search_type_id));
}
if ($search_amount_deb) {
	$param .= '&search_amount_deb='.urlencode($search_amount_deb);
}
if ($search_amount_cred) {
	$param .= '&search_amount_cred='.urlencode($search_amount_cred);
}
if ($search_bank_account > 0) {
	$param .= '&search_account='.urlencode((string) ($search_bank_account));
}
if ($search_accountancy_account > 0) {
	$param .= '&search_accountancy_account='.urlencode($search_accountancy_account);
}
if ($search_accountancy_subledger > 0) {
	$param .= '&search_accountancy_subledger='.urlencode($search_accountancy_subledger);
}

$url = DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.urlencode((string) ($socid));
}

// List of mass actions available
$arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$newcardbutton  = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('MenuNewVariousPayment'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('banque', 'modifier'));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'object_payment', 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if VARIOUSPAYMENT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}

$arrayofmassactions = array();

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');


print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal noborder liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Ref
if ($arrayfields['ref']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" size="3" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Label
if ($arrayfields['label']['checked']) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="10" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}

// Payment date
if ($arrayfields['datep']['checked']) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datep_start ? $search_datep_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datep_end ? $search_datep_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Value date
if ($arrayfields['datev']['checked']) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datev_start ? $search_datev_start : -1, 'search_date_value_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datev_end ? $search_datev_end : -1, 'search_date_value_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Payment type
if ($arrayfields['type']['checked']) {
	print '<td class="liste_titre center">';
	print $form->select_types_paiements($search_type_id, 'search_type_id', '', 0, 1, 1, 16, 1, 'maxwidth100', 1);
	print '</td>';
}

// Project
if (isModEnabled('project') && $arrayfields['project']['checked']) {
	print '<td class="liste_titre">';
	// TODO
	print '</td>';
}

// Bank account
if ($arrayfields['bank']['checked']) {
	print '<td class="liste_titre">';
	$form->select_comptes($search_bank_account, 'search_account', 0, '', 1, '', 0, 'maxwidth100');
	print '</td>';
}

// Bank entry
if ($arrayfields['entry']['checked']) {
	print '<td class="liste_titre left">';
	print '<input name="search_bank_entry" class="flat maxwidth50" type="text" value="'.dol_escape_htmltag($search_bank_entry).'">';
	print '</td>';
}

// Accounting account
if (!empty($arrayfields['account']['checked'])) {
	/** @var FormAccounting $formaccounting */
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	print $formaccounting->select_account($search_accountancy_account, 'search_accountancy_account', 1, array(), 1, 1, 'maxwidth200');
	print '</div>';
	print '</td>';
}

// Subledger account
if (!empty($arrayfields['subledger']['checked'])) {
	/** @var FormAccounting $formaccounting */
	print '<td class="liste_titre">';
	print '<div class="nowrap">';

	if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
		print $formaccounting->select_auxaccount($search_accountancy_subledger, 'search_accountancy_subledger', 1, 'maxwidth150');
	} else {
		print '<input type="text" class="maxwidth150 maxwidthonsmartphone" name="search_accountancy_subledger" value="'.$search_accountancy_subledger.'">';
	}

	print '</div>';
	print '</td>';
}

// Debit
if (!empty($arrayfields['debit']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input name="search_amount_deb" class="flat maxwidth50" type="text" value="'.dol_escape_htmltag($search_amount_deb).'">';
	print '</td>';
}

// Credit
if ($arrayfields['credit']['checked']) {
	print '<td class="liste_titre right">';
	print '<input name="search_amount_cred" class="flat maxwidth50" type="text" size="8" value="'.dol_escape_htmltag($search_amount_cred).'">';
	print '</td>';
}

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
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}

if ($arrayfields['ref']['checked']) {
	print_liste_field_titre($arrayfields['ref']['label'], $_SERVER["PHP_SELF"], 'v.rowid', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if ($arrayfields['label']['checked']) {
	print_liste_field_titre($arrayfields['label']['label'], $_SERVER["PHP_SELF"], 'v.label', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if ($arrayfields['datep']['checked']) {
	print_liste_field_titre($arrayfields['datep']['label'], $_SERVER["PHP_SELF"], 'v.datep,v.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if ($arrayfields['datev']['checked']) {
	print_liste_field_titre($arrayfields['datev']['label'], $_SERVER["PHP_SELF"], 'v.datev,v.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if ($arrayfields['type']['checked']) {
	print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], 'type', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (isModEnabled('project') && $arrayfields['project']['checked']) {
	print_liste_field_titre($arrayfields['project']['label'], $_SERVER["PHP_SELF"], 'fk_project', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if ($arrayfields['bank']['checked']) {
	print_liste_field_titre($arrayfields['bank']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if ($arrayfields['entry']['checked']) {
	print_liste_field_titre($arrayfields['entry']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['account']['checked'])) {
	// False positive @phan-suppress-next-line PhanTypeInvalidDimOffset
	print_liste_field_titre($arrayfields['account']['label'], $_SERVER["PHP_SELF"], 'v.accountancy_code', '', $param, '', $sortfield, $sortorder, 'left ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['subledger']['checked'])) {
	print_liste_field_titre($arrayfields['subledger']['label'], $_SERVER["PHP_SELF"], 'v.subledger_account', '', $param, '', $sortfield, $sortorder, 'left ');
	$totalarray['nbfield']++;
}
if ($arrayfields['debit']['checked']) {
	print_liste_field_titre($arrayfields['debit']['label'], $_SERVER["PHP_SELF"], 'v.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if ($arrayfields['credit']['checked']) {
	print_liste_field_titre($arrayfields['credit']['label'], $_SERVER["PHP_SELF"], 'v.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$totalarray['val']['total_cred'] = 0;
$totalarray['val']['total_deb'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$variousstatic->id = $obj->rowid;
	$variousstatic->ref = $obj->rowid;
	$variousstatic->label = $obj->label;
	$variousstatic->datep = $obj->datep;
	$variousstatic->type_payment = $obj->payment_code;
	$variousstatic->accountancy_code = $obj->accountancy_code;
	$variousstatic->amount = $obj->amount;

	if ($mode == 'kanban') {
		if ($obj->fk_bank > 0) {
			$bankline->fetch($obj->fk_bank);
		} else {
			$bankline->id = 0;
		}
		$accountingaccount->fetch(0, $obj->accountancy_code, 1);

		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		print $variousstatic->getKanbanView('', array('selected' => in_array($object->id, $arrayofselected), 'bankline' => $bankline, 'formatedaccountancycode' => $accountingaccount->getNomUrl(0, 0, 1, $obj->accountancy_code, 1)));
		if ($i == ($imaxinloop) - 1) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show here line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// No
		if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
			print '<td>'.(($offset * $limit) + $i).'</td>';
		}

		// Ref
		if ($arrayfields['ref']['checked']) {
			print '<td>'.$variousstatic->getNomUrl(1)."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Label payment
		if ($arrayfields['label']['checked']) {
			print '<td class="tdoverflowmax150" title="'.$variousstatic->label.'">'.$variousstatic->label."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date payment
		if ($arrayfields['datep']['checked']) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}


		// Date value
		if ($arrayfields['datev']['checked']) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->datev), 'day')."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Type
		if ($arrayfields['type']['checked']) {
			$labeltoshow = '';
			if ($obj->payment_code) {
				$labeltoshow = $langs->transnoentitiesnoconv("PaymentTypeShort".$obj->payment_code).' ';
			}
			$labeltoshow .= $obj->num_payment;
			print '<td class="center tdoverflowmax150" title="'.dolPrintHTML($labeltoshow).'">';
			print $labeltoshow;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project
		if (isModEnabled('project') && $arrayfields['project']['checked']) {
			print '<td class="nowraponall">';
			if ($obj->fk_project > 0 && is_object($proj)) {
				$proj->fetch($obj->fk_project);
				print $proj->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank account
		if ($arrayfields['bank']['checked']) {
			print '<td class="nowraponall">';
			if (is_object($accountstatic) && $obj->bid > 0) {
				$accountstatic->id = $obj->bid;
				$accountstatic->ref = $obj->bref;
				$accountstatic->number = $obj->bnumber;

				if (isModEnabled('accounting') && is_object($accountingjournal)) {
					$accountstatic->account_number = $obj->bank_account_number;
					$accountingjournal->fetch($obj->accountancy_journal);
					$accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
				}

				$accountstatic->label = $obj->blabel;
				print $accountstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank entry
		if ($arrayfields['entry']['checked']) {
			$bankline->fetch($obj->fk_bank);
			print '<td>'.$bankline->getNomUrl(1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Accounting account
		if (!empty($arrayfields['account']['checked'])) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
			$result = $accountingaccount->fetch(0, $obj->accountancy_code, 1);
			if ($result > 0) {
				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->accountancy_code.' '.$accountingaccount->label).'">'.$accountingaccount->getNomUrl(0, 1, 1, '', 1).'</td>';
			} else {
				print '<td></td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Accounting subledger account
		if (!empty($arrayfields['subledger']['checked'])) {
			print '<td class="tdoverflowmax150">'.length_accounta($obj->subledger_account).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Debit
		if ($arrayfields['debit']['checked']) {
			print '<td class="nowrap right">';
			if ($obj->sens == 0) {
				print '<span class="amount">'.price($obj->amount).'</span>';
				$totalarray['val']['total_deb'] += $obj->amount;
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_deb';
			}
			print '</td>';
		}

		// Credit
		if ($arrayfields['credit']['checked']) {
			print '<td class="nowrap right">';
			if ($obj->sens == 1) {
				print '<span class="amount">'.price($obj->amount).'</span>';
				$totalarray['val']['total_cred'] += $obj->amount;
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_cred';
			}
			print '</td>';
		}

		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
