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

class GraphPie extends DolibarrGraph {

  Function GraphPie($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;
    $this->bgcolor = "#DEE7EC";
    $this->barcolor = "green";
    $this->client = 0;
    $this->showframe = true;
  }
  
  Function GraphDraw($file, $datas, $labels)
  {
    // Create the graph. These two calls are always required

    $height = 240;
    $width = 400;

    if ($this->width <> $width && $this->width > 0)
      $width = $this->width;

    if ($this->height <> $height && $this->height > 0)
      $height = $this->height;

    $graph = new PieGraph($width, $height,"auto");    

    $graph->SetColor("gray") ;

    // Create the bar plots
    
    $pieplot = new PiePlot($datas);
    
    $pieplot->SetCenter(0.33,0.5);
    
    // Label font and color setup
    $pieplot->SetFont(FF_FONT1,FS_BOLD);
    $pieplot->SetFontColor("darkred");
    
    
    // Size of pie in fraction of the width of the graph
    $pieplot->SetSize(0.38);
    
    // Legends
    $pieplot->SetLegends($labels);
    $graph->legend->Pos(0.05,0.15);
    
    $graph->Add($pieplot);
    
    $graph->title->Set($this->titre);
    
    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    
    
    // Display the graph
    
    $graph->img->SetImgFormat("png");

    if (sizeof($datas) > 0)
      {
	$graph->Stroke($file);
      }
  }
}   
?>
