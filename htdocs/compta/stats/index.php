<?php
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

/**
        \file        htdocs/compta/stats/index.php
        \brief       Page reporting CA
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

if ($modecompta=='CREANCES-DETTES') {
	$title="Chiffre d'affaire (".$conf->monnaie." HT)";
} else {
	$title="Chiffre d'affaire (".$conf->monnaie." TTC)";
}
$lien=($year_start?"<a href='index.php?year_start=".($year_start-1)."'>".img_previous()."</a> <a href='index.php?year_start=".($year_start+1)."'>".img_next()."</a>":"");
print_fiche_titre($title,$lien);


// Affiche règles de calcul
print "Ce rapport présente le CA:<br>\n";
if ($modecompta=="CREANCES-DETTES")
{
    print $langs->trans("RulesCADue");
    print '(Voir le rapport <a href="index.php?year_start='.($year_start).'&modecompta=RECETTES-DEPENSES">recettes-dépenses</a> pour n\'inclure que les factures effectivement payées).<br>';
    print '<br>';
}
else {
    print $langs->trans("RulesCAIn");
    print '(Voir le rapport en <a href="index.php?year_start='.($year_start).'&modecompta=CREANCES-DETTES">créances-dettes</a> pour inclure les factures non encore payée).<br>';
    print '<br>';
}

print '<br>';


if ($conf->compta->mode == 'CREANCES-DETTES') { 
	$sql = "SELECT sum(f.total) as amount, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.fk_statut = 1";
	$sql .= " AND f.paye = 1";
} else {
/*	$sql = "SELECT sum(f.total) as amount, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f ";
	$sql .= "left join ".MAIN_DB_PREFIX."paiement as p ";
	$sql .= "on f.rowid = p.fk_facture";*/
	$sql = "SELECT sum(p.amount) as amount, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p ";
	$sql .= "left join ".MAIN_DB_PREFIX."facture as f ";
	$sql .= "on f.rowid = p.fk_facture";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";


$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0; 
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $cum[$obj->dm] = $obj->amount;
        $i++;
    }
    $db->free($result);
}
else {
    dolibarr_print_error($db);
}

print '<table width="100%" class="noborder">';
print '<tr class="liste_titre"><td rowspan="2">'.$langs->trans("Month").'</td>';

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
$total_CA=0;
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

	  if ($annee == $year_current) {
	  	$total_CA += $cum[$case];
	  }
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
	  if ($cum[$caseprev]) {
	    $percent=(round(($cum[$case]-$cum[$caseprev])/$cum[$caseprev],4)*100);
	    print '<td align="right">'.($percent>=0?"+$percent":"$percent").'%</td>';
	  
	  }
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
print "<tr><td align=\"right\"><b>".$langs->trans("Total")." :</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print "<td align=\"right\"><b>".($total[$annee]?price($total[$annee]):"&nbsp;")."</b></td>";
  
  // Pourcentage evol
  if ($total[$annee-1]) {
    if ($annee <= $year_current) {
      if ($total[$annee-1]) {
	    $percent=(round(($total[$annee]-$total[$annee-1])/$total[$annee-1],4)*100);
	    print '<td align="right"><b>'.($percent>=0?"+$percent":"$percent").'%</b></td>';
        }
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
/* en mode recettes/dépenses, il faut compléter avec les montants facturés non réglés
* et les propales signées mais pas facturées
* en effet, en recettes-dépenses, on comptabilise lorsque le montant est sur le compte
* donc il est intéressant d'avoir une vision de ce qui va arriver
*/
if ($conf->compta->mode != 'CREANCES-DETTES') { 
/*
* 
* Facture non réglées
* 
*/

  $sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp, f.total_ttc, sum(pf.amount) as am";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
  $sql .= " WHERE s.idp = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  $sql .= " GROUP BY f.facnumber,f.rowid,s.nom, s.idp, f.total_ttc";   
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      
      if ($num)
	{
	  $var = True;
	  $total_ttc_Rac = $totalam_Rac = $total_Rac = 0;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      $total_ttc_Rac +=  $obj->total_ttc;
	      $totalam_Rac +=  $obj->am;
	      $i++;
	    }
	  $var=!$var;
	  print "<tr $bc[$var]><td align=\"right\" colspan=\"5\"><i>Facturé à encaisser : </i></td><td align=\"right\"><i>".price($total_ttc_Rac)."</i></td><td colspan=\"3\">&nbsp;</td></tr>";
	  $total_CA +=$total_ttc_Rac;
	}
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }  

/*
* 
* Propales signées, et non facturées
* 
*/
  $sql = "SELECT sum(f.total) as tot_fht,sum(f.total_ttc) as tot_fttc, p.rowid, p.ref, s.nom, s.idp, p.total_ht, p.total_ttc
			FROM ".MAIN_DB_PREFIX."commande AS p, llx_societe AS s
			LEFT JOIN ".MAIN_DB_PREFIX."co_fa AS co_fa ON co_fa.fk_commande = p.rowid
			LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON co_fa.fk_facture = f.rowid
			WHERE p.fk_soc = s.idp
					AND p.fk_statut >=1
					AND p.facture =0";
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
	$sql .= " GROUP BY p.rowid";

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      
      if ($num)
	{
	  $var = True;
	  $total_pr = 0;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      $total_pr +=  $obj->total_ttc-$obj->tot_fttc;
	      $i++;
	    }
	  $var=!$var;
	  print "<tr $bc[$var]><td align=\"right\" colspan=\"5\"><i>Signé : </i></td><td align=\"right\"><i>".price($total_pr)."</i></td><td colspan=\"3\">&nbsp;</td></tr>";
	  $total_CA += $total_pr;
	}
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }  
  print "<tr $bc[$var]><td align=\"right\" colspan=\"5\"><i>Total CA prévisionnel : </i></td><td align=\"right\"><i>".price($total_CA)."</i></td><td colspan=\"3\">&nbsp;</td></tr>";
}
print "</table>";
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
