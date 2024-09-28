<?php
/* Copyright (c) 2003-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
 *    $dolgraph->setShowLegend(2);
 *    $dolgraph->setShowPercent(1);
 *    $dolgraph->SetType(array('pie'));
 *    $dolgraph->setHeight('200');
 *    $dolgraph->draw('idofgraph');
 *    print $dolgraph->show($total?0:1);
 */
class DolGraph
{
	public $type = array(); // Array with type of each series. Example: array('bars', 'horizontalbars', 'lines', 'pies', 'piesemicircle', 'polar'...)
	public $mode = 'side'; // Mode bars graph: side, depth
	private $_library; // Graphic library to use (jflot, chart, artichow)

	//! Array of data
	public $data; // Data of graph: array(array('abs1',valA1,valB1), array('abs2',valA2,valB2), ...)
	public $title; // Title of graph
	public $cssprefix = ''; // To add into css styles

	/**
	 * @var int|string 		Width of graph. It can be a numeric for pixels or a string like '100%' or "100px'
	 */
	public $width = 380;
	/**
	 * @var int|string 	Height of graph. It can be a numeric for pixels or a string like '100%' or "100px'
	 */
	public $height = 200;

	public $MaxValue = 0;
	public $MinValue = 0;
	public $SetShading = 0;

	public $horizTickIncrement = -1;
	public $SetNumXTicks = -1;
	public $labelInterval = -1;
	public $YLabel;

	public $hideXGrid = false;
	public $hideXValues = false;
	public $hideYGrid = false;

	public $Legend = array();
	public $LegendWidthMin = 0;
	public $showlegend = 1;
	public $showpointvalue = 1;
	public $showpercent = 0;
	public $combine = 0; // 0.05 if you want to combine records < 5% into "other"
	public $graph; // Object Graph (Artichow, Phplot...)
	/**
	 * @var boolean 					Mirrors graph values
	 */
	public $mirrorGraphValues = false;
	public $tooltipsTitles = null;
	public $tooltipsLabels = null;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	public $bordercolor; // array(R,G,B)
	public $bgcolor; // array(R,G,B)
	public $bgcolorgrid = array(255, 255, 255); // array(R,G,B)
	public $datacolor; // array(array(R,G,B),...)
	public $borderwidth = 1;
	public $borderskip = 'start';

	private $stringtoshow; // To store string to output graph into HTML page


	/**
	 * Constructor
	 *
	 * @param	string	$library		'auto' (default)
	 */
	public function __construct($library = 'auto')
	{
		global $conf;
		global $theme_bordercolor, $theme_datacolor, $theme_bgcolor;

		// Some default values for the case it is not defined into the theme later.
		$this->bordercolor = array(235, 235, 224);
		$this->datacolor = array(array(120, 130, 150), array(160, 160, 180), array(190, 190, 220));
		$this->bgcolor = array(235, 235, 224);

		// For small screen, we prefer a default with of 300
		if (!empty($conf->dol_optimize_smallscreen)) {
			$this->width = 300;
		}

		// Load color of the theme
		$color_file = DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php';
		if (is_readable($color_file)) {
			include $color_file;
			if (isset($theme_bordercolor)) {
				$this->bordercolor = $theme_bordercolor;
			}
			if (isset($theme_datacolor)) {
				$this->datacolor   = $theme_datacolor;
			}
			if (isset($theme_bgcolor)) {
				$this->bgcolor     = $theme_bgcolor;
			}
		}
		//print 'bgcolor: '.join(',',$this->bgcolor).'<br>';

		$this->_library = $library;
		if ($this->_library == 'auto') {
			$this->_library = (!getDolGlobalString('MAIN_JS_GRAPH') ? 'chart' : $conf->global->MAIN_JS_GRAPH);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 *
	 * @param 	float 		$xi		Xi
	 * @return	boolean				True
	 */
	public function SetHorizTickIncrement($xi)
	{
		// phpcs:enable
		$this->horizTickIncrement = $xi;
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 *
	 * @param 	float 		$xt		Xt
	 * @return	boolean				True
	 */
	public function SetNumXTicks($xt)
	{
		// phpcs:enable
		$this->SetNumXTicks = $xt;
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set label interval to reduce number of labels
	 *
	 * @param 	float 		$x		Label interval
	 * @return	boolean				True
	 */
	public function SetLabelInterval($x)
	{
		// phpcs:enable
		$this->labelInterval = $x;
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Hide X grid
	 *
	 * @param	boolean		$bool	XGrid or not
	 * @return	boolean				true
	 */
	public function SetHideXGrid($bool)
	{
		// phpcs:enable
		$this->hideXGrid = $bool;
		return true;
	}

	/**
	 * Hide X Values
	 *
	 * @param	boolean		$bool	XValues or not
	 * @return	boolean				true
	 */
	public function setHideXValues($bool)
	{
		$this->hideXValues = $bool;
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Hide Y grid
	 *
	 * @param	boolean		$bool	YGrid or not
	 * @return	boolean				true
	 */
	public function SetHideYGrid($bool)
	{
		// phpcs:enable
		$this->hideYGrid = $bool;
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set y label
	 *
	 * @param 	string			$label		Y label
	 * @return	void
	 */
	public function SetYLabel($label)
	{
		// phpcs:enable
		$this->YLabel = $label;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set width
	 *
	 * @param 	int|string		$w			Width (Example: 320 or '100%' or '10px')
	 * @return	void
	 */
	public function SetWidth($w)
	{
		// phpcs:enable
		$this->width = $w;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set title
	 *
	 * @param 	string	$title		Title
	 * @return	void
	 */
	public function SetTitle($title)
	{
		// phpcs:enable
		$this->title = $title;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set data
	 *
	 * @param 	array	$data		Data
	 * @return	void
	 * @see draw_jflot() for syntax of data array
	 */
	public function SetData($data)
	{
		// phpcs:enable
		$this->data = $data;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set data color
	 *
	 * @param 	array	$datacolor		Data color array(array(R,G,B),array(R,G,B)...) or array('#......','#......'...)
	 * @return	void
	 */
	public function SetDataColor($datacolor)
	{
		// phpcs:enable
		$this->datacolor = $datacolor;
	}

	/**
	 * Set border color
	 *
	 * @param 	array	$bordercolor		Border Color array(array(R,G,B),array(R,G,B)...) or array('#FFFFFF','#......'...)
	 * @return	void
	 */
	public function setBorderColor($bordercolor)
	{
		$this->bordercolor = $bordercolor;
	}

	/**
	 * Set border width
	 *
	 * @param 	int     $borderwidth 	Border Width
	 * @return	void
	 */
	public function setBorderWidth($borderwidth)
	{
		$this->borderwidth = $borderwidth;
	}

	/**
	 * Set border skip
	 *
	 * @param 	int     $borderskip     Can be 'start' to skip start border, 'end' to skip end border, 'middle' to skip middle border,
	 * 									'false' to not skip any border, 'true' to skip all border
	 * @return	void
	 */
	public function setBorderSkip($borderskip)
	{
		$this->borderskip = $borderskip;
	}

	/**
	 * Set tooltips labels of the graph
	 *
	 * @param 	array	$tooltipsLabels		Tooltips Labels array('...','...'...)
	 * @return	void
	 */
	public function setTooltipsLabels($tooltipsLabels)
	{
		$this->tooltipsLabels = $tooltipsLabels;
	}

	/**
	 * Set tooltips titles of the graph
	 *
	 * @param 	array	$tooltipsTitles		Tooltips Titles array('...','...'...)
	 * @return	void
	 */
	public function setTooltipsTitles($tooltipsTitles)
	{
		$this->tooltipsTitles = $tooltipsTitles;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set type
	 *
	 * @param 	array	$type		Array with type for each series. Example: array('type1', 'type2', ...) where type can be:
	 * 								'pie', 'piesemicircle', 'polar', 'lines', 'linesnopoint', 'bars', 'horizontalbars'...
	 * @return	void
	 */
	public function SetType($type)
	{
		// phpcs:enable
		$this->type = $type;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set legend
	 *
	 * @param 	array	$legend		Legend. Example: array('seriename1','seriname2',...)
	 * @return	void
	 */
	public function SetLegend($legend)
	{
		// phpcs:enable
		$this->Legend = $legend;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set min width
	 *
	 * @param 	int		$legendwidthmin		Min width
	 * @return	void
	 */
	public function SetLegendWidthMin($legendwidthmin)
	{
		// phpcs:enable
		$this->LegendWidthMin = $legendwidthmin;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set max value
	 *
	 * @param 	int		$max			Max value
	 * @return	void
	 */
	public function SetMaxValue($max)
	{
		// phpcs:enable
		$this->MaxValue = $max;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Get max value
	 *
	 * @return	int		Max value
	 */
	public function GetMaxValue()
	{
		// phpcs:enable
		return $this->MaxValue;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set min value
	 *
	 * @param 	int		$min			Min value
	 * @return	void
	 */
	public function SetMinValue($min)
	{
		// phpcs:enable
		$this->MinValue = $min;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Get min value
	 *
	 * @return	int		Max value
	 */
	public function GetMinValue()
	{
		// phpcs:enable
		return $this->MinValue;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set height
	 *
	 * @param 	int|string		$h		Height int or '90%' or '10px'
	 * @return	void
	 */
	public function SetHeight($h)
	{
		// phpcs:enable
		$this->height = $h;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set shading
	 *
	 * @param 	string	$s				Shading
	 * @return	void
	 */
	public function SetShading($s)
	{
		// phpcs:enable
		$this->SetShading = $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set shading
	 *
	 * @param 	string	$s				Shading
	 * @return	void
	 */
	public function SetCssPrefix($s)
	{
		// phpcs:enable
		$this->cssprefix = $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Reset bg color
	 *
	 * @return	void
	 */
	public function ResetBgColor()
	{
		// phpcs:enable
		unset($this->bgcolor);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Reset bgcolorgrid
	 *
	 * @return	void
	 */
	public function ResetBgColorGrid()
	{
		// phpcs:enable
		unset($this->bgcolorgrid);
	}

	/**
	 * Mirror Values of the graph
	 *
	 * @param	boolean 	$mirrorGraphValues	Mirror Values if true and doesn't if false
	 * @return	void
	 */
	public function setMirrorGraphValues($mirrorGraphValues)
	{
		$this->mirrorGraphValues = $mirrorGraphValues;
	}

	/**
	 * Is graph ko
	 *
	 * @return	string		Error
	 */
	public function isGraphKo()
	{
		return $this->error;
	}

	/**
	 * Show legend or not
	 *
	 * @param	int		$showlegend		1=Show legend (default), 0=Hide legend, 2=Show legend on right
	 * @return	void
	 */
	public function setShowLegend($showlegend)
	{
		$this->showlegend = $showlegend;
	}

	/**
	 * Show pointvalue or not
	 *
	 * @param	int		$showpointvalue		1=Show value for each point, as tooltip or inline (default), 0=Hide value, 2=Show values for each series on same point
	 * @return	void
	 */
	public function setShowPointValue($showpointvalue)
	{
		$this->showpointvalue = $showpointvalue;
	}

	/**
	 * Show percent or not
	 *
	 * @param	int		$showpercent		1=Show percent for each point, as tooltip or inline, 0=Hide percent (default)
	 * @return	void
	 */
	public function setShowPercent($showpercent)
	{
		$this->showpercent = $showpercent;
	}



	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Define background color of complete image
	 *
	 * @param	array	$bg_color		array(R,G,B) ou 'onglet' ou 'default'
	 * @return	void
	 */
	public function SetBgColor($bg_color = array(255, 255, 255))
	{
		// phpcs:enable
		global $theme_bgcolor, $theme_bgcoloronglet;

		if (!is_array($bg_color)) {
			if ($bg_color == 'onglet') {
				//print 'ee'.join(',',$theme_bgcoloronglet);
				$this->bgcolor = $theme_bgcoloronglet;
			} else {
				$this->bgcolor = $theme_bgcolor;
			}
		} else {
			$this->bgcolor = $bg_color;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Define background color of grid
	 *
	 * @param	array	$bg_colorgrid		array(R,G,B) ou 'onglet' ou 'default'
	 * @return	void
	 */
	public function SetBgColorGrid($bg_colorgrid = array(255, 255, 255))
	{
		// phpcs:enable
		global $theme_bgcolor, $theme_bgcoloronglet;

		if (!is_array($bg_colorgrid)) {
			if ($bg_colorgrid == 'onglet') {
				//print 'ee'.join(',',$theme_bgcoloronglet);
				$this->bgcolorgrid = $theme_bgcoloronglet;
			} else {
				$this->bgcolorgrid = $theme_bgcolor;
			}
		} else {
			$this->bgcolorgrid = $bg_colorgrid;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Reset data color
	 *
	 * @return	void
	 */
	public function ResetDataColor()
	{
		// phpcs:enable
		unset($this->datacolor);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Get max value among all values of all series
	 *
	 * @return	int		Max value
	 */
	public function GetMaxValueInData()
	{
		// phpcs:enable
		if (!is_array($this->data)) {
			return 0;
		}

		$max = null;

		$nbseries = (empty($this->data[0]) ? 0 : count($this->data[0]) - 1);

		foreach ($this->data as $x) {	// Loop on each x
			for ($i = 0; $i < $nbseries; $i++) {	// Loop on each series
				if (is_null($max)) {
					$max = $x[$i + 1];		// $i+1 because the index 0 is the legend
				} elseif ($max < $x[$i + 1]) {
					$max = $x[$i + 1];
				}
			}
		}

		return $max;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return min value of all values of all series
	 *
	 * @return	int		Min value of all data
	 */
	public function GetMinValueInData()
	{
		// phpcs:enable
		if (!is_array($this->data)) {
			return 0;
		}

		$min = null;

		$nbseries = (empty($this->data[0]) ? 0 : count($this->data[0]) - 1);

		foreach ($this->data as $x) {	// Loop on each x
			for ($i = 0; $i < $nbseries; $i++) {	// Loop on each series
				if (is_null($min)) {
					$min = $x[$i + 1];		// $i+1 because the index 0 is the legend
				} elseif ($min > $x[$i + 1]) {
					$min = $x[$i + 1];
				}
			}
		}

		return $min;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return max value of all data
	 *
	 * @return 	int		Max value of all data
	 */
	public function GetCeilMaxValue()
	{
		// phpcs:enable
		$max = $this->GetMaxValueInData();
		if ($max != 0) {
			$max++;
		}
		$size = dol_strlen((string) abs(ceil($max)));
		$factor = 1;
		for ($i = 0; $i < ($size - 1); $i++) {
			$factor *= 10;
		}

		$res = 0;
		if (is_numeric($max)) {
			$res = ceil($max / $factor) * $factor;
		}

		//print "max=".$max." res=".$res;
		return (int) $res;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return min value of all data
	 *
	 * @return 	double		Max value of all data
	 */
	public function GetFloorMinValue()
	{
		// phpcs:enable
		$min = $this->GetMinValueInData();
		if ($min == '') {
			$min = 0;
		}
		if ($min != 0) {
			$min--;
		}
		$size = dol_strlen((string) abs(floor($min)));
		$factor = 1;
		for ($i = 0; $i < ($size - 1); $i++) {
			$factor *= 10;
		}

		$res = floor($min / $factor) * $factor;

		//print "min=".$min." res=".$res;
		return $res;
	}

	/**
	 * Build a graph into memory using correct library  (may also be wrote on disk, depending on library used)
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk
	 * @return	mixed|boolean
	 */
	public function draw($file, $fileurl = '')
	{
		if (empty($file)) {
			$this->error = "Call to draw method was made with empty value for parameter file.";
			dol_syslog(get_class($this) . "::draw " . $this->error, LOG_ERR);
			return -2;
		}
		if (!is_array($this->data)) {
			$this->error = "Call to draw method was made but SetData was not called or called with an empty dataset for parameters";
			dol_syslog(get_class($this) . "::draw " . $this->error, LOG_ERR);
			return -1;
		}
		if (count($this->data) < 1) {
			$this->error = "Call to draw method was made but SetData was is an empty dataset";
			dol_syslog(get_class($this) . "::draw " . $this->error, LOG_WARNING);
		}
		$call = "draw_" . $this->_library;	// Example "draw_jflot"

		return call_user_func_array(array($this, $call), array($file, $fileurl));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Build a graph into ->stringtoshow using the JFlot library. Input when calling this method should be:
	 *	$this->data  = array(array(0=>'labelxA',1=>yA),  array('labelxB',yB));
	 *	$this->data  = array(array(0=>'labelxA',1=>yA1,...,n=>yAn), array('labelxB',yB1,...yBn));   // when there is n series to show for each x
	 *  $this->data  = array(array('label'=>'labelxA','data'=>yA),  array('labelxB',yB));			// Syntax deprecated
	 *  $this->legend= array("Val1",...,"Valn");													// list of n series name
	 *  $this->type  = array('bars',...'lines','linesnopoint'); or array('pie') or array('polar')
	 *  $this->mode = 'depth' ???
	 *  $this->bgcolorgrid
	 *  $this->datacolor
	 *  $this->shownodatagraph
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk. Never used here.
	 * @return	void
	 */
	private function draw_jflot($file, $fileurl) // @phpstan-ignore-line
	{
		// phpcs:enable
		global $langs;

		dol_syslog(get_class($this) . "::draw_jflot this->type=" . implode(',', $this->type) . " this->MaxValue=" . $this->MaxValue);

		if (empty($this->width) && empty($this->height)) {
			print 'Error width or height not set';
			return;
		}

		$legends = array();
		$nblot = 0;
		if (is_array($this->data) && is_array($this->data[0])) {
			$nblot = count($this->data[0]) - 1; // -1 to remove legend
		}
		if ($nblot < 0) {
			dol_syslog('Bad value for property ->data. Must be set by mydolgraph->SetData before calling mydolgrapgh->draw', LOG_WARNING);
		}
		$firstlot = 0;
		// Works with line but not with bars
		//if ($nblot > 2) $firstlot = ($nblot - 2);        // We limit nblot to 2 because jflot can't manage more than 2 bars on same x

		$i = $firstlot;
		$series = array();
		while ($i < $nblot) {	// Loop on each series
			$values = array(); // Array with horizontal y values (specific values of a series) for each abscisse x
			$series[$i] = "var d" . $i . " = [];\n";

			// Fill array $values
			$x = 0;
			foreach ($this->data as $valarray) {	// Loop on each x
				$legends[$x] = $valarray[0];
				$values[$x]  = (is_numeric($valarray[$i + 1]) ? $valarray[$i + 1] : null);
				$x++;
			}

			if (isset($this->type[$firstlot]) && in_array($this->type[$firstlot], array('pie', 'piesemicircle', 'polar'))) {
				foreach ($values as $x => $y) {
					if (isset($y)) {
						$series[$i] .= 'd' . $i . '.push({"label":"' . dol_escape_js($legends[$x]) . '", "data":' . $y . '});' . "\n";
					}
				}
			} else {
				foreach ($values as $x => $y) {
					if (isset($y)) {
						$series[$i] .= 'd' . $i . '.push([' . $x . ', ' . $y . ']);' . "\n";
					}
				}
			}

			unset($values);
			$i++;
		}
		$tag = dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file), '_', array('-', '.'))));

		$this->stringtoshow = '<!-- Build using jflot -->' . "\n";
		if (!empty($this->title)) {
			$this->stringtoshow .= '<div class="center dolgraphtitle' . (empty($this->cssprefix) ? '' : ' dolgraphtitle' . $this->cssprefix) . '">' . $this->title . '</div>';
		}
		if (!empty($this->shownographyet)) {
			$this->stringtoshow .= '<div style="width:' . $this->width . 'px;height:' . $this->height . 'px;" class="nographyet"></div>';
			$this->stringtoshow .= '<div class="nographyettext margintoponly">' . $langs->trans("NotEnoughDataYet") . '...</div>';
			return;
		}

		// Start the div that will contains all the graph
		$dolxaxisvertical = '';
		if (count($this->data) > 20) {
			$dolxaxisvertical = 'dol-xaxis-vertical';
		}
		$this->stringtoshow .= '<div id="placeholder_' . $tag . '" style="width:' . $this->width . 'px;height:' . $this->height . 'px;" class="dolgraph' . (empty($dolxaxisvertical) ? '' : ' ' . $dolxaxisvertical) . (empty($this->cssprefix) ? '' : ' dolgraph' . $this->cssprefix) . ' center"></div>' . "\n";

		$this->stringtoshow .= '<script nonce="'.getNonce().'" id="' . $tag . '">' . "\n";
		$this->stringtoshow .= '$(function () {' . "\n";
		$i = $firstlot;
		if ($nblot < 0) {
			$this->stringtoshow .= '<!-- No series of data -->' . "\n";
		} else {
			while ($i < $nblot) {
				$this->stringtoshow .= '<!-- Series ' . $i . ' -->' . "\n";
				$this->stringtoshow .= $series[$i] . "\n";
				$i++;
			}
		}
		$this->stringtoshow .= "\n";

		// Special case for Graph of type 'pie'
		if (isset($this->type[$firstlot]) && in_array($this->type[$firstlot], array('pie', 'piesemicircle', 'polar'))) {
			$datacolor = array();
			foreach ($this->datacolor as $val) {
				if (is_array($val)) {
					$datacolor[] = "#" . sprintf("%02x%02x%02x", $val[0], $val[1], $val[2]); // If datacolor is array(R, G, B)
				} else {
					$datacolor[] = "#" . str_replace(array('#', '-'), '', $val); // If $val is '124' or '#124'
				}
			}

			$urltemp = ''; // TODO Add support for url link into labels
			$showlegend = $this->showlegend;
			$showpointvalue = $this->showpointvalue;
			$showpercent = $this->showpercent;

			$this->stringtoshow .= '
			function plotWithOptions_' . $tag . '() {
			$.plot($("#placeholder_' . $tag . '"), d0,
			{
				series: {
					pie: {
						show: true,
						radius: 0.8,
						' . ($this->combine ? '
						combine: {
						 	threshold: ' . $this->combine . '
						},' : '') . '
						label: {
							show: true,
							radius: 0.9,
							formatter: function(label, series) {
								var percent=Math.round(series.percent);
								var number=series.data[0][1];
								return \'';
			$this->stringtoshow .= '<span style="font-size:8pt;text-align:center;padding:2px;color:black;">';
			if ($urltemp) {
				$this->stringtoshow .= '<a style="color: #FFFFFF;" border="0" href="' . $urltemp . '">';
			}
			$this->stringtoshow .= '\'+';
			$this->stringtoshow .= ($showlegend ? '' : 'label+\' \'+'); // Hide label if already shown in legend
			$this->stringtoshow .= ($showpointvalue ? 'number+' : '');
			$this->stringtoshow .= ($showpercent ? '\'<br>\'+percent+\'%\'+' : '');
			$this->stringtoshow .= '\'';
			if ($urltemp) {
				$this->stringtoshow .= '</a>';
			}
			$this->stringtoshow .= '</span>\';
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
			if (count($datacolor)) {
				$this->stringtoshow .= 'colors: ' . json_encode($datacolor) . ',';
			}
			$this->stringtoshow .= 'legend: {show: ' . ($showlegend ? 'true' : 'false') . ', position: \'ne\' }
		});
		}' . "\n";
		} else {
			// Other cases, graph of type 'bars', 'lines'
			// Add code to support tooltips
			// TODO: remove js css and use graph-tooltip-inner class instead by adding css in each themes
			$this->stringtoshow .= '
			function showTooltip_' . $tag . '(x, y, contents) {
				$(\'<div class="graph-tooltip-inner" id="tooltip_' . $tag . '">\' + contents + \'</div>\').css({
					position: \'absolute\',
					display: \'none\',
					top: y + 10,
					left: x + 15,
					border: \'1px solid #000\',
					padding: \'5px\',
					\'background-color\': \'#000\',
					\'color\': \'#fff\',
					\'font-weight\': \'bold\',
					width: 200,
					opacity: 0.80
				}).appendTo("body").fadeIn(100);
			}

			var previousPoint = null;
			$("#placeholder_' . $tag . '").bind("plothover", function (event, pos, item) {
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
			if ($this->showpointvalue > 0) {
				$this->stringtoshow .= '
							showTooltip_' . $tag . '(item.pageX, item.pageY, item.series.label + "<br>" + z + " => " + y);
						';
			}
			$this->stringtoshow .= '
					}
				}
				else {
					$("#tooltip_' . $tag . '").remove();
					previousPoint = null;
				}
			});
			';

			$this->stringtoshow .= 'var stack = null, steps = false;' . "\n";

			$this->stringtoshow .= 'function plotWithOptions_' . $tag . '() {' . "\n";
			$this->stringtoshow .= '$.plot($("#placeholder_' . $tag . '"), [ ' . "\n";
			$i = $firstlot;
			while ($i < $nblot) {
				if ($i > $firstlot) {
					$this->stringtoshow .= ', ' . "\n";
				}
				$color = sprintf("%02x%02x%02x", $this->datacolor[$i][0], $this->datacolor[$i][1], $this->datacolor[$i][2]);
				$this->stringtoshow .= '{ ';
				if (!isset($this->type[$i]) || $this->type[$i] == 'bars') {
					if ($nblot == 3) {
						if ($i == $firstlot) {
							$align = 'right';
						} elseif ($i == $firstlot + 1) {
							$align = 'center';
						} else {
							$align = 'left';
						}
						$this->stringtoshow .= 'bars: { lineWidth: 1, show: true, align: "' . $align . '", barWidth: 0.45 }, ';
					} else {
						$this->stringtoshow .= 'bars: { lineWidth: 1, show: true, align: "' . ($i == $firstlot ? 'center' : 'left') . '", barWidth: 0.5 }, ';
					}
				}
				if (isset($this->type[$i]) && ($this->type[$i] == 'lines' || $this->type[$i] == 'linesnopoint')) {
					$this->stringtoshow .= 'lines: { show: true, fill: false }, points: { show: ' . ($this->type[$i] == 'linesnopoint' ? 'false' : 'true') . ' }, ';
				}
				$this->stringtoshow .= 'color: "#' . $color . '", label: "' . (isset($this->Legend[$i]) ? dol_escape_js($this->Legend[$i]) : '') . '", data: d' . $i . ' }';
				$i++;
			}
			// shadowSize: 0 -> Drawing is faster without shadows
			$this->stringtoshow .= "\n" . ' ], { series: { shadowSize: 0, stack: stack, lines: { fill: false, steps: steps }, bars: { barWidth: 0.6,  fillColor: { colors: [{opacity: 0.9 }, {opacity: 0.85}] }} }' . "\n";

			// Xaxis
			$this->stringtoshow .= ', xaxis: { ticks: [' . "\n";
			$x = 0;
			foreach ($this->data as $key => $valarray) {
				if ($x > 0) {
					$this->stringtoshow .= ', ' . "\n";
				}
				$this->stringtoshow .= ' [' . $x . ', "' . $valarray[0] . '"]';
				$x++;
			}
			$this->stringtoshow .= '] }' . "\n";

			// Yaxis
			$this->stringtoshow .= ', yaxis: { min: ' . $this->MinValue . ', max: ' . ($this->MaxValue) . ' }' . "\n";

			// Background color
			$color1 = sprintf("%02x%02x%02x", $this->bgcolorgrid[0], $this->bgcolorgrid[0], $this->bgcolorgrid[2]);
			$color2 = sprintf("%02x%02x%02x", $this->bgcolorgrid[0], $this->bgcolorgrid[1], $this->bgcolorgrid[2]);
			$this->stringtoshow .= ', grid: { hoverable: true, backgroundColor: { colors: ["#' . $color1 . '", "#' . $color2 . '"] }, borderWidth: 1, borderColor: \'#e6e6e6\', tickColor  : \'#e6e6e6\' }' . "\n";
			$this->stringtoshow .= '});' . "\n";
			$this->stringtoshow .= '}' . "\n";
		}

		$this->stringtoshow .= 'plotWithOptions_' . $tag . '();' . "\n";
		$this->stringtoshow .= '});' . "\n";
		$this->stringtoshow .= '</script>' . "\n";
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Build a graph using Chart library. Input when calling this method should be:
	 *	$this->data  = array(array(0=>'labelxA',1=>yA),  array('labelxB',yB));
	 *	$this->data  = array(array(0=>'labelxA',1=>yA1,...,n=>yAn), array('labelxB',yB1,...yBn));   // when there is n series to show for each x
	 *  $this->data  = array(array('label'=>'labelxA','data'=>yA),  array('labelxB',yB));			// Syntax deprecated
	 *  $this->legend= array("Val1",...,"Valn");													// list of n series name
	 *  $this->type  = array('bars',...'lines', 'linesnopoint'); or array('pie') or array('polar') or array('piesemicircle');
	 *  $this->mode = 'depth' ???
	 *  $this->bgcolorgrid
	 *  $this->datacolor
	 *  $this->shownodatagraph
	 *
	 * @param	string	$file    	Image file name to use to save onto disk (also used as javascript unique id)
	 * @param	string	$fileurl	Url path to show image if saved onto disk. Never used here.
	 * @return	void
	 */
	private function draw_chart($file, $fileurl) // @phpstan-ignore-line
	{
		// phpcs:enable
		global $langs;

		dol_syslog(get_class($this) . "::draw_chart this->type=" . implode(',', $this->type) . " this->MaxValue=" . $this->MaxValue);

		if (empty($this->width) && empty($this->height)) {
			print 'Error width or height not set';
			return;
		}

		$showlegend = $this->showlegend;
		$bordercolor = "";

		$legends = array();
		$nblot = 0;
		if (is_array($this->data)) {
			foreach ($this->data as $valarray) {      // Loop on each x
				$nblot = max($nblot, count($valarray) - 1); // -1 to remove legend
			}
		}
		//var_dump($nblot);
		if ($nblot < 0) {
			dol_syslog('Bad value for property ->data. Must be set by mydolgraph->SetData before calling mydolgrapgh->draw', LOG_WARNING);
		}
		$firstlot = 0;
		// Works with line but not with bars
		//if ($nblot > 2) $firstlot = ($nblot - 2);        // We limit nblot to 2 because jflot can't manage more than 2 bars on same x

		$series = array();
		$arrayofgroupslegend = array();
		//var_dump($this->data);

		$i = $firstlot;
		while ($i < $nblot) {	// Loop on each series
			$values = array(); // Array with horizontal y values (specific values of a series) for each abscisse x (with x=0,1,2,...)
			$series[$i] = "";

			// Fill array $series from $this->data
			$x = 0;
			foreach ($this->data as $valarray) {	// Loop on each x
				$legends[$x] = (array_key_exists('label', $valarray) ? $valarray['label'] : $valarray[0]);
				$array_of_ykeys = array_keys($valarray);
				$alabelexists = 1;
				$tmpykey = explode('_', ($array_of_ykeys[$i + ($alabelexists ? 1 : 0)]), 3);
				if (isset($tmpykey[2]) && (!empty($tmpykey[2]) || $tmpykey[2] == '0')) {		// This is a 'Group by' array
					$tmpvalue = (array_key_exists('y_' . $tmpykey[1] . '_' . $tmpykey[2], $valarray) ? $valarray['y_' . $tmpykey[1] . '_' . $tmpykey[2]] : $valarray[$i + 1]);
					$values[$x] = (is_numeric($tmpvalue) ? $tmpvalue : null);
					$arrayofgroupslegend[$i] = array(
						'stacknum' => $tmpykey[1],
						'legend' => $this->Legend[$tmpykey[1]],
						'legendwithgroup' => $this->Legend[$tmpykey[1]] . ' - ' . $tmpykey[2]
					);
				} else {
					$tmpvalue = (array_key_exists('y_' . $i, $valarray) ? $valarray['y_' . $i] : $valarray[$i + 1]);
					//var_dump($i.'_'.$x.'_'.$tmpvalue);
					$values[$x] = (is_numeric($tmpvalue) ? $tmpvalue : null);
				}
				$x++;
			}
			//var_dump($values);
			$j = 0;
			foreach ($values as $x => $y) {
				if (isset($y)) {
					$series[$i] .= ($j > 0 ? ", " : "") . $y;
				} else {
					$series[$i] .= ($j > 0 ? ", " : "") . 'null';
				}
				$j++;
			}

			$values = null; // Free mem
			$i++;
		}
		//var_dump($series);
		//var_dump($arrayofgroupslegend);

		$tag = dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file), '_', array('-', '.'))));

		$this->stringtoshow = '<!-- Build using chart -->' . "\n";
		if (!empty($this->title)) {
			$this->stringtoshow .= '<div class="center dolgraphtitle' . (empty($this->cssprefix) ? '' : ' dolgraphtitle' . $this->cssprefix) . '">' . $this->title . '</div>';
		}
		if (!empty($this->shownographyet)) {
			$this->stringtoshow .= '<div style="width:' . $this->width . (strpos($this->width, '%') > 0 ? '' : 'px') . '; height:' . $this->height . 'px;" class="nographyet"></div>';
			$this->stringtoshow .= '<div class="nographyettext margintoponly">' . $langs->trans("NotEnoughDataYet") . '...</div>';
			return;
		}

		// Start the div that will contains all the graph
		$dolxaxisvertical = '';
		if (count($this->data) > 20) {
			$dolxaxisvertical = 'dol-xaxis-vertical';
		}
		// No height for the pie graph
		$cssfordiv = 'dolgraphchart';
		if (isset($this->type[$firstlot])) {
			$cssfordiv .= ' dolgraphchar' . $this->type[$firstlot];
		}
		$this->stringtoshow .= '<div id="placeholder_'.$tag.'" style="min-height: '.$this->height.(strpos((string) $this->height, '%') > 0 ? '' : 'px').'; max-height: '.(strpos((string) $this->height, '%') > 0 ? $this->height : ((int) $this->height + 100) . 'px').'; width:'.$this->width.(strpos((string) $this->width, '%') > 0 ? '' : 'px').';" class="'.$cssfordiv.' dolgraph'.(empty($dolxaxisvertical) ? '' : ' '.$dolxaxisvertical).(empty($this->cssprefix) ? '' : ' dolgraph'.$this->cssprefix).' center">'."\n";
		$this->stringtoshow .= '<canvas id="canvas_'.$tag.'"></canvas></div>'."\n";

		$this->stringtoshow .= '<script nonce="'.getNonce().'" id="' . $tag . '">' . "\n";
		$i = $firstlot;
		if ($nblot < 0) {
			$this->stringtoshow .= '<!-- No series of data -->';
		} else {
			while ($i < $nblot) {
				//$this->stringtoshow .= '<!-- Series '.$i.' -->'."\n";
				//$this->stringtoshow .= $series[$i]."\n";
				$i++;
			}
		}
		$this->stringtoshow .= "\n";

		// Special case for Graph of type 'pie', 'piesemicircle', or 'polar'
		if (isset($this->type[$firstlot]) && (in_array($this->type[$firstlot], array('pie', 'polar', 'piesemicircle')))) {
			$type = $this->type[$firstlot]; // pie or polar
			//$this->stringtoshow .= 'var options = {' . "\n";
			$this->stringtoshow .= 'var options = { maintainAspectRatio: false, aspectRatio: 2.5, ';


			$legendMaxLines = 0; // Does not work

			/* For Chartjs v2.9 */
			if (empty($showlegend)) {
				$this->stringtoshow .= 'legend: { display: false }, ';
			} else {
				$this->stringtoshow .= 'legend: { labels: { boxWidth: 15 }, position: \'' . ($showlegend == 2 ? 'right' : 'top') . '\'';
				if (!empty($legendMaxLines)) {
					$this->stringtoshow .= ', maxLines: ' . $legendMaxLines;
				}
				$this->stringtoshow .= ' }, ' . "\n";
			}

			/* For Chartjs v3.5 */
			$this->stringtoshow .= 'plugins: { ';
			if (empty($showlegend)) {
				$this->stringtoshow .= 'legend: { display: false }, ';
			} else {
				$this->stringtoshow .= 'legend: { labels: { boxWidth: 15 }, position: \'' . ($showlegend == 2 ? 'right' : 'top') . '\'';
				if (!empty($legendMaxLines)) {
					$this->stringtoshow .= ', maxLines: ' . $legendMaxLines;
				}
				$this->stringtoshow .= ' }, ' . "\n";
			}
			$this->stringtoshow .= ' }, ' . "\n";


			if ($this->type[$firstlot] == 'piesemicircle') {
				$this->stringtoshow .= 'circumference: Math.PI,' . "\n";
				$this->stringtoshow .= 'rotation: -Math.PI,' . "\n";
			}
			$this->stringtoshow .= 'elements: { arc: {' . "\n";
			// Color of each arc
			$this->stringtoshow .= 'backgroundColor: [';
			$i = 0;
			$foundnegativecolor = 0;
			foreach ($legends as $val) {	// Loop on each series
				if ($i > 0) {
					$this->stringtoshow .= ', ' . "\n";
				}
				if (is_array($this->datacolor[$i])) {
					$color = 'rgb(' . $this->datacolor[$i][0] . ', ' . $this->datacolor[$i][1] . ', ' . $this->datacolor[$i][2] . ')'; // If datacolor is array(R, G, B)
				} else {
					$tmp = str_replace('#', '', $this->datacolor[$i]);
					if (strpos($tmp, '-') !== false) {
						$foundnegativecolor++;
						$color = 'rgba(0,0,0,.0)'; // If $val is '-123'
					} else {
						$color = "#" . $tmp; // If $val is '123' or '#123'
					}
				}
				$this->stringtoshow .= "'" . $color . "'";
				$i++;
			}
			$this->stringtoshow .= '], ' . "\n";
			// Border color
			if ($foundnegativecolor) {
				$this->stringtoshow .= 'borderColor: [';
				$i = 0;
				foreach ($legends as $val) {	// Loop on each series
					if ($i > 0) {
						$this->stringtoshow .= ', ' . "\n";
					}
					if (is_array($this->datacolor[$i])) {
						$color = 'null'; // If datacolor is array(R, G, B)
					} else {
						$tmp = str_replace('#', '', $this->datacolor[$i]);
						if (strpos($tmp, '-') !== false) {
							$color = '#' . str_replace('-', '', $tmp); // If $val is '-123'
						} else {
							$color = 'null'; // If $val is '123' or '#123'
						}
					}
					$this->stringtoshow .= ($color == 'null' ? "'rgba(0,0,0,0.2)'" : "'" . $color . "'");
					$i++;
				}
				$this->stringtoshow .= ']';
			}
			$this->stringtoshow .= '} } };' . "\n";

			$this->stringtoshow .= '
				var ctx = document.getElementById("canvas_' . $tag . '").getContext("2d");
				var chart = new Chart(ctx, {
			    // The type of chart we want to create
    			type: \'' . (in_array($type, array('pie', 'piesemicircle')) ? 'doughnut' : 'polarArea') . '\',
				// Configuration options go here
    			options: options,
				data: {
					labels: [';

			$i = 0;
			foreach ($legends as $val) {	// Loop on each series
				if ($i > 0) {
					$this->stringtoshow .= ', ';
				}
				$this->stringtoshow .= "'" . dol_escape_js(dol_trunc($val, 25)) . "'";	// Lower than 25 make some important label (that we can't shorten) to be truncated
				$i++;
			}

			$this->stringtoshow .= '],
					datasets: [';
			$i = 0;
			while ($i < $nblot) {	// Loop on each series
				$color = 'rgb(' . $this->datacolor[$i][0] . ', ' . $this->datacolor[$i][1] . ', ' . $this->datacolor[$i][2] . ')';

				if ($i > 0) {
					$this->stringtoshow .= ', ' . "\n";
				}
				$this->stringtoshow .= '{' . "\n";
				//$this->stringtoshow .= 'borderColor: \''.$color.'\', ';
				//$this->stringtoshow .= 'backgroundColor: \''.$color.'\', ';
				$this->stringtoshow .= '  data: [' . $series[$i] . ']';
				$this->stringtoshow .= '}' . "\n";
				$i++;
			}
			$this->stringtoshow .= ']' . "\n";
			$this->stringtoshow .= '}' . "\n";
			$this->stringtoshow .= '});' . "\n";
		} else {
			// Other cases, graph of type 'bars', 'lines', 'linesnopoint'
			$type = 'bar';
			$xaxis = '';

			if (isset($this->type[$firstlot]) && $this->type[$firstlot] == 'horizontalbars') {
				$xaxis = "indexAxis: 'y', ";
			}
			if (isset($this->type[$firstlot]) && ($this->type[$firstlot] == 'lines' || $this->type[$firstlot] == 'linesnopoint')) {
				$type = 'line';
			}

			// Set options
			$this->stringtoshow .= 'var options = { maintainAspectRatio: false, aspectRatio: 2.5, ';
			$this->stringtoshow .= $xaxis;
			if ($this->showpointvalue == 2) {
				$this->stringtoshow .= 'interaction: { intersect: true, mode: \'index\'}, ';
			}

			/* For Chartjs v2.9 */
			/*
			if (empty($showlegend)) {
				$this->stringtoshow .= 'legend: { display: false }, '."\n";
			} else {
				$this->stringtoshow .= 'legend: { maxWidth: '.round($this->width / 2).', labels: { boxWidth: 15 }, position: \'' . ($showlegend == 2 ? 'right' : 'top') . '\' }, '."\n";
			}
			*/

			/* For Chartjs v3.5 */
			$this->stringtoshow .= 'plugins: { '."\n";
			if (empty($showlegend)) {
				$this->stringtoshow .= 'legend: { display: false }, '."\n";
			} else {
				$this->stringtoshow .= 'legend: { maxWidth: '.round(intval($this->width) / 2).', labels: { boxWidth: 15 }, position: \'' . (($showlegend && $showlegend == 2) ? 'right' : 'top') . '\' },'."\n";
			}
			if (is_array($this->tooltipsLabels) || is_array($this->tooltipsTitles)) {
				$this->stringtoshow .= 'tooltip: { mode: \'nearest\',
					callbacks: {';
				if (is_array($this->tooltipsTitles)) {
					$this->stringtoshow .= '
							title: function(tooltipItem, data) {
								var tooltipsTitle ='.json_encode($this->tooltipsTitles).'
								return tooltipsTitle[tooltipItem[0].datasetIndex];
							},';
				}
				if (is_array($this->tooltipsLabels)) {
					$this->stringtoshow .= 'label: function(tooltipItem, data) {
								var tooltipslabels ='.json_encode($this->tooltipsLabels).'
								return tooltipslabels[tooltipItem.datasetIndex]
							}';
				}
				$this->stringtoshow .= '}},';
			}
			$this->stringtoshow .= "}, \n";

			/* For Chartjs v2.9 */
			/*
			 $this->stringtoshow .= 'scales: { xAxis: [{ ';
			if ($this->hideXValues) {
				$this->stringtoshow .= ' ticks: { display: false }, display: true,';
			}
			//$this->stringtoshow .= 'type: \'time\', ';		// Need Moment.js
			$this->stringtoshow .= 'distribution: \'linear\'';
			if ($type == 'bar' && count($arrayofgroupslegend) > 0) {
				$this->stringtoshow .= ', stacked: true';
			}
			$this->stringtoshow .= ' }]';
			$this->stringtoshow .= ', yAxis: [{ ticks: { beginAtZero: true }';
			if ($type == 'bar' && count($arrayofgroupslegend) > 0) {
				$this->stringtoshow .= ', stacked: true';
			}
			$this->stringtoshow .= ' }] }';
			*/

			// Add a callback to change label to show only positive value
			if (is_array($this->tooltipsLabels) || is_array($this->tooltipsTitles)) {
				$this->stringtoshow .= 'tooltips: { mode: \'nearest\',
					callbacks: {';
				if (is_array($this->tooltipsTitles)) {
					$this->stringtoshow .= '
							title: function(tooltipItem, data) {
								var tooltipsTitle ='.json_encode($this->tooltipsTitles).'
								return tooltipsTitle[tooltipItem[0].datasetIndex];
							},';
				}
				if (is_array($this->tooltipsLabels)) {
					$this->stringtoshow .= 'label: function(tooltipItem, data) {
								var tooltipslabels ='.json_encode($this->tooltipsLabels).'
								return tooltipslabels[tooltipItem.datasetIndex]
							}';
				}
				$this->stringtoshow .= '}},';
			}
			$this->stringtoshow .= '};';
			$this->stringtoshow .= '
				var ctx = document.getElementById("canvas_' . $tag . '").getContext("2d");
				var chart = new Chart(ctx, {
			    // The type of chart we want to create
    			type: \'' . $type . '\',
				// Configuration options go here
    			options: options,
				data: {
					labels: [';

			$i = 0;
			foreach ($legends as $val) {	// Loop on each series
				if ($i > 0) {
					$this->stringtoshow .= ', ';
				}
				$this->stringtoshow .= "'" . dol_escape_js(dol_trunc($val, 32)) . "'";
				$i++;
			}

			//var_dump($arrayofgroupslegend);

			$this->stringtoshow .= '],
					datasets: [';

			global $theme_datacolor;
			'@phan-var-force array{0:array{0:int,1:int,2:int},1:array{0:int,1:int,2:int},2:array{0:int,1:int,2:int},3:array{0:int,1:int,2:int}} $theme_datacolor';
			//var_dump($arrayofgroupslegend);
			$i = 0;
			$iinstack = 0;
			$oldstacknum = -1;
			while ($i < $nblot) {	// Loop on each series
				$foundnegativecolor = 0;
				$usecolorvariantforgroupby = 0;
				// We used a 'group by' and we have too many colors so we generated color variants per
				if (!empty($arrayofgroupslegend) && is_array($arrayofgroupslegend[$i]) && count($arrayofgroupslegend[$i]) > 0) {	// If we used a group by.
					$nbofcolorneeds = count($arrayofgroupslegend);
					$nbofcolorsavailable = count($theme_datacolor);
					if ($nbofcolorneeds > $nbofcolorsavailable) {
						$usecolorvariantforgroupby = 1;
					}

					$textoflegend = $arrayofgroupslegend[$i]['legendwithgroup'];
				} else {
					$textoflegend = !empty($this->Legend[$i]) ? $this->Legend[$i] : '';
				}

				if ($usecolorvariantforgroupby) {
					$newcolor = $this->datacolor[$arrayofgroupslegend[$i]['stacknum']];
					// If we change the stack
					if ($oldstacknum == -1 || $arrayofgroupslegend[$i]['stacknum'] != $oldstacknum) {
						$iinstack = 0;
					}

					//var_dump($iinstack);
					if ($iinstack) {
						// Change color with offset of $iinstack
						//var_dump($newcolor);
						if ($iinstack % 2) {	// We increase aggressiveness of reference color for color 2, 4, 6, ...
							$ratio = min(95, 10 + 10 * $iinstack); // step of 20
							$brightnessratio = min(90, 5 + 5 * $iinstack); // step of 10
						} else {				// We decrease aggressiveness of reference color for color 3, 5, 7, ..
							$ratio = max(-100, -15 * $iinstack + 10); // step of -20
							$brightnessratio = min(90, 10 * $iinstack); // step of 20
						}
						//var_dump('Color '.($iinstack+1).' : '.$ratio.' '.$brightnessratio);

						$newcolor = array_values(colorHexToRgb(colorAgressiveness(colorArrayToHex($newcolor), $ratio, $brightnessratio), false, true));
					}
					$oldstacknum = $arrayofgroupslegend[$i]['stacknum'];

					$color = 'rgb(' . $newcolor[0] . ', ' . $newcolor[1] . ', ' . $newcolor[2] . ', 0.9)';
					$bordercolor = 'rgb(' . $newcolor[0] . ', ' . $newcolor[1] . ', ' . $newcolor[2] . ')';
				} else { // We do not use a 'group by'
					if (!empty($this->datacolor[$i])) {
						if (is_array($this->datacolor[$i])) {
							$color = 'rgb(' . $this->datacolor[$i][0] . ', ' . $this->datacolor[$i][1] . ', ' . $this->datacolor[$i][2] . ', 0.9)';
						} else {
							$color = $this->datacolor[$i];
						}
					}
					// else: $color will be undefined
					if (!empty($this->bordercolor[$i]) && is_array($this->bordercolor[$i])) {
						$bordercolor = 'rgb(' . $this->bordercolor[$i][0] . ', ' . $this->bordercolor[$i][1] . ', ' . $this->bordercolor[$i][2] . ', 0.9)';
					} else {
						if ($type != 'horizontalBar') {
							$bordercolor = $color;
						} else {
							$bordercolor = $this->bordercolor[$i];
						}
					}

					// For negative colors, we invert border and background
					$tmp = str_replace('#', '', $color);
					if (strpos($tmp, '-') !== false) {
						$foundnegativecolor++;
						$bordercolor = str_replace('-', '', $color);
						$color = '#FFFFFF'; // If $val is '-123'
					}
				}
				if ($i > 0) {
					$this->stringtoshow .= ', ';
				}
				$this->stringtoshow .= "\n";
				$this->stringtoshow .= '{';
				$this->stringtoshow .= 'dolibarrinfo: \'y_' . $i . '\', ';
				$this->stringtoshow .= 'label: \'' . dol_escape_js(dol_string_nohtmltag($textoflegend)) . '\', ';
				$this->stringtoshow .= 'pointStyle: \'' . ((!empty($this->type[$i]) && $this->type[$i] == 'linesnopoint') ? 'line' : 'circle') . '\', ';
				$this->stringtoshow .= 'fill: ' . ($type == 'bar' ? 'true' : 'false') . ', ';
				if ($type == 'bar' || $type == 'horizontalBar') {
					$this->stringtoshow .= 'borderWidth: \''.$this->borderwidth.'\', ';
				}
				$this->stringtoshow .= 'borderColor: \'' . $bordercolor . '\', ';
				$this->stringtoshow .= 'borderSkipped: \'' . $this->borderskip . '\', ';
				$this->stringtoshow .= 'backgroundColor: \'' . $color . '\', ';
				if (!empty($arrayofgroupslegend) && !empty($arrayofgroupslegend[$i])) {
					$this->stringtoshow .= 'stack: \'' . $arrayofgroupslegend[$i]['stacknum'] . '\', ';
				}
				$this->stringtoshow .= 'data: [';

				$this->stringtoshow .= $this->mirrorGraphValues ? '[-' . $series[$i] . ',' . $series[$i] . ']' : $series[$i];
				$this->stringtoshow .= ']';
				$this->stringtoshow .= '}' . "\n";

				$i++;
				$iinstack++;
			}
			$this->stringtoshow .= ']' . "\n";
			$this->stringtoshow .= '}' . "\n";
			$this->stringtoshow .= '});' . "\n";
		}

		$this->stringtoshow .= '</script>' . "\n";
	}


	/**
	 * Output HTML string to total value
	 *
	 * @return	float|int							HTML string to total value
	 */
	public function total()
	{
		$value = 0;
		foreach ($this->data as $valarray) {	// Loop on each x
			$value += $valarray[1];
		}
		return $value;
	}

	/**
	 * Output HTML string ->stringtoshow to show the graph
	 *
	 * @param	int|string		$shownographyet    Show graph to say there is not enough data or the message in $shownographyet if it is a string.
	 * @return	string							   HTML string to show graph
	 */
	public function show($shownographyet = 0)
	{
		global $langs;

		if ($shownographyet) {
			$s = '<div class="nographyet" style="width:' . (preg_match('/%/', $this->width) ? $this->width : $this->width . 'px') . '; height:' . (preg_match('/%/', $this->height) ? $this->height : $this->height . 'px') . ';"></div>';
			$s .= '<div class="nographyettext margintoponly">';
			if (is_numeric($shownographyet)) {
				$s .= $langs->trans("NotEnoughDataYet") . '...';
			} else {
				$s .= $shownographyet . '...';
			}
			$s .= '</div>';
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
	public static function getDefaultGraphSizeForStats($direction, $defaultsize = '')
	{
		global $conf;
		$defaultsize = (int) $defaultsize;

		if ($direction == 'width') {
			if (empty($conf->dol_optimize_smallscreen)) {
				return ($defaultsize ? $defaultsize : 500);
			} else {
				return (empty($_SESSION['dol_screenwidth']) ? 280 : ($_SESSION['dol_screenwidth'] - 40));
			}
		} elseif ($direction == 'height') {
			return (empty($conf->dol_optimize_smallscreen) ? ($defaultsize ? $defaultsize : 220) : 200);
		}
		return 0;
	}
}
