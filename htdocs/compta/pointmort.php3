<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");

function pt ($db, $sql) {
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; 
    $total = 0 ;
    $month = 1 ;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);

      $ca[$obj->dm] = $obj->sum;
      
      $i++;
    }

    $db->free();
    return $ca;
  }
}

function pm ($db) {
  $sql = "SELECT amount, date_format('%Y%m',month) as dm FROM llx_pointmort";
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0 ;

    $month = 1 ;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);

      $ca[$obj->dm] = $obj->amount;
      
      print $obj->dm ."=". $obj->amount ."<br>";

      $i++;
    }

    $db->free();
    return $ca;
  }
}


function ppt ($db) {
    
  $sql = "SELECT sum(f.amount), date_format(f.datef,'%Y%m') as dm";
  $sql .= " FROM llx_facture as f WHERE f.paye = 1";
  $sql .= " GROUP BY dm";
  
  $ca = pt($db, $sql);
  $ptmt = pm($db);
  
  
  print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
  print "<TR bgcolor=\"orange\">";
  print "<TD>&nbsp;</TD>";
  print "<TD>Mois</TD>";
  print "<TD align=\"right\">CA</TD>";
  print "<TD align=\"right\">Point mort</TD>";
  print "<TD align=\"right\">Delta</TD>";
  print "<TD align=\"right\">Somme</TD>";
  print "<TD align=\"right\">Somme par an</TD>";
  print "<TD align=\"right\">Somme / nb mois</TD>";
  print "<TD align=\"right\">Ecart</TD>";
  print "</TR>\n";
  $bc[0]="bgcolor=\"#90c090\"";
  $bc[1]="bgcolor=\"#b0e0b0\"";
  $var = 1 ; 
  $pmt = 0 ;
  $subpmt = 0 ; $totalca = 0 ;
  $totalpm = 0;
  $xdate = mktime(0, 0, 0, 7 , 1, 2000);
  $oldyear = 0;
  $i = 1;
  while ($xdate < time()) {
    if ($oldyear <> date("Y",$xdate)) {
      $oldyear = date("Y",$xdate);
      $subpmt = 0 ;
      print '<TR><td>&nbsp;</td><td>'.$oldyear.'</td>';
      print '<td colspan="7" align="center">&nbsp;</td></tr>';
    }

    $var=!$var;
    
    print "<TR $bc[$var]><td>$i</td>";
    print "<TD>".strftime("%Y %B", $xdate)."</TD>\n";

    $b = strftime("%Y", $xdate) .  strftime("%m", $xdate);
    $totalca = $ca[$b] + $totalca;
    $totalpm = $ptmt[$b] + $totalpm;
    $pm = $ca[$b] - $ptmt[$b];
    $pmt = $pmt + $pm;
    $subpmt = $subpmt + $pm;

    if ($ca[$b]) {
      print "<TD align=\"right\">".$ca[$b]."</TD>\n";
    } else {
      print "<TD align=\"right\">0</TD>\n";
    }

    print "<TD align=\"right\">".$ptmt[$b]."</TD>\n";
    if ($pm > 0) {
      print "<TD align=\"right\"><b>+$pm</b></TD>\n";
    } else {
      print "<TD align=\"right\">$pm</TD>\n";
    }
    print "<TD align=\"right\">$pmt</TD>\n";
    print "<TD align=\"right\">$subpmt</TD>\n";

    $pmbymonth = round($pmt/$i);

    print "<TD align=\"right\">$pmbymonth</TD>\n";

    $pmbymdelta = ($pmbymonth - $pmbymontha);

    if ( $pmbymdelta > 0 ) {
      print "<TD align=\"right\"><b> +$pmbymdelta</b></TD>\n";
    } else {
      print "<TD align=\"right\">$pmbymdelta</TD>\n";
    }

    $pmbymontha = $pmbymonth;

    print "</TR>\n";

    $xdate = mktime(0, 0, 0, date("m", $xdate + (33 * 24 * 3600)), 1 , date("Y", $xdate + (33 * 24 * 3600))) ;
    $i++;
  }
  print "<tr><td colspan=\"2\" align=\"right\">Totaux en euros :</td><td align=\"right\">$totalca</td>";
  print "<td align=\"right\">$totalpm</td><td align=\"right\" bgcolor=\"#f0f0f0\">$pmt</td>";
  print "<td colspan=\"4\">&nbsp;</td></tr>";
  print "<tr><td colspan=\"2\" align=\"right\">Totaux en francs :</td><td align=\"right\">".francs($totalca)."</td>";
  print "<td align=\"right\">".francs($totalpm)."</td>";
  print "<td align=\"right\">".francs($pmt)."</td>";
  print "<td colspan=\"4\">&nbsp;</td></tr>";
  print "</table>";
}


/*
 *
 */

llxHeader();

print "<b>Point mort</b>";



ppt($db, 0);


print "<br><br><br><table cellspacing=0 border=1 cellpadding=3>";
print "<tr><td bgcolor=\"#e0e0e0\"><a href=\"pmt.php3\">Paramétrer le point mort</a></td></tr>";
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
