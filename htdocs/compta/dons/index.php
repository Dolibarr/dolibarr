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

/*!	    \file       htdocs/compta/dons/index.php
		\ingroup    don
		\brief      Page accueil espace don
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("donations");

llxHeader();

$sql = "SELECT count(d.rowid) as nb, sum(d.amount) as somme , d.fk_statut FROM ".MAIN_DB_PREFIX."don as d GROUP BY d.fk_statut order by d.fk_statut";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);

      $somme[$objp->fk_statut] = $objp->somme;
      $nb[$objp->fk_statut] = $objp->nb;
      $i++;
    }
  $db->free();
} else {
    dolibarr_print_error($db);   
}

print_titre($langs->trans("Donations"));

print '<table class="noborder" width="50%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Status").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("Amount").'</td>';
print "</tr>\n";

$var=True;

for ($i = 0 ; $i < 3 ; $i++)
{
  $var=!$var;
  print "<tr $bc[$var]>";
  print '<td><a href="liste.php?statut='.$i.'">'.$libelle[$i].'</a></td>';
  print '<td align="right">'.$nb[$i].'</td>';
  print '<td align="right">'.price($somme[$i]).'</td>';
  $totalnb += $nb[$i];
  $total += $somme[$i];
  print "</tr>";
}

$var=!$var;
print "<tr $bc[$var]>";
print '<td>'.$langs->trans("Total").'</td>';
print '<td align="right">'.$totalnb.'</td>';
print '<td align="right">'.price($total).'</td>';
print '</tr>';
print "</table>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
