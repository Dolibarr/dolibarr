<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php3");


require("../../tva.class.php3");
require("../../chargesociales.class.php3");

/*
 *
 */

llxHeader();


$db = new Db();


print_titre("Résultat $year");


print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<TR class="liste_titre">';
print "<TD>Num&eacute;ro</TD>";
print "<TD align=\"center\" colspan=\"2\">Montant</TD><td align=\"right\">Solde</td>";
print "</TR>\n";

$sql = "SELECT s.nom,sum(f.amount) as amount";
$sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp";
  
if ($year > 0) {
  $sql .= " AND date_format(f.datef, '%Y') = $year";
}
  
$sql .= " GROUP BY s.nom ASC";
  
$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
    
  $i = 0;
  
  if ($num > 0) {
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
            
      print "<TR $bc[$var]>";
      print "<td>Facture <a href=\"/compta/facture.php3?facid=$objp->facid\">$objp->facnumber</a> $objp->nom</TD>\n";
      
      print "<TD align=\"right\">".price($objp->amount)."</TD><td>&nbsp;</td>\n";
      
      $total = $total + $objp->amount;
      print "<TD align=\"right\">".price($total)."</TD>\n";
      
      print "</TR>\n";
      $i++;
    }
  }
  $db->free();
} else {
  print $db->error();
}
/*
 * Charges sociales
 *
 */


$sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
$sql .= " FROM c_chargesociales as c, llx_chargesociales as s";
$sql .= " WHERE s.fk_type = c.id";
if ($year > 0) {
  $sql .= " AND date_format(s.periode, '%Y') = $year";
}
$sql .= " GROUP BY c.libelle DESC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    $var = !$var;
    print "<tr $bc[$var]>";
    print '<td>'.$obj->nom.'</td>';
    print '<td>&nbsp;</td><td align="right">'.price($obj->amount).'</td>';
    $total = $total - $obj->amount;
    print "<TD align=\"right\">".price($total)."</TD>\n";

    print '</tr>';
    $i++;
  }
} else {
  print $db->error();
}



print "</TABLE>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
