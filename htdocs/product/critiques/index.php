<?php
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
  $sortfield="date_added";
}
if ($sortorder == "") {
  $sortorder="DESC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Critiques", $page, "index.php");

$sql = "SELECT r.reviews_id, r.reviews_rating, d.reviews_text, p.products_name FROM ".DB_NAME_OSC.".reviews as r, ".DB_NAME_OSC.".reviews_description as d, ".DB_NAME_OSC.".products_description as p";
$sql .= " WHERE r.reviews_id = d.reviews_id AND r.products_id=p.products_id";
$sql .= " AND p.language_id = ".OSC_LANGUAGE_ID. " AND d.languages_id=".OSC_LANGUAGE_ID;
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);

print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<TR class="liste_titre">';
print "<td>Produit</td>";
print "<td>Critique</td>";
print "<td align=\"center\">Note</td>";
print "<TD align=\"right\"></TD>";
print "</TR>\n";
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD>".substr($objp->products_name, 0, 30)."</TD>\n";
    print '<TD><a href="fiche.php?id='.$objp->reviews_id.'">'.substr($objp->reviews_text, 0, 40)." ...</a></td>\n";
    print "<td align=\"center\">$objp->reviews_rating</TD>\n";
    print "</TR>\n";
    $i++;
  }
  $db->free();
}

print "</TABLE>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
