<?php
/* Copyright (c) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/graph.class.php
		\brief      Fichier de la classe mère de gestion des graph phplot
		\version    $Revision$
*/

include_once(DOL_DOCUMENT_ROOT."/includes/phplot/phplot.php");


/**
        \class      Graph
	    \brief      Classe mère permettant la gestion des graph phplot
*/

class Graph
{
    var $db;
    var $errorstr;

    var $graph;     // Objet PHPlot
    
    
    /**
     *    \brief      Génère le fichier graphique sur le disque
     *    \param      file    Nom du fichier image
     *    \param      data    Tableau des données
     *    \param      title   Titre de l'image
     */
    function draw($file)
    {
        // Prepare parametres
        $this->prepare($file);

        // Génère le fichier $file
        $this->graph->DrawGraph();
    }
    
    /**
     *    \brief      Prépare l'objet PHPlot
     *    \param      file    Nom du fichier image à générer
     *    \param      data    Tableau des données
     *    \param      title   Titre de l'image
     */
    function prepare($file)
    {
        // Define the object
        $this->graph = new PHPlot($this->width, $this->height);
        $this->graph->SetIsInline(1);
    
        $this->graph->SetPlotType( $this->PlotType );
    
        //Set some data
        $this->graph->SetDataValues($this->data);

        if (isset($this->MaxValue))
        {
            $nts = array();
            $this->MaxValue = $this->MaxValue + 1;
            $max = $this->MaxValue;
            if (($max % 2) <> 0)
            {
                $this->MaxValue = $this->MaxValue + 1;
                $max++;
            }
    
            $this->graph->SetPlotAreaWorld(0,0,12,$this->MaxValue);
    
            $j = 0;
            for ($i = 1 ; $i < 11 ; $i++)
            {
                $res = $max % $i;
                $cal = $max / $i;
    
                if ($res == 0 && $cal <= 11)
                {
                    $nts[$j] = $cal;
                    $j++;
                }
    
            }
            rsort($nts);
    
            $this->graph->SetNumVertTicks($nts[0]);
        }
        else
        {
            $this->graph->SetPlotAreaPixels(60, 10, $this->width-10, $this->height - 30) ;
        }
    
        $this->graph->SetBackgroundColor($this->bgcolor);
        $this->graph->SetDataColors($this->datacolor, $this->bordercolor);
    
        // Define title
        if (strlen($this->title)) $this->graph->SetTitle($this->title);
    
        // TODO
        //$this->graph->SetPrecisionY($this->precision_Y);
        //    $this->graph->SetVertTickIncrement(0);
        //    $this->graph->SetSkipBottomTick(1);

        $this->graph->SetVertTickPosition('plotleft');
   
        $this->graph->SetYGridLabelType("data");
    
        $this->graph->SetDrawYGrid(1);
    
        // Affiche les valeurs
        //$this->graph->SetDrawDataLabels('1');
        //$this->graph->SetLabelScalePosition('1');

        $this->graph->SetOutputFile($file);
    
        // Défini position du graphe (et legende) au sein de l'image
        if (isset($this->Legend))
        {
            $this->graph->SetMarginsPixels(60,100,10,30);

            $this->graph->SetLegend($this->Legend);
            $this->graph->SetLegendWorld(13,$this->MaxValue);
        }
        else
        {
            $this->graph->SetMarginsPixels(60,10,10,30);
        }
            
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
    
    }

  function SetPrecisionY($which_prec)
  {
    $this->precision_y = $which_prec;
    return true;
  }

  function SetYLabel($label)
  {
    $this->YLabel = $label;
  }

  function SetWidth($w)
  {
    $this->width = $w;
  }

  function SetTitle($title)
  {
    $this->title = $title;
  }

  function SetData($data)
  {
    $this->data = $data;
  }

  function SetLegend($legend)
  {
    $this->Legend = $legend;
  }

  function SetMaxValue($max)
  {
    $this->MaxValue = $max;
  }

  function SetHeight($h)
  {
    $this->height = $h;
  }

  function ResetBgColor()
  {
    unset($this->bgcolor);
  }
  
  function SetBgColor($bg_color = array(255,255,255))
  {
    $this->bgcolor = $bg_color;
  }

  function ResetDataColor()
  {
    unset($this->datacolor);
  }

  function GetMaxValue()
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

  function GetAmountMaxValue()
  {

    $max = ceil($this->GetMaxValue());
    $size = strlen("$max");
    if (substr($max,0,1) == 9)
      {
	$res = 1;
      }
    else
      {
	$size = $size - 1;
	$res = substr($max,0,1) + 1;
      }

    for ($i = 0 ; $i < $size ; $i++)
      {
	$res .= "0";
      }

	return ($res - 2);
  }

}

?>
