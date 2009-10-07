<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

require_once (DOL_DOCUMENT_ROOT."/includes/artichow/BarPlot.class.php");

class DolibarrSimpleBar {

  Function DolibarrSimpleBar($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;
    $this->barcolor = "green";
    $this->height = 240;
    $this->width = 360;
    $this->yAxisLegend = '';
  }
  
  Function GraphDraw($file, $datas, $labels)
  {
    $group = new PlotGroup;
    $group->setPadding(30, 10, NULL, NULL);
    
    $graph = new Graph($this->width, $this->height);
    $graph->border->hide();
    $graph->setAntiAliasing(true);
    if (isset($this->titre)) 
      {
	$graph->title->set($this->titre);
	$graph->title->setFont(new Tuffy(10));
      }

    $bgcolor= new Color(222,231,236);
    $graph->setBackgroundColor($bgcolor);

    
    $plot = new BarPlot($datas);

    $plot->barShadow->setSize(2);
    $plot->barShadow->setPosition('Shadow::RIGHT_TOP');
    $plot->barShadow->setColor(new Color(160, 160, 160, 10));
    $plot->barShadow->smooth(TRUE);
    
    $color = new $this->barcolor;
    $plot->setBarColor($color);

    $plot->xAxis->setLabelText($labels);
    $plot->xAxis->label->setFont(new Tuffy(7));
    
    if ($this->yAxisLegend)
      {
	$plot->yAxis->title->set($this->yAxisLegend);
	$plot->yAxis->title->setFont(new Tuffy(7));
      }

    $graph->add($plot);
    $graph->draw($file);
  }
}   
?>
