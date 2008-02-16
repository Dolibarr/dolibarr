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
 */

/**
        \file        htdocs/compta/stats/exercices.php
        \brief       Page ???
        \version     $Id$
*/
 
require("./pre.inc.php");
require("./lib.inc.php");

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}



llxHeader();

print_titre("Comparatif CA année en cours avec année précédente (".$langs->trans("Currency".$conf->monnaie)." HT, ".$modecompta.")");
print "<br>\n";


function factures ($db, $year, $month, $paye)
{
  global $bc,$langs;

  $sql = "SELECT s.nom, s.rowid as socid, f.facnumber, f.total as amount,".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid AND f.paye = ".$paye;
  $sql .= " AND date_format(f.datef, '%Y') = ".$year;
  $sql .= " AND round(date_format(f.datef, '%m')) = ".$month;
  $sql .= " ORDER BY f.datef DESC ";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num > 0) {
      $i = 0;
      print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
      print "<TR bgcolor=\"orange\"><td colspan=\"3\"><b>Factures</b></td></tr>";
      print "<TR bgcolor=\"orange\">";
      print "<TD>Societe</td>";
      print "<TD>Num</TD>";
      print "<TD align=\"right\">Date</TD>";
      print "<TD align=\"right\">Montant</TD>";
      print "<TD align=\"right\">Payé</TD>";
      print "</TR>\n";
      $var=True;
      while ($i < $num) {
	$objp = $db->fetch_object($result);
	$var=!$var;
	print "<TR $bc[$var]>";
	print "<TD><a href=\"comp.php?socid=".$objp->socid."\">".$objp->nom."</a></TD>\n";
	print "<TD><a href=\"facture.php?facid=".$objp->facid."\">".$objp->facnumber."</a></TD>\n";
	if ($objp->df > 0 ) {
	  print "<TD align=\"right\">".dolibarr_print_date($objp->df)."</TD>\n";
	} else {
	  print "<TD align=\"right\"><b>!!!</b></TD>\n";
	}
	
	print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
	
	$payes[1] = "oui";
	$payes[0] = "<b>non</b>";
	
	
	print "<TD align=\"right\">".$payes[$objp->paye]."</TD>\n";
	print "</TR>\n";
	
	$total = $total + $objp->amount;
	
	$i++;
      }
      print "<tr><td  align=\"right\"><b>".$langs->trans("Total")." : ".price($total)."</b></td><td></td></tr>";
      print "</TABLE>";
      $db->free();
    }
  }
}


function pt ($db, $sql, $year) {
  global $bc,$langs;

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0 ;
    print '<table class="border" width="100%">';
    print "<tr class=\"liste_titre\">";
    print '<td>'.$langs->trans("Month").'</td>';
    print '<td align="right">'.$langs->trans("Amount").'</td></tr>';
    $var=True;
    $month = 1 ;

    while ($i < $num) {
      $obj = $db->fetch_object($result);
      $var=!$var;

      if ($obj->dm > $month ) {
        	for ($b = $month ; $b < $obj->dm ; $b++) {
        	  print "<tr $bc[$var]>";
        	  print "<td>".strftime("%B",mktime(12,0,0,$b, 1, $year))."</td>\n";
        	  print "<td align=\"right\">0</td>\n";	  
        	  print "</tr>\n";
        	  $var=!$var;
        	  $ca[$b] = 0;
        	}
      }

      if ($obj->sum > 0) {
	print "<tr $bc[$var]>";
	print "<td>";
	print strftime("%B",mktime(12,0,0,$obj->dm, 1, $year))."</td>\n";
	print "<td align=\"right\">".price($obj->sum)."</td>\n";
	
	print "</tr>\n";
	$month = $obj->dm + 1;
	$ca[$obj->dm] = $obj->sum;
	$total = $total + $obj->sum;
      }
      $i++;
    }

    if ($num) {
      $beg = $obj->dm;
    } else {
      $beg = 1 ;
    }

    if ($beg <= 12 ) {
      for ($b = $beg + 1 ; $b < 13 ; $b++) {
	$var=!$var;
	print "<tr $bc[$var]>";
	print "<td>".strftime("%B",mktime(12,0,0,$b, 1, $year))."</td>\n";
	print "<td align=\"right\">0</td>\n";	  
	print "</tr>\n";
	$ca[$b] = 0;
      }
    }

    print "<tr class=\"total\"><td align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td></tr>";    
    print "</table>";

    $db->free();
    return $ca;
  } else {
    print $db->error();
  }
}

function ppt ($db, $year, $socid)
{
  global $bc,$conf,$langs;
  print "<table width=\"100%\">";

  print "<tr class=\"liste_titre\"><td align=\"center\" width=\"30%\">";
  print "CA ".($year - 1);
  
  print "</td><td align=\"center\">CA $year</td>";
  print '<td align="center">Delta</td></tr>';
  print "<tr><td valign=\"top\" width=\"30%\">";
  
  $sql = "SELECT sum(f.total) as sum, round(date_format(f.datef, '%m')) as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
  $sql .= " WHERE f.fk_statut in (1,2)";
  $sql .= " AND date_format(f.datef,'%Y') = ".($year-1);
    
    if ($conf->compta->mode != 'CREANCES-DETTES') { 
    	$sql .= " AND f.paye = 1";
    }
    if ($socid)
    {
      $sql .= " AND f.fk_soc = $socid";
    }
  $sql .= " GROUP BY dm";
  
  $prev = pt($db, $sql, $year - 1);
  
  print "</td><td valign=\"top\" width=\"30%\">";
  
  $sql = "SELECT sum(f.total) as sum, round(date_format(f.datef, '%m')) as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
  $sql .= " WHERE f.fk_statut in (1,2)";
  $sql .= " AND date_format(f.datef,'%Y') = $year ";
  if ($conf->compta->mode != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
  }
  if ($socid)
    {
      $sql .= " AND f.fk_soc = $socid";
    }
  $sql .= " GROUP BY dm";
  
  $ca = pt($db, $sql, $year);
  
  print "</td><td valign=\"top\" width=\"30%\">";
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
  print "<tr class=\"liste_titre\">";
  print '<td>'.$langs->trans("Month").'</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print '<td align="right">Cumul</td>';
  print "</tr>\n";

  $var = 1 ;
  for ($b = 1 ; $b <= 12 ; $b++)
    {
      $var=!$var;

      $delta = $ca[$b] - $prev[$b];
      $deltat = $deltat + $delta ;
      print "<TR $bc[$var]>";
      print "<TD>".strftime("%B",mktime(12,0,0,$b, 1, $year))."</TD>\n";
      print "<TD align=\"right\">".price($delta)."</TD>\n";
      print "<TD align=\"right\">".price($deltat)."</TD>\n";
      print "</TR>\n";
    }


  print '<tr class="total"><td align="right">Total :</td><td align="right"><b>'.price($deltat).'<b></td></tr>';

  print '</table>';
  print '</td></tr></table>';

}


$cyear = strftime ("%Y", time());
ppt($db, $cyear, $socid);

$db->close();


llxFooter('$Date$ r&eacute;vision $Revision$');
?>
