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

print_barre_liste("Liste des tables", $page, "sqltables.php");

$sql = "SELECT name, loaded FROM ".MAIN_DB_PREFIX."sqltables";

print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<TR class="liste_titre">';
print "<td>Nom</td>";
print '<td align="center">Chargée</td>';
print '<td align="center">Action</td>';
  print "</TR>\n";
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD>$objp->name</TD>\n";
    print '<TD align="center">'.$objp->loaded."</TD>\n";

    if ($objp->loaded) 
      {
	print '<TD align="center"><a href="'.$PHPSELF.'?action=drop">Supprimer</TD>';
      }
    else 
      {
	print '<TD align="center"><a href="'.$PHPSELF.'?action=create">Créer</TD>';
      }
    print "</tr>\n";
    $i++;
  }
  $db->free();
}

print "</TABLE>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
