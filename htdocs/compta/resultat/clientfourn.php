<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*
 *
 */
$user->getrights('compta');
if (!$user->rights->compta->resultat->lire)
  accessforbidden();

llxHeader();

$year=$_GET["year"];
$month=$_GET["month"];
if (! $year) { $year = strftime("%Y", time()); }


print_fiche_titre("Détail recettes-dépenses par client/fournisseur",($year?"&nbsp; <a href='clientfourn.php?year=".($year-1)."'>".img_previous()."</a> Année $year <a href='clientfourn.php?year=".($year+1)."'>".img_next()."</a>":""));

print '<br>';

print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<tr class="liste_titre">';
print '<td width="10%">&nbsp;</td><td>Elément</td>';
print "<td align=\"right\">Montant HT</td>";
print "</tr>\n";


/*
 * Factures clients
 *
 */

$sql = "SELECT s.nom,s.idp,sum(f.total) as amount";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
if ($year) {
	$sql .= " AND f.datef between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
}
$sql .= " GROUP BY s.nom ASC";


print '<tr><td colspan="4">Facturation clients</td></tr>';

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
    
  $i = 0;
  
  if ($num > 0) {
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
            
      print "<tr $bc[$var]><td>&nbsp</td>";
      print "<td>Factures  <a href=\"../facture.php?socidp=$objp->idp\">$objp->nom</td>\n";
      
      print "<td align=\"right\">".price($objp->amount)."</td>\n";
      
      $total = $total + $objp->amount;
      print "</tr>\n";
      $i++;
    }
  }
  $db->free();
} else {
  print $db->error();
}
print '<tr><td colspan="3" align="right">'.price($total).'</td></tr>';

/*
 * Frais, factures fournisseurs.
 *
 */

$sql = "SELECT s.nom,s.idp,sum(f.total_ht) as amount";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f WHERE f.fk_soc = s.idp"; 
if ($year) {
	$sql .= " AND f.datef between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
}
$sql .= " GROUP BY s.nom ASC, s.idp";

print '<tr><td colspan="4">Facturation fournisseurs</td></tr>';
$subtotal = 0;
$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  $i = 0;
  
  if ($num > 0) {
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
            
      print "<tr $bc[$var]><td>&nbsp</td>";
      print "<td>Factures <a href=\"../../fourn/facture/index.php?socid=".$objp->idp."\">$objp->nom</a></td>\n";
      
      print "<td align=\"right\">".price($objp->amount)."</td>\n";
      
      $total = $total - $objp->amount;
      $subtotal = $subtotal + $objp->amount;
      print "</tr>\n";
      $i++;
    }
  }
  $db->free();
} else {
  print $db->error();
}
print '<tr><td colspan="3" align="right">'.price($subtotal).'</td></tr>';

/*
 * Charges sociales
 *
 */

$subtotal = 0;
print '<tr><td colspan="4">Prestations/Charges déductibles</td></tr>';

$sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql .= " WHERE s.fk_type = c.id AND c.deductible=1";
if ($year) {
	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
}
$sql .= " GROUP BY c.libelle DESC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);

    $total = $total - $obj->amount;
    $subtotal = $subtotal + $obj->amount;

    $var = !$var;
    print "<tr $bc[$var]><td>&nbsp</td>";
    print '<td>'.$obj->nom.'</td>';
    print '<td align="right">'.price($obj->amount).'</td>';
    print '</tr>';
    $i++;
  }
} else {
  print $db->error();
}
print '<tr><td colspan="3" align="right">'.price($subtotal).'</td></tr>';

print '<tr><td align="right" colspan="2">Résultat</td><td class="border" align="right">'.price($total).'</td></tr>';

/*
 * Charges sociales non déductibles
 *
 */

$subtotal = 0;
print '<tr><td colspan="4">Prestations/Charges NON déductibles</td></tr>';

$sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql .= " WHERE s.fk_type = c.id AND c.deductible=0";
if ($year) {
	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
}
$sql .= " GROUP BY c.libelle DESC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);

    $total = $total - $obj->amount;
    $subtotal = $subtotal + $obj->amount;

    $var = !$var;
    print "<tr $bc[$var]><td>&nbsp</td>";
    print '<td>'.$obj->nom.'</td>';
    print '<td align="right">'.price($obj->amount).'</td>';
    print '</tr>';
    $i++;
  }
} else {
  print $db->error();
}
print '<tr><td colspan="3" align="right">'.price($subtotal).'</td></tr>';

print '<tr><td align="right" colspan="2">Résultat</td><td class="border" align="right">'.price($total).'</td></tr>';


print "</table>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
