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
 * BarPlot
 *
 * @package Artichow
 */
class awBarPlot extends awPlot {
	
	/**
	 * Labels on your bar plot
	 *
	 * @var Label
	 */
	var $label;
	
	/**
	 * Bar plot identifier
	 *
	 * @var int
	 */
	var $identifier;
	
	/**
	 * Bar plot number
	 *
	 * @var int
	 */
	var $number;
	
	/**
	 * Bar plot depth
	 *
	 * @var int
	 */
	var $depth;
	
	/**
	 * For moving bars
	 *
	 * @var int
	 */
	var $move;
	
	/**
	 * Bars shadow
	 *
	 * @var Shadow
	 */
	var $barShadow;
	
	/**
	 * Bars border
	 *
	 * @var Border
	 */
	var $barBorder;
	
	/**
	 * Bars padding
	 *
	 * @var Side
	 */
	var $barPadding;
	
	/**
	 * Bars space
	 *
	 * @var int
	 */
	var $barSpace = 0;
	
	/**
	 * Bars background
	 *
	 * @var Color, Gradient
	 */
	var $barBackground;
	 	
	/**
	 * Construct a new awBarPlot
	 *
	 * @param array $values Some numeric values for Y axis
	 * @param int $identifier Plot identifier
	 * @param int $number Bar plot number
	 * @param int $depth Bar plot depth in pixels
	 */
	 function awBarPlot($values, $identifier = 1, $number = 1, $depth = 0) {
	
		parent::awPlot();
		
		$this->label = new awLabel;
		
		$this->barPadding = new awSide(0.08, 0.08, 0, 0);
		$this->barShadow = new awShadow(SHADOW_RIGHT_TOP);
		$this->barBorder = new awBorder;
		
		$this->setValues($values);
		
		$this->identifier = (int)$identifier;
		$this->number = (int)$number;
		$this->depth = (int)$depth;
		
		$this->move = new awSide;
		
		// Hide vertical grid
		$this->grid->hideVertical(TRUE);
	
	}
	
	/**
	 * Change bars padding
	 * This method is not compatible with awBarPlot::setBarPadding()
	 *
	 * @param float $left Left padding (between 0 and 1)
	 * @param float $right Right padding (between 0 and 1)
	 */
	 function setBarPadding($left = NULL, $right = NULL) {
		$this->barPadding->set($left, $right);
	}
	
	/**
	 * Change bars size
	 * This method is not compatible with awBarPlot::setBarPadding()
	 *
	 * @param int $width Bars size (between 0 and 1)
	 */
	 function setBarSize($size) {
		$padding = (1 - $size) / 2;
		$this->barPadding->set($padding, $padding);
	}
	
	/**
	 * Move bars
	 *
	 * @param int $x
	 * @param int $y
	 */
	 function move($x, $y) {
		$this->move->set($x, NULL, $y, NULL);
	}
	
	/**
	 * Change bars space
	 *
	 * @param int $space Space in pixels
	 */
	 function setBarSpace($space) {
		$this->barSpace = (int)$space;
	}
	
	/**
	 * Change line background color
	 *
	 * @param $color
	 */
	 function setBarColor($color) {
		$this->barBackground = $color;
	}
	
	/**
	 * Change line background gradient
	 *
	 * @param $gradient
	 */
	 function setBarGradient($gradient) {
		$this->barBackground = $gradient;
	}

	/**
	 * Get the line thickness
	 *
	 * @return int
	 */
	 function getLegendLineThickness() {
	}

	/**
	 * Get the line type
	 *
	 * @return int
	 */
	 function getLegendLineStyle() {
	}

	/**
	 * Get the color of line
	 *
	 * @return Color
	 */
	 function getLegendLineColor() {
	}

	/**
	 * Get the background color or gradient of an element of the component
	 *
	 * @return Color, Gradient
	 */
	 function getLegendBackground() {
		return $this->barBackground;
	}

	/**
	 * Get a mark object
	 *
	 * @return Mark
	 */
	 function getLegendMark() {
	}
	
	 function drawComponent($drawer, $x1, $y1, $x2, $y2, $aliasing) {
		
		$count = count($this->datay);
		$max = $this->getRealYMax(NULL);
		$min = $this->getRealYMin(NULL);
		
		// Find zero for bars
		if($this->xAxisZero and $min <= 0 and $max >= 0) {
			$zero = 0;
		} else if($max < 0) {
			$zero = $max;
		} else {
			$zero = $min;
		}
		
		// Get base position
		$zero = awAxis::toPosition($this->xAxis, $this->yAxis, new awPoint(0, $zero));
		
		// Distance between two values on the graph
		$distance = $this->xAxis->getDistance(0, 1);
		
		// Compute paddings
		$leftPadding = $this->barPadding->left * $distance;
		$rightPadding = $this->barPadding->right * $distance;
		
		$padding = $leftPadding + $rightPadding;
		$space = $this->barSpace * ($this->number - 1);
		
		$barSize = ($distance - $padding - $space) / $this->number;
		$barPosition = $leftPadding + $barSize * ($this->identifier - 1);
		
		for($key = 0; $key < $count; $key++) {
		
			$value = $this->datay[$key];
			
			if($value !== NULL) {
		
				$position = awAxis::toPosition(
					$this->xAxis,
					$this->yAxis,
					new awPoint($key, $value)
				);
			
				$barStart = $barPosition + ($this->identifier - 1) * $this->barSpace + $position->x;
				$barStop = $barStart + $barSize;
				
				$t1 = min($zero->y, $position->y);
				$t2 = max($zero->y, $position->y);
				
				if(round($t2 - $t1) == 0) {
					continue;
				}
				
				$p1 = new awPoint(
					round($barStart) + $this->depth + $this->move->left,
					round($t1) - $this->depth + $this->move->top
				);
				
				$p2 = new awPoint(
					round($barStop) + $this->depth + $this->move->left,
					round($t2) - $this->depth + $this->move->top
				);
				
				$this->drawBar($drawer, $p1, $p2);
				
			}
		
		}
		
		// Draw labels
		foreach($this->datay as $key => $value) {
			
			if($value !== NULL) {
		
				$position = awAxis::toPosition(
					$this->xAxis,
					$this->yAxis,
					new awPoint($key, $value)
				);
			
				$point = new awPoint(
					$barPosition + ($this->identifier - 1) * $this->barSpace + $position->x + $barSize / 2 + 1 + $this->depth,
					$position->y - $this->depth
				);
	
				$this->label->draw($drawer, $point, $key);
				
			}
			
		}
		
	}
	
	 function getXAxisNumber() {
		return count($this->datay) + 1;
	}
	// ça bidouille à fond ici !
	 function getXMax() {
		return array_max($this->datax) + 1;
	}
	
	 function getXCenter() {
		return TRUE;
	}
	
	 function drawBar($drawer, $p1, $p2) {
	
		// Draw shadow
		$this->barShadow->draw(
			$drawer,
			$p1,
			$p2,
			SHADOW_OUT
		);
		
		if(abs($p2->y - $p1->y) > 1) {
			
			$this->barBorder->rectangle(
				$drawer,
				$p1,
				$p2
			);
			
			if($this->barBackground !== NULL) {
			
				$size = $this->barBorder->visible() ? 1 : 0;
		
				$b1 = $p1->move($size, $size);
				$b2 = $p2->move(-1 * $size, -1 * $size);
				
				// Draw background
				$drawer->filledRectangle(
					$this->barBackground,
					new awLine($b1, $b2)
				);
				
			}
		
		}
	}

}

registerClass('BarPlot');
?>
