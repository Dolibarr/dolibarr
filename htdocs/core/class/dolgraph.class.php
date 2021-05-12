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
<<<<<<< HEAD
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
=======
	public $type=array();			// Array with type of each series. Example: array('bars', 'lines', ...)
	public $mode='side';		    // Mode bars graph: side, depth
	private $_library='jflot';	// Graphic library to use (jflot, artichow)

	//! Array of data
	public $data;				// Data of graph: array(array('abs1',valA1,valB1), array('abs2',valA2,valB2), ...)
	public $title;				// Title of graph
	public $cssprefix='';		// To add into css styles

	/**
	 * @var int|string 		Width of graph. It can be a numeric for pixels or a string like '100%'
	 */
	public $width=380;
	/**
	 * @var int 			Height of graph
	 */
	public $height=200;

	public $MaxValue=0;
	public $MinValue=0;
	public $SetShading=0;

	public $horizTickIncrement=-1;
	public $SetNumXTicks=-1;
	public $labelInterval=-1;

	public $hideXGrid=false;
	public $hideYGrid=false;

	public $Legend=array();
	public $LegendWidthMin=0;
	public $showlegend=1;
	public $showpointvalue=1;
	public $showpercent=0;
	public $combine=0;				// 0.05 if you want to combine records < 5% into "other"
	public $graph;     			// Objet Graph (Artichow, Phplot...)

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	public $bordercolor;			// array(R,G,B)
	public $bgcolor;				// array(R,G,B)
	public $bgcolorgrid=array(255,255,255);			// array(R,G,B)
	public $datacolor;				// array(array(R,G,B),...)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	private $stringtoshow;      // To store string to output graph into HTML page


	/**
	 * Constructor
	 *
	 * @param	string	$library		'jflot' (default) or 'artichow' (no more supported)
	 */
<<<<<<< HEAD
	function __construct($library='jflot')
	{
		global $conf;
		global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;
=======
	public function __construct($library = 'jflot')
	{
		global $conf;
		global $theme_bordercolor, $theme_datacolor, $theme_bgcolor;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
				return -1;
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}
		}

		$this->bordercolor = array(235,235,224);
		$this->datacolor = array(array(120,130,150), array(160,160,180), array(190,190,220));
		$this->bgcolor = array(235,235,224);

<<<<<<< HEAD
		$color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/graph-color.php';
=======
		$color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if (is_readable($color_file))
		{
			include_once $color_file;
			if (isset($theme_bordercolor)) $this->bordercolor = $theme_bordercolor;
			if (isset($theme_datacolor))   $this->datacolor   = $theme_datacolor;
			if (isset($theme_bgcolor))     $this->bgcolor     = $theme_bgcolor;
		}
		//print 'bgcolor: '.join(',',$this->bgcolor).'<br>';
	}


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set Y precision
	 *
	 * @param 	float	$which_prec		Precision
	 * @return 	boolean
<<<<<<< HEAD
	 */
	function SetPrecisionY($which_prec)
	{
		$this->PrecisionY = $which_prec;
		return true;
	}

=======
	 * @deprecated
	 */
	public function SetPrecisionY($which_prec)
	{
        // phpcs:enable
		return true;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 *
	 * @param 	float 		$xi		Xi
	 * @return	boolean				True
	 */
<<<<<<< HEAD
	function SetHorizTickIncrement($xi)
	{
=======
	public function SetHorizTickIncrement($xi)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->horizTickIncrement = $xi;
		return true;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Utiliser SetNumTicks ou SetHorizTickIncrement mais pas les 2
	 *
	 * @param 	float 		$xt		Xt
	 * @return	boolean				True
	 */
<<<<<<< HEAD
	function SetNumXTicks($xt)
	{
=======
	public function SetNumXTicks($xt)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->SetNumXTicks = $xt;
		return true;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set label interval to reduce number of labels
	 *
	 * @param 	float 		$x		Label interval
	 * @return	boolean				True
	 */
<<<<<<< HEAD
	function SetLabelInterval($x)
	{
=======
	public function SetLabelInterval($x)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->labelInterval = $x;
		return true;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Hide X grid
	 *
	 * @param	boolean		$bool	XGrid or not
	 * @return	boolean				true
	 */
<<<<<<< HEAD
	function SetHideXGrid($bool)
	{
=======
	public function SetHideXGrid($bool)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->hideXGrid = $bool;
		return true;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Hide Y grid
	 *
	 * @param	boolean		$bool	YGrid or not
	 * @return	boolean				true
	 */
<<<<<<< HEAD
	function SetHideYGrid($bool)
	{
=======
	public function SetHideYGrid($bool)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->hideYGrid = $bool;
		return true;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set y label
	 *
	 * @param 	string	$label		Y label
	 * @return	boolean|null				True
	 */
<<<<<<< HEAD
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

=======
	public function SetYLabel($label)
	{
        // phpcs:enable
		$this->YLabel = $label;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set width
	 *
	 * @param 	int|string		$w			Width (Example: 320 or '100%')
	 * @return	boolean|null				True
	 */
	public function SetWidth($w)
	{
        // phpcs:enable
		$this->width = $w;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set title
	 *
	 * @param 	string	$title		Title
	 * @return	void
	 */
<<<<<<< HEAD
	function SetTitle($title)
	{
		$this->title = $title;
	}

=======
	public function SetTitle($title)
	{
        // phpcs:enable
		$this->title = $title;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set data
	 *
	 * @param 	array	$data		Data
	 * @return	void
<<<<<<< HEAD
	 * @see draw_jflot for syntax of data array
	 */
	function SetData($data)
	{
		$this->data = $data;
	}

=======
	 * @see draw_jflot() for syntax of data array
	 */
	public function SetData($data)
	{
        // phpcs:enable
		$this->data = $data;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set data
	 *
	 * @param 	array	$datacolor		Data color array(array(R,G,B),array(R,G,B)...)
	 * @return	void
	 */
<<<<<<< HEAD
	function SetDataColor($datacolor)
	{
		$this->datacolor = $datacolor;
	}

=======
	public function SetDataColor($datacolor)
	{
        // phpcs:enable
		$this->datacolor = $datacolor;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set type
	 *
	 * @param 	array	$type		Array with type for each serie. Example: array('pie'), array('lines',...,'bars')
	 * @return	void
	 */
<<<<<<< HEAD
	function SetType($type)
	{
		$this->type = $type;
	}

=======
	public function SetType($type)
	{
        // phpcs:enable
		$this->type = $type;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set legend
	 *
	 * @param 	array	$legend		Legend. Example: array('seriename1','seriname2',...)
	 * @return	void
	 */
<<<<<<< HEAD
	function SetLegend($legend)
	{
		$this->Legend = $legend;
	}

=======
	public function SetLegend($legend)
	{
        // phpcs:enable
		$this->Legend = $legend;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set min width
	 *
	 * @param 	int		$legendwidthmin		Min width
	 * @return	void
	 */
<<<<<<< HEAD
	function SetLegendWidthMin($legendwidthmin)
	{
		$this->LegendWidthMin = $legendwidthmin;
	}

=======
	public function SetLegendWidthMin($legendwidthmin)
	{
        // phpcs:enable
		$this->LegendWidthMin = $legendwidthmin;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set max value
	 *
	 * @param 	int		$max			Max value
	 * @return	void
	 */
<<<<<<< HEAD
	function SetMaxValue($max)
	{
		$this->MaxValue = $max;
	}

=======
    public function SetMaxValue($max)
	{
        // phpcs:enable
		$this->MaxValue = $max;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Get max value
	 *
	 * @return	int		Max value
	 */
<<<<<<< HEAD
	function GetMaxValue()
	{
		return $this->MaxValue;
	}

=======
    public function GetMaxValue()
	{
        // phpcs:enable
		return $this->MaxValue;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set min value
	 *
	 * @param 	int		$min			Min value
	 * @return	void
	 */
<<<<<<< HEAD
	function SetMinValue($min)
	{
		$this->MinValue = $min;
	}

=======
    public function SetMinValue($min)
	{
        // phpcs:enable
		$this->MinValue = $min;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Get min value
	 *
	 * @return	int		Max value
	 */
<<<<<<< HEAD
	function GetMinValue()
	{
		return $this->MinValue;
	}

=======
    public function GetMinValue()
	{
        // phpcs:enable
		return $this->MinValue;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set height
	 *
	 * @param 	int		$h				Height
	 * @return	void
	 */
<<<<<<< HEAD
	function SetHeight($h)
	{
		$this->height = $h;
	}

=======
    public function SetHeight($h)
	{
        // phpcs:enable
		$this->height = $h;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set shading
	 *
	 * @param 	string	$s				Shading
	 * @return	void
	 */
<<<<<<< HEAD
	function SetShading($s)
	{
		$this->SetShading = $s;
	}

=======
    public function SetShading($s)
	{
        // phpcs:enable
		$this->SetShading = $s;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Set shading
	 *
	 * @param 	string	$s				Shading
	 * @return	void
	 */
<<<<<<< HEAD
	function SetCssPrefix($s)
	{
		$this->cssprefix = $s;
	}

=======
    public function SetCssPrefix($s)
	{
        // phpcs:enable
		$this->cssprefix = $s;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Reset bg color
	 *
	 * @return	void
	 */
<<<<<<< HEAD
	function ResetBgColor()
	{
		unset($this->bgcolor);
	}

=======
    public function ResetBgColor()
	{
        // phpcs:enable
		unset($this->bgcolor);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Reset bgcolorgrid
	 *
	 * @return	void
	 */
<<<<<<< HEAD
	function ResetBgColorGrid()
	{
=======
    public function ResetBgColorGrid()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		unset($this->bgcolorgrid);
	}

	/**
	 * Is graph ko
	 *
	 * @return	string		Error
	 */
<<<<<<< HEAD
	function isGraphKo()
=======
    public function isGraphKo()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->error;
	}

	/**
	 * Show legend or not
	 *
	 * @param	int		$showlegend		1=Show legend (default), 0=Hide legend
	 * @return	void
	 */
<<<<<<< HEAD
	function setShowLegend($showlegend)
=======
    public function setShowLegend($showlegend)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$this->showlegend=$showlegend;
	}

	/**
	 * Show pointvalue or not
	 *
	 * @param	int		$showpointvalue		1=Show value for each point, as tooltip or inline (default), 0=Hide value
	 * @return	void
	 */
<<<<<<< HEAD
	function setShowPointValue($showpointvalue)
=======
    public function setShowPointValue($showpointvalue)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$this->showpointvalue=$showpointvalue;
	}

	/**
	 * Show percent or not
	 *
	 * @param	int		$showpercent		1=Show percent for each point, as tooltip or inline, 0=Hide percent (default)
	 * @return	void
	 */
<<<<<<< HEAD
	function setShowPercent($showpercent)
=======
    public function setShowPercent($showpercent)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$this->showpercent=$showpercent;
	}



<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Define background color of complete image
	 *
	 * @param	array	$bg_color		array(R,G,B) ou 'onglet' ou 'default'
	 * @return	void
	 */
<<<<<<< HEAD
	function SetBgColor($bg_color = array(255,255,255))
	{
=======
    public function SetBgColor($bg_color = array(255,255,255))
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Define background color of grid
	 *
	 * @param	array	$bg_colorgrid		array(R,G,B) ou 'onglet' ou 'default'
	 * @return	void
	 */
<<<<<<< HEAD
	function SetBgColorGrid($bg_colorgrid = array(255,255,255))
	{
=======
    public function SetBgColorGrid($bg_colorgrid = array(255,255,255))
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Reset data color
	 *
	 * @return	void
	 */
<<<<<<< HEAD
	function ResetDataColor()
	{
		unset($this->datacolor);
	}

	/**
=======
    public function ResetDataColor()
    {
        // phpcs:enable
        unset($this->datacolor);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 * Get max value
	 *
	 * @return	int		Max value
	 */
<<<<<<< HEAD
	function GetMaxValueInData()
	{
=======
    public function GetMaxValueInData()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return min value of all data
	 *
	 * @return	int		Min value of all data
	 */
<<<<<<< HEAD
	function GetMinValueInData()
	{
=======
    public function GetMinValueInData()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return max value of all data
	 *
	 * @return 	int		Max value of all data
	 */
<<<<<<< HEAD
	function GetCeilMaxValue()
	{
=======
    public function GetCeilMaxValue()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return min value of all data
	 *
	 * @return 	double		Max value of all data
	 */
<<<<<<< HEAD
	function GetFloorMinValue()
	{
=======
    public function GetFloorMinValue()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function draw($file, $fileurl='')
=======
    public function draw($file, $fileurl = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		if (empty($file))
		{
			$this->error="Call to draw method was made with empty value for parameter file.";
			dol_syslog(get_class($this)."::draw ".$this->error, LOG_ERR);
			return -2;
		}
		if (! is_array($this->data))
		{
			$this->error="Call to draw method was made but SetData was not called or called with an empty dataset for parameters";
			dol_syslog(get_class($this)."::draw ".$this->error, LOG_ERR);
			return -1;
		}
		if (count($this->data) < 1)
		{
			$this->error="Call to draw method was made but SetData was is an empty dataset";
			dol_syslog(get_class($this)."::draw ".$this->error, LOG_WARNING);
		}
		$call = "draw_".$this->_library;
		call_user_func_array(array($this,$call), array($file,$fileurl));
	}


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Build a graph onto disk using Artichow library and return img string to it
	 *
	 * @param	string	$file    	Image file name to use if we save onto disk
	 * @param	string	$fileurl	Url path to show image if saved onto disk
	 * @return	void
	 */
<<<<<<< HEAD
	private function draw_artichow($file,$fileurl)
	{
		global $artichow_defaultfont;

		dol_syslog(get_class($this)."::draw_artichow this->type=".join(',',$this->type));

		if (! defined('SHADOW_RIGHT_TOP'))  define('SHADOW_RIGHT_TOP',3);
		if (! defined('LEGEND_BACKGROUND')) define('LEGEND_BACKGROUND',2);
		if (! defined('LEGEND_LINE'))       define('LEGEND_LINE',1);
=======
	private function draw_artichow($file, $fileurl)
	{
        // phpcs:enable
		global $artichow_defaultfont;

		dol_syslog(get_class($this)."::draw_artichow this->type=".join(',', $this->type));

		if (! defined('SHADOW_RIGHT_TOP'))  define('SHADOW_RIGHT_TOP', 3);
		if (! defined('LEGEND_BACKGROUND')) define('LEGEND_BACKGROUND', 2);
		if (! defined('LEGEND_LINE'))       define('LEGEND_LINE', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		// Create graph
		$classname='';
		if (! isset($this->type[0]) || $this->type[0] == 'bars')  $classname='BarPlot';    // Only one type (first one) is supported by artichow
<<<<<<< HEAD
		else if ($this->type[0] == 'lines') $classname='LinePlot';
=======
		elseif ($this->type[0] == 'lines' || $this->type[0] == 'linesnopoint') $classname='LinePlot';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		else $classname='TypeUnknown';
		include_once ARTICHOW_PATH.$classname.'.class.php';

		// Definition de couleurs
<<<<<<< HEAD
		$bgcolor=new Color($this->bgcolor[0],$this->bgcolor[1],$this->bgcolor[2]);
		$bgcolorgrid=new Color($this->bgcolorgrid[0],$this->bgcolorgrid[1],$this->bgcolorgrid[2]);
		$colortrans=new Color(0,0,0,100);
		$colorsemitrans=new Color(255,255,255,60);
		$colorgradient= new LinearGradient(new Color(235, 235, 235),new Color(255, 255, 255),0);
		$colorwhite=new Color(255,255,255);
=======
		$bgcolor=new Color($this->bgcolor[0], $this->bgcolor[1], $this->bgcolor[2]);
		$bgcolorgrid=new Color($this->bgcolorgrid[0], $this->bgcolorgrid[1], $this->bgcolorgrid[2]);
		$colortrans=new Color(0, 0, 0, 100);
		$colorsemitrans=new Color(255, 255, 255, 60);
		$colorgradient= new LinearGradient(new Color(235, 235, 235), new Color(255, 255, 255), 0);
		$colorwhite=new Color(255, 255, 255);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
		$strl=dol_strlen(max(abs($this->MaxValue),abs($this->MinValue)));
		if ($strl > 6) $paddleft += ($strl * 4);
		$group->setPadding($paddleft, $paddright);		// Width on left and right for Y axis values
		$group->legend->setSpace(0);
		$group->legend->setPadding(2,2,2,2);
=======
		$strl=dol_strlen(max(abs($this->MaxValue), abs($this->MinValue)));
		if ($strl > 6) $paddleft += ($strl * 4);
		$group->setPadding($paddleft, $paddright);		// Width on left and right for Y axis values
		$group->legend->setSpace(0);
		$group->legend->setPadding(2, 2, 2, 2);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$group->legend->setPosition(null, 0.1);
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

<<<<<<< HEAD
				$color=new Color($this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2],20);
				$colorbis=new Color(min($this->datacolor[$i][0]+50,255),min($this->datacolor[$i][1]+50,255),min($this->datacolor[$i][2]+50,255),50);

				$colorgrey=new Color(100,100,100);
				$colorborder=new Color($this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2]);
=======
				$color=new Color($this->datacolor[$i][0], $this->datacolor[$i][1], $this->datacolor[$i][2], 20);
				$colorbis=new Color(min($this->datacolor[$i][0]+50, 255), min($this->datacolor[$i][1]+50, 255), min($this->datacolor[$i][2]+50, 255), 50);

				$colorgrey=new Color(100, 100, 100);
				$colorborder=new Color($this->datacolor[$i][0], $this->datacolor[$i][1], $this->datacolor[$i][2]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
				$plot->barShadow->smooth(true);
				//$plot->setSize(1, 0.96);
				//$plot->setCenter(0.5, 0.52);

				// Le mode automatique est plus efficace
				$plot->SetYMax($this->MaxValue);
				$plot->SetYMin($this->MinValue);
			}

<<<<<<< HEAD
			if ($this->type[0] == 'lines')
			{
				$color=new Color($this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2],20);
				$colorbis=new Color(min($this->datacolor[$i][0]+20,255),min($this->datacolor[$i][1]+20,255),min($this->datacolor[$i][2]+20,255),60);
				$colorter=new Color(min($this->datacolor[$i][0]+50,255),min($this->datacolor[$i][1]+50,255),min($this->datacolor[$i][2]+50,255),90);
=======
			if ($this->type[0] == 'lines' || $this->type[0] == 'linesnopoint')
			{
				$color=new Color($this->datacolor[$i][0], $this->datacolor[$i][1], $this->datacolor[$i][2], 20);
				$colorbis=new Color(min($this->datacolor[$i][0]+20, 255), min($this->datacolor[$i][1]+20, 255), min($this->datacolor[$i][2]+20, 255), 60);
				$colorter=new Color(min($this->datacolor[$i][0]+50, 255), min($this->datacolor[$i][1]+50, 255), min($this->datacolor[$i][2]+50, 255), 90);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
				if ($this->type[0] == 'bars')  $group->legend->add($plot, $this->Legend[$i], LEGEND_BACKGROUND);
				if ($this->type[0] == 'lines') $group->legend->add($plot, $this->Legend[$i], LEGEND_LINE);
=======
				if ($this->type[0] == 'bars')  										$group->legend->add($plot, $this->Legend[$i], LEGEND_BACKGROUND);
				if ($this->type[0] == 'lines' || $this->type[0] == 'linesnopoint')	$group->legend->add($plot, $this->Legend[$i], LEGEND_LINE);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	private function draw_jflot($file,$fileurl)
	{
		global $artichow_defaultfont;

		dol_syslog(get_class($this)."::draw_jflot this->type=".join(',',$this->type)." this->MaxValue=".$this->MaxValue);
=======
	private function draw_jflot($file, $fileurl)
	{
        // phpcs:enable
		global $langs;

		dol_syslog(get_class($this)."::draw_jflot this->type=".join(',', $this->type)." this->MaxValue=".$this->MaxValue);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		if (empty($this->width) && empty($this->height))
		{
			print 'Error width or height not set';
			return;
		}

		$legends=array();
		$nblot=count($this->data[0])-1;    // -1 to remove legend
		if ($nblot < 0) dol_syslog('Bad value for property ->data. Must be set by mydolgraph->SetData before calling mydolgrapgh->draw', LOG_WARNING);
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
<<<<<<< HEAD
		$tag=dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file),'_',array('-','.'))));

		$this->stringtoshow ='<!-- Build using '.$this->_library.' -->'."\n";
		if (! empty($this->title)) $this->stringtoshow.='<div align="center" class="dolgraphtitle'.(empty($this->cssprefix)?'':' dolgraphtitle'.$this->cssprefix).'">'.$this->title.'</div>';
=======
		$tag=dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file), '_', array('-','.'))));

		$this->stringtoshow ='<!-- Build using '.$this->_library.' -->'."\n";
		if (! empty($this->title)) $this->stringtoshow.='<div class="center" class="dolgraphtitle'.(empty($this->cssprefix)?'':' dolgraphtitle'.$this->cssprefix).'">'.$this->title.'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if (! empty($this->shownographyet))
		{
		  $this->stringtoshow.='<div style="width:'.$this->width.'px;height:'.$this->height.'px;" class="nographyet"></div>';
		  $this->stringtoshow.='<div class="nographyettext">'.$langs->trans("NotEnoughDataYet").'</div>';
		  return;
		}
<<<<<<< HEAD
		$this->stringtoshow.='<div id="placeholder_'.$tag.'" style="width:'.$this->width.'px;height:'.$this->height.'px;" class="dolgraph'.(empty($this->cssprefix)?'':' dolgraph'.$this->cssprefix).'"></div>'."\n";
=======
		$this->stringtoshow.='<div id="placeholder_'.$tag.'" style="width:'.$this->width.'px;height:'.$this->height.'px;" class="dolgraph'.(empty($this->cssprefix)?'':' dolgraph'.$this->cssprefix).' center"></div>'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$this->stringtoshow.='<script id="'.$tag.'">'."\n";
		$this->stringtoshow.='$(function () {'."\n";
		$i=$firstlot;
		if ($nblot < 0)
		{
			$this->stringtoshow.='<!-- No series of data -->';
		}
		else
		{
			while ($i < $nblot)
			{
				$this->stringtoshow.=$serie[$i];
				$i++;
			}
		}
		$this->stringtoshow.="\n";

		// Special case for Graph of type 'pie'
		if (isset($this->type[$firstlot]) && $this->type[$firstlot] == 'pie')
		{
			$datacolor=array();
<<<<<<< HEAD
			foreach($this->datacolor as $val) $datacolor[]="#".sprintf("%02x%02x%02x",$val[0],$val[1],$val[2]);
=======
			foreach($this->datacolor as $val) $datacolor[]="#".sprintf("%02x%02x%02x", $val[0], $val[1], $val[2]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
								$this->stringtoshow.='<span style="font-size:8pt;text-align:center;padding:2px;color:black;">';
								if ($urltemp) $this->stringtoshow.='<a style="color: #FFFFFF;" border="0" href="'.$urltemp.'">';
								$this->stringtoshow.='\'+';
								$this->stringtoshow.=($showlegend?'':'label+\' \'+');	// Hide label if already shown in legend
								$this->stringtoshow.=($showpointvalue?'number+':'');
								$this->stringtoshow.=($showpercent?'\'<br/>\'+percent+\'%\'+':'');
								$this->stringtoshow.='\'';
								if ($urltemp) $this->stringtoshow.='</a>';
								$this->stringtoshow.='</span>\';
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
<<<<<<< HEAD
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
=======
		    // TODO: remove js css and use graph-tooltip-inner class instead by adding css in each themes
			$this->stringtoshow.='
			function showTooltip_'.$tag.'(x, y, contents) {
				$(\'<div class="graph-tooltip-inner" id="tooltip_'.$tag.'">\' + contents + \'</div>\').css({
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
				$color=sprintf("%02x%02x%02x",$this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2]);
				$this->stringtoshow.='{ ';
				if (! isset($this->type[$i]) || $this->type[$i] == 'bars') $this->stringtoshow.='bars: { show: true, align: "'.($i==$firstlot?'center':'left').'", barWidth: 0.5 }, ';
				if (isset($this->type[$i]) && $this->type[$i] == 'lines')  $this->stringtoshow.='lines: { show: true, fill: false }, ';
				$this->stringtoshow.='color: "#'.$color.'", label: "'.(isset($this->Legend[$i]) ? dol_escape_js($this->Legend[$i]) : '').'", data: d'.$i.' }';
				$i++;
			}
			$this->stringtoshow.="\n".' ], { series: { stack: stack, lines: { fill: false, steps: steps }, bars: { barWidth: 0.6 } }'."\n";
=======
				$color=sprintf("%02x%02x%02x", $this->datacolor[$i][0], $this->datacolor[$i][1], $this->datacolor[$i][2]);
				$this->stringtoshow.='{ ';
				if (! isset($this->type[$i]) || $this->type[$i] == 'bars') $this->stringtoshow.='bars: { lineWidth: 1, show: true, align: "'.($i==$firstlot?'center':'left').'", barWidth: 0.5 }, ';
				if (isset($this->type[$i]) && ($this->type[$i] == 'lines' || $this->type[$i] == 'linesnopoint')) $this->stringtoshow.='lines: { show: true, fill: false }, points: { show: '.($this->type[$i] == 'linesnopoint' ? 'false' : 'true').' }, ';
				$this->stringtoshow.='color: "#'.$color.'", label: "'.(isset($this->Legend[$i]) ? dol_escape_js($this->Legend[$i]) : '').'", data: d'.$i.' }';
				$i++;
			}
			// shadowSize: 0 -> Drawing is faster without shadows
			$this->stringtoshow.="\n".' ], { series: { shadowSize: 0, stack: stack, lines: { fill: false, steps: steps }, bars: { barWidth: 0.6 } }'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
			$color1=sprintf("%02x%02x%02x",$this->bgcolorgrid[0],$this->bgcolorgrid[0],$this->bgcolorgrid[2]);
			$color2=sprintf("%02x%02x%02x",$this->bgcolorgrid[0],$this->bgcolorgrid[1],$this->bgcolorgrid[2]);
			$this->stringtoshow.=', grid: { hoverable: true, backgroundColor: { colors: ["#'.$color1.'", "#'.$color2.'"] } }'."\n";
			//$this->stringtoshow.=', shadowSize: 20'."\n";    TODO Uncommet this
			$this->stringtoshow.='});'."\n";
			$this->stringtoshow.='}'."\n";

=======
			$color1=sprintf("%02x%02x%02x", $this->bgcolorgrid[0], $this->bgcolorgrid[0], $this->bgcolorgrid[2]);
			$color2=sprintf("%02x%02x%02x", $this->bgcolorgrid[0], $this->bgcolorgrid[1], $this->bgcolorgrid[2]);
			$this->stringtoshow.=', grid: { hoverable: true, backgroundColor: { colors: ["#'.$color1.'", "#'.$color2.'"] }, borderWidth: 1, borderColor: \'#e6e6e6\', tickColor  : \'#e6e6e6\' }'."\n";
			//$this->stringtoshow.=', shadowSize: 20'."\n";    TODO Uncommet this
			$this->stringtoshow.='});'."\n";
			$this->stringtoshow.='}'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function show($shownographyet=0)
=======
    public function show($shownographyet = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;

		if ($shownographyet)
		{
<<<<<<< HEAD
			$s= '<div class="nographyet" style="width:'.(preg_match('/%/',$this->width)?$this->width:$this->width.'px').'; height:'.(preg_match('/%/',$this->height)?$this->height:$this->height.'px').';"></div>';
=======
			$s= '<div class="nographyet" style="width:'.(preg_match('/%/', $this->width)?$this->width:$this->width.'px').'; height:'.(preg_match('/%/', $this->height)?$this->height:$this->height.'px').';"></div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	static function getDefaultGraphSizeForStats($direction,$defaultsize='')
=======
	public static function getDefaultGraphSizeForStats($direction, $defaultsize = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD

}

=======
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
