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

/*!	\file htdocs/admin/sqltables.php
		\brief      Page d'administration/configuration des tables sql
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


llxHeader();

print_barre_liste("Liste des tables", $page, "sqltables.php");

$sql = "SELECT name, loaded FROM ".MAIN_DB_PREFIX."sqltables";

print "<table class=\"noborder\" width=\"100%\">";
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td align="center">Chargée</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
  print "</tr>\n";
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object();
    $var=!$var;
    print "<tr $bc[$var]>";
    print "<td>$objp->name</td>\n";
    print '<td align="center">'.$objp->loaded."</td>\n";

    if ($objp->loaded) 
      {
	print '<td align="center"><a href="'.$PHPSELF.'?action=drop">Supprimer</td>';
      }
    else 
      {
	print '<td align="center"><a href="'.$PHPSELF.'?action=create">Créer</td>';
      }
    print "</tr>\n";
    $i++;
  }
  $db->free();
}

print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
