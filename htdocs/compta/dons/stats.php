<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
	    \file       htdocs/compta/dons/stats.php
        \ingroup    don
		\brief      Page des statistiques de dons
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();


print_titre($langs->trans("Statistics"));

$sql = "SELECT d.amount";
$sql .= " FROM ".MAIN_DB_PREFIX."don as d LEFT JOIN ".MAIN_DB_PREFIX."don_projet as p";
$sql .= " ON p.rowid = d.fk_don_projet";

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();

  $i=0;
  $total=0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $total += $objp->amount;
      $i++;
    }

  print '<table class="border">';

  print "<tr $bc[1]>";
  print '<td>Nombre de dons</td><td align="right">'.$num.'</td></tr>';
  print "<tr $bc[0]>".'<td>'.$langs->trans("Total").'</td><td align="right">'.price($total).'</td>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Average").'</td><td align="right">'.price($total / ($num?$num:1)).'</td>';
  print "</tr>";

  print "</table>";
}
else
{
  pdolibarr_print_error($db);
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
