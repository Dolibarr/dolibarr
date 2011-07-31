<?php
/* Copyright (c) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/dolgraph.class.php
 *  \ingroup    core
 *	\brief      Fichier de la classe mere de gestion des graph
 *	\version    $Id: dolgraph.class.php,v 1.7 2011/07/31 23:45:15 eldy Exp $
 *
 *	Usage:
 *	$graph_data = array(array('labelA',yA),array('labelB',yB));
 *	array(array('labelA',yA1,...,yAn),array('labelB',yB1,...yBn));
 *	$px = new DolGraph();
 *	$px->SetData($graph_data);
 *	$px->SetMaxValue($px->GetCeilMaxValue());
 *	$px->SetMinValue($px->GetFloorMinValue());
 *	$px->SetTitle("title");
 *	$px->SetLegend(array("Val1","Val2"));
 *	$px->SetWidth(width);
 *	$px->SetHeight(height);
 *	$px->draw("file.png");
 */


/**
 *	\class      DolGraph
 *	\brief      Parent class of graph classes
 */
class DolGraph
{
	//! Type du graphique
	var $type='bars';		// bars, lines, ...
	var $mode='side';		// Mode bars graph: side, depth

	//! Tableau de donnees
	var $data;				// array(array('abs1',valA1,valB1), array('abs2',valA2,valB2), ...)
	var $width=380;
	var $height=200;
	var $MaxValue=0;
	var $MinValue=0;
	var $SetShading=0;

	var $PrecisionY=-1;

	var $horizTickIncrement=-1;
	var $SetNumXTicks=-1;
	var $labelInterval=-1;

	var $hideXGrid=false;
	var $hideYGrid=false;

	var $Legend=array();
	var $LegendWidthMin=0;

	var $graph;     		// Objet Graph (Artichow, Phplot...)
	var $error;

	var $library='';		// Par defaut on utiliser PHPlot

	var $bordercolor;		// array(R,G,B)
	var $bgcolor;			// array(R,G,B)
	var $bgcolorgrid;		// array(R,G,B)
	var $datacolor;			// array(array(R,G,B),...)


	/**
	 *	Constructeur
	 */
	function DolGraph()
	{
		global $conf;
		global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;


		// Test si module GD present
		$modules_list = get_loaded_extensions();
		$isgdinstalled=0;
		foreach ($modules_list as $module)
		{
			if ($module == 'gd') { $isgdinstalled=1; }
		}
		if (! $isgdinstalled)
		{
			$this->error="Error: PHP GD module is not available. It is required to build graphics.";
			return -1;
		}


		// Defini proprietes de l'objet graphe
		$this->library=$conf->global->MAIN_GRAPH_LIBRARY;

		$this->bordercolor = array(235,235,224);
		$this->datacolor = array(array(120,130,150), array(160,160,180), array(190,190,220));
		$this->bgcolor = array(235,235,224);

		$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
		if (is_readable($color_file))
		{
			include_once($color_file);
			if (isset($theme_bordercolor)) $this->bordercolor = $theme_bordercolor;
			if (isset($theme_datacolor))   $this->datacolor   = $theme_datacolor;
			if (isset($theme_bgcolor))     $this->bgcolor     = $theme_bgcolor;
		}
		//print 'bgcolor: '.join(',',$this->bgcolor).'<br>';
		return 1;
	}


	/**
	 */
	function SetPrecisionY($which_prec)
	{
		$this->PrecisionY = $which_prec;
		return true;
	}

	/**
	 *		\remarks	Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 */
	function SetHorizTickIncrement($xi)
	{
		$this->horizTickIncrement = $xi;
		return true;
	}

	/**
	 *		\remarks	Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 */
	function SetNumXTicks($xt)
	{
		$this->SetNumXTicks = $xt;
		return true;
	}

	/**
	 *		\brief		Set label interval to reduce number of labels
	 */
	function SetLabelInterval($x)
	{
		$this->labelInterval = $x;
		return true;
	}

	/**
	 * 		\brief		Hide X grid
	 */
	function SetHideXGrid($bool)
	{
		$this->hideXGrid = $bool;
		return true;
	}

	/**
	 * 		\brief		Hide Y grid
	 */
	function SetHideYGrid($bool)
	{
		$this->hideYGrid = $bool;
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
	function SetLegendWidthMin($legendwidthmin)
	{
		$this->LegendWidthMin = $legendwidthmin;
	}

	function SetMaxValue($max)
	{
		$this->MaxValue = $max;
	}
	function GetMaxValue()
	{
		return $this->MaxValue;
	}

	function SetMinValue($min)
	{
		$this->MinValue = $min;
	}
	function GetMinValue()
	{
		return $this->MinValue;
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

	function ResetBgColorGrid()
	{
		unset($this->bgcolorgrid);
	}

	function isGraphKo()
	{
		return $this->error;
	}


	/**
	 *	\brief		Definie la couleur de fond de l'image complete
	 *	\param		bg_color		array(R,G,B) ou 'onglet' ou 'default'
	 */
	function SetBgColor($bg_color = array(255,255,255))
	{
		global $theme_bgcolor,$theme_bgcoloronglet;
		if (! is_array($bg_color))
		{
			if ($bg_color == 'onglet')
		  	{
		  		//print 'ee'.join(',',$theme_bgcoloronglet);
		  		$this->bgcolor = $theme_bgcoloronglet;
		  	}
		  	else
		  	{
		  		$this->bgcolor = $theme_bgcolor;
			}
		}
		else
		{
			$this->bgcolor = $bg_color;
		}
	}

	/**
	 *	\brief		Definie la couleur de fond de la grille
	 *	\param		bg_colorgrid		array(R,G,B) ou 'onglet' ou 'default'
	 */
	function SetBgColorGrid($bg_colorgrid = array(255,255,255))
	{
		global $theme_bgcolor,$theme_bgcoloronglet;
		if (! is_array($bg_colorgrid))
		{
			if ($bg_colorgrid == 'onglet')
		  	{
		  		//print 'ee'.join(',',$theme_bgcoloronglet);
		  		$this->bgcolorgrid = $theme_bgcoloronglet;
		  	}
		  	else
		  	{
		  		$this->bgcolorgrid = $theme_bgcolor;
			}
		}
		else
		{
			$this->bgcolorgrid = $bg_colorgrid;
		}
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

	/**
	 * Return max value of all data
	 *
	 * @return 	int		Max value of all data
	 */
	function GetCeilMaxValue()
	{
		$max = $this->GetMaxValueInData();
		if ($max != 0) $max++;
		$size=dol_strlen(abs(ceil($max)));
		$factor=1;
		for ($i=0; $i < ($size-1); $i++)
		{
			$factor*=10;
		}

		$res=0;
		if (is_numeric($max)) $res=ceil($max/$factor)*$factor;

		//print "max=".$max." res=".$res;
		return $res;
	}

	/**
	 * Return min value of all data
	 *
	 * @return 	int		Max value of all data
	 */
	function GetFloorMinValue()
	{
		$min = $this->GetMinValueInData();
		if ($min != 0) $min--;
		$size=dol_strlen(abs(floor($min)));
		$factor=1;
		for ($i=0; $i < ($size-1); $i++)
		{
			$factor*=10;
		}

		$res=floor($min/$factor)*$factor;

		//print "min=".$min." res=".$res;
		return $res;
	}

	/**
	 *    	Build a graph onto disk
	 *    	@param      file    Image file name on disk
	 */
	function draw($file)
	{
		if (! is_array($this->data) || sizeof($this->data) < 1)
		{
			$this->error="Call to draw method was made but SetData was not called or called with an empty dataset for parameters";
			dol_syslog("DolGraph::draw ".$this->error, LOG_ERR);
			return -1;
		}
		$call = "draw_".$this->library;
		$this->$call($file);
	}


	/**
	 * 		Build a graph onto disk using Artichow library
	 *    	@param      file    Image file name on disk
	 */
	function draw_artichow($file)
	{
		dol_syslog("DolGraph.class::draw_artichow this->type=".$this->type);

		if (! defined('SHADOW_RIGHT_TOP'))  define('SHADOW_RIGHT_TOP',3);
		if (! defined('LEGEND_BACKGROUND')) define('LEGEND_BACKGROUND',2);
		if (! defined('LEGEND_LINE'))       define('LEGEND_LINE',1);

		// Create graph
		$classname='';
		if ($this->type == 'bars')  $classname='BarPlot';
		if ($this->type == 'lines') $classname='LinePlot';
		include_once DOL_DOCUMENT_ROOT."/includes/artichow/".$classname.".class.php";

		// Definition de couleurs
		$bgcolor=new Color($this->bgcolor[0],$this->bgcolor[1],$this->bgcolor[2]);
		$bgcolorgrid=new Color($this->bgcolorgrid[0],$this->bgcolorgrid[1],$this->bgcolorgrid[2]);
		$colortrans=new Color(0,0,0,100);
		$colorsemitrans=new Color(255,255,255,60);
		$colorgradient= new LinearGradient(new Color(235, 235, 235),new Color(255, 255, 255),0);
		$colorwhite=new Color(255,255,255);

		// Graph
		$graph = new Graph($this->width, $this->height);
		$graph->border->hide();
		$graph->setAntiAliasing(true);
		if (isset($this->title))
		{
			$graph->title->set($this->title);
			$graph->title->setFont(new Tuffy(10));
		}

		if (is_array($this->bgcolor)) $graph->setBackgroundColor($bgcolor);
		else $graph->setBackgroundGradient($colorgradient);

		$group = new PlotGroup;
		//$group->setSpace(5, 5, 0, 0);

		$paddleft=50;
		$paddright=10;
		$strl=dol_strlen(max(abs($this->MaxValue),abs($this->MinValue)));
		if ($strl > 6) $paddleft += ($strln * 4);
		$group->setPadding($paddleft, $paddright);		// Width on left and right for Y axis values
		$group->legend->setSpace(0);
		$group->legend->setPadding(2,2,2,2);
		$group->legend->setPosition(NULL,0.1);
		$group->legend->setBackgroundColor($colorsemitrans);

		if (is_array($this->bgcolorgrid)) $group->grid->setBackgroundColor($bgcolorgrid);
		else $group->grid->setBackgroundColor($colortrans);

		if ($this->hideXGrid)	$group->grid->hideVertical(true);
		if ($this->hideYGrid)	$group->grid->hideHorizontal(true);

		// On boucle sur chaque lot de donnees
		$legends=array();
		$i=0;
		$nblot=sizeof($this->data[0])-1;

		while ($i < $nblot)
		{
			$j=0;
			$values=array();
			foreach($this->data as $key => $valarray)
			{
				$legends[$j] = $valarray[0];
				$values[$j]  = $valarray[$i+1];
				$j++;
			}

			// Artichow ne gere pas les valeurs inconnues
			// Donc si inconnu, on la fixe a null
			$newvalues=array();
			foreach($values as $val)
			{
				$newvalues[]=(is_numeric($val) ? $val : null);
			}


			if ($this->type == 'bars')
			{
				//print "Lot de donnees $i<br>";
				//print_r($values);
				//print '<br>';

				$color=new Color($this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2],20);
				$colorbis=new Color(min($this->datacolor[$i][0]+50,255),min($this->datacolor[$i][1]+50,255),min($this->datacolor[$i][2]+50,255),50);

				$colorgrey=new Color(100,100,100);
				$colorborder=new Color($this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2]);

				if ($this->mode == 'side')  $plot = new BarPlot($newvalues, $i+1, $nblot);
				if ($this->mode == 'depth') $plot = new BarPlot($newvalues, 1, 1, ($nblot-$i-1)*5);

				$plot->barBorder->setColor($colorgrey);
				//$plot->setBarColor($color);
				$plot->setBarGradient( new LinearGradient($colorbis, $color, 90) );

				if ($this->mode == 'side')  $plot->setBarPadding(0.1, 0.1);
				if ($this->mode == 'depth') $plot->setBarPadding(0.1, 0.4);
				if ($this->mode == 'side')  $plot->setBarSpace(5);
				if ($this->mode == 'depth') $plot->setBarSpace(2);

				$plot->barShadow->setSize($this->SetShading);
				$plot->barShadow->setPosition(SHADOW_RIGHT_TOP);
				$plot->barShadow->setColor(new Color(160, 160, 160, 50));
				$plot->barShadow->smooth(TRUE);
				//$plot->setSize(1, 0.96);
				//$plot->setCenter(0.5, 0.52);

				// Le mode automatique est plus efficace
				$plot->SetYMax($this->MaxValue);
				$plot->SetYMin($this->MinValue);
			}

			if ($this->type == 'lines')
			{
				$color=new Color($this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2],20);
				$colorbis=new Color(min($this->datacolor[$i][0]+20,255),min($this->datacolor[$i][1]+20,255),min($this->datacolor[$i][2]+20,255),60);
				$colorter=new Color(min($this->datacolor[$i][0]+50,255),min($this->datacolor[$i][1]+50,255),min($this->datacolor[$i][2]+50,255),90);

				$plot = new LinePlot($newvalues);
				//$plot->setSize(1, 0.96);
				//$plot->setCenter(0.5, 0.52);

				$plot->setColor($color);
				$plot->setThickness(1);

				// Set line background gradient
				$plot->setFillGradient( new LinearGradient($colorter, $colorbis, 90) );

				$plot->xAxis->setLabelText($legends);

				// Le mode automatique est plus efficace
				$plot->SetYMax($this->MaxValue);
				$plot->SetYMin($this->MinValue);
				//$plot->setYAxis(0);
				//$plot->hideLine(true);
			}

			//$plot->reduce(80);		// Evite temps d'affichage trop long et nombre de ticks absisce satures

			$group->legend->setTextFont(new Tuffy(10)); // This is to force Artichow to use awFileFontDriver to
														// solve a bug in Artichow with UTF8
			if (sizeof($this->Legend))
			{
				if ($this->type == 'bars')  $group->legend->add($plot, $this->Legend[$i], LEGEND_BACKGROUND);
				if ($this->type == 'lines') $group->legend->add($plot, $this->Legend[$i], LEGEND_LINE);
			}
			$group->add($plot);

			$i++;
		}

		$group->axis->bottom->setLabelText($legends);
		$group->axis->bottom->label->setFont(new Tuffy(7));

		//print $group->axis->bottom->getLabelNumber();
		if ($this->labelInterval > 0) $group->axis->bottom->setLabelInterval($this->labelInterval);

		$graph->add($group);

		// Generate file
		$graph->draw($file);
	}

}

?>
