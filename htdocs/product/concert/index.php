<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

if ($sortfield == "") {
  $sortfield="c.date_concert";
}
if ($sortorder == "") {
  $sortorder="DESC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des concerts", $page, $PHP_SELF);

//$sql = "SELECT c.rowid, c.date_concert as dc, ga.nom, lc.nom as lieu, lc.ville";
$sql = "SELECT c.rowid, c.date_concert as dc, c.fk_groupart, c.fk_lieu_concert, ga.nom, lc.nom as lieu, lc.ville";
$sql .= " FROM ".MAIN_DB_PREFIX."concert as c, ".MAIN_DB_PREFIX."groupart as ga, ".MAIN_DB_PREFIX."lieu_concert as lc";
$sql .= " WHERE c.fk_groupart = ga.rowid AND c.fk_lieu_concert = lc.rowid";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Titre",$PHP_SELF, "a.title");
  print "</td><td>";
  print_liste_field_titre("Artiste/Groupe",$PHP_SELF, "ga.nom");
  print "</td><td>";
  print_liste_field_titre("Salle",$PHP_SELF, "lc.nom");
  print "</td><td>";
  print_liste_field_titre("Ville",$PHP_SELF, "lc.ville");
  print "</td>";

  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"fiche.php?id=$objp->rowid\">$objp->dc</a></TD>\n";
    //    print '<TD><a href="product_info.php?products_id='.$objp->osc_id.'">'.$objp->nom.'</a></TD>';
    print '<TD><a href="../groupart/fiche.php?id='.$objp->fk_groupart.'">'.$objp->nom.'</a></TD>';
    //    print '<TD><a href="product_info.php?products_id='.$objp->osc_id.'">'.$objp->lieu.'</a></TD>';
    print '<TD><a href="fichelieu.php?id='.$objp->fk_lieu_concert.'">'.$objp->lieu.'</a></TD>';
    print '<TD>'.$objp->ville.'</TD>';
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
