<?php
/* Copyright (c) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/bargraph.class.php
		\brief      Fichier de la classe de gestion de graphs phplot
		\version    $Revision$
*/

include_once(DOL_DOCUMENT_ROOT."/graph.class.php");


/**
        \class      BarGraph
	    \brief      Classe permettant la gestion des graphs phplot
*/

class BarGraph extends Graph
{
  var $db;
  var $errorstr;
  

  /**
   *    \brief      Initialisation
   *    \return     int     Retour: 0 si ko, 1 si ok
   */
  function BarGraph($data=array()) {
    
	$modules_list = get_loaded_extensions();
	$isgdinstalled=0;
	foreach ($modules_list as $module) 
	{
    	if ($module == 'gd') { $isgdinstalled=1; }
	}
	if (! $isgdinstalled) {
    	$this->errorstr="Erreur: Le module GD pour php ne semble pas disponible. Il est requis pour générer les graphiques.";
    	return;
	}

    $this->data = $data;
    
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

  function isGraphKo() {
    return $this->errorstr;
  }

  /**
   *    \brief      Génère le fichier graphique sur le disque
   *    \param      file    Nom du fichier image
   *    \param      data    Tableau des données
   *    \param      title   Titre de l'image
   */
  function draw($file, $data, $title='') {
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
    
    // Génère le fichier $file
    $this->graph->DrawGraph();
  }
}

?>
