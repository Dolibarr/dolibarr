<?PHP
/* Copyright (c) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   * Retour: 0 si ko, 1 si ok
   */

  Function BarGraph($data=array()) {
    
	$modules_list = get_loaded_extensions();
	$isgdinstalled=0;
	foreach ($modules_list as $module) 
	{
    	if ($module == 'gd') { $isgdinstalled=1; }
	}
	if (! $isgdinstalled) {
    	$this->errorstr="Erreur: Le module GD pour PHP ne semble pas disponible. Il est requis pour générer les graphiques.";
    	return;
	}

    $this->data = $data;
    
    include_once(DOL_DOCUMENT_ROOT."/includes/phplot/phplot.php");

    $this->bgcolor = array(235,235,224);
    //$this->bgcolor = array(235,235,200);
    $this->bordercolor = array(235,235,224);
    $this->datacolor = array(array(204,204,179),
			     array(187,187,136),
			     array(235,235,224));

    
    $color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
    if (is_readable($color_file))
      {
	include($color_file);
	$this->bgcolor = $theme_bgcolor;
      }
    
    $this->precision_y = 0;

    $this->width = 400;
    $this->height = 200;

    $this->PlotType = 'bars';

    return;
  }

  Function isGraphKo() {
    return $this->errorstr;
  }

  /**
   * Dessine le graphique
   *
   */
  Function draw($file, $data, $title='') {
    $this->prepare($file, $data, $title);
    
    if (substr($this->MaxValue,0,1) == 1)
      {
	$this->graph->SetNumVertTicks(10);
      }
    elseif (substr($this->MaxValue,0,1) == 2)
      {
	$this->graph->SetNumVertTicks(4);
      }
    elseif (substr($this->MaxValue,0,1) == 3)
      {
	$this->graph->SetNumVertTicks(6);
      }
    elseif (substr($this->MaxValue,0,1) == 4)
      {
	$this->graph->SetNumVertTicks(8);
      }
    else
      {
	$this->graph->SetNumVertTicks(substr($this->MaxValue,0,1));
      }
    


    $this->graph->DrawGraph();
  }
}

?>
