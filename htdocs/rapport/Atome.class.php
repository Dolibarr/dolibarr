<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

include_once DOL_DOCUMENT_ROOT.'/bargraph.class.php';

class Atome
{
  var $id;
  var $db;
  var $name;
  var $periode;
  var $graph_values;
  /**
   * Initialisation de la classe
   *
   */

  Function AtomeInitialize($periode, $name, $daystart)
  {
    $this->year = strftime("%Y", $daystart);
    $this->month = strftime("%m", $daystart);
    $this->periode = $periode;
    $this->name = $name;
  }
  /**
   * 
   *
   *
   */
  Function BarGraph()
  {
    $filename = DOL_DOCUMENT_ROOT.'/document/';

    $this->graph_values = array();

    if ($this->periode == 'year')
      {
	$filename .= $this->name.$this->year.'.png';

	for ($i = 0 ; $i < 12 ; $i++)
	  {
	    $index = $this->year . substr('00'.($i+1),-2);
	    $value = 0;
	    if ($this->datas[$index])
	      {
		$value = $this->datas[$index];
	      }

	    $libelle = ucfirst(strftime("%b", mktime(12,0,0,($i+1),1,2004)));

	    $this->graph_values[$i] = array($libelle, $value);
	  }
      }

    if ($this->periode == 'month')
      {
	$filename .= $this->name.$this->year.$this->month.'.png';

	$datex = mktime(12,0,0,$this->month, 1, $this->year);
	$i = 0;
	while (strftime("%Y%m", $datex) == $this->year.$this->month)
	  {

	    $index = $this->year . $this->month . substr('00'.($i+1),-2);
	    $value = 0;
	    if ($this->datas[$index])
	      {
		$value = $this->datas[$index];
	      }

	    $libelle = ($i+1);

	    $this->graph_values[$i] = array($libelle, $value);

	    $i++;
	    $datex = $datex + 86400;
	  }
      }

    // var_dump($this->graph_values);


    $bgraph = new BarGraph();
    $bgraph->bgcolor = array(255,255,255);
    $bgraph->width = 600;
    $bgraph->height = 400;
    $bgraph->draw($filename, $this->graph_values);

    return $filename;
  }
}
?>
