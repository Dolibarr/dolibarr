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

class GraphCamenbert extends DolibarrGraph {

  Function GraphCamenbert($DB, $file)
  {
    $this->file = $file;
    $this->titre = "Camenbert";
    $this->colors = array('pink','yellow','blue','green','red','white','grey');
  }
  
  Function GraphDraw($datas, $legends)
  {    
    
    // Create the graph. These two calls are always required
    $graph = new PieGraph(420,260,"auto");
    
    $graph->SetColor("gray") ;
    
    // Create the bar plots
    
    $pieplot = new PiePlot($datas);
    
    $pieplot->SetCenter(0.33,0.5);
    
    // Label font and color setup
    $pieplot->SetFont(FF_FONT1,FS_BOLD);
    $pieplot->SetFontColor("darkred");
    
    // Use absolute values (type==1)
    //$pieplot->SetLabelType(0);
    
    // Label format
    //$pieplot->SetLabelFormat("%d%%");
    
    $pieplot->SetSliceColors($this->colors);
    
    //$pieplot->SetStartAngle(45);
    //$pieplot->SetLabelPos(0.6);
    
    // Size of pie in fraction of the width of the graph
    $pieplot->SetSize(0.38);
    
    // Legends
    $pieplot->SetLegends($legends);
    $graph->legend->Pos(0.05,0.15);
    
    $graph->Add($pieplot);
    
    $graph->title->Set($this->titre);
    
    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    

    // Display the graph
    
    $graph->img->SetImgFormat("png");
    $graph->Stroke($this->file);
  }
}
?>
