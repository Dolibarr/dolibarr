<?php
/* Copyright (C) 2016       Neil Orley          <neil.orley@oeris.fr>
 * Copyright (C) 2013-2016  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2020  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file 		htdocs/accountancy/bookkeeping/listbysubaccount.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		List operation of ledger ordered by subaccount number
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "compta"));

$action = GETPOST('action', 'aZ09');
$search_date_startyear =  GETPOST('search_date_startyear', 'int');
$search_date_startmonth =  GETPOST('search_date_startmonth', 'int');
$search_date_startday =  GETPOST('search_date_startday', 'int');
$search_date_endyear =  GETPOST('search_date_endyear', 'int');
$search_date_endmonth =  GETPOST('search_date_endmonth', 'int');
$search_date_endday =  GETPOST('search_date_endday', 'int');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
$search_date_end = dol_mktime(0, 0, 0, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));
$search_date_export_startyear =  GETPOST('search_date_export_startyear', 'int');
$search_date_export_startmonth =  GETPOST('search_date_export_startmonth', 'int');
$search_date_export_startday =  GETPOST('search_date_export_startday', 'int');
$search_date_export_endyear =  GETPOST('search_date_export_endyear', 'int');
$search_date_export_endmonth =  GETPOST('search_date_export_endmonth', 'int');
$search_date_export_endday =  GETPOST('search_date_export_endday', 'int');
$search_date_export_start = dol_mktime(0, 0, 0, $search_date_export_startmonth, $search_date_export_startday, $search_date_export_startyear);
$search_date_export_end = dol_mktime(0, 0, 0, $search_date_export_endmonth, $search_date_export_endday, $search_date_export_endyear);

$search_accountancy_code = GETPOST("search_accountancy_code");
$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}
$search_doc_ref = GETPOST('search_doc_ref', 'alpha');
$search_label_operation = GETPOST('search_label_operation', 'alpha');
$search_mvt_num = GETPOST('search_mvt_num', 'int');
$search_direction = GETPOST('search_direction', 'alpha');
$search_ledger_code = GETPOST('search_ledger_code', 'alpha');
$search_debit = GETPOST('search_debit', 'alpha');
$search_credit = GETPOST('search_credit', 'alpha');
$search_lettering_code = GETPOST('search_lettering_code', 'alpha');
$search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt.x") || GETPOST("button_delmvt")) {
	$action = 'delbookkeepingyear';
}

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) ? $conf->liste_limit : $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") $sortorder = "ASC";
if ($sortfield == "") $sortfield = "t.doc_date,t.rowid";

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new BookKeeping($db);
$formfile = new FormFile($db);
$hookmanager->initHooks(array('bookkeepingbysubaccountlist'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('search_date_startday') && !GETPOSTISSET('search_date_startmonth') && !GETPOSTISSET('search_date_starthour')) {
	$sql = "SELECT date_start, date_end from ".MAIN_DB_PREFIX."accounting_fiscalyear ";
	$sql .= " where date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."'";
	$sql .= $db->plimit(1);
	$res = $db->query($sql);

	if ($res->num_rows > 0) {
		$fiscalYear = $db->fetch_object($res);
		$search_date_start = strtotime($fiscalYear->date_start);
		$search_date_end = strtotime($fiscalYear->date_end);
	} else {
		$month_start = ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
		$year_start = dol_print_date(dol_now(), '%Y');
		if (dol_print_date(dol_now(), '%m') < $month_start) $year_start--; // If current month is lower that starting fiscal month, we start last year
		$year_end = $year_start + 1;
		$month_end = $month_start - 1;
		if ($month_end < 1)
		{
			$month_end = 12;
			$year_end--;
		}
		$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
		$search_date_end = dol_get_last_day($year_end, $month_end);
	}
}

$arrayfields = array(
	// 't.subledger_account'=>array('label'=>$langs->trans("SubledgerAccount"), 'checked'=>1),
	't.piece_num'=>array('label'=>$langs->trans("TransactionNumShort"), 'checked'=>1),
	't.code_journal'=>array('label'=>$langs->trans("Codejournal"), 'checked'=>1),
	't.doc_date'=>array('label'=>$langs->trans("Docdate"), 'checked'=>1),
	't.doc_ref'=>array('label'=>$langs->trans("Piece"), 'checked'=>1),
	't.label_operation'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	't.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1),
	't.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1),
	't.lettering_code'=>array('label'=>$langs->trans("LetteringCode"), 'checked'=>1),
	't.date_export'=>array('label'=>$langs->trans("DateExport"), 'checked'=>1),
);

if (empty($conf->global->ACCOUNTING_ENABLE_LETTERING)) unset($arrayfields['t.lettering_code']);

if ($search_date_start && empty($search_date_startyear)) {
	$tmparray = dol_getdate($search_date_start);
	$search_date_startyear = $tmparray['year'];
	$search_date_startmonth = $tmparray['mon'];
	$search_date_startday = $tmparray['mday'];
}
if ($search_date_end && empty($search_date_endyear)) {
	$tmparray = dol_getdate($search_date_end);
	$search_date_endyear = $tmparray['year'];
	$search_date_endmonth = $tmparray['mon'];
	$search_date_endday = $tmparray['mday'];
}


/*
 * Action
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_doc_date = '';
		$search_accountancy_code = '';
		$search_accountancy_code_start = '';
		$search_accountancy_code_end = '';
		$search_label_account = '';
		$search_doc_ref = '';
		$search_label_operation = '';
		$search_mvt_num = '';
		$search_direction = '';
		$search_ledger_code = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_date_startyear = '';
		$search_date_startmonth = '';
		$search_date_startday = '';
		$search_date_endyear = '';
		$search_date_endmonth = '';
		$search_date_endday = '';
		$search_date_export_start = '';
		$search_date_export_end = '';
		$search_date_export_startyear = '';
		$search_date_export_startmonth = '';
		$search_date_export_startday = '';
		$search_date_export_endyear = '';
		$search_date_export_endmonth = '';
		$search_date_export_endday = '';
		$search_debit = '';
		$search_credit = '';
		$search_lettering_code = '';
		$search_not_reconciled = '';
	}

	// Must be after the remove filter action, before the export.
	$param = '';
	$filter = array();

	if (!empty($search_date_start)) {
		$filter['t.doc_date>='] = $search_date_start;
		$param .= '&search_date_startmonth='.$search_date_startmonth.'&search_date_startday='.$search_date_startday.'&search_date_startyear='.$search_date_startyear;
	}
	if (!empty($search_date_end)) {
		$filter['t.doc_date<='] = $search_date_end;
		$param .= '&search_date_endmonth='.$search_date_endmonth.'&search_date_endday='.$search_date_endday.'&search_date_endyear='.$search_date_endyear;
	}
	if (!empty($search_doc_date)) {
		$filter['t.doc_date'] = $search_doc_date;
		$param .= '&doc_datemonth='.GETPOST('doc_datemonth', 'int').'&doc_dateday='.GETPOST('doc_dateday', 'int').'&doc_dateyear='.GETPOST('doc_dateyear', 'int');
	}
	if (!empty($search_accountancy_code_start)) {
		$filter['t.subledger_account>='] = $search_accountancy_code_start;
		$param .= '&search_accountancy_code_start='.urlencode($search_accountancy_code_start);
	}
	if (!empty($search_accountancy_code_end)) {
		$filter['t.subledger_account<='] = $search_accountancy_code_end;
		$param .= '&search_accountancy_code_end='.urlencode($search_accountancy_code_end);
	}
	if (!empty($search_label_account)) {
		$filter['t.label_compte'] = $search_label_account;
		$param .= '&search_label_compte='.urlencode($search_label_account);
	}
	if (!empty($search_mvt_num)) {
		$filter['t.piece_num'] = $search_mvt_num;
		$param .= '&search_mvt_num='.urlencode($search_mvt_num);
	}
	if (!empty($search_doc_ref)) {
		$filter['t.doc_ref'] = $search_doc_ref;
		$param .= '&search_doc_ref='.urlencode($search_doc_ref);
	}
	if (!empty($search_label_operation)) {
		$filter['t.label_operation'] = $search_label_operation;
		$param .= '&search_label_operation='.urlencode($search_label_operation);
	}
	if (!empty($search_direction)) {
		$filter['t.sens'] = $search_direction;
		$param .= '&search_direction='.urlencode($search_direction);
	}
	if (!empty($search_ledger_code)) {
		$filter['t.code_journal'] = $search_ledger_code;
		$param .= '&search_ledger_code='.urlencode($search_ledger_code);
	}
	if (!empty($search_debit)) {
		$filter['t.debit'] = $search_debit;
		$param .= '&search_debit='.urlencode($search_debit);
	}
	if (!empty($search_credit)) {
		$filter['t.credit'] = $search_credit;
		$param .= '&search_credit='.urlencode($search_credit);
	}
	if (!empty($search_lettering_code)) {
		$filter['t.lettering_code'] = $search_lettering_code;
		$param .= '&search_lettering_code='.urlencode($search_lettering_code);
	}
	if (!empty($search_not_reconciled)) {
		$filter['t.reconciled_option'] = $search_not_reconciled;
		$param .= '&search_not_reconciled='.urlencode($search_not_reconciled);
	}
	if (!empty($search_date_export_start)) {
		$filter['t.date_export>='] = $search_date_export_start;
		$param .= '&search_date_export_startmonth='.$search_date_export_startmonth.'&search_date_export_startday='.$search_date_export_startday.'&search_date_export_startyear='.$search_date_export_startyear;
	}
	if (!empty($search_date_export_end)) {
		$filter['t.date_export<='] = $search_date_export_end;
		$param .= '&search_date_export_endmonth='.$search_date_export_endmonth.'&search_date_export_endday='.$search_date_export_endday.'&search_date_export_endyear='.$search_date_export_endyear;
	}
}

if ($action == 'delbookkeeping' && $user->rights->accounting->mouvements->supprimer) {
	$import_key = GETPOST('importkey', 'alpha');

	if (!empty($import_key)) {
		$result = $object->deleteByImportkey($import_key);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		// Make a redirect to avoid to launch the delete later after a back button
		header("Location: ".$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
		exit;
	}
}
if ($action == 'delbookkeepingyearconfirm' && $user->rights->accounting->mouvements->supprimer_tous) {
	$delmonth = GETPOST('delmonth', 'int');
	$delyear = GETPOST('delyear', 'int');
	if ($delyear == -1) {
		$delyear = 0;
	}
	$deljournal = GETPOST('deljournal', 'alpha');
	if ($deljournal == -1) {
		$deljournal = 0;
	}

	if (!empty($delmonth) || !empty($delyear) || !empty($deljournal))
	{
		$result = $object->deleteByYearAndJournal($delyear, $deljournal, '', ($delmonth > 0 ? $delmonth : 0));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			setEventMessages("RecordDeleted", null, 'mesgs');
		}

		// Make a redirect to avoid to launch the delete later after a back button
		header("Location: ".$_SERVER["PHP_SELF"].($param ? '?'.$param : ''));
		exit;
	} else {
		setEventMessages("NoRecordDeleted", null, 'warnings');
	}
}
if ($action == 'delmouvconfirm' && $user->rights->accounting->mouvements->supprimer) {
	$mvt_num = GETPOST('mvt_num', 'int');

	if (!empty($mvt_num)) {
		$result = $object->deleteMvtNum($mvt_num);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		}

		header("Location: ".$_SERVER["PHP_SELF"]."?noreset=1".($param ? '&'.$param : ''));
		exit;
	}
}


/*
 * View
 */

$formaccounting = new FormAccounting($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$form = new Form($db);

$title_page = $langs->trans("Operations").' - '.$langs->trans("VueByAccountAccounting").' ('.$langs->trans("BookkeepingSubAccount").')';

llxHeader('', $title_page);


// List
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 1);
	if ($nbtotalofrecords < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 1);

if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
}

$num = count($object->lines);


if ($action == 'delmouv') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?mvt_num='.GETPOST('mvt_num'), $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvtPartial'), 'delmouvconfirm', '', 0, 1);
	print $formconfirm;
}
if ($action == 'delbookkeepingyear') {
	$form_question = array();
	$delyear = GETPOST('delyear', 'int');
	$deljournal = GETPOST('deljournal', 'alpha');

	if (empty($delyear)) {
		$delyear = dol_print_date(dol_now(), '%Y');
	}
	$month_array = array();
	for ($i = 1; $i <= 12; $i++) {
		$month_array[$i] = $langs->trans("Month".sprintf("%02d", $i));
	}
	$year_array = $formaccounting->selectyear_accountancy_bookkepping($delyear, 'delyear', 0, 'array');
	$journal_array = $formaccounting->select_journal($deljournal, 'deljournal', '', 1, 1, 1, '', 0, 1);

	$form_question['delmonth'] = array(
		'name' => 'delmonth',
		'type' => 'select',
		'label' => $langs->trans('DelMonth'),
		'values' => $month_array,
		'default' => ''
	);
	$form_question['delyear'] = array(
		'name' => 'delyear',
		'type' => 'select',
		'label' => $langs->trans('DelYear'),
		'values' => $year_array,
		'default' => $delyear
	);
	$form_question['deljournal'] = array(
		'name' => 'deljournal',
		'type' => 'other', // We don't use select here, the journal_array is already a select html component
		'label' => $langs->trans('DelJournal'),
		'value' => $journal_array,
		'default' => $deljournal
	);

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?'.$param, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'delbookkeepingyearconfirm', $form_question, '', 1, 300);
	print $formconfirm;
}


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';


$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$newcardbutton = dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?'.$param);
	$newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/accountancy/bookkeeping/listbyaccount.php?'.$param, '', 1, array('morecss' => 'marginleftonly'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT.'/accountancy/bookkeeping/listbysubaccount.php?'.$param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?action=create');
}

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $result, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

print info_admin($langs->trans("WarningRecordWithoutSubledgerAreExcluded"));

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

// Reverse sort order
if (preg_match('/^asc/i', $sortorder)) $sortorder = "asc";
else $sortorder = "desc";

$moreforfilter = '';

// Accountancy account
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('AccountAccounting').': ';
$moreforfilter .= '<div class="nowrap inline-block">';
$moreforfilter .= $langs->trans('From').' ';
$moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_start, 'search_accountancy_code_start', 1, 'maxwidth200');
$moreforfilter .= ' '.$langs->trans('to').' ';
$moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_end, 'search_accountancy_code_end', 1, 'maxwidth200');
$moreforfilter .= '</div>';
$moreforfilter .= '</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print $moreforfilter;
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent">';

// Filters lines
print '<tr class="liste_titre_filter">';

// Movement number
if (!empty($arrayfields['t.piece_num']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="'.dol_escape_htmltag($search_mvt_num).'"></td>';
}
// Code journal
if (!empty($arrayfields['t.code_journal']['checked'])) {
	print '<td class="liste_titre center"><input type="text" name="search_ledger_code" size="3" value="'.dol_escape_htmltag($search_ledger_code).'"></td>';
}
// Date document
if (!empty($arrayfields['t.doc_date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Ref document
if (!empty($arrayfields['t.doc_ref']['checked'])) {
	print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_doc_ref" value="'.dol_escape_htmltag($search_doc_ref).'"/></td>';
}
// Label operation
if (!empty($arrayfields['t.label_operation']['checked'])) {
	print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_label_operation" value="'.dol_escape_htmltag($search_label_operation).'"/></td>';
}
// Debit
if (!empty($arrayfields['t.debit']['checked'])) {
	print '<td class="liste_titre right"><input type="text" class="flat" name="search_debit" size="4" value="'.dol_escape_htmltag($search_debit).'"></td>';
}
// Credit
if (!empty($arrayfields['t.credit']['checked'])) {
	print '<td class="liste_titre right"><input type="text" class="flat" name="search_credit" size="4" value="'.dol_escape_htmltag($search_credit).'"></td>';
}
// Lettering code
if (!empty($arrayfields['t.lettering_code']['checked']))
{
	print '<td class="liste_titre center">';
	print '<input type="text" size="3" class="flat" name="search_lettering_code" value="'.$search_lettering_code.'"/>';
	print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"'.($search_not_reconciled == 'notreconciled' ? ' checked' : '').'>'.$langs->trans("NotReconciled").'</span>';
	print '</td>';
}
// Date export
if (!empty($arrayfields['t.date_export']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
print '<td class="liste_titre center">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (!empty($arrayfields['t.piece_num']['checked']))				print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['t.code_journal']['checked']))			print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.doc_date']['checked']))				print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.doc_ref']['checked']))				print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.label_operation']['checked']))		print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.debit']['checked']))					print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['t.credit']['checked']))				print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['t.lettering_code']['checked']))		print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.date_export']['checked']))			print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export", "", $param, '', $sortfield, $sortorder, 'center ');
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";


$total_debit = 0;
$total_credit = 0;
$sous_total_debit = 0;
$sous_total_credit = 0;
$displayed_account_number = null; // Start with undefined to be able to distinguish with empty

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
while ($i < min($num, $limit))
{
	$line = $object->lines[$i];

	$total_debit += $line->debit;
	$total_credit += $line->credit;

	$accountg = length_accounta($line->subledger_account);
	//if (empty($accountg)) $accountg = '-';

	// Is it a break ?
	if ($accountg != $displayed_account_number || !isset($displayed_account_number)) {
		$colnumber = 5;
		$colnumberend = 7;

		if (empty($conf->global->ACCOUNTING_ENABLE_LETTERING) || empty($arrayfields['t.lettering_code']['checked'])) $colnumber--;
		if (empty($arrayfields['t.date_export']['checked'])) $colnumber--;

        $colspan = $totalarray['nbfield'] - $colnumber;
        $colspanend = $totalarray['nbfield'] - $colnumberend;
		// Show a subtotal by accounting account
		if (isset($displayed_account_number)) {
			print '<tr class="liste_total">';
			print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("TotalForAccount").' '.length_accounta($displayed_account_number).':</td>';
			print '<td class="nowrap right">'.price($sous_total_debit).'</td>';
			print '<td class="nowrap right">'.price($sous_total_credit).'</td>';
			print '<td colspan="'.$colspanend.'"></td>';
			print '</tr>';
			// Show balance of last shown account
			$balance = $sous_total_debit - $sous_total_credit;
			print '<tr class="liste_total">';
			print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("Balance").':</td>';
			if ($balance > 0)
			{
				print '<td class="nowraponall right">';
				print price($sous_total_debit - $sous_total_credit);
				print '</td>';
				print '<td></td>';
			} else {
				print '<td></td>';
				print '<td class="nowraponall right">';
				print price($sous_total_credit - $sous_total_debit);
				print '</td>';
			}
			print '<td colspan="'.$colspanend.'"></td>';
			print '</tr>';
		}

		// Show the break account
		print "<tr>";
		print '<td colspan="'.($totalarray['nbfield'] ? $totalarray['nbfield'] : 10).'" style="font-weight:bold; border-bottom: 1pt solid black;">';
		if ($line->subledger_account != "" && $line->subledger_account != '-1') {
			print $object->get_compte_desc($line->numero_compte).' : '.length_accounta($line->subledger_account);
		} else {
			// Should not happen: subledger account must be null or a non empty value
			print '<span class="error">'.$langs->trans("Unknown");
			if ($line->subledger_label) {
				print ' ('.$line->subledger_label.')';
				$htmltext = 'EmptyStringForSubledgerAccountButSubledgerLabelDefined';
			} else {
				$htmltext = 'EmptyStringForSubledgerAccountAndSubledgerLabel';
			}
			print $form->textwithpicto('', $htmltext);
			print '</span>';
		}
		print '</td>';
		print '</tr>';

		$displayed_account_number = $accountg;
		//if (empty($displayed_account_number)) $displayed_account_number='-';
		$sous_total_debit = 0;
		$sous_total_credit = 0;

		$colspan = 0;
	}

	print '<tr class="oddeven">';

	// Piece number
	if (!empty($arrayfields['t.piece_num']['checked']))
	{
		print '<td>';
		$object->id = $line->id;
		$object->piece_num = $line->piece_num;
		print $object->getNomUrl(1, '', 0, '', 1);
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Journal code
	if (!empty($arrayfields['t.code_journal']['checked']))
	{
		$accountingjournal = new AccountingJournal($db);
		$result = $accountingjournal->fetch('', $line->code_journal);
		$journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
		print '<td class="center">'.$journaltoshow.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Document date
	if (!empty($arrayfields['t.doc_date']['checked']))
	{
		print '<td class="center">'.dol_print_date($line->doc_date, 'day').'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Document ref
	if (!empty($arrayfields['t.doc_ref']['checked']))
	{
		if ($line->doc_type == 'customer_invoice')
		{
			$langs->loadLangs(array('bills'));

			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$objectstatic = new Facture($db);
			$objectstatic->fetch($line->fk_doc);
			//$modulepart = 'facture';

			$filename = dol_sanitizeFileName($line->doc_ref);
			$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($line->doc_ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$objectstatic->id;
			$documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		} elseif ($line->doc_type == 'supplier_invoice')
		{
			$langs->loadLangs(array('bills'));

			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
			$objectstatic = new FactureFournisseur($db);
			$objectstatic->fetch($line->fk_doc);
			//$modulepart = 'invoice_supplier';

			$filename = dol_sanitizeFileName($line->doc_ref);
			$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $modulepart).dol_sanitizeFileName($line->doc_ref);
			$subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $modulepart).dol_sanitizeFileName($line->doc_ref);
			$documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
		} elseif ($line->doc_type == 'expense_report')
		{
			$langs->loadLangs(array('trips'));

			require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
			$objectstatic = new ExpenseReport($db);
			$objectstatic->fetch($line->fk_doc);
			//$modulepart = 'expensereport';

			$filename = dol_sanitizeFileName($line->doc_ref);
			$filedir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($line->doc_ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$objectstatic->id;
			$documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		} else {
			// Other type
		}

		print '<td class="maxwidth400">';

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		// Picto + Ref
		print '<td class="nobordernopadding">';

		if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report')
		{
			print $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
			print $documentlink;
		} else {
			print $line->doc_ref;
		}
		print '</td></tr></table>';

		print "</td>\n";
		if (!$i) $totalarray['nbfield']++;
	}

	// Label operation
	if (!empty($arrayfields['t.label_operation']['checked'])) {
		// Affiche un lien vers la facture client/fournisseur
		$doc_ref = preg_replace('/\(.*\)/', '', $line->doc_ref);
		print strlen(length_accounta($line->subledger_account)) == 0 ? '<td>'.$line->label_operation.'</td>' : '<td>'.$line->label_operation.'<br><span style="font-size:0.8em">('.length_accounta($line->subledger_account).')</span></td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Amount debit
	if (!empty($arrayfields['t.debit']['checked']))
	{
		print '<td class="nowrap right">'.($line->debit ? price($line->debit) : '').'</td>';
		if (!$i) $totalarray['nbfield']++;
		if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
		$totalarray['val']['totaldebit'] += $line->debit;
	}

	// Amount credit
	if (!empty($arrayfields['t.credit']['checked'])) {
		print '<td class="nowrap right">'.($line->credit ? price($line->credit) : '').'</td>';
		if (!$i) $totalarray['nbfield']++;
		if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
		$totalarray['val']['totalcredit'] += $line->credit;
	}

	// Lettering code
	if (!empty($arrayfields['t.lettering_code']['checked']))
	{
		print '<td class="center">'.$line->lettering_code.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Exported operation date
	if (!empty($arrayfields['t.date_export']['checked']))
	{
		print '<td class="center">'.dol_print_date($line->date_export, 'dayhour').'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print '<td class="nowraponall center">';
	if (empty($line->date_export)) {
		if ($user->rights->accounting->mouvements->creer) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?piece_num='.$line->piece_num.$param.'&page='.$page.($sortfield ? '&sortfield='.$sortfield : '').($sortorder ? '&sortorder='.$sortorder : '').'">'.img_edit().'</a>';
		}
		if ($user->rights->accounting->mouvements->supprimer) {
			print ' &nbsp; <a class="paddingleft" href="'.$_SERVER['PHP_SELF'].'?action=delmouv&mvt_num='.$line->piece_num.$param.'&page='.$page.($sortfield ? '&sortfield='.$sortfield : '').($sortorder ? '&sortorder='.$sortorder : '').'">'.img_delete().'</a>';
		}
	}
	print '</td>';
	if (!$i) $totalarray['nbfield']++;

	// Comptabilise le sous-total
	$sous_total_debit += $line->debit;
	$sous_total_credit += $line->credit;

	print "</tr>\n";

	$i++;
}

if ($num > 0) {
	$colspan = $totalarray['nbfield'] - $colnumber;
	$colspanend = $totalarray['nbfield'] - $colnumberend;
	print '<tr class="liste_total">';
	print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("TotalForAccount").' '.$accountg.':</td>';
	print '<td class="nowrap right">'.price($sous_total_debit).'</td>';
	print '<td class="nowrap right">'.price($sous_total_credit).'</td>';
	print '<td colspan="'.$colspanend.'"></td>';
	print '</tr>';
	// Show balance of last shown account
	$balance = $sous_total_debit - $sous_total_credit;
	print '<tr class="liste_total">';
	print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("Balance").':</td>';
	if ($balance > 0)
	{
		print '<td class="nowraponall right">';
		print price($sous_total_debit - $sous_total_credit);
		print '</td>';
		print '<td></td>';
	} else {
		print '<td></td>';
		print '<td class="nowraponall right">';
		print price($sous_total_credit - $sous_total_debit);
		print '</td>';
	}
	print '<td colspan="'.$colspanend.'"></td>';
	print '</tr>';
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';


print "</table>";
print '</div>';

// TODO Replace this with mass delete action
if ($user->rights->accounting->mouvements->supprimer_tous) {
	print '<div class="tabsAction tabsActionNoBottom">'."\n";
	print '<a class="butActionDelete" name="button_delmvt" href="'.$_SERVER["PHP_SELF"].'?action=delbookkeepingyear'.($param ? '&'.$param : '').'">'.$langs->trans("DeleteMvt").'</a>';
	print '</div>';
}

print '</form>';

// End of page
llxFooter();
$db->close();
