<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../commande.class.php");
require("../../graph.class.php");

llxHeader();
/*
 *
 *
 */

print_fiche_titre('Statistiques commandes', $mesg);
      
print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td>Année</td><td width="40%">Nb de commande</td><td>Somme des commandes</td></tr>';

$sql = "SELECT count(*), date_format(date_commande,'%Y') as dm, sum(total_ht)  FROM llx_commande WHERE fk_statut > 0 GROUP BY dm DESC ";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $nbproduct = $row[0];
      $year = $row[1];
      print "<tr>";
      print '<td><a href="month.php?year='.$year.'">'.$year.'</a></td><td>'.$nbproduct.'</td><td>'.price($row[2]).'</td></tr>';
      $i++;
    }
}
$db->free();

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
