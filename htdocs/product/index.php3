<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./pre.inc.php3");

llxHeader();

$db = new Db();
if ($sortfield == "") {
  $sortfield="lower(p.label),p.price";
}
if ($sortorder == "") {
  $sortorder="ASC";
}


if ($action == 'update') {

  $sql = "UPDATE llx_product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


print_barre_liste("Liste des produits", $page, $PHP_SELF);

$sql = "SELECT p.rowid, p.label, p.price, p.ref FROM llx_product as p";
  
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Réf",$PHP_SELF, "p.ref");
  print "</td><td>";
  print_liste_field_titre("Libellé",$PHP_SELF, "p.label");
  print "</td><TD align=\"right\">Prix de vente</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"fiche.php3?id=$objp->rowid\">$objp->ref</a></TD>\n";
    print "<TD>$objp->label</TD>\n";
    print '<TD align="right">'.price($objp->price).'</TD>';
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
