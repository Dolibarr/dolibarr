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

function pt ($db, $sql, $title) 
{
  $bc[0]='bgcolor="#90c090"';
  $bc[1]='bgcolor="#b0e0b0"';

  print '<p><TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<TR bgcolor=\"orange\">";
  print "<TD>$title</TD>";
  print "<TD align=\"right\">Montant</TD>";
  
  $result = $db->query($sql);
  if ($result) 
    {
      $num = $db->num_rows();
      $i = 0; $total = 0 ;
    
      print "</TR>\n";
      $var=True;
      while ($i < $num) 
	{
	  $obj = $db->fetch_object( $i);
	  $var=!$var;
	  print '<TR '.$bc[$var].'>';
	  print '<TD>'.$obj->dm.'</TD>';
	  print '<TD align="right">'.price($obj->amount).'</TD>';
	  
	  print "</TR>\n";
	  $total = $total + $obj->amount;
	  $i++;
	}
      print "<tr><td colspan=\"2\" align=\"right\"><b>Total : ".price($total)."</b> euros HT</td></tr>";
    
      $db->free();
    } 
  else 
    {
      print "<tr><td>".$db->error() . "</td></tr>";

    }
  print "</TABLE>";
      
}
/*
 *
 */

llxHeader();


$db = new Db();
if ($sortfield == "") {
  $sortfield="lower(p.label)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}

$in = "(1,2)";
//$in = "(3)";

print "<P>CA Prévisionnel basé sur les propal <b>ouvertes</b> et <b>signées</b>";

print '<table width="100%">';

print '<tr><td valign="top">';

$sql = "SELECT sum(f.price) as amount, date_format(f.datep,'%Y-%m') as dm";
$sql .= " FROM llx_propal as f WHERE fk_statut in $in";
$sql .= " GROUP BY dm DESC";

pt($db, $sql, "Mois");

print '</td><td valign="top">';

$sql = "SELECT sum(f.price) as amount, year(f.datep) as dm";
$sql .= " FROM llx_propal as f WHERE fk_statut in $in";
$sql .= " GROUP BY dm DESC";

pt($db, $sql, "Année");

print "<P>";

$sql = "SELECT sum(f.price) as amount, month(f.datep) as dm";
$sql .= " FROM llx_propal as f WHERE fk_statut in $in";
$sql .= " GROUP BY dm";

pt($db, $sql, "Mois cumulés");

print "</td></tr></table>";

$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
