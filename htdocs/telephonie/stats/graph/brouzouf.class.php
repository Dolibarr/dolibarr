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

class GraphBrouzouf {

  Function GraphBrouzouf($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;

  }
  
  Function GraphMakeGraph($datas, $labels)
  {
    // Create the graph. These two calls are always required

    $height = 240;
    $width = 360;

    if ($this->width <> $width && $this->width > 0)
      $width = $this->width;

    $graph = new Graph($width, $height,"auto");    
    $graph->SetScale("textlin");
    $graph->yaxis->scale->SetGrace(20);
    
    
    $graph->img->SetMargin(50,20,20,40);
    
    if ($this->type == 'LinePlot')
      {
	$b2plot = new LinePlot($datas);
      }
    else
      {
	$b2plot = new BarPlot($datas);
      }

    $b2plot->SetFillColor($this->barcolor);
    
    $graph->xaxis->scale->SetGrace(20);
    //$graph->xaxis->SetLabelAlign('center','bottom');
    
    $graph->Add($b2plot);
    
    $graph->title->Set($this->titre);   
    
    $graph->xaxis->SetTickLabels($labels);
    
    // Display the graph
    
    $graph->img->SetImgFormat("png");
    $graph->Stroke($this->file);
  }

}   
?>
