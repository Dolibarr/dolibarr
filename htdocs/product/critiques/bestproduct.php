<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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

$db = new Db();

if ($sortfield == "") {
  $sortfield="rat";
}
if ($sortorder == "") {
  $sortorder="DESC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


print_barre_liste("Liste des produits classés pas critiques", $page, $PHP_SELF);

$sql = "SELECT sum(r.reviews_rating)/count(r.reviews_rating) as rat, r.products_id, p.products_model, p.products_quantity, p.products_status";
$sql .= " FROM ".DB_NAME_OSC.".reviews as r,".DB_NAME_OSC.".products as p ";
$sql .= " WHERE r.products_id = p.products_id";
$sql .= " GROUP BY r.products_id, p.products_model, p.products_quantity, p.products_status";

$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);

  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\"><td>Réf";
  print '</td><TD align="center">Indice critiques</TD>';
  print '</td><td align="center">Quantité';
  print '</td><td align="center">Status</TD>';
  print "</TR>\n";
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print '<TD><a href="'.DOL_URL_ROOT.'/boutique/livre/fiche.php?oscid='.$objp->products_id.'">'.$objp->products_model.'</a></TD>';
    print '<TD align="center">'.$objp->rat."</TD>\n";
    print '<TD align="center">'.$objp->products_quantity."</TD>\n";
    print '<TD align="center">'.$objp->products_status."</TD>\n";
    print "</TR>\n";
    $i++;
  }
  $db->free();
}

print "</TABLE>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
