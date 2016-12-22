<?php
/* Copyright (C) 2016 		Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2016 		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2016 		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *  \file 		htdocs/accountancy/bookkeeping/balance.php
 *  \ingroup 	Advanced accountancy
 *  \brief 		Balance of book keeping
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
$search_date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));

$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}

if (GETPOST("button_export_csv_x") || GETPOST("button_export_csv")) {
	$action = 'export_csv';
}

$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;

$offset = $limit * $page;

$object = new BookKeeping($db);

$formventilation = new FormVentilation($db);
$formother = new FormOther($db);
$form = new Form($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_accountancy_code_start = '';
	$search_accountancy_code_end = '';
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
	$sortfield = "t.numero_compte";

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
if (! empty($search_accountancy_code_start)) {
	$filter['t.numero_compte>='] = $search_accountancy_code_start;
	$options .= '&amp;search_accountancy_code_start=' . $search_accountancy_code_start;
}
if (! empty($search_accountancy_code_end)) {
	$filter['t.numero_compte<='] = $search_accountancy_code_end;
	$options .= '&amp;search_accountancy_code_end=' . $search_accountancy_code_end;
}


/*
 * Action
 */

if ($action == 'export_csv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
	$journal = 'bookkepping';

	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	$result = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	foreach ( $object->lines as $line ) {

		if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) {
			$sep = ";";
		}
		print length_accountg($line->numero_compte) . $sep;
		print $line->debit . $sep;
		print $line->credit . $sep;
		print $line->debit . $sep;
		print $line->credit - $line->debit . $sep;
		print "\n";
	}
}

else {

	$title_page = $langs->trans("AccountBalance") . ' ' . dol_print_date($search_date_start) . '-' . dol_print_date($search_date_end);

	llxHeader('', $title_page);

	/*
	 * List
	 */
	$nbtotalofrecords = 0;
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$nbtotalofrecords = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter);
		if ($nbtotalofrecords < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, $offset, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $options, $sortfield, $sortorder, '', $result);

	print '<form method="GET" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<div class="tabsAction">' . "\n";
	print '<div class="inline-block divButAction"><input type="submit" name="button_export_csv" class="butAction" value="' . $langs->trans("Export") . '" /></div>';
	print '</div>';

	$moreforfilter='';

	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('DateStart') . ': ';
	$moreforfilter.=$form->select_date($search_date_start, 'date_start', 0, 0, 1, '', 1, 0, 1);
	$moreforfilter.=$langs->trans('DateEnd') . ': ';
	$moreforfilter.=$form->select_date($search_date_end, 'date_end', 0, 0, 1, '', 1, 0, 1);
	$moreforfilter.='</div>';

	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	    print $hookmanager->resPrint;
	    print '</div>';
	}

	print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("AccountAccountingShort"), $_SERVER['PHP_SELF'], "t.numero_compte", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Labelcompte"), $_SERVER['PHP_SELF'], "t.label_compte", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Debit"), $_SERVER['PHP_SELF'], "t.debit", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Credit"), $_SERVER['PHP_SELF'], "t.credit", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Solde"), $_SERVER["PHP_SELF"], "", $options, "", 'width="60" align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Action"), $_SERVER["PHP_SELF"], "", $options, "", 'width="60" align="center"', $sortfield, $sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td colspan="2">';
	print $langs->trans('From');
	print $formventilation->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array (), 1, 1, '');
	print '<br>';
	print $langs->trans('to');
	print $formventilation->select_account($search_accountancy_code_end, 'search_accountancy_code_end', 1, array (), 1, 1, '');
	print '</td>';

	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';

	print '<td align="right" class="liste_titre">';
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
		$link = '';
		$total_debit += $line->debit;
		$total_credit += $line->credit;
		$description = $object->get_compte_desc($line->numero_compte); // Search description of the account
		if(empty($description)){
			$link = '<a href="../admin/card.php?action=create&compte=' . length_accountg($line->numero_compte) . '">' . img_edit_add() .'</a>';
		}
		print '<tr'. $bc[$var].'>';

		print '<td>' . length_accountg($line->numero_compte) . '</td>';
		print '<td>' . $description . '</td>';
		print '<td align="right">' .  number_format($line->debit, 2, ',', ' ') . '</td>';
        print '<td align="right">' . number_format($line->credit, 2, ',', ' ') . '</td>';
        print '<td align="right">' . number_format($line->credit - $line->debit, 2, ',', ' ') . '</td>';
		print '<td align="center">' . $link;
		print '</td>';
		print "</tr>\n";
	}

	print '<tr class="liste_total">';
	print '<td></td>';
	print '<td></td>';
	print '<td  align="right">';
	print price($total_debit);
	print '</td>';
	print '<td  align="right">';
	print price($total_credit);
	print '</td>';
	print '<td align="right">' . price($total_credit - $total_debit) . '</td>';
	print '<td align="right"></td>';
	print '</tr>';

	print "</table>";
	print '</form>';

	llxFooter();
}
$db->close();