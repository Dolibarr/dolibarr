<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/compta/dons/index.php
		\ingroup    don
		\brief      Page accueil espace don
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/don.class.php");

$langs->load("donations");


/*
 * Affichage
 */
 
llxHeader();

$donstatic=new Don($db);

$sql = "SELECT count(d.rowid) as nb, sum(d.amount) as somme , d.fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."don as d";
$sql.= " GROUP BY d.fk_statut";
$sql.= " ORDER BY d.fk_statut";

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
  $db->free($result);
} else {
    dol_print_error($db);   
}

print_fiche_titre($langs->trans("DonationsArea"));


print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td class="notopnoleft">';


print '<table class="noborder" width="50%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Status").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">'.$langs->trans("Average").'</td>';
print "</tr>\n";

$var=True;

for ($i = 0 ; $i < 3 ; $i++)
{
  $var=!$var;
  print "<tr $bc[$var]>";
  print '<td><a href="liste.php?statut='.$i.'">'.$donstatic->LibStatut($i,4).'</a></td>';
  print '<td align="right">'.$nb[$i].'</td>';
  print '<td align="right">'.($nb[$i]?price($somme[$i]):'&nbsp;').'</td>';
  print '<td align="right">'.($nb[$i]?(price($somme[$i])/$nb[$i]):'&nbsp;').'</td>';
  $totalnb += $nb[$i];
  $total += $somme[$i];
  print "</tr>";
}

print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print '<td align="right">'.$totalnb.'</td>';
print '<td align="right">'.price($total).'</td>';
print '<td align="right">'.($totalnb?(price($total)/$totalnb):'&nbsp;').'</td>';
print '</tr>';
print "</table>";


print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
