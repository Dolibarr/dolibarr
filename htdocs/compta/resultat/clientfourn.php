<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 *
 */
require("./pre.inc.php");
require("../../tva.class.php");
require("../../chargesociales.class.php");

$langs->load("bills");

$user->getrights('compta');
if (!$user->rights->compta->resultat->lire)
  accessforbidden();


llxHeader();

$year=$_GET["year"];
if (! $year) { $year = strftime("%Y", time()); }
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];


print_fiche_titre("Résultat exercice, par client/fournisseur",($year?"&nbsp; <a href='clientfourn.php?year=".($year-1)."'>".img_previous()."</a> Année $year <a href='clientfourn.php?year=".($year+1)."'>".img_next()."</a>":""));
print '<br>';

print "Cet état permet de faire un bilan des recettes et dépenses:<br>";
if ($modecompta=="CREANCES-DETTES")
{
    print $langs->trans("RulesResultDue");
    print '(Voir le rapport en <a href="clientfourn.php?year='.$year.'&modecompta=RECETTES-DEPENSES">recettes-dépenses</a> pour n\'inclure que les factures effectivement payées).<br>';
    print '<br>';
}
else {
    print $langs->trans("RulesResultInOut");
    print '(Voir le rapport en <a href="clientfourn.php?year='.$year.'&modecompta=CREANCES-DETTES">créances-dettes</a> pour inclure les factures non encore payée).<br>';
    print '<br>';
}


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="10%">&nbsp;</td><td>'.$langs->trans("Element").'</td>';
if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".$langs->trans("AmountHT")."</td>";
print "<td align=\"right\">".$langs->trans("AmountTTC")."</td>";
print "</tr>\n";


/*
 * Factures clients
 */
if ($modecompta == 'CREANCES-DETTES') { 
    $sql = "SELECT s.nom, s.idp, sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
   if ($year) {
    	$sql .= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
} else {
    /*
     * Liste des paiements par société (les anciens paiements ne sont pas inclus
     * car n'était pas liés sur les vieilles versions)
     */
	$sql = "SELECT s.nom as nom, s.idp as idp, sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " WHERE f.fk_soc = s.idp AND f.rowid = pf.fk_facture AND pf.fk_paiement = p.rowid";
    if ($year) {
    	$sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
}    
$sql .= " GROUP BY nom";
$sql .= " ORDER BY nom";

print '<tr><td colspan="4">Facturation clients</td></tr>';

$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $i = 0;
    $var=true;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $var=!$var;
            
        print "<tr $bc[$var]><td>&nbsp</td>";
        print "<td>".$langs->trans("Bills")." <a href=\"../facture.php?socidp=$objp->idp\">$objp->nom</td>\n";
        
        if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
        print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";
        
        $total_ht = $total_ht + $objp->amount_ht;
        $total_ttc = $total_ttc + $objp->amount_ttc;
        print "</tr>\n";
        $i++;
    }
    $db->free($result);
} else {
    dolibarr_print_error($db);
}
// Ajoute paiements anciennes version non liés par paiement_facture
if ($modecompta != 'CREANCES-DETTES') { 
    $sql = "SELECT 'Autres' as nom, '0' as idp, sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql .= " WHERE pf.rowid IS NULL";
    if ($year) {
    	$sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql .= " GROUP BY nom";
    $sql .= " ORDER BY nom";

    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        $var=true;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
                
            print "<tr $bc[$var]><td>&nbsp</td>";
            print "<td>".$langs->trans("Bills")." ".$langs->trans("Other")."\n";
            
            if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
            print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";
            
            $total_ht = $total_ht + $objp->amount_ht;
            $total_ttc = $total_ttc + $objp->amount_ttc;
            print "</tr>\n";
            $i++;
        }
        $db->free($result);
    } else {
        dolibarr_print_error($db);
    }
}
print '<tr>';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($total_ht).'</td>';
print '<td colspan="3" align="right">'.price($total_ttc).'</td>';
print '</tr>';

/*
 * Frais, factures fournisseurs.
 */
if ($modecompta == 'CREANCES-DETTES') { 
    $sql = "SELECT s.nom, s.idp, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f";
    $sql .= " WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
   if ($year) {
    	$sql .= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
} else {
	$sql = "SELECT s.nom, s.idp, sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= " ON f.rowid = p.fk_facture_fourn";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
	$sql .= " ON f.fk_soc = s.idp";
    $sql .= " WHERE 1=1";
    if ($year) {
    	$sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
}
$sql .= " GROUP BY nom, idp";
$sql .= " ORDER BY nom, idp";

print '<tr><td colspan="4">Facturation fournisseurs</td></tr>';
$subtotal_ht = 0;
$subtotal_ttc = 0;
$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows($result);
  $i = 0;
  if ($num > 0) {
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object($result);
      $var=!$var;
            
      print "<tr $bc[$var]><td>&nbsp</td>";
      print "<td>".$langs->trans("Bills")." <a href=\"../../fourn/facture/index.php?socid=".$objp->idp."\">$objp->nom</a></td>\n";
      
      if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
      print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";
      
      $total_ht = $total_ht - $objp->amount_ht;
      $total_ttc = $total_ttc - $objp->amount_ttc;
      $subtotal_ht = $subtotal_ht + $objp->amount_ht;
      $subtotal_ttc = $subtotal_ttc + $objp->amount_ttc;
      print "</tr>\n";
      $i++;
    }
  }
  $db->free($result);
} else {
  dolibarr_print_error($db);
}
print '<tr>';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price($subtotal_ttc).'</td>';
print '</tr>';


/*
 * Charges sociales non déductibles
 */

print '<tr><td colspan="4">Prestations/Charges NON déductibles</td></tr>';

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
    $sql .= " WHERE s.fk_type = c.id AND c.deductible=0";
    if ($year) {
    	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle";
}
else {
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s, ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid AND s.fk_type = c.id AND c.deductible=0";
    if ($year) {
    	$sql .= " AND p.datep between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle";
}
$result=$db->query($sql);
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($result) {
    $num = $db->num_rows($result);
    $var=false;
    $i = 0;
    if ($num) {    
      while ($i < $num) {
        $obj = $db->fetch_object($result);
    
        $total_ht = $total_ht - $obj->amount;
        $total_ttc = $total_ttc - $obj->amount;
        $subtotal_ht = $subtotal_ht + $obj->amount;
        $subtotal_ttc = $subtotal_ttc + $obj->amount;
    
        $var = !$var;
        print "<tr $bc[$var]><td>&nbsp</td>";
        print '<td>'.$obj->nom.'</td>';
        if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price($obj->amount).'</td>';
        print '<td align="right">'.price($obj->amount).'</td>';
        print '</tr>';
        $i++;
      }
    }
    else {
        print "<tr $bc[$var]><td>&nbsp</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
  dolibarr_print_error($db);
}
print '<tr>';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price($subtotal_ttc).'</td>';
print '</tr>';


/*
 * Charges sociales déductibles
 */

print '<tr><td colspan="4">Prestations/Charges déductibles</td></tr>';

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
    $sql .= " WHERE s.fk_type = c.id AND c.deductible=1";
    if ($year) {
    	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle DESC";
}
else {
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s, ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid AND s.fk_type = c.id AND c.deductible=1";
    if ($year) {
    	$sql .= " AND p.datep between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle";
}
$result=$db->query($sql);
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($result) {
    $num = $db->num_rows($result);
    $var=false;
    $i = 0;
    if ($num) {
        while ($i < $num) {
        $obj = $db->fetch_object($result);
        
        $total_ht = $total_ht - $obj->amount;
        $total_ttc = $total_ttc - $obj->amount;
        $subtotal_ht = $subtotal_ht + $obj->amount;
        $subtotal_ttc = $subtotal_ttc + $obj->amount;
        
        $var = !$var;
        print "<tr $bc[$var]><td>&nbsp</td>";
        print '<td>'.$obj->nom.'</td>';
        if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price($obj->amount).'</td>';
        print '<td align="right">'.price($obj->amount).'</td>';
        print '</tr>';
        $i++;
        }
    }
    else {
        print "<tr $bc[$var]><td>&nbsp</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
  dolibarr_print_error($db);
}
print '<tr>';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price($subtotal_ttc).'</td>';
print '</tr>';


// Total

print "<tr>";
print '<td colspan="4">&nbsp;</td>';
print '</tr>';

print '<tr><td align="right" colspan="2">Résultat</td>';
if ($modecompta == 'CREANCES-DETTES') print '<td class="border" align="right">'.price($total_ht).'</td>';
print '<td class="border" align="right">'.price($total_ttc).'</td>';
print '</tr>';


print "</table>";
print '<br>';


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
