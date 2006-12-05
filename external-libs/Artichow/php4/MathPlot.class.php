<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

require_once dirname(__FILE__)."/Component.class.php";

/**
 * A mathematic function
 *
 * @package Artichow
 */
class awMathFunction {

	/**
	 * Function line
	 *
	 * @var Line
	 */
	var $line;
	
	/**
	 * Marks for your plot
	 *
	 * @var Mark
	 */
	var $mark;
	
	/**
	 * Callback function
	 *
	 * @var string
	 */
	var $f;
	
	/**
	 * Start the drawing from this value
	 *
	 * @var float
	 */
	var $fromX;
	
	/**
	 * Stop the drawing at this value
	 *
	 * @var float
	 */
	var $toX;

	/**
	 * Line color
	 *
	 * @var Color
	 */
	var $color;
	
	/**
	 * Construct the function
	 *
	 * @param string $f Callback function
	 * @param float $fromX
	 * @param float $toX
	 */
	 function awMathFunction($f, $fromX = NULL, $toX = NULL) {
	
		$this->f = (string)$f;
		$this->fromX = is_null($fromX) ? NULL : (float)$fromX;
		$this->toX = is_null($toX) ? NULL : (float)$toX;
		
		$this->line = new awLine;
		$this->mark = new awMark;
		$this->color = new awBlack;
	
	}
	
	/**
	 * Change line color
	 *
	 * @param $color A new awcolor
	 */
	 function setColor($color) {
		$this->color = $color;
	}
	
	/**
	 * Get line color
	 *
	 * @return Color
	 */
	 function getColor() {
		return $this->color;
	}

	/**
	 * Get the background color or gradient of an element of the component
	 *
	 * @return Color, Gradient
	 */
	 function getLegendBackground() {
	}

	/**
	 * Get the line thickness
	 *
	 * @return NULL
	 */
	 function getLegendLineThickness() {
		return $this->line->getThickness();
	}

	/**
	 * Get the line type
	 *
	 * @return NULL
	 */
	 function getLegendLineStyle() {
		return $this->line->getStyle();
	}

	/**
	 * Get the color of line
	 *
	 * @return NULL
	 */
	 function getLegendLineColor() {
		return $this->color;
	}

	/**
	 * Get a mark object
	 *
	 * @return NULL
	 */
	 function getLegendMark() {
		return $this->mark;
	}

}

registerClass('MathFunction');
 
/**
 * For mathematics functions
 *
 * @package Artichow
 */
class awMathPlot extends awComponent {
	
	/**
	 * Functions
	 *
	 * @var array
	 */
	var $functions = array();
	
	/**
	 * Grid properties
	 *
	 * @var Grid
	 */
	var $grid;
	
	/**
	 * X axis
	 *
	 * @var Axis
	 */
	var $xAxis;
	
	/**
	 * Y axis
	 *
	 * @var Axis
	 */
	var $yAxis;
	
	/**
	 * Extremum
	 *
	 * @var Side
	 */
	var $extremum = NULL;
	
	/**
	 * Interval
	 *
	 * @var float
	 */
	var $interval = 1;
	
	/**
	 * Build the plot
	 *
	 * @param int $xMin Minimum X value
	 * @param int $xMax Maximum X value
	 * @param int $yMax Maximum Y value
	 * @param int $yMin Minimum Y value
	 */
	 function awMathPlot($xMin, $xMax, $yMax, $yMin) {
	
		parent::awComponent();
		
		$this->setPadding(8, 8, 8, 8);
		
		$this->grid = new awGrid;
		
		// Hide grid by default
		$this->grid->hide(TRUE);
		
		// Set extremum
		$this->extremum = new awSide($xMin, $xMax, $yMax, $yMin);
		
		// Create axis
		$this->xAxis = new awAxis;
		$this->xAxis->setTickStyle(TICK_IN);
		$this->xAxis->label->hideValue(0);
		$this->initAxis($this->xAxis);
		
		$this->yAxis = new awAxis;
		$this->yAxis->setTickStyle(TICK_IN);
		$this->yAxis->label->hideValue(0);
		$this->initAxis($this->yAxis);
		
	}
	
	 function initAxis(&$axis) {
	
		$axis->setLabelPrecision(1);
		$axis->addTick('major', new awTick(0, 5));
		$axis->addTick('minor', new awTick(0, 3));
		$axis->addTick('micro', new awTick(0, 1));
		$axis->setNumberByTick('minor', 'major', 1);
		$axis->setNumberByTick('micro', 'minor', 4);
		$axis->label->setFont(new awTuffy(7));
		
	}
	
	/**
	 * Interval to calculate values
	 *
	 * @param float $interval
	 */
	 function setInterval($interval) {
		$this->interval = (float)$interval;
	}
	
	/**
	 * Add a formula f(x)
	 *
	 * @param &$function
	 * @param string $name Name for the legend (can be NULL if you don't want to set a legend)
	 * @param int $type Type for the legend
	 */
	 function add(&$function, $name = NULL, $type = LEGEND_LINE) {
	
		$this->functions[] = $function;
		
		if($name !== NULL) {
			$this->legend->add($function, $name, $type);
		}
	
	}
	
	 function init($drawer) {
		
		list($x1, $y1, $x2, $y2) = $this->getPosition();
		
		$this->xAxis->line->setX($x1, $x2);
		$this->xAxis->label->setAlign(NULL, LABEL_BOTTOM);
		$this->xAxis->label->move(0, 3);
		$this->xAxis->setRange($this->extremum->left, $this->extremum->right);
		
		$this->yAxis->line->setY($y2, $y1);
		$this->yAxis->label->setAlign(LABEL_RIGHT);
		$this->yAxis->label->move(-6, 0);
		$this->yAxis->reverseTickStyle();
		$this->yAxis->setRange($this->extremum->bottom, $this->extremum->top);
		
		
		$this->xAxis->setYCenter($this->yAxis, 0);
		$this->yAxis->setXCenter($this->xAxis, 0);
		
		if($this->yAxis->getLabelNumber() === NULL) {
			$number = $this->extremum->top - $this->extremum->bottom + 1;
			$this->yAxis->setLabelNumber($number);
		}
		
		if($this->xAxis->getLabelNumber() === NULL) {
			$number = $this->extremum->right - $this->extremum->left + 1;
			$this->xAxis->setLabelNumber($number);
		}
		
		// Set ticks
		
		$this->xAxis->ticks['major']->setNumber($this->xAxis->getLabelNumber());
		$this->yAxis->ticks['major']->setNumber($this->yAxis->getLabelNumber());
		
		
		// Set axis labels
		$labels = array();
		for($i = 0, $count = $this->xAxis->getLabelNumber(); $i < $count; $i++) {
			$labels[] = $i;
		}
		$this->xAxis->label->set($labels);
		
		$labels = array();
		for($i = 0, $count = $this->yAxis->getLabelNumber(); $i < $count; $i++) {
			$labels[] = $i;
		}
		$this->yAxis->label->set($labels);
	
		parent::init($drawer);
		
		// Create the grid
		$this->createGrid();
	
		// Draw the grid
		$this->grid->draw($drawer, $x1, $y1, $x2, $y2);
		
	}
	
	 function drawEnvelope($drawer) {
		
		// Draw axis
		$this->xAxis->draw($drawer);
		$this->yAxis->draw($drawer);
	
	}
	
	 function drawComponent($drawer, $x1, $y1, $x2, $y2, $aliasing) {
	
		foreach($this->functions as $function) {
		
			$f = $function->f;
			$fromX = is_null($function->fromX) ? $this->extremum->left : $function->fromX;
			$toX = is_null($function->toX) ? $this->extremum->right : $function->toX;
			
			$old = NULL;
			
			for($i = $fromX; $i <= $toX; $i += $this->interval) {
			
				$p = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($i, $f($i)));
				
				if($p->y >= $y1 and $p->y <= $y2) {
					$function->mark->draw($drawer, $p);
				}
			
				if($old !== NULL) {
				
					$line = $function->line;
					$line->setLocation($old, $p);
				
					if(
						($line->p1->y >= $y1 and $line->p1->y <= $y2) or
						($line->p2->y >= $y1 and $line->p2->y <= $y2)
					) {
						$drawer->line(
							$function->getColor(),
							$line
						);
					}
				
				}
				
				$old = $p;
			
			}
			
			// Draw last point if needed
			if($old !== NULL and $i - $this->interval != $toX) {
			
				$p = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint($toX, $f($toX)));
				
				if($p->y >= $y1 and $p->y <= $y2) {
					$function->mark->draw($drawer, $p);
				}
				
				
				$line = $function->line;
				$line->setLocation($old, $p);
				
				if(
					($line->p1->y >= $y1 and $line->p1->y <= $y2) or
					($line->p2->y >= $y1 and $line->p2->y <= $y2)
				) {
					$drawer->line(
						$function->getColor(),
						$line
					);
				}
				
			}
		
		}
	
	}
	
	 function createGrid() {
		
		// Horizontal lines of the grid

		$major = $this->yAxis->tick('major');
		$interval = $major->getInterval();
		$number = $this->yAxis->getLabelNumber() - 1;
		
		$h = array();
		if($number > 0) {
			for($i = 0; $i <= $number; $i++) {
				$h[] = $i / $number;
			}
		}
		
		// Vertical lines
	
		$major = $this->xAxis->tick('major');
		$interval = $major->getInterval();
		$number = $this->xAxis->getLabelNumber() - 1;
		
		$w = array();
		if($number > 0) {
			for($i = 0; $i <= $number; $i++) {
				if($i%$interval === 0) {
					$w[] = $i / $number;
				}
			}
		}
	
		$this->grid->setGrid($w, $h);
	
	}

}

registerClass('MathPlot');
?>