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
require("./pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socidp = $user->societe_id;
}


function pt ($db, $sql, $title) {
  global $bc;

  print '<p><TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<TR class=\"liste_titre\">";
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


if ($sortfield == "")
{
  $sortfield="lower(p.label)";
}
if ($sortorder == "")
{
  $sortorder="ASC";
}

$in = "(1,2)";

print_titre ("CA Prévisionnel basé sur les propal <b>ouvertes</b> et <b>signées</b>");

print '<table width="100%">';

print '<tr><td valign="top">';

$sql = "SELECT sum(f.price - f.remise) as amount, date_format(f.datep,'%Y-%m') as dm";
$sql .= " FROM llx_propal as f WHERE fk_statut in $in";
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

pt($db, $sql, "Mois");

print '</td><td valign="top">';

$sql = "SELECT sum(f.price - f.remise) as amount, year(f.datep) as dm";
$sql .= " FROM llx_propal as f WHERE fk_statut in $in";
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

pt($db, $sql, "Année");

print "<P>";

$sql = "SELECT sum(f.price - f.remise) as amount, month(f.datep) as dm";
$sql .= " FROM llx_propal as f WHERE fk_statut in $in";
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm";

pt($db, $sql, "Mois cumulés");

print "</td></tr></table>";

$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
