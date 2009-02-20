<?PHP
/* Copyright (C) 2005-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require DOL_DOCUMENT_ROOT.'/telephonie/distributeurtel.class.php';

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Distributeur');

/*
 *
 *
 *
 */
$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/index.php';
$head[$h][1] = "Prise d'ordre";
$h++;
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/resultats.php';
$head[$h][1] = "Résultats";
$hselected = $h;
$h++;

$year = strftime("%Y",time());
if (strftime("%m",time()) == 1)
{
  $year = $year -1;
}
if ($_GET["year"] > 0)
{
  $year = $_GET["year"];
}
$total = 0;
$var = True;
dol_fiche_head($head, $hselected, "Distributeurs");
stat_year_bar($year);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="70%">';
print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/resultat.mensuel.'.$year.'.png" alt="Resultat mensuel" title="Resultat mensuel">'."\n";
print '</td><td valign="top" width="30%">';
print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Mois</td><td align="right">Resultat</td></tr>';

$sql = "SELECT valeur,legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
$sql .= " WHERE graph = 'distributeur.resultat.mensuel'";
$sql .= " AND legend like '".$year."%'";
$sql .= " ORDER BY legend DESC";  
$resql = $db->query($sql);
  
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      print "<tr $bc[$var]><td>".$row[1].'</td>';  
      print '<td align="right">'.price($row[0]).'</td></tr>';
      $total += $row[0];
      $var=!$var;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print "<tr $bc[$var]><td>Total</td>";
print '<td align="right">'.price($total).'</td></tr>';
print '</table>';

print '</td></tr><tr><td valign="top" width="70%">';

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/gain.mensuel.'.$year.'.png" alt="Gain mensuel" title="Gain mensuel">'."\n";
print '</td><td valign="top" width="30%">';
print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Mois</td><td align="right">Gain</td></tr>';

$sql = "SELECT sum(valeur),legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
$sql .= " WHERE graph = 'distributeur.gain.mensuel'";
$sql .= " AND legend like '".$year."%'";
$sql .= " GROUP BY legend DESC";  
$resql = $db->query($sql);
$total = 0;
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      print "<tr $bc[$var]><td>".$row[1].'</td>';  
      print '<td align="right">'.price($row[0]).'</td></tr>';
      $total += $row[0];
      $var=!$var;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print "<tr $bc[$var]><td>Total</td>";
print '<td align="right">'.price($total).'</td></tr>';
print '</table>';

print '</td></tr><tr><td valign="top" width="70%">';
print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/commission.mensuel.'.$year.'.png" alt="Commission mensuelle" title="Commission mensuelle">'."\n";

print '</td><td valign="top" width="30%">';

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Mois</td><td align="right">Commission</td></tr>';

$sql = "SELECT valeur,legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
$sql .= " WHERE graph = 'distributeur.commission.mensuel'";
$sql .= " AND legend like '".$year."%'";
$sql .= " GROUP BY legend DESC";  
$resql = $db->query($sql);
$total = 0;  
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      print "<tr $bc[$var]><td>".$row[1].'</td>';  
      print '<td align="right">'.price($row[0]).'</td></tr>';
      $total += $row[0];
      $var=!$var;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print "<tr $bc[$var]><td>Total</td>";
print '<td align="right">'.price($total).'</td></tr>';
print '</table>';

print '</td></tr><tr><td valign="top" width="70%">';

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=distributeurs/ca.mensuel.'.$year.'.png" alt="Ca mensuel" title="Ca mensuel">'."\n";
print '</td><td valign="top" width="30%">';
print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Mois</td><td align="right">Ca</td></tr>';

$sql = "SELECT sum(valeur),legend FROM ".MAIN_DB_PREFIX."telephonie_stats";  
$sql .= " WHERE graph = 'distributeur.ca.mensuel'";
$sql .= " AND legend like '".$year."%'";
$sql .= " GROUP BY legend DESC";  
$resql = $db->query($sql);
$total = 0;
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      print "<tr $bc[$var]><td>".$row[1].'</td>';  
      print '<td align="right">'.price($row[0]).'</td></tr>';
      $total += $row[0];
      $var=!$var;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print "<tr $bc[$var]><td>Total</td>";
print '<td align="right">'.price($total).'</td></tr>';
print '</table>';

print '</td></tr>';

print '</table>';
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
