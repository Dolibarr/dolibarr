<?php
/* Copyright (c) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/dolgraph.class.php
		\brief      Fichier de la classe mère de gestion des graph phplot
		\version    $Revision$
	    \remarks    Usage: 
                    $graph_data = array(array('labelA',yA),array('labelB',yB));
                                  array(array('labelA',yA1,...,yAn),array('labelB',yB1,...yBn));
	                $px = new DolGraph();
                    $px->SetData($graph_data);
				    $px->SetMaxValue($px->GetCeilMaxValue());
				    $px->SetMinValue($px->GetFloorMinValue());
                    $px->SetTitle("title");
                    $px->SetLegend(array("Val1","Val2"));
                    $px->SetWidth(width);
                    $px->SetHeight(height);
                    $px->draw("file.png");
*/


/**
        \class      Graph
	    \brief      Classe mère permettant la gestion des graph
*/

class DolGraph
{
	var $type='bars';  	// Type de graph
	var $data;			// Tableau de donnees
	var $width=380;
	var $height=200;
	var $MaxValue=0;
	var $MinValue=0;
	var $SetShading=0;
	var $PrecisionY=-1;
	
	var $graph;     	// Objet PHPlot
	
	var $error;
	
	
	function DolGraph()
	{
		global $conf;
	
		// Test si module GD présent
		$modules_list = get_loaded_extensions();
		$isgdinstalled=0;
		foreach ($modules_list as $module)
		{
			if ($module == 'gd') { $isgdinstalled=1; }
		}
		if (! $isgdinstalled) {
			$this->error="Erreur: Le module GD pour php ne semble pas disponible. Il est requis pour générer les graphiques.";
			return -1;
		}

		// Vérifie que chemin vers PHPLOT_PATH est connu et defini $graphpath
		$graphpathdir=DOL_DOCUMENT_ROOT."/includes/phplot";
		if ($conf->global->PHPLOT_PATH) $graphpathdir=$conf->global->PHPLOT_PATH;
		if (! eregi('[\\\/]$',$graphpathdir)) $graphpathdir.='/';

		include_once($graphpathdir.'phplot.php');
		
	
		// Défini propriétés de l'objet graphe
		$this->bordercolor = array(235,235,224);
		$this->datacolor = array(array(120,130,150), array(160,160,180), array(190,190,220));
		$this->bgcolor = array(235,235,224);
	
		$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
		if (is_readable($color_file))
		{
			include($color_file);
			if (isset($theme_bordercolor)) $this->bordercolor = $theme_bordercolor;
			if (isset($theme_datacolor))   $this->datacolor   = $theme_datacolor;
			if (isset($theme_bgcolor))     $this->bgcolor     = $theme_bgcolor;
		}
	
		return 1;
	}
	
	
	function isGraphKo()
	{
		return $this->error;
	}
	
	
	/**
	*    \brief      Génère le fichier graphique sur le disque
	*    \param      file    Nom du fichier image
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
	*/
	function prepare($file)
	{
		// Define the object
		$this->graph = new PHPlot($this->width, $this->height);
		$this->graph->SetIsInline(1);
	
		$this->graph->SetPlotType($this->type);
		$this->graph->SetDataValues($this->data);

		// Precision axe y (pas de decimal si 3 chiffres ou plus)
		if ($this->PrecisionY > -1)
		{
			$this->graph->SetPrecisionY($this->PrecisionY);
			// Si precision de 0
			if ($this->PrecisionY == 0)
			{
				$maxval=$this->getCeilMaxValue();
				if (abs($maxval) < 2)
				{
					$this->SetMaxValue(2);
					$this->graph->SetNumVertTicks(2);
				}
				else
				{
					$maxticks=min(10,$maxval);
					$this->graph->SetNumVertTicks($maxticks);
				}
			}
		}
		else
		{
			$this->graph->SetPrecisionY(3-strlen(round($this->GetMaxValueInData())));
		}
		$this->graph->SetPrecisionX(0);
	
		// Set areas
		$this->graph->SetNewPlotAreaPixels(80, 40, $this->width-10, $this->height-40);
		if (isset($this->MaxValue))
		{
			$this->graph->SetPlotAreaWorld(0,$this->MinValue,sizeof($this->data),$this->MaxValue);
		}

		if (isset($this->SetShading))
		{
			$this->graph->SetShading($this->SetShading);
		}
		$this->graph->SetTickLength(6);

		$this->graph->SetBackgroundColor($this->bgcolor);
		$this->graph->SetDataColors($this->datacolor, $this->bordercolor);

		//$this->graph->SetDrawHorizTicks(true);	// Pour avoir les ticks axe x (phplot 5)
		$this->graph->SetHorizTickIncrement(1);


		//$this->graph->SetXGridLabelType('data');
		//$this->graph->SetXGridLabelType('');
		//$this->graph->SetXGridLabelType('title');

		$this->graph->SetPlotBorderType("left");		// Affiche axe y a gauche uniquement
		$this->graph->SetVertTickPosition('plotleft');	// Affiche tick axe y a gauche uniquement


	

	
		// Define title
		if (strlen($this->title)) $this->graph->SetTitle($this->title);
	
		// Défini position du graphe (et legende) au sein de l'image
		if (isset($this->Legend))
		{
			$this->graph->SetLegend($this->Legend);
		}

		$this->graph->SetOutputFile($file);
	}
	
	function SetPrecisionY($which_prec)
	{
		$this->PrecisionY = $which_prec;
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
	
	function SetType($type)
	{
		$this->type = $type;
	}
	
	function SetLegend($legend)
	{
		$this->Legend = $legend;
	}
	
	function SetMaxValue($max)
	{
		$this->MaxValue = $max;
	}

	function SetMinValue($min)
	{
		$this->MinValue = $min;
	}
	
	function SetHeight($h)
	{
		$this->height = $h;
	}

	function SetShading($s)
	{
		$this->SetShading = $s;
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

	function GetMaxValueInData()
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
	
	function GetMinValueInData()
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
		sort($vals);
		return $vals[0];
	}
	
	function GetCeilMaxValue()
	{
		$max = $this->GetMaxValueInData();
		$size=strlen(abs(ceil($max)));
		$factor=1;
		for ($i=0; $i < ($size-1); $i++)
		{
			$factor*=10;
		}
		$res=ceil($max/$factor)*$factor;
		
		//print "max=".$max." res=".$res;
		return $res;
	}

	function GetFloorMinValue()
	{
		$min = $this->GetMinValueInData();
		$size=strlen(abs(floor($min)));
		$factor=1;
		for ($i=0; $i < ($size-1); $i++)
		{
			$factor*=10;
		}
		$res=floor($min/$factor)*$factor;
		
		//print "min=".$min." res=".$res;
		return $res;
	}
}

?>
