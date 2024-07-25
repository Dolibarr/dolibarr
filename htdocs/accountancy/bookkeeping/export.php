<?php
/* Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2022       Lionel Vessiller        <lvessiller@open-dsi.fr>
 * Copyright (C) 2016-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022       Progiseize              <a.bisotti@progiseize.fr>
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
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
 * \file		htdocs/accountancy/bookkeeping/export.php
 * \ingroup		Accountancy (Double entries)
 * \brief 		Export operation of book keeping
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/lettering.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "compta"));

$socid = GETPOSTINT('socid');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bookkeepinglist';
$search_mvt_num = GETPOST('search_mvt_num', 'alpha');
$search_doc_type = GETPOST("search_doc_type", 'alpha');
$search_doc_ref = GETPOST("search_doc_ref", 'alpha');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_doc_date = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
$search_date_creation_startyear = GETPOSTINT('search_date_creation_startyear');
$search_date_creation_startmonth = GETPOSTINT('search_date_creation_startmonth');
$search_date_creation_startday = GETPOSTINT('search_date_creation_startday');
$search_date_creation_endyear = GETPOSTINT('search_date_creation_endyear');
$search_date_creation_endmonth = GETPOSTINT('search_date_creation_endmonth');
$search_date_creation_endday = GETPOSTINT('search_date_creation_endday');
$search_date_creation_start = dol_mktime(0, 0, 0, $search_date_creation_startmonth, $search_date_creation_startday, $search_date_creation_startyear);
$search_date_creation_end = dol_mktime(23, 59, 59, $search_date_creation_endmonth, $search_date_creation_endday, $search_date_creation_endyear);
$search_date_modification_startyear = GETPOSTINT('search_date_modification_startyear');
$search_date_modification_startmonth = GETPOSTINT('search_date_modification_startmonth');
$search_date_modification_startday = GETPOSTINT('search_date_modification_startday');
$search_date_modification_endyear = GETPOSTINT('search_date_modification_endyear');
$search_date_modification_endmonth = GETPOSTINT('search_date_modification_endmonth');
$search_date_modification_endday = GETPOSTINT('search_date_modification_endday');
$search_date_modification_start = dol_mktime(0, 0, 0, $search_date_modification_startmonth, $search_date_modification_startday, $search_date_modification_startyear);
$search_date_modification_end = dol_mktime(23, 59, 59, $search_date_modification_endmonth, $search_date_modification_endday, $search_date_modification_endyear);
$search_date_export_startyear = GETPOSTINT('search_date_export_startyear');
$search_date_export_startmonth = GETPOSTINT('search_date_export_startmonth');
$search_date_export_startday = GETPOSTINT('search_date_export_startday');
$search_date_export_endyear = GETPOSTINT('search_date_export_endyear');
$search_date_export_endmonth = GETPOSTINT('search_date_export_endmonth');
$search_date_export_endday = GETPOSTINT('search_date_export_endday');
$search_date_export_start = dol_mktime(0, 0, 0, $search_date_export_startmonth, $search_date_export_startday, $search_date_export_startyear);
$search_date_export_end = dol_mktime(23, 59, 59, $search_date_export_endmonth, $search_date_export_endday, $search_date_export_endyear);
$search_date_validation_startyear = GETPOSTINT('search_date_validation_startyear');
$search_date_validation_startmonth = GETPOSTINT('search_date_validation_startmonth');
$search_date_validation_startday = GETPOSTINT('search_date_validation_startday');
$search_date_validation_endyear = GETPOSTINT('search_date_validation_endyear');
$search_date_validation_endmonth = GETPOSTINT('search_date_validation_endmonth');
$search_date_validation_endday = GETPOSTINT('search_date_validation_endday');
$search_date_validation_start = dol_mktime(0, 0, 0, $search_date_validation_startmonth, $search_date_validation_startday, $search_date_validation_startyear);
$search_date_validation_end = dol_mktime(23, 59, 59, $search_date_validation_endmonth, $search_date_validation_endday, $search_date_validation_endyear);
$search_import_key = GETPOST("search_import_key", 'alpha');

//var_dump($search_date_start);exit;
if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt.x") || GETPOST("button_delmvt")) {
	$action = 'delbookkeepingyear';
}
if (GETPOST("button_export_file_x") || GETPOST("button_export_file.x") || GETPOST("button_export_file")) {
	$action = 'export_file';
}

$search_account_category = GETPOSTINT('search_account_category');

$search_accountancy_code = GETPOST("search_accountancy_code", 'alpha');
$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}

$search_accountancy_aux_code = GETPOST("search_accountancy_aux_code", 'alpha');
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
$search_ledger_code = GETPOST('search_ledger_code', 'array');
$search_lettering_code = GETPOST('search_lettering_code', 'alpha');
$search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$optioncss = GETPOST('optioncss', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") {
	$sortorder = "ASC";
}
if ($sortfield == "") {
	$sortfield = "t.piece_num,t.rowid";
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new BookKeeping($db);
$hookmanager->initHooks(array('bookkeepingexport'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

if (!in_array($action, array('export_file', 'delmouv', 'delmouvconfirm')) && !GETPOSTISSET('begin') && !GETPOSTISSET('formfilteraction') && GETPOSTINT('page') == '' && !GETPOSTINT('noreset') && $user->hasRight('accounting', 'mouvements', 'export')) {
	if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('restore_lastsearch_values') && !GETPOST('search_accountancy_code_start')) {
		$query = "SELECT date_start, date_end from ".MAIN_DB_PREFIX."accounting_fiscalyear ";
		$query .= " where date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."' limit 1";
		$res = $db->query($query);

		if ($db->num_rows($res) > 0) {
			$fiscalYear = $db->fetch_object($res);
			$search_date_start = strtotime($fiscalYear->date_start);
			$search_date_end = strtotime($fiscalYear->date_end);
		} else {
			$month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
			$year_start = dol_print_date(dol_now(), '%Y');
			if (dol_print_date(dol_now(), '%m') < $month_start) {
				$year_start--; // If current month is lower that starting fiscal month, we start last year
			}
			$year_end = $year_start + 1;
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
				$year_end--;
			}
			$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
			$search_date_end = dol_get_last_day($year_end, $month_end);
		}
	}
}


$arrayfields = array(
	't.piece_num' => array('label' => $langs->trans("TransactionNumShort"), 'checked' => 1),
	't.code_journal' => array('label' => $langs->trans("Codejournal"), 'checked' => 1),
	't.doc_date' => array('label' => $langs->trans("Docdate"), 'checked' => 1),
	't.doc_ref' => array('label' => $langs->trans("Piece"), 'checked' => 1),
	't.numero_compte' => array('label' => $langs->trans("AccountAccountingShort"), 'checked' => 1),
	't.subledger_account' => array('label' => $langs->trans("SubledgerAccount"), 'checked' => 1),
	't.label_operation' => array('label' => $langs->trans("Label"), 'checked' => 1),
	't.debit' => array('label' => $langs->trans("AccountingDebit"), 'checked' => 1),
	't.credit' => array('label' => $langs->trans("AccountingCredit"), 'checked' => 1),
	't.lettering_code' => array('label' => $langs->trans("LetteringCode"), 'checked' => 1),
	't.date_creation' => array('label' => $langs->trans("DateCreation"), 'checked' => 0),
	't.tms' => array('label' => $langs->trans("DateModification"), 'checked' => 0),
	't.date_export' => array('label' => $langs->trans("DateExport"), 'checked' => 1),
	't.date_validated' => array('label' => $langs->trans("DateValidationAndLock"), 'checked' => 1, 'enabled' => !getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")),
	't.import_key' => array('label' => $langs->trans("ImportId"), 'checked' => 0, 'position' => 1100),
);

if (!getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
	unset($arrayfields['t.lettering_code']);
}

$accountancyexport = new AccountancyExport($db);
$listofformat = $accountancyexport->getType();
$formatexportset = getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV');
if (empty($listofformat[$formatexportset])) {
	$formatexportset = 1;
}

$error = 0;

if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
	accessforbidden();
}


/*
 * Actions
 */

$param = '';

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha')) {
	$massaction = '';
}

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_mvt_num = '';
		$search_doc_type = '';
		$search_doc_ref = '';
		$search_doc_date = '';
		$search_account_category = '';
		$search_accountancy_code = '';
		$search_accountancy_code_start = '';
		$search_accountancy_code_end = '';
		$search_accountancy_aux_code = '';
		$search_accountancy_aux_code_start = '';
		$search_accountancy_aux_code_end = '';
		$search_mvt_label = '';
		$search_direction = '';
		$search_ledger_code = array();
		$search_date_startyear = '';
		$search_date_startmonth = '';
		$search_date_startday = '';
		$search_date_endyear = '';
		$search_date_endmonth = '';
		$search_date_endday = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_date_creation_startyear = '';
		$search_date_creation_startmonth = '';
		$search_date_creation_startday = '';
		$search_date_creation_endyear = '';
		$search_date_creation_endmonth = '';
		$search_date_creation_endday = '';
		$search_date_creation_start = '';
		$search_date_creation_end = '';
		$search_date_modification_startyear = '';
		$search_date_modification_startmonth = '';
		$search_date_modification_startday = '';
		$search_date_modification_endyear = '';
		$search_date_modification_endmonth = '';
		$search_date_modification_endday = '';
		$search_date_modification_start = '';
		$search_date_modification_end = '';
		$search_date_export_startyear = '';
		$search_date_export_startmonth = '';
		$search_date_export_startday = '';
		$search_date_export_endyear = '';
		$search_date_export_endmonth = '';
		$search_date_export_endday = '';
		$search_date_export_start = '';
		$search_date_export_end = '';
		$search_date_validation_startyear = '';
		$search_date_validation_startmonth = '';
		$search_date_validation_startday = '';
		$search_date_validation_endyear = '';
		$search_date_validation_endmonth = '';
		$search_date_validation_endday = '';
		$search_date_validation_start = '';
		$search_date_validation_end = '';
		$search_debit = '';
		$search_credit = '';
		$search_lettering_code = '';
		$search_not_reconciled = '';
		$search_import_key = '';
		$toselect = array();
	}

	// Must be after the remove filter action, before the export.
	$filter = array();
	if (!empty($search_date_start)) {
		$filter['t.doc_date>='] = $search_date_start;
		$tmp = dol_getdate($search_date_start);
		$param .= '&search_date_startmonth='.((int) $tmp['mon']).'&search_date_startday='.((int) $tmp['mday']).'&search_date_startyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_end)) {
		$filter['t.doc_date<='] = $search_date_end;
		$tmp = dol_getdate($search_date_end);
		$param .= '&search_date_endmonth='.((int) $tmp['mon']).'&search_date_endday='.((int) $tmp['mday']).'&search_date_endyear='.((int) $tmp['year']);
	}
	if (!empty($search_doc_date)) {
		$filter['t.doc_date'] = $search_doc_date;
		$tmp = dol_getdate($search_doc_date);
		$param .= '&doc_datemonth='.((int) $tmp['mon']).'&doc_dateday='.((int) $tmp['mday']).'&doc_dateyear='.((int) $tmp['year']);
	}
	if (!empty($search_doc_type)) {
		$filter['t.doc_type'] = $search_doc_type;
		$param .= '&search_doc_type='.urlencode($search_doc_type);
	}
	if (!empty($search_doc_ref)) {
		$filter['t.doc_ref'] = $search_doc_ref;
		$param .= '&search_doc_ref='.urlencode($search_doc_ref);
	}
	if ($search_account_category != '-1' && !empty($search_account_category)) {
		require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
		$accountingcategory = new AccountancyCategory($db);

		$listofaccountsforgroup = $accountingcategory->getCptsCat(0, 'fk_accounting_category = '.((int) $search_account_category));
		$listofaccountsforgroup2 = array();
		if (is_array($listofaccountsforgroup)) {
			foreach ($listofaccountsforgroup as $tmpval) {
				$listofaccountsforgroup2[] = "'".$db->escape($tmpval['id'])."'";
			}
		}
		$filter['t.search_accounting_code_in'] = implode(',', $listofaccountsforgroup2);
		$param .= '&search_account_category='.urlencode((string) ($search_account_category));
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
		foreach ($search_ledger_code as $code) {
			$param .= '&search_ledger_code[]='.urlencode($code);
		}
	}
	if (!empty($search_mvt_num)) {
		$filter['t.piece_num'] = $search_mvt_num;
		$param .= '&search_mvt_num='.urlencode((string) ($search_mvt_num));
	}
	if (!empty($search_date_creation_start)) {
		$filter['t.date_creation>='] = $search_date_creation_start;
		$tmp = dol_getdate($search_date_creation_start);
		$param .= '&search_date_creation_startmonth='.((int) $tmp['mon']).'&search_date_creation_startday='.((int) $tmp['mday']).'&search_date_creation_startyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_creation_end)) {
		$filter['t.date_creation<='] = $search_date_creation_end;
		$tmp = dol_getdate($search_date_creation_end);
		$param .= '&search_date_creation_endmonth='.((int) $tmp['mon']).'&search_date_creation_endday='.((int) $tmp['mday']).'&search_date_creation_endyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_modification_start)) {
		$filter['t.tms>='] = $search_date_modification_start;
		$tmp = dol_getdate($search_date_modification_start);
		$param .= '&search_date_modification_startmonth='.((int) $tmp['mon']).'&search_date_modification_startday='.((int) $tmp['mday']).'&search_date_modification_startyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_modification_end)) {
		$filter['t.tms<='] = $search_date_modification_end;
		$tmp = dol_getdate($search_date_modification_end);
		$param .= '&search_date_modification_endmonth='.((int) $tmp['mon']).'&search_date_modification_endday='.((int) $tmp['mday']).'&search_date_modification_endyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_export_start)) {
		$filter['t.date_export>='] = $search_date_export_start;
		$tmp = dol_getdate($search_date_export_start);
		$param .= '&search_date_export_startmonth='.((int) $tmp['mon']).'&search_date_export_startday='.((int) $tmp['mday']).'&search_date_export_startyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_export_end)) {
		$filter['t.date_export<='] = $search_date_export_end;
		$tmp = dol_getdate($search_date_export_end);
		$param .= '&search_date_export_endmonth='.((int) $tmp['mon']).'&search_date_export_endday='.((int) $tmp['mday']).'&search_date_export_endyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_validation_start)) {
		$filter['t.date_validated>='] = $search_date_validation_start;
		$tmp = dol_getdate($search_date_validation_start);
		$param .= '&search_date_validation_startmonth='.((int) $tmp['mon']).'&search_date_validation_startday='.((int) $tmp['mday']).'&search_date_validation_startyear='.((int) $tmp['year']);
	}
	if (!empty($search_date_validation_end)) {
		$filter['t.date_validated<='] = $search_date_validation_end;
		$tmp = dol_getdate($search_date_validation_end);
		$param .= '&search_date_validation_endmonth='.((int) $tmp['mon']).'&search_date_validation_endday='.((int) $tmp['mday']).'&search_date_validation_endyear='.((int) $tmp['year']);
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
	if (!empty($search_import_key)) {
		$filter['t.import_key'] = $search_import_key;
		$param .= '&search_import_key='.urlencode($search_import_key);
	}

	if ($action == 'setreexport') {
		$setreexport = GETPOSTINT('value');
		if (!dolibarr_set_const($db, "ACCOUNTING_REEXPORT", $setreexport, 'yesno', 0, '', $conf->entity)) {
			$error++;
		}

		if (!$error) {
			if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {
				setEventMessages($langs->trans("ExportOfPiecesAlreadyExportedIsDisable"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("ExportOfPiecesAlreadyExportedIsEnable"), null, 'warnings');
			}
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}

	// Mass actions
	$objectclass = 'Bookkeeping';
	$objectlabel = 'Bookkeeping';
	$permissiontoread = $user->hasRight('societe', 'lire');
	$permissiontodelete = $user->hasRight('societe', 'supprimer');
	$permissiontoadd = $user->hasRight('societe', 'creer');
	$uploaddir = $conf->societe->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
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
$sql .= " t.date_lettering,";
$sql .= " t.montant as amount,";
$sql .= " t.sens,";
$sql .= " t.fk_user_author,";
$sql .= " t.import_key,";
$sql .= " t.code_journal,";
$sql .= " t.journal_label,";
$sql .= " t.piece_num,";
$sql .= " t.date_creation,";
$sql .= " t.date_lim_reglement,";
$sql .= " t.tms as date_modification,";
$sql .= " t.date_export,";
$sql .= " t.date_validated as date_validation,";
$sql .= " t.import_key";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' as t';
// Manage filter
$sqlwhere = array();
if (count($filter) > 0) {
	foreach ($filter as $key => $value) {
		if ($key == 't.doc_date') {
			$sqlwhere[] = $db->sanitize($key)." = '".$db->idate($value)."'";
		} elseif ($key == 't.doc_date>=') {
			$sqlwhere[] = "t.doc_date >= '".$db->idate($value)."'";
		} elseif ($key == 't.doc_date<=') {
			$sqlwhere[] = "t.doc_date <= '".$db->idate($value)."'";
		} elseif ($key == 't.doc_date>') {
			$sqlwhere[] = "t.doc_date > '".$db->idate($value)."'";
		} elseif ($key == 't.doc_date<') {
			$sqlwhere[] = "t.doc_date < '".$db->idate($value)."'";
		} elseif ($key == 't.numero_compte>=') {
			$sqlwhere[] = "t.numero_compte >= '".$db->escape($value)."'";
		} elseif ($key == 't.numero_compte<=') {
			$sqlwhere[] = "t.numero_compte <= '".$db->escape($value)."'";
		} elseif ($key == 't.subledger_account>=') {
			$sqlwhere[] = "t.subledger_account >= '".$db->escape($value)."'";
		} elseif ($key == 't.subledger_account<=') {
			$sqlwhere[] = "t.subledger_account <= '".$db->escape($value)."'";
		} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
			$sqlwhere[] = $db->sanitize($key).'='.((int) $value);
		} elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
			$sqlwhere[] = $db->sanitize($key)." LIKE '".$db->escape($db->escapeforlike($value))."%'";
		} elseif ($key == 't.subledger_account') {
			$sqlwhere[] = natural_search($key, $value, 0, 1);
		} elseif ($key == 't.tms>=') {
			$sqlwhere[] = "t.tms >= '".$db->idate($value)."'";
		} elseif ($key == 't.tms<=') {
			$sqlwhere[] = "t.tms <= '".$db->idate($value)."'";
		} elseif ($key == 't.date_creation>=') {
			$sqlwhere[] = "t.date_creation >= '".$db->idate($value)."'";
		} elseif ($key == 't.date_creation<=') {
			$sqlwhere[] = "t.date_creation <= '".$db->idate($value)."'";
		} elseif ($key == 't.date_export>=') {
			$sqlwhere[] = "t.date_export >= '".$db->idate($value)."'";
		} elseif ($key == 't.date_export<=') {
			$sqlwhere[] = "t.date_export <= '".$db->idate($value)."'";
		} elseif ($key == 't.date_validated>=') {
			$sqlwhere[] = "t.date_validated >= '".$db->idate($value)."'";
		} elseif ($key == 't.date_validated<=') {
			$sqlwhere[] = "t.date_validated <= '".$db->idate($value)."'";
		} elseif ($key == 't.credit' || $key == 't.debit') {
			$sqlwhere[] = natural_search($key, $value, 1, 1);
		} elseif ($key == 't.reconciled_option') {
			$sqlwhere[] = 't.lettering_code IS NULL';
		} elseif ($key == 't.code_journal' && !empty($value)) {
			if (is_array($value)) {
				$sqlwhere[] = natural_search("t.code_journal", implode(',', $value), 3, 1);
			} else {
				$sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
			}
		} elseif ($key == 't.search_accounting_code_in' && !empty($value)) {
			$sqlwhere[] = 't.numero_compte IN ('.$db->sanitize($value, 1).')';
		} else {
			$sqlwhere[] = natural_search($key, $value, 0, 1);
		}
	}
}
$sql .= ' WHERE t.entity IN ('.getEntity('accountancy').')';
if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {	// Reexport not enabled (default mode)
	$sql .= " AND t.date_export IS NULL";
}
if (count($sqlwhere) > 0) {
	$sql .= ' AND '.implode(' AND ', $sqlwhere);
}
//print $sql;


// Export into a file with format defined into setup (FEC, CSV, ...)
// Must be after definition of $sql
if ($action == 'export_fileconfirm' && $user->hasRight('accounting', 'mouvements', 'export')) {
	// Export files then exit
	$accountancyexport = new AccountancyExport($db);

	$error = 0;
	$nbtotalofrecords = 0;

	// Open transaction to read lines to export, export them and update field date_export or date_validated
	$db->begin();

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

	$db->free($resql);

	//$sqlforexport = $sql;
	//$sqlforexport .= $db->order($sortfield, $sortorder);


	// TODO Call the fetchAll for a $limit and $offset
	// Replace the fetchAll to get all ->line followed by call to ->export(). fetchAll() currently consumes too much memory on large export.
	// Replace this with the query($sqlforexport) on a limited block and loop on each line to export them.
	$limit = 0;
	$offset = 0;
	$result = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter, 'AND', (getDolGlobalString('ACCOUNTING_REEXPORT') ? 1 : 0));

	if ($result < 0) {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		$formatexport = GETPOSTINT('formatexport');
		$notexportlettering = GETPOST('notexportlettering', 'alpha');


		if (!empty($notexportlettering)) {
			if (is_array($object->lines)) {
				foreach ($object->lines as $k => $movement) {
					unset($object->lines[$k]->lettering_code);
					unset($object->lines[$k]->date_lettering);
				}
			}
		}

		$notifiedexportdate = GETPOST('notifiedexportdate', 'alpha');
		$notifiedvalidationdate = GETPOST('notifiedvalidationdate', 'alpha');
		$withAttachment = !empty(trim(GETPOST('notifiedexportfull', 'alphanohtml'))) ? 1 : 0;

		// Output data on screen or download
		//$result = $accountancyexport->export($object->lines, $formatexport, $withAttachment);
		$result = $accountancyexport->export($object->lines, $formatexport, $withAttachment, 1, 1, 1);

		if ($result < 0) {
			$error++;
		} else {
			if (!empty($notifiedexportdate) || !empty($notifiedvalidationdate)) {
				if (is_array($object->lines)) {
					dol_syslog("/accountancy/bookkeeping/list.php Function export_file set movements as exported", LOG_DEBUG);

					// TODO Merge update for each line into one global using rowid IN (list of movement ids)
					foreach ($object->lines as $movement) {
						// Update the line to set date_export and/or date_validated (if not already set !)
						$now = dol_now();

						$setfields = '';
						if (!empty($notifiedexportdate) && empty($movement->date_export)) {
							$setfields .= ($setfields ? "," : "")." date_export = '".$db->idate($now)."'";
						}
						if (!empty($notifiedvalidationdate) && empty($movement->date_validation)) {
							$setfields .= ($setfields ? "," : "")." date_validated = '".$db->idate($now)."'";
						}

						if ($setfields) {
							$sql = " UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping";
							$sql .= " SET ".$setfields;		// $setfields is already a sanitized SQL string
							$sql .= " WHERE rowid = ".((int) $movement->id);

							$result = $db->query($sql);
							if (!$result) {
								$error++;
								break;
							}
						}
					}

					if ($error) {
						$accountancyexport->errors[] = $langs->trans('NotAllExportedMovementsCouldBeRecordedAsExportedOrValidated');
					}
				}
			}
		}
	}

	if (!$error) {
		$db->commit();

		$downloadFilePath = $accountancyexport->generatedfiledata['downloadFilePath'];
		$downloadFileMimeType = $accountancyexport->generatedfiledata['downloadFileMimeType'];
		$downloadFileFullName = $accountancyexport->generatedfiledata['downloadFileFullName'];

		// No error, we can output the file
		top_httphead($downloadFileMimeType);

		header('Content-Description: File Transfer');
		// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)
		$attachment = 1;
		if ($attachment) {
			header('Content-Disposition: attachment; filename="'.$downloadFileFullName.'"');
		} else {
			header('Content-Disposition: inline; filename="'.$downloadFileFullName.'"');
		}
		// Ajout directives pour resoudre bug IE
		header('Cache-Control: Public, must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . dol_filesize($downloadFilePath));

		readfileLowMemory($downloadFilePath);
	} else {
		$db->rollback();

		setEventMessages('', $accountancyexport->errors, 'errors');
		header('Location: '.$_SERVER['PHP_SELF']);
	}
	exit(0); // download or show errors
}


/*
 * View
 */

$formother = new FormOther($db);
$formfile = new FormFile($db);

$title_page = $langs->trans("Operations").' - '.$langs->trans("ExportAccountancy");

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

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
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

$arrayofselected = is_array($toselect) ? $toselect : array();

// Output page
// --------------------------------------------------------------------
$help_url = 'EN:Module_Double_Entry_Accounting#Exports|FR:Module_Comptabilit&eacute;_en_Partie_Double#Exports';

llxHeader('', $title_page, $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-consultation page-export');

$formconfirm = '';

if ($action == 'export_file') {
	$form_question = array();

	$form_question['formatexport'] = array(
		'name' => 'formatexport',
		'type' => 'select',
		'label' => $langs->trans('Modelcsv'),		// TODO  Use Selectmodelcsv and show a select combo
		'values' => $listofformat,
		'default' => $formatexportset,
		'morecss' => 'minwidth200 maxwidth200'
	);

	$form_question['separator0'] = array('name' => 'separator0', 'type' => 'separator');

	if (getDolGlobalInt("ACCOUNTING_ENABLE_LETTERING")) {
		// If 1, we check by default.
		$checked = getDolGlobalString('ACCOUNTING_DEFAULT_NOT_EXPORT_LETTERING') ? 'true' : 'false';
		$form_question['notexportlettering'] = array(
			'name' => 'notexportlettering',
			'type' => 'checkbox',
			'label' => $langs->trans('NotExportLettering'),
			'value' => $checked,
		);

		$form_question['separator1'] = array('name' => 'separator1', 'type' => 'separator');
	}

	// If 1 or not set, we check by default.
	$checked = (!isset($conf->global->ACCOUNTING_DEFAULT_NOT_NOTIFIED_EXPORT_DATE) || getDolGlobalString('ACCOUNTING_DEFAULT_NOT_NOTIFIED_EXPORT_DATE'));
	$form_question['notifiedexportdate'] = array(
		'name' => 'notifiedexportdate',
		'type' => 'checkbox',
		'label' => $langs->trans('NotifiedExportDate'),
		'value' => (getDolGlobalString('ACCOUNTING_DEFAULT_NOT_NOTIFIED_EXPORT_DATE') ? 'false' : 'true'),
	);

	$form_question['separator2'] = array('name' => 'separator2', 'type' => 'separator');

	if (!getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")) {
		// If 0 or not set, we NOT check by default.
		$checked = (isset($conf->global->ACCOUNTING_DEFAULT_NOT_NOTIFIED_VALIDATION_DATE) || getDolGlobalString('ACCOUNTING_DEFAULT_NOT_NOTIFIED_VALIDATION_DATE'));
		$form_question['notifiedvalidationdate'] = array(
			'name' => 'notifiedvalidationdate',
			'type' => 'checkbox',
			'label' => $langs->trans('NotifiedValidationDate', $langs->transnoentitiesnoconv("MenuAccountancyClosure")),
			'value' => $checked,
		);

		$form_question['separator3'] = array('name' => 'separator3', 'type' => 'separator');
	}

	// add documents in an archive for some accountancy export format
	if (getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV') == AccountancyExport::$EXPORT_TYPE_QUADRATUS
		|| getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV') == AccountancyExport::$EXPORT_TYPE_FEC
		|| getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV') == AccountancyExport::$EXPORT_TYPE_FEC2
	) {
		$form_question['notifiedexportfull'] = array(
			'name' => 'notifiedexportfull',
			'type' => 'checkbox',
			'label' => $langs->trans('NotifiedExportFull'),
			'value' => 'false',
		);
	}

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?'.$param, $langs->trans("ExportFilteredList").'...', $langs->trans('ConfirmExportFile'), 'export_fileconfirm', $form_question, '', 1, 390, 700);
}

// Print form confirm
print $formconfirm;

//$param='';	param started before
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}

// List of mass actions available
$arrayofmassactions = array();
$massactionbutton = $form->selectMassAction($massaction, $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.urlencode($optioncss).'">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

if (count($filter)) {
	$buttonLabel = $langs->trans("ExportFilteredList");
} else {
	$buttonLabel = $langs->trans("ExportList");
}

$parameters = array('param' => $param);
$reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;
if (empty($reshook)) {
	// Button re-export
	if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {
		$newcardbutton .= '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?action=setreexport&token='.newToken().'&value=1'.($param ? '&'.$param : '').'&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder).'">'.img_picto($langs->trans("ClickToShowAlreadyExportedLines"), 'switch_off', 'class="small size15x valignmiddle"');
		$newcardbutton .= '<span class="valignmiddle marginrightonly paddingleft">'.$langs->trans("ClickToShowAlreadyExportedLines").'</span>';
		$newcardbutton .= '</a>';
	} else {
		$newcardbutton .= '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?action=setreexport&token='.newToken().'&value=0'.($param ? '&'.$param : '').'&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder).'">'.img_picto($langs->trans("DocsAlreadyExportedAreIncluded"), 'switch_on', 'class="warning size15x valignmiddle"');
		$newcardbutton .= '<span class="valignmiddle marginrightonly paddingleft">'.$langs->trans("DocsAlreadyExportedAreIncluded").'</span>';
		$newcardbutton .= '</a>';
	}

	if ($user->hasRight('accounting', 'mouvements', 'export')) {
		$newcardbutton .= dolGetButtonTitle($buttonLabel, $langs->trans("ExportFilteredList"), 'fa fa-file-export paddingleft', $_SERVER["PHP_SELF"].'?action=export_file&token='.newToken().($param ? '&'.$param : '').'&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder), $user->hasRight('accounting', 'mouvements', 'export'));
	}
}

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Not display message when all the list of docs are included
if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {
	print info_admin($langs->trans("WarningDataDisappearsWhenDataIsExported"), 0, 0, 0, 'hideonsmartphone info');
}

//$topicmail = "Information";
//$modelmail = "accountingbookkeeping";
//$objecttmp = new BookKeeping($db);
//$trackid = 'bk'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
if ($massactionbutton && $contextpage != 'poslist') {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

$moreforfilter = '';

if (!empty($search_account_category)) {
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('AccountingCategory').': ';
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
	$moreforfilter .= '</div>';
	$moreforfilter .= '</div>';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if ($moreforfilter) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

// Filters lines
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
// Movement number
if (!empty($arrayfields['t.piece_num']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="'.dol_escape_htmltag($search_mvt_num).'"></td>';
}
// Code journal
if (!empty($arrayfields['t.code_journal']['checked'])) {
	print '<td class="liste_titre center">';
	print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'small maxwidth75');
	print '</td>';
}
// Date document
if (!empty($arrayfields['t.doc_date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Ref document
if (!empty($arrayfields['t.doc_ref']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="'.dol_escape_htmltag($search_doc_ref).'"></td>';
}
// Accountancy account
if (!empty($arrayfields['t.numero_compte']['checked'])) {
	print '<td class="liste_titre">';
	print '<div class="nowrap">';
	print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth150', 'account');
	print '</div>';
	print '<div class="nowrap">';
	print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth150', 'account');
	print '</div>';
	print '</td>';
}
// Subledger account
if (!empty($arrayfields['t.subledger_account']['checked'])) {
	print '<td class="liste_titre">';
	// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
	// use setup of "keypress to select thirdparty" and this hangs browser on large databases.
	if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
		print '<div class="nowrap">';
		//print $langs->trans('From').' ';
		print $formaccounting->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', $langs->trans('From'), 'maxwidth150', 'subledgeraccount');
		print '</div>';
		print '<div class="nowrap">';
		print $formaccounting->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', $langs->trans('to'), 'maxwidth150', 'subledgeraccount');
		print '</div>';
	} else {
		print '<input type="text" class="maxwidth75" name="search_accountancy_aux_code" value="'.dol_escape_htmltag($search_accountancy_aux_code).'">';
	}
	print '</td>';
}
// Label operation
if (!empty($arrayfields['t.label_operation']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" size="7" class="flat" name="search_mvt_label" value="'.dol_escape_htmltag($search_mvt_label).'"/>';
	print '</td>';
}
// Debit
if (!empty($arrayfields['t.debit']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat" name="search_debit" size="4" value="'.dol_escape_htmltag($search_debit).'">';
	print '</td>';
}
// Credit
if (!empty($arrayfields['t.credit']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat" name="search_credit" size="4" value="'.dol_escape_htmltag($search_credit).'">';
	print '</td>';
}
// Lettering code
if (!empty($arrayfields['t.lettering_code']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input type="text" size="3" class="flat" name="search_lettering_code" value="'.dol_escape_htmltag($search_lettering_code).'"/>';
	print '<br><span class="nowrap"><input type="checkbox" id="search_not_reconciled" name="search_not_reconciled" value="notreconciled"'.($search_not_reconciled == 'notreconciled' ? ' checked' : '').'><label for="search_not_reconciled">'.$langs->trans("NotReconciled").'</label></span>';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Date creation
if (!empty($arrayfields['t.date_creation']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_creation_start, 'search_date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_creation_end, 'search_date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['t.tms']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modification_start, 'search_date_modification_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modification_end, 'search_date_modification_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '</td>';
}
// Date export
if (!empty($arrayfields['t.date_export']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Date validation
if (!empty($arrayfields['t.date_validated']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_validation_start, 'search_date_validation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_validation_end, 'search_date_validation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['t.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
}
if (!empty($arrayfields['t.piece_num']['checked'])) {
	print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.code_journal']['checked'])) {
	print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.doc_date']['checked'])) {
	print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.doc_ref']['checked'])) {
	print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.numero_compte']['checked'])) {
	print_liste_field_titre($arrayfields['t.numero_compte']['label'], $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.subledger_account']['checked'])) {
	print_liste_field_titre($arrayfields['t.subledger_account']['label'], $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.label_operation']['checked'])) {
	print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.debit']['checked'])) {
	print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.credit']['checked'])) {
	print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.lettering_code']['checked'])) {
	print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['t.date_creation']['checked'])) {
	print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER['PHP_SELF'], "t.date_creation", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.tms']['checked'])) {
	print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], "t.tms", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.date_export']['checked'])) {
	print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.date_validated']['checked'])) {
	print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER["PHP_SELF"], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";


$line = new BookKeepingLine($db);

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
$totalarray['nbfield'] = 0;
$totalarray['val'] = array();
$totalarray['val']['totaldebit'] = 0;
$totalarray['val']['totalcredit'] = 0;

while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

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
	$line->date_validation = $db->jdate($obj->date_validation);

	print '<tr class="oddeven">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowraponall center">';
		if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($line->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$line->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$line->id.'"'.($selected ? ' checked="checked"' : '').' />';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Piece number
	if (!empty($arrayfields['t.piece_num']['checked'])) {
		print '<td>';
		$object->id = $line->id;
		$object->piece_num = $line->piece_num;
		print $object->getNomUrl(1, '', 0, '', 1);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Journal code
	if (!empty($arrayfields['t.code_journal']['checked'])) {
		$accountingjournal = new AccountingJournal($db);
		$result = $accountingjournal->fetch('', $line->code_journal);
		$journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
		print '<td class="center tdoverflowmax150">'.$journaltoshow.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Document date
	if (!empty($arrayfields['t.doc_date']['checked'])) {
		print '<td class="center">'.dol_print_date($line->doc_date, 'day').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Document ref
	$modulepart = '';
	if (!empty($arrayfields['t.doc_ref']['checked'])) {
		$documentlink = '';
		$objectstatic = null;
		if ($line->doc_type == 'customer_invoice') {
			$langs->loadLangs(array('bills'));

			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$objectstatic = new Facture($db);
			$objectstatic->fetch($line->fk_doc);

			if ($objectstatic->id > 0) {
				$filename = dol_sanitizeFileName($line->doc_ref);
				$filedir = $conf->invoice->dir_output.'/'.dol_sanitizeFileName($line->doc_ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$objectstatic->id;
				$documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			}
		} elseif ($line->doc_type == 'supplier_invoice') {
			$langs->loadLangs(array('bills'));

			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
			$objectstatic = new FactureFournisseur($db);
			$objectstatic->fetch($line->fk_doc);

			if ($objectstatic->id > 0) {
				$modulepart = 'invoice_supplier';
				$filename = dol_sanitizeFileName($line->doc_ref);
				$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $objectstatic->element).dol_sanitizeFileName($line->doc_ref);
				$subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $objectstatic->element).dol_sanitizeFileName($line->doc_ref);
				$documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
			}
		} elseif ($line->doc_type == 'expense_report') {
			$langs->loadLangs(array('trips'));

			require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
			$objectstatic = new ExpenseReport($db);
			$objectstatic->fetch($line->fk_doc);

			if ($objectstatic->id > 0) {
				$filename = dol_sanitizeFileName($line->doc_ref);
				$filedir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($line->doc_ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$objectstatic->id;
				$documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			}
		} elseif ($line->doc_type == 'bank') {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$objectstatic = new AccountLine($db);
			$objectstatic->fetch($line->fk_doc);
		} else {
			// Other type
		}

		$labeltoshow = '';
		$labeltoshowalt = '';
		if (($objectstatic instanceof CommonObject)  && in_array($line->doc_type, array('customer_invoice', 'supplier_invoice', 'expense_report'))) {
			if ($objectstatic->id > 0) {
				$labeltoshow .= $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
				$labeltoshow .= $documentlink;
				$labeltoshowalt .= $objectstatic->ref;
			} else {
				$labeltoshow = '<span class="opacitymedium">'.$langs->trans("Deleted").'</span>';
			}
		} elseif ($line->doc_type == 'bank') {
			$labeltoshow .= $objectstatic->getNomUrl(1);
			$labeltoshowalt .= $objectstatic->ref;
			$bank_ref = strstr($line->doc_ref, '-');
			$labeltoshow .= " " . $bank_ref;
			$labeltoshowalt .= " " . $bank_ref;
		} else {
			$labeltoshow .= $line->doc_ref;
			$labeltoshowalt .= $line->doc_ref;
		}

		print '<td class="nowraponall tdoverflowmax200" title="'.dol_escape_htmltag($labeltoshowalt).'">';
		print $labeltoshow;
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Account number
	if (!empty($arrayfields['t.numero_compte']['checked'])) {
		print '<td>'.length_accountg($line->numero_compte).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Subledger account
	if (!empty($arrayfields['t.subledger_account']['checked'])) {
		print '<td>'.length_accounta($line->subledger_account).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Label operation
	if (!empty($arrayfields['t.label_operation']['checked'])) {
		print '<td class="small tdoverflowmax200" title="'.dol_escape_htmltag($line->label_operation).'">'.dol_escape_htmltag($line->label_operation).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Amount debit
	if (!empty($arrayfields['t.debit']['checked'])) {
		print '<td class="right nowraponall amount">'.($line->debit != 0 ? price($line->debit) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
		}
		$totalarray['val']['totaldebit'] += $line->debit;
	}

	// Amount credit
	if (!empty($arrayfields['t.credit']['checked'])) {
		print '<td class="right nowraponall amount">'.($line->credit != 0 ? price($line->credit) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
		}
		$totalarray['val']['totalcredit'] += $line->credit;
	}

	// Lettering code
	if (!empty($arrayfields['t.lettering_code']['checked'])) {
		print '<td class="center">'.$line->lettering_code.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Creation operation date
	if (!empty($arrayfields['t.date_creation']['checked'])) {
		print '<td class="center">'.dol_print_date($line->date_creation, 'dayhour', 'tzuserrel').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Modification operation date
	if (!empty($arrayfields['t.tms']['checked'])) {
		print '<td class="center">'.dol_print_date($line->date_modification, 'dayhour', 'tzuserrel').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Exported operation date
	if (!empty($arrayfields['t.date_export']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($line->date_export, 'dayhour', 'tzuserrel').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Validated operation date
	if (!empty($arrayfields['t.date_validated']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($line->date_validation, 'dayhour', 'tzuserrel').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['t.import_key']['checked'])) {
		print '<td class="tdoverflowmax100">'.$obj->import_key."</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowraponall center">';
		if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($line->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$line->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$line->id.'"'.($selected ? ' checked="checked"' : '').' />';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}


	print "</tr>\n";

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

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';

print '</form>';

// End of page
llxFooter();

$db->close();
