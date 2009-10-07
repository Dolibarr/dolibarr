<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
/* <php4> */

define("SHADOW_LEFT_TOP", 1);
define("SHADOW_LEFT_BOTTOM", 2);
define("SHADOW_RIGHT_TOP", 3);
define("SHADOW_RIGHT_BOTTOM", 4);

define("SHADOW_IN", 1);
define("SHADOW_OUT", 2);

/* </php4> */

/**
 * Draw shadows
 *
 */
class awShadow {

	/**
	 * Shadow on left and top sides
	 *
	 * @var int
	 */
	

	/**
	 * Shadow on left and bottom sides
	 *
	 * @var int
	 */
	
	

	/**
	 * Shadow on right and top sides
	 *
	 * @var int
	 */
	

	/**
	 * Shadow on right and bottom sides
	 *
	 * @var int
	 */
	
	
	/**
	 * In mode
	 *
	 * @var int
	 */
	
	
	/**
	 * Out mode
	 *
	 * @var int
	 */
	

	/**
	 * Shadow size
	 *
	 * @var int
	 */
	var $size = 0;
	
	/**
	 * Hide shadow ?
	 *
	 * @var bool
	 */
	var $hide = FALSE;

	/**
	 * Shadow color
	 *
	 * @var Color
	 */
	var $color;

	/**
	 * Shadow position
	 *
	 * @var int
	 */
	var $position;

	/**
	 * Smooth shadow ?
	 *
	 * @var bool
	 */
	var $smooth = FALSE;
	
	/**
	 * Shadow constructor
	 *
	 * @param int $position Shadow position
	 */
	 function awShadow($position) {
		$this->setPosition($position);
	}
	
	/**
	 * Hide shadow ?
	 *
	 * @param bool $hide
	 */
	 function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show shadow ?
	 *
	 * @param bool $show
	 */
	 function show($show = TRUE) {
		$this->hide = (bool)!$show;
	}
	
	/**
	 * Change shadow size
	 *
	 * @param int $size
	 * @param bool $smooth Smooth the shadow (facultative argument)
	 */
	 function setSize($size, $smooth = NULL) {
		$this->size = (int)$size;
		if($smooth !== NULL) {
			$this->smooth($smooth);
		}
	}
	
	/**
	 * Change shadow color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->color = $color;
	}
	
	/**
	 * Change shadow position
	 *
	 * @param int $position
	 */
	 function setPosition($position) {
		$this->position = (int)$position;
	}
	
	/**
	 * Smooth shadow ?
	 *
	 * @param bool $smooth
	 */
	 function smooth($smooth) {
		$this->smooth = (bool)$smooth;
	}
	
	/**
	 * Get the space taken by the shadow
	 *
	 * @return Side
	 */
	 function getSpace() {
	
		return new awSide(
			($this->position === SHADOW_LEFT_TOP or $this->position === SHADOW_LEFT_BOTTOM) ? $this->size : 0,
			($this->position === SHADOW_RIGHT_TOP or $this->position === SHADOW_RIGHT_BOTTOM) ? $this->size : 0,
			($this->position === SHADOW_LEFT_TOP or $this->position === SHADOW_RIGHT_TOP) ? $this->size : 0,
			($this->position === SHADOW_LEFT_BOTTOM or $this->position === SHADOW_RIGHT_BOTTOM) ? $this->size : 0
		);
	
	}
	
	/**
	 * Draw shadow
	 *
	 * @param $drawer
	 * @param $p1 Top-left point
	 * @param $p2 Right-bottom point
	 * @param int Drawing mode
	 */
	 function draw($drawer, $p1, $p2, $mode) {
	
		if($this->hide) {
			return;
		}
	
		if($this->size <= 0) {
			return;
		}
		
		
		
		$color = (is_a($this->color, 'awColor')) ? $this->color : new awColor(125, 125, 125);
	
		switch($this->position) {
		
			case SHADOW_RIGHT_BOTTOM :
			
				if($mode === SHADOW_OUT) {
					$t1 = $p1->move(0, 0);
					$t2 = $p2->move($this->size + 1, $this->size + 1);
				} else { // PHP 4 compatibility
					$t1 = $p1->move(0, 0);
					$t2 = $p2->move(0, 0);
				}
		
				$width = $t2->x - $t1->x;
				$height = $t2->y - $t1->y;
		
				$drawer->setAbsPosition($t1->x + $drawer->x, $t1->y + $drawer->y);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint($width - $this->size, $this->size),
						new awPoint($width - 1, $height - 1)
					)
				);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint($this->size, $height - $this->size),
						new awPoint($width - $this->size - 1, $height - 1)
					)
				);
				
				$this->smoothPast($drawer, $color, $width, $height);
				
				break;
		
			case SHADOW_LEFT_TOP :
			
				if($mode === SHADOW_OUT) {
					$t1 = $p1->move(- $this->size, - $this->size);
					$t2 = $p2->move(0, 0);
				} else { // PHP 4 compatibility
					$t1 = $p1->move(0, 0);
					$t2 = $p2->move(0, 0);
				}
		
				$width = $t2->x - $t1->x;
				$height = $t2->y - $t1->y;
		
				$drawer->setAbsPosition($t1->x + $drawer->x, $t1->y + $drawer->y);
				
				$height = max($height + 1, $this->size);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint(0, 0),
						new awPoint($this->size - 1, $height - $this->size - 1)
					)
				);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint($this->size, 0),
						new awPoint($width - $this->size - 1, $this->size - 1)
					)
				);
				
				$this->smoothPast($drawer, $color, $width, $height);
				
				break;
		
			case SHADOW_RIGHT_TOP :
			
				if($mode === SHADOW_OUT) {
					$t1 = $p1->move(0, - $this->size);
					$t2 = $p2->move($this->size + 1, 0);
				} else { // PHP 4 compatibility
					$t1 = $p1->move(0, 0);
					$t2 = $p2->move(0, 0);
				}
		
				$width = $t2->x - $t1->x;
				$height = $t2->y - $t1->y;
		
				$drawer->setAbsPosition($t1->x + $drawer->x, $t1->y + $drawer->y);
				
				$height = max($height + 1, $this->size);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint($width - $this->size, 0),
						new awPoint($width - 1, $height - $this->size - 1)
					)
				);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint($this->size, 0),
						new awPoint($width - $this->size - 1, $this->size - 1)
					)
				);
				
				$this->smoothFuture($drawer, $color, $width, $height);
				
				break;
		
			case SHADOW_LEFT_BOTTOM :
			
				if($mode === SHADOW_OUT) {
					$t1 = $p1->move(- $this->size, 0);
					$t2 = $p2->move(0, $this->size + 1);
				} else { // PHP 4 compatibility
					$t1 = $p1->move(0, 0);
					$t2 = $p2->move(0, 0);
				}
		
				$width = $t2->x - $t1->x;
				$height = $t2->y - $t1->y;
		
				$drawer->setAbsPosition($t1->x + $drawer->x, $t1->y + $drawer->y);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint(0, $this->size),
						new awPoint($this->size - 1, $height - 1)
					)
				);
			
				$drawer->filledRectangle(
					$color,
					new awLine(
						new awPoint($this->size, $height - $this->size),
						new awPoint($width - $this->size - 1, $height - 1)
					)
				);
				
				$this->smoothFuture($drawer, $color, $width, $height);
				
				break;
		
		}
	
	}
	
	 function smoothPast($drawer, $color, $width, $height) {
		
		if($this->smooth) {
		
			for($i = 0; $i < $this->size; $i++) {
				for($j = 0; $j <= $i; $j++) {
					$drawer->point(
						$color,
						new awPoint($i, $j + $height - $this->size)
					);
				}
			}
			
			for($i = 0; $i < $this->size; $i++) {
				for($j = 0; $j <= $i; $j++) {
					$drawer->point(
						$color,
						new awPoint($width - $this->size + $j, $i)
					);
				}
			}
			
		}
		
	}
	
	 function smoothFuture($drawer, $color, $width, $height) {
		
		if($this->smooth) {
		
			for($i = 0; $i < $this->size; $i++) {
				for($j = 0; $j <= $i; $j++) {
					$drawer->point(
						$color,
						new awPoint($i, $this->size - $j - 1)
					);
				}
			}
			
			for($i = 0; $i < $this->size; $i++) {
				for($j = 0; $j <= $i; $j++) {
					$drawer->point(
						$color,
						new awPoint($width - $this->size + $j, $height - $i - 1)
					);
				}
			}
			
		}
	}

}

registerClass('Shadow');
?>