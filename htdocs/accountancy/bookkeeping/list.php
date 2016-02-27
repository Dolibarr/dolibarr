<?php
/* Copyright (C) 2013-2016 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com> 
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
 *
 */

/**
 * \file 		htdocs/accountancy/bookkeeping/list.php
 * \ingroup 	Advanced accountancy
 * \brief 		List operation of book keeping
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

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

if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt")) {
	$action = 'delbookkeepingyear';
}
if (GETPOST("button_export_csv_x") || GETPOST("button_export_csv")) {
	$action = 'export_csv';
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

$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;

$offset = $limit * $page;

$object = new BookKeeping($db);

$formventilation = new FormVentilation($db);
$formother = new FormOther($db);
$form = new Form($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
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

if (empty($search_date_start)) {
	$search_date_start = dol_mktime(0, 0, 0, 1, 1, dol_print_date(dol_now(), '%Y'));
	$search_date_end = dol_mktime(0, 0, 0, 12, 31, dol_print_date(dol_now(), '%Y'));
}
if ($sortorder == "")
	$sortorder = "ASC";
if ($sortfield == "")
	$sortfield = "t.rowid";

$options = '';
$filter = array ();
if (! empty($search_date_start)) {
	$filter['t.doc_date>='] = $search_date_start;
	$options .= '&amp;date_startmonth=' . GETPOST('date_startmonth', 'int') . '&amp;date_startday=' . GETPOST('date_startday', 'int') . '&amp;date_startyear=' . GETPOST('date_startyear', 'int');
}
if (! empty($search_date_end)) {
	$filter['t.doc_date<='] = $search_date_end;
	$options .= '&amp;date_endmonth=' . GETPOST('date_endmonth', 'int') . '&amp;date_endday=' . GETPOST('date_endday', 'int') . '&amp;date_endyear=' . GETPOST('date_endyear', 'int');
}
if (! empty($search_doc_type)) {
	$filter['t.doc_type'] = $search_doc_type;
	$options .= '&amp;search_doc_type=' . $search_doc_type;
}
if (! empty($search_doc_date)) {
	$filter['t.doc_date'] = $search_doc_date;
	$options .= '&amp;doc_datemonth=' . GETPOST('doc_datemonth', 'int') . '&amp;doc_dateday=' . GETPOST('doc_dateday', 'int') . '&amp;doc_dateyear=' . GETPOST('doc_dateyear', 'int');
}
if (! empty($search_doc_ref)) {
	$filter['t.doc_ref'] = $search_doc_ref;
	$options .= '&amp;search_doc_ref=' . $search_doc_ref;
}
if (! empty($search_accountancy_code)) {
	$filter['t.numero_compte'] = $search_accountancy_code;
	$options .= '&amp;search_accountancy_code=' . $search_accountancy_code;
}
if (! empty($search_accountancy_code_start)) {
	$filter['t.numero_compte>='] = $search_accountancy_code_start;
	$options .= '&amp;search_accountancy_code_start=' . $search_accountancy_code_start;
}
if (! empty($search_accountancy_code_end)) {
	$filter['t.numero_compte<='] = $search_accountancy_code_end;
	$options .= '&amp;search_accountancy_code_end=' . $search_accountancy_code_end;
}
if (! empty($search_accountancy_aux_code)) {
	$filter['t.code_tiers'] = $search_accountancy_aux_code;
	$options .= '&amp;search_accountancy_aux_code=' . $search_accountancy_aux_code;
}
if (! empty($search_accountancy_aux_code_start)) {
	$filter['t.code_tiers>='] = $search_accountancy_aux_code_start;
	$options .= '&amp;search_accountancy_aux_code_start=' . $search_accountancy_aux_code_start;
}
if (! empty($search_accountancy_aux_code_end)) {
	$filter['t.code_tiers<='] = $search_accountancy_aux_code_end;
	$options .= '&amp;search_accountancy_aux_code_end=' . $search_accountancy_aux_code_end;
}
if (! empty($search_mvt_label)) {
	$filter['t.label_compte'] = $search_mvt_label;
	$options .= '&amp;search_mvt_label=' . $search_mvt_label;
}
if (! empty($search_direction)) {
	$filter['t.sens'] = $search_direction;
	$options .= '&amp;search_direction=' . $search_direction;
}
if (! empty($search_ledger_code)) {
	$filter['t.code_journal'] = $search_ledger_code;
	$options .= '&amp;search_ledger_code=' . $search_ledger_code;
}
if (! empty($search_mvt_num)) {
	$filter['t.piece_num'] = $search_mvt_num;
	$options .= '&amp;search_mvt_num=' . $search_mvt_num;
}

/*
 * Action
 */
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
} elseif ($action == 'delbookkeepingyearconfirm') {
	
	$delyear = GETPOST('delyear', 'int');
	
	if (! empty($delyear)) {
		$result = $object->deleteByYear($delyear);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		Header("Location: list.php");
		exit();
	}
} elseif ($action == 'delmouvconfirm') {
	
	$mvt_num = GETPOST('mvt_num', 'int');
	
	if (! empty($mvt_num)) {
		$result = $object->deleteMvtNum($mvt_num);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		// if (!empty($options)) {
		// Header("Location: list.php?".urldecode($options));
		// } else {
		Header("Location: list.php");
		// }
		exit();
	}
} elseif ($action == 'export_csv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
	$journal = 'bookkepping';
	
	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';
	
	$result = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	
	foreach ( $object->lines as $line ) {
		
		if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) {
			$sep = ";";
			// Model Cegid Expert Export
			$date = dol_print_date($line->doc_date, '%d%m%Y');
			print $date . $sep;
			print $line->code_journal . $sep;
			print length_accountg($line->numero_compte) . $sep;
			print ' ' . $sep;
			print $line->sens . $sep;
			print price($line->montant) . $sep;
			print dol_trunc($line->label_compte, 32) . $sep;
			print $line->doc_ref . $sep;
			
			/*print $line->piece_num . $sep;
			 print length_accounta($line->code_tiers) . $sep;
			 print  . $sep;
			 print price($line->debit) . $sep;
			 print price($line->credit) . $sep;*/
			
			print "\n";
		} elseif ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 1) {
			
			// Std export
			$date = dol_print_date($line->doc_date, $conf->global->ACCOUNTING_EXPORT_DATE);
			print $date . $sep;
			print $line->doc_ref . $sep;
			print length_accountg($line->numero_compte) . $sep;
			print length_accounta($line->code_tiers) . $sep;
			print price($line->debit) . $sep;
			print price($line->credit) . $sep;
			
			/*print $line->piece_num . $sep;
			 print $line->label_compte . $sep;
			 print price($line->montant) . $sep;
			 print $line->sens . $sep;*/
			print $line->code_journal . $sep;
			print "\n";
		} elseif ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 3) {
			
			// Coala export
			$date = dol_print_date($line->doc_date, '%d/%m/%Y');
			print $date . $sep;
			print $line->code_journal . $sep;
			print length_accountg($line->numero_compte) . $sep;
			print $line->piece_num . $sep;
			print $line->doc_ref . $sep;
			print price($line->debit) . $sep;
			print price($line->credit) . $sep;
			print 'E' . $sep;
			print length_accountg($line->code_tiers) . $sep;
			print "\n";
		}
	}
} 

/*
 * View
 */
else {
	
	$title_page = $langs->trans("Bookkeeping") . ' ' . dol_print_date($search_date_start) . '-' . dol_print_date($search_date_end);
	
	llxHeader('', $title_page);
	
	/*
	 * List
	 */
	
	$nbtotalofrecords = 0;
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$nbtotalofrecords = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter);
		if ($nbtotalofrecords < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	
	$result = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	
	if ($action == 'delmouv') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?mvt_num=' . GETPOST('mvt_num'), $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delmouvconfirm', '', 0, 1);
		print $formconfirm;
	}
	if ($action == 'delbookkeepingyear') {
		
		$form_question = array ();
		$delyear = GETPOST('delyear');
		
		if (empty($delyear)) {
			$delyear = dol_print_date(dol_now(), '%Y');
		}
		$year_array = $formventilation->selectyear_accountancy_bookkepping($delyear, 'delyear', 0, 'array');
		
		$form_question['delyear'] = array (
				'name' => 'delyear',
				'type' => 'select',
				'label' => $langs->trans('DelYear'),
				'values' => $year_array,
				'default' => $delyear 
		);
		
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delbookkeepingyearconfirm', $form_question, 0, 1);
		print $formconfirm;
	}
	
	print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $options, $sortfield, $sortorder, '', $result, $nbtotalofrecords);
	
	/*print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	 print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	 print '<input type="hidden" name="action" value="delbookkeeping">';
	 
	 print $formventilation->select_bookkeeping_importkey('importkey', GETPOST('importkey'));
	 
	 print '<div class="inline-block divButAction"><input type="submit" class="butAction" value="' . $langs->trans("DelBookKeeping") . '" /></div>';
	 
	 print '</form>';*/
	
	print '<form method="GET" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<div class="tabsAction">' . "\n";
	print '<div class="inline-block divButAction"><input type="submit" name="button_delmvt" class="butAction" value="' . $langs->trans("DelBookKeeping") . '" /></div>';
	print '<div class="inline-block divButAction"><a class="butAction" href="./card.php?action=create">' . $langs->trans("NewAccountingMvt") . '</a></div>';
	// print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=export_csv">' . $langs->trans("Export") . '</a>';
	print '<div class="inline-block divButAction"><input type="submit" name="button_export_csv" class="butAction" value="' . $langs->trans("Export") . '" /></div>';
	
	print '</div>';
	
	/*print '<div class="liste_titre">';
	 print $langs->trans('DateStart') . ': ';
	 print $form->select_date($search_date_start, 'date_start', 0, 0, 1);
	 print $langs->trans('DateEnd') . ': ';
	 print $form->select_date($search_date_end, 'date_end', 0, 0, 1);
	 print '</div>';
	 print '<div class="liste_titre">';
	 print $langs->trans('From') . ' ' . $langs->trans('AccountAccounting') . ': ';
	 print $formventilation->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array (), 1, 1, '');
	 print $langs->trans('To') . ' ' . $langs->trans('AccountAccounting') . ': ';
	 print $formventilation->select_account($search_accountancy_code_end, 'search_accountancy_code_end', 1, array (), 1, 1, '');
	 print '</div>';
	 print '<div class="liste_titre">';
	 print $langs->trans('From') . ' ' . $langs->trans('ThirdPartyAccount') . ': ';
	 print $formventilation->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', 1);
	 print $langs->trans('To') . ' ' . $langs->trans('ThirdPartyAccount') . ': ';
	 print $formventilation->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', 1);
	 print '</div>';*/
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("NumPiece"), $_SERVER['PHP_SELF'], "t.piece_num", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Docdate"), $_SERVER['PHP_SELF'], "t.doc_date", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Docref"), $_SERVER['PHP_SELF'], "t.doc_ref", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Numerocompte"), $_SERVER['PHP_SELF'], "t.numero_compte", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Code_tiers"), $_SERVER['PHP_SELF'], "t.code_tiers", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Labelcompte"), $_SERVER['PHP_SELF'], "bk_label_compte", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Debit"), $_SERVER['PHP_SELF'], "t.debit", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Credit"), $_SERVER['PHP_SELF'], "t.credit", "", $options, 'align="right"', $sortfield, $sortorder);
	// print_liste_field_titre($langs->trans("Amount"), $_SERVER['PHP_SELF'], "t.montant", "", $options, 'align="center"', $sortfield, $sortorder);
	// print_liste_field_titre($langs->trans("Sens"), $_SERVER['PHP_SELF'], "t.sens", "", $options, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Codejournal"), $_SERVER['PHP_SELF'], "t.code_journal", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Action"), $_SERVER["PHP_SELF"], "", $options, "", 'width="60" align="center"', $sortfield, $sortorder);
	print "</tr>\n";
	
	print '<tr class="liste_titre">';
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="GET">';
	print '<td><input type="text" name="search_mvt_num" size="6" value="' . $search_mvt_num . '"></td>';
	print '<td class="liste_titre">';
	print $langs->trans('From') . ': ';
	print $form->select_date($search_date_start, 'date_start', 0, 0, 1);
	print '<br>';
	print $langs->trans('to') . ': ';
	print $form->select_date($search_date_end, 'date_end', 0, 0, 1);
	// print $form->select_date($search_doc_date, 'doc_date', 0, 0, 1);
	print '</td>';
	print '<td><input type="text" name="search_doc_ref" size="8" value="' . $search_doc_ref . '"></td>';
	print '<td>';
	// print $langs->trans('From') . ' ' . $langs->trans('AccountAccounting') . ': ';
	print $langs->trans('From');
	print $formventilation->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array (), 1, 1, '');
	// print $langs->trans('To') . ' ' . $langs->trans('AccountAccounting') . ': ';
	print '<br>';
	print $langs->trans('to');
	print $formventilation->select_account($search_accountancy_code_end, 'search_accountancy_code_end', 1, array (), 1, 1, '');
	print '</td>';
	// print '<td><input type="text" name="search_accountancy_code" size="8" value="' . $search_accountancy_code . '"></td>';
	print '<td>';
	// print $langs->trans('From') . ' ' . $langs->trans('ThirdPartyAccount') . ': ';
	print $langs->trans('From');
	print $formventilation->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', 1);
	// print $langs->trans('To') . ' ' . $langs->trans('ThirdPartyAccount') . ': ';
	print '<br>';
	print $langs->trans('to');
	print $formventilation->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', 1);
	print '</td>';
	// print '<td><input type="text" name="search_accountancy_aux_code" size="8" value="' . $search_accountancy_aux_code . '"></td>';
	
	print '<td class="liste_titre">';
	print '<input type="text" size=6 class="flat" name="search_mvt_label" value="' . $search_mvt_label . '"/>';
	print '</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	// print '<td>&nbsp;</td>';
	// print '<td>&nbsp;</td>';
	print '<td  align="right"><input type="text" name="search_ledger_code" size="3" value="' . $search_ledger_code . '"></td>';
	print '<td align="right" colspan="2" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp;';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" name="button_removefilter" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';
	
	print '</tr>';
	
	$var = True;
	
	$total_debit = 0;
	$total_credit = 0;
	
	foreach ( $object->lines as $line ) {
		$var = ! $var;
		
		$total_debit += $line->debit;
		$total_credit += $line->credit;
		
		print "<tr $bc[$var]>";
		
		print '<td><a href="./card.php?piece_num=' . $line->piece_num . '">' . $line->piece_num . '</a></td>';
		print '<td align="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
		print '<td>' . $line->doc_ref . '</td>';
		print '<td>' . length_accountg($line->numero_compte) . '</td>';
		print '<td>' . length_accounta($line->code_tiers) . '</td>';
		print '<td>' . $line->label_compte . '</td>';
		print '<td align="right">' . price($line->debit) . '</td>';
		print '<td align="right">' . price($line->credit) . '</td>';
		// print '<td align="right">' . price($line->montant) . '</td>';
		// print '<td align="center">' . $line->sens . '</td>';
		print '<td align="right">' . $line->code_journal . '</td>';
		print '<td align="center">';
		print '<a href="./card.php?piece_num=' . $line->piece_num . '">' . img_edit() . '</a>&nbsp;';
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delmouv&mvt_num=' . $line->piece_num . $options . '&page=' . $page . '">' . img_delete() . '</a>';
		print '</td>';
		print "</tr>\n";
	}
	
	print '<tr class="liste_total">';
	print '<td colspan="6"></td>';
	print '<td  align="right">';
	print price($total_debit);
	print '</td>';
	print '<td  align="right">';
	print price($total_credit);
	print '</td>';
	print '<td colspan="2"></td>';
	print '</tr>';
	
	print "</table>";
	print '</form>';
	
	llxFooter();
}

$db->close();