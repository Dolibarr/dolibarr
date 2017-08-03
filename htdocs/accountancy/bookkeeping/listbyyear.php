<?php
/* Copyright (C) 2013-2016 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2013-2016 Florian Henry		<florian.henry@open-concept.pro>
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
 * \file 		htdocs/accountancy/bookkeeping/listbyyear.php
 * \ingroup 	Advanced accountancy
 * \brief 		Book keeping by year
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

// Langs
$langs->load("accountancy");

$page = GETPOST("page");
$sortorder = GETPOST("sortorder");
$sortfield = GETPOST("sortfield");
$limit = GETPOST('limit','int')?GETPOST('limit', 'int'):$conf->liste_limit;
$search_date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));
$search_doc_type = GETPOST('search_doc_type', 'alpha');
$search_doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));
$search_doc_ref = GETPOST('search_doc_ref', 'alpha');
$search_numero_compte = GETPOST('search_numero_compte', 'alpha');
$search_numero_compte_start = GETPOST('search_numero_compte_start', 'alpha');
if ($search_numero_compte_start == - 1) {
	$search_numero_compte_start = '';
}
$search_numero_compte_end = GETPOST('search_numero_compte_end', 'alpha');
if ($search_numero_compte_end == - 1) {
	$search_numero_compte_end = '';
}
$search_subledger_account = GETPOST('search_subledger_account', 'alpha');
$search_subledger_account_start = GETPOST('search_subledger_account_start', 'alpha');
if ($search_subledger_account_start == - 1) {
	$search_subledger_account_start = '';
}
$search_subledger_account_end = GETPOST('search_subledger_account_end', 'alpha');
if ($search_subledger_account_end == - 1) {
	$search_subledger_account_end = '';
}
$search_label_operation = GETPOST('search_label_operation', 'alpha');
$search_sens = GETPOST('search_sens', 'alpha');
$search_code_journal = GETPOST('search_code_journal', 'alpha');

$object = new BookKeeping($db);
$form = new Form($db);
$formaccounting = new FormAccounting($db);

// Filter
if (empty($search_date_start)) {
	$search_date_start = dol_mktime(0, 0, 0, 1, 1, dol_print_date(dol_now(), '%Y'));
	$search_date_end = dol_mktime(0, 0, 0, 12, 31, dol_print_date(dol_now(), '%Y'));
}
if ($sortorder == "")
	$sortorder = "ASC";
if ($sortfield == "")
	$sortfield = "t.rowid";

$offset = $limit * $page;


/*
 * Actions
 */

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_doc_type = "";
	$search_doc_date = "";
	$search_doc_ref = "";
	$search_numero_compte = "";
	$search_subledger_account = "";
	$search_label_operation = "";
	$search_sens = "";
	$search_code_journal = "";
}

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
if (! empty($search_numero_compte)) {
	$filter['t.numero_compte'] = $search_numero_compte;
	$options .= '&amp;search_numero_compte=' . $search_numero_compte;
}
if (! empty($search_numero_compte_start)) {
	$filter['t.numero_compte>='] = $search_numero_compte_start;
	$options .= '&amp;search_numero_compte_start=' . $search_numero_compte_start;
}
if (! empty($search_numero_compte_end)) {
	$filter['t.numero_compte<='] = $search_numero_compte_end;
	$options .= '&amp;search_numero_compte_end=' . $search_numero_compte_end;
}
if (! empty($search_subledger_account)) {
	$filter['t.subledger_account'] = $search_subledger_account;
	$options .= '&amp;search_subledger_account=' . $search_subledger_account;
}
if (! empty($search_subledger_account_start)) {
	$filter['t.subledger_account>='] = $search_subledger_account_start;
	$options .= '&amp;search_subledger_account_start=' . $search_subledger_account_start;
}
if (! empty($search_subledger_account_end)) {
	$filter['t.subledger_account<='] = $search_subledger_account_end;
	$options .= '&amp;search_subledger_account_end=' . $search_subledger_account_end;
}
if (! empty($search_label_operation)) {
	$filter['t.label_operation'] = $search_label_operation;
	$options .= '&amp;search_label_operation=' . $search_label_operation;
}
if (! empty($search_sens)) {
	$filter['t.sens'] = $search_sens;
	$options .= '&amp;search_sens=' . $search_sens;
}
if (! empty($search_code_journal)) {
	$filter['t.code_journal'] = $search_code_journal;
	$options .= '&amp;search_code_journal=' . $search_code_journal;
}


/*
 * Actions
 */

llxHeader('', $langs->trans("Bookkeeping"));

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetchAll($sortorder, $sortfield, 0, 0);
	if ($nbtotalofrecords < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$result = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
}

print_barre_liste($langs->trans("Bookkeeping") . ' ' . dol_print_date($search_date_start) . '-' . dol_print_date($search_date_end), $page, $_SERVER['PHP_SELF'], $options, $sortfield, $sortorder, '', $result, $nbtotalofrecords, 'title_accountancy');

print '<form method="GET" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
print '<div class="liste_titre">';
print $langs->trans('DateStart') . ': ';
print $form->select_date($search_date_start, 'date_start');
print $langs->trans('DateEnd') . ': ';
print $form->select_date($search_date_end, 'date_end');
print '</div>';
print '<div class="liste_titre">';
print $langs->trans('From') . ' ' . $langs->trans('AccountAccounting') . ': ';
print $formaccounting->select_account($search_numero_compte_start, 'search_numero_compte_start', 1, array (), 1, 1, '');
print $langs->trans('To') . ' ' . $langs->trans('AccountAccounting') . ': ';
print $formaccounting->select_account($search_numero_compte_end, 'search_numero_compte_end', 1, array (), 1, 1, '');
print '</div>';
print '<div class="liste_titre">';
print $langs->trans('From') . ' ' . $langs->trans('ThirdPartyAccount') . ': ';
print $formaccounting->select_auxaccount($search_subledger_account_start, 'search_subledger_account_start', 1);
print $langs->trans('To') . ' ' . $langs->trans('ThirdPartyAccount') . ': ';
print $formaccounting->select_auxaccount($search_subledger_account_end, 'search_subledger_account_end', 1);
print '</div>';
print "<table class=\"noborder\" width=\"100%\">";

print '<tr class="liste_titre">';
print_liste_field_titre("NumPiece", $_SERVER['PHP_SELF'], "t.piece_num", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("Doctype", $_SERVER['PHP_SELF'], "t.doc_type", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("Date", $_SERVER['PHP_SELF'], "t.doc_date", "", $options, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Docref", $_SERVER['PHP_SELF'], "t.doc_ref", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("AccountAccounting", $_SERVER['PHP_SELF'], "t.numero_compte", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("ThirdPartyAccount", $_SERVER['PHP_SELF'], "t.subledger_account", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("Label", $_SERVER['PHP_SELF'], "t.label_operation", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("Debit", $_SERVER['PHP_SELF'], "t.debit", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre("Credit", $_SERVER['PHP_SELF'], "t.credit", "", $options, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Amount", $_SERVER['PHP_SELF'], "t.montant", "", $options, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Sens", $_SERVER['PHP_SELF'], "t.sens", "", $options, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Codejournal", $_SERVER['PHP_SELF'], "t.code_journal", "", $options, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", $options, "", 'width="60" align="center"', $sortfield, $sortorder);
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre">';
print '<input type="text" size=4 class="flat" name="search_piece_num" value="' . $search_piece_num . '"/>';
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" size=7 class="flat" name="search_doc_type" value="' . $search_doc_type . '"/>';
print '</td>';

print '<td class="liste_titre">';
print $form->select_date($search_doc_date, 'doc_date', 0, 0, 1);
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" size=6 class="flat" name="search_doc_ref" value="' . $search_doc_ref . '"/>';
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" size=6 class="flat" name="search_numero_compte" value="' . $search_numero_compte . '"/>';
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" size=6 class="flat" name="search_subledger_account" value="' . $search_subledger_account . '"/>';
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" size=6 class="flat" name="search_label_operation" value="' . $search_label_operation . '"/>';
print '</td>';

print '<td class="liste_titre">';
print '</td>';

print '<td class="liste_titre">';
print '</td>';

print '<td class="liste_titre">';
print '</td>';

print '<td class="liste_titre" align="center">';
print '<input type="text" size=2 class="flat" name="search_sens" value="' . $search_sens . '"/>';
print '</td>';

print '<td class="liste_titre" align="center">';
print '<input type="text" size=3 class="flat" name="search_code_journal" value="' . $search_code_journal . '"/>';
print '</td>';

print '<td align="right" colspan="2" class="liste_titre">';
print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '&nbsp;';
print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" name="button_removefilter" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</td>';

print "</tr>\n";

foreach ( $object->lines as $line ) {

	print '<tr class="oddeven">';
	print '<td>' . $line->piece_num . '</td>' . "\n";
	print '<td>' . $line->doc_type . '</td>' . "\n";
	print '<td align="center">' . dol_print_date($line->doc_date) . '</td>';
	print '<td>' . $line->doc_ref . '</td>';
	print '<td>' . length_accountg($line->numero_compte) . '</td>';
	print '<td>' . length_accounta($line->subledger_account) . '</td>';
	print '<td>' . $line->label_operation . '</td>';
	print '<td align="right">' . price($line->debit) . '</td>';
	print '<td align="right">' . price($line->credit) . '</td>';
	print '<td align="right">' . price($line->montant) . '</td>';
	print '<td align="center">' . $line->sens . '</td>';
	print '<td align="right">' . $line->code_journal . '</td>';
	print '<td align="center"><a href="./card.php?action=update&amp;piece_num=' . $line->piece_num . '">' . img_edit() . '</a></td>';
	print "</tr>\n";
}
print "</table>";
print '</form>';

llxFooter();
$db->close();
