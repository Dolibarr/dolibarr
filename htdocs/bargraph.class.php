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

include_once DOL_DOCUMENT_ROOT . "/graph.class.php";

class BarGraph extends Graph
{
  var $db;
  var $errorstr;

  /**
   * Initialisation
   *
   */

  Function BarGraph($data=array())
  {
    $this->data = $data;
    
    include_once(DOL_DOCUMENT_ROOT."/includes/phplot/phplot.php");
    
    $this->bgcolor = array(235,235,224);
    //$this->bgcolor = array(235,235,200);
    $this->bordercolor = array(235,235,224);
    $this->datacolor = array(array(204,204,179),
			     array(187,187,136),
			     array(235,235,224));
    
    $this->precision_y = 0;

    $this->width = 400;
    $this->height = 200;

    $this->PlotType = 'bars';

    return 1;
  }

  /**
   * Dessine le graphique
   *
   */
  Function draw($file, $data, $title='')
  {
    $this->prepare($file, $data, $title);

    //Draw it
    $this->graph->DrawGraph();
  }
}

?>
