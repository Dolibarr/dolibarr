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
 * Generation des graphiques sur les founisseurs
 *
 *
 *
 */
print strftime("%H:%M:%S",time())."\n";
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

$Tfourn = array();
$sql = "SELECT rowid, nom";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_fournisseur";
$resql = $db->query($sql);

if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $Tfourn[$row[0]] = $row[1];
    }
}

$sql = "SELECT distinct date_format(date, '%m%Y')";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";

$resql = $db->query($sql);

if ($resql)
{
  $Tdate = array();
  while ($row = $db->fetch_row($resql))
    {
      array_push($Tdate, $row[0]);
    }
}

$sql = "SELECT fk_fournisseur, date_format(date, '%m%Y'), duree, numero";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";

$resql = $db->query($sql);

if ($resql)
{
  $Tinter = array();
  $Tnatio = array();
  $Tmobil = array();

  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      if (substr($row[3],0,2) == '00')
	{
	  $Tinter[$row[0]][$row[1]] += $row[2];
	}
      elseif (substr($row[3],0,2) == '06')
	{
	  $Tmobil[$row[0]][$row[1]] += $row[2];
	}
      else
	{
	  $Tnatio[$row[0]][$row[1]] += $row[2];
	}

      $i++;
    }
}

$graphs = array("inter","natio","mobil");
$colors = array("yellow","red","blue","pink","orange","green");
foreach($graphs as $graph)
{
  $datas = array();

  $tab = "T".$graph;
  foreach ($$tab as $key => $value)
    {

      $j = 0;
      foreach($Tdate as $date)
	{
	  $datas[$key][$j] = ($value[$date]/60);
	  $j++;
	}
      
    }
  $file = $img_root . "communications/fourn_".$graph.".png";

  $graph = new Graph(640,480,"auto");    
  $graph->SetScale("textlin");
  $graph->yaxis->scale->SetGrace(20);
  $graph->xaxis->scale->SetGrace(20);  
  $graph->img->SetMargin(40,20,20,40);
  $graph->legend->Pos(0.10,0.12,"left","center");
  $i=0;
  $plots = array();
  foreach ($$tab as $key => $value)
    {
      $bplot = new BarPlot($datas[$key]);
      $bplot->SetFillColor($colors[$i]);
      $bplot->SetLegend($Tfourn[$key]);
      array_push($plots, $bplot);
      $i++;
    }
   
    $gbplot = new GroupBarPlot($plots);

    $graph->Add($gbplot);
    
    $graph->title->Set("Nombre de minutes par fournisseurs");
    $graph->xaxis->SetTickLabels($Tdate);
    
    $graph->img->SetImgFormat("png");
    $graph->Stroke($file);
}
print strftime("%H:%M:%S",time())."\n";
?>
