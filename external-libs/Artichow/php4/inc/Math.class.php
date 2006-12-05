<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

 class awShape {
	
	/**
	 * Is the shape hidden ?
	 *
	 * @var bool
	 */
	var $hide = FALSE;
	
	/**
	 * Shape style
	 *
	 * @var int
	 */
	var $style;
	
	/**
	 * Shape thickness
	 *
	 * @var int
	 */
	var $thickness;
	
	/**
	 * Solid shape
	 *
	 * @var int
	 */
	
	
	/**
	 * Dotted shape
	 *
	 * @var int
	 */
	
	
	/**
	 * Dashed shape
	 *
	 * @var int
	 */
	
	
	/**
	 * Change shape style
	 *
	 * @param int $style Line style
	 */
	 function setStyle($style) {
		$this->style = (int)$style;
	}
	
	/**
	 * Return shape style
	 *
	 * @return int
	 */
	 function getStyle() {
		return $this->style;
	}
	
	/**
	 * Change shape thickness
	 *
	 * @param int $thickness Shape thickness in pixels
	 */
	 function setThickness($thickness) {
		$this->thickness = (int)$thickness;
	}
	
	/**
	 * Return shape thickness
	 *
	 * @return int
	 */
	 function getThickness() {
		return $this->thickness;
	}
	
	/**
	 * Hide the shape
	 *
	 * @param bool $hide
	 */
	 function hide($hide) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show the shape
	 *
	 * @param bool $shape
	 */
	 function show($shape) {
		$this->hide = (bool)!$shape;
	}
	
	/**
	 * Is the line hidden ?
	 *
	 * @return bool
	 */
	 function isHidden() {
		return $this->hide;
	}
	
}

registerClass('Shape', TRUE);

/**
 * Describe a point
 *
 * @package Artichow
 */
class awPoint extends awShape {

	/**
	 * X coord
	 *
	 * @var float
	 */
	var $x;

	/**
	 * Y coord
	 *
	 * @var float
	 */
	var $y;
	
	/**
	 * Build a new awpoint
	 *
	 * @param float $x
	 * @param float $y
	 */
	 function awPoint($x, $y) {
	
		$this->setLocation($x, $y);
		
	}
	
	/**
	 * Change X value
	 *
	 * @param float $x
	 */
	 function setX($x) {
		$this->x = (float)$x;
	}
	
	/**
	 * Change Y value
	 *
	 * @param float $y
	 */
	 function setY($y) {
		$this->y = (float)$y;
	}
	
	/**
	 * Change point location
	 *
	 * @param float $x
	 * @param float $y
	 */
	 function setLocation($x, $y) {
		$this->setX($x);
		$this->setY($y);
	}
	
	/**
	 * Get point location
	 *
	 * @param array Point location
	 */
	 function getLocation() {
		return array($this->x, $this->y);
	}
	
	/**
	 * Get distance to another point
	 *
	 * @param $p A point
	 * @return float
	 */
	 function getDistance($p) {
	
		return sqrt(pow($p->x - $this->x, 2) + pow($p->y - $this->y, 2));
	
	}
	
	/**
	 * Move the point to another location
	 *
	 * @param Point A Point with the new awlocation
	 */
	 function move($x, $y) {
	
		return new awPoint(
			$this->x + $x,
			$this->y + $y
		);
		
	}

}

registerClass('Point');
 
/* <php4> */

define("LINE_SOLID", 1);
define("LINE_DOTTED", 2);
define("LINE_DASHED", 3);

/* </php4> */

/**
 * Describe a line
 *
 * @package Artichow
 */
class awLine extends awShape {

	/**
	 * Line first point
	 *
	 * @param Point
	 */
	var $p1;

	/**
	 * Line second point
	 *
	 * @param Point
	 */
	var $p2;
	
	/**
	 * Build a new awline
	 *
	 * @param $p1 First point
	 * @param $p2 Second point
	 * @param int $type Style of line (default to solid)
	 * @param int $thickness Line thickness (default to 1)
	 */
	 function awLine($p1 = NULL, $p2 = NULL, $type = LINE_SOLID, $thickness = 1) {
	
		$this->setLocation($p1, $p2);
		$this->setStyle($type);
		$this->setThickness($thickness);
		
	}
	
	/**
	 * Build a line from 4 coords
	 *
	 * @param int $x1 Left position
	 * @param int $y1 Top position
	 * @param int $x2 Right position
	 * @param int $y2 Bottom position
	 */
	  function build($x1, $y1, $x2, $y2) {
	
		return new awLine(
			new awPoint($x1, $y1),
			new awPoint($x2, $y2)
		);
	
	}
	
	/**
	 * Change X values of the line
	 *
	 * @param int $x1 Begin value
	 * @param int $x2 End value
	 */
	 function setX($x1, $x2) {
		$this->p1->setX($x1);
		$this->p2->setX($x2);
	}
	
	/**
	 * Change Y values of the line
	 *
	 * @param int $y1 Begin value
	 * @param int $y2 End value
	 */
	 function setY($y1, $y2) {
		$this->p1->setY($y1);
		$this->p2->setY($y2);
	}
	
	/**
	 * Change line location
	 *
	 * @param $p1 First point
	 * @param $p2 Second point
	 */
	 function setLocation($p1, $p2) {
		if(is_null($p1) or is_a($p1, 'awPoint')) {
			$this->p1 = $p1;
		}
		if(is_null($p2) or is_a($p2, 'awPoint')) {
			$this->p2 = $p2;
		}
	}
	
	/**
	 * Get line location
	 *
	 * @param array Line location
	 */
	 function getLocation() {
		return array($this->p1, $this->p2);
	}
	
	/**
	 * Get the line size
	 *
	 * @return float
	 */
	 function getSize() {
	
		$square = pow($this->p2->x - $this->p1->x, 2) + pow($this->p2->y - $this->p1->y, 2);
		return sqrt($square);
	
	}
	
	/**
	 * Test if the line can be considered as a point
	 *
	 * @return bool
	 */
	 function isPoint() {
		return ($this->p1->x === $this->p2->x and $this->p1->y === $this->p2->y);
	}
	
	/**
	 * Test if the line is a vertical line
	 *
	 * @return bool
	 */
	 function isVertical() {
		return ($this->p1->x === $this->p2->x);
	}
	
	/**
	 * Test if the line is an horizontal line
	 *
	 * @return bool
	 */
	 function isHorizontal() {
		return ($this->p1->y === $this->p2->y);
	}

}

registerClass('Line');

/**
 * A vector is a type of line
 * The sense of the vector goes from $p1 to $p2.
 *
 * @package Artichow
 */
class awVector extends awLine {
	
	/**
	 * Get vector angle in radians
	 *
	 * @return float
	 */
	 function getAngle() {
	
		if($this->isPoint()) {
			return 0.0;
		}
		
		$size = $this->getSize();
	
		$width = ($this->p2->x - $this->p1->x);
		$height = ($this->p2->y - $this->p1->y) * -1;
		
		if($width >= 0 and $height >= 0) {
			return acos($width / $size);
		} else if($width <= 0 and $height >= 0) {
			return acos($width / $size);
		} else {
			$height *= -1;
			if($width >= 0 and $height >= 0) {
				return 2 * M_PI - acos($width / $size);
			} else if($width <= 0 and $height >= 0) {
				return 2 * M_PI - acos($width / $size);
			}
		}
	
	}

}

registerClass('Vector');
 
/* <php4> */

define("POLYGON_SOLID", 1);
define("POLYGON_DOTTED", 2);
define("POLYGON_DASHED", 3);

/* </php4> */

/**
 * Describe a polygon
 *
 * @package Artichow
 */
class awPolygon extends awShape {

	/**
	 * Polygon points
	 *
	 * @var array
	 */
	var $points = array();

	/**
	 * Set a point in the polygon
	 *
	 * @param int $pos Point position
	 * @param $point
	 */
	 function set($pos, $point) {
		if(is_null($point) or is_a($point, 'awPoint')) {
			$this->points[$pos] = $point;
		}
	}
	
	/**
	 * Add a point at the end of the polygon
	 *
	 * @param $point
	 */
	 function append($point) {
		if(is_null($point) or is_a($point, 'awPoint')) {
			$this->points[] = $point;
		}
	}
	
	/**
	 * Get a point at a position in the polygon
	 *
	 * @param int $pos Point position
	 * @return Point
	 */
	 function get($pos) {
		return $this->points[$pos];
	}
	
	/**
	 * Count number of points in the polygon
	 *
	 * @return int
	 */
	 function count() {
		return count($this->points);
	}
	
	/**
	 * Returns all points in the polygon
	 *
	 * @return array
	 */
	 function all() {
		return $this->points;
	}

}

registerClass('Polygon');
?>