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


  Function Graph($data=array())
  {
    $this->data = $data;
    
    include_once(DOL_DOCUMENT_ROOT."/includes/phplot/phplot.php");
    $this->bgcolor = array(235,235,224);
    $this->bordercolor = array(235,235,224);
    $this->datacolor = array(array(204,204,179),
			     array(187,187,136),
			     array(235,235,224));

    $this->precision_y = 0;

    $this->width = 400;
    $this->height = 200;

    return 1;
  }
  
  /*
   *
   *
   *
   */
  Function draw($file, $data, $title='')
  {
    //Define the object
    $graph = new PHPlot($this->width, $this->height);
    $graph->SetIsInline(1);

    $graph->SetPlotType('bars');

    if (isset($this->MaxValue))
      {
	$graph->SetPlotAreaWorld(0,0,12,$this->MaxValue);
      }
    else
      {
	$graph->SetPlotAreaPixels(60, 10, $this->width-10, $this->height - 30) ;
      }

    $graph->SetBackgroundColor($this->bgcolor);

    // TODO
    //$graph->SetPrecisionY($this->precision_Y);

    $graph->SetDataColors($this->datacolor, $this->bordercolor);

    $graph->SetOutputFile($file);

    //Set some data
    $graph->SetDataValues($data);

    $graph->SetVertTickIncrement(0);

    //
    if (strlen($title))
      {
	$graph->SetTitle = $title;
      }

    //    $graph->SetSkipBottomTick(1);

    // Affiche les valeurs
    //$graph->SetDrawDataLabels('1');
    //$graph->SetLabelScalePosition('1');

    if (isset($this->MaxValue))
      {
	$graph->SetVertTickPosition('plotleft');
    
	$graph->SetMarginsPixels(40,50,30,30);
	$graph->SetLegend(array('2002','2003'));
	$graph->SetLegendWorld(12,$this->MaxValue);
      }

    //Draw it
    $graph->DrawGraph();
  }

  Function SetPrecisionY($which_prec)
  {
    $this->precision_y = $which_prec;
    return true;
  }

  Function SetYLabel($label)
  {
    $this->YLabel = $label;
  }

  Function SetWidth($w)
  {
    $this->width = $w;
  }

  Function SetMaxValue($max)
  {
    $this->MaxValue = $max;
  }

  Function SetHeight($h)
  {
    $this->height = $h;
  }

  Function SetLegend()
  {

  }

  Function GetMaxValue()
  {
    $k = 0;
    $vals = array();
    $nblines = sizeof($this->data);
    $nbvalues = sizeof($this->data[0]) - 1;

    for ($j = 0 ; $j < $nblines ; $j++)
      {
	for ($i = 0 ; $i < $nbvalues ; $i++)
	  {
	    $vals[$k] = $this->data[$j][$i+1];
	    $k++;
	  }
      }
    rsort($vals);
    return $vals[0];
  }
}

?>
