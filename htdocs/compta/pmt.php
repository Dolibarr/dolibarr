<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php");


/*
 *
 */

llxHeader();

print "Point mort";



if ($action == 'update') {
  $datepm = mktime(12, 0 , 0, $pmonth, 1, $pyear); 

  $sql = "DELETE FROM ".MAIN_DB_PREFIX."pointmort WHERE int(month) = $datepm ;";
  $sql .= "INSERT INTO ".MAIN_DB_PREFIX."pointmort VALUES ($datepm, $pm)";
  $result = $db->query($sql);
}



print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\">";
print "<tr><td valign=\"top\">";

$sql = "SELECT amount, int(month) as dm FROM ".MAIN_DB_PREFIX."pointmort ORDER BY month DESC";

$result = $db->query($sql);

if ($result) {



  print "<table class=\"noborder\" cellspacing=\"0\" cellpadding=\"3\">";
  print "<tr bgcolor=\"orange\">";
  print "<td>".$langs->trans("Month")."</td>";
  print "<td align=\"right\">Montant</td>";
  print "</tr>\n";

  $bc[0]="bgcolor=\"#90c090\"";
  $bc[1]="bgcolor=\"#b0e0b0\"";
  $var = 1 ; 
  
  $i = 0;
  $num = $db->num_rows();
  while ($i < $num) {
  $obj = $db->fetch_object( $i);
    $var=!$var;
    
    print "<TR $bc[$var]>";
    
    print "<TD>".strftime("%Y %B",$obj->dm)."</TD>\n";
    print "<TD align=\"right\">$obj->amount</TD>\n";


    print "</TR>\n";


    $i++;
  }

  print "</table>";
}

print "</td><td valign=\"top\">";

print '<form action="pmt.php" method="post">';
print "<input type=\"hidden\" name=\"action\" value=\"update\">";

$strmonth[1] = "Janvier";
$strmonth[2] = "F&eacute;vrier";
$strmonth[3] = "Mars";
$strmonth[4] = "Avril";
$strmonth[5] = "Mai";
$strmonth[6] = "Juin";
$strmonth[7] = "Juillet";
$strmonth[8] = "Ao&ucirc;t";
$strmonth[9] = "Septembre";
$strmonth[10] = "Octobre";
$strmonth[11] = "Novembre";
$strmonth[12] = "D&eacute;cembre";

print "Date :";
$cmonth = date("n", time());
print "<select name=\"pmonth\">";    
for ($month = 1 ; $month <= 12 ; $month++) {
  if ($month == $cmonth) {
    print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
  } else {
    print "<option value=\"$month\">" . $strmonth[$month];
  }
}
print "</select>";

print "<select name=\"pyear\">";
$syear = date("Y", time() ) ;
print "<option value=\"".($syear-1)."\">".($syear-1);
print "<option value=\"$syear\" SELECTED>$syear";

for ($year = $syear +1 ; $year < $syear + 5 ; $year++) {
  print "<option value=\"$year\">$year";
}
print "</select><br>";

print "Valeur : <input type=\"text\" name=\"pm\"><br>";
print "<input type=\"submit\" value=\"Mettre a jour\">";
print "</form>";




print "</td></tr></table>";

$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
