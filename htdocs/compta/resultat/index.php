<?php
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

$year_current = $_GET["year"];
if (! $year_current) { $year_current = strftime("%Y", time()); }
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];

print_titre("Résultat comptable, résumé annuel");
print '<br>';

print "Ce rapport présente la balance entre les recettes et les dépenses facturées aux clients ou fournisseurs. Les dépenses de charges ne sont pas incluses.<br>\n";
if ($modecompta=="CREANCES-DETTES")
{
    print 'Il se base sur la date de validation des factures et inclut les factures dues, qu\'elles soient payées ou non';
    print ' (<a href="index.php?year='.$year.'&modecompta=RECETTES-DEPENSES">Voir le rapport sur les factures effectivement payées uniquement</a>).<br>';
    print '<br>';
}
else {
    print 'Il se base sur la date de validation des factures et n\'inclut que les factures effectivement payées';
    print ' (<a href="index.php?year='.$year.'&modecompta=CREANCES-DETTES">Voir le rapport en créances-dettes qui inclut les factures non encore payée</a>).<br>';
    print '<br>';
}

/*
 * Factures clients
 */
$sql = "SELECT sum(f.total) as amount, date_format(f.datef,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
if ($_GET["year"]) {
	$sql .= " AND f.datef between '".$_GET["year"]."-01-01 00:00:00' and '".$_GET["year"]."-12-31 23:59:59'";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
if ($modecompta != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
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
else {
	dolibarr_print_error($db);	
}

/*
 * Frais, factures fournisseurs.
 */
$sql = "SELECT sum(f.total_ht) as amount, date_format(f.datef,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f";
$sql .= " WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
if ($_GET["year"]) {
	$sql .= " AND f.datef between '".$_GET["year"]."-01-01 00:00:00' and '".$_GET["year"]."-12-31 23:59:59'";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
if ($modecompta != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
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
else {
	dolibarr_print_error($db);	
}

/*
 * Charges sociales
 */


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td rowspan=2>'.$langs->trans("Month").'</td>';


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
  print '<td align="center" width="20%" colspan="2"><a href="clientfourn.php?year='.$annee.'">'.$annee.'</a></td>';
}
print '</tr>';
print '<tr class="liste_titre">';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right">Recettes</td><td align="right">Dépenses</td>';
}
print '</tr>';

$var=True;
for ($mois = 1 ; $mois < 13 ; $mois++)
{
  $var=!$var;
  print '<tr '.$bc[$var].'>';
  print "<td>".strftime("%B",mktime(1,1,1,$mois,1,$annee))."</td>";
  for ($annee = $year_start ; $annee <= $year_end ; $annee++)
    {
      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($encaiss[$case]>0)
	{
	  print price($encaiss[$case]);
	  $totentrees[$annee]+=$encaiss[$case];
	}
      print "</td>";

      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($decaiss[$case]>0)
	{
	  print price($decaiss[$case]);
	  $totsorties[$annee]+=$decaiss[$case];
	}
      print "</td>";
    }

  print '</tr>';
}

$var=!$var;
print "<tr $bc[$var]><td><b>Total annuel</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right">'.price($totentrees[$annee]).'</td><td align="right">'.price($totsorties[$annee]).'</td>';
}
print "</tr>\n";

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
