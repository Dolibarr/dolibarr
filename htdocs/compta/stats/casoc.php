<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Antoine Iauch           <aiauch@gpcsolutions.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file        htdocs/compta/stats/casoc.php
 *       \brief       Page reporting Turnover (CA) by thirdparty
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
if (GETPOST("modecompta")) $modecompta=GETPOST("modecompta");

$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="nom";

$socid = GETPOST('socid','int');

// Category
$selected_cat = (int) GETPOST('search_categ', 'int');
$subcat = false;
if (GETPOST('subcat', 'alpha') === 'yes') {
    $subcat = true;
}

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');

// Date range
$year=GETPOST("year",'int');
$month=GETPOST("month",'int');
$search_societe = GETPOST("search_societe",'alpha');
$search_zip = GETPOST("search_zip",'alpha');
$search_town = GETPOST("search_town",'alpha');
$search_country = GETPOST("search_country",'alpha');
$date_startyear = GETPOST("date_startyear",'alpha');
$date_startmonth = GETPOST("date_startmonth",'alpha');
$date_startday = GETPOST("date_startday",'alpha');
$date_endyear = GETPOST("date_endyear",'alpha');
$date_endmonth = GETPOST("date_endmonth",'alpha');
$date_endday = GETPOST("date_endday",'alpha');
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$month_current = strftime("%m",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = strftime("%m",dol_now());
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,GETPOST("date_startmonth"),GETPOST("date_startday"),GETPOST("date_startyear"));
$date_end=dol_mktime(23,59,59,GETPOST("date_endmonth"),GETPOST("date_endday"),GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q","int")?GETPOST("q","int"):0;
	if (empty($q))
	{
		// We define date_start and date_end
		$month_start=GETPOST("month")?GETPOST("month"):($conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START):1);
		$year_end=$year_start;
		$month_end=$month_start;
		if (! GETPOST("month"))	// If month not forced
		{
			if (! GETPOST('year') && $month_start > $month_current)
			{
				$year_start--;
				$year_end--;
			}
			$month_end=$month_start-1;
			if ($month_end < 1) $month_end=12;
			else $year_end++;
		}
		$date_start=dol_get_first_day($year_start,$month_start,false); $date_end=dol_get_last_day($year_end,$month_end,false);
	}
	if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
	if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
	if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
	if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
}
else
{
	// TODO We define q
}

// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps=dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe=dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;

$commonparams=array();
$commonparams['modecompta']=$modecompta;
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
$tableparams['subcat'] = ($subcat === true)?'yes':'';

// Adding common parameters
$allparams = array_merge($commonparams, $headerparams, $tableparams);
$headerparams = array_merge($commonparams, $headerparams);
$tableparams = array_merge($commonparams, $tableparams);

foreach($allparams as $key => $value) {
    $paramslink .= '&' . $key . '=' . $value;
}


/*
 * View
 */

llxHeader();

$form=new Form($db);
$thirdparty_static=new Societe($db);
$formother = new FormOther($db);

// TODO Report from bookkeeping not yet available, so we switch on report on business events
if ($modecompta=="BOOKKEEPING") $modecompta="CREANCES-DETTES";
if ($modecompta=="BOOKKEEPINGCOLLECTED") $modecompta="RECETTES-DEPENSES";

// Show report header
if ($modecompta=="CREANCES-DETTES")
{
	$name=$langs->trans("Turnover").', '.$langs->trans("ByThirdParties");
	$calcmode=$langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$description=$langs->trans("RulesCADue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
	$builddate=dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else if ($modecompta=="RECETTES-DEPENSES")
{
	$name=$langs->trans("TurnoverCollected").', '.$langs->trans("ByThirdParties");
	$calcmode=$langs->trans("CalcModeEngagement");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$description=$langs->trans("RulesCAIn");
	$description.= $langs->trans("DepositsAreIncluded");
	$builddate=dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else if ($modecompta=="BOOKKEEPING")
{


}
else if ($modecompta=="BOOKKEEPINGCOLLECTED")
{


}
$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
if ($date_end == dol_time_plus_duree($date_start, 1, 'y') - 1) $periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
else $periodlink = '';

report_header($name,$namelink,$period,$periodlink,$description,$builddate,$exportlink,$tableparams,$calcmode);

if (! empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING')
{
    print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}


$name=array();

// Show Array
$catotal=0;
if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT DISTINCT s.rowid as socid, s.nom as name, s.zip, s.town, s.fk_pays,";
	$sql.= " sum(f.total) as amount, sum(f.total_ttc) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
	if ($selected_cat === -2)	// Without any category
	{
	    $sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";
	}
	else if ($selected_cat) 	// Into a specific category
	{
	    $sql.= ", ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_societe as cs";
	}
	$sql.= " WHERE f.fk_statut in (1,2)";
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	    $sql.= " AND f.type IN (0,1,2,5)";
	} else {
	    $sql.= " AND f.type IN (0,1,2,3,5)";
	}
	$sql.= " AND f.fk_soc = s.rowid";
	if ($date_start && $date_end) {
	    $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
	}
	if ($selected_cat === -2)	// Without any category
	{
	    $sql.=" AND cs.fk_soc is null";
	}
	else if ($selected_cat) {	// Into a specific category
	    $sql.= " AND (c.rowid = ".$db->escape($selected_cat);
	    if ($subcat) $sql.=" OR c.fk_parent = " . $db->escape($selected_cat);
	    $sql.= ")";
		$sql.= " AND cs.fk_categorie = c.rowid AND cs.fk_soc = s.rowid";
	}
} else {
	/*
	 * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
	 * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
	 */
	$sql = "SELECT s.rowid as socid, s.nom as name, s.zip, s.town, s.fk_pays, sum(pf.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if ($selected_cat === -2)	// Without any category
	{
	    $sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";
	}
	else if ($selected_cat) 	// Into a specific category
	{
	    $sql.= ", ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_societe as cs";
	}
	$sql.= " WHERE p.rowid = pf.fk_paiement";
	$sql.= " AND pf.fk_facture = f.rowid";
	$sql.= " AND f.fk_soc = s.rowid";
	if ($date_start && $date_end) {
	    $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
	if ($selected_cat === -2)	// Without any category
	{
	    $sql.=" AND cs.fk_soc is null";
	}
	else if ($selected_cat) {	// Into a specific category
	    $sql.= " AND (c.rowid = ".$selected_cat;
	    if ($subcat) $sql.=" OR c.fk_parent = " . $selected_cat;
	    $sql.= ")";
		$sql.= " AND cs.fk_categorie = c.rowid AND cs.fk_soc = s.rowid";
	}
}
if (!empty($search_societe))  $sql.= natural_search('s.nom', $search_societe);
if (!empty($search_zip))      $sql.= natural_search('s.zip', $search_zip);
if (!empty($search_town))     $sql.= natural_search('s.town', $search_town);
if ($search_country > 0)      $sql.= ' AND s.fk_pays = '.$search_country.'';
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY s.rowid, s.nom, s.zip, s.town, s.fk_pays";
$sql.= " ORDER BY s.rowid";
//echo $sql;

dol_syslog("casoc", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i=0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
	        $amount_ht[$obj->socid] = $obj->amount;
	        $amount[$obj->socid] = $obj->amount_ttc;
	        $name[$obj->socid] = $obj->name.' '.$obj->firstname;
			$address_zip[$obj->socid] = $obj->zip;
			$address_town[$obj->socid] = $obj->town;
			$address_pays[$obj->socid] = getCountry($obj->fk_pays);
	        $catotal_ht+=$obj->amount;
	        $catotal+=$obj->amount_ttc;
	        $i++;

	}
} else {
	dol_print_error($db);
}

// On ajoute les paiements anciennes version, non lies par paiement_facture
if ($modecompta != 'CREANCES-DETTES') {
	$sql = "SELECT '0' as socid, 'Autres' as name, sum(p.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql.= " WHERE pf.rowid IS NULL";
	$sql.= " AND p.fk_bank = b.rowid";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity IN (".getEntity('bank_account').")";
	if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	$sql.= " GROUP BY socid, name";
	$sql.= " ORDER BY name";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i=0;
		while ($i < $num) {
			$obj = $db->fetch_object($result);
			$amount[$obj->rowid] += $obj->amount_ttc;
			$name[$obj->rowid] = $obj->name;
			$address_zip[$obj->rowid] = $obj->zip;
			$address_town[$obj->rowid] = $obj->town;
			$address_pays[$obj->rowid] = getCountry($obj->fk_pays);
			$catotal+=$obj->amount_ttc;
			$i++;
		}
	} else {
		dol_print_error($db);
	}
}


// Show array
$i = 0;
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
// Extra parameters management
foreach($headerparams as $key => $value)
{
     print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
}

$moreforfilter='';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Category filter
print '<tr class="liste_titre">';
print '<td>';
print $langs->trans("Category") . ': ' . $formother->select_categories(Categorie::TYPE_CUSTOMER, $selected_cat, 'search_categ', true);
print ' ';
print $langs->trans("SubCats") . '? ';
print '<input type="checkbox" name="subcat" value="yes"';
if ($subcat) {
    print ' checked';
}
print'></td>';
print '<td colspan="7" align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre" align="left">';
print '<input class="flat" size="6" type="text" name="search_societe" value="'.$search_societe.'">';
print '</td>';
print '<td class="liste_titre" align="left">';
print '<input class="flat" size="6" type="text" name="search_zip" value="'.$search_zip.'">';
print '</td>';
print '<td class="liste_titre" align="left">';
print '<input class="flat" size="6" type="text" name="search_town" value="'.$search_town.'">';
print '</td>';
print '<td class="liste_titre" align="left">';
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
	$sortfield,$sortorder
	);
print_liste_field_titre(
	$langs->trans("Zip"),
	$_SERVER["PHP_SELF"],
	"zip",
	"",
	$paramslink,
	"",
	$sortfield,$sortorder
	);
print_liste_field_titre(
	$langs->trans("Town"),
	$_SERVER["PHP_SELF"],
	"town",
	"",
	$paramslink,
	"",
	$sortfield,$sortorder
	);
print_liste_field_titre(
	$langs->trans("Country"),
	$_SERVER["PHP_SELF"],
	"country",
	"",
	$paramslink,
	"",
	$sortfield,$sortorder
	);
if ($modecompta == 'CREANCES-DETTES') {
    print_liste_field_titre(
           $langs->trans('AmountHT'),
           $_SERVER["PHP_SELF"],
           "amount_ht",
           "",
           $paramslink,
           'align="right"',
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
	'align="right"',
	$sortfield,
	$sortorder
	);
print_liste_field_titre(
	$langs->trans("Percentage"),
	$_SERVER["PHP_SELF"],
	"amount_ttc",
	"",
	$paramslink,
	'align="right"',
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
	$arrayforsort=$name;
	// Defining array arrayforsort
	if ($sortfield == 'nom' && $sortorder == 'asc') {
		asort($name);
		$arrayforsort=$name;
	}
	if ($sortfield == 'nom' && $sortorder == 'desc') {
		arsort($name);
		$arrayforsort=$name;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'asc') {
	    asort($amount_ht);
	    $arrayforsort=$amount_ht;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'desc') {
	    arsort($amount_ht);
	    $arrayforsort=$amount_ht;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'asc') {
		asort($amount);
		$arrayforsort=$amount;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'desc') {
		arsort($amount);
		$arrayforsort=$amount;
	}
	if ($sortfield == 'zip' && $sortorder == 'asc') {
		asort($address_zip);
		$arrayforsort=$address_zip;
	}
	if ($sortfield == 'zip' && $sortorder == 'desc') {
		arsort($address_zip);
		$arrayforsort=$address_zip;
	}
	if ($sortfield == 'town' && $sortorder == 'asc') {
		asort($address_town);
		$arrayforsort=$address_town;
	}
	if ($sortfield == 'town' && $sortorder == 'desc') {
		arsort($address_town);
		$arrayforsort=$address_town;
	}
	if ($sortfield == 'country' && $sortorder == 'asc') {
		asort($address_pays);
		$arrayforsort=$address_town;
	}
	if ($sortfield == 'country' && $sortorder == 'desc') {
		arsort($address_pays);
		$arrayforsort=$address_town;
	}

	foreach($arrayforsort as $key=>$value) {

		print '<tr class="oddeven">';

		// Third party
		$fullname=$name[$key];
		if ($key > 0) {
		    $thirdparty_static->id=$key;
		    $thirdparty_static->name=$fullname;
		    $thirdparty_static->client=1;
		    $linkname=$thirdparty_static->getNomUrl(1,'customer');
		} else {
			$linkname=$langs->trans("PaymentsNotLinkedToInvoice");
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
		print '<td align="right">';
		if ($modecompta != 'CREANCES-DETTES') {
                    if ($key > 0) {
			print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?socid='.$key.'">';
		    } else {
			print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?socid=-1">';
		    }
		} else {
		    if ($key > 0) {
			print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$key.'">';
		    } else {
			print '<a href="#">';
		    }
		print price($amount_ht[$key]);
		}
		print '</td>';

		// Amount with VAT
		print '<td align="right">';
		if ($modecompta != 'CREANCES-DETTES') {
                    if ($key > 0) {
                        print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?socid='.$key.'">';
		    } else {
			print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?orphelins=1">';
		    }
		} else {
                    if ($key > 0) {
                        print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$key.'">';
		    } else {
			print '<a href="#">';
		    }
		}
		print price($amount[$key]);
		print '</a>';
		print '</td>';

		// Percent;
		print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';

        // Other stats
        print '<td align="center">';
        if (! empty($conf->propal->enabled) && $key>0) {
	    print '&nbsp;<a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$key.'">'.img_picto($langs->trans("ProposalStats"),"stats").'</a>&nbsp;';
	}
        if (! empty($conf->commande->enabled) && $key>0) {
	    print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/stats/index.php?socid='.$key.'">'.img_picto($langs->trans("OrderStats"),"stats").'</a>&nbsp;';
	}
        if (! empty($conf->facture->enabled) && $key>0) {
	    print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$key.'">'.img_picto($langs->trans("InvoiceStats"),"stats").'</a>&nbsp;';
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
	    print '<td colspan="1"></td>';
	} else {
	    print '<td align="right">'.price($catotal_ht).'</td>';
	}
	print '<td align="right">'.price($catotal).'</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	$db->free($result);
}

print "</table>";
print "</div>";

print '</form>';

llxFooter();

$db->close();
