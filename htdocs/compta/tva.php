<?PHP
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
 *
 */

function pt ($db, $sql, $date) {
  global $bc; 

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0 ;
    print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
    print "<TR class=\"liste_titre\">";
    print "<TD width=\"60%\">$date</TD>";
    print "<TD align=\"right\">Montant</TD>";
    print "<td>&nbsp;</td>\n";
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>$obj->dm</TD>\n";
      print "<TD align=\"right\">".price($obj->amount)."</TD><td>&nbsp;</td>\n";
      print "</TR>\n";
      
      $total = $total + $obj->amount;
      
      $i++;
    }
    print "<tr><td align=\"right\">".$langs->trans("TotalHT").":</td><td align=\"right\"><b>".price($total)."</b></td><td>".MAIN_MONNAIE."</td></tr>";
    
    print "</TABLE>";
    $db->free();
  }
}

/*
 *
 */

llxHeader();

$yearc = strftime("%Y",time());


echo '<table width="100%"><tr><td width="50%" valign="top">';

print "<b>TVA collectée</b>";

for ($y = $yearc ; $y >= $conf->years ; $y=$y-1 ) {

  print "<table width=\"100%\">";
  print "<tr><td valign=\"top\">";

  $sql = "SELECT sum(f.tva) as amount , date_format(f.datef,'%Y-%m') as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1 AND f.datef >= '$y-01-01' AND f.datef <= '$y-12-31' ";
  $sql .= " GROUP BY dm DESC";
  
  pt($db, $sql,"Année $y");
  
  print "</td></tr></table>";
}

echo '</td><td valign="top" width="50%">';
echo 'Tva Payée<br>';
echo '</td></tr></table>';


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
