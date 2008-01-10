<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
        \file        htdocs/compta/stats/casoc.php
        \brief       Page reporting CA par société
        \version     $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.inc.php");

$langs->load("companies");

$year=$_GET["year"];
if (! $year) { $year = strftime("%Y", time()); }
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];

$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="nom";

// Sécurité accés client
if ($user->societe_id > 0) $socid = $user->societe_id;


llxHeader();

$html=new Form($db);

// Affiche en-tête de rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByThirdParties");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$langs->trans("Year")." ".$year;
    $periodlink='<a href='.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesCADue");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByThirdParties");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period=$langs->trans("Year")." ".$year;
    $periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesCAIn");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// Charge tableau
$catotal=0;
if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT s.rowid as socid, s.nom as name, sum(f.total) as amount, sum(f.total_ttc) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_statut in (1,2) AND f.fk_soc = s.rowid";
    if ($year) $sql .= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
}
else
{
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'étaient pas liés via paiement_facture. On les ajoute plus loin)
     */
	$sql = "SELECT s.rowid as socid, s.nom as name, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " WHERE p.rowid = pf.fk_paiement AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid";
    if ($year) $sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
}
if ($socid) $sql .= " AND f.fk_soc = ".$socid;
$sql .= " GROUP BY s.rowid";
$sql .= " ORDER BY s.rowid";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i=0;
    while ($i < $num)
    {
         $obj = $db->fetch_object($result);
         $amount[$obj->socid] += $obj->amount_ttc;
         $name[$obj->socid] = $obj->name;
         $catotal+=$obj->amount_ttc;
         $i++;
    }
}
else {
    dolibarr_print_error($db);   
}

// On ajoute les paiements anciennes version, non liés par paiement_facture
if ($modecompta != 'CREANCES-DETTES')
{
    $sql = "SELECT 'Autres' as nom, '0' as idp, sum(p.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql .= " WHERE pf.rowid IS NULL";
    if ($year) $sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    $sql .= " GROUP BY nom";
    $sql .= " ORDER BY nom";

    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i=0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $amount[$obj->rowid] += $obj->amount_ttc;
            $name[$obj->rowid] = $obj->name;
            $catotal+=$obj->amount_ttc;
            $i++;
        }
    }
    else {
        dolibarr_print_error($db);   
    }
}


$i = 0;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"nom","",'&amp;year='.($year).'&modecompta='.$modecompta,"",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"amount_ttc","",'&amp;year='.($year).'&modecompta='.$modecompta,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Percentage"),$_SERVER["PHP_SELF"],"amount_ttc","",'&amp;year='.($year).'&modecompta='.$modecompta,'align="right"',$sortfield,$sortorder);
print "</tr>\n";
$var=true;

if (sizeof($amount))
{
    $arrayforsort=$name;
    
    // On définit tableau arrayforsort
    if ($sortfield == 'nom' && $sortorder == 'asc') {
        asort($name);
        $arrayforsort=$name;
    }
    if ($sortfield == 'nom' && $sortorder == 'desc') {
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

    foreach($arrayforsort as $key=>$value)
    {
        $var=!$var;
        print "<tr $bc[$var]>";

        $fullname=$name[$key];
        if ($key > 0) {
            $linkname='<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$key.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$fullname.'</a>';
        }
        else {
            $linkname=$langs->trans("Autres paiements liés à aucune facture, donc aucune société");
        }
        print "<td>".$linkname."</td>\n";
        print '<td align="right">'.price($amount[$key]).'</td>';
        print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';
        print "</tr>\n";
        $i++;
    }

    // Total
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.price($catotal).'</td><td>&nbsp;</td></tr>';

    $db->free($result);
}

print "</table>";
print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
