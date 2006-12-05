<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

require_once dirname(__FILE__)."/Plot.class.php";
 
/* <php4> */

define("LINEPLOT_LINE", 0);
define("LINEPLOT_MIDDLE", 1);

/* </php4> */

/**
 * LinePlot
 *
 * @package Artichow
 */
class awLinePlot extends awPlot {
	
	/**
	 * Add marks to your line plot
	 *
	 * @var Mark
	 */
	var $mark;
	
	/**
	 * Labels on your line plot
	 *
	 * @var Label
	 */
	var $label;
	
	/**
	 * Filled areas
	 *
	 * @var bool
	 */
	var $areas = array();
	
	/**
	 * Is the line hidden
	 *
	 * @var bool
	 */
	var $lineHide = FALSE;
	
	/**
	 * Line color
	 *
	 * @var Color
	 */
	var $lineColor;
	
	/**
	 * Line mode
	 *
	 * @var int
	 */
	var $lineMode = LINEPLOT_LINE;
	
	/**
	 * Line type
	 *
	 * @var int
	 */
	var $lineStyle = LINE_SOLID;
	
	/**
	 * Line thickness
	 *
	 * @var int
	 */
	var $lineThickness = 1;
	
	/**
	 * Line background
	 *
	 * @var Color, Gradient
	 */
	var $lineBackground;
	
	/**
	 * Line mode
	 *
	 * @var int
	 */
	
	
	/**
	 * Line in the middle
	 *
	 * @var int
	 */
	
	 	
	/**
	 * Construct a new awLinePlot
	 *
	 * @param array $values Some numeric values for Y axis
	 * @param int $mode
	 */
	 function awLinePlot($values, $mode = LINEPLOT_LINE) {
	
		parent::awPlot();
		
		$this->mark = new awMark;
		$this->label = new awLabel;
		
		$this->lineMode = (int)$mode;
		
		$this->setValues($values);
	
	}
	
	/**
	 * Hide line
	 *
	 * @param bool $hide
	 */
	 function hideLine($hide) {
		$this->lineHide = (bool)$hide;
	}
	
	/**
	 * Add a filled area
	 *
	 * @param int $start Begining of the area
	 * @param int $end End of the area
	 * @param mixed $background Background color or gradient of the area
	 */
	 function setFilledArea($start, $stop, $background) {
	
		if($stop <= $start) {
			trigger_error("End position can not be greater than begin position in awLinePlot::setFilledArea()", E_USER_ERROR);
		}
	
		$this->areas[] = array((int)$start, (int)$stop, $background);
	
	}
	
	/**
	 * Change line color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->lineColor = $color;
	}
	
	/**
	 * Change line style
	 *
	 * @param int $style
	 */
	 function setStyle($style) {
		$this->lineStyle = (int)$style;
	}
	
	/**
	 * Change line tickness
	 *
	 * @param int $tickness
	 */
	 function setThickness($tickness) {
		$this->lineThickness = (int)$tickness;
	}
	
	/**
	 * Change line background color
	 *
	 * @param $color
	 */
	 function setFillColor($color) {
		$this->lineBackground = $color;
	}
	
	/**
	 * Change line background gradient
	 *
	 * @param $gradient
	 */
	 function setFillGradient($gradient) {
		$this->lineBackground = $gradient;
	}

	/**
	 * Get the line thickness
	 *
	 * @return int
	 */
	 function getLegendLineThickness() {
		return $this->lineThickness;
	}

	/**
	 * Get the line type
	 *
	 * @return int
	 */
	 function getLegendLineStyle() {
		return $this->lineStyle;
	}

	/**
	 * Get the color of line
	 *
	 * @return Color
	 */
	 function getLegendLineColor() {
		return $this->lineColor;
	}

	/**
	 * Get the background color or gradient of an element of the component
	 *
	 * @return Color, Gradient
	 */
	 function getLegendBackground() {
		return $this->lineBackground;
	}

	/**
	 * Get a mark object
	 *
	 * @return Mark
	 */
	 function getLegendMark() {
		return $this->mark;
	}
	
	 function drawComponent($drawer, $x1, $y1, $x2, $y2, $aliasing) {
		
		$max = $this->getRealYMax();
		$min = $this->getRealYMin();
		
		// Get start and stop values
		list($start, $stop) = $this->getLimit();
		
		if($this->lineMode === LINEPLOT_MIDDLE) {
			$inc = $this->xAxis->getDistance(0, 1) / 2;
		} else {
			$inc = 0;
		}
		
		// Build the polygon
		$polygon = new awPolygon;
		
		for($key = $start; $key <= $stop; $key++) {
		
			$value = $this->datay[$key];
			
			if($value !== NULL) {
			
				$p = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($key, $value));
				$p = $p->move($inc, 0);
				$polygon->set($key, $p);
				
			}
		
		}
		
		// Draw backgrounds
		if(is_a($this->lineBackground, 'awColor') or is_a($this->lineBackground, 'awGradient')) {
		
			$backgroundPolygon = new awPolygon;
		
			$p = $this->xAxisPoint($start);
			$p = $p->move($inc, 0);
			$backgroundPolygon->append($p);
			
			// Add others points
			foreach($polygon->all() as $point) {
				$backgroundPolygon->append($point);
			}
			
			$p = $this->xAxisPoint($stop);
			$p = $p->move($inc, 0);
			$backgroundPolygon->append($p);
		
			// Draw polygon background
			$drawer->filledPolygon($this->lineBackground, $backgroundPolygon);
		
		}
		
		$this->drawArea($drawer, $polygon);
		
		// Draw line
		$prev = NULL;
		
		// Line color
		if($this->lineHide === FALSE) {
		
			if($this->lineColor === NULL) {
				$this->lineColor = new awColor(0, 0, 0);
			}
			
			foreach($polygon->all() as $point) {
			
				if($prev !== NULL) {
					$drawer->line(
						$this->lineColor,
						new awLine(
							$prev,
							$point,
							$this->lineStyle,
							$this->lineThickness
						)
					);
				}
				$prev = $point;
				
			}
			
			$this->lineColor->free();
			
		}
		
		// Draw marks and labels
		foreach($polygon->all() as $key => $point) {

			$this->mark->draw($drawer, $point);
			$this->label->draw($drawer, $point, $key);
			
		}
		
	}
	
	 function drawArea($drawer, &$polygon) {
	
		$starts = array();
		foreach($this->areas as $area) {
			list($start) = $area;
			$starts[$start] = TRUE;
		}
		
		// Draw filled areas
		foreach($this->areas as $area) {
		
			list($start, $stop, $background) = $area;
			
			$polygonArea = new awPolygon;
			
			$p = $this->xAxisPoint($start);
			$polygonArea->append($p);
			
			for($i = $start; $i <= $stop; $i++) {
				$p = $polygon->get($i);
				if($i === $stop and array_key_exists($stop, $starts)) {
					$p = $p->move(-1, 0);
				}
				$polygonArea->append($p);
			}
			
			$p = $this->xAxisPoint($stop);
			if(array_key_exists($stop, $starts)) {
				$p = $p->move(-1, 0);
			}
			$polygonArea->append($p);
		
			// Draw area
			$drawer->filledPolygon($background, $polygonArea);
		
		}
		
	}
	
	 function getXAxisNumber() {
		if($this->lineMode === LINEPLOT_MIDDLE) {
			return count($this->datay) + 1;
		} else {
			return count($this->datay);
		}
	}
	
	 function xAxisPoint($position) {
		$y = $this->xAxisZero ? 0 : $this->getRealYMin();
		return awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($position, $y));
	}
	
	 function getXCenter() {
		return ($this->lineMode === LINEPLOT_MIDDLE);
	}

}

registerClass('LinePlot');


/**
 * Simple LinePlot
 * Useful to draw simple horizontal lines
 *
 * @package Artichow
 */
class awSimpleLinePlot extends awPlot {
	
	/**
	 * Line color
	 *
	 * @var Color
	 */
	var $lineColor;
	
	/**
	 * Line start
	 *
	 * @var int
	 */
	var $lineStart;
	
	/**
	 * Line stop
	 *
	 * @var int
	 */
	var $lineStop;
	
	/**
	 * Line value
	 *
	 * @var flaot
	 */
	var $lineValue;
	
	/**
	 * Line mode
	 *
	 * @var int
	 */
	var $lineMode = LINEPLOT_LINE;
	
	/**
	 * Line type
	 *
	 * @var int
	 */
	var $lineStyle = LINE_SOLID;
	
	/**
	 * Line thickness
	 *
	 * @var int
	 */
	var $lineThickness = 1;
	
	/**
	 * Line mode
	 *
	 * @var int
	 */
	
	
	/**
	 * Line in the middle
	 *
	 * @var int
	 */
	
	 	
	/**
	 * Construct a new awLinePlot
	 *
	 * @param float $value A Y value
	 * @param int $start Line start index
	 * @param int $stop Line stop index
	 * @param int $mode Line mode
	 */
	 function awSimpleLinePlot($value, $start, $stop, $mode = LINEPLOT_LINE) {
	
		parent::awPlot();
		
		$this->lineMode = (int)$mode;
		
		$this->lineStart = (int)$start;
		$this->lineStop = (int)$stop;
		$this->lineValue = (float)$value;
		
		$this->lineColor = new awColor(0, 0, 0);
	
	}
	
	/**
	 * Change line color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->lineColor = $color;
	}
	
	/**
	 * Change line style
	 *
	 * @param int $style
	 */
	 function setStyle($style) {
		$this->lineStyle = (int)$style;
	}
	
	/**
	 * Change line tickness
	 *
	 * @param int $tickness
	 */
	 function setThickness($tickness) {
		$this->lineThickness = (int)$tickness;
	}

	/**
	 * Get the line thickness
	 *
	 * @return int
	 */
	 function getLegendLineThickness() {
		return $this->lineThickness;
	}

	/**
	 * Get the line type
	 *
	 * @return int
	 */
	 function getLegendLineStyle() {
		return $this->lineStyle;
	}

	/**
	 * Get the color of line
	 *
	 * @return Color
	 */
	 function getLegendLineColor() {
		return $this->lineColor;
	}

	 function getLegendBackground() {
		return NULL;
	}

	 function getLegendMark() {
		return NULL;
	}
	
	 function drawComponent($drawer, $x1, $y1, $x2, $y2, $aliasing) {
		
		if($this->lineMode === LINEPLOT_MIDDLE) {
			$inc = $this->xAxis->getDistance(0, 1) / 2;
		} else {
			$inc = 0;
		}
		
		$p1 = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($this->lineStart, $this->lineValue));
		$p2 = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($this->lineStop, $this->lineValue));
		
		$drawer->line(
			$this->lineColor,
			new awLine(
				$p1->move($inc, 0),
				$p2->move($inc, 0),
				$this->lineStyle,
				$this->lineThickness
			)
		);
		
		$this->lineColor->free();
		
	}
	
	 function getXAxisNumber() {
		if($this->lineMode === LINEPLOT_MIDDLE) {
			return count($this->datay) + 1;
		} else {
			return count($this->datay);
		}
	}
	
	 function xAxisPoint($position) {
		$y = $this->xAxisZero ? 0 : $this->getRealYMin();
		return awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($position, $y));
	}
	
	 function getXCenter() {
		return ($this->lineMode === LINEPLOT_MIDDLE);
	}

}

registerClass('SimpleLinePlot');
?>
