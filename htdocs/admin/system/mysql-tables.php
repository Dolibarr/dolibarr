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
 */
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


if ($_GET["action"] == 'convert')
{
  $db->query("alter table ".$_GET["table"]." type=INNODB");
}

llxHeader();

print_titre("Tables Mysql");

print '<br>';
print '<table class="noborder" cellpadding="4" cellspacing="1">';
print '<tr class="liste_titre">';
print '<td>Nom de la table</td>';
print '<td colspan="2">'.$langs->trans("Type").'</td>';
print '<td>Format lignes</td>';
print '<td>Nb enr.</td>';
print '<td>Avg_row_length</td>';
print '<td>Data_length</td>';
print '<td>Max_Data_length</td>';
print '<td>Index_length</td>';
print '<td>Last check</td>';
print "</tr>\n";

$sql = "SHOW TABLE STATUS";

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $var=True;
  $i=0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $var=!$var;
      print "<TR $bc[$var]>";

      print '<td>'.$row[0].'</td>';
      print '<td>'.$row[1].'</td>';
      if ($row[1] == "MyISAM")
	{
	  print '<td><a href="mysql-tables.php?action=convert&amp;table='.$row[0].'">Convertir</a></td>';
	}
      else
	{
	  print '<td>-</td>';
	}
      print '<td>'.$row[2].'</td>';
      print '<td align="right">'.$row[3].'</td>';
      print '<td align="right">'.$row[4].'</td>';
      print '<td align="right">'.$row[5].'</td>';
      print '<td align="right">'.$row[6].'</td>';
      print '<td align="right">'.$row[7].'</td>';
      print '<td align="right">'.$row[12].'</td>';
      print '</tr>';
      $i++;
    }
}
print '</table>';

llxFooter();
?>
