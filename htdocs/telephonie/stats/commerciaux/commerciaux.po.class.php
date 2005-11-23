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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/graph.class.php");

class GraphCommerciauxPO  {

  Function GraphCommerciauxPO($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Prises d'ordre mensuelle";
    $this->showframe = true;
    $this->barcolor = array("green","blue","yellow","pink","orange");
    $this->commerciaux = array(2,5,7,18);
  }

  Function GraphMakeGraph()
  {
    $width = 800;
    $height = 300;
    $graph = new Graph($width, $height,"auto");    
    $graph->SetScale("textlin");

    $graph->yaxis->scale->SetGrace(20);
    $graph->SetFrame($this->showframe);
    
    $graph->img->SetMargin(50,120,20,35);
    $gbspl = array();
    $i = 0;

    $sql = "SELECT rowid, firstname, name";
    $sql .= " FROM ".MAIN_DB_PREFIX."user";
    $resql = $this->db->query($sql);
    $comm_names = array();
    if ($resql)
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    $comm_names[$row[0]]= $row[1];//." ".$row[2];
	  }
      }
    
    foreach ($this->commerciaux as $commercial)
      {
	$datas = array();
	$xdatas = array();
	$sql = "SELECT date_format(datepo, '%m'), sum(montant)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
	$sql .= " WHERE p.fk_commercial = ".$commercial;
	$sql .= " GROUP BY date_format(datepo, '%Y%m')";
	$resql = $this->db->query($sql);
	
	if ($resql)
	  {
	    while ($row = $this->db->fetch_row($resql))
	      {
		$xdatas[$row[0]]= $row[1];
	      }
	  }
	else
	  {
	    print $sql;
	  }

	for ($j = 0; $j < 12 ; $j++)
	  {
	    $datas[$j] = $xdatas[substr("00".$j,-2)];
	  }

	$bplot = new BarPlot($datas);
	$bplot->SetFillColor($this->barcolor[$i]);
	$bplot->SetLegend($comm_names[$commercial]);
	
	array_push($gbspl, $bplot);
	$i++;
      }
    
    $gbplot =  new GroupBarPlot ($gbspl); 
    
    // Adjust the legend position
    $graph->legend->Pos(0.86,0.1,"left","top");
    
    $graph->Add($gbplot); 
    
    $graph->xaxis->scale->SetGrace(20);
    
    $graph->title->Set($this->titre);
    $labels= array();
    for ($j = 0; $j < 12 ; $j++)
      {
	$labels[$j] = strtoupper(substr(strftime("%B",mktime(1,1,1,($j+1),1,2005)),0,1));
      }



    $graph->xaxis->SetTickLabels($labels);
    
    // Display the graph
    
    $graph->img->SetImgFormat("png");
    $graph->Stroke($this->file);    
  }
}   

?>
