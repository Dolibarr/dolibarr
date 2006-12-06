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
	const LEFT_TOP = 1;

	/**
	 * Shadow on left and bottom sides
	 *
	 * @var int
	 */
	const LEFT_BOTTOM = 2;
	

	/**
	 * Shadow on right and top sides
	 *
	 * @var int
	 */
	const RIGHT_TOP = 3;

	/**
	 * Shadow on right and bottom sides
	 *
	 * @var int
	 */
	const RIGHT_BOTTOM = 4;
	
	/**
	 * In mode
	 *
	 * @var int
	 */
	const IN = 1;
	
	/**
	 * Out mode
	 *
	 * @var int
	 */
	const OUT = 2;

	/**
	 * Shadow size
	 *
	 * @var int
	 */
	private $size = 0;
	
	/**
	 * Hide shadow ?
	 *
	 * @var bool
	 */
	protected $hide = FALSE;

	/**
	 * Shadow color
	 *
	 * @var Color
	 */
	private $color;

	/**
	 * Shadow position
	 *
	 * @var int
	 */
	private $position;

	/**
	 * Smooth shadow ?
	 *
	 * @var bool
	 */
	private $smooth = FALSE;
	
	/**
	 * Shadow constructor
	 *
	 * @param int $position Shadow position
	 */
	public function __construct($position) {
		$this->setPosition($position);
	}
	
	/**
	 * Hide shadow ?
	 *
	 * @param bool $hide
	 */
	public function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show shadow ?
	 *
	 * @param bool $show
	 */
	public function show($show = TRUE) {
		$this->hide = (bool)!$show;
	}
	
	/**
	 * Change shadow size
	 *
	 * @param int $size
	 * @param bool $smooth Smooth the shadow (facultative argument)
	 */
	public function setSize($size, $smooth = NULL) {
		$this->size = (int)$size;
		if($smooth !== NULL) {
			$this->smooth($smooth);
		}
	}
	
	/**
	 * Change shadow color
	 *
	 * @param awColor $color
	 */
	public function setColor(awColor $color) {
		$this->color = $color;
	}
	
	/**
	 * Change shadow position
	 *
	 * @param int $position
	 */
	public function setPosition($position) {
		$this->position = (int)$position;
	}
	
	/**
	 * Smooth shadow ?
	 *
	 * @param bool $smooth
	 */
	public function smooth($smooth) {
		$this->smooth = (bool)$smooth;
	}
	
	/**
	 * Get the space taken by the shadow
	 *
	 * @return Side
	 */
	public function getSpace() {
	
		return new awSide(
			($this->position === awShadow::LEFT_TOP or $this->position === awShadow::LEFT_BOTTOM) ? $this->size : 0,
			($this->position === awShadow::RIGHT_TOP or $this->position === awShadow::RIGHT_BOTTOM) ? $this->size : 0,
			($this->position === awShadow::LEFT_TOP or $this->position === awShadow::RIGHT_TOP) ? $this->size : 0,
			($this->position === awShadow::LEFT_BOTTOM or $this->position === awShadow::RIGHT_BOTTOM) ? $this->size : 0
		);
	
	}
	
	/**
	 * Draw shadow
	 *
	 * @param awDrawer $drawer
	 * @param awPoint $p1 Top-left point
	 * @param awPoint $p2 Right-bottom point
	 * @param int Drawing mode
	 */
	public function draw(awDrawer $drawer, awPoint $p1, awPoint $p2, $mode) {
	
		if($this->hide) {
			return;
		}
	
		if($this->size <= 0) {
			return;
		}
		
		$drawer = clone $drawer;
		
		$color = ($this->color instanceof awColor) ? $this->color : new awColor(125, 125, 125);
	
		switch($this->position) {
		
			case awShadow::RIGHT_BOTTOM :
			
				if($mode === awShadow::OUT) {
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
		
			case awShadow::LEFT_TOP :
			
				if($mode === awShadow::OUT) {
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
		
			case awShadow::RIGHT_TOP :
			
				if($mode === awShadow::OUT) {
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
		
			case awShadow::LEFT_BOTTOM :
			
				if($mode === awShadow::OUT) {
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
	
	private function smoothPast(awDrawer $drawer, awColor $color, $width, $height) {
		
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
	
	private function smoothFuture(awDrawer $drawer, awColor $color, $width, $height) {
		
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