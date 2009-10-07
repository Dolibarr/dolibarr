<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

abstract class awShape {
	
	/**
	 * Is the shape hidden ?
	 *
	 * @var bool
	 */
	protected $hide = FALSE;
	
	/**
	 * Shape style
	 *
	 * @var int
	 */
	public $style;
	
	/**
	 * Shape thickness
	 *
	 * @var int
	 */
	public $thickness;
	
	/**
	 * Solid shape
	 *
	 * @var int
	 */
	const SOLID = 1;
	
	/**
	 * Dotted shape
	 *
	 * @var int
	 */
	const DOTTED = 2;
	
	/**
	 * Dashed shape
	 *
	 * @var int
	 */
	const DASHED = 3;
	
	/**
	 * Change shape style
	 *
	 * @param int $style Line style
	 */
	public function setStyle($style) {
		$this->style = (int)$style;
	}
	
	/**
	 * Return shape style
	 *
	 * @return int
	 */
	public function getStyle() {
		return $this->style;
	}
	
	/**
	 * Change shape thickness
	 *
	 * @param int $thickness Shape thickness in pixels
	 */
	public function setThickness($thickness) {
		$this->thickness = (int)$thickness;
	}
	
	/**
	 * Return shape thickness
	 *
	 * @return int
	 */
	public function getThickness() {
		return $this->thickness;
	}
	
	/**
	 * Hide the shape
	 *
	 * @param bool $hide
	 */
	public function hide($hide) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show the shape
	 *
	 * @param bool $shape
	 */
	public function show($shape) {
		$this->hide = (bool)!$shape;
	}
	
	/**
	 * Is the line hidden ?
	 *
	 * @return bool
	 */
	public function isHidden() {
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
	public $x;

	/**
	 * Y coord
	 *
	 * @var float
	 */
	public $y;
	
	/**
	 * Build a new awpoint
	 *
	 * @param float $x
	 * @param float $y
	 */
	public function __construct($x, $y) {
	
		$this->setLocation($x, $y);
		
	}
	
	/**
	 * Change X value
	 *
	 * @param float $x
	 */
	public function setX($x) {
		$this->x = (float)$x;
	}
	
	/**
	 * Change Y value
	 *
	 * @param float $y
	 */
	public function setY($y) {
		$this->y = (float)$y;
	}
	
	/**
	 * Change point location
	 *
	 * @param float $x
	 * @param float $y
	 */
	public function setLocation($x, $y) {
		$this->setX($x);
		$this->setY($y);
	}
	
	/**
	 * Get point location
	 *
	 * @param array Point location
	 */
	public function getLocation() {
		return array($this->x, $this->y);
	}
	
	/**
	 * Get distance to another point
	 *
	 * @param awPoint $p A point
	 * @return float
	 */
	public function getDistance(awPoint $p) {
	
		return sqrt(pow($p->x - $this->x, 2) + pow($p->y - $this->y, 2));
	
	}
	
	/**
	 * Move the point to another location
	 *
	 * @param Point A Point with the new awlocation
	 */
	public function move($x, $y) {
	
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
	public $p1;

	/**
	 * Line second point
	 *
	 * @param Point
	 */
	public $p2;
	
	/**
	 * Build a new awline
	 *
	 * @param awPoint $p1 First point
	 * @param awPoint $p2 Second point
	 * @param int $type Style of line (default to solid)
	 * @param int $thickness Line thickness (default to 1)
	 */
	public function __construct($p1 = NULL, $p2 = NULL, $type = awLine::SOLID, $thickness = 1) {
	
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
	public static function build($x1, $y1, $x2, $y2) {
	
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
	public function setX($x1, $x2) {
		$this->p1->setX($x1);
		$this->p2->setX($x2);
	}
	
	/**
	 * Change Y values of the line
	 *
	 * @param int $y1 Begin value
	 * @param int $y2 End value
	 */
	public function setY($y1, $y2) {
		$this->p1->setY($y1);
		$this->p2->setY($y2);
	}
	
	/**
	 * Change line location
	 *
	 * @param awPoint $p1 First point
	 * @param awPoint $p2 Second point
	 */
	public function setLocation($p1, $p2) {
		if(is_null($p1) or $p1 instanceof awPoint) {
			$this->p1 = $p1;
		}
		if(is_null($p2) or $p2 instanceof awPoint) {
			$this->p2 = $p2;
		}
	}
	
	/**
	 * Get line location
	 *
	 * @param array Line location
	 */
	public function getLocation() {
		return array($this->p1, $this->p2);
	}
	
	/**
	 * Get the line size
	 *
	 * @return float
	 */
	public function getSize() {
	
		$square = pow($this->p2->x - $this->p1->x, 2) + pow($this->p2->y - $this->p1->y, 2);
		return sqrt($square);
	
	}
	
	/**
	 * Test if the line can be considered as a point
	 *
	 * @return bool
	 */
	public function isPoint() {
		return ($this->p1->x === $this->p2->x and $this->p1->y === $this->p2->y);
	}
	
	/**
	 * Test if the line is a vertical line
	 *
	 * @return bool
	 */
	public function isVertical() {
		return ($this->p1->x === $this->p2->x);
	}
	
	/**
	 * Test if the line is an horizontal line
	 *
	 * @return bool
	 */
	public function isHorizontal() {
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
	public function getAngle() {
	
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
	protected $points = array();

	/**
	 * Set a point in the polygon
	 *
	 * @param int $pos Point position
	 * @param awPoint $point
	 */
	public function set($pos, $point) {
		if(is_null($point) or $point instanceof awPoint) {
			$this->points[$pos] = $point;
		}
	}
	
	/**
	 * Add a point at the end of the polygon
	 *
	 * @param awPoint $point
	 */
	public function append($point) {
		if(is_null($point) or $point instanceof awPoint) {
			$this->points[] = $point;
		}
	}
	
	/**
	 * Get a point at a position in the polygon
	 *
	 * @param int $pos Point position
	 * @return Point
	 */
	public function get($pos) {
		return $this->points[$pos];
	}
	
	/**
	 * Count number of points in the polygon
	 *
	 * @return int
	 */
	public function count() {
		return count($this->points);
	}
	
	/**
	 * Returns all points in the polygon
	 *
	 * @return array
	 */
	public function all() {
		return $this->points;
	}

}

registerClass('Polygon');
?>