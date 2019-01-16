<?php
/* Copyright (C) 2016       Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2016       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2018  Alexandre Spangaro      <aspangaro@zendsi.com>
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy"));

$page = GETPOST("page");
$sortorder = GETPOST("sortorder", 'alpha');
$sortfield = GETPOST("sortfield", 'alpha');
$action = GETPOST('action', 'aZ09');
if (GETPOST("exportcsv",'alpha')) $action = 'export_csv';

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1 || GETPOST('button_search','alpha') || GETPOST('button_removefilter','alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield="p.date_fin";
//if (! $sortorder) $sortorder="DESC";


$search_date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
$search_date_end = dol_mktime(23, 59, 59, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));

$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}

$object = new BookKeeping($db);

$formaccounting = new FormAccounting($db);
$formother = new FormOther($db);
$form = new Form($db);

if (empty($search_date_start) && ! GETPOSTISSET('formfilteraction'))
{
	$sql = 	"SELECT date_start, date_end from ".MAIN_DB_PREFIX."accounting_fiscalyear ";
	$sql.= " where date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."'";
	$sql.= $db->plimit(1);
	$res = $db->query($sql);
	if ($res->num_rows > 0) {
		$fiscalYear = $db->fetch_object($res);
		$search_date_start = strtotime($fiscalYear->date_start);
		$search_date_end = strtotime($fiscalYear->date_end);
	} else {
		$month_start= ($conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START):1);
		$year_start = dol_print_date(dol_now(), '%Y');
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
if ($sortorder == "")
	$sortorder = "ASC";
if ($sortfield == "")
	$sortfield = "t.numero_compte";


$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);

$filter = array ();
if (! empty($search_date_start)) {
	$filter['t.doc_date>='] = $search_date_start;
	$param .= '&amp;date_startmonth=' . GETPOST('date_startmonth', 'int') . '&amp;date_startday=' . GETPOST('date_startday', 'int') . '&amp;date_startyear=' . GETPOST('date_startyear', 'int');
}
if (! empty($search_date_end)) {
	$filter['t.doc_date<='] = $search_date_end;
	$param .= '&amp;date_endmonth=' . GETPOST('date_endmonth', 'int') . '&amp;date_endday=' . GETPOST('date_endday', 'int') . '&amp;date_endyear=' . GETPOST('date_endyear', 'int');
}
if (! empty($search_accountancy_code_start)) {
	$filter['t.numero_compte>='] = $search_accountancy_code_start;
	$param .= '&amp;search_accountancy_code_start=' . $search_accountancy_code_start;
}
if (! empty($search_accountancy_code_end)) {
	$filter['t.numero_compte<='] = $search_accountancy_code_end;
	$param .= '&amp;search_accountancy_code_end=' . $search_accountancy_code_end;
}

/*
 * Action
 */

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_accountancy_code_start = '';
	$search_accountancy_code_end = '';
	$search_date_start = '';
	$search_date_end = '';
	$filter = array();
}


/*
 * View
 */

if ($action == 'export_csv')
{
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	$filename = 'balance';
	$type_export = 'balance';
	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, 0, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	foreach ($object->lines as $line)
	{
		print length_accountg($line->numero_compte) . $sep;
		print $object->get_compte_desc($line->numero_compte) . $sep;
		print price($line->debit) . $sep;
		print price($line->credit) . $sep;
		print price($line->credit - $line->debit) . $sep;
		print "\n";
	}

	exit;
}


$title_page = $langs->trans("AccountBalance");

llxHeader('', $title_page);


if ($action != 'export_csv')
{
	// List
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$nbtotalofrecords = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter);
		if ($nbtotalofrecords < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, $offset, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	$button = '<input type="submit" name="exportcsv" class="butAction" value="' . $langs->trans("Export") . ' ('.$conf->global->ACCOUNTING_EXPORT_FORMAT.')" />';

	print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $button, $result, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	$moreforfilter = '';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateStart') . ': ';
	$moreforfilter .= $form->selectDate($search_date_start?$search_date_start:-1, 'date_start', 0, 0, 1, '', 1, 0);
	$moreforfilter .= $langs->trans('DateEnd') . ': ';
	$moreforfilter .= $form->selectDate($search_date_end?$search_date_end:-1, 'date_end', 0, 0, 1, '', 1, 0);
	$moreforfilter .= '</div>';

	if (! empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		print '</div>';
	}

	print '<table class="liste ' . ($moreforfilter ? "listwithfilterbefore" : "") . '">';

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre" colspan="5">';
	print $langs->trans('From');
	print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array(), 1, 1, '');
	print ' ';
	print $langs->trans('to');
	print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', 1, array(), 1, 1, '');
	print '</td>';
	print '<td align="right" class="liste_titre">';
	$searchpicto=$form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("AccountAccounting", $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Debit", $_SERVER['PHP_SELF'], "t.debit", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Credit", $_SERVER['PHP_SELF'], "t.credit", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Balance", $_SERVER["PHP_SELF"], "", $param, "", 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
	print "</tr>\n";

	$total_debit = 0;
	$total_credit = 0;
	$sous_total_debit = 0;
	$sous_total_credit = 0;
	$displayed_account = "";

	foreach ($object->lines as $line)
	{
		$link = '';
		$total_debit += $line->debit;
		$total_credit += $line->credit;
		$description = $object->get_compte_desc($line->numero_compte); // Search description of the account
		$root_account_description = $object->get_compte_racine($line->numero_compte);
		if (empty($description)) {
			$link = '<a href="../admin/card.php?action=create&accountingaccount=' . length_accountg($line->numero_compte) . '">' . img_edit_add() . '</a>';
		}
		print '<tr class="oddeven">';

		// Permet d'afficher le compte comptable
		if (empty($displayed_account) || $root_account_description != $displayed_account)
		{
			// Affiche un Sous-Total par compte comptable
			if ($displayed_account != "") {
				print '<tr class="liste_total"><td align="right" colspan="2">' . $langs->trans("SubTotal") . ':</td><td class="nowrap" align="right">' . price($sous_total_debit) . '</td><td class="nowrap" align="right">' . price($sous_total_credit) . '</td><td class="nowrap" align="right">' . price(price2num($sous_total_credit - $sous_total_debit)) . '</td>';
				print "<td>&nbsp;</td>\n";
				print '</tr>';
			}

			// Affiche le compte comptable en debut de ligne
			print "<tr>";
			print '<td colspan="6" style="font-weight:bold; border-bottom: 1pt solid black;">' . $line->numero_compte . ($root_account_description ? ' - ' . $root_account_description : '') . '</td>';
			print '</tr>';

			$displayed_account = $root_account_description;
			$sous_total_debit = 0;
			$sous_total_credit = 0;
		}

		// $object->get_compte_racine($line->numero_compte);

		print '<td>' . length_accountg($line->numero_compte) . '</td>';
		print '<td>' . $description . '</td>';
		print '<td align="right">' . price($line->debit) . '</td>';
		print '<td align="right">' . price($line->credit) . '</td>';
		print '<td align="right">' . price($line->credit - $line->debit) . '</td>';
		print '<td align="center">' . $link;
		print '</td>';
		print "</tr>\n";

		// Comptabilise le sous-total
		$sous_total_debit += $line->debit;
		$sous_total_credit += $line->credit;
	}

	print '<tr class="liste_total"><td align="right" colspan="2">' . $langs->trans("SubTotal") . ':</td><td class="nowrap" align="right">' . price($sous_total_debit) . '</td><td class="nowrap" align="right">' . price($sous_total_credit) . '</td><td class="nowrap" align="right">' . price(price2num($sous_total_credit - $sous_total_debit)) . '</td>';
	print "<td>&nbsp;</td>\n";
	print '</tr>';

	print '<tr class="liste_total"><td align="right" colspan="2">' . $langs->trans("AccountBalance") . ':</td><td class="nowrap" align="right">' . price($total_debit) . '</td><td class="nowrap" align="right">' . price($total_credit) . '</td><td class="nowrap" align="right">' . price(price2num($total_credit - $total_debit)) . '</td>';
	print "<td>&nbsp;</td>\n";
	print '</tr>';

	print "</table>";
	print '</form>';
}

// End of page
llxFooter();
$db->close();
