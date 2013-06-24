<?php
/* Copyright (c) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 *	Usage:
 *	$graph_data = array(array('labelxA',yA),array('labelxB',yB));
 *	$graph_data = array(array('labelxA',yA1,...,yAn),array('labelxB',yB1,...yBn));	// when there is n value to show for each x
 *  $legend     = array("Val1",...,"Valn");											// list of n series name
 *	$px = new DolGraph();
 *	$px->SetData($graph_data);
 *	$px->SetMaxValue($px->GetCeilMaxValue());
 *	$px->SetMinValue($px->GetFloorMinValue());
 *	$px->SetTitle("title");
 *	$px->SetLegend($legend);
 *	$px->SetWidth(width);
 *	$px->SetHeight(height);
 *	$px->draw("file.png","/viewdownload?...");
 */


/**
 *	Parent class of graph classes
 */
class DolGraph
{
    //! Type of graph
    var $type=array('bars');	// bars, lines, ...
    var $mode='side';		    // Mode bars graph: side, depth
    private $_library='jflot';	// Graphic library to use (jflot, artichow)

    //! Array of data
    var $data;				// array(array('abs1',valA1,valB1), array('abs2',valA2,valB2), ...)
    var $title;
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

    var $graph;     			// Objet Graph (Artichow, Phplot...)
    var $error;

    var $bordercolor;			// array(R,G,B)
    var $bgcolor;				// array(R,G,B)
    var $bgcolorgrid=array(255,255,255);			// array(R,G,B)
    var $datacolor;				// array(array(R,G,B),...)

    private $_stringtoshow;      // To store string to output graph into HTML page


    /**
     * Constructor
     */
    function __construct()
    {
        global $conf;
        global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;

        // To use old feature
        if (isset($conf->global->MAIN_GRAPH_LIBRARY) && $conf->global->MAIN_GRAPH_LIBRARY == 'artichow')
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
        return 1;
    }


    /**
     * Set Y precision
     *
     * @param 	float	$which_prec		Precision
     * @return 	string
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
     * @return	boolean				True
     */
    function SetYLabel($label)
    {
        $this->YLabel = $label;
    }

    /**
     * Set width
     *
     * @param 	int		$w			Width
     * @return	boolean				True
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
     */
    function SetData($data)
    {
        $this->data = $data;
        //var_dump($this->data);
    }

    /**
     * Set type
     *
     * @param 	array	$type		Array with type for each serie
     * @return	void
     */
    function SetType($type)
    {
        $this->type = $type;
    }

    /**
     * Set legend
     *
     * @param 	string	$legend		Legend
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
     * Build a graph onto disk using correct library
     *
     * @param	string	$file    	Image file name to use if we save onto disk
     * @param	string	$fileurl	Url path to show image if saved onto disk
     * @return	void
     */
    function draw($file,$fileurl='')
    {
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
     * Build a graph onto disk using Artichow library
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
        if ($this->type[0] == 'bars')  $classname='BarPlot';    // Only first type of type is supported by artichow
        if ($this->type[0] == 'lines') $classname='LinePlot';
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

        $this->_stringtoshow='<!-- Build using '.$this->_library.' --><img src="'.$fileurl.'" title="'.dol_escape_htmltag($this->title?$this->title:$this->YLabel).'" alt="'.dol_escape_htmltag($this->title?$this->title:$this->YLabel).'">';
    }


    /**
     * Build a graph onto disk using JFlot library
     *	$graph_data = array(array('labelxA',yA),array('labelxB',yB));
     *	$graph_data = array(array('labelxA',yA1,...,yAn),array('labelxB',yB1,...yBn));	// when there is n value to show for each x
     *   $legend     = array("Val1",...,"Valn");											// list of n series name
     *
     * @param	string	$file    	Image file name to use if we save onto disk
     * @param	string	$fileurl	Url path to show image if saved onto disk
     * @return	void
     */
    private function draw_jflot($file,$fileurl)
    {
        global $artichow_defaultfont;

        dol_syslog(get_class($this)."::draw_jflot this->type=".join(',',$this->type));

        // On boucle sur chaque lot de donnees
        $legends=array();
        $nblot=count($this->data[0])-1;    // -1 to remove legend
        $firstlot=0;
        if ($nblot > 2) $firstlot = ($nblot - 2);        // We limit nblot to 2 because jflot can't manage more than 2 bars on same x

        $i=$firstlot;
        $serie=array();
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

            //print "Lot de donnees $i<br>";
            //print_r($values);
            //print '<br>';
            $serie[$i]="var d".$i." = [];\n";
            $x=0;
            foreach($newvalues as $key => $val)
            {
                if (isset($val)) $serie[$i].="d".$i.".push([".$x.", ".$val."]);\n";
                $x++;
            }

            $i++;
        }

        $tag=dol_escape_htmltag(dol_string_unaccent(dol_string_nospecial(basename($file),'_',array('-','.'))));

        $this->_stringtoshow ='<!-- Build using '.$this->_library.' -->'."\n";
        $this->_stringtoshow.='<br><div align="center">'.$this->title.'</div><br>';
        $this->_stringtoshow.='<div id="placeholder_'.$tag.'" style="width:'.$this->width.'px;height:'.$this->height.'px;" class="dolgraph"></div>'."\n";
        $this->_stringtoshow.='<script id="'.$tag.'">'."\n";
        $this->_stringtoshow.='$(function () {'."\n";
        $i=$firstlot;
        while ($i < $nblot)
        {
            $this->_stringtoshow.=$serie[$i];
            $i++;
        }
        $this->_stringtoshow.="\n";

        $this->_stringtoshow.='
        function showTooltip_'.$tag.'(x, y, contents) {
            $(\'<div id="tooltip_'.$tag.'">\' + contents + \'</div>\').css( {
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

                showTooltip_'.$tag.'(item.pageX, item.pageY,
                            item.series.label + "<br>" + z + " => " + y);
            }
        }
        else {
            $("#tooltip_'.$tag.'").remove();
            previousPoint = null;
        }

    	});
        ';

        $this->_stringtoshow.='var stack = null, steps = false;'."\n";

        $this->_stringtoshow.='function plotWithOptions_'.$tag.'() {'."\n";
        $this->_stringtoshow.='$.plot($("#placeholder_'.$tag.'"), [ '."\n";
        $i=$firstlot;
        while ($i < $nblot)
        {
            if ($i > $firstlot) $this->_stringtoshow.=', '."\n";
            $color=sprintf("%02x%02x%02x",$this->datacolor[$i][0],$this->datacolor[$i][1],$this->datacolor[$i][2]);
            $this->_stringtoshow.='{ ';
            if (! isset($this->type[$i]) || $this->type[$i] == 'bars') $this->_stringtoshow.='bars: { show: true, align: "'.($i==$firstlot?'center':'left').'", barWidth: 0.5 }, ';
            if (isset($this->type[$i]) && $this->type[$i] == 'lines')  $this->_stringtoshow.='lines: { show: true, fill: false }, ';
            $this->_stringtoshow.='color: "#'.$color.'", label: "'.(isset($this->Legend[$i]) ? dol_escape_js($this->Legend[$i]) : '').'", data: d'.$i.' }';
            $i++;
        }
        $this->_stringtoshow.="\n".' ], { series: { stack: stack, lines: { fill: false, steps: steps }, bars: { barWidth: 0.6 } }'."\n";

        // Xaxis
        $this->_stringtoshow.=', xaxis: { ticks: ['."\n";
        $x=0;
        foreach($this->data as $key => $valarray)
        {
            if ($x > 0) $this->_stringtoshow.=', '."\n";
            $this->_stringtoshow.= ' ['.$x.', "'.$valarray[0].'"]';
            $x++;
        }
        $this->_stringtoshow.='] }'."\n";

        // Yaxis
        $this->_stringtoshow.=', yaxis: { min: '.$this->MinValue.', max: '.($this->MaxValue).' }'."\n";

        // Background color
        $color1=sprintf("%02x%02x%02x",$this->bgcolorgrid[0],$this->bgcolorgrid[0],$this->bgcolorgrid[2]);
        $color2=sprintf("%02x%02x%02x",$this->bgcolorgrid[0],$this->bgcolorgrid[1],$this->bgcolorgrid[2]);
        $this->_stringtoshow.=', grid: { hoverable: true, backgroundColor: { colors: ["#'.$color1.'", "#'.$color2.'"] } }'."\n";
        //$this->_stringtoshow.=', shadowSize: 20'."\n";    TODO Uncommet this
        $this->_stringtoshow.='});'."\n";
        $this->_stringtoshow.='}'."\n";

        $this->_stringtoshow.='plotWithOptions_'.$tag.'();'."\n";
        $this->_stringtoshow.='});'."\n";
        $this->_stringtoshow.='</script>'."\n";
    }



    /**
     * Output HTML string to show graph
     *
     * @return	string		HTML string to show graph
     */
    function show()
    {
        return $this->_stringtoshow;
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

    	if ($direction == 'width')  return ($conf->dol_optimize_smallscreen?(empty($_SESSION['dol_screen_width'])?'280':$_SESSION['dol_screen_width']-40):($defaultsize?$defaultsize:'500'));
    	if ($direction == 'height') return ($conf->dol_optimize_smallscreen?'160':($defaultsize?$defaultsize:'200'));
    	return 0;
    }
}

?>
