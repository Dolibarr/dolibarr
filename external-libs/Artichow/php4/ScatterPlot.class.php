<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

require_once dirname(__FILE__)."/Plot.class.php";

/**
 * ScatterPlot
 *
 * @package Artichow
 */
class awScatterPlot extends awPlot {
	
	/**
	 * Add marks to the scatter plot
	 *
	 * @var Mark
	 */
	var $mark;
	
	/**
	 * Labels on the plot
	 *
	 * @var Label
	 */
	var $label;
	
	/**
	 * Link points ?
	 *
	 * @var bool
	 */
	var $link = FALSE;
	
	/**
	 * Display impulses
	 *
	 * @var bool
	 */
	var $impulse = NULL;
	
	/**
	 * Link NULL points ?
	 *
	 * @var bool
	 */
	var $linkNull = FALSE;
	
	/**
	 * Line color
	 *
	 * @var Color
	 */
	var $lineColor;
	
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
	 * Construct a new awScatterPlot
	 *
	 * @param array $datay Numeric values for Y axis
	 * @param array $datax Numeric values for X axis
	 * @param int $mode
	 */
	 function awScatterPlot($datay, $datax = NULL) {
	
		parent::awPlot();
		
		// Defaults marks
		$this->mark = new awMark;
		$this->mark->setType(MARK_CIRCLE);
		$this->mark->setSize(7);
		$this->mark->border->show();
		
		$this->label = new awLabel;
		
		$this->setValues($datay, $datax);
		$this->setColor(new awBlack);
	
	}
	
	/**
	 * Display plot as impulses
	 *
	 * @param $impulse Impulses color (or NULL to disable impulses)
	 */
	 function setImpulse($color) {
		$this->impulse = $color;
	}
	
	/**
	 * Link scatter plot points
	 *
	 * @param bool $link
	 * @param $color Line color (default to black)
	 */
	 function link($link, $color = NULL) {
		$this->link = (bool)$link;
		if(is_a($color, 'awColor')) {
			$this->setColor($color);
		}
	}
	
	/**
	 * Ignore null values for Y data and continue linking
	 *
	 * @param bool $link
	 */
	 function linkNull($link) {
		$this->linkNull = (bool)$link;
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

	/**
	 * Get the background color or gradient of an element of the component
	 *
	 * @return Color, Gradient
	 */
	 function getLegendBackground() {
		return NULL;
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
	
		$count = count($this->datay);
		
		// Get start and stop values
		list($start, $stop) = $this->getLimit();
		
		// Build the polygon
		$polygon = new awPolygon;
		
		for($key = 0; $key < $count; $key++) {
		
			$x = $this->datax[$key];
			$y = $this->datay[$key];
			
			if($y !== NULL) {
				$p = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($x, $y));
				$polygon->set($key, $p);
			} else if($this->linkNull === FALSE) {
				$polygon->set($key, NULL);
			}
		
		}
		
		// Link points if needed
		if($this->link) {
		
			$prev = NULL;
			
			foreach($polygon->all() as $point) {
			
				if($prev !== NULL and $point !== NULL) {
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
		
		// Draw impulses
		if(is_a($this->impulse, 'awColor')) {
			
			foreach($polygon->all() as $key => $point) {
			
				if($point !== NULL) {
					
					$zero = awAxis::toPosition(
						$this->xAxis,
						$this->yAxis,
						new awPoint($key, 0)
					);
					
					$drawer->line(
						$this->impulse,
						new awLine(
							$zero,
							$point,
							LINE_SOLID,
							1
						)
					);
					
				}
				
			}
		
		}
		
		// Draw marks and labels
		foreach($polygon->all() as $key => $point) {

			$this->mark->draw($drawer, $point);
			$this->label->draw($drawer, $point, $key);
			
		}
		
	}
	
	 function xAxisPoint($position) {
		$y = $this->xAxisZero ? 0 : $this->getRealYMin();
		return awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($position, $y));
	}
	
	 function getXCenter() {
		return FALSE;
	}

}

registerClass('ScatterPlot');
?>
