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

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";


print 'Utilisateur : ' . $user->prenom . ' ' . $user->nom .' ['.$user->code.']';

print "<ul>";
print "<li><A href=\"../comm/\">Commercial</A>";
print "<li><A href=\"../compta/\">Compta</A>";
print "<li><A href=\"../stats/\">Stats</A></ul>";



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
/*
 *
 */
$db = new Db();

if ($db->ok) {

  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td valign="top" width="50%">';

  print "<TABLE border=\"1\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR bgcolor=\"orange\">";
  print "<td>Description</td><td>Valeur</TD>";
  print "</TR>\n";

  $sql = "SELECT count(*) FROM llx_propal WHERE fk_statut = 0";
  if (valeur($sql)) {
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"comm/propal.php3?viewstatut=0\">Propales brouillons</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
  }

  $sql = "SELECT count(*) FROM llx_propal WHERE fk_statut = 1";
  if (valeur($sql)) {
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"comm/propal.php3?viewstatut=1\">Propales ouvertes</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
  }

  $sql = "SELECT count(*) FROM llx_facture WHERE paye=0";
  if (valeur($sql)) {
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"compta/index.php3\">Factures en attente de paiement</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
  }

  print "</table><br>";

  print '</td><td valign="top">';


  print '<A href="comm/index.php3">Societes</A>';
  print '<form action="comm/index.php3">';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="soc">';
  print '<input type="text" name="socname" size="8">&nbsp;';
  print "<input type=\"submit\" value=\"go\">";
  print "</form>";

  print '<A href="comm/contact.php3">Contacts</A>';
  print '<form action="comm/contact.php3">';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="contact">';
  print "<input type=\"text\" name=\"contactname\" size=\"8\">&nbsp;";
  print "<input type=\"submit\" value=\"go\">";
  print '</form>';


  print '</td></tr>';

  print '</table>';

  $db->close();
 
}
$Id = "Révision";
llxFooter("<em>$Id$</em>");
?>










