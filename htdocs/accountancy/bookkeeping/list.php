<?php
/* Copyright (C) 2013-2016 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@zendsi.com>
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

// Langs
$langs->load("accountancy");

$page = GETPOST("page");
$sortorder = GETPOST("sortorder");
$sortfield = GETPOST("sortfield");
$action = GETPOST('action', 'alpha');
$search_mvt_num = GETPOST('search_mvt_num', 'int');
$search_doc_type = GETPOST("search_doc_type");
$search_doc_ref = GETPOST("search_doc_ref");
$search_date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));
$search_doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));

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
$search_ledger_code = GETPOST('search_ledger_code', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit', 'int'):(empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page','int');
if ($page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") $sortorder = "ASC";
if ($sortfield == "") $sortfield = "t.rowid";


$object = new BookKeeping($db);

$formaccounting = new FormAccounting($db);
$formother = new FormOther($db);
$form = new Form($db);


if ($action != 'export_file' && ! isset($_POST['begin']) && ! isset($_GET['begin']) && ! isset($_POST['formfilteraction']) && empty($page)) {
    $search_date_start = dol_mktime(0, 0, 0, 1, 1, dol_print_date(dol_now(), '%Y'));
    $search_date_end = dol_mktime(0, 0, 0, 12, 31, dol_print_date(dol_now(), '%Y'));
}



/*
 * Action
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

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
}

// Must be after the remove filter action, before the export.
$param = '';
$filter = array ();
if (! empty($search_date_start)) {
    $filter['t.doc_date>='] = $search_date_start;
    $tmp=dol_getdate($search_date_start);
    $param .= '&date_startmonth=' . $tmp['mon'] . '&date_startday=' . $tmp['mday'] . '&date_startyear=' . $tmp['year'];
}
if (! empty($search_date_end)) {
    $filter['t.doc_date<='] = $search_date_end;
    $tmp=dol_getdate($search_date_end);
    $param .= '&date_endmonth=' . $tmp['mon'] . '&date_endday=' . $tmp['mday'] . '&date_endyear=' . $tmp['year'];
}
if (! empty($search_doc_date)) {
    $filter['t.doc_date'] = $search_doc_date;
    $tmp=dol_getdate($search_doc_date);
    $param .= '&doc_datemonth=' . $tmp['mon'] . '&doc_dateday=' . $tmp['mday'] . '&doc_dateyear=' . $tmp['year'];
}
if (! empty($search_doc_type)) {
    $filter['t.doc_type'] = $search_doc_type;
    $param .= '&search_doc_type=' . $search_doc_type;
}
if (! empty($search_doc_ref)) {
    $filter['t.doc_ref'] = $search_doc_ref;
    $param .= '&search_doc_ref=' . $search_doc_ref;
}
if (! empty($search_accountancy_code)) {
    $filter['t.numero_compte'] = $search_accountancy_code;
    $param .= '&search_accountancy_code=' . $search_accountancy_code;
}
if (! empty($search_accountancy_code_start)) {
    $filter['t.numero_compte>='] = $search_accountancy_code_start;
    $param .= '&search_accountancy_code_start=' . $search_accountancy_code_start;
}
if (! empty($search_accountancy_code_end)) {
    $filter['t.numero_compte<='] = $search_accountancy_code_end;
    $param .= '&search_accountancy_code_end=' . $search_accountancy_code_end;
}
if (! empty($search_accountancy_aux_code)) {
    $filter['t.subledger_account'] = $search_accountancy_aux_code;
    $param .= '&search_accountancy_aux_code=' . $search_accountancy_aux_code;
}
if (! empty($search_accountancy_aux_code_start)) {
    $filter['t.subledger_account>='] = $search_accountancy_aux_code_start;
    $param .= '&search_accountancy_aux_code_start=' . $search_accountancy_aux_code_start;
}
if (! empty($search_accountancy_aux_code_end)) {
    $filter['t.subledger_account<='] = $search_accountancy_aux_code_end;
    $param .= '&search_accountancy_aux_code_end=' . $search_accountancy_aux_code_end;
}
if (! empty($search_mvt_label)) {
    $filter['t.label_operation'] = $search_mvt_label;
    $param .= '&search_mvt_label=' . $search_mvt_label;
}
if (! empty($search_direction)) {
    $filter['t.sens'] = $search_direction;
    $param .= '&search_direction=' . $search_direction;
}
if (! empty($search_ledger_code)) {
    $filter['t.code_journal'] = $search_ledger_code;
    $param .= '&search_ledger_code=' . $search_ledger_code;
}
if (! empty($search_mvt_num)) {
    $filter['t.piece_num'] = $search_mvt_num;
    $param .= '&search_mvt_num=' . $search_mvt_num;
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
		Header("Location: list.php");
		exit;
	}
}

// Export into a file with format defined into setup
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
        if (!empty($accountancyexport->errors)) {
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
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?mvt_num=' . GETPOST('mvt_num'), $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvtPartial'), 'delmouvconfirm', '', 0, 1);
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
			'type' => 'other',       // We don't use select here, the journal_array is already a select html component
			'label' => $langs->trans('DelJournal'),
			'value' => $journal_array,
			'default' => $deljournal
	);

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delbookkeepingyearconfirm', $form_question, 0, 1, 250);
	print $formconfirm;
}

//$param='';    param started before
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
$button.=' ('.$listofformat[$conf->global->ACCOUNTING_EXPORT_MODELCSV].')';
$button.= '</a>';

$groupby = ' <a class="nohover" href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/listbyaccount.php"">' . $langs->trans("GroupByAccountAccounting") . '</a>';

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $button, $result, $nbtotalofrecords, 'title_accountancy', 0, $groupby, '', $limit);

print '<div class="tabsAction tabsActionNoBottom">' . "\n";
print '<div class="inline-block divButAction"><a class="butAction" href="./card.php?action=create">' . $langs->trans("NewAccountingMvt") . '</a></div>';
print '<div class="inline-block divButAction"><a class="butActionDelete" name="button_delmvt" href="'.$_SERVER["PHP_SELF"].'?action=delbookkeepingyear'.($param?'&'.$param:'').'">' . $langs->trans("DelBookKeeping") . '</a></div>';

print '</div>';

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
print '<td class="liste_titre center">';
print '<div class="nowrap">';
print $langs->trans('From') . ' ';
print $form->select_date($search_date_start, 'date_start', 0, 0, 1);
print '</div>';
print '<div class="nowrap">';
print $langs->trans('to') . ' ';
print $form->select_date($search_date_end, 'date_end', 0, 0, 1);
print '</div>';
print '</td>';
print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="' . dol_escape_htmltag($search_doc_ref) . '"></td>';
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
print '<td class="liste_titre">';
print '<div class="nowrap">';
print $langs->trans('From').' ';
// TODO For the moment we keep a fre input text instead of a combo. The select_auxaccount has problem because it does not
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
// TODO For the moment we keep a fre input text instead of a combo. The select_auxaccount has problem because it does not
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
print '<td class="liste_titre">';
print '<input type="text" size="7" class="flat" name="search_mvt_label" value="' . $search_mvt_label . '"/>';
print '</td>';
print '<td class="liste_titre center">&nbsp;</td>';
print '<td class="liste_titre center">&nbsp;</td>';
print '<td class="liste_titre center"><input type="text" name="search_ledger_code" size="3" value="' . $search_ledger_code . '"></td>';
print '<td class="liste_titre center">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre("TransactionNumShort", $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("Docdate", $_SERVER['PHP_SELF'], "t.doc_date", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Docref", $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("AccountAccountingShort", $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("SubledgerAccount", $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("Label", $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("Debit", $_SERVER['PHP_SELF'], "t.debit", "", $param, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre("Credit", $_SERVER['PHP_SELF'], "t.credit", "", $param, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre("Codejournal", $_SERVER['PHP_SELF'], "t.code_journal", "", $param, 'align="center"', $sortfield, $sortorder);
$checkpicto='';
if ($massactionbutton) $checkpicto=$form->showCheckAddButtons('checkforselect', 1);
print_liste_field_titre($checkpicto, $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
print "</tr>\n";


$total_debit = 0;
$total_credit = 0;

$i=0;
while ($i < min($num, $limit))
{
	$line = $object->lines[$i];

	$total_debit += $line->debit;
	$total_credit += $line->credit;

	print '<tr class="oddeven">';

	print '<td><a href="./card.php?piece_num=' . $line->piece_num . '">' . $line->piece_num . '</a></td>';
	print '<td align="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
	print '<td class="nowrap">' . $line->doc_ref . '</td>';
	print '<td>' . length_accountg($line->numero_compte) . '</td>';
	print '<td>' . length_accounta($line->subledger_account) . '</td>';
	print '<td>' . $line->label_operation . '</td>';
	print '<td align="right">' . ($line->debit ? price($line->debit) : ''). '</td>';
	print '<td align="right">' . ($line->credit ? price($line->credit) : '') . '</td>';

	$accountingjournal = new AccountingJournal($db);
	$result = $accountingjournal->fetch('',$line->code_journal);
	$journaltoshow = (($result > 0)?$accountingjournal->getNomUrl(0,0,0,'',0) : $line->code_journal);
	print '<td align="center">' . $journaltoshow . '</td>';

	print '<td align="center">';
	print '<a href="./card.php?piece_num=' . $line->piece_num . '">' . img_edit() . '</a>&nbsp;';
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delmouv&mvt_num=' . $line->piece_num . $param . '&page=' . $page . '">' . img_delete() . '</a>';
	print '</td>';
	print "</tr>\n";

	$i++;
}

print '<tr class="liste_total">';
if ($num < $limit) print '<td align="left" colspan="6">'.$langs->trans("Total").'</td>';
else print '<td align="left" colspan="6">'.$langs->trans("Totalforthispage").'</td>';
print '</td>';
print '<td  align="right">';
print price($total_debit);
print '</td>';
print '<td align="right">';
print price($total_credit);
print '</td>';
print '<td colspan="2"></td>';
print '</tr>';

print "</table>";
print '</div>';

print '</form>';

llxFooter();

$db->close();
