<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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

if ($sortfield == "")
{
  $sortfield="c";
}
if ($sortorder == "")
{
  $sortorder="DESC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

llxHeader();

print_barre_liste("Liste des produits par popularité dans les propositions commerciales", $page, "popuprop.php");

print '<table class="noborder" width="100%">';

print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("Ref"),"popuprop.php", "p.ref");
print_liste_field_titre($langs->trans("Label"),"popuprop.php", "p.label");
print_liste_field_titre("Nb. de proposition","popuprop.php", "c","","",'align=\"center\"');
print "</tr>\n";

$sql = "select p.rowid, p.label, p.ref, count(*) as c from ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."product as p where p.rowid = pd.fk_product group by (p.rowid)";

$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);

 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></TD>\n";
      print "<TD>$objp->label</TD>\n";
      print '<TD align="center">'.$objp->c.'</TD>';
      print "</TR>\n";
      $i++;
    }
  $db->free();
}
print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
