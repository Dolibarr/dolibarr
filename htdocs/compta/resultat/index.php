<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
if (!$user->rights->compta->resultat)
  accessforbidden();

llxHeader();

print_titre("Résultat");

print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<TR class="liste_titre">';
print '<td width="10%">&nbsp;</td><TD>Elément</TD>';
print "<TD align=\"right\">Montant</td>";
print "</tr>\n";

$sql = "SELECT s.nom,s.idp,sum(f.amount) as amount";
$sql .= " FROM llx_societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.fk_statut = 1 AND f.fk_user_valid is not NULL"; 

$sql .= " GROUP BY s.nom ASC";

print '<tr><td colspan="4">Factures</td></tr>';

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
    
  $i = 0;
  
  if ($num > 0) {
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
            
      print "<TR $bc[$var]><td>&nbsp</td>";
      print "<td>Factures  <a href=\"../facture.php?socidp=$objp->idp\">$objp->nom</TD>\n";
      
      print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
      
      $total = $total + $objp->amount;
      print "</TR>\n";
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
 *
 */
$sql = "SELECT s.nom,s.idp,sum(f.total_ht) as amount";
$sql .= " FROM llx_societe as s,llx_facture_fourn as f WHERE f.fk_soc = s.idp"; 
  
$sql .= " GROUP BY s.nom ASC, s.idp";

print '<tr><td colspan="4">Frais</td></tr>';
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
            
      print "<TR $bc[$var]><td>&nbsp</td>";
      print "<td>Factures <a href=\"../../fourn/facture/index.php?socid=".$objp->idp."\">$objp->nom</a></TD>\n";
      
      print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
      
      $total = $total - $objp->amount;
      $subtotal = $subtotal + $objp->amount;
      print "</TR>\n";
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
print '<tr><td colspan="4">Prestations déductibles</td></tr>';

$sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
$sql .= " FROM c_chargesociales as c, llx_chargesociales as s";
$sql .= " WHERE s.fk_type = c.id AND c.deductible=1";

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
print '<tr><td colspan="4">Prestations NON déductibles</td></tr>';

$sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
$sql .= " FROM c_chargesociales as c, llx_chargesociales as s";
$sql .= " WHERE s.fk_type = c.id AND c.deductible=0";

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


print "</TABLE>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
