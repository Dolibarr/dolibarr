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

llxHeader();

$db = new Db();

print_barre_liste("Chiffre d'affaire par société", $page, $PHP_SELF);



$sql = "SELECT s.nom, s.idp, sum(f.amount) as ca";
$sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp GROUP BY s.nom, s.idp ORDER BY ca DESC";
 
$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  if ($num > 0) {
    $i = 0;
    print "<p><TABLE border=\"0\" width=\"50%\" cellspacing=\"0\" cellpadding=\"4\">";
    print "<TR class=\"liste_titre\">";
    print "<TD>Société</td>";
    print "<TD align=\"right\">Montant</TD><td>&nbsp;</td>";
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      
      print "<TD><a href=\"fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
      print "<TD align=\"right\">".price($objp->ca)."</TD><td>&nbsp;</td>\n";
      
      $total = $total + $objp->ca;	  
      print "</TR>\n";
      $i++;
    }
    print "<tr><td colspan=\"2\" align=\"right\"><b>Total : ".price($total)."</b></td><td>euros HT</td></tr>";
    print "<tr><td colspan=\"2\" align=\"right\">Moyenne : ".price($total/$i)."</td><td>euros HT</td></tr>";
    print "</TABLE>";
  }
  $db->free();
} else {
  print $db->error();
}


$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
