<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");
require("../../tva.class.php3");

/*
 *
 */

llxHeader();

$tva = new Tva($db);

print_titre("Réglements TVA");

$sql = "SELECT amount, date_format(f.datev,'%d-%M-%Y') as dm";
$sql .= " FROM llx_tva as f ";
$sql .= " ORDER  BY dm DESC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0; 
  $total = 0 ;
  print '<p><TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
  print '<TR class="liste_titre">';
  print "<TD width=\"60%\">Date</TD>";
  print "<TD align=\"right\">Montant</TD>";
  print "<td>&nbsp;</td>\n";
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>$obj->dm</TD>\n";
      $total = $total + $obj->amount;
      
      print "<TD align=\"right\">".price($obj->amount)."</td><td>&nbsp;</td>";
      print "</TR>\n";
      
      $i++;
    }
  print "<tr><td align=\"right\">Total :</td>";
  print "<td align=\"right\"><b>".price($total)."</b></td><td>euros&nbsp;HT</td></tr>";
  
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error();
}
  

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
