<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
/**
 * Grid
 *
 * @package Artichow 
 */
class awGrid {
	
	/**
	 * Vertical lines of the grid
	 *
	 * @var array
	 */
	var $xgrid = array();
	
	/**
	 * Horizontal lines of the grid
	 *
	 * @var array
	 */
	var $ygrid = array();

	/**
	 * Is the component grid hidden ?
	 *
	 * @var bool
	 */
	var $hide = FALSE;

	/**
	 * Are horizontal lines hidden ?
	 *
	 * @var bool
	 */
	var $hideHorizontal = FALSE;

	/**
	 * Are vertical lines hidden ?
	 *
	 * @var bool
	 */
	var $hideVertical = FALSE;
	
	/**
	 * Grid color
	 *
	 * @var Color
	 */
	var $color;
	
	/**
	 * Grid space
	 *
	 * @var int
	 */
	var $space;
	
	/**
	 * Line type
	 *
	 * @var int
	 */
	var $type = LINE_SOLID;
	
	/**
	 * Grid interval
	 *
	 * @var int
	 */
	var $interval = array(1, 1);
	
	/**
	 * Grid background color
	 *
	 * @var Color
	 */
	var $background;
	
	/**
	 * Build the factory
	 */
	 function awGrid() {
	
		// Set a grid default color
		$this->color = new awColor(210, 210, 210);
		$this->background = new awColor(255, 255, 255, 100);
		
	}
	
	/**
	 * Hide grid ?
	 *
	 * @param bool $hide
	 */
	 function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Hide horizontal lines ?
	 *
	 * @param bool $hideHorizontal
	 */
	 function hideHorizontal($hide = TRUE) {
		$this->hideHorizontal = (bool)$hide;
	}
	
	/**
	 * Hide vertical lines ?
	 *
	 * @param bool $hideVertical
	 */
	 function hideVertical($hide = TRUE) {
		$this->hideVertical = (bool)$hide;
	}
	
	/**
	 * Change grid color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->color = $color;
	}
	
	/**
	 * Remove grid background
	 */
	 function setNoBackground() {
		$this->background = NULL;
	}
	
	/**
	 * Change grid background color
	 *
	 * @param $color
	 */
	 function setBackgroundColor($color) {
		$this->background = $color;
	}
	
	/**
	 * Change line type
	 *
	 * @param int $type
	 */
	 function setType($type) {
		$this->type = (int)$type;
	}
	
	/**
	 * Change grid interval
	 *
	 * @param int $hInterval
	 * @param int $vInterval
	 */
	 function setInterval($hInterval, $vInterval) {
		$this->interval = array((int)$hInterval, (int)$vInterval);
	}
	
	/**
	 * Set grid space
	 *
	 * @param int $left Left space in pixels
	 * @param int $right Right space in pixels
	 * @param int $top Top space in pixels
	 * @param int $bottom Bottom space in pixels
	 */
	 function setSpace($left, $right, $top, $bottom) {
		$this->space = array((int)$left, (int)$right, (int)$top, (int)$bottom);
	}
	
	/**
	 * Change the current grid
	 *
	 * @param array $xgrid Vertical lines
	 * @param array $ygrid Horizontal lines
	 */
	 function setGrid($xgrid, $ygrid) {
	
		$this->xgrid = $xgrid;
		$this->ygrid = $ygrid;
	
	}
	
	/**
	 * Draw grids
	 *
	 * @param $drawer A drawer object
	 * @param int $x1
	 * @param int $y1
	 * @param int $x2
	 * @param int $y2
	 */
	 function draw($drawer, $x1, $y1, $x2, $y2) {
	
		if(is_a($this->background, 'awColor')) {
		
			// Draw background color
			$drawer->filledRectangle(
				$this->background, 
				awLine::build($x1, $y1, $x2, $y2)
			);
		
			$this->background->free();
			
		}
	
		if($this->hide === FALSE) {
			
			$this->drawGrid(
				$drawer,
				$this->color,
				$this->hideVertical ? array() : $this->xgrid,
				$this->hideHorizontal ? array() : $this->ygrid,
				$x1, $y1, $x2, $y2,
				$this->type,
				$this->space,
				$this->interval[0],
				$this->interval[1]
			);
			
		}
		
		$this->color->free();
	
	}
	
	 function drawGrid(
		$drawer, $color,
		$nx, $ny, $x1, $y1, $x2, $y2,
		$type, $space, $hInterval, $vInterval
	) {
	
		list($left, $right, $top, $bottom) = $space;
		
		$width = $x2 - $x1 - $left - $right;
		$height = $y2 - $y1 - $top - $bottom;
	
		foreach($nx as $key => $n) {
		
			if(($key % $vInterval) === 0) {
		
				$pos = (int)round($x1 + $left + $n * $width);
				$drawer->line(
					$color,
					new awLine(
						new awPoint($pos, $y1),
						new awPoint($pos, $y2),
						$type
					)
				);
				
			}
		
		}
	
		foreach($ny as $key => $n) {
		
			if(($key % $hInterval) === 0) {
		
				$pos = (int)round($y1 + $top + $n * $height);
				$drawer->line(
					$color,
					new awLine(
						new awPoint($x1, $pos),
						new awPoint($x2, $pos),
						$type
					)
				);
				
			}
		
		}
	
	}

}

registerClass('Grid');
?>