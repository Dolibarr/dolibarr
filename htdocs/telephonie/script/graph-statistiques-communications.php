<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Generation des graphiques
 *
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

$nbval = 14;

/*
 * Remplacé par ...statistiques-fournisseurs.php
 *
 $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
 $sql .= " WHERE graph = 'communications.duree'";

 $resql = $db->query($sql);

 $sql = "SELECT date_format(date, '%m'), sum(duree), count(duree)";
 $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
 $sql .= " GROUP BY date_format(date, '%Y%m') ASC"; 
 $resql = $db->query($sql);

 _deal($db, $resql, "communications.duree", $kilomindurees, $labels);

 $file = $img_root . "communications/duree.png";
 $graphgain = new GraphBar ($db, $file);
 $graphgain->show_console = 0 ;
 $graphgain->width = 480 ;
 $graphgain->titre = "Nb minutes (milliers)";
 print $graphgain->titre."\n";
 $graphgain->GraphDraw($file, $kilomindurees, $labels);
*/

/*
 *
 *
 */

$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
$sql .= " WHERE graph = 'communications.duree_mobiles'";

$resql = $db->query($sql);

$sql = "SELECT date_format(date, '%m'), sum(duree), count(duree)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " WHERE numero like '06%'";
$sql .= " GROUP BY date_format(date, '%Y%m') ASC";

$resql = $db->query($sql);

if ($resql)
{
  $durees = array();
  $kilomindurees = array();
  $labels = array();

  $num = $db->num_rows($resql);
  $lim = ($num - $nbval);
  $i = 0;
  $j = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      if ($i >= $lim )
	{
	  $labels[$j] = $row[0];
	  $durees[$j] = $row[1];
	  $kilomindurees_mob[$j] = ($row[1]/60000);
	  
	  $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
	  $sqli .= " (graph, ord, legend, valeur) VALUES (";
	  $sqli .= "'communications.duree_mobiles','".$j."','".$row[0]."','".($row[1]/60)."')";
	  
	  if (!$resqli = $db->query($sqli))
	    {
	      print $db->error();
	    }
	  $j++;
	}
      $i++;
    }
}

$file = $img_root . "communications/duree_mob.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->width = 480 ;
$graphgain->titre = "Nb minutes -> portables (milliers)";
print $graphgain->titre."\n";
$graphgain->GraphDraw($file, $kilomindurees_mob, $labels);

function _deal($db, $resql, $graph, &$data, &$labels)
{
  global $nbval;

  if ($resql)
    {      
      $num = $db->num_rows($resql);
      $lim = ($num - $nbval);
      $i = 0;
      $j = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  
	  if ($i >= $lim )
	    {
	      $labels[$j] = $row[0];
	      $data[$j] = ($row[1]/60000);
	      
	      $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
	      $sqli .= " (graph, ord, legend, valeur) VALUES (";
	      $sqli .= "'".$graph."','".$j."','".$row[0]."','".($row[1]/60)."')";
	      
	      if (!$resqli = $db->query($sqli))
		{
		  print $db->error();
		}
	      $j++;
	    }
	  $i++;
	}
    }   
}
?>
