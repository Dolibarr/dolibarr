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

if ($action == 'add') {
  $product = new Product($db);

  $product->ref = $ref;
  $product->libelle = $libelle;
  $product->price = $price;
  $product->description = $desc;

  $id = $product->create($user);
}

if ($action == 'update') {
  $product = new Product($db);

  $product->ref = $ref;
  $product->libelle = $libelle;
  $product->price = $price;
  $product->description = $desc;

  $product->update($id, $user);
}
/*
 *
 *
 */
if ($action == 'create') {

  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
      
  print 'Nouveau produit<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Référence</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Libellé</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td>Prix</td><TD><input name="price" size="10" value=""></td></tr>';    
  print "<tr><td valign=\"top\">Description</td><td>";
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
      

} else {
  if ($id) {

    $product = new Product($db);
    $result = $product->fetch($id);

    if ( $result ) { 
      print '<div class="titre">Fiche produit : '.$product->ref.'</div>';
      
      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print "<tr>";
      print "<td>Référence</td><td>$product->ref</td></tr>\n";
      print "<td>Libellé</td><td>$product->label</td></tr>\n";
      print '<tr><td>Prix</td><TD>'.price($product->price).'</td></tr>';    
      print "<tr><td valign=\"top\">Description</td><td>".nl2br($product->description)."</td></tr>";
      print "</table>";
    }
    

    if ($action == 'edit') {
      print "<hr><form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
      print "<input type=\"hidden\" name=\"action\" value=\"update\">";
      
      print 'Edition de la fiche produit<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print "<tr>";
      print '<td>Référence</td><td><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
      print '<td>Libellé</td><td><input name="libelle" size="40" value="'.$product->label.'"></td></tr>';
      print '<tr><td>Prix</td><TD><input name="price" size="10" value="'.$product->price.'"></td></tr>';    
      print "<tr><td valign=\"top\">Description</td><td>";
      print '<textarea name="desc" rows="8" cols="50">';
      print $product->description;
      print "</textarea></td></tr>";
      print '<tr><td>&nbsp;</td><td><input type="submit"></td></tr>';
      print '</table>';
      print '</form>';
    }    
  } else {
    print "Error";
  }
}


print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';

print '<td width="20%" align="center">[<a href="fiche.php3?action=edit&id='.$id.'">Editer</a>]</td>';

print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
