<?php
/* Copyright (C) 2020       Maxime Kohlhaas         <maxime@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	   \file		htdocs/compta/stats/supplier_turnover_by_prodserv.php
 *	   \brief	   Page reporting purchase turnover by Products & Services
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("products", "categories", "errors", 'accountancy'));

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = getDolGlobalString('ACCOUNTING_MODE');
if (GETPOST("modecompta")) {
	$modecompta = GETPOST("modecompta");
}

// Sort Order
$sortorder = GETPOST("sortorder", 'aZ09comma');
$sortfield = GETPOST("sortfield", 'aZ09comma');
if (!$sortorder) {
	$sortorder = "asc";
}
if (!$sortfield) {
	$sortfield = "ref";
}

// Category
$selected_cat = GETPOSTINT('search_categ');
$selected_soc = GETPOSTINT('search_soc');
$subcat = false;
if (GETPOST('subcat', 'alpha') === 'yes') {
	$subcat = true;
}
// product/service
$selected_type = GETPOST('search_type', "intcomma");
if ($selected_type == '') {
	$selected_type = -1;
}

// Hook
$hookmanager->initHooks(array('supplierturnoverbyprodservlist'));

$date_startyear = GETPOSTINT("date_startyear");
$date_startmonth = GETPOSTINT("date_startmonth");
$date_startday = GETPOSTINT("date_startday");
$date_endyear = GETPOSTINT("date_endyear");
$date_endmonth = GETPOSTINT("date_endmonth");
$date_endday = GETPOSTINT("date_endday");

$nbofyear = 1;

// Date range
$year = GETPOSTINT("year");
$month = GETPOSTINT("month");
if (empty($year)) {
	$year_current = (int) dol_print_date(dol_now(), "%Y");
	$month_current = (int) dol_print_date(dol_now(), "%m");
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = (int) dol_print_date(dol_now(), "%m");
	$year_start = $year - $nbofyear + (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 0 : 1);
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear, 'tzserver');	// We use timezone of server so report is same from everywhere
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear, 'tzserver');		// We use timezone of server so report is same from everywhere

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOSTINT("q");
	if (empty($q)) {
		// We define date_start and date_end
		$year_end = $year_start + $nbofyear - (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 0 : 1);
		$month_start = GETPOSTISSET("month") ? GETPOSTINT("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		if (!GETPOST("month")) {	// If month not forced
			if (!$year && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
			}
		} else {
			$month_end = $month_start;
		}
		$date_start = dol_get_first_day($year_start, $month_start, false);
		$date_end = dol_get_last_day($year_end, $month_end, false);
	}
	if ($q == 1) {
		$date_start = dol_get_first_day($year_start, 1, false);
		$date_end = dol_get_last_day($year_start, 3, false);
	}
	if ($q == 2) {
		$date_start = dol_get_first_day($year_start, 4, false);
		$date_end = dol_get_last_day($year_start, 6, false);
	}
	if ($q == 3) {
		$date_start = dol_get_first_day($year_start, 7, false);
		$date_end = dol_get_last_day($year_start, 9, false);
	}
	if ($q == 4) {
		$date_start = dol_get_first_day($year_start, 10, false);
		$date_end = dol_get_last_day($year_start, 12, false);
	}
}

// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps = dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;

$commonparams = array();
if (!empty($modecompta)) {
	$commonparams['modecompta'] = $modecompta;
}
if (!empty($sortorder)) {
	$commonparams['sortorder'] = $sortorder;
}
if (!empty($sortfield)) {
	$commonparams['sortfield'] = $sortfield;
}

$headerparams = array();
if (!empty($date_startyear)) {
	$headerparams['date_startyear'] = $date_startyear;
}
if (!empty($date_startmonth)) {
	$headerparams['date_startmonth'] = $date_startmonth;
}
if (!empty($date_startday)) {
	$headerparams['date_startday'] = $date_startday;
}
if (!empty($date_endyear)) {
	$headerparams['date_endyear'] = $date_endyear;
}
if (!empty($date_endmonth)) {
	$headerparams['date_endmonth'] = $date_endmonth;
}
if (!empty($date_endday)) {
	$headerparams['date_endday'] = $date_endday;
}
if (!empty($year)) {
	$headerparams['year'] = $year;
}
if (!empty($month)) {
	$headerparams['month'] = $month;
}
$headerparams['q'] = $q;

$tableparams = array();
if (!empty($selected_cat)) {
	$tableparams['search_categ'] = $selected_cat;
}
if (!empty($selected_soc)) {
	$tableparams['search_soc'] = $selected_soc;
}
if (!empty($selected_type)) {
	$tableparams['search_type'] = $selected_type;
}
$tableparams['subcat'] = $subcat ? 'yes' : '';

// Adding common parameters
$allparams = array_merge($commonparams, $headerparams, $tableparams);
$headerparams = array_merge($commonparams, $headerparams);
$tableparams = array_merge($commonparams, $tableparams);

$paramslink = '';
foreach ($allparams as $key => $value) {
	$paramslink .= '&'.$key.'='.$value;
}

// Security pack (data & check)
$socid = GETPOSTINT('socid');

if ($user->socid > 0) {
	$socid = $user->socid;
}
if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}


/*
 * View
 */

llxHeader();

$form = new Form($db);
$formother = new FormOther($db);

// TODO Report from bookkeeping not yet available, so we switch on report on business events
if ($modecompta == "BOOKKEEPING") {
	$modecompta = "CREANCES-DETTES";
}
if ($modecompta == "BOOKKEEPINGCOLLECTED") {
	$modecompta = "RECETTES-DEPENSES";
}

// Show report header
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("PurchaseTurnover").', '.$langs->trans("ByProductsAndServices");
	$calcmode = $langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';

	$description = $langs->trans("RulesPurchaseTurnoverDue");
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("PurchaseTurnoverCollected").', '.$langs->trans("ByProductsAndServices");
	$calcmode = $langs->trans("CalcModePayment");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$description = $langs->trans("RulesPurchaseTurnoverIn");
} elseif ($modecompta == "BOOKKEEPING") {
	// TODO
} elseif ($modecompta == "BOOKKEEPINGCOLLECTED") {
	// TODO
}

$builddate = dol_now();
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
if ($date_end == dol_time_plus_duree($date_start, 1, 'y') - 1) {
	$periodlink = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start - 1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start + 1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
} else {
	$periodlink = '';
}

$exportlink = '';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, $tableparams, $calcmode);

if (isModEnabled('accounting')) {
	if ($modecompta != 'BOOKKEEPING') {
		print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
	} else {
		// Test if there is at least one line in bookkeeping
		$pcgverid = getDolGlobalString('CHARTOFACCOUNTS');
		$pcgvercode = dol_getIdFromCode($db, $pcgverid, 'accounting_system', 'rowid', 'pcg_version');
		if (empty($pcgvercode)) {
			$pcgvercode = $pcgverid;
		}

		$sql = "SELECT b.rowid ";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b,";
		$sql .= " ".MAIN_DB_PREFIX."accounting_account as aa";
		$sql .= " WHERE b.entity = ".$conf->entity; // In module double party accounting, we never share entities
		$sql .= " AND b.numero_compte = aa.account_number";
		$sql .= " AND aa.entity = ".$conf->entity;
		$sql .= " AND aa.fk_pcg_version = '".$db->escape($pcgvercode)."'"; // fk_pcg_version is varchar(32)
		$sql .= $db->plimit(1);

		$resql = $db->query($sql);
		$nb = $db->num_rows($resql);
		if ($nb == 0) {
			$langs->load("errors");
			print info_admin($langs->trans("WarningNoDataTransferedInAccountancyYet"), 0, 0, '1');
		}
	}
}



$name = array();
$amount = array();
$amount_ht = array();
$qty = array();

// SQL request
$catotal = 0;
$catotal_ht = 0;
$qtytotal = 0;

if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT DISTINCT p.rowid as rowid, p.ref as ref, p.label as label, p.fk_product_type as product_type,";
	$sql .= " SUM(l.total_ht) as amount, SUM(l.total_ttc) as amount_ttc,";
	$sql .= " SUM(CASE WHEN f.type = 2 THEN -l.qty ELSE l.qty END) as qty";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	if ($selected_soc > 0) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON (soc.rowid = f.fk_soc)";
	}
	$sql .= ",".MAIN_DB_PREFIX."facture_fourn_det as l";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
	if ($selected_cat === -2) {	// Without any category
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
	} elseif ($selected_cat) { 	// Into a specific category
		$sql .= ", ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_product as cp";
	}
	$sql .= " WHERE l.fk_facture_fourn = f.rowid";
	$sql .= " AND f.fk_statut in (1,2)";
	$sql .= " AND f.type IN (0,2)";

	if ($date_start && $date_end) {
		$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
	}
	if ($selected_type >= 0) {
		$sql .= " AND l.product_type = ".((int) $selected_type);
	}
	if ($selected_cat === -2) {	// Without any category
		$sql .= " AND cp.fk_product is null";
	} elseif ($selected_cat) {	// Into a specific category
		$sql .= " AND (c.rowid = ".((int) $selected_cat);
		if ($subcat) {
			$sql .= " OR c.fk_parent = ".((int) $selected_cat);
		}
		$sql .= ")";
		$sql .= " AND cp.fk_categorie = c.rowid AND cp.fk_product = p.rowid";
	}
	if ($selected_soc > 0) {
		$sql .= " AND soc.rowid=".((int) $selected_soc);
	}
	$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
	$sql .= " GROUP BY p.rowid, p.ref, p.label, p.fk_product_type";
	$sql .= $db->order($sortfield, $sortorder);

	dol_syslog("supplier_turnover_by_prodserv", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$amount_ht[$obj->rowid] = $obj->amount;
			$amount[$obj->rowid] = $obj->amount_ttc;
			$qty[$obj->rowid] = $obj->qty;
			$name[$obj->rowid] = $obj->ref.'&nbsp;-&nbsp;'.$obj->label;
			$type[$obj->rowid] = $obj->product_type;

			$catotal_ht += $obj->amount;
			$catotal += $obj->amount_ttc;
			$qtytotal += $obj->qty;

			$i++;
		}
	} else {
		dol_print_error($db);
	}

	// Show array
	$i = 0;
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
	// Extra parameters management
	foreach ($headerparams as $key => $value) {
		print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
	}

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Category filter
	print '<tr class="liste_titre">';
	print '<td>';
	print img_picto('', 'category', 'class="paddingrightonly"');
	print $formother->select_categories(Categorie::TYPE_PRODUCT, $selected_cat, 'search_categ', 0, $langs->trans("Category"), 'maxwidth300');
	print ' ';
	print '<label for="subcat" class="marginleftonly">'.$langs->trans("SubCats").'?</label> ';
	print '<input type="checkbox" id="subcat" name="subcat" value="yes"';
	if ($subcat) {
		print ' checked';
	}
	print '>';
	// type filter (produit/service)
	print ' &nbsp; ';
	$form->select_type_of_lines(isset($selected_type) ? $selected_type : -1, 'search_type', $langs->trans("Type"), 1, 1);

	//select thirdparty
	print '<br>';
	print img_picto('', 'company', 'class="paddingrightonly"');
	print $form->select_thirdparty_list($selected_soc, 'search_soc', '', $langs->trans("ThirdParty"), 0, 0, [], '', 0, 0, 'maxwidth250');
	print '</td>';

	print '<td colspan="5" class="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td></tr>';

	// Array header
	print '<tr class="liste_titre">';
	print_liste_field_titre(
		$langs->trans("Product"),
		$_SERVER["PHP_SELF"],
		"ref",
		"",
		$paramslink,
		"",
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans('Quantity'),
		$_SERVER["PHP_SELF"],
		"qty",
		"",
		$paramslink,
		'class="right"',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("Percentage"),
		$_SERVER["PHP_SELF"],
		"qty",
		"",
		$paramslink,
		'class="right"',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans('AmountHT'),
		$_SERVER["PHP_SELF"],
		"amount",
		"",
		$paramslink,
		'class="right"',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("AmountTTC"),
		$_SERVER["PHP_SELF"],
		"amount_ttc",
		"",
		$paramslink,
		'class="right"',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		$langs->trans("Percentage"),
		$_SERVER["PHP_SELF"],
		"amount_ttc",
		"",
		$paramslink,
		'class="right"',
		$sortfield,
		$sortorder
	);
	print "</tr>\n";

	if (count($name)) {
		foreach ($name as $key => $value) {
			print '<tr class="oddeven">';

			// Product
			print "<td>";
			$fullname = $name[$key];
			if ($key > 0) {
				$linkname = '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$key.'">'.img_object($langs->trans("ShowProduct"), $type[$key] == 0 ? 'product' : 'service').' '.$fullname.'</a>';
			} else {
				$linkname = $langs->trans("PaymentsNotLinkedToProduct");
			}
			print $linkname;
			print "</td>\n";

			// Quantity
			print '<td class="right">';
			print $qty[$key];
			print '</td>';

			// Percent;
			print '<td class="right">'.($qtytotal > 0 ? round(100 * $qty[$key] / $qtytotal, 2).'%' : '&nbsp;').'</td>';

			// Amount w/o VAT
			print '<td class="right">';
			print price($amount_ht[$key]);
			print '</td>';

			// Amount with VAT
			print '<td class="right">';
			print price($amount[$key]);
			print '</td>';

			// Percent;
			print '<td class="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';

			// TODO: statistics?

			print "</tr>\n";
			$i++;
		}

		// Total
		print '<tr class="liste_total">';
		print '<td>'.$langs->trans("Total").'</td>';
		print '<td class="right">'.$qtytotal.'</td>';
		print '<td class="right">100%</td>';
		print '<td class="right">'.price($catotal_ht).'</td>';
		print '<td class="right">'.price($catotal).'</td>';
		print '<td class="right">100%</td>';
		print '</tr>';

		$db->free($result);
	} else {
		print '<tr><td colspan="6"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}
	print "</table>";
	print "</div>";

	print '</form>';
} else {
	// $modecompta != 'CREANCES-DETTES'
	// "Calculation of part of each product for accountancy in this mode is not possible. When a partial payment (for example 5 euros) is done on an
	// invoice with 2 product (product A for 10 euros and product B for 20 euros), what is part of paiment for product A and part of paiment for product B ?
	// Because there is no way to know this, this report is not relevant.
	print '<br>'.$langs->trans("TurnoverPerProductInCommitmentAccountingNotRelevant").'<br>';
}

// End of page
llxFooter();
$db->close();
