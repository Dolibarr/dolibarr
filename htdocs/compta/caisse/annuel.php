<?PHP
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

print_titre("Caisse");

$sql = "SELECT sum(f.amount) as amount , date_format(f.datep,'%Y-%m') as dm";
$sql .= " FROM llx_paiement as f";

if ($socidp)
{
  $sql .= " WHERE f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0; 
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $encaiss[$row[1]] = $row[0];
      $i++;
    }
}

$sql = "SELECT sum(f.amount) as amount , date_format(f.datep,'%Y-%m') as dm";
$sql .= " FROM llx_paiementfourn as f";

if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0; 
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $decaiss[$row[1]] = $row[0];
      $i++;
    }
}

print '<table class="border" width="100%" border="1" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Mois</td>';

$year_current = strftime("%Y",time());

if ($year_current < (MAIN_START_YEAR + 2))
{
  $year_start = MAIN_START_YEAR;
  $year_end = (MAIN_START_YEAR + 2);
}
else
{
  $year_start = $year_current - 2;
  $year_end = $year_current;
}

for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="center" width="20%" colspan="2">'.$annee.'</td>';
}
print '</tr>';
for ($mois = 1 ; $mois < 13 ; $mois++)
{
  print '<tr>';
  print "<td>".strftime("%B",mktime(1,1,1,$mois,1,2000))."</td>";
  for ($annee = $year_start ; $annee <= $year_end ; $annee++)
    {
      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($encaiss[$case]>0)
	{
	  print price($encaiss[$case]);
	}
      print "</td>";

      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($decaiss[$case]>0)
	{
	  print price($decaiss[$case]);
	}
      print "</td>";
    }

  print '</tr>';
}

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
