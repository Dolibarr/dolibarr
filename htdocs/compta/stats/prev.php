<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}


function pt ($db, $sql, $title) {
  global $bc;
  global $langs,$conf;

  print '<table class="border" width="100%">';
  print '<tr class="liste_titre">';
  print "<td>$title</td>";
  print "<td align=\"right\">Montant</td>";
  print "</tr>\n";
  
  $result = $db->query($sql);
  if ($result) 
    {
      $num = $db->num_rows();
      $i = 0; $total = 0 ;
    
      $var=True;
      while ($i < $num) 
	{
	  $obj = $db->fetch_object($result);
	  $var=!$var;
	  print '<tr '.$bc[$var].'>';
	  print '<td>'.$obj->dm.'</td>';
	  print '<td align="right">'.price($obj->amount).'</td>';
	  
	  print "</tr>\n";
	  $total = $total + $obj->amount;
	  $i++;
	}
      print "<tr class=\"total\"><td colspan=\"2\" align=\"right\"><b>".$langs->trans("TotalHT").": ".price($total)."</b> ".$langs->trans("Currency".$conf->monnaie)."</td></tr>";
    
      $db->free();
    } 
  else 
    {
      dol_print_error($db);

    }
  print "</table>";
      
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

$in = "(1,2,4)";

print_titre ("CA Prévisionnel basé sur les propositions <b>ouvertes</b> et <b>signées</b>");

print '<table width="100%">';

print '<tr><td valign="top">';

$sql = "SELECT sum(f.price) as amount, date_format(f.datep,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as f WHERE fk_statut in $in";
if ($socid)
{
  $sql .= " AND f.fk_soc = $socid";
}
$sql .= " GROUP BY dm DESC";

pt($db, $sql, $langs->trans("Month"));

print '</td><td valign="top">';

$sql = "SELECT sum(f.price) as amount, year(f.datep) as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as f WHERE fk_statut in $in";
if ($socid)
{
  $sql .= " AND f.fk_soc = $socid";
}
$sql .= " GROUP BY dm DESC";

pt($db, $sql, "Année");

print "<br>";

$sql = "SELECT sum(f.price) as amount, month(f.datep) as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as f WHERE fk_statut in $in";
if ($socid)
{
  $sql .= " AND f.fk_soc = $socid";
}
$sql .= " GROUP BY dm";

pt($db, $sql, "Mois cumulés");

print "</td></tr></table>";

$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
