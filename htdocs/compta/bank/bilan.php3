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

function valeur($sql) {
  global $db;
  if ( $db->query($sql) ) {
    if ( $db->num_rows() ) {
      $valeur = $db->result(0,0);
    }
    $db->free();
  }
  return $valeur;
}


print_titre("Bilan");

print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR class=\"liste_titre\">";
print "<td>Description</td><td align=\"right\">Montant</TD><td align=\"right\">francs</TD>";
print "</TR>\n";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_paiement";
$paiem = valeur($sql);
print "<tr $bc[$var]><td>Somme des paiements</td><td align=\"right\">".price($paiem)."</td><td align=\"right\">".francs($paiem)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_bank WHERE amount > 0";
$credits = valeur($sql);
print "<tr $bc[$var]><td>Somme des credits</td><td align=\"right\">".price($credits)."</td><td align=\"right\">".francs($credits)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_bank WHERE amount < 0";
$debits = valeur($sql);
print "<tr $bc[$var]><td>Somme des debits</td><td align=\"right\">".price($debits)."</td><td align=\"right\">".francs($debits)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_bank ";
$solde = valeur($sql);
print "<tr $bc[$var]><td>Solde compte</td><td align=\"right\">".price($solde)."</td><td align=\"right\">".francs($solde)."</td></tr>";


print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
