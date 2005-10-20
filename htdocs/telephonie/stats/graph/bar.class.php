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

class GraphBar extends DolibarrGraph {

  Function GraphBar($DB, $file)
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
    $width = 320;

    if ($this->width <> $width && $this->width > 0)
      $width = $this->width;
    
    if ($this->height <> $height && $this->height > 0)
      $height = $this->height;

    if (sizeof($datas) && sizeof($labels))
      {
	$graph = new Graph($width, $height,"auto");    
	$graph->SetScale("textlin");

	$graph->yaxis->scale->SetGrace(20);

	$graph->SetFrame($this->showframe);
    
	$graph->img->SetMargin(50,20,20,35);
    
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
	/*
	$LabelAngle = 0;

	if ($this->LabelAngle <> $LabelAngle && strlen($this->LabelAngle) > 0)
	  $LabelAngle = $this->LabelAngle;

	if ($this->LabelAngle > 0)
	  {
	    $graph->xaxis->SetLabelAngle($LabelAngle);
	    $graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,7);
	  }
	*/
	$graph->title->Set($this->titre);
    
	//$graph->title->SetFont(FF_VERDANA,FS_NORMAL);

    
	$graph->xaxis->SetTickLabels($labels);   
	$graph->xaxis->title->Set(strftime("%d/%m/%y %H:%M:%S", time()));

	$graph->Add($b2plot);

	// Display the graph
    
	$graph->img->SetImgFormat("png");
	$graph->Stroke($file);
      }
    else
      {
	// Setup a basic canvas we can work
	$g = new CanvasGraph($width,$height,'auto');
	$g->SetMargin(5,11,6,11);
	$g->SetShadow();
	$g->SetMarginColor("teal");
	
	// We need to stroke the plotarea and margin before we add the
	// text since we otherwise would overwrite the text.
	//$g->InitFrame();

	// Draw a text box in the middle
	$txt = "Données manquantes !";
	$t = new Text($txt,ceil($width / 2),ceil($height/2));
	//$t->SetFont(FF_VERDANA, FS_BOLD, 10);
	
	// How should the text box interpret the coordinates?
	$t->Align('center','top');
	$t->SetBox("white","black","gray");	
	$t->ParagraphAlign('center');
	
	// Stroke the text
	$t->Stroke($g->img);
	
	// Stroke the graph
	$g->Stroke($file);
      }
  }
}   
?>
