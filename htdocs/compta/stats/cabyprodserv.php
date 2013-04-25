<?php
/* Copyright (C) 2013      Antoine Iauch        <aiauch@gpcsolutions.fr>
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
 *       \file        htdocs/compta/stats/cabyprodserv.php
 *       \brief       Page reporting TO by Products & Services
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("products");
$langs->load("categories");
$langs->load("errors");

// Security pack (data & check)
$socid = GETPOST('socid','int');

if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->global->COMPTA_MODE;
if (GETPOST("modecompta")) $modecompta=GETPOST("modecompta");

$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="name";

// Category
$selected_cat = (int) GETPOST('search_categ', 'int');
$subcat = false;
if (GETPOST('subcat', 'alpha') === 'yes') {
    $subcat = true;
}

// Date range
$year=GETPOST("year");
$month=GETPOST("month");
$date_startyear = GETPOST("date_startyear");
$date_startmonth = GETPOST("date_startmonth");
$date_startday = GETPOST("date_startday");
$date_endyear = GETPOST("date_endyear");
$date_endmonth = GETPOST("date_endmonth");
$date_endday = GETPOST("date_endday");
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
$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q")?GETPOST("q"):0;
	if ($q==0)
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
} else {
	// TODO We define q
}

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
$formother = new FormOther($db);

// Show report header
$nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByProductsAndServices");

if ($modecompta=="CREANCES-DETTES") {
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';

    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);

    $description=$langs->trans("RulesCADue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	    $description.= $langs->trans("DepositsAreNotIncluded");
	} else {
	    $description.= $langs->trans("DepositsAreIncluded");
	}

    $builddate=time();
} else {
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';

    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);

    $description=$langs->trans("RulesCAIn");
    $description.= $langs->trans("DepositsAreIncluded");

    $builddate=time();
}

report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink,$tableparams);


// SQL request
$catotal=0;

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT DISTINCT p.rowid as rowid, p.ref as ref, p.label as label,";
    $sql.= " sum(DISTINCT l.total_ht) as amount, sum(DISTINCT l.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
    $sql.= " JOIN ".MAIN_DB_PREFIX."facturedet as l";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON l.fk_facture = f.rowid";
    if ($selected_cat === -2) {
	$sql.=" LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
    }
    if ($selected_cat && $selected_cat !== -2) {
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = " . $selected_cat;
	if ($subcat) {
	    $sql.=" OR c.fk_parent = " . $selected_cat;
	}
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_categorie = c.rowid";
    }
    $sql.= " WHERE l.fk_product = p.rowid";
    $sql.= " AND f.fk_statut in (1,2)";
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$sql.= " AND f.type IN (0,1,2)";
    } else {
	$sql.= " AND f.type IN (0,1,2,3)";
    }
    if ($date_start && $date_end) {
	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    }
    if ($selected_cat === -2) {
	$sql.=" AND cp.fk_product is null";
    }
    if ($selected_cat && $selected_cat !== -2) {
	$sql.= " AND cp.fk_product = p.rowid";
    }
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY p.rowid ";
    $sql.= "ORDER BY p.ref ";

    $result = $db->query($sql);
    if ($result) {
	$num = $db->num_rows($result);
	$i=0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		$amount_ht[$obj->rowid] = $obj->amount;
		$amount[$obj->rowid] = $obj->amount_ttc;
		$name[$obj->rowid] = $obj->ref . '&nbsp;-&nbsp;' . $obj->label;
		$catotal_ht+=$obj->amount;
		$catotal+=$obj->amount_ttc;
		$i++;
	}
    } else {
	dol_print_error($db);
    }

    // Show Array
    $i=0;
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    // Extra parameters management
    foreach($headerparams as $key => $value)
    {
	 print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
    }

    print '<table class="noborder" width="100%">';
    // Category filter
    print '<tr class="liste_titre">';
    print '<td>';
    print $langs->trans("Category") . ': ' . $formother->select_categories(0, $selected_cat, 'search_categ', true);
    print ' ';
    print $langs->trans("SubCats") . '? ';
    print '<input type="checkbox" name="subcat" value="yes"';
    if ($subcat) {
	print ' checked';
    }
    print '></td>';
    print '<td colspan="3" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '</td></tr>';
	    // Array header
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre(
	    $langs->trans("Product"),
	    $_SERVER["PHP_SELF"],
	    "name",
	    "",
	    $paramslink,
	    "",
	    $sortfield,
	    $sortorder
	    );
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
    // TODO: statistics?
    print "</tr>\n";

    // Array Data
    $var=true;

    if (count($amount)) {
	    $arrayforsort=$name;
	    // defining arrayforsort
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
	    foreach($arrayforsort as $key=>$value) {
		    $var=!$var;
		    print "<tr ".$bc[$var].">";

		    // Third party
		     $fullname=$name[$key];
		    if ($key >= 0) {
			$linkname='<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$key.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$fullname.'</a>';
		    } else {
			$linkname=$langs->trans("PaymentsNotLinkedToProduct");
		    }

		print "<td>".$linkname."</td>\n";

		// Amount w/o VAT
		print '<td align="right">';
		if ($key > 0) {
		    print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?productid='.$key.'">';
		} else {
		    print '<a href="#">';
		}
		print price($amount_ht[$key]);
		print '</td>';

		// Amount with VAT
		print '<td align="right">';
		if ($key > 0) {
		    print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?productid='.$key.'">';
		} else {
		    print '<a href="#">';
		}
		print price($amount[$key]);
		print '</a>';
		print '</td>';

		// Percent;
		print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';

		// TODO: statistics?

		print "</tr>\n";
		$i++;
	    }

	    // Total
	    print '<tr class="liste_total">';
	    print '<td>'.$langs->trans("Total").'</td>';
	    print '<td align="right">'.price($catotal_ht).'</td>';
	    print '<td align="right">'.price($catotal).'</td>';
	    print '<td>&nbsp;</td>';
	    print '</tr>';

	    $db->free($result);
    }
    print "</table>";
    print '</form>';
} else {
    // $modecompta != 'CREANCES-DETTES'
    // TODO: better message
    print '<div class="warning">' . $langs->trans("WarningNotRelevant") . '</div>';
}

llxFooter();
$db->close();
?>
