<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file htdocs/compta/dons/index.php
		\ingroup    don
		\brief      Page accueil espace don
		\version    $Revision$
*/

require("./pre.inc.php");


llxHeader();

$sql = "SELECT sum(d.amount) as somme , d.fk_statut FROM ".MAIN_DB_PREFIX."don as d GROUP BY d.fk_statut";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);

      $somme[$objp->fk_statut] = $objp->somme;
      $i++;
    }
  $db->free();
}

print_titre("Dons");

print '<table class="noborder">';
print '<tr class="liste_titre">';
print "<td>&nbsp;</td>";
print "<td>Somme</td>";
print "</tr>\n";

$var=True;

for ($i = 0 ; $i < 4 ; $i++)
{
  $var=!$var;
  print "<tr $bc[$var]>";
  print '<td><a href="liste.php?statut='.$i.'">'.$libelle[$i].'</a></td>';
  print '<td align="right">'.price($somme[$i]).'</td>';
  $total += $somme[$i];
  print "</tr>";
}
print "<tr $bc[0]>".'<td>'.$langs->trans("Total").'</td><td align="right">'.price($total).'</td></tr>';
print "</table>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
