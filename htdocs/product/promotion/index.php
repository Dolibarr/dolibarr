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

$db = new Db();

print_barre_liste("Liste des promotions", $page, $PHP_SELF);

$sql = "SELECT pd.products_name, s.specials_new_products_price, p.products_price";
$sql .= " FROM ".DB_NAME_OSC.".specials as s,".DB_NAME_OSC.".products_description as pd,".DB_NAME_OSC.".products as p";
$sql .= " WHERE s.products_id = pd.products_id AND pd.products_id = p.products_id AND pd.language_id = ".OSC_LANGUAGE_ID;
  
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print '<p><TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Titre",$PHP_SELF, "a.title");
  print "</td>";
  print '<td align="right">Prix initial</td>';
  print '<td align="right">Prix remisé</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      
      print "<tr>";
      print '<td>'.$objp->products_name."</td>";
      print '<td align="rigth">'.price($objp->products_price)."</td>";
      print '<td align="right">'.price($objp->specials_new_products_price)."</td>";
      print "</tr>";
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
