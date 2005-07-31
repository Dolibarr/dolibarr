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
 */

require ("../../htdocs/master.inc.php");

include_once (JPGRAPH_DIR."jpgraph.php");
include_once (JPGRAPH_DIR."jpgraph_line.php");
include_once (JPGRAPH_DIR."jpgraph_bar.php");
include_once (JPGRAPH_DIR."jpgraph_pie.php");
include_once (JPGRAPH_DIR."jpgraph_error.php");
include_once (JPGRAPH_DIR."jpgraph_canvas.php");

$error = 0;

$labels = array();
$datas = array();

$datetime = time();
$month = strftime("%m", $datetime);
$year = strftime("%Y", $datetime);

if ($month == 1)
{
  $monthprev = "12";
  $yearprev = $year - 1;
}
else
{
  $monthprev = substr("00".($month - 1), -2) ;
}

if ($month == 12)
{
  $monthnext = "01";
  $yearnext = $year + 1;
}
else
{
  $monthnext = substr("00".($month + 1), -2) ;
}

$sql = "SELECT distinct(fk_account)";
$sql .= " FROM ".MAIN_DB_PREFIX."bank";
$sql .= " WHERE date_format(datev,'%Y%m') = '".$year.$month."'";

$resql = $db->query($sql);

$amounts = array();

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      array_push($accounts, $row[0]);
      $i++;
    }

}
  
$account = 1; 

foreach ($accounts as $account)
{




  $sql = "SELECT sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND datev < '".$year."-".$month."-01';";
  
  $resql = $db->query($sql);
  if ($resql)
  {
    $row = $db->fetch_row($resql);
    $solde = $row[0];
  }
  else
    {
      print $sql ;
    }


$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
$sql .= " FROM ".MAIN_DB_PREFIX."bank";
$sql .= " WHERE fk_account = ".$account;
$sql .= " AND date_format(datev,'%Y%m') = '".$year.$month."'";
$sql .= " GROUP BY date_format(datev,'%Y%m%d');";

$resql = $db->query($sql);

$amounts = array();

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $amounts[$row[0]] = $row[1];
      $i++;
    }

}
else
{
  print $sql ;
}

$subtotal = 0;


$day = mktime(1,1,1,$month,1,$year);

$xmonth = substr("00".strftime("%m",$day), -2);
$i = 0;
while ($xmonth == $month)
{
  //print strftime ("%e %d %m %y",$day)."\n";

  $subtotal = $subtotal + $amounts[strftime("%Y%m%d",$day)];

  $datas[$i] = $solde + $subtotal;
  $labels[$i] = strftime("%d",$day);

  $day += 86400;
  $xmonth = substr("00".strftime("%m",$day), -2);
  $i++;
}

	      
$width = 750;
$height = 350;
	      
$graph = new Graph($width, $height,"auto");    
$graph->SetScale("textlin");

$graph->yaxis->scale->SetGrace(2);
$graph->SetFrame(1);
$graph->img->SetMargin(40,20,20,35);

$b2plot = new BarPlot($datas);

$b2plot->SetColor("blue");
//$b2plot->SetWeight(2);

$graph->title->Set("Solde");

$graph->xaxis->SetTickLabels($labels);   
//$graph->xaxis->title->Set(strftime("%d/%m/%y %H:%M:%S", time()));

$graph->Add($b2plot);
$graph->img->SetImgFormat("png");

$file= DOL_DATA_ROOT."/graph/banque/solde.$account.png";

$graph->Stroke($file);
}

?>
