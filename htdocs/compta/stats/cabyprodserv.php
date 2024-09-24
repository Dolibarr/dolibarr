<?php
/* Copyright (C) 2013       Antoine Iauch	        <aiauch@gpcsolutions.fr>
 * Copyright (C) 2013-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Charlene Benke      	<charlene@patas-monkey.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	   \file		htdocs/compta/stats/cabyprodserv.php
 *	   \brief	   	Page reporting Turnover billed by Products & Services
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("products", "categories", "errors", 'accountancy'));

// Security pack (data & check)
$socid = GETPOSTINT('socid');

if ($user->socid > 0) {
	$socid = $user->socid;
}

// Hook
$hookmanager->initHooks(array('cabyprodservlist'));

if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = getDolGlobalString('ACCOUNTING_MODE');
if (GETPOST("modecompta")) {
	$modecompta = GETPOST("modecompta");
}

$sortorder = GETPOST("sortorder", 'aZ09comma');
$sortfield = GETPOST("sortfield", 'aZ09comma');
if (!$sortorder) {
	$sortorder = "asc";
}
if (!$sortfield) {
	$sortfield = "ref";
}

// Category
$selected_cat = GETPOST('search_categ', 'intcomma');
$selected_catsoc = GETPOST('search_categ_soc', 'intcomma');
$selected_soc = GETPOST('search_soc', 'intcomma');
$selected_commercial = GETPOST('search_commercial', 'int');
$typent_id = GETPOST('typent_id', 'int');
$subcat = false;
if (GETPOST('subcat', 'alpha') === 'yes') {
	$subcat = true;
}
$categorie = new Categorie($db);

// product/service
$selected_type = GETPOST('search_type', 'intcomma');
if ($selected_type == '') {
	$selected_type = -1;
}

// Date range
$year = GETPOST("year");
$month = GETPOST("month");
$date_startyear = GETPOST("date_startyear");
$date_startmonth = GETPOST("date_startmonth");
$date_startday = GETPOST("date_startday");
$date_endyear = GETPOST("date_endyear");
$date_endmonth = GETPOST("date_endmonth");
$date_endday = GETPOST("date_endday");
if (empty($year)) {
	$year_current = dol_print_date(dol_now(), '%Y');
	$month_current = dol_print_date(dol_now(), '%m');
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = dol_print_date(dol_now(), '%m');
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"), 'tzserver');	// We use timezone of server so report is same from everywhere
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"), 'tzserver');		// We use timezone of server so report is same from everywhere
// Quarter
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOSTINT("q");
	if (empty($q)) {
		// We define date_start and date_end
		$month_start = GETPOST("month") ? GETPOST("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$year_end = $year_start;
		$month_end = $month_start;
		if (!GETPOST("month")) {	// If month not forced
			if (!GETPOST('year') && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
			} else {
				$year_end++;
			}
		}
		$date_start = dol_get_first_day($year_start, $month_start, false);
		$date_end = dol_get_last_day($year_end, $month_end, false);
	} else {
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
} else {
	// TODO We define q
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
if (!empty($q)) {
	$headerparams['q'] = $q;
}

$tableparams = array();
if (!empty($selected_cat)) {
	$tableparams['search_categ'] = $selected_cat;
}
if (!empty($selected_catsoc)) {
	$tableparams['search_categ_soc'] = $selected_catsoc;
}
if (!empty($selected_soc)) {
	$tableparams['search_soc'] = $selected_soc;
}
if (!empty($selected_type)) {
	$tableparams['search_type'] = $selected_type;
}
if (!empty($typent_id)) {
	$tableparams['typent_id'] = $typent_id;
}
$tableparams['subcat'] = $subcat ? 'yes' : '';

// Adding common parameters
$allparams = array_merge($commonparams, $headerparams, $tableparams);
$headerparams = array_merge($commonparams, $headerparams);
$tableparams = array_merge($commonparams, $tableparams);

$paramslink = "";
foreach ($allparams as $key => $value) {
	$paramslink .= '&'.urlencode($key).'='.urlencode($value);
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

$exportlink = "";
$namelink = "";

// Show report header
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("Turnover").', '.$langs->trans("ByProductsAndServices");
	$calcmode = $langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';

	$description = $langs->trans("RulesCADue");
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}
	$builddate = dol_now();
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("TurnoverCollected").', '.$langs->trans("ByProductsAndServices");
	$calcmode = $langs->trans("CalcModePayment");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';

	$description = $langs->trans("RulesCAIn");
	$description .= $langs->trans("DepositsAreIncluded");

	$builddate = dol_now();
} elseif ($modecompta == "BOOKKEEPING") {
} elseif ($modecompta == "BOOKKEEPINGCOLLECTED") {
}

$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
if ($date_end == dol_time_plus_duree($date_start, 1, 'y') - 1) {
	$periodlink = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start - 1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start + 1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
} else {
	$periodlink = '';
}

report_header($name, $namelink, $period, $periodlink, $description, $builddate, $exportlink, $tableparams, $calcmode);

if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
}


$name = array();

// SQL request
$catotal = 0;
$catotal_ht = 0;
$qtytotal = 0;

if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT p.rowid as rowid, p.ref as ref, p.label as label, p.fk_product_type as product_type,";
	$sql .= " SUM(l.total_ht) as amount, SUM(l.total_ttc) as amount_ttc,";
	$sql .= " SUM(CASE WHEN f.type = 2 THEN -l.qty ELSE l.qty END) as qty";

	$parameters = array();
	$hookmanager->executeHooks('printFieldListSelect', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= ",".MAIN_DB_PREFIX."facturedet as l";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
	if ($typent_id > 0) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON (soc.rowid = f.fk_soc)";
	}
	$parameters = array();
	$hookmanager->executeHooks('printFieldListFrom', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " WHERE l.fk_facture = f.rowid";
	$sql .= " AND f.fk_statut in (1,2)";
	$sql .= " AND l.product_type in (0,1)";
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$sql .= " AND f.type IN (0,1,2,5)";
	} else {
		$sql .= " AND f.type IN (0,1,2,3,5)";
	}
	if ($date_start && $date_end) {
		$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
	}
	if ($selected_type >= 0) {
		$sql .= " AND l.product_type = ".((int) $selected_type);
	}

	// Search for tag/category ($searchCategoryProductList is an array of ID)
	$searchCategoryProductOperator = -1;
	$searchCategoryProductList = array($selected_cat);
	if ($subcat) {
		$TListOfCats = $categorie->get_full_arbo('product', $selected_cat, 1);
		$searchCategoryProductList = array();
		foreach ($TListOfCats as $key => $cat) {
			$searchCategoryProductList[] = $cat['id'];
		}
	}
	if (!empty($searchCategoryProductList)) {
		$searchCategoryProductSqlList = array();
		$listofcategoryid = '';
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			if (intval($searchCategoryProduct) == -2) {
				$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck WHERE l.fk_product = ck.fk_product)";
			} elseif (intval($searchCategoryProduct) > 0) {
				if ($searchCategoryProductOperator == 0) {
					$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck WHERE l.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
				} else {
					$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
				}
			}
		}
		if ($listofcategoryid) {
			$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck WHERE l.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
		}
		if ($searchCategoryProductOperator == 1) {
			if (!empty($searchCategoryProductSqlList)) {
				$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
			}
		} else {
			if (!empty($searchCategoryProductSqlList)) {
				$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
			}
		}
	}

	// Search for tag/category ($searchCategorySocieteList is an array of ID)
	$searchCategorySocieteOperator = -1;
	$searchCategorySocieteList = array($selected_catsoc);
	if (!empty($searchCategorySocieteList)) {
		$searchCategorySocieteSqlList = array();
		$listofcategoryid = '';
		foreach ($searchCategorySocieteList as $searchCategorySociete) {
			if (intval($searchCategorySociete) == -2) {
				$searchCategorySocieteSqlList[] = "NOT EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE f.fk_soc = cs.fk_soc)";
			} elseif (intval($searchCategorySociete) > 0) {
				if ($searchCategorySocieteOperator == 0) {
					$searchCategorySocieteSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE f.fk_soc = cs.fk_soc AND cs.fk_categorie = ".((int) $searchCategorySociete).")";
				} else {
					$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategorySociete);
				}
			}
		}
		if ($listofcategoryid) {
			$searchCategorySocieteSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE f.fk_soc = cs.fk_soc AND cs.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
		}
		if ($searchCategorySocieteOperator == 1) {
			if (!empty($searchCategorySocieteSqlList)) {
				$sql .= " AND (".implode(' OR ', $searchCategorySocieteSqlList).")";
			}
		} else {
			if (!empty($searchCategorySocieteSqlList)) {
				$sql .= " AND (".implode(' AND ', $searchCategorySocieteSqlList).")";
			}
		}
	}

	if ($selected_soc > 0) {
		$sql .= " AND f.fk_soc = ".((int) $selected_soc);
	}

	if ($typent_id > 0) {
		$sql .= " AND soc.fk_typent = ".((int) $typent_id);
	}
	if ($selected_commercial > 0) {
		$sql.= " AND EXISTS (SELECT sc.rowid FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE f.fk_soc = sc.fk_soc";
		$sql.= " AND sc.fk_user = ".$selected_commercial.")";
	}
	$sql .= " AND f.entity IN (".getEntity('invoice').")";

	$parameters = array();
	$hookmanager->executeHooks('printFieldListWhere', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY p.rowid, p.ref, p.label, p.fk_product_type";
	$sql .= $db->order($sortfield, $sortorder);

	dol_syslog("cabyprodserv", LOG_DEBUG);
	$result = $db->query($sql);

	$amount_ht = array();
	$amount = array();
	$qty = array();
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($result);

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

	// Show Array
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
	print img_picto('', 'category', 'class="pictofixedwidth"');
	print $formother->select_categories(Categorie::TYPE_PRODUCT, $selected_cat, 'search_categ', 0, $langs->trans("Category"));
	print ' ';
	print '<input type="checkbox" class="marginleft" id="subcat" name="subcat" value="yes"';
	if ($subcat) {
		print ' checked';
	}
	print '>';
	print '<label for="subcat" class="marginrightonly">'.$langs->trans("SubCats").'?</label>';
	// type filter (produit/service)
	$form->select_type_of_lines(isset($selected_type) ? $selected_type : -1, 'search_type', $langs->trans("Type"), 1, 1);

	// Third party filter
	print '<br>';
	print img_picto('', 'category', 'class="pictofixedwidth"');
	print $formother->select_categories(Categorie::TYPE_CUSTOMER, $selected_catsoc, 'search_categ_soc', 0, $langs->trans("CustomersProspectsCategoriesShort"));

	// Type of third party filter
	print '&nbsp; &nbsp;';
	$formcompany = new FormCompany($db);
	// NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on ascending label.
	$sortparam = getDolGlobalString('SOCIETE_SORT_ON_TYPEENT', 'ASC');
	print $form->selectarray("typent_id", $formcompany->typent_array(0), $typent_id, $langs->trans("ThirdPartyType"), 0, 0, '', 0, 0, 0, $sortparam, '', 1);

	print '<br>';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print $form->select_company($selected_soc, 'search_soc', '', $langs->trans("ThirdParty"));
	print '<br>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	print $form->select_dolusers($selected_commercial, 'search_commercial', $tmptitle);
	print '</td>';

	print '<td colspan="5" class="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';

	print '</tr>';

	// Array header
	print "<tr class=\"liste_titre\">";
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
			/*if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?productid='.$key.'">';
			} else {
				print '<a href="#">';
			}*/
			print price($amount_ht[$key]);
			//print '</a>';
			print '</td>';

			// Amount with VAT
			print '<td class="right">';
			/*if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?productid='.$key.'">';
			} else {
				print '<a href="#">';
			}*/
			print price($amount[$key]);
			//print '</a>';
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
	print '</div>';

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
