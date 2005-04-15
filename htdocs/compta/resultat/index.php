<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file        htdocs/compta/resultat/index.php
        \brief       Page reporting resultat
        \version     $Revision$
*/

require("./pre.inc.php");

$year_start=isset($_GET["year_start"])?$_GET["year_start"]:$_POST["year_start"];
$year_current = strftime("%Y",time());
if (! $year_start) {
    $year_start = $year_current - 2;
    $year_end = $year_current;
}
else {
    $year_end=$year_start+2;   
}


llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socidp = $user->societe_id;
}

$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];


$title="Résultat exercice, résumé annuel";
$lien=($year_start?"<a href='index.php?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='index.php?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
print_fiche_titre($title,$lien);
print '<br>';

// Affiche règles de calcul
print "Cet état permet de faire un bilan des recettes et dépenses:<br>\n";
if ($modecompta=="CREANCES-DETTES")
{
    print $langs->trans("RulesResultDue");
    print '(Voir le rapport <a href="index.php?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">recettes-dépenses</a> pour n\'inclure que les factures effectivement payées).<br>';
    print '<br>';
}
else {
    print $langs->trans("RulesResultInOut");
    print '(Voir le rapport en <a href="index.php?year_start='.$year_start.'&modecompta=CREANCES-DETTES">créances-dettes</a> pour inclure les factures non encore payée).<br>';
    print '<br>';
}

/*
 * Factures clients
 */
if ($modecompta == 'CREANCES-DETTES') { 
    $sql  = "SELECT sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
} else {
	$sql  = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f";
	$sql .= " ON f.rowid = p.fk_facture";
	$sql .= " WHERE 1=1";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm";


$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0; 
    while ($i < $num)
    {
        $row = $db->fetch_object($result);
        $encaiss[$row->dm] = $row->amount_ht;
        $encaiss_ttc[$row->dm] = $row->amount_ttc;
        $i++;
    }
    $db->free($result);
}
else {
	dolibarr_print_error($db);	
}


/*
 * Frais, factures fournisseurs.
 */
if ($modecompta == 'CREANCES-DETTES') { 
    $sql  = "SELECT sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f";
    $sql .= " WHERE f.fk_soc = s.idp AND f.fk_statut = 1";
} else {
	$sql = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= " ON f.rowid = p.fk_facture_fourn";
	$sql .= " WHERE 1=1";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0; 
    while ($i < $num)
    {
        $row = $db->fetch_object($result);
        $decaiss[$row->dm] = $row->amount_ht;
        $decaiss_ttc[$row->dm] = $row->amount_ttc;
        $i++;
    }
    $db->free($result);
}
else {
	dolibarr_print_error($db);	
}


/*
 * Charges sociales non déductibles
 */

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
    $sql .= " WHERE s.fk_type = c.id AND c.deductible=0";
    if ($year) {
    	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle";
}
else {
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s, ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid AND s.fk_type = c.id AND c.deductible=0";
    if ($year) {
    	$sql .= " AND p.datep between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle";
}
$result=$db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $var=false;
    $i = 0;
    if ($num) {    
      while ($i < $num) {
        $obj = $db->fetch_object($result);
    
        $decaiss[$row->dm] += $row->amount_ht;
        $decaiss_ttc[$row->dm] += $row->amount_ttc;
    
        $i++;
      }
    }
} else {
  dolibarr_print_error($db);
}


/*
 * Charges sociales déductibles
 */

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
    $sql .= " WHERE s.fk_type = c.id AND c.deductible=1";
    if ($year) {
    	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle DESC";
}
else {
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s, ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid AND s.fk_type = c.id AND c.deductible=1";
    if ($year) {
    	$sql .= " AND p.datep between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle";
}
$result=$db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $var=false;
    $i = 0;
    if ($num) {
        while ($i < $num) {
        $obj = $db->fetch_object($result);
        
        $decaiss[$row->dm] += $row->amount_ht;
        $decaiss_ttc[$row->dm] += $row->amount_ttc;
        
        $i++;
        }
    }
} else {
  dolibarr_print_error($db);
}



/*
 * Affiche tableau
 */

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td rowspan=2>'.$langs->trans("Month").'</td>';

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
      if ($encaiss_ttc[$case]>0)
	{
	  print price($encaiss_ttc[$case]);
	  $totentrees[$annee]+=$encaiss_ttc[$case];
	}
      print "</td>";

      print '<td align="right" width="10%">&nbsp;';
      $case = strftime("%Y-%m",mktime(1,1,1,$mois,1,$annee));
      if ($decaiss_ttc[$case]>0)
	{
	  print price($decaiss_ttc[$case]);
	  $totsorties[$annee]+=$decaiss_ttc[$case];
	}
      print "</td>";
    }

  print '</tr>';
}

$var=!$var;
print "<tr $bc[$var]><td><b>".$langs->trans("TotalTTC")."</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right">'.price($totentrees[$annee]).'</td><td align="right">'.price($totsorties[$annee]).'</td>';
}
print "</tr>\n";

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
