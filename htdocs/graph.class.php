<?PHP
/* Copyright (c) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

class Graph
{
  var $db;
  var $errorstr;


  Function Graph()
  {
    
    include_once(DOL_DOCUMENT_ROOT."/includes/phplot/phplot.php");
    $this->bgcolor = array(235,235,224);
    $this->bordercolor = array(235,235,224);
    $this->datacolor = array(array(204,204,179),
			     array(187,187,136),
			     array(235,235,224));
    return 1;
  }
  
  /*
   *
   *
   *
   */
  Function draw($file, $data)
  {
    $w = 400;
    $h = 200;
    //Define the object
    $graph = new PHPlot($w,$h);
    $graph->SetIsInline(1);
    $graph->SetPlotType('bars');

    $graph->SetPlotAreaPixels(60,10,$w-10,$h-30) ;

    $graph->SetBackgroundColor($this->bgcolor);

    $graph->SetDataColors($this->datacolor, $this->bordercolor);

    $graph->SetOutputFile($file);

    //Set some data
    $graph->SetDataValues($data);

    $graph->SetVertTickIncrement(0);

    //Draw it
    $graph->DrawGraph();

  }
}

?>
