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

/*
 *
 */

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socidp = $user->societe_id;
}

print_titre("Chiffres d'affaires en euros HT");

$sql = "SELECT sum(f.total) as amount , date_format(f.datef,'%Y-%m') as dm";
$sql .= " FROM llx_facture as f WHERE f.paye = 1";

if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0; 
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $cum[$row[1]] = $row[0];
      $i++;
    }
}

print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td>&nbsp;</td>';


$year_current = strftime("%Y",time());

if ($year_current < (MAIN_START_YEAR + 4))
{
  $year_start = MAIN_START_YEAR;
  $year_end = (MAIN_START_YEAR + 3);
}
else
{
  $year_start = $year_current - 3;
  $year_end = $year_current ;
}


for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="center" width="14%">'.$annee.'</td>';
}
print '</tr>';
for ($mois = 1 ; $mois < 13 ; $mois++)
{
  $var=!$var;
  print "<TR $bc[$var]>";

  print "<td>".strftime("%B",mktime(1,1,1,$mois,1,2000))."</td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
    {
      print '<td align="right">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($cum[$case]>0)
	{
	  print price($cum[$case]);
	}
      print "</td>";
    }

  print '</tr>';
}

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
