<?php
/* Copyright (c) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/class/dolchartjs.class.php
 *  \ingroup    core
 *  \brief      File for class to generate graph with chartjs
 */


/**
 * Class to build graphs with chartjs
 * Usage is:
 *    $dolchartjs = new DolChartJs();
 *    $dolchartjs-> ...
 */
class DolChartJs
{
    /**
     * @var array
     */
    private $types = array(
        'bar',
        'bubble',
        'doughnut',
        'horizontalBar',
        'line',
        'pie',
        'polarArea',
        'radar',
        'scatter',
    );

    /**
     * @var array
     */
    private $defaults = array(
        'datasets' => array(),
        'labels'   => array(),
        'type'     => 'line',
        'options'  => array(),
        'size'     => array(
            'width' => null,
            'height' => null,
            )
    );

    /**
     * @var string
     */
    private $element;

    /**
     *  array(R,G,B)
     * @var array
     */
    public $bordercolor;

    /**
     * array('#RGB', ...)
     * @var array
     */
    public $bgcolor;

    /**
     * array('#RGB', ...)
     * @var array
     */
    public $bgcolorgrid = '#FFFFFF';

    /**
     * array('#RGB', ...)
     * @var array
     */
    public $datacolor;

    /**
     * array('#RGB', ...)
     * @var array
     */
    public $bgdatacolor;

    /**
     * array('pie', ...)
     * @var array
     */
    public $switchers;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        global $conf;
        global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        $this->bordercolor = '#e0e0e0';
        $this->bgcolor = '#0b0b0b';
        $this->datacolor = array('#788296', '#a0a0b4', '#bebedc');
        $this->bgdatacolor = array('#788296', '#a0a0b4', '#bebedc');

        $color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/graph-color.php';
        // $theme_bordercolor = array(235,235,224);
        // $theme_bgcolor = array(hexdec('F4'),hexdec('F4'),hexdec('F4'));
        // $theme_bgcoloronglet = array(hexdec('DE'),hexdec('E7'),hexdec('EC'));
        // $theme_datacolor = array(
        //     array(136,102,136),
        //     array(0,130,110),
        //     array(140,140,220),
        //     array(190,120,120),
        //     array(190,190,100),
        //     array(115,125,150),
        //     array(100,170,20),
        //     array(250,190,30),
        //     array(150,135,125),
        //     array(85,135,150),
        //     array(150,135,80),
        //     array(150,80,150),
        // );

        if (is_readable($color_file)) {
            include_once $color_file;
            if (isset($theme_bordercolor)) {
                $this->bordercolor = $this->setFromArray($theme_bordercolor);
            }
            if (isset($theme_datacolor)) {
                $this->datacolor = $this->setFromArray($theme_datacolor);
                $this->bgdatacolor =  $this->setFromArray($theme_datacolor, 0.95);
            }
            if (isset($theme_bgcolor)) {
                $this->bgcolor = $this->setFromArray($theme_bgcolor);
            }
        }
    }

    /**
     *
     */
    private function setfromArray($arraycolor, $coef = 1)
    {
        $arrayhex = array();
        foreach ($arraycolor as $value) {
            $arrayhex[] = '#'.colorArrayToHex(array($value[0]*$coef, $value[1]*$coef, $value[2]*$coef));
        }
        return $arrayhex;
    }

    /**
     * @param $element
     *
     * @return $this|DolChartJs
     */
    public function element($element)
    {
        $this->element = $element;
        $this->charts[$element] = $this->defaults;
        $this->set('switchers', array());
        return $this;
    }

    /**
     * @param array $size
     *
     * @return DolChartJs
     */
    public function setSize($size)
    {
        return $this->set('size', $size);
    }

    /**
     * @param $type
     *
     * @return DolChartJs
     */
    public function setType($type)
    {
        if (!in_array($type, $this->types)) {
            throw new \InvalidArgumentException('Invalid Chart type.');
        }
        return $this->set('type', $type);
    }

    /**
     * @param array $labels
     *
     * @return DolChartJs
     */
    public function setLabels(array $labels)
    {
        return $this->set('labels', $labels);
    }

    /**
     * @param array $datasets
     *
     * @return DolChartJs
     */
    public function setDatasets(array $datasets)
    {
        return $this->set('datasets', $datasets);
    }

    /**
     * @param array $options
     *
     * @return $this|DolChartJs
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->set('options.' . $key, $value);
        }
        return $this;
    }

    /**
     * @param array $switchers
     *
     * @return DolChartJs
     */
    public function setSwitchers(array $switchers)
    {
        return $this->set('switchers', $switchers);
    }

    /**
     * @return mixed
     */
    public function renderChart()
    {
        $chart = $this->charts[$this->element];
        $switchers = $this->charts[$this->element]['switchers'];
        $template = "<div class=\"".$this->element."-container\" style=\"position: relative; width:".$chart['size']['width']."vh; height:".$chart['size']['height']."vh;\">";
        $template .= "<canvas id=\"".$this->element."\"></canvas></div>";
        foreach ($switchers as $switcher) {
            $template .= "<span><button type=\"button\" class=\"fa fa-".$switcher."-chart\" onclick=\"setChart".$this->element."('".$switcher."');\"></button></span>";
        }
        $template .= "<script>\n";
        $template .= "var ctx".$this->element." = document.getElementById('".$this->element."').getContext('2d');\n";
        $template .= "var chart".$this->element.";\n";
        $template .= "var chartType".$this->element."='".$chart['type']."';\n";
        $template .= "\tinit".$this->element."();\n";
        $template .= "\tfunction init".$this->element."() {\n";
        $template .= "\tconsole.log(chartType".$this->element.");\n";
        $template .= "\t\t\tchart".$this->element." = new Chart(ctx".$this->element.", {\n";
        $template .= "\t\t\t\ttype: chartType".$this->element.",\n";
        $template .= "\t\t\t\tdata: {\n";
        $template .= "\t\t\t\t\tlabels: ". json_encode($chart['labels']).",\n";
        $template .= "\t\t\t\t\tdatasets: ". json_encode($chart['datasets'])."\n";
        $template .= "\t\t\t\t},\n";
        if (!empty($chart['optionsRaw'])) {
            $template .= "\t\t\t\toptions: ". $chart['optionsRaw']."\n";
        } elseif (!empty($chart['options'])) {
            $template .= "\t\t\t\toptions: ". json_encode($chart['options'])."\n";
        }
        $template .= "\t\t\t});\n";
        $template .= "\t}\n";
        $template .= "function setChart".$this->element."(type) {\n";
        $template .= "\t//destroy chart\n";
        $template .= "\tchart".$this->element.".destroy();\n";
        $template .= "\tthis.chartType".$this->element." = type;\n";
        $template .= "\tinit".$this->element."();\n";
        $template .= "}\n";
        $template .= '</script>'."\n";

        return $template;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    private function get($key)
    {
        return arrayGet($this->charts[$this->element], $key);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this|DolChartJs
     */
    private function set($key, $value)
    {
        $this->arraySet($this->charts[$this->element], $key, $value);
        return $this;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    function arraySet(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = array();
            }
            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function arrayGet($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }
        if (isset($array[$key])) {
            return $array[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if ( ! is_array($array) || ! array_key_exists($segment, $array)) {
                return value($default);
            }
            $array = $array[$segment];
        }
        return $array;
    }

    /**
     * getDefaultGraphSizeForStats
     *
     * @param   string  $direction      'width' or 'height'
     * @param   string	$defaultsize    Value we want as default size
     * @return  int                     Value of width or height to use by default
     */
    static function getDefaultGraphSizeForStats($direction, $defaultsize = '')
    {
        global $conf;

        if ($direction == 'width') {
            //var_dump($_SESSION['dol_screen_width']);
            if (empty($conf->dol_optimize_smallscreen)) {
                return ($defaultsize ? $defaultsize : '500');
            } else {
                return (empty($_SESSION['dol_screen_width']) ? '280' : ($_SESSION['dol_screen_width']-40));
            }
        }
        if ($direction == 'height') {
            return (empty($conf->dol_optimize_smallscreen)?($defaultsize?$defaultsize:'200'):'160');
        }
        return 0;
    }
}
