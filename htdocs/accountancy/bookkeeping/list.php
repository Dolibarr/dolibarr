<?php
/* Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2020  Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file		htdocs/accountancy/bookkeeping/list.php
 * \ingroup		Accountancy (Double entries)
 * \brief 		List operation of book keeping
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "compta"));

$socid = GETPOST('socid', 'int');

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
$search_date_export_start = dol_mktime(0, 0, 0, GETPOST('date_export_startmonth', 'int'), GETPOST('date_export_startday', 'int'), GETPOST('date_export_startyear', 'int'));
$search_date_export_end = dol_mktime(0, 0, 0, GETPOST('date_export_endmonth', 'int'), GETPOST('date_export_endday', 'int'), GETPOST('date_export_endyear', 'int'));

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
$search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

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
if ($sortfield == "") $sortfield = "t.piece_num,t.rowid";

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new BookKeeping($db);
$hookmanager->initHooks(array('bookkeepinglist'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

if (!in_array($action, array('export_file', 'delmouv', 'delmouvconfirm')) && !GETPOSTISSET('begin') && !GETPOSTISSET('formfilteraction') && GETPOST('page', 'int') == '' && !GETPOST('noreset', 'int') && $user->rights->accounting->mouvements->export)
{
	if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('restore_lastsearch_values') && !GETPOST('search_accountancy_code_start'))
	{
		$query = "SELECT date_start, date_end from ".MAIN_DB_PREFIX."accounting_fiscalyear ";
		$query .= " where date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."' limit 1";
		$res = $db->query($query);

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
}


$arrayfields = array(
	't.piece_num'=>array('label'=>$langs->trans("TransactionNumShort"), 'checked'=>1),
	't.code_journal'=>array('label'=>$langs->trans("Codejournal"), 'checked'=>1),
	't.doc_date'=>array('label'=>$langs->trans("Docdate"), 'checked'=>1),
	't.doc_ref'=>array('label'=>$langs->trans("Piece"), 'checked'=>1),
	't.numero_compte'=>array('label'=>$langs->trans("AccountAccountingShort"), 'checked'=>1),
	't.subledger_account'=>array('label'=>$langs->trans("SubledgerAccount"), 'checked'=>1),
	't.label_operation'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	't.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1),
	't.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1),
	't.lettering_code'=>array('label'=>$langs->trans("LetteringCode"), 'checked'=>1),
	't.date_creation'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0),
	't.tms'=>array('label'=>$langs->trans("DateModification"), 'checked'=>0),
	't.date_export'=>array('label'=>$langs->trans("DateExport"), 'checked'=>1),
);

if (empty($conf->global->ACCOUNTING_ENABLE_LETTERING)) unset($arrayfields['t.lettering_code']);

$listofformat = AccountancyExport::getType();
$formatexportset = $conf->global->ACCOUNTING_EXPORT_MODELCSV;
if (empty($listofformat[$formatexportset])) $formatexportset = 1;

$error = 0;


/*
 * Actions
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
		$search_date_export_start = '';
		$search_date_export_end = '';
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
		$tmp = dol_getdate($search_date_start);
		$param .= '&search_date_startmonth='.urlencode($tmp['mon']).'&search_date_startday='.urlencode($tmp['mday']).'&search_date_startyear='.urlencode($tmp['year']);
	}
	if (!empty($search_date_end)) {
		$filter['t.doc_date<='] = $search_date_end;
		$tmp = dol_getdate($search_date_end);
		$param .= '&search_date_endmonth='.urlencode($tmp['mon']).'&search_date_endday='.urlencode($tmp['mday']).'&search_date_endyear='.urlencode($tmp['year']);
	}
	if (!empty($search_doc_date)) {
		$filter['t.doc_date'] = $search_doc_date;
		$tmp = dol_getdate($search_doc_date);
		$param .= '&doc_datemonth='.urlencode($tmp['mon']).'&doc_dateday='.urlencode($tmp['mday']).'&doc_dateyear='.urlencode($tmp['year']);
	}
	if (!empty($search_doc_type)) {
		$filter['t.doc_type'] = $search_doc_type;
		$param .= '&search_doc_type='.urlencode($search_doc_type);
	}
	if (!empty($search_doc_ref)) {
		$filter['t.doc_ref'] = $search_doc_ref;
		$param .= '&search_doc_ref='.urlencode($search_doc_ref);
	}
	if (!empty($search_accountancy_code)) {
		$filter['t.numero_compte'] = $search_accountancy_code;
		$param .= '&search_accountancy_code='.urlencode($search_accountancy_code);
	}
	if (!empty($search_accountancy_code_start)) {
		$filter['t.numero_compte>='] = $search_accountancy_code_start;
		$param .= '&search_accountancy_code_start='.urlencode($search_accountancy_code_start);
	}
	if (!empty($search_accountancy_code_end)) {
		$filter['t.numero_compte<='] = $search_accountancy_code_end;
		$param .= '&search_accountancy_code_end='.urlencode($search_accountancy_code_end);
	}
	if (!empty($search_accountancy_aux_code)) {
		$filter['t.subledger_account'] = $search_accountancy_aux_code;
		$param .= '&search_accountancy_aux_code='.urlencode($search_accountancy_aux_code);
	}
	if (!empty($search_accountancy_aux_code_start)) {
		$filter['t.subledger_account>='] = $search_accountancy_aux_code_start;
		$param .= '&search_accountancy_aux_code_start='.urlencode($search_accountancy_aux_code_start);
	}
	if (!empty($search_accountancy_aux_code_end)) {
		$filter['t.subledger_account<='] = $search_accountancy_aux_code_end;
		$param .= '&search_accountancy_aux_code_end='.urlencode($search_accountancy_aux_code_end);
	}
	if (!empty($search_mvt_label)) {
		$filter['t.label_operation'] = $search_mvt_label;
		$param .= '&search_mvt_label='.urlencode($search_mvt_label);
	}
	if (!empty($search_direction)) {
		$filter['t.sens'] = $search_direction;
		$param .= '&search_direction='.urlencode($search_direction);
	}
	if (!empty($search_ledger_code)) {
		$filter['t.code_journal'] = $search_ledger_code;
		$param .= '&search_ledger_code='.urlencode($search_ledger_code);
	}
	if (!empty($search_mvt_num)) {
		$filter['t.piece_num'] = $search_mvt_num;
		$param .= '&search_mvt_num='.urlencode($search_mvt_num);
	}
	if (!empty($search_date_creation_start)) {
		$filter['t.date_creation>='] = $search_date_creation_start;
		$tmp = dol_getdate($search_date_creation_start);
		$param .= '&date_creation_startmonth='.urlencode($tmp['mon']).'&date_creation_startday='.urlencode($tmp['mday']).'&date_creation_startyear='.urlencode($tmp['year']);
	}
	if (!empty($search_date_creation_end)) {
		$filter['t.date_creation<='] = $search_date_creation_end;
		$tmp = dol_getdate($search_date_creation_end);
		$param .= '&date_creation_endmonth='.urlencode($tmp['mon']).'&date_creation_endday='.urlencode($tmp['mday']).'&date_creation_endyear='.urlencode($tmp['year']);
	}
	if (!empty($search_date_modification_start)) {
		$filter['t.tms>='] = $search_date_modification_start;
		$tmp = dol_getdate($search_date_modification_start);
		$param .= '&date_modification_startmonth='.urlencode($tmp['mon']).'&date_modification_startday='.urlencode($tmp['mday']).'&date_modification_startyear='.urlencode($tmp['year']);
	}
	if (!empty($search_date_modification_end)) {
		$filter['t.tms<='] = $search_date_modification_end;
		$tmp = dol_getdate($search_date_modification_end);
		$param .= '&date_modification_endmonth='.urlencode($tmp['mon']).'&date_modification_endday='.urlencode($tmp['mday']).'&date_modification_endyear='.urlencode($tmp['year']);
	}
	if (!empty($search_date_export_start)) {
		$filter['t.date_export>='] = $search_date_export_start;
		$tmp = dol_getdate($search_date_export_start);
		$param .= '&date_export_startmonth='.urlencode($tmp['mon']).'&date_export_startday='.urlencode($tmp['mday']).'&date_export_startyear='.urlencode($tmp['year']);
	}
	if (!empty($search_date_export_end)) {
		$filter['t.date_export<='] = $search_date_export_end;
		$tmp = dol_getdate($search_date_export_end);
		$param .= '&date_export_endmonth='.urlencode($tmp['mon']).'&date_export_endday='.urlencode($tmp['mday']).'&date_export_endyear='.urlencode($tmp['year']);
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
}

if ($action == 'delbookkeeping' && $user->rights->accounting->mouvements->supprimer) {
	$import_key = GETPOST('importkey', 'alpha');

	if (!empty($import_key)) {
		$result = $object->deleteByImportkey($import_key);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		// Make a redirect to avoid to launch the delete later after a back button
		header("Location: list.php".($param ? '?'.$param : ''));
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
		header("Location: list.php".($param ? '?'.$param : ''));
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

		header("Location: list.php?noreset=1".($param ? '&'.$param : ''));
		exit;
	}
}
if ($action == 'setreexport') {
	$setreexport = GETPOST('value', 'int');
	if (!dolibarr_set_const($db, "ACCOUNTING_REEXPORT", $setreexport, 'yesno', 0, '', $conf->entity)) $error++;

	if (!$error) {
		if ($conf->global->ACCOUNTING_REEXPORT == 1) {
			setEventMessages($langs->trans("ExportOfPiecesAlreadyExportedIsEnable"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ExportOfPiecesAlreadyExportedIsDisable"), null, 'mesgs');
		}
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Build and execute select (used by page and export action)
// must de set after the action that set $filter
// --------------------------------------------------------------------

$sql = 'SELECT';
$sql .= ' t.rowid,';
$sql .= " t.doc_date,";
$sql .= " t.doc_type,";
$sql .= " t.doc_ref,";
$sql .= " t.fk_doc,";
$sql .= " t.fk_docdet,";
$sql .= " t.thirdparty_code,";
$sql .= " t.subledger_account,";
$sql .= " t.subledger_label,";
$sql .= " t.numero_compte,";
$sql .= " t.label_compte,";
$sql .= " t.label_operation,";
$sql .= " t.debit,";
$sql .= " t.credit,";
$sql .= " t.lettering_code,";
$sql .= " t.montant as amount,";
$sql .= " t.sens,";
$sql .= " t.fk_user_author,";
$sql .= " t.import_key,";
$sql .= " t.code_journal,";
$sql .= " t.journal_label,";
$sql .= " t.piece_num,";
$sql .= " t.date_creation,";
$sql .= " t.tms as date_modification,";
$sql .= " t.date_export";
$sql .= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' as t';
// Manage filter
$sqlwhere = array();
if (count($filter) > 0) {
	foreach ($filter as $key => $value) {
		if ($key == 't.doc_date') {
			$sqlwhere[] = $key.'=\''.$db->idate($value).'\'';
		} elseif ($key == 't.doc_date>=' || $key == 't.doc_date<=') {
			$sqlwhere[] = $key.'\''.$db->idate($value).'\'';
		} elseif ($key == 't.numero_compte>=' || $key == 't.numero_compte<=' || $key == 't.subledger_account>=' || $key == 't.subledger_account<=') {
			$sqlwhere[] = $key.'\''.$db->escape($value).'\'';
		} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
			$sqlwhere[] = $key.'='.$value;
		} elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
			$sqlwhere[] = $key.' LIKE \''.$db->escape($value).'%\'';
		} elseif ($key == 't.date_creation>=' || $key == 't.date_creation<=') {
			$sqlwhere[] = $key.'\''.$db->idate($value).'\'';
		} elseif ($key == 't.tms>=' || $key == 't.tms<=') {
			$sqlwhere[] = $key.'\''.$db->idate($value).'\'';
		} elseif ($key == 't.date_export>=' || $key == 't.date_export<=') {
			$sqlwhere[] = $key.'\''.$db->idate($value).'\'';
		} elseif ($key == 't.credit' || $key == 't.debit') {
			$sqlwhere[] = natural_search($key, $value, 1, 1);
		} elseif ($key == 't.reconciled_option') {
			$sqlwhere[] = 't.lettering_code IS NULL';
		} else {
			$sqlwhere[] = natural_search($key, $value, 0, 1);
		}
	}
}
$sql .= ' WHERE t.entity IN ('.getEntity('accountancy').')';
if ($conf->global->ACCOUNTING_REEXPORT == 0) {
	$sql .= " AND t.date_export IS NULL";
}
if (count($sqlwhere) > 0) {
	$sql .= ' AND '.implode(' AND ', $sqlwhere);
}
if (!empty($sortfield)) {
	$sql .= $db->order($sortfield, $sortorder);
}
//print $sql;


// Export into a file with format defined into setup (FEC, CSV, ...)
// Must be after definition of $sql
if ($action == 'export_file' && $user->rights->accounting->mouvements->export) {
	// TODO Replace the fetchAll + ->export later that consume too much memory on large export with the query($sql) and loop on each line to export them.
	$result = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter, 'AND', $conf->global->ACCOUNTING_REEXPORT);

	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		// Export files
		$accountancyexport = new AccountancyExport($db);
		$accountancyexport->export($object->lines, $formatexportset);

		if (!empty($accountancyexport->errors))
		{
			setEventMessages('', $accountancyexport->errors, 'errors');
		} else {
			// Specify as export : update field date_export
			$error = 0;
			$db->begin();

			if (is_array($object->lines))
			{
				foreach ($object->lines as $movement)
				{
					$now = dol_now();

					$sql = " UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping";
					$sql .= " SET date_export = '".$db->idate($now)."'";
					$sql .= " WHERE rowid = ".$movement->id;

					dol_syslog("/accountancy/bookeeping/list.php Function export_file Specify movements as exported sql=".$sql, LOG_DEBUG);
					$result = $db->query($sql);
					if (!$result)
					{
						$error++;
						break;
					}
				}
			}

			if (!$error)
			{
				$db->commit();
				// setEventMessages($langs->trans("AllExportedMovementsWereRecordedAsExported"), null, 'mesgs');
			} else {
				$error++;
				$db->rollback();
				setEventMessages($langs->trans("NotAllExportedMovementsCouldBeRecordedAsExported"), null, 'errors');
			}
		}
		exit;
	}
}


/*
 * View
 */

$formother = new FormOther($db);
$formfile = new FormFile($db);

$title_page = $langs->trans("Operations").' - '.$langs->trans("Journals");

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords)
{
	$num = $nbtotalofrecords;
} else {
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title_page);


if ($action == 'delmouv') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?mvt_num='.GETPOST('mvt_num').$param, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvtPartial'), 'delmouvconfirm', '', 0, 1);
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

//$param='';	param started before
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.urlencode($optioncss).'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.urlencode($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.urlencode($sortorder).'">';

if (count($filter)) $buttonLabel = $langs->trans("ExportFilteredList");
else $buttonLabel = $langs->trans("ExportList");

$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	// Button re-export
	if (!empty($conf->global->ACCOUNTING_REEXPORT)) {
		$newcardbutton = '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?action=setreexport&token='.newToken().'&value=0'.($param ? '&'.$param : '').'">'.img_picto($langs->trans("Activated"), 'switch_on').'</a> ';
	} else {
		$newcardbutton = '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?action=setreexport&token='.newToken().'&value=1'.($param ? '&'.$param : '').'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a> ';
	}
	$newcardbutton .= '<span class="valignmiddle marginrightonly">'.$langs->trans("IncludeDocsAlreadyExported").'</span>';

	if (!empty($user->rights->accounting->mouvements->export)) $newcardbutton .= dolGetButtonTitle($buttonLabel, $langs->trans("ExportFilteredList").' ('.$listofformat[$formatexportset].')', 'fa fa-file-export paddingleft', $_SERVER["PHP_SELF"].'?action=export_file'.($param ? '&'.$param : ''), $user->rights->accounting->mouvements->export);

	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?'.$param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/accountancy/bookkeeping/listbyaccount.php?'.$param, '', 1, array('morecss' => 'marginleftonly'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT.'/accountancy/bookkeeping/listbysubaccount.php?'.$param, '', 1, array('morecss' => 'marginleftonly'));

	$url = './card.php?action=create';
	if (!empty($socid)) $url .= '&socid='.$socid;
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', $url, '', $user->rights->accounting->mouvements->creer);
}

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

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
if (!empty($arrayfields['t.code_journal']['checked']))
{
	print '<td class="liste_titre center"><input type="text" name="search_ledger_code" size="3" value="'.(is_array($search_ledger_code) ? join('|', $search_ledger_code) : $search_ledger_code).'"></td>';
}
// Date document
if (!empty($arrayfields['t.doc_date']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Ref document
if (!empty($arrayfields['t.doc_ref']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="'.dol_escape_htmltag($search_doc_ref).'"></td>';
}
// Accountancy account
if (!empty($arrayfields['t.numero_compte']['checked']))
{
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	print $langs->trans('From').' ';
	print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array(), 1, 1, 'maxwidth200');
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to').' ';
	print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', 1, array(), 1, 1, 'maxwidth200');
	print '</div>';
	print '</td>';
}
// Subledger account
if (!empty($arrayfields['t.subledger_account']['checked']))
{
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
	// use setup of keypress to select thirdparty and this hang browser on large database.
	if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
	{
		print $langs->trans('From').' ';
		print $formaccounting->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', 1);
	} else {
		print '<input type="text" class="maxwidth100" name="search_accountancy_aux_code_start" value="'.$search_accountancy_aux_code_start.'" placeholder="'.$langs->trans("From").'">';
	}
	print '</div>';
	print '<div class="nowrap">';
	// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
	// use setup of keypress to select thirdparty and this hang browser on large database.
	if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
	{
		print $langs->trans('to').' ';
		print $formaccounting->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', 1);
	} else {
		print '<input type="text" class="maxwidth100" name="search_accountancy_aux_code_end" value="'.$search_accountancy_aux_code_end.'" placeholder="'.$langs->trans("to").'">';
	}
	print '</div>';
	print '</td>';
}
// Label operation
if (!empty($arrayfields['t.label_operation']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" size="7" class="flat" name="search_mvt_label" value="'.$search_mvt_label.'"/>';
	print '</td>';
}
// Debit
if (!empty($arrayfields['t.debit']['checked']))
{
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat" name="search_debit" size="4" value="'.dol_escape_htmltag($search_debit).'">';
	print '</td>';
}
// Credit
if (!empty($arrayfields['t.credit']['checked']))
{
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat" name="search_credit" size="4" value="'.dol_escape_htmltag($search_credit).'">';
	print '</td>';
}
// Lettering code
if (!empty($arrayfields['t.lettering_code']['checked']))
{
	print '<td class="liste_titre center">';
	print '<input type="text" size="3" class="flat" name="search_lettering_code" value="'.$search_lettering_code.'"/>';
	print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"'.($search_not_reconciled == 'notreconciled' ? ' checked' : '').'>'.$langs->trans("NotReconciled").'</span>';
	print '</td>';
}


// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Date creation
if (!empty($arrayfields['t.date_creation']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_creation_start, 'date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_creation_end, 'date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['t.tms']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_modification_start, 'date_modification_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_modification_end, 'date_modification_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '</td>';
}
// Date export
if (!empty($arrayfields['t.date_export']['checked']))
{
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_export_start, 'date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_export_end, 'date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Action column
print '<td class="liste_titre center">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (!empty($arrayfields['t.piece_num']['checked']))			print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.code_journal']['checked']))			print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.doc_date']['checked']))				print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.doc_ref']['checked']))				print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.numero_compte']['checked']))		print_liste_field_titre($arrayfields['t.numero_compte']['label'], $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.subledger_account']['checked']))	print_liste_field_titre($arrayfields['t.subledger_account']['label'], $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.label_operation']['checked']))		print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['t.debit']['checked']))				print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['t.credit']['checked']))				print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['t.lettering_code']['checked']))		print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['t.date_creation']['checked']))		print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER['PHP_SELF'], "t.date_creation", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.tms']['checked']))					print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], "t.tms", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['t.date_export']['checked']))          print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";


$line = new BookKeepingLine();

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	$line->id = $obj->rowid;
	$line->doc_date = $db->jdate($obj->doc_date);
	$line->doc_type = $obj->doc_type;
	$line->doc_ref = $obj->doc_ref;
	$line->fk_doc = $obj->fk_doc;
	$line->fk_docdet = $obj->fk_docdet;
	$line->thirdparty_code = $obj->thirdparty_code;
	$line->subledger_account = $obj->subledger_account;
	$line->subledger_label = $obj->subledger_label;
	$line->numero_compte = $obj->numero_compte;
	$line->label_compte = $obj->label_compte;
	$line->label_operation = $obj->label_operation;
	$line->debit = $obj->debit;
	$line->credit = $obj->credit;
	$line->montant = $obj->amount; // deprecated
	$line->amount = $obj->amount;
	$line->sens = $obj->sens;
	$line->lettering_code = $obj->lettering_code;
	$line->fk_user_author = $obj->fk_user_author;
	$line->import_key = $obj->import_key;
	$line->code_journal = $obj->code_journal;
	$line->journal_label = $obj->journal_label;
	$line->piece_num = $obj->piece_num;
	$line->date_creation = $db->jdate($obj->date_creation);
	$line->date_modification = $db->jdate($obj->date_modification);
	$line->date_export = $db->jdate($obj->date_export);

	$total_debit += $line->debit;
	$total_credit += $line->credit;

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

		print '<td class="nowrap">';

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		// Picto + Ref
		print '<td class="nobordernopadding nowrap">';

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

	// Account number
	if (!empty($arrayfields['t.numero_compte']['checked']))
	{
		print '<td>'.length_accountg($line->numero_compte).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Subledger account
	if (!empty($arrayfields['t.subledger_account']['checked']))
	{
		print '<td>'.length_accounta($line->subledger_account).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Label operation
	if (!empty($arrayfields['t.label_operation']['checked']))
	{
		print '<td>'.$line->label_operation.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Amount debit
	if (!empty($arrayfields['t.debit']['checked']))
	{
		print '<td class="nowrap right">'.($line->debit != 0 ? price($line->debit) : '').'</td>';
		if (!$i) $totalarray['nbfield']++;
		if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
		$totalarray['val']['totaldebit'] += $line->debit;
	}

	// Amount credit
	if (!empty($arrayfields['t.credit']['checked']))
	{
		print '<td class="nowrap right">'.($line->credit != 0 ? price($line->credit) : '').'</td>';
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

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Creation operation date
	if (!empty($arrayfields['t.date_creation']['checked']))
	{
		print '<td class="center">'.dol_print_date($line->date_creation, 'dayhour').'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Modification operation date
	if (!empty($arrayfields['t.tms']['checked']))
	{
		print '<td class="center">'.dol_print_date($line->date_modification, 'dayhour').'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Exported operation date
	if (!empty($arrayfields['t.date_export']['checked']))
	{
		print '<td class="center">'.dol_print_date($line->date_export, 'dayhour').'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Action column
	print '<td class="nowraponall center">';
	if (empty($line->date_export)) {
		if ($user->rights->accounting->mouvements->creer) {
			print '<a class="editfielda paddingleft marginrightonly" href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?piece_num='.$line->piece_num.$param.'&page='.$page.($sortfield ? '&sortfield='.$sortfield : '').($sortorder ? '&sortorder='.$sortorder : '').'">'.img_edit().'</a>';
		}
		if ($user->rights->accounting->mouvements->supprimer) {
			print '<a class="reposition paddingleft marginrightonly" href="'.$_SERVER['PHP_SELF'].'?action=delmouv&mvt_num='.$line->piece_num.$param.'&page='.$page.($sortfield ? '&sortfield='.$sortfield : '').($sortorder ? '&sortorder='.$sortorder : '').'">'.img_delete().'</a>';
		}
	}
	print '</td>';

	if (!$i) $totalarray['nbfield']++;

	print "</tr>\n";

	$i++;
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
