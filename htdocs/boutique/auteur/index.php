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
  $sortfield="lower(e.nom)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des Auteurs", $page, "index.php");

$sql = "SELECT e.rowid, e.nom FROM ".MAIN_DB_PREFIX."auteur as e";
  
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\"><td>";
  print_liste_field_titre("Nom","index.php", "e.nom");
  print "</td>";
  print "</tr>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object();
    $var=!$var;
    print "<tr $bc[$var]>";
    print "<td width='70%'><a href=\"fiche.php?id=$objp->rowid\">$objp->nom</a></td>\n";
    print "</tr>\n";
    $i++;
  }
  print "</table>";
  $db->free();
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
