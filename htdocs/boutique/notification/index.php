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

if ($sortfield == "") {
  $sortfield="lower(c.customers_lastname)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des notifications", $page, $PHP_SELF);

$sql = "SELECT c.customers_id, c.customers_lastname, c.customers_firstname, p.products_name, p.products_id";
$sql .= " FROM ".DB_NAME_OSC.".products_notifications as n,".DB_NAME_OSC.".products_description as p";
$sql .= ",".DB_NAME_OSC.".customers as c";
$sql .= " WHERE n.customers_id = c.customers_id AND p.products_id=n.products_id";
$sql .= " AND p.language_id = ".OSC_LANGUAGE_ID;
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Client",$PHP_SELF, "c.customers_lastname");
  print "</td>";
  print "<td>Produit</td>";
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD width='70%'><a href=\"fiche.php?id=$objp->rowid\">$objp->customers_firstname $objp->customers_lastname</a></TD>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/boutique/livre/fiche.php?oscid='.$objp->products_id.'">'.$objp->products_name."</a></td>";
      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
