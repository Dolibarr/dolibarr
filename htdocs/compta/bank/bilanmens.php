<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

print_titre("Bilan mensuel Entrées/Sorties pour ce compte bancaire");
print '<br>';

# Le bilan est basé sur lx_bank (car doit inclure les transactions sans facture)
# plutot que sur llx_paiement + llx_paiementfourn

$sql = "SELECT sum(f.amount), date_format(f.dateo,'%Y-%m') as dm";
$sql .= " FROM llx_bank as f";
$sql .= " WHERE f.amount >= 0";
if ($account) { $sql .= " AND fk_account = $account"; }
$sql .= " GROUP BY dm";

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
} else {
	print "Erreur: SQL $sql";
}

$sql = "SELECT sum(f.amount), date_format(f.dateo,'%Y-%m') as dm";
$sql .= " FROM llx_bank as f";
$sql .= " WHERE f.amount <= 0";
if ($account) { $sql .= " AND fk_account = $account"; }
$sql .= " GROUP BY dm";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $decaiss[$row[1]] = -$row[0];
      $i++;
    }
} else {
    print "Erreur: SQL $sql";
}


print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td rowspan=2>Mois</td>';

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
print '<tr class="liste_titre">';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{ 
  print '<td align="right">Débits</td><td align="right">Crédits</td>';
}
print '</tr>';

$var=True;
for ($mois = 1 ; $mois < 13 ; $mois++)
{
  $var=!$var;
  print '<tr '.$bc[$var].'>';
  print "<td>".strftime("%B",mktime(1,1,1,$mois,1,2000))."</td>";
  for ($annee = $year_start ; $annee <= $year_end ; $annee++)
    {
      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($decaiss[$case]>0)
	{
	  print price($decaiss[$case]);
	  $totsorties[$annee]+=$decaiss[$case];
	}
      print "</td>";

      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($encaiss[$case]>0)
	{
	  print price($encaiss[$case]);
	  $totentrees[$annee]+=$encaiss[$case];
	}
      print "</td>";
    }

  print '</tr>';
}

$var=!$var;
print "<tr ".$bc[$var]."><td><b>Total annuel</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right"><b>'.price($totsorties[$annee]).'</b></td><td align="right"><b>'.price($totentrees[$annee]).'</b></td>';
}
print "</tr>\n";

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
