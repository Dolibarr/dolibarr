<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file        htdocs/compta/stats/cabyuser.php
 *      \brief       Page reporting Salesover by user
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/tax.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

// Security check
$socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->compta->resultat->lire && !$user->rights->accounting->comptarapport->lire)
accessforbidden();

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];

$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="name";

// Date range
$year=GETPOST("year");
$month=GETPOST("month");
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


/*
 * View
 */

llxHeader();


$html=new Form($db);

// Affiche en-tete du rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByUserAuthorOfInvoice");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period=$html->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink="<a href='".$_SERVER["PHP_SELF"]."?year=".($year-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year+1)."&modecompta=".$modecompta."'>".img_next()."</a>";
    $description=$langs->trans("RulesCADue");
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByUserAuthorOfInvoice");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';
	$period=$html->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink="<a href='".$_SERVER["PHP_SELF"]."?year=".($year-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year+1)."&modecompta=".$modecompta."'>".img_next()."</a>";
    $description=$langs->trans("RulesCAIn");
    $builddate=time();
    //$exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// Charge tableau
$catotal=0;
if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT u.rowid as rowid, u.name as name, u.firstname as firstname, sum(f.total) as amount, sum(f.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.fk_user_author = u.rowid";
    $sql.= " WHERE f.fk_statut in (1,2) ";
    $sql.= " AND (";
    $sql.= " f.type = 0";          // Standard
    $sql.= " OR f.type = 1";       // Replacement
    $sql.= " OR f.type = 2";       // Credit note
    //$sql.= " OR f.type = 3";       // We do not include deposit
    $sql.= ")";
	if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
else
{
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
     */
	$sql = "SELECT u.rowid as rowid, u.name as name, u.firstname as firstname, sum(pf.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u" ;
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.fk_user_author = u.rowid ";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_facture = f.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.rowid = pf.fk_paiement";
	$sql.= " WHERE 1=1";
	if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql .= " GROUP BY u.rowid, u.name, u.firstname";
$sql .= " ORDER BY u.rowid";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i=0;
    while ($i < $num)
    {
         $obj = $db->fetch_object($result);
         $amount[$obj->rowid] = $obj->amount_ttc;
         $name[$obj->rowid] = $obj->name.' '.$obj->firstname;
         $catotal+=$obj->amount_ttc;
         $i++;
    }
}
else {
    dol_print_error($db);
}

// On ajoute les paiements ancienne version, non lies par paiement_facture donc sans user
if ($modecompta != 'CREANCES-DETTES')
{
    $sql = "SELECT -1 as rowidx, '' as name, '' as firstname, sum(p.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
    $sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE pf.rowid IS NULL";
    $sql.= " AND p.fk_bank = b.rowid";
    $sql.= " AND b.fk_account = ba.rowid";
    $sql.= " AND ba.entity = ".$conf->entity;
	if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
    $sql.= " GROUP BY rowidx, name, firstname";
    $sql.= " ORDER BY rowidx";

    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i=0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $amount[$obj->rowidx] = $obj->amount_ttc;
            $name[$obj->rowidx] = $obj->name.' '.$obj->firstname;
            $catotal+=$obj->amount_ttc;
            $i++;
        }
    }
    else {
        dol_print_error($db);
    }
}


$i = 0;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("User"),$_SERVER["PHP_SELF"],"name","",'&amp;year='.($year).'&modecompta='.$modecompta,"",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"amount_ttc","",'&amp;year='.($year).'&modecompta='.$modecompta,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Percentage"),$_SERVER["PHP_SELF"],"amount_ttc","",'&amp;year='.($year).'&modecompta='.$modecompta,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("OtherStatistics"),$_SERVER["PHP_SELF"],"","","",'align="center" width="20%"');
print "</tr>\n";
$var=true;

if (count($amount))
{
    $arrayforsort=$name;

    // We define arrayforsort
    if ($sortfield == 'name' && $sortorder == 'asc') {
        asort($name);
        $arrayforsort=$name;
    }
    if ($sortfield == 'name' && $sortorder == 'desc') {
        arsort($name);
        $arrayforsort=$name;
    }
    if ($sortfield == 'amount_ttc' && $sortorder == 'asc') {
        asort($amount);
        $arrayforsort=$amount;
    }
    if ($sortfield == 'amount_ttc' && $sortorder == 'desc') {
        arsort($amount);
        $arrayforsort=$amount;
    }

    foreach($arrayforsort as $key => $value)
    {
        $var=!$var;
        print "<tr ".$bc[$var].">";

        // Third party
        $fullname=$name[$key];
        if ($key >= 0) {
            $linkname='<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$key.'">'.img_object($langs->trans("ShowUser"),'user').' '.$fullname.'</a>';
        }
        else {
            $linkname=$langs->trans("PaymentsNotLinkedToUser");
        }
        print "<td>".$linkname."</td>\n";

        // Amount
        print '<td align="right">';
        if ($modecompta != 'CREANCES-DETTES')
        {
            if ($key > 0) print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?userid='.$key.'">';
            else print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?userid=-1">';
        }
        else
        {
            if ($key > 0) print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?userid='.$key.'">';
            else print '<a href="#">';
        }
        print price($amount[$key]);
        print '</td>';

        // Percent
        print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal,2).'%' : '&nbsp;').'</td>';

        // Other stats
        print '<td align="center">';
        if ($conf->propal->enabled && $key>0) print '&nbsp;<a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?userid='.$key.'">'.img_picto($langs->trans("ProposalStats"),"stats").'</a>&nbsp;';
        if ($conf->commande->enabled && $key>0) print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/stats/index.php?userid='.$key.'">'.img_picto($langs->trans("OrderStats"),"stats").'</a>&nbsp;';
        if ($conf->facture->enabled && $key>0) print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?userid='.$key.'">'.img_picto($langs->trans("InvoiceStats"),"stats").'</a>&nbsp;';
        print '</td>';

        print "</tr>\n";
        $i++;
    }

    // Total
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.price($catotal).'</td><td>&nbsp;</td>';
    print '<td>&nbsp;</td>';
    print '</tr>';

    $db->free($result);
}

print "</table>";

$db->close();

llxFooter();
?>
