<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013      Antoine Iauch         <aiauch@gpcsolutions.fr>
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
 *       \brief       Page reporting CA par societe
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("companies");
$langs->load("categories");

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->global->COMPTA_MODE;
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
}
else
{
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
$thirdparty_static=new Societe($db);
$formother = new FormOther($db);

// Show report header
if ($modecompta=="CREANCES-DETTES")
{
	$nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByThirdParties");
	$nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description=$langs->trans("RulesCADue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
	$builddate=time();
	//$exportlink=$langs->trans("NotYetAvailable");
} else {
	$nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByThirdParties");
	$nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description=$langs->trans("RulesCAIn");
	$description.= $langs->trans("DepositsAreIncluded");
	$builddate=time();
	//$exportlink=$langs->trans("NotYetAvailable");
}

report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink,$tableparams);


// Show Array
$catotal=0;
if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT DISTINCT s.rowid as socid, s.nom as name,";
	$sql.= " sum(DISTINCT f.total) as amount, sum(DISTINCT f.total_ttc) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= " JOIN ".MAIN_DB_PREFIX."facture as f";
	if ($selected_cat === -2) {
	    $sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_societe";
	}
	if ($selected_cat && $selected_cat !== -2) {
	    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = ".$selected_cat;
	    if ($subcat) {
		$sql.=" OR c.fk_parent = " . $selected_cat;
	    }
	     $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON cs.fk_categorie = c.rowid";
	}
	$sql.= " WHERE f.fk_statut in (1,2)";
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	    $sql.= " AND f.type IN (0,1,2)";
	} else {
	    $sql.= " AND f.type IN (0,1,2,3)";
	}
	$sql.= " AND f.fk_soc = s.rowid";
	if ($date_start && $date_end) {
	    $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
	}
	if ($selected_cat === -2) {
	    $sql.=" AND cs.fk_societe is null";
	}
	if ($selected_cat && $selected_cat !== -2) {
	    $sql.= " AND cs.fk_societe = s.rowid";
	}
    } else {
	/*
	 * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
	 * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
	 */
	$sql = "SELECT s.rowid as socid, s.nom as name, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."facture as f";
	$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " WHERE p.rowid = pf.fk_paiement";
	$sql.= " AND pf.fk_facture = f.rowid";
	$sql.= " AND f.fk_soc = s.rowid";
	if ($date_start && $date_end) {
	    $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY s.rowid, s.nom";
$sql.= " ORDER BY s.rowid";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i=0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
	        $amount_ht[$obj->socid] = $obj->amount;
	        $amount[$obj->socid] = $obj->amount_ttc;
	        $name[$obj->socid] = $obj->name.' '.$obj->firstname;
	        $catotal_ht+=$obj->amount;
	        $catotal+=$obj->amount_ttc;
	        $i++;

	}
} else {
	dol_print_error($db);
}

// On ajoute les paiements anciennes version, non lies par paiement_facture
if ($modecompta != 'CREANCES-DETTES') {
	$sql = "SELECT '0' as socid, 'Autres' as name, sum(DISTINCT p.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql.= " WHERE pf.rowid IS NULL";
	$sql.= " AND p.fk_bank = b.rowid";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
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
print "<table class=\"noborder\" width=\"100%\">";
    // Category filter
print '<tr class="liste_titre">';
print '<td>';
print $langs->trans("Category") . ': ' . $formother->select_categories(2, $selected_cat, 'search_categ', true);
print ' ';
print $langs->trans("SubCats") . '? ';
print '<input type="checkbox" name="subcat" value="yes"';
if ($subcat) {
    print ' checked="checked"';
}
print'></td>';
print '<td colspan="4" align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '</td>';
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
	print '<td colspan="1"></td>';
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
$var=true;

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

	foreach($arrayforsort as $key=>$value) {
		$var=!$var;
		print "<tr ".$bc[$var].">";

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

		// Amount w/o VAT
		print '<td align="right">';
		if ($modecompta != 'CREANCES-DETTES') {
                    if ($key > 0) {
			print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?userid='.$key.'">';
		    } else {
			print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?userid=-1">';
		    }
		} else {
		    if ($key > 0) {
			print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?userid='.$key.'">';
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
                        print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?socid='.$key.'">';
		    } else {
			print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?orphelins=1">';
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
print '</form>';

llxFooter();

$db->close();
?>