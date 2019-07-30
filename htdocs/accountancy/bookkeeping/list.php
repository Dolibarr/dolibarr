<?php
/* Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2018  Alexandre Spangaro      <aspangaro@zendsi.com>
 * Copyright (C) 2016-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file		htdocs/accountancy/bookkeeping/list.php
 * \ingroup		Advanced accountancy
 * \brief 		List operation of book keeping
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy"));

$action = GETPOST('action', 'aZ09');
$search_mvt_num = GETPOST('search_mvt_num', 'int');
$search_doc_type = GETPOST("search_doc_type", 'alpha');
$search_doc_ref = GETPOST("search_doc_ref", 'alpha');
$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));
$search_doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));
$search_date_creation_start = dol_mktime(0, 0, 0, GETPOST('date_creation_startmonth', 'int'), GETPOST('date_creation_startday', 'int'), GETPOST('date_creation_startyear', 'int'));
$search_date_creation_end = dol_mktime(0, 0, 0, GETPOST('date_creation_endmonth', 'int'), GETPOST('date_creation_endday', 'int'), GETPOST('date_creation_endyear', 'int'));
$search_date_modification_start = dol_mktime(0, 0, 0, GETPOST('date_modification_startmonth', 'int'), GETPOST('date_modification_startday', 'int'), GETPOST('date_modification_startyear', 'int'));
$search_date_modification_end = dol_mktime(0, 0, 0, GETPOST('date_modification_endmonth', 'int'), GETPOST('date_modification_endday', 'int'), GETPOST('date_modification_endyear', 'int'));
//var_dump($search_date_start);exit;
if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt.x") || GETPOST("button_delmvt")) {
	$action = 'delbookkeepingyear';
}
if (GETPOST("button_export_file_x") || GETPOST("button_export_file.x") || GETPOST("button_export_file")) {
	$action = 'export_file';
}

$search_accountancy_code = GETPOST("search_accountancy_code");
$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}

$search_accountancy_aux_code = GETPOST("search_accountancy_aux_code");
$search_accountancy_aux_code_start = GETPOST('search_accountancy_aux_code_start', 'alpha');
if ($search_accountancy_aux_code_start == - 1) {
	$search_accountancy_aux_code_start = '';
}
$search_accountancy_aux_code_end = GETPOST('search_accountancy_aux_code_end', 'alpha');
if ($search_accountancy_aux_code_end == - 1) {
	$search_accountancy_aux_code_end = '';
}
$search_mvt_label = GETPOST('search_mvt_label', 'alpha');
$search_direction = GETPOST('search_direction', 'alpha');
$search_debit = GETPOST('search_debit', 'alpha');
$search_credit = GETPOST('search_credit', 'alpha');
$search_ledger_code = GETPOST('search_ledger_code', 'alpha');
$search_lettering_code = GETPOST('search_lettering_code', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit', 'int'):(empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page','int');
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") $sortorder = "ASC";
if ($sortfield == "") $sortfield = "t.piece_num,t.rowid";


$object = new BookKeeping($db);

$formaccounting = new FormAccounting($db);
$formother = new FormOther($db);
$form = new Form($db);

if (! in_array($action, array('export_file', 'delmouv', 'delmouvconfirm')) && ! isset($_POST['begin']) && ! isset($_GET['begin']) && ! isset($_POST['formfilteraction']) && GETPOST('page','int') == '' && ! GETPOST('noreset','int'))
{
	if (empty($search_date_start) && empty($search_date_end) && ! GETPOSTISSET('restore_lastsearch_values'))
	{
		$query = "SELECT date_start, date_end from ".MAIN_DB_PREFIX."accounting_fiscalyear ";
		$query.= " where date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."' limit 1";
		$res = $db->query($query);

		if ($res->num_rows > 0) {
			$fiscalYear = $db->fetch_object($res);
			$search_date_start = strtotime($fiscalYear->date_start);
			$search_date_end = strtotime($fiscalYear->date_end);
		} else {
			$month_start= ($conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START):1);
			$year_start = dol_print_date(dol_now(), '%Y');
			if (dol_print_date(dol_now(), '%m') < $month_start) $year_start--;	// If current month is lower that starting fiscal month, we start last year
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
}


$arrayfields=array(
	't.piece_num'=>array('label'=>$langs->trans("TransactionNumShort"), 'checked'=>1),
	't.doc_date'=>array('label'=>$langs->trans("Docdate"), 'checked'=>1),
	't.doc_ref'=>array('label'=>$langs->trans("Piece"), 'checked'=>1),
	't.numero_compte'=>array('label'=>$langs->trans("AccountAccountingShort"), 'checked'=>1),
	't.subledger_account'=>array('label'=>$langs->trans("SubledgerAccount"), 'checked'=>1),
	't.label_operation'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	't.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1),
	't.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1),
	't.lettering_code'=>array('label'=>$langs->trans("LetteringCode"), 'checked'=>1),
	't.code_journal'=>array('label'=>$langs->trans("Codejournal"), 'checked'=>1),
	't.date_creation'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0),
	't.tms'=>array('label'=>$langs->trans("DateModification"), 'checked'=>0),
);

if (empty($conf->global->ACCOUNTING_ENABLE_LETTERING)) unset($arrayfields['t.lettering_code']);


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_mvt_num = '';
	$search_doc_type = '';
	$search_doc_ref = '';
	$search_doc_date = '';
	$search_accountancy_code = '';
	$search_accountancy_code_start = '';
	$search_accountancy_code_end = '';
	$search_accountancy_aux_code = '';
	$search_accountancy_aux_code_start = '';
	$search_accountancy_aux_code_end = '';
	$search_mvt_label = '';
	$search_direction = '';
	$search_ledger_code = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_date_creation_start = '';
	$search_date_creation_end = '';
	$search_date_modification_start = '';
	$search_date_modification_end = '';
	$search_debit = '';
	$search_credit = '';
	$search_lettering_code = '';
}

// Must be after the remove filter action, before the export.
$param = '';
$filter = array ();
if (! empty($search_date_start)) {
	$filter['t.doc_date>='] = $search_date_start;
	$tmp=dol_getdate($search_date_start);
	$param .= '&search_date_startmonth=' . $tmp['mon'] . '&search_date_startday=' . $tmp['mday'] . '&search_date_startyear=' . $tmp['year'];
}
if (! empty($search_date_end)) {
	$filter['t.doc_date<='] = $search_date_end;
	$tmp=dol_getdate($search_date_end);
	$param .= '&search_date_endmonth=' . $tmp['mon'] . '&search_date_endday=' . $tmp['mday'] . '&search_date_endyear=' . $tmp['year'];
}
if (! empty($search_doc_date)) {
	$filter['t.doc_date'] = $search_doc_date;
	$tmp=dol_getdate($search_doc_date);
	$param .= '&doc_datemonth=' . $tmp['mon'] . '&doc_dateday=' . $tmp['mday'] . '&doc_dateyear=' . $tmp['year'];
}
if (! empty($search_doc_type)) {
	$filter['t.doc_type'] = $search_doc_type;
	$param .= '&search_doc_type=' . urlencode($search_doc_type);
}
if (! empty($search_doc_ref)) {
	$filter['t.doc_ref'] = $search_doc_ref;
	$param .= '&search_doc_ref=' . urlencode($search_doc_ref);
}
if (! empty($search_accountancy_code)) {
	$filter['t.numero_compte'] = $search_accountancy_code;
	$param .= '&search_accountancy_code=' . urlencode($search_accountancy_code);
}
if (! empty($search_accountancy_code_start)) {
	$filter['t.numero_compte>='] = $search_accountancy_code_start;
	$param .= '&search_accountancy_code_start=' . urlencode($search_accountancy_code_start);
}
if (! empty($search_accountancy_code_end)) {
	$filter['t.numero_compte<='] = $search_accountancy_code_end;
	$param .= '&search_accountancy_code_end=' . urlencode($search_accountancy_code_end);
}
if (! empty($search_accountancy_aux_code)) {
	$filter['t.subledger_account'] = $search_accountancy_aux_code;
	$param .= '&search_accountancy_aux_code=' . urlencode($search_accountancy_aux_code);
}
if (! empty($search_accountancy_aux_code_start)) {
	$filter['t.subledger_account>='] = $search_accountancy_aux_code_start;
	$param .= '&search_accountancy_aux_code_start=' . urlencode($search_accountancy_aux_code_start);
}
if (! empty($search_accountancy_aux_code_end)) {
	$filter['t.subledger_account<='] = $search_accountancy_aux_code_end;
	$param .= '&search_accountancy_aux_code_end=' . urlencode($search_accountancy_aux_code_end);
}
if (! empty($search_mvt_label)) {
	$filter['t.label_operation'] = $search_mvt_label;
	$param .= '&search_mvt_label=' . urlencode($search_mvt_label);
}
if (! empty($search_direction)) {
	$filter['t.sens'] = $search_direction;
	$param .= '&search_direction=' . urlencode($search_direction);
}
if (! empty($search_ledger_code)) {
	$filter['t.code_journal'] = $search_ledger_code;
	$param .= '&search_ledger_code=' . urlencode($search_ledger_code);
}
if (! empty($search_mvt_num)) {
	$filter['t.piece_num'] = $search_mvt_num;
	$param .= '&search_mvt_num=' . urlencode($search_mvt_num);
}
if (! empty($search_date_creation_start)) {
	$filter['t.date_creation>='] = $search_date_creation_start;
	$tmp=dol_getdate($search_date_creation_start);
	$param .= '&date_creation_startmonth=' . $tmp['mon'] . '&date_creation_startday=' . $tmp['mday'] . '&date_creation_startyear=' . $tmp['year'];
}
if (! empty($search_date_creation_end)) {
	$filter['t.date_creation<='] = $search_date_creation_end;
	$tmp=dol_getdate($search_date_creation_end);
	$param .= '&date_creation_endmonth=' . $tmp['mon'] . '&date_creation_endday=' . $tmp['mday'] . '&date_creation_endyear=' . $tmp['year'];
}
if (! empty($search_date_modification_start)) {
	$filter['t.tms>='] = $search_date_modification_start;
	$tmp=dol_getdate($search_date_modification_start);
	$param .= '&date_modification_startmonth=' . $tmp['mon'] . '&date_modification_startday=' . $tmp['mday'] . '&date_modification_startyear=' . $tmp['year'];
}
if (! empty($search_date_modification_end)) {
	$filter['t.tms<='] = $search_date_modification_end;
	$tmp=dol_getdate($search_date_modification_end);
	$param .= '&date_modification_endmonth=' . $tmp['mon'] . '&date_modification_endday=' . $tmp['mday'] . '&date_modification_endyear=' . $tmp['year'];
}
if (! empty($search_debit)) {
	$filter['t.debit'] = $search_debit;
	$param .= '&search_debit=' . urlencode($search_debit);
}
if (! empty($search_credit)) {
	$filter['t.credit'] = $search_credit;
	$param .= '&search_credit=' . urlencode($search_credit);
}
if (! empty($search_lettering_code)) {
	$filter['t.lettering_code'] = $search_lettering_code;
	$param .= '&search_lettering_code=' . urlencode($search_lettering_code);
 }


if ($action == 'delbookkeeping') {

	$import_key = GETPOST('importkey', 'alpha');

	if (! empty($import_key)) {
		$result = $object->deleteByImportkey($import_key);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		Header("Location: list.php");
		exit();
	}
}
if ($action == 'delbookkeepingyearconfirm') {

	$delyear = GETPOST('delyear', 'int');
	if ($delyear==-1) {
		$delyear=0;
	}
	$deljournal = GETPOST('deljournal','alpha');
	if ($deljournal==-1) {
		$deljournal=0;
	}

	if (! empty($delyear) || ! empty($deljournal))
	{
		$result = $object->deleteByYearAndJournal($delyear,$deljournal);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		else
		{
			setEventMessages("RecordDeleted", null, 'mesgs');
		}
		Header("Location: list.php");
		exit;
	}
	else
	{
		setEventMessages("NoRecordDeleted", null, 'warnings');
		Header("Location: list.php");
		exit;
	}
}
if ($action == 'delmouvconfirm') {

	$mvt_num = GETPOST('mvt_num', 'int');

	if (! empty($mvt_num)) {
		$result = $object->deleteMvtNum($mvt_num);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		else
		{
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		}

		Header("Location: list.php?noreset=1".($param?'&'.$param:''));
		exit;
	}
}

// Export into a file with format defined into setup (FEC, CSV, ...)
if ($action == 'export_file') {

	$result = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter);

	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
	else
	{
		$accountancyexport = new AccountancyExport($db);
		$accountancyexport->export($object->lines);

		if (!empty($accountancyexport->errors))
		{
			setEventMessages('', $accountancyexport->errors, 'errors');
		}
		exit;
	}
}


/*
 * View
 */

$title_page = $langs->trans("Bookkeeping");

llxHeader('', $title_page);

// List
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter);
	if ($nbtotalofrecords < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// TODO Do not use this
$result = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
}

$num=count($object->lines);

if ($action == 'delmouv') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?mvt_num='.GETPOST('mvt_num').$param, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvtPartial'), 'delmouvconfirm', '', 0, 1);
	print $formconfirm;
}
if ($action == 'delbookkeepingyear') {

	$form_question = array ();
	$delyear = GETPOST('delyear');
	$deljournal = GETPOST('deljournal');

	if (empty($delyear)) {
		$delyear = dol_print_date(dol_now(), '%Y');
	}
	$year_array = $formaccounting->selectyear_accountancy_bookkepping($delyear, 'delyear', 0, 'array');
	$journal_array = $formaccounting->select_journal($deljournal, 'deljournal', '', 1, 1, 1, '', 0, 1);

	$form_question['delyear'] = array (
			'name' => 'delyear',
			'type' => 'select',
			'label' => $langs->trans('DelYear'),
			'values' => $year_array,
			'default' => $delyear
	);
	$form_question['deljournal'] = array (
			'name' => 'deljournal',
			'type' => 'other',	   // We don't use select here, the journal_array is already a select html component
			'label' => $langs->trans('DelJournal'),
			'value' => $journal_array,
			'default' => $deljournal
	);

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delbookkeepingyearconfirm', $form_question, 0, 1, 250);
	print $formconfirm;
}

//$param='';	param started before
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

$listofformat=AccountancyExport::getType();
$button = '<a class="butAction" name="button_export_file" href="'.$_SERVER["PHP_SELF"].'?action=export_file'.($param?'&'.$param:'').'">';
if (count($filter)) $button.= $langs->trans("ExportFilteredList");
else $button.= $langs->trans("ExportList");
//$button.=' ('.$listofformat[$conf->global->ACCOUNTING_EXPORT_MODELCSV].')';
$button.= '</a>';


$groupby = ' <a class="nohover marginrightonly" href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/listbyaccount.php?'.$param.'">' . $langs->trans("GroupByAccountAccounting") . '</a>';
$newcardbutton = '<a class="butActionNew" href="./card.php?action=create"><span class="valignmiddle">' . $langs->trans("NewAccountingMvt").'</span>';
$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
$newcardbutton.= '</a>';

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $button, $result, $nbtotalofrecords, 'title_accountancy', 0, $groupby.$newcardbutton, '', $limit);

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste" width="100%">';

// Filters lines
print '<tr class="liste_titre_filter">';
// Movement number
if (! empty($arrayfields['t.piece_num']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
}
// Date document
if (! empty($arrayfields['t.doc_date']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $langs->trans('From') . ' ';
	print $form->selectDate($search_date_start?$search_date_start:-1, 'search_date_start', 0, 0, 1);
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to') . ' ';
	print $form->selectDate($search_date_end?$search_date_end:-1, 'search_date_end', 0, 0, 1);
	print '</div>';
	print '</td>';
}
// Ref document
if (! empty($arrayfields['t.doc_ref']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="' . dol_escape_htmltag($search_doc_ref) . '"></td>';
}
// Accountancy account
if (! empty($arrayfields['t.numero_compte']['checked']))
{
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	print $langs->trans('From').' ';
	print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array (), 1, 1, 'maxwidth200');
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to').' ';
	print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', 1, array (), 1, 1, 'maxwidth200');
	print '</div>';
	print '</td>';
}
// Subledger account
if (! empty($arrayfields['t.subledger_account']['checked']))
{
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	print $langs->trans('From').' ';
	// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
	// use setup of keypress to select thirdparty and this hang browser on large database.
	if (! empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
	{
		print $formaccounting->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', 1);
	}
	else
	{
		print '<input type="text" name="search_accountancy_aux_code_start" value="'.$search_accountancy_aux_code_start.'">';
	}
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to').' ';
	// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
	// use setup of keypress to select thirdparty and this hang browser on large database.
	if (! empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
	{
		print $formaccounting->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', 1);
	}
	else
	{
		print '<input type="text" name="search_accountancy_aux_code_end" value="'.$search_accountancy_aux_code_end.'">';
	}
	print '</div>';
	print '</td>';
}
// Label operation
if (! empty($arrayfields['t.label_operation']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" size="7" class="flat" name="search_mvt_label" value="' . $search_mvt_label . '"/>';
	print '</td>';
}
// Debit
if (! empty($arrayfields['t.debit']['checked']))
{
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="search_debit" size="4" value="'.dol_escape_htmltag($search_debit).'">';
	print '</td>';
}
// Credit
if (! empty($arrayfields['t.credit']['checked']))
{
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="search_credit" size="4" value="'.dol_escape_htmltag($search_credit).'">';
	print '</td>';
}
// Lettering code
if (! empty($arrayfields['t.lettering_code']['checked']))
{
	print '<td class="liste_titre center">';
	print '<input type="text" size="3" class="flat" name="search_lettering_code" value="' . $search_lettering_code . '"/>';
	print '</td>';
}
// Code journal
if (! empty($arrayfields['t.code_journal']['checked']))
{
	print '<td class="liste_titre center"><input type="text" name="search_ledger_code" size="3" value="' . $search_ledger_code . '"></td>';
}
// Date creation
if (! empty($arrayfields['t.date_creation']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $langs->trans('From') . ' ';
	print $form->selectDate($search_date_creation_start, 'date_creation_start', 0, 0, 1);
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to') . ' ';
	print $form->selectDate($search_date_creation_end, 'date_creation_end', 0, 0, 1);
	print '</div>';
	print '</td>';
}
// Date modification
if (! empty($arrayfields['t.tms']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $langs->trans('From') . ' ';
	print $form->selectDate($search_date_modification_start, 'date_modification_start', 0, 0, 1);
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to') . ' ';
	print $form->selectDate($search_date_modification_end, 'date_modification_end', 0, 0, 1);
	print '</div>';
	print '</td>';
}
// Action column
print '<td class="liste_titre center">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['t.piece_num']['checked']))			print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['t.doc_date']['checked']))				print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['t.doc_ref']['checked']))				print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['t.numero_compte']['checked']))		print_liste_field_titre($arrayfields['t.numero_compte']['label'], $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['t.subledger_account']['checked']))	print_liste_field_titre($arrayfields['t.subledger_account']['label'], $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['t.label_operation']['checked']))		print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['t.debit']['checked']))				print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, 'align="right"', $sortfield, $sortorder);
if (! empty($arrayfields['t.credit']['checked']))				print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, 'align="right"', $sortfield, $sortorder);
if (! empty($arrayfields['t.lettering_code']['checked']))		print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['t.code_journal']['checked']))			print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['t.date_creation']['checked']))		print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER['PHP_SELF'], "t.date_creation", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['t.tms']['checked']))					print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], "t.tms", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";


if ($num > 0)
{
	$i=0;
	$totalarray=array();
	while ($i < min($num, $limit))
	{
		$line = $object->lines[$i];

		$total_debit += $line->debit;
		$total_credit += $line->credit;

		print '<tr class="oddeven">';

		// Piece number
		if (! empty($arrayfields['t.piece_num']['checked']))
		{
			print '<td>';
			$object->id = $line->id;
			$object->piece_num = $line->piece_num;
			print $object->getNomUrl(1,'',0,'',1);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Document date
		if (! empty($arrayfields['t.doc_date']['checked']))
		{
			print '<td align="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Document ref
		if (! empty($arrayfields['t.doc_ref']['checked']))
		{
			print '<td class="nowrap">' . $line->doc_ref . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Account number
		if (! empty($arrayfields['t.numero_compte']['checked']))
		{
			print '<td>' . length_accountg($line->numero_compte) . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Subledger account
		if (! empty($arrayfields['t.subledger_account']['checked']))
		{
			print '<td>' . length_accounta($line->subledger_account) . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Label operation
		if (! empty($arrayfields['t.label_operation']['checked']))
		{
			print '<td>' . $line->label_operation . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Amount debit
		if (! empty($arrayfields['t.debit']['checked']))
		{
			print '<td class="nowrap right">' . ($line->debit ? price($line->debit) : ''). '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totaldebitfield']=$totalarray['nbfield'];
			$totalarray['totaldebit'] += $line->debit;
		}

		// Amount credit
		if (! empty($arrayfields['t.credit']['checked']))
		{
			print '<td class="nowrap right">' . ($line->credit ? price($line->credit) : '') . '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalcreditfield']=$totalarray['nbfield'];
			$totalarray['totalcredit'] += $line->credit;
		}

		// Lettering code
		if (! empty($arrayfields['t.lettering_code']['checked']))
		{
			print '<td align="center">' . $line->lettering_code . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Journal code
		if (! empty($arrayfields['t.code_journal']['checked']))
		{
			$accountingjournal = new AccountingJournal($db);
			$result = $accountingjournal->fetch('',$line->code_journal);
			$journaltoshow = (($result > 0)?$accountingjournal->getNomUrl(0,0,0,'',0) : $line->code_journal);
			print '<td align="center">' . $journaltoshow . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Creation operation date
		if (! empty($arrayfields['t.date_creation']['checked']))
		{
			print '<td align="center">' . dol_print_date($line->date_creation, 'dayhour') . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Modification operation date
		if (! empty($arrayfields['t.tms']['checked']))
		{
			print '<td align="center">' . dol_print_date($line->date_modification, 'dayhour') . '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Action column
		print '<td align="center" class="nowraponall">';
		print '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?piece_num=' . $line->piece_num . $param . '&page=' . $page . ($sortfield ? '&sortfield='.$sortfield : '') . ($sortorder ? '&sortorder='.$sortorder : '') . '">' . img_edit() . '</a>&nbsp;';
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delmouv&mvt_num=' . $line->piece_num . $param . '&page=' . $page . ($sortfield ? '&sortfield='.$sortfield : '') . ($sortorder ? '&sortorder='.$sortorder : '') . '">' . img_delete() . '</a>';
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totaldebitfield']) || isset($totalarray['totalcreditfield']))
	{
		$i=0;
		print '<tr class="liste_total">';
		while ($i < $totalarray['nbfield'])
		{
			$i++;
				if ($i == 1)
				{
					if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
					else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
				}
				elseif ($totalarray['totaldebitfield'] == $i)  print '<td class="nowrap right">'.price($totalarray['totaldebit']).'</td>';
				elseif ($totalarray['totalcreditfield'] == $i) print '<td class="nowrap right">'.price($totalarray['totalcredit']).'</td>';
				else print '<td></td>';
		}
		print '</tr>';
	}
}

print "</table>";
print '</div>';

// TODO Replace this with mass delete action
print '<div class="tabsAction tabsActionNoBottom">' . "\n";
print '<a class="butActionDelete" name="button_delmvt" href="'.$_SERVER["PHP_SELF"].'?action=delbookkeepingyear'.($param?'&'.$param:'').'">' . $langs->trans("DeleteMvt") . '</a>';
print '</div>';


print '</form>';

// End of page
llxFooter();
$db->close();
