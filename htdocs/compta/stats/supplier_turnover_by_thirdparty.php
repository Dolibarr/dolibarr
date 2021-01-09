<?php
/* Copyright (C) 2020       Maxime Kohlhaas         <maxime@atm-consulting.fr>
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
 *       \file        htdocs/compta/stats/supplier_ca_by_thirdparty.php
 *       \brief       Page reporting purchase turnover by thirdparty
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'categories', 'bills', 'compta'));

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->global->ACCOUNTING_MODE;
if (GETPOST("modecompta")) $modecompta = GETPOST("modecompta");

$sortorder = isset($_GET["sortorder"]) ? $_GET["sortorder"] : $_POST["sortorder"];
$sortfield = isset($_GET["sortfield"]) ? $_GET["sortfield"] : $_POST["sortfield"];
if (!$sortorder) $sortorder = "asc";
if (!$sortfield) $sortfield = "nom";

$socid = GETPOST('socid', 'int');

// Category
$selected_cat = (int) GETPOST('search_categ', 'int');
$subcat = false;
if (GETPOST('subcat', 'alpha') === 'yes') {
	$subcat = true;
}

// Hook
$hookmanager->initHooks(array('supplierturnoverbythirdpartylist'));

// Security check
if ($user->socid > 0) $socid = $user->socid;
if (!empty($conf->comptabilite->enabled)) $result = restrictedArea($user, 'compta', '', '', 'resultat');
if (!empty($conf->accounting->enabled)) $result = restrictedArea($user, 'accounting', '', '', 'comptarapport');

// Date range
$year = GETPOST("year", 'int');
$month = GETPOST("month", 'int');
$search_societe = GETPOST("search_societe", 'alpha');
$search_zip = GETPOST("search_zip", 'alpha');
$search_town = GETPOST("search_town", 'alpha');
$search_country = GETPOST("search_country", 'alpha');
$date_startyear = GETPOST("date_startyear", 'alpha');
$date_startmonth = GETPOST("date_startmonth", 'alpha');
$date_startday = GETPOST("date_startday", 'alpha');
$date_endyear = GETPOST("date_endyear", 'alpha');
$date_endmonth = GETPOST("date_endmonth", 'alpha');
$date_endday = GETPOST("date_endday", 'alpha');
if (empty($year))
{
	$year_current = strftime("%Y", dol_now());
	$month_current = strftime("%m", dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = strftime("%m", dol_now());
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"));
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q = GETPOST("q", "int") ?GETPOST("q", "int") : 0;
	if (empty($q))
	{
		// We define date_start and date_end
		$month_start = GETPOST("month") ?GETPOST("month") : ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
		$year_end = $year_start;
		$month_end = $month_start;
		if (!GETPOST("month"))	// If month not forced
		{
			if (!GETPOST('year') && $month_start > $month_current)
			{
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) $month_end = 12;
			else $year_end++;
		}
		$date_start = dol_get_first_day($year_start, $month_start, false); $date_end = dol_get_last_day($year_end, $month_end, false);
	}
	if ($q == 1) { $date_start = dol_get_first_day($year_start, 1, false); $date_end = dol_get_last_day($year_start, 3, false); }
	if ($q == 2) { $date_start = dol_get_first_day($year_start, 4, false); $date_end = dol_get_last_day($year_start, 6, false); }
	if ($q == 3) { $date_start = dol_get_first_day($year_start, 7, false); $date_end = dol_get_last_day($year_start, 9, false); }
	if ($q == 4) { $date_start = dol_get_first_day($year_start, 10, false); $date_end = dol_get_last_day($year_start, 12, false); }
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
$commonparams['modecompta'] = $modecompta;
$commonparams['sortorder'] = $sortorder;
$commonparams['sortfield'] = $sortfield;

$headerparams = array();
$headerparams['date_startyear'] = $date_startyear;
$headerparams['date_startmonth'] = $date_startmonth;
$headerparams['date_startday'] = $date_startday;
$headerparams['date_endyear'] = $date_endyear;
$headerparams['date_endmonth'] = $date_endmonth;
$headerparams['date_endday'] = $date_endday;
$headerparams['q'] = $q;

$tableparams = array();
$tableparams['search_categ'] = $selected_cat;
$tableparams['search_societe'] = $search_societe;
$tableparams['search_zip'] = $search_zip;
$tableparams['search_town'] = $search_town;
$tableparams['search_country'] = $search_country;
$tableparams['subcat'] = ($subcat === true) ? 'yes' : '';

// Adding common parameters
$allparams = array_merge($commonparams, $headerparams, $tableparams);
$headerparams = array_merge($commonparams, $headerparams);
$tableparams = array_merge($commonparams, $tableparams);

foreach ($allparams as $key => $value) {
	$paramslink .= '&'.$key.'='.$value;
}


/*
 * View
 */

llxHeader();

$form = new Form($db);
$thirdparty_static = new Societe($db);
$formother = new FormOther($db);

// TODO Report from bookkeeping not yet available, so we switch on report on business events
if ($modecompta == "BOOKKEEPING") $modecompta = "CREANCES-DETTES";
if ($modecompta == "BOOKKEEPINGCOLLECTED") $modecompta = "RECETTES-DEPENSES";

// Show report header
if ($modecompta == "CREANCES-DETTES")
{
	$name = $langs->trans("PurchaseTurnover").', '.$langs->trans("ByThirdParties");
	$calcmode = $langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$description = $langs->trans("RulesPurchaseTurnoverDue");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "RECETTES-DEPENSES")
{
	$name = $langs->trans("PurchaseTurnoverCollected").', '.$langs->trans("ByThirdParties");
	$calcmode = $langs->trans("CalcModeEngagement");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$description = $langs->trans("RulesPurchaseTurnoverIn");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING")
{
} elseif ($modecompta == "BOOKKEEPINGCOLLECTED")
{
}
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
if ($date_end == dol_time_plus_duree($date_start, 1, 'y') - 1) $periodlink = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start - 1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start + 1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
else $periodlink = '';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, $tableparams, $calcmode);

if (!empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING')
{
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}

// Show Array
$catotal = 0;
$catotal_ht = 0;
$name = array();
$amount = array();
$amount_ht = array();
if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT DISTINCT s.rowid as socid, s.nom as name, s.zip, s.town, s.fk_pays,";
	$sql .= " sum(f.total_ht) as amount, sum(f.total_ttc) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
	if ($selected_cat === -2)	// Without any category
	{
		$sql .= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";
	} elseif ($selected_cat) 	// Into a specific category
	{
		$sql .= ", ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_societe as cs";
	}
	$sql .= " WHERE f.fk_statut in (1,2)";
	$sql .= " AND f.type IN (0,2)";
	$sql .= " AND f.fk_soc = s.rowid";
	if ($date_start && $date_end) {
		$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
	}
	if ($selected_cat === -2)	// Without any category
	{
		$sql .= " AND cs.fk_soc is null";
	} elseif ($selected_cat) {	// Into a specific category
		$sql .= " AND (c.rowid = ".$db->escape($selected_cat);
		if ($subcat) $sql .= " OR c.fk_parent = ".$db->escape($selected_cat);
		$sql .= ")";
		$sql .= " AND cs.fk_categorie = c.rowid AND cs.fk_soc = s.rowid";
	}
} else {
	$sql = "SELECT s.rowid as socid, s.nom as name, s.zip, s.town, s.fk_pays, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
	$sql .= ", ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if ($selected_cat === -2)	// Without any category
	{
		$sql .= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";
	} elseif ($selected_cat) 	// Into a specific category
	{
		$sql .= ", ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_societe as cs";
	}
	$sql .= " WHERE p.rowid = pf.fk_paiementfourn";
	$sql .= " AND pf.fk_facturefourn = f.rowid";
	$sql .= " AND f.fk_soc = s.rowid";
	if ($date_start && $date_end) {
		$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
	if ($selected_cat === -2)	// Without any category
	{
		$sql .= " AND cs.fk_soc is null";
	} elseif ($selected_cat) {	// Into a specific category
		$sql .= " AND (c.rowid = ".$selected_cat;
		if ($subcat) $sql .= " OR c.fk_parent = ".$selected_cat;
		$sql .= ")";
		$sql .= " AND cs.fk_categorie = c.rowid AND cs.fk_soc = s.rowid";
	}
}
if (!empty($search_societe))  $sql .= natural_search('s.nom', $search_societe);
if (!empty($search_zip))      $sql .= natural_search('s.zip', $search_zip);
if (!empty($search_town))     $sql .= natural_search('s.town', $search_town);
if ($search_country > 0)      $sql .= ' AND s.fk_pays = '.$search_country.'';
$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
if ($socid) $sql .= " AND f.fk_soc = ".$socid;
$sql .= " GROUP BY s.rowid, s.nom, s.zip, s.town, s.fk_pays";
$sql .= " ORDER BY s.rowid";
//echo $sql;

dol_syslog("supplier_turnover_by_thirdparty", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
			$amount_ht[$obj->socid] = $obj->amount;
			$amount[$obj->socid] = $obj->amount_ttc;
			$name[$obj->socid] = $obj->name.' '.$obj->firstname;
			$address_zip[$obj->socid] = $obj->zip;
			$address_town[$obj->socid] = $obj->town;
			$address_pays[$obj->socid] = getCountry($obj->fk_pays);
			$catotal_ht += $obj->amount;
			$catotal += $obj->amount_ttc;
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
foreach ($headerparams as $key => $value)
{
	 print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Category filter
print '<tr class="liste_titre">';
print '<td>';
print $langs->trans("Category").': '.$formother->select_categories(Categorie::TYPE_SUPPLIER, $selected_cat, 'search_categ', true);
print ' ';
print $langs->trans("SubCats").'? ';
print '<input type="checkbox" name="subcat" value="yes"';
if ($subcat) {
	print ' checked';
}
print'></td>';
print '<td colspan="7" class="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre left">';
print '<input class="flat" size="6" type="text" name="search_societe" value="'.$search_societe.'">';
print '</td>';
print '<td class="liste_titre left">';
print '<input class="flat" size="6" type="text" name="search_zip" value="'.$search_zip.'">';
print '</td>';
print '<td class="liste_titre left">';
print '<input class="flat" size="6" type="text" name="search_town" value="'.$search_town.'">';
print '</td>';
print '<td class="liste_titre left">';
print $form->select_country($search_country, 'search_country');
//print '<input class="flat" size="6" type="text" name="search_country" value="'.$search_country.'">';
print '</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '</tr>';

// Array titles
print "<tr class='liste_titre'>";
print_liste_field_titre(
	$langs->trans("Company"),
	$_SERVER["PHP_SELF"],
	"nom",
	"",
	$paramslink,
	"",
	$sortfield, $sortorder
	);
print_liste_field_titre(
	$langs->trans("Zip"),
	$_SERVER["PHP_SELF"],
	"zip",
	"",
	$paramslink,
	"",
	$sortfield, $sortorder
	);
print_liste_field_titre(
	$langs->trans("Town"),
	$_SERVER["PHP_SELF"],
	"town",
	"",
	$paramslink,
	"",
	$sortfield, $sortorder
	);
print_liste_field_titre(
	$langs->trans("Country"),
	$_SERVER["PHP_SELF"],
	"country",
	"",
	$paramslink,
	"",
	$sortfield, $sortorder
	);
if ($modecompta == 'CREANCES-DETTES') {
	print_liste_field_titre(
		   $langs->trans('AmountHT'),
		   $_SERVER["PHP_SELF"],
		   "amount_ht",
		   "",
		   $paramslink,
		   'class="right"',
		   $sortfield,
		   $sortorder
	);
} else {
	print_liste_field_titre('');
}
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
print_liste_field_titre(
	$langs->trans("OtherStatistics"),
	$_SERVER["PHP_SELF"],
	"",
	"",
	"",
	'align="center" width="20%"'
);
print "</tr>\n";


if (count($amount)) {
	$arrayforsort = $name;
	// Defining array arrayforsort
	if ($sortfield == 'nom' && $sortorder == 'asc') {
		asort($name);
		$arrayforsort = $name;
	}
	if ($sortfield == 'nom' && $sortorder == 'desc') {
		arsort($name);
		$arrayforsort = $name;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'asc') {
		asort($amount_ht);
		$arrayforsort = $amount_ht;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'desc') {
		arsort($amount_ht);
		$arrayforsort = $amount_ht;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'asc') {
		asort($amount);
		$arrayforsort = $amount;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'desc') {
		arsort($amount);
		$arrayforsort = $amount;
	}
	if ($sortfield == 'zip' && $sortorder == 'asc') {
		asort($address_zip);
		$arrayforsort = $address_zip;
	}
	if ($sortfield == 'zip' && $sortorder == 'desc') {
		arsort($address_zip);
		$arrayforsort = $address_zip;
	}
	if ($sortfield == 'town' && $sortorder == 'asc') {
		asort($address_town);
		$arrayforsort = $address_town;
	}
	if ($sortfield == 'town' && $sortorder == 'desc') {
		arsort($address_town);
		$arrayforsort = $address_town;
	}
	if ($sortfield == 'country' && $sortorder == 'asc') {
		asort($address_pays);
		$arrayforsort = $address_town;
	}
	if ($sortfield == 'country' && $sortorder == 'desc') {
		arsort($address_pays);
		$arrayforsort = $address_town;
	}

	foreach ($arrayforsort as $key=>$value) {
		print '<tr class="oddeven">';

		// Third party
		$fullname = $name[$key];
		if ($key > 0) {
			$thirdparty_static->id = $key;
			$thirdparty_static->name = $fullname;
			$thirdparty_static->client = 1;
			$linkname = $thirdparty_static->getNomUrl(1, 'supplier');
		} else {
			$linkname = $langs->trans("PaymentsNotLinkedToInvoice");
		}
		print "<td>".$linkname."</td>\n";

		print '<td>';
		print $address_zip[$key];
		print '</td>';

		print '<td>';
		print $address_town[$key];
		print '</td>';

		print '<td>';
		print $address_pays[$key];
		print '</td>';

		// Amount w/o VAT
		print '<td class="right">';
		if ($modecompta != 'CREANCES-DETTES') {
			if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/paiement/list.php?socid='.$key.'">';
			} else {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/paiement/list.php?socid=-1">';
			}
		} else {
			if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$key.'">';
			} else {
				print '<a href="#">';
			}
			print price($amount_ht[$key]);
		}
		print '</td>';

		// Amount with VAT
		print '<td class="right">';
		if ($modecompta != 'CREANCES-DETTES') {
			if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/paiement/list.php?socid='.$key.'">';
			} else {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/paiement/list.php?orphelins=1">';
			}
		} else {
			if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$key.'">';
			} else {
				print '<a href="#">';
			}
		}
		print price($amount[$key]);
		print '</a>';
		print '</td>';

		// Percent;
		print '<td class="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';

		// Other stats
		print '<td class="center">';
		if (!empty($conf->supplier_proposal->enabled) && $key > 0) {
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$key.'">'.img_picto($langs->trans("ProposalStats"), "stats").'</a>&nbsp;';
		}
		if (!empty($conf->fournisseur->enabled) && $key > 0) {
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/stats/index.php?mode=supplier&socid='.$key.'">'.img_picto($langs->trans("OrderStats"), "stats").'</a>&nbsp;';
		}
		if (!empty($conf->fournisseur->enabled) && $key > 0) {
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?mode=supplier&socid='.$key.'">'.img_picto($langs->trans("InvoiceStats"), "stats").'</a>&nbsp;';
		}
		print '</td>';
		print "</tr>\n";
		$i++;
	}

	// Total
	print '<tr class="liste_total">';
	print '<td>'.$langs->trans("Total").'</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	if ($modecompta != 'CREANCES-DETTES') {
		print '<td></td>';
	} else {
		print '<td class="right">'.price($catotal_ht).'</td>';
	}
	print '<td class="right">'.price($catotal).'</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	$db->free($result);
}

print "</table>";
print "</div>";

print '</form>';

// End of page
llxFooter();
$db->close();
