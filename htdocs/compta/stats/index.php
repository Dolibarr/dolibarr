<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$mode='recettes';
if ($conf->compta->mode == 'CREANCES-DETTES') { $mode='creances'; }

print_titre("Chiffre d'affaire (".MAIN_MONNAIE." HT, ".$mode.")");

$sql = "SELECT sum(f.total) as amount , date_format(f.datef,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.fk_statut = 1";
if ($conf->compta->mode != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
}
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

print '<table width="100%" class="noborder" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td rowspan="2">'.$langs->trans("Month").'</td>';

$year_current = strftime("%Y",time());
$nbyears = 3;

if ($year_current < (MAIN_START_YEAR + $nbyears))
{
  $year_start = MAIN_START_YEAR;
  $year_end = (MAIN_START_YEAR + $nbyears - 1);
}
else
{
  $year_start = $year_current - ($nbyears + 1);
  $year_end = $year_current ;
}

for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="center" width="10%" colspan="2">'.$annee.'</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right">Montant</td>';
  print '<td align="center">Delta</td>';
}
print '</tr>';

for ($mois = 1 ; $mois < 13 ; $mois++)
{
  $var=!$var;
  print "<tr $bc[$var]>";

  print "<td>".strftime("%B",mktime(1,1,1,$mois,1,2000))."</td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
    {
      $casenow = strftime("%Y-%m",mktime());
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      $caseprev = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee-1));


      // Valeur CA

      print '<td align="right">';
      if ($cum[$case])
	{
	  print price($cum[$case],1);
	}
      else
	{
	  if ($case <= $casenow) { print '0'; }
	  else { print '&nbsp;'; }
	}
      print "</td>";
      // Pourcentage evol
      if ($cum[$caseprev]) {
	if ($case <= $casenow) {
	  if ($cum[$caseprev]) 
	    print '<td align="center">'.(round(($cum[$case]-$cum[$caseprev])/$cum[$caseprev],4)*100).'%</td>';
	  else
	    print '<td align="center">+Inf%</td>';
	}
	else
	  {
	    print '<td>&nbsp;</td>';
	  }
      } else {
	if ($case <= $casenow) {
	  print '<td align="center">-</td>';
	}
	else {
	  print '<td>&nbsp;</td>';
	}
      }
      
      $total[$annee]+=$cum[$case];
    }
 
 print '</tr>';
}

// Affiche total
print "<tr><td align=\"right\"><b>Total :</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print "<td align=\"right\"><b>".($total[$annee]?$total[$annee]:"&nbsp;")."</b></td>";
  
  // Pourcentage evol
  if ($total[$annee-1]) {
    if ($annee <= $year_current) {
      if ($total[$annee-1]) 
	print '<td align="center"><b>'.(round(($total[$annee]-$total[$annee-1])/$total[$annee-1],4)*100).'%</b></td>';
      else
	print '<td align="center">+Inf%</td>';
    }
    else
      {
	print '<td>&nbsp;</td>';
      }
  }
  else
    {
      if ($annee <= $year_current)
	{
	  print '<td align="center">-</td>';
	}
      else
	{
	  print '<td>&nbsp;</td>';
	}
    }
  
}
print "</tr>\n";
print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
