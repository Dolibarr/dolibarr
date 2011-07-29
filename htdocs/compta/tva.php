<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: tva.php,v 1.12 2011/08/03 00:46:24 eldy Exp $
 */

/*	\deprecated	Ce fichier semble ne plus servir. A virer */

require('../main.inc.php');


/*
 *
 *
 */

function pt ($db, $sql, $date) {
  global $bc;

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows($result);
    $i = 0; $total = 0 ;
    print "<TABLE border=\"1\" width=\"100%\">";
    print "<TR class=\"liste_titre\">";
    print "<TD width=\"60%\">$date</TD>";
    print "<TD align=\"right\">Montant</TD>";
    print "<td>&nbsp;</td>\n";
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $obj = $db->fetch_object($result);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>$obj->dm</TD>\n";
      print "<TD align=\"right\">".price($obj->amount)."</TD><td>&nbsp;</td>\n";
      print "</TR>\n";

      $total = $total + $obj->amount;

      $i++;
    }
    print "<tr><td align=\"right\">".$langs->trans("TotalHT").":</td><td align=\"right\"><b>".price($total)."</b></td><td>".$langs->trans("Currency".$conf->monnaie)."</td></tr>";

    print "</table>";
    $db->free();
  }
}

/*
 *
 */

llxHeader();

$yearc = strftime("%Y",time());


echo '<table width="100%"><tr><td width="50%" valign="top">';

print "<b>TVA collect�e</b>";

for ($y = $yearc ; $y >= $conf->years ; $y=$y-1 ) {

  print "<table width=\"100%\">";
  print "<tr><td valign=\"top\">";

  $sql = "SELECT sum(f.tva) as amount , date_format(f.datef,'%Y-%m') as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1 AND f.datef >= '$y-01-01' AND f.datef <= '$y-12-31' ";
  $sql .= " GROUP BY dm DESC";

  pt($db, $sql,"Ann�e $y");

  print "</td></tr></table>";
}

echo '</td><td valign="top" width="50%">';
echo 'Tva Pay�e<br>';
echo '</td></tr></table>';


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date: 2011/08/03 00:46:24 $ r&eacute;vision $Revision: 1.12 $</em>");
?>
