<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../contact.class.php3");
require("../societe.class.php3");

llxHeader();
$db = new Db();
if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="nom";
}
$bc[0]="bgcolor=\"#c0f0c0\"";
$bc[1]="bgcolor=\"#b0e0b0\"";
$bc2[0]="bgcolor=\"#c9f000\"";
$bc2[1]="bgcolor=\"#b9e000\"";

print '<div class="titre">Liste des fiches d\'intervention</div><p>';

$sql = "SELECT s.nom,s.idp, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut";
$sql .= " FROM societe as s, llx_fichinter as f ";
$sql .= " WHERE f.fk_soc = s.idp ";
$sql .= " ORDER BY f.datei DESC ;";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<TD>Num</TD>";
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
  print "<TD>Date</TD>";
  print "<TD>Statut</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"fiche.php3?id=$objp->fichid\">$objp->ref</a></TD>\n";
    print "<TD><a href=\"../comm/index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
    print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
    print "<TD>$objp->fk_statut</TD>\n";
    
    print "</TR>\n";
    
    $i++;
  }

  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
  print "<p>$sql";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
