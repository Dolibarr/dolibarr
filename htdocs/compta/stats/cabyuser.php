<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file        htdocs/compta/stats/cabyuser.php
        \brief       Page reporting CA par utilisateur
        \version     $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.inc.php");


$year=$_GET["year"];
if (! $year) { $year = strftime("%Y", time()); }
// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];

$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="name";

// Sécurité accés client
if ($user->societe_id > 0) $socid = $user->societe_id;


llxHeader();


$html=new Form($db);

// Affiche en-tête du rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByUserAuthorOfInvoice");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$langs->trans("Year")." $year";
    $periodlink="<a href='".$_SERVER["PHP_SELF"]."?year=".($year-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year+1)."&modecompta=".$modecompta."'>".img_next()."</a>";
    $description=$langs->trans("RulesCADue");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByUserAuthorOfInvoice");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period=$langs->trans("Year")." $year";
    $periodlink="<a href='".$_SERVER["PHP_SELF"]."?year=".($year-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year+1)."&modecompta=".$modecompta."'>".img_next()."</a>";
    $description=$langs->trans("RulesCAIn");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// Charge tableau
$catotal=0;
if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT u.rowid as rowid, u.name as name, u.firstname as firstname, sum(f.total) as amount, sum(f.total_ttc) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u,".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_statut in (1,2) AND f.fk_user_author = u.rowid";
    if ($year) $sql .= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
}
else
{
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'étaient pas liés via paiement_facture. On les ajoute plus loin)
     */
	$sql = "SELECT u.rowid as rowid, u.name as name, u.firstname as firstname, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " WHERE p.rowid = pf.fk_paiement AND pf.fk_facture = f.rowid AND f.fk_user_author = u.rowid";
    if ($year) $sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
}
if ($socid) $sql .= " AND f.fk_soc = $socid";
$sql .= " GROUP BY rowid";
$sql .= " ORDER BY rowid";

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
    dolibarr_print_error($db);   
}

// On ajoute les paiements anciennes version, non liés par paiement_facture
if ($modecompta != 'CREANCES-DETTES')
{
    $sql = "SELECT -1 as rowid, '' as name, '' as firstname, sum(p.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql .= " WHERE pf.rowid IS NULL";
    if ($year) $sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    $sql .= " GROUP BY rowid";
    $sql .= " ORDER BY rowid";

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
        dolibarr_print_error($db);   
    }
}


$i = 0;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("User"),$_SERVER["PHP_SELF"],"name","",'&amp;year='.($year).'&modecompta='.$modecompta,"",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"amount_ttc","",'&amp;year='.($year).'&modecompta='.$modecompta,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Percentage"),$_SERVER["PHP_SELF"],"amount_ttc","",'&amp;year='.($year).'&modecompta='.$modecompta,'align="right"',$sortfield,$sortorder);
print "</tr>\n";
$var=true;

if (sizeof($amount))
{
    $arrayforsort=$name;
    
    // On définit tableau arrayforsort
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

    foreach($arrayforsort as $key=>$value)
    {
        $var=!$var;
        print "<tr $bc[$var]>";

        $fullname=$name[$key];
        if ($key >= 0) {
            $linkname='<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$key.'">'.img_object($langs->trans("ShowUser"),'user').' '.$fullname.'</a>';
        }
        else {
            $linkname=$langs->trans("Paiements liés à aucune facture");
        }
        print "<td>".$linkname."</td>\n";
        print '<td align="right">'.price($amount[$key]).'</td>';
        print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal,2).'%' : '&nbsp;').'</td>';
        print "</tr>\n";
        $i++;
    }

    // Total
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.price($catotal).'</td><td>&nbsp;</td></tr>';

    $db->free($result);
}

print "</table>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
