<?php
/* Copyright (c) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\brief      File for class to generate graph
 */


/**
 * Class to build graphs.
 * Usage is:
 *    $dolgraph=new DolGraph();
 *    $dolgraph->SetTitle($langs->transnoentities('MyTitle').'<br>'.$langs->transnoentities('MyTitlePercent').'%');
 *    $dolgraph->SetMaxValue(50);
 *    $dolgraph->SetData($data);
 *    $dolgraph->setShowLegend(1);
 *    $dolgraph->setShowPercent(1);
 *    $dolgraph->SetType(array('pie'));
 *    $dolgraph->setWidth('100%');
 *    $dolgraph->draw('idofgraph');
 *    print $dolgraph->show($total?0:1);
 */
class DolGraph
{
	var $type=array();			// Array with type of each series. Example: array('bars', 'lines', ...)
	var $mode='side';		    // Mode bars graph: side, depth
	private $_library='jflot';	// Graphic library to use (jflot, artichow)

	//! Array of data
	var $data;				// Data of graph: array(array('abs1',valA1,valB1), array('abs2',valA2,valB2), ...)
	var $title;				// Title of graph
	var $cssprefix='';		// To add into css styles
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
	var $showlegend=1;
	var $showpointvalue=1;
	var $showpercent=0;
	var $combine=0;				// 0.05 if you want to combine records < 5% into "other"
	var $graph;     			// Objet Graph (Artichow, Phplot...)
	var $error;

	var $bordercolor;			// array(R,G,B)
	var $bgcolor;				// array(R,G,B)
	var $bgcolorgrid=array(255,255,255);			// array(R,G,B)
	var $datacolor;				// array(array(R,G,B),...)

	private $stringtoshow;      // To store string to output graph into HTML page


	/**
	 * Constructor
	 *
	 * @param	string	$library		'jflot' (default) or 'artichow' (no more supported)
	 */
	function __construct($library='jflot')
	{
		global $conf;
		global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;

		// To use old feature
		if ($library == 'artichow')
		{
			$this->_library='artichow';

			// Test if module GD present
			$modules_list = get_loaded_extensions();
			$isgdinstalled=0;
			foreach ($modules_list as $module)
			{
				if ($module == 'gd') $isgdinstalled=1;
			}
			if (! $isgdinstalled)
			{
				$this->error="Error: PHP GD module is not available. It is required to build graphics.";
				return -1;
			}
		}

		$this->bordercolor = array(235,235,224);
		$this->datacolor = array(array(120,130,150), array(160,160,180), array(190,190,220));
		$this->bgcolor = array(235,235,224);

		$color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/graph-color.php';
		if (is_readable($color_file))
		{
			include_once $color_file;
			if (isset($theme_bordercolor)) $this->bordercolor = $theme_bordercolor;
			if (isset($theme_datacolor))   $this->datacolor   = $theme_datacolor;
			if (isset($theme_bgcolor))     $this->bgcolor     = $theme_bgcolor;
		}
		//print 'bgcolor: '.join(',',$this->bgcolor).'<br>';
	}


	/**
	 * Set Y precision
	 *
	 * @param 	float	$which_prec		Precision
	 * @return 	boolean
	 */
	function SetPrecisionY($which_prec)
	{
		$this->PrecisionY = $which_prec;
		return true;
	}

	/**
	 * Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 *
	 * @param 	float 		$xi		Xi
	 * @return	boolean				True
	 */
	function SetHorizTickIncrement($xi)
	{
		$this->horizTickIncrement = $xi;
		return true;
	}

	/**
	 * Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 *
	 * @param 	float 		$xt		Xt
	 * @return	boolean				True
	 */
	function SetNumXTicks($xt)
	{
		$this->SetNumXTicks = $xt;
		return true;
	}

	/**
	 * Set label interval to reduce number of labels
	 *
	 * @param 	float 		$x		Label interval
	 * @return	boolean				True
	 */
	function SetLabelInterval($x)
	{
		$this->labelInterval = $x;
		return true;
	}

	/**
	 * Hide X grid
	 *
	 * @param	boolean		$bool	XGrid or not
	 * @return	boolean				true
	 */
	function SetHideXGrid($bool)
	{
		$this->hideXGrid = $bool;
		return true;
	}

	/**
	 * Hide Y grid
	 *
	 * @param	boolean		$bool	YGrid or not
	 * @return	boolean				true
	 */
	function SetHideYGrid($bool)
	{
		$this->hideYGrid = $bool;
		return true;
	}

	/**
	 * Set y label
	 *
	 * @param 	string	$label		Y label
	 * @return	boolean|null				True
	 */
	function SetYLabel($label)
	{
		$this->YLabel = $label;
	}

	/**
	 * Set width
	 *
	 * @param 	int		$w			Width
	 * @return	boolean|null				True
	 */
	function SetWidth($w)
	{
		$this->width = $w;
	}

	/**
	 * Set title
	 *
	 * @param 	string	$title		Title
	 * @return	void
	 */
	function SetTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * Set data
	 *
	 * @param 	array	$data		Data
	 * @return	void
	 * @see draw_jflot for syntax of data array
	 */
	function SetData($data)
	{
		$this->data = $data;
	}

	/**
	 * Set data
	 *
	 * @param 	array	$datacolor		Data color array(array(R,G,B),array(R,G,B)...)
	 * @return	void
	 */
	function SetDataColor($datacolor)
	{
		$this->datacolor = $datacolor;
	}

	/**
	 * Set type
	 *
	 * @param 	array	$type		Array with type for each serie. Example: array('pie'), array('lines',...,'bars')
	 * @return	void
	 */
	function SetType($type)
	{
		$this->type = $type;
	}

	/**
	 * Set legend
	 *
	 * @param 	array	$legend		Legend. Example: array('seriename1','seriname2',...)
	 * @return	void
	 */
	function SetLegend($legend)
	{
		$this->Legend = $legend;
	}

	/**
	 * Set min width
	 *
	 * @param 	int		$legendwidthmin		Min width
	 * @return	void
	 */
	function SetLegendWidthMin($legendwidthmin)
	{
		$this->LegendWidthMin = $legendwidthmin;
	}

	/**
	 * Set max value
	 *
	 * @param 	int		$max			Max value
	 * @return	void
	 */
	function SetMaxValue($max)
	{
		$this->MaxValue = $max;
	}

	/**
	 * Get max value
	 *
	 * @return	int		Max value
	 */
	function GetMaxValue()
	{
		return $this->MaxValue;
	}

	/**
	 * Set min value
	 *
	 * @param 	int		$min			Min value
	 * @return	void
	 */
	function SetMinValue($min)
	{
		$this->MinValue = $min;
	}

	/**
	 * Get min value
	 *
	 * @return	int		Max value
	 */
	function GetMinValue()
	{
		return $this->MinValue;
	}

	/**
	 * Set height
	 *
	 * @param 	int		$h				Height
	 * @return	void
	 */
	function SetHeight($h)
	{
		$this->height = $h;
	}

	/**
	 * Set shading
	 *
	 * @param 	string	$s				Shading
	 * @return	void
	 */
	function SetShading($s)
	{
		$this->SetShading = $s;
	}

	/**
	 * Set shading
	 *
	 * @param 	string	$s				Shading
	 * @return	void
	 */
	function SetCssPrefix($s)
	{
		$this->cssprefix = $s;
	}

	/**
	 * Reset bg color
	 *
	 * @return	void
	 */
	function ResetBgColor()
	{
		unset($this->bgcolor);
	}

	/**
	 * Reset bgcolorgrid
	 *
	 * @return	void
	 */
	function ResetBgColorGrid()
	{
		unset($this->bgcolorgrid);
	}

	/**
	 * Is graph ko
	 *
	 * @return	string		Error
	 */
	function isGraphKo()
	{
		return $this->error;
	}

	/**
	 * Show legend or not
	 *
	 * @param	int		$showlegend		1=Show legend (default), 0=Hide legend
	 * @return	void
	 */
	function setShowLegend($showlegend)
	{
		$this->showlegend=$showlegend;
	}

	/**
	 * Show pointvalue or not
	 *
	 * @param	int		$showpointvalue		1=Show value for each point, as tooltip or inline (default), 0=Hide value
	 * @return	void
	 */
	function setShowPointValue($showpointvalue)
	{
		$this->showpointvalue=$showpointvalue;
	}

	/**
	 * Show percent or not
	 *
	 * @param	int		$showpercent		1=Show percent for each point, as tooltip or inline, 0=Hide percent (default)
	 * @return	void
	 */
	function setShowPercent($showpercent)
	{
		$this->showpercent=$showpercent;
	}



	/**
	 * Define background color of complete image
	 *
	 * @param	array	$bg_color		array(R,G,B) ou 'onglet' ou 'default'
	 * @return	void
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
	 * Define background color of grid
	 *
	 * @param	array	$bg_colorgrid		array(R,G,B) ou 'onglet' ou 'default'
	 * @return	void
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

	/**
	 * Reset data color
	 *
	 * @return	void
	 */
	function ResetDataColor()
	{
		unset($this->datacolor);
	}

	/**
	 * Get max value
	 *
	 * @return	int		Max value
	 */
	function GetMaxValueInData()
	{
		$k = 0;
		$vals = array();

		$nblines = count($this->data);
		$nbvalues = count($this->data[0]) - 1;

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

	/**
	 * Return min value of all data
	 *
	 * @return	int		Min value of all data
	 */
	function GetMinValueInData()
	{
		$k = 0;
		$vals = array();

		$nblines = count($this->data);
		$nbvalues = count($this->data[0]) - 1;

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
	 * @return 	double		Max value of all data
	 */
	function GetFloorMinValue()
	{
		$min = $this->GetMinValueInData();
		if ($min == '') $min=0;
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
	 * Build a graph into memory using correct library  (may also be wrote on disk, depending on library used)
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk
	 * @return	integer|null
	 */
	function draw($file,$fileurl='')
	{
		if (empty($file))
		{
			$this->error="Call to draw method was made with empty value for parameter file.";
			dol_syslog(get_class($this)."::draw ".$this->error, LOG_ERR);
			return -2;
		}
		if (! is_array($this->data) || count($this->data) < 1)
		{
			$this->error="Call to draw method was made but SetData was not called or called with an empty dataset for parameters";
			dol_syslog(get_class($this)."::draw ".$this->error, LOG_ERR);
			return -1;
		}
		$call = "draw_".$this->_library;
		call_user_func_array(array($this,$call), array($file,$fileurl));
	}


	/**
	 * Build a graph onto disk using Artichow library and return img string to it
	 *
	 * @param	string	$file    	Image file name to use if we save onto disk
	 * @param	string	$fileurl	Url path to show image if saved onto disk
	 * @return	void
	 */
	private function draw_artichow($file,$fileurl)
	{
		global $artichow_defaultfont;

		dol_syslog(get_class($this)."::draw_artichow this->type=".join(',',$this->type));

		if (! defined('SHADOW_RIGHT_TOP'))  define('SHADOW_RIGHT_TOP',3);
		if (! defined('LEGEND_BACKGROUND')) define('LEGEND_BACKGROUND',2);
		if (! defined('LEGEND_LINE'))       define('LEGEND_LINE',1);

		// Create graph
		$classname='';
		if (! isset($this->type[0]) || $this->type[0] == 'bars')  $classname='BarPlot';    // Only one type (first one) is supported by artichow
		else if ($this->type[0] == 'lines') $classname='LinePlot';
		else $classname='TypeUnknown';
		include_once ARTICHOW_PATH.$classname.'.class.php';

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
			//print $artichow_defaultfont;exit;
			$graph->title->setFont(new $artichow_defaultfont(10));
		}

		if (is_array($this->bgcolor)) $graph->setBackgroundColor($bgcolor);
		else $graph->setBackgroundGradient($colorgradient);

		$group = new PlotGroup;
		//$group->setSpace(5, 5, 0, 0);

		$paddleft=50;
		$paddright=10;
		$strl=dol_strlen(max(abs($this->MaxValue),abs($this->MinValue)));
		if ($strl > 6) $paddleft += ($strl * 4);
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
		$nblot=count($this->data[0])-1;

		while ($i < $nblot)
		{
			$x=0;
			$values=array();
			foreach($this->data as $key => $valarray)
			{
				$legends[$x] = $valarray[0];
				$values[$x]  = $valarray[$i+1];
				$x++;
			}

			// We fix unknown values to null
			$newvalues=array();
			foreach($values as $val)
			{
				$newvalues[]=(is_numeric($val) ? $val : null);
			}


			if ($this->type[0] == 'bars')
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
				$plot->setBarGradient(new LinearGradient($colorbis, $color, 90));

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

			if ($this->type[0] == 'lines')
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
				$plot->setFillGradient(new LinearGradient($colorter, $colorbis, 90));

				$plot->xAxis->setLabelText($legends);

				// Le mode automatique est plus efficace
				$plot->SetYMax($this->MaxValue);
				$plot->SetYMin($this->MinValue);
				//$plot->setYAxis(0);
				//$plot->hideLine(true);
			}

			//$plot->reduce(80);		// Evite temps d'affichage trop long et nombre de ticks absisce satures

			$group->legend->setTextFont(new $artichow_defaultfont(10)); // This is to force Artichow to use awFileFontDriver to
			// solve a bug in Artichow with UTF8
			if (count($this->Legend))
			{
				if ($this->type[0] == 'bars')  $group->legend->add($plot, $this->Legend[$i], LEGEND_BACKGROUND);
				if ($this->type[0] == 'lines') $group->legend->add($plot, $this->Legend[$i], LEGEND_LINE);
			}
			$group->add($plot);

			$i++;
		}

		$group->axis->bottom->setLabelText($legends);
		$group->axis->bottom->label->setFont(new $artichow_defaultfont(7));

		//print $group->axis->bottom->getLabelNumber();
		if ($this->labelInterval > 0) $group->axis->bottom->setLabelInterval($this->labelInterval);

		$graph->add($group);

		// Generate file
		$graph->draw($file);

		$this->stringtoshow='<!-- Build using '.$this->_library.' --><img src="'.$fileurl.'" title="'.dol_escape_htmltag($this->title?$this->title:$this->YLabel).'" alt="'.dol_escape_htmltag($this->title?$this->title:$this->YLabel).'">';
	}


	/**
	 * Build a graph using JFlot library. Input when calling this method should be:
	 *	$this->data  = array(array(0=>'labelxA',1=>yA),  array('labelxB',yB));
	 *	$this->data  = array(array(0=>'labelxA',1=>yA1,...,n=>yAn), array('labelxB',yB1,...yBn));   // or when there is n series to show for each x
	 *  $this->data  = array(array('label'=>'labelxA','data'=>yA),  array('labelxB',yB));			// Syntax deprecated
	 *  $this->legend= array("Val1",...,"Valn");													// list of n series name
	 *  $this->type  = array('bars',...'lines'); or array('pie')
	 *  $this->mode = 'depth' ???
	 *  $this->bgcolorgrid
	 *  $this->datacolor
	 *  $this->shownodatagraph
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk. Never used here.
	 * @return	void
	 */
	private function draw_jflot($file,$fileurl)
	{
		global $artichow_defaultfont;

		dol_syslog(get_class($this)."::draw_jflot this->type=".join(',',$this->type)." this->MaxValue=".$this->MaxValue);

		if (empty($this->width) && empty($this->height))
		{
			print 'Error width or height not set';
			return;
		}

		$legends=array();
		$nblot=count($this->data[0])-1;    // -1 to remove legend
		if ($nblot < 0) dol_print_error('', 'Bad value for property ->data. Must be set by mydolgraph->SetData before calling mydolgrapgh->draw');
		$firstlot=0;
		// Works with line but not with bars
		//if ($nblot > 2) $firstlot = ($nblot - 2);        // We limit nblot to 2 because jflot can't manage more than 2 bars on same x

		$i=$firstlot;
		$serie=array();
		while ($i < $nblot)	// Loop on each serie
		{
			$values=array();	// Array with horizontal y values (specific values of a serie) for each abscisse x
			$serie[$i]="var d".$i." = [];\n";

			// Fill array $values
			$x=0;
			foreach($this->data as $valarray)	// Loop on each x
			{
				$legends[$x] = $valarray[0];
				$values[$x]  = (is_numeric($valarray[$i+1]) ? $valarray[$i+1] : null);
				$x++;
			}

			// TODO Avoid push by adding generated long array...
			if (isset($this->type[$firstlot]) && $this->type[$firstlot] == 'pie')
			{
				foreach($values as $x => $y) {
					if (isset($y)) $serie[$i].='d'.$i.'.push({"label":"'.dol_escape_js($legends[$x]).'", "data":'.$y.'});'."\n";
				}
			}
			else
			{
				foreach($values as $x => $y) {
					if (isset($y)) $serie[$i].='d'.$i.'.push(['.$x.', '.$y.']);'."\n";
				}
			}

			unset($values);
			$i++;
		}
		$tag=dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file),'_',array('-','.'))));

		$this->stringtoshow ='<!-- Build using '.$this->_library.' -->'."\n";
		if (! empty($this->title)) $this->stringtoshow.='<div align="center" class="dolgraphtitle'.(empty($this->cssprefix)?'':' dolgraphtitle'.$this->cssprefix).'">'.$this->title.'</div>';
		if (! empty($this->shownographyet))
		{
		  $this->stringtoshow.='<div style="width:'.$this->width.'px;height:'.$this->height.'px;" class="nographyet"></div>';
		  $this->stringtoshow.='<div class="nographyettext">'.$langs->trans("NotEnoughDataYet").'</div>';
		  return;
		}
		$this->stringtoshow.='<div id="placeholder_'.$tag.'" style="width:'.$this->width.'px;height:'.$this->height.'px;" class="dolgraph'.(empty($this->cssprefix)?'':' dolgraph'.$this->cssprefix).'"></div>'."\n";

		$this->stringtoshow.='<script id="'.$tag.'">'."\n";
		$this->stringtoshow.='$(function () {'."\n";
		$i=$firstlot;
		while ($i < $nblot)
		{
			$this->stringtoshow.=$serie[$i];
			$i++;
		}
		$this->stringtoshow.="\n";

		// Special case for Graph of type 'pie'
		if (isset($this->type[$firstlot]) && $this->type[$firstlot] == 'pie')
		{
			$datacolor=array();
			foreach($this->datacolor as $val) $datacolor[]="#".sprintf("%02x%02x%02x",$val[0],$val[1],$val[2]);

			$urltemp='';	// TODO Add support for url link into labels
			$showlegend=$this->showlegend;
			$showpointvalue=$this->showpointvalue;
			$showpercent=$this->showpercent;

			$this->stringtoshow.= '
			function plotWithOptions_'.$tag.'() {
			$.plot($("#placeholder_'.$tag.'"), d0,
			{
				series: {
					pie: {
						show: true,
						radius: 0.8,
						'.($this->combine ? '
						combine: {
						 	threshold: '.$this->combine.'
						},' : '') . '
						label: {
							show: true,
							radius: 0.9,
							formatter: function(label, series) {
								var percent=Math.round(series.percent);
								var number=series.data[0][1];
								return \'';
								$this->stringtoshow.='<div style="font-size:8pt;text-align:center;padding:2px;color:black;">';
								if ($urltemp) $this->stringtoshow.='<a style="color: #FFFFFF;" border="0" href="'.$urltemp.'">';
								$this->stringtoshow.='\'+';
								$this->stringtoshow.=($showlegend?'':'label+\' \'+');	// Hide label if already shown in legend
								$this->stringtoshow.=($showpointvalue?'number+':'');
								$this->stringtoshow.=($showpercent?'\'<br/>\'+percent+\'%\'+':'');
								$this->stringtoshow.='\'';
								if ($urltemp) $this->stringtoshow.='</a>';
								$this->stringtoshow.='</div>\';
							},
							background: {
							opacity: 0.0,
							color: \'#000000\'
						}
					}
				}
			},
			zoom: {
				interactive: true
			},
			pan: {
				interactive: true
			},';
			if (count($datacolor))
			{
				$this->stringtoshow.='colors: '.(! empty($data['seriescolor']) ? json_encode($data['seriescolor']) : json_encode($datacolor)).',';
			}
			$this->stringtoshow.='legend: {show: '.($showlegend?'true':'false').', position: \'ne\' }
		});
		}'."\n";
		}
		// Other cases, graph of type 'bars', 'lines'
		else
		{
			// Add code to support tooltips
			$this->stringtoshow.='
			function showTooltip_'.$tag.'(x, y, contents) {
				$(\'<div id="tooltip_'.$tag.'">\' + contents + \'</div>\').css({
					position: \'absolute\',
					display: \'none\',
					top: y + 5,
					left: x + 5,
					border: \'1px solid #ddd\',
					padding: \'2px\',
					\'background-color\': \'#ffe\',
					width: 200,
					opacity: 0.80
				}).appendTo("body").fadeIn(20);
			}

			var previousPoint = null;
			$("#placeholder_'.$tag.'").bind("plothover", function (event, pos, item) {
				$("#x").text(pos.x.toFixed(2));
				$("#y").text(pos.y.toFixed(2));

				if (item) {
					if (previousPoint != item.dataIndex) {
						previousPoint = item.dataIndex;

						$("#tooltip").remove();
						/* console.log(item); */
						var x = item.datapoint[0].toFixed(2);
						var y = item.datapoint[1].toFixed(2);
						var z = item.series.xaxis.ticks[item.dataIndex].label;
						';
						if ($this->showpointvalue > 0) $this->stringtoshow.='
							showTooltip_'.$tag.'(item.pageX, item.pageY, item.series.label + "<br>" + z + " => " + y);
						';
						$this->stringtoshow.='
					}
				}
				else {
					$("#tooltip_'.$tag.'").remove();
					previousPoint = null;
				}
			});
			';

			$this->stringtoshow.='var stack = null, steps = false;'."\n";

			$this->stringtoshow.='function plotWithOptions_'.$tag.'() {'."\n";
			$this->stringtoshow.='$.plot($("#placeholder_'.$tag.'"), [ '."\n";
			$i=$firstlot;
			while ($i < $nblot)
			{
				if ($i > $firstlot) $this->stringtoshow.=', '."\n";
				$color=sprintf("%02x%02x%02x",$this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2]);
				$this->stringtoshow.='{ ';
				if (! isset($this->type[$i]) || $this->type[$i] == 'bars') $this->stringtoshow.='bars: { show: true, align: "'.($i==$firstlot?'center':'left').'", barWidth: 0.5 }, ';
				if (isset($this->type[$i]) && $this->type[$i] == 'lines')  $this->stringtoshow.='lines: { show: true, fill: false }, ';
				$this->stringtoshow.='color: "#'.$color.'", label: "'.(isset($this->Legend[$i]) ? dol_escape_js($this->Legend[$i]) : '').'", data: d'.$i.' }';
				$i++;
			}
			$this->stringtoshow.="\n".' ], { series: { stack: stack, lines: { fill: false, steps: steps }, bars: { barWidth: 0.6 } }'."\n";

			// Xaxis
			$this->stringtoshow.=', xaxis: { ticks: ['."\n";
			$x=0;
			foreach($this->data as $key => $valarray)
			{
				if ($x > 0) $this->stringtoshow.=', '."\n";
				$this->stringtoshow.= ' ['.$x.', "'.$valarray[0].'"]';
				$x++;
			}
			$this->stringtoshow.='] }'."\n";

			// Yaxis
			$this->stringtoshow.=', yaxis: { min: '.$this->MinValue.', max: '.($this->MaxValue).' }'."\n";

			// Background color
			$color1=sprintf("%02x%02x%02x",$this->bgcolorgrid[0],$this->bgcolorgrid[0],$this->bgcolorgrid[2]);
			$color2=sprintf("%02x%02x%02x",$this->bgcolorgrid[0],$this->bgcolorgrid[1],$this->bgcolorgrid[2]);
			$this->stringtoshow.=', grid: { hoverable: true, backgroundColor: { colors: ["#'.$color1.'", "#'.$color2.'"] } }'."\n";
			//$this->stringtoshow.=', shadowSize: 20'."\n";    TODO Uncommet this
			$this->stringtoshow.='});'."\n";
			$this->stringtoshow.='}'."\n";

		}

		$this->stringtoshow.='plotWithOptions_'.$tag.'();'."\n";
		$this->stringtoshow.='});'."\n";
		$this->stringtoshow.='</script>'."\n";
	}



	/**
	 * Output HTML string to show graph
	 *
	 * @param	int			$shownographyet 	Show graph to say there is not enough data
	 * @return	string							HTML string to show graph
	 */
	function show($shownographyet=0)
	{
		global $langs;

		if ($shownographyet)
		{
			$s= '<div class="nographyet" style="width:'.(preg_match('/%/',$this->width)?$this->width:$this->width.'px').'; height:'.(preg_match('/%/',$this->height)?$this->height:$this->height.'px').';"></div>';
			$s.='<div class="nographyettext">'.$langs->trans("NotEnoughDataYet").'</div>';
			return $s;
		}

		return $this->stringtoshow;
	}


	/**
	 * getDefaultGraphSizeForStats
	 *
	 * @param	string	$direction		'width' or 'height'
	 * @param	string	$defaultsize	Value we want as default size
	 * @return	int						Value of width or height to use by default
	 */
	static function getDefaultGraphSizeForStats($direction,$defaultsize='')
	{
		global $conf;

		if ($direction == 'width')
		{
			if (empty($conf->dol_optimize_smallscreen)) return ($defaultsize ? $defaultsize : '500');
			else return (empty($_SESSION['dol_screen_width']) ? '280' : ($_SESSION['dol_screen_width']-40));
		}
		if ($direction == 'height')
		{
			return (empty($conf->dol_optimize_smallscreen)?($defaultsize?$defaultsize:'200'):'160');
		}
		return 0;
	}

}

