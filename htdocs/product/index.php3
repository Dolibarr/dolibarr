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

$yn["t"] = "oui";
$yn["f"] = "non";

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print "<a href=\"$PHP_SELF\">Liste</a><P>";

if ($rowid) {

  $sql = "SELECT p.rowid, p.label, p.price, p.description, p.duration,p.ref FROM llx_product as p";
  $sql .= " WHERE p.rowid = $rowid;";
  
 
 if ( $db->query($sql) ) {
   $num = $db->num_rows();
   $i = 0;
   print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
   print "<TR bgcolor=\"orange\">";
   print "<TD>Réf</TD>";
   print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Nom</a></td>";
   print "</TR>\n";
   $var=True;
   if ( $num ) {
     $objp = $db->fetch_object(0);
     $var=!$var;
     print "<TR $bc[$var]>";
     print "<TD><a href=\"$PHP_SELF?rowid=$objp->rowid\">$objp->ref</a></TD>\n";
     print "<TD>$objp->label</TD></tr>\n";
     print "<tr><td>prix</td><TD>$objp->price</td></tr>\n";
     print "<tr><td>duree</td><TD>$objp->duration</td></tr>\n";

     print "<tr><td>desc</td><td>".nl2br($objp->description)."</td></tr>";
     $i++;
   }
   print "</TABLE>";
   $db->free();

   print "<hr><form action=\"$PHP_SELF?rowid=$rowid\" method=\"post\">\n";
   print "<input type=\"hidden\" name=\"action\" value=\"update\">";
   print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
   print nl2br($objp->description);
   print "</textarea><br>";
   print "<input type=\"submit\">";
   print "</form>";
 }



} else {

  $sql = "SELECT p.rowid, p.label, p.price, p.duration,p.ref FROM llx_product as p";
  
  $sql .= " ORDER BY $sortfield $sortorder ";
  $sql .= $db->plimit( $limit ,$offset);
 
 if ( $db->query($sql) ) {
   $num = $db->num_rows();
   $i = 0;
   print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
   print "<TR bgcolor=\"orange\">";
   print "<TD>Réf</TD>";
   print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Nom</a></td>";
   print "<TD align=\"right\">Prix</TD>";
   print "<TD align=\"right\">Durée</TD>";
   print "</TR>\n";
   $var=True;
   while ($i < $num) {
     $objp = $db->fetch_object( $i);
     $var=!$var;
     print "<TR $bc[$var]>";
     print "<TD><a href=\"$PHP_SELF?rowid=$objp->rowid\">$objp->ref</a></TD>\n";
     print "<TD>$objp->label</TD>\n";
     print '<TD align="right">'.price($objp->price).'</TD>';
     print "<TD align=\"right\">$objp->duration</TD>\n";
     print "</TR>\n";
     $i++;
   }
   print "</TABLE>";
   $db->free();
 }

}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
