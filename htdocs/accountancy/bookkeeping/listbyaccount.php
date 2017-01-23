<?php
/*
 * Copyright (C) 2016 Neil Orley	<neil.orley@oeris.fr> largely based on the great work of :
 *  - Copyright (C) 2013-2016 Olivier Geffroy		<jeff@jeffinfo.com>
 *  - Copyright (C) 2013-2016 Florian Henry		<florian.henry@open-concept.pro>
 *  - Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 * \file 		htdocs/accountancy/bookkeeping/listbyaccount.php
 * \ingroup 	Advanced accountancy
 * \brief 		List operation of book keeping ordered by account number
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
$search_doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));



$search_accountancy_code = GETPOST("search_accountancy_code");

$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_label_account = GETPOST('search_label_account', 'alpha');

$search_mvt_label = GETPOST('search_mvt_label', 'alpha');
$search_direction = GETPOST('search_direction', 'alpha');
$search_ledger_code = GETPOST('search_ledger_code', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit') ? GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page','int');
if ($page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") $sortorder = "ASC";
if ($sortfield == "") $sortfield = "t.rowid";

if (empty($search_date_start)) $search_date_start = dol_mktime(0, 0, 0, 1, 1, dol_print_date(dol_now(), '%Y'));
if (empty($search_date_end)) $search_date_end = dol_mktime(0, 0, 0, 12, 31, dol_print_date(dol_now(), '%Y'));

$object = new BookKeeping($db);

$formventilation = new FormVentilation($db);
$formother = new FormOther($db);
$form = new Form($db);


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
if (! empty($search_doc_date)) {
	$filter['t.doc_date'] = $search_doc_date;
	$options .= '&amp;doc_datemonth=' . GETPOST('doc_datemonth', 'int') . '&amp;doc_dateday=' . GETPOST('doc_dateday', 'int') . '&amp;doc_dateyear=' . GETPOST('doc_dateyear', 'int');
}


if (!GETPOST("button_removefilter_x") && !GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
  if (! empty($search_accountancy_code_start)) {
  	$filter['t.numero_compte'] = $search_accountancy_code_start;
  	$options .= '&amp;search_accountancy_code_start=' . $search_accountancy_code_start;
  }
  if (! empty($search_label_account)) {
  	$filter['t.label_compte'] = $search_label_account;
  	$options .= '&amp;search_label_account=' . $search_label_account;
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
}


/*
 * Action
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
	$search_doc_date = '';
	$search_accountancy_code = '';
	$search_accountancy_code_start = '';
    $search_label_account = '';
	$search_mvt_label = '';
	$search_direction = '';
	$search_ledger_code = '';
}

if ($action == 'delmouvconfirm') {

	$mvt_num = GETPOST('mvt_num', 'int');

	if (! empty($mvt_num)) {
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

$title_page = $langs->trans("Bookkeeping") . ' ' . strtolower($langs->trans("By")) . ' ' . $langs->trans("AccountAccounting");

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
$nbtotalofrecords = $result;

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

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delbookkeepingyearconfirm', $form_question, 0, 1, 250);
	print $formconfirm;
}




print '<form method="GET" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';

$viewflat = ' <a href="./list.php">' . $langs->trans("ViewFlatList") . '</a>';

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $options, $sortfield, $sortorder, '', $result, $nbtotalofrecords,'title_accountancy',0,$viewflat,'',$limit);

// Reverse sort order
if ( preg_match('/^asc/i', $sortorder) )
  $sortorder = "asc";
else
  $sortorder = "desc";

print '<div class="tabsAction">' . "\n";
print '<div class="inline-block divButAction"><a class="butAction" href="./card.php?action=create">' . $langs->trans("NewAccountingMvt") . '</a></div>';
print '</div>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("AccountAccounting") . '</td>';
print_liste_field_titre($langs->trans("TransactionNumShort"), $_SERVER['PHP_SELF'], "t.piece_num", "", $options, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Docdate"), $_SERVER['PHP_SELF'], "t.doc_date", "", $options, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Docref"), $_SERVER['PHP_SELF'], "t.doc_ref", "", $options, "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("SuppliersInvoices") . ' / ' . $langs->trans("CustomersInvoices"));
print_liste_field_titre($langs->trans("Debit"), $_SERVER['PHP_SELF'], "t.debit", "", $options, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Credit"), $_SERVER['PHP_SELF'], "t.credit", "", $options, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Codejournal"), $_SERVER['PHP_SELF'], "t.code_journal", "", $options, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre('', $_SERVER["PHP_SELF"], "", $options, "", 'width="60" align="center"', $sortfield, $sortorder);
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre">' . $object->select_account($search_accountancy_code_start, 'search_accountancy_code_start', 1, array (), 1, 1, '') . '</td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre" align="center">';
print $langs->trans('From') . ': ';
print $form->select_date($search_date_start, 'date_start', 0, 0, 1);
print '<br>';
print $langs->trans('to') . ': ';
print $form->select_date($search_date_end, 'date_end', 0, 0, 1);
print '</td>';
print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_mvt_label" value="' . $search_mvt_label . '"/></td>';
print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_label_account" value="' . $search_label_account . '"/></td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre" align="right"><input type="text" name="search_ledger_code" size="3" value="' . $search_ledger_code . '"></td>';
print '<td class="liste_titre" align="right" colspan="2">';
$searchpitco=$form->showFilterAndCheckAddButtons(0);
print $searchpitco;
print '</td>';

print '</tr>';

$var = True;

$total_debit = 0;
$total_credit = 0;
$sous_total_debit = 0;
$sous_total_credit = 0;
$displayed_account_number = null;       // Start with undefined to be able to distinguish with empty 

foreach ( $object->lines as $line ) {
	$var = ! $var;

	$total_debit += $line->debit;
	$total_credit += $line->credit;

    $accountg = length_accountg($line->numero_compte);
	//if (empty($accountg)) $accountg = '-';
	
	// Is it a break ?
    if ($accountg != $displayed_account_number || ! isset($displayed_account_number)) {
        
        // Affiche un Sous-Total par compte comptable
        if (isset($displayed_account_number)) {
          print '<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("SubTotal").':</td><td class="nowrap" align="right">'.price($sous_total_debit).'</td><td class="nowrap" align="right">'.price($sous_total_credit).'</td>';
          print "<td>&nbsp;</td>\n";
          print '</tr>';
        }
        
        // Show the break account
        $colspan = 9;
        print "<tr>";
        print '<td colspan="'.$colspan.'" style="font-weight:bold; border-bottom: 1pt solid black;">';
        if (! empty($line->numero_compte) && $line->numero_compte != '-1') print length_accountg($line->numero_compte) . ' : ' . $object->get_compte_desc($line->numero_compte);
        else print '<span class="error">'.$langs->trans("Unknown").'</span>';
        print '</td>';
        print '</tr>';
        
        $displayed_account_number = $accountg;
        //if (empty($displayed_account_number)) $displayed_account_number='-';
        $sous_total_debit = 0;
        $sous_total_credit = 0;
    }

	print '<tr '. $bc[$var].'>';
	print '<td>&nbsp;</td>';
	print '<td align="right">'.$line->piece_num.'</td>';
	print '<td align="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
	print '<td><a href="./card.php?piece_num=' . $line->piece_num . '">' . $line->doc_ref . '</a></td>';
    
    // Affiche un lien vers la facture client/fournisseur
    $doc_ref = preg_replace('/\(.*\)/', '', $line->doc_ref);
    if ($line->doc_type == 'supplier_invoice')
     print strlen(length_accounta($line->code_tiers)) == 0 ? '<td><a href="/fourn/facture/list.php?search_ref_supplier=' . $doc_ref . '">' . $line->label_compte . '</a></td>' : '<td><a href="/fourn/facture/list.php?search_ref_supplier=' . $doc_ref . '">' . $line->label_compte . '</a><br /><span style="font-size:0.8em">(' . length_accounta($line->code_tiers) . ')</span></td>';
    elseif ($line->doc_type == 'customer_invoice')
    print strlen(length_accounta($line->code_tiers)) == 0 ? '<td><a href="/compta/facture/list.php?search_ref=' . $doc_ref . '">' . $line->label_compte . '</a></td>' : '<td><a href="/compta/facture/list.php?search_ref=' . $doc_ref . '">' . $line->label_compte . '</a><br /><span style="font-size:0.8em">(' . length_accounta($line->code_tiers) . ')</span></td>';
    else
    print strlen(length_accounta($line->code_tiers)) == 0 ? '<td>' . $line->label_compte . '</td>' : '<td>' . $line->label_compte . '<br /><span style="font-size:0.8em">(' . length_accounta($line->code_tiers) . ')</span></td>';


	print '<td align="right">' . price($line->debit) . '</td>';
	print '<td align="right">' . price($line->credit) . '</td>';
	print '<td align="center">' . $line->code_journal . '</td>';
	print '<td align="center">';
	print '<a href="./card.php?piece_num=' . $line->piece_num . '">' . img_edit() . '</a>&nbsp;';
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delmouv&mvt_num=' . $line->piece_num . $options . '&page=' . $page . '">' . img_delete() . '</a>';
	print '</td>';
	print "</tr>\n";

  // Comptabilise le sous-total
  $sous_total_debit += $line->debit;
  $sous_total_credit += $line->credit;

}

// Affiche un Sous-Total du dernier compte comptable affiché
print '<tr class="liste_total">';
print '<td align="right" colspan="5">'.$langs->trans("SubTotal").':</td><td class="nowrap" align="right">'.price($sous_total_debit).'</td><td class="nowrap" align="right">'.price($sous_total_credit).'</td>';
print "<td>&nbsp;</td>\n";
print '</tr>';


// Affiche le Total
print '<tr class="liste_total">';
print '<td align="right" colspan="5">'.$langs->trans("Total").':</td>';
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


$db->close();
