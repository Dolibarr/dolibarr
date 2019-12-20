<?php
/* Copyright (C) 2016       Neil Orley          <neil.orley@oeris.fr>
 * Copyright (C) 2013-2016  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2018  Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 * \file 		htdocs/accountancy/bookkeeping/listbyaccount.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		List operation of book keeping ordered by account number
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy"));

$action = GETPOST('action', 'alpha');
$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));
$search_doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));

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
$search_direction = GETPOST('search_direction', 'alpha');
$search_ledger_code = GETPOST('search_ledger_code', 'alpha');
$search_debit = GETPOST('search_debit', 'alpha');
$search_credit = GETPOST('search_credit', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) ? $conf->liste_limit : $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") $sortorder = "ASC";
if ($sortfield == "") $sortfield = "t.doc_date,t.rowid";

if (empty($search_date_start) && empty($search_date_end) && GETPOSTISSET('search_date_startday') && GETPOSTISSET('search_date_startmonth') && GETPOSTISSET('search_date_starthour')) {
	$sql = 	"SELECT date_start, date_end from ".MAIN_DB_PREFIX."accounting_fiscalyear ";
	$sql.= " where date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."'";
	$sql.= $db->plimit(1);
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

$object = new BookKeeping($db);

/*
 * Action
 */
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_doc_date = '';
	$search_accountancy_code = '';
	$search_accountancy_code_start = '';
	$search_accountancy_code_end = '';
	$search_label_account = '';
	$search_doc_ref = '';
	$search_label_operation = '';
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
	$search_debit = '';
	$search_credit = '';
}

// Must be after the remove filter action, before the export.
$param = '';
$filter = array();

if (!empty($search_date_start)) {
	$filter['t.doc_date>='] = $search_date_start;
	$param .= '&search_date_startmonth='.GETPOST('search_date_startmonth', 'int').'&search_date_startday='.GETPOST('search_date_startday', 'int').'&search_date_startyear='.GETPOST('search_date_startyear', 'int');
}
if (!empty($search_date_end)) {
	$filter['t.doc_date<='] = $search_date_end;
	$param .= '&search_date_endmonth='.GETPOST('search_date_endmonth', 'int').'&search_date_endday='.GETPOST('search_date_endday', 'int').'&search_date_endyear='.GETPOST('search_date_endyear', 'int');
}
if (!empty($search_doc_date)) {
	$filter['t.doc_date'] = $search_doc_date;
	$param .= '&doc_datemonth='.GETPOST('doc_datemonth', 'int').'&doc_dateday='.GETPOST('doc_dateday', 'int').'&doc_dateyear='.GETPOST('doc_dateyear', 'int');
}
if (!empty($search_accountancy_code_start)) {
	$filter['t.numero_compte>='] = $search_accountancy_code_start;
	$param .= '&search_accountancy_code_start='.urlencode($search_accountancy_code_start);
}
if (!empty($search_accountancy_code_end)) {
	$filter['t.numero_compte<='] = $search_accountancy_code_end;
	$param .= '&search_accountancy_code_end='.urlencode($search_accountancy_code_end);
}
if (!empty($search_label_account)) {
	$filter['t.label_compte'] = $search_label_account;
	$param .= '&search_label_compte='.urlencode($search_label_account);
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


if ($action == 'delmouvconfirm') {
	$mvt_num = GETPOST('mvt_num', 'int');

	if (!empty($mvt_num)) {
		$result = $object->deleteMvtNum($mvt_num);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		Header("Location: listbyaccount.php");
		exit();
	}
}


/*
 * View
 */

$formaccounting = new FormAccounting($db);
$formother = new FormOther($db);
$form = new Form($db);

$title_page = $langs->trans("Bookkeeping").' '.strtolower($langs->trans("By")).' '.strtolower($langs->trans("AccountAccounting"));

llxHeader('', $title_page);


// List
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter);
	if ($nbtotalofrecords < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter);

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

	if (empty($delyear)) {
		$delyear = dol_print_date(dol_now(), '%Y');
	}
	$year_array = $formaccounting->selectyear_accountancy_bookkepping($delyear, 'delyear', 0, 'array');

	$form_question['delyear'] = array(
			'name' => 'delyear',
			'type' => 'select',
			'label' => $langs->trans('DelYear'),
			'values' => $year_array,
			'default' => $delyear
	);

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delbookkeepingyearconfirm', $form_question, 0, 1, 250);
	print $formconfirm;
}


print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';


$newcardbutton.= dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft', DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?'.$param);
$newcardbutton.= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', './card.php?action=create');

if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $result, $nbtotalofrecords, 'title_accountancy', 0, $viewflat.$newcardbutton, '', $limit);

// Reverse sort order
if (preg_match('/^asc/i', $sortorder)) $sortorder = "asc";
else $sortorder = "desc";

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
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
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center">';
print $langs->trans('From').': ';
print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1);
print '<br>';
print $langs->trans('to').': ';
print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1);
print '</td>';
print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_doc_ref" value="'.dol_escape_htmltag($search_doc_ref).'"/></td>';
print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_label_operation" value="'.dol_escape_htmltag($search_label_operation).'"/></td>';
print '<td class="liste_titre right"><input type="text" class="flat" name="search_debit" size="4" value="'.dol_escape_htmltag($search_debit).'"></td>';
print '<td class="liste_titre right"><input type="text" class="flat" name="search_credit" size="4" value="'.dol_escape_htmltag($search_credit).'"></td>';
print '<td class="liste_titre center"><input type="text" name="search_ledger_code" size="3" value="'.dol_escape_htmltag($search_ledger_code).'"></td>';
print '<td class="liste_titre right" colspan="2">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre("AccountAccountingShort", $_SERVER['PHP_SELF']);
print_liste_field_titre("TransactionNumShort", $_SERVER['PHP_SELF'], "t.piece_num", "", $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre("Docdate", $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre("Piece", $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("Label");
print_liste_field_titre("Debit", $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre("Credit", $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre("Codejournal", $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre('', $_SERVER["PHP_SELF"], "", $param, "", 'width="60"', $sortfield, $sortorder, 'center ');
print "</tr>\n";


$total_debit = 0;
$total_credit = 0;
$sous_total_debit = 0;
$sous_total_credit = 0;
$displayed_account_number = null; // Start with undefined to be able to distinguish with empty

$i = 0;
while ($i < min($num, $limit))
{
	$line = $object->lines[$i];

	$total_debit += $line->debit;
	$total_credit += $line->credit;

	$accountg = length_accountg($line->numero_compte);
	//if (empty($accountg)) $accountg = '-';

	// Is it a break ?
	if ($accountg != $displayed_account_number || ! isset($displayed_account_number)) {
		// Affiche un Sous-Total par compte comptable
		if (isset($displayed_account_number)) {
			print '<tr class="liste_total"><td class="right" colspan="5">'.$langs->trans("SubTotal").':</td><td class="nowrap right">'.price($sous_total_debit).'</td><td class="nowrap right">'.price($sous_total_credit).'</td>';
			print "<td>&nbsp;</td>\n";
			print "<td>&nbsp;</td>\n";
			print '</tr>';
		}

		// Show the break account
		$colspan = 9;
		print "<tr>";
		print '<td colspan="'.$colspan.'" style="font-weight:bold; border-bottom: 1pt solid black;">';
		if ($line->numero_compte != "" && $line->numero_compte != '-1') print length_accountg($line->numero_compte) . ' : ' . $object->get_compte_desc($line->numero_compte);
		else print '<span class="error">'.$langs->trans("Unknown").'</span>';
		print '</td>';
		print '</tr>';

		$displayed_account_number = $accountg;
		//if (empty($displayed_account_number)) $displayed_account_number='-';
		$sous_total_debit = 0;
		$sous_total_credit = 0;
	}

	print '<tr class="oddeven">';
	print '<td>&nbsp;</td>';
	print '<td class="right"><a href="./card.php?piece_num='.$line->piece_num.'">'.$line->piece_num.'</a></td>';
	print '<td class="center">'.dol_print_date($line->doc_date, 'day').'</td>';

	// TODO Add a link according to doc_type and fk_doc
	print '<td class="nowrap">';
	//if ($line->doc_type == 'supplier_invoice')
	//if ($line->doc_type == 'customer_invoice')
	print $line->doc_ref;
	print '</td>';

	// Affiche un lien vers la facture client/fournisseur
	$doc_ref = preg_replace('/\(.*\)/', '', $line->doc_ref);
	print strlen(length_accounta($line->subledger_account)) == 0 ? '<td>' . $line->label_operation . '</td>' : '<td>' . $line->label_operation . '<br><span style="font-size:0.8em">(' . length_accounta($line->subledger_account) . ')</span></td>';


	print '<td class="nowrap right">' . ($line->debit ? price($line->debit) :''). '</td>';
	print '<td class="nowrap right">' . ($line->credit ? price($line->credit) : '') . '</td>';

	$accountingjournal = new AccountingJournal($db);
	$result = $accountingjournal->fetch('', $line->code_journal);
	$journaltoshow = (($result > 0)?$accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
	print '<td class="center">' . $journaltoshow . '</td>';

	print '<td class="center">';
	print '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?piece_num=' . $line->piece_num . '">' . img_edit() . '</a>&nbsp;';
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delmouv&mvt_num=' . $line->piece_num . $param . '&page=' . $page . '">' . img_delete() . '</a>';
	print '</td>';
	print "</tr>\n";

	// Comptabilise le sous-total
	$sous_total_debit += $line->debit;
	$sous_total_credit += $line->credit;

	$i++;
}

// Show sub-total of last shown account
print '<tr class="liste_total">';
print '<td class="right" colspan="5">'.$langs->trans("SubTotal").':</td><td class="nowrap right">'.price($sous_total_debit).'</td><td class="nowrap right">'.price($sous_total_credit).'</td>';
print '<td class="nowraponall center">';
print price($sous_total_debit - $sous_total_credit);
print '</td>';
print '<td></td>';
print '</tr>';


// Show total
print '<tr class="liste_total">';
print '<td class="right" colspan="5">'.$langs->trans("Total").':</td>';
print '<td class="nowraponall right">';
print price($total_debit);
print '</td>';
print '<td class="nowraponall right">';
print price($total_credit);
print '</td>';
print '<td></td>';
print '<td></td>';
print '</tr>';

print "</table>";
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
