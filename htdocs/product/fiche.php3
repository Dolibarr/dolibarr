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

if ($action == 'update') {

  $sql = "UPDATE llx_product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}

if ($id) {

  $product = new Product($db);
  $result = $product->fetch($id);

  if ( $result ) {
    
    print '<div class="titre">Fiche produit : '.$product->ref.'</div>';

    print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

    print "<TR>";
    print "<TD>Référence</td><td>$product->ref</a></tr>\n";
    print "<TD>Libellé</td><td>$product->label</TD></tr>\n";

    print "<tr><td>Prix</td><TD>$product->price</td></tr>\n";
    
    print "<tr><td valign=\"top\">Description</td><td>".nl2br($product->description)."</td></tr>";
    
  }
  print "</TABLE>";

  if ($action == 'edit') {


    print "<hr><form action=\"$PHP_SELF?rowid=$rowid\" method=\"post\">\n";
    print "<input type=\"hidden\" name=\"action\" value=\"update\">";
    print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
    print nl2br($product->description);
    print "</textarea><br>";
    print "<input type=\"submit\">";
    print "</form>";



  }


} else {
  print "Error";
}




$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
