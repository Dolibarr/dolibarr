<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

/**
 * Draw your objects
 *
 * @package Artichow
 */
class awDrawer {

	/**
	 * A GD resource
	 *
	 * @var $resource
	 */
	public $resource;
	
	/**
	 * Image width
	 *
	 * @var int
	 */
	public $width;
	
	/**
	 * Image height
	 *
	 * @var int
	 */
	public $height;
	
	/**
	 * Drawer X position
	 *
	 * @var int
	 */
	public $x;
	
	/**
	 * Drawer Y position
	 *
	 * @var int
	 */
	public $y;
	
	private $w;
	private $h;

	/**
	 * Build your drawer
	 *
	 * @var resource $resource A GD resource
	 */
	public function __construct($resource) {
	
		$this->resource = $resource;
		
	}
	
	/**
	 * Change the image size
	 *
	 * @param int $width Image width
	 * @param int $height Image height
	 */
	public function setImageSize($width, $height) {
	
		$this->width = $width;
		$this->height = $height;
	
	}
	
	/**
	 * Inform the drawer of the position of your image
	 *
	 * @param float $x Position on X axis of the center of the component
	 * @param float $y Position on Y axis of the center of the component
	 */
	public function setPosition($x, $y) {
		
		// Calcul absolute position
		$this->x = round($x * $this->width - $this->w / 2);
		$this->y = round($y * $this->height - $this->h / 2);
	
	}
	
	/**
	 * Inform the drawer of the position of your image
	 * This method need absolutes values
	 * 
	 * @param int $x Left-top corner X position
	 * @param int $y Left-top corner Y position
	 */
	public function setAbsPosition($x, $y) {
		
		$this->x = $x;
		$this->y = $y;
	
	}
	
	/**
	 * Move the position of the image
	 *
	 * @param int $x Add this value to X axis
	 * @param int $y Add this value to Y axis
	 */
	public function movePosition($x, $y) {

		$this->x += (int)$x;
		$this->y += (int)$y;
	
	}
	
	/**
	 * Inform the drawer of the size of your image
	 * Height and width must be between 0 and 1.
	 *
	 * @param int $w Image width
	 * @param int $h Image height
	 * @return array Absolute width and height of the image
	 */
	public function setSize($w, $h) {
	
		// Calcul absolute size
		$this->w = round($w * $this->width);
		$this->h = round($h * $this->height);
		
		return $this->getSize();
	
	}
	
	/**
	 * Inform the drawer of the size of your image
	 * You can set absolute size with this method.
	 *
	 * @param int $w Image width
	 * @param int $h Image height
	 */
	public function setAbsSize($w, $h) {
	
		$this->w = $w;
		$this->h = $h;
		
		return $this->getSize();
	
	}
	
	/**
	 * Get the size of the component handled by the drawer
	 *
	 * @return array Absolute width and height of the component
	 */
	public function getSize() {
		
		return array($this->w, $this->h);
	
	}
	
	/**
	 * Draw an image here
	 *
	 * @param awImage $image Image
	 * @param int $p1 Image top-left point
	 * @param int $p2 Image bottom-right point
	 */
	public function copyImage(awImage $image, awPoint $p1, awPoint $p2) {
	
		list($x1, $y1) = $p1->getLocation();
		list($x2, $y2) = $p2->getLocation();
	
		$drawer = $image->getDrawer();
		imagecopy($this->resource, $drawer->resource, $this->x + $x1, $this->y + $y1, 0, 0, $x2 - $x1, $y2 - $y1);
	
	}
	
	/**
	 * Draw an image here
	 *
	 * @param awImage $image Image
	 * @param int $d1 Destination top-left position
	 * @param int $d2 Destination bottom-right position
	 * @param int $s1 Source top-left position
	 * @param int $s2 Source bottom-right position
	 * @param bool $resample Resample image ? (default to TRUE)
	 */
	public function copyResizeImage(awImage $image, awPoint $d1, awPoint $d2, awPoint $s1, awPoint $s2, $resample = TRUE) {
		
		if($resample) {
			$function = 'imagecopyresampled';
		} else {
			$function = 'imagecopyresized';
		}
		
		$drawer = $image->getDrawer();
	
		$function(
			$this->resource,
			$drawer->resource,
			$this->x + $d1->x, $this->y + $d1->y,
			$s1->x, $s1->y,
			$d2->x - $d1->x, $d2->y - $d1->y,
			$s2->x - $s1->x, $s2->y - $s1->y
		);
	
	}
	
	/**
	 * Draw a string
	 *
	 * @var awText $text Text to print
	 * @param awPoint $point Draw the text at this point
	 */
	public function string(awText $text, awPoint $point) {
		
		$font = $text->getFont();
		
		if($text->getBackground() !== NULL or $text->border->visible()) {
		
			list($left, $right, $top, $bottom) = $text->getPadding();
			
			$width = $font->getTextWidth($text);
			$height = $font->getTextHeight($text);
			
			$x1 = floor($point->x - $left);
			$y1 = floor($point->y - $top);
			$x2 = $x1 + $width + $left + $right;
			$y2 = $y1 + $height + $top + $bottom;
			
			$this->filledRectangle(
				$text->getBackground(),
				awLine::build($x1, $y1, $x2, $y2)
			);
			
			$text->border->rectangle(
				$this,
				new awPoint($x1 - 1, $y1 - 1),
				new awPoint($x2 + 1, $y2 + 1)
			);
			
		}
		
		$font->draw($this, $point, $text);
		
	}
	
	/**
	 * Draw a pixel
	 *
	 * @param awColor $color Pixel color
	 * @param awPoint $p
	 */
	public function point(awColor $color, awPoint $p) {
	
		if($p->isHidden() === FALSE) {
			$rgb = $color->getColor($this->resource);
			imagesetpixel($this->resource, $this->x + round($p->x), $this->y + round($p->y), $rgb);
		}
	
	}
	
	/**
	 * Draw a colored line
	 *
	 * @param awColor $color Line color
	 * @param awLine $line
	 * @param int $thickness Line tickness
	 */
	public function line(awColor $color, awLine $line) {
	
		if($line->thickness > 0 and $line->isHidden() === FALSE) {
	
			$rgb = $color->getColor($this->resource);
			$thickness = $line->thickness;
			
			list($p1, $p2) = $line->getLocation();
			
			$this->startThickness($thickness);
			
			switch($line->getStyle()) {
			
				case awLine::SOLID :
					imageline($this->resource, $this->x + round($p1->x), $this->y + round($p1->y), $this->x + round($p2->x), $this->y + round($p2->y), $rgb);
					break;
					
				case awLine::DOTTED :
					$size = sqrt(pow($p2->y - $p1->y, 2) + pow($p2->x - $p1->x, 2));
					$cos = ($p2->x - $p1->x) / $size;
					$sin = ($p2->y - $p1->y) / $size;
					for($i = 0; $i <= $size; $i += 2) {
						$p = new awPoint(
							round($i * $cos + $p1->x),
							round($i * $sin + $p1->y)
						);
						$this->point($color, $p);
					}
					break;
					
				case awLine::DASHED :
					$width = $p2->x - $p1->x;
					$height = $p2->y - $p1->y;
					$size = sqrt(pow($height, 2) + pow($width, 2));
					
					if($size == 0) {
						return;
					}
					
					$cos = $width / $size;
					$sin = $height / $size;
					
					for($i = 0; $i <= $size; $i += 6) {
						
						$t1 = new awPoint(
							round($i * $cos + $p1->x),
							round($i * $sin + $p1->y)
						);
						
						$function = ($height > 0) ? 'min' : 'max';
						$t2 = new awPoint(
							round(min(($i + 3) * $cos, $width) + $p1->x),
							round($function(($i + 3) * $sin, $height) + $p1->y)
						);
						
						$this->line($color, new awLine($t1, $t2));
						
					}
					break;
			
			}
			
			$this->stopThickness($thickness);
			
		}
		
	}
	
	/**
	 * Draw a color arc
	 
	 * @param awColor $color Arc color
	 * @param awPoint $center Point center
	 * @param int $width Ellipse width
	 * @param int $height Ellipse height
	 * @param int $from Start angle
	 * @param int $to End angle
	 */
	public function arc(awColor $color, awPoint $center, $width, $height, $from, $to) {
	
		imagefilledarc(
			$this->resource,
			$this->x + $center->x, $this->y + $center->y,
			$width, $height,
			$from, $to,
			$color->getColor($this->resource),
			IMG_ARC_EDGED | IMG_ARC_NOFILL
		);
	
	}
	
	/**
	 * Draw an arc with a background color
	 *
	 * @param awColor $color Arc background color
	 * @param awPoint $center Point center
	 * @param int $width Ellipse width
	 * @param int $height Ellipse height
	 * @param int $from Start angle
	 * @param int $to End angle
	 */
	public function filledArc(awColor $color, awPoint $center, $width, $height, $from, $to) {
	
		imagefilledarc(
			$this->resource,
			$this->x + $center->x, $this->y + $center->y,
			$width, $height,
			$from, $to,
			$color->getColor($this->resource),
			IMG_ARC_PIE
		);
	
	}
	
	/**
	 * Draw a colored ellipse
	 *
	 * @param awColor $color Ellipse color
	 * @param awPoint $center Ellipse center
	 * @param int $width Ellipse width
	 * @param int $height Ellipse height
	 */
	public function ellipse(awColor $color, awPoint $center, $width, $height) {
	
		list($x, $y) = $center->getLocation();
	
		$rgb = $color->getColor($this->resource);
		imageellipse(
			$this->resource,
			$this->x + $x,
			$this->y + $y,
			$width,
			$height,
			$rgb
		);
		
	}
	
	/**
	 * Draw an ellipse with a background
	 *
	 * @param mixed $background Background (can be a color or a gradient)
	 * @param awPoint $center Ellipse center
	 * @param int $width Ellipse width
	 * @param int $height Ellipse height
	 */
	public function filledEllipse($background, awPoint $center, $width, $height) {
	
		if($background instanceof awColor) {
	
			list($x, $y) = $center->getLocation();
		
			$rgb = $background->getColor($this->resource);
			
			imagefilledellipse(
				$this->resource,
				$this->x + $x,
				$this->y + $y,
				$width,
				$height,
				$rgb
			);
			
		} else if($background instanceof awGradient) {
	
			list($x, $y) = $center->getLocation();
			
			$x1 = $x - round($width / 2);
			$y1 = $y - round($height / 2);
			$x2 = $x1 + $width;
			$y2 = $y1 + $height;
		
			$gradientDrawer = new awGradientDrawer($this);
			$gradientDrawer->filledEllipse(
				$background,
				$x1, $y1,
				$x2, $y2
			);
		
		}
		
	}
	
	/**
	 * Draw a colored rectangle
	 *
	 * @param awColor $color Rectangle color
	 * @param awLine $line Rectangle diagonale
	 * @param awPoint $p2
	 */
	public function rectangle(awColor $color, awLine $line) {
	
		$p1 = $line->p1;
		$p2 = $line->p2;
		
		switch($line->getStyle()) {
		
			case awLine::SOLID :
				$thickness = $line->getThickness();
				$this->startThickness($thickness);
				$rgb = $color->getColor($this->resource);
				imagerectangle($this->resource, $this->x + $p1->x, $this->y + $p1->y, $this->x + $p2->x, $this->y + $p2->y, $rgb);
				$this->stopThickness($thickness);
				break;
			
			default :
				
				// Top side
				$line->setLocation(
					new awPoint($p1->x, $p1->y),
					new awPoint($p2->x, $p1->y)
				);
				$this->line($color, $line);
				
				// Right side
				$line->setLocation(
					new awPoint($p2->x, $p1->y),
					new awPoint($p2->x, $p2->y)
				);
				$this->line($color, $line);
				
				// Bottom side
				$line->setLocation(
					new awPoint($p1->x, $p2->y),
					new awPoint($p2->x, $p2->y)
				);
				$this->line($color, $line);
				
				// Left side
				$line->setLocation(
					new awPoint($p1->x, $p1->y),
					new awPoint($p1->x, $p2->y)
				);
				$this->line($color, $line);
			
				break;
		
		}
	
	}
	
	/**
	 * Draw a rectangle with a background
	 *
	 * @param mixed $background Background (can be a color or a gradient)
	 * @param awLine $line Rectangle diagonale
	 */
	public function filledRectangle($background, awLine $line) {
	
		$p1 = $line->p1;
		$p2 = $line->p2;
	
		if($background instanceof awColor) {
			$rgb = $background->getColor($this->resource);
			imagefilledrectangle($this->resource, $this->x + $p1->x, $this->y + $p1->y, $this->x + $p2->x, $this->y + $p2->y, $rgb);
		} else if($background instanceof awGradient) {
			$gradientDrawer = new awGradientDrawer($this);
			$gradientDrawer->filledRectangle($background, $p1, $p2);
		}
	
	}
	
	/**
	 * Draw a polygon
	 *
	 * @param awColor $color Polygon color
	 * @param Polygon A polygon
	 */
	public function polygon(awColor $color, awPolygon $polygon) {
		
		switch($polygon->getStyle()) {
		
			case awPolygon::SOLID :
				$thickness = $line->getThickness();
				$this->startThickness($thickness);
				$points = $this->getPolygonPoints($polygon);
				$rgb = $color->getColor($this->resource);
				imagepolygon($this->resource, $points, $polygon->count(), $rgb);
				$this->stopThickness($thickness);
				break;
				
			default :
			
				if($polygon->count() > 1) {
				
					$prev = $polygon->get(0);
					
					$line = new awLine;
					$line->setStyle($polygon->getStyle());
					$line->setThickness($polygon->getThickness());
					
					for($i = 1; $i < $polygon->count(); $i++) {
						$current = $polygon->get($i);
						$line->setLocation($prev, $current);
						$this->line($color, $line);
						$prev = $current;
					}
					
				}
		
		}
		
	}
	
	/**
	 * Draw a polygon with a background
	 *
	 * @param mixed $background Background (can be a color or a gradient)
	 * @param Polygon A polygon
	 */
	public function filledPolygon($background, awPolygon $polygon) {
		
		if($background instanceof awColor) {
			$points = $this->getPolygonPoints($polygon);
			$rgb = $background->getColor($this->resource);
			imagefilledpolygon($this->resource, $points, $polygon->count(), $rgb);
		} else if($background instanceof awGradient) {
			$gradientDrawer = new awGradientDrawer($this);
			$gradientDrawer->filledPolygon($background, $polygon);
		}
		
	}
	
	private function getPolygonPoints(awPolygon $polygon) {
		
		$points = array();
		
		foreach($polygon->all() as $point) {
			$points[] = $point->x + $this->x;
			$points[] = $point->y + $this->y;
		}
		
		return $points;
		
	}
	
	private function startThickness($thickness) {
		
		if($thickness > 1) {
		
			// Beurk :'(
			if(function_exists('imageantialias')) {
				imageantialias($this->resource, FALSE);
			}
			imagesetthickness($this->resource, $thickness);
			
		}
		
	}
	
	private function stopThickness($thickness) {
		
		if($thickness > 1) {
		
			if(function_exists('imageantialias')) {
				imageantialias($this->resource, TRUE);
			}
			imagesetthickness($this->resource, 1);
			
		}
		
	}
	

}

registerClass('Drawer');

/**
 * To your gradients
 *
 * @package Artichow
 */

class awGradientDrawer {

	/**
	 * A drawer
	 *
	 * @var Drawer
	 */
	protected $drawer;

	/**
	 * Build your GradientDrawer
	 *
	 * @var awDrawer $drawer 
	 */
	public function __construct(awDrawer $drawer) {
	
		$this->drawer = $drawer;
		
	}
	
	public function drawFilledFlatTriangle(awGradient $gradient, awPoint $a, awPoint $b, awPoint $c) {
	
		if($gradient->angle !== 0) {
			trigger_error("Flat triangles can only be used with 0 degree gradients", E_USER_ERROR);
		}
	
		// Look for right-angled triangle
		if($a->x !== $b->x and $b->x !== $c->x) {
			trigger_error("Not right-angled flat triangles are not supported yet", E_USER_ERROR);
		}
		
		if($a->x === $b->x) {
			$d = $a;
			$e = $c;
		} else {
			$d = $c;
			$e = $a;
		}
		
		$this->init($gradient, $b->y - $d->y);
	
		for($i = $c->y + 1; $i < $b->y; $i++) {
			
			$color = $this->color($i - $d->y);
			$pos = ($i - $d->y) / ($b->y - $d->y);
			
			$p1 = new awPoint($e->x, $i);
			$p2 = new awPoint(1 + floor($e->x - $pos * ($e->x - $d->x)), $i);
			
			$this->drawer->filledRectangle($color, new awLine($p1, $p2));
			
			$color->free();
			unset($color);
			
		}
	
	}
	
	public function filledRectangle(awGradient $gradient, awPoint $p1, awPoint $p2) {
	
		list($x1, $y1) = $p1->getLocation();
		list($x2, $y2) = $p2->getLocation();
	
		if($y1 < $y2) {
			$y1 ^= $y2 ^= $y1 ^= $y2;
		}
	
		if($x2 < $x1) {
			$x1 ^= $x2 ^= $x1 ^= $x2;
		}
		
		if($gradient instanceof awLinearGradient) {
			$this->rectangleLinearGradient($gradient, new awPoint($x1, $y1), new awPoint($x2, $y2));
		} else {
			trigger_error("This gradient is not supported by rectangles", E_USER_ERROR);
		}
	
	}
	
	public function filledPolygon(awGradient $gradient, awPolygon $polygon) {
	
		if($gradient instanceof awLinearGradient) {
			$this->polygonLinearGradient($gradient, $polygon);
		} else {
			trigger_error("This gradient is not supported by polygons", E_USER_ERROR);
		}
	
	}
	
	protected function rectangleLinearGradient(awLinearGradient $gradient, awPoint $p1, awPoint $p2) {
	
		list($x1, $y1) = $p1->getLocation();
		list($x2, $y2) = $p2->getLocation();
	
		if($y1 - $y2 > 0) {
		
			if($gradient->angle === 0) {
			
				$this->init($gradient, $y1 - $y2);
		
				for($i = $y2; $i <= $y1; $i++) {
				
					$color = $this->color($i - $y2);
					
					$p1 = new awPoint($x1, $i);
					$p2 = new awPoint($x2, $i);
			
					$this->drawer->filledRectangle($color, new awLine($p1, $p2));
					
					$color->free();
					unset($color);
					
				}
				
			} else if($gradient->angle === 90) {
			
				$this->init($gradient, $x2 - $x1);
		
				for($i = $x1; $i <= $x2; $i++) {
				
					$color = $this->color($i - $x1);
					
					$p1 = new awPoint($i, $y2);
					$p2 = new awPoint($i, $y1);
			
					$this->drawer->filledRectangle($color, new awLine($p1, $p2));
					
					$color->free();
					unset($color);
					
				}
				
			}
			
		}
	
	}
	
	public function filledEllipse(awGradient $gradient, $x1, $y1, $x2, $y2) {
	
		if($y1 < $y2) {
			$y1 ^= $y2 ^= $y1 ^= $y2;
		}
	
		if($x2 < $x1) {
			$x1 ^= $x2 ^= $x1 ^= $x2;
		}
		
		if($gradient instanceof awRadialGradient) {
			$this->ellipseRadialGradient($gradient, $x1, $y1, $x2, $y2);
		} else if($gradient instanceof awLinearGradient) {
			$this->ellipseLinearGradient($gradient, $x1, $y1, $x2, $y2);
		} else {
			trigger_error("This gradient is not supported by ellipses", E_USER_ERROR);
		}
	
	}
	
	protected function ellipseRadialGradient(awGradient $gradient, $x1, $y1, $x2, $y2) {
	
		if($y1 - $y2 > 0) {
	
			if($y1 - $y2 != $x2 - $x1) {
				trigger_error("Radial gradients are only implemented on circle, not ellipses");
			}
			
			$c = new awPoint($x1 + ($x2 - $x1) / 2, $y1 + ($y2 - $y1) / 2); 
			$r = ($x2 - $x1) / 2;
			$ok = array();
			
			// Init gradient
			$this->init($gradient, $r);
			
			for($i = 0; $i <= $r; $i += 0.45) {
			
				$p = ceil((2 * M_PI * $i));
				
				if($p > 0) {
					$interval = 360 / $p;
				} else {
					$interval = 360;
				}
				
				$color = $this->color($i);
				
				for($j = 0; $j < 360; $j += $interval) {
				
					$rad = ($j / 360) * (2 * M_PI);
					
					$x = round($i * cos($rad));
					$y = round($i * sin($rad));
					
					$l = sqrt($x * $x + $y * $y);
					
					if($l <= $r) {
					
						if(
							array_key_exists((int)$x, $ok) === FALSE or
							array_key_exists((int)$y, $ok[$x]) === FALSE
						) {
						
							// Print the point
							$this->drawer->point($color, new awPoint($c->x + $x, $c->y + $y));
							
							$ok[(int)$x][(int)$y] = TRUE;
						
						}
						
					}
				
				}
				
				$color->free();
				unset($color);
			
			}
		
		}
	
	}
	
	protected function ellipseLinearGradient(awGradient $gradient, $x1, $y1, $x2, $y2) {
	
		// Gauche->droite : 90°
	
		if($y1 - $y2 > 0) {
	
			if($y1 - $y2 != $x2 - $x1) {
				trigger_error("Linear gradients are only implemented on circle, not ellipses");
			}
			
			$r = ($x2 - $x1) / 2;
			
			// Init gradient
			$this->init($gradient, $x2 - $x1);
			
			for($i = -$r; $i <= $r; $i++) {
				
				$h = sin(acos($i / $r)) * $r;
				
				$color = $this->color($i + $r);
				
				if($gradient->angle === 90) {
				
					// Print the line
					$p1 = new awPoint(
						$x1 + $i + $r,
						round(max($y2 + $r - $h + 1, $y2))
					);
					
					$p2 = new awPoint(
						$x1 + $i + $r,
						round(min($y1 - $r + $h - 1, $y1))
					);
					
				} else {
				
					// Print the line
					$p1 = new awPoint(
						round(max($x1 + $r - $h + 1, $x1)),
						$y2 + $i + $r
					);
					
					$p2 = new awPoint(
						round(min($x2 - $r + $h - 1, $x2)),
						$y2 + $i + $r
					);
					
				}
				
				$this->drawer->filledRectangle($color, new awLine($p1, $p2));
				
				$color->free();
				unset($color);
			
			}
		
		}
	
	}
	
	protected function polygonLinearGradient(awLinearGradient $gradient, awPolygon $polygon) {
	
		$count = $polygon->count();
		
		if($count >= 3) {
		
			$left = $polygon->get(0);
			$right = $polygon->get($count - 1);
			
			if($gradient->angle === 0) {
			
				// Get polygon maximum and minimum
				$offset = $polygon->get(0);
				$max = $min = $offset->y;
				for($i = 1; $i < $count - 1; $i++) {
					$offset = $polygon->get($i);
					$max = max($max, $offset->y);
					$min = min($min, $offset->y);
				}
				
				$this->init($gradient, $max - $min);
			
				$prev = $polygon->get(1);
				
				$sum = 0;
			
				for($i = 2; $i < $count - 1; $i++) {
				
					$current = $polygon->get($i);
					
					$interval = 1;
					
					if($i !== $count - 2) {
						$current->x -= $interval;
					}
					
					if($current->x - $prev->x > 0) {
				
						// Draw rectangle
						$x1 = $prev->x;
						$x2 = $current->x;
						$y1 = max($prev->y, $current->y);
						$y2 = $left->y;
						
						$gradient = new awLinearGradient(
							$this->color($max - $min - ($y2 - $y1)),
							$this->color($max - $min),
							0
						);
						
						if($y1 > $y2) {
							$y2 = $y1;
						}
						
						$this->drawer->filledRectangle(
							$gradient,
							awLine::build($x1, $y1, $x2, $y2)
						);
						
						$top = ($prev->y < $current->y) ? $current : $prev;
						$bottom = ($prev->y >= $current->y) ? $current : $prev;
						
						$gradient = new awLinearGradient(
							$this->color($bottom->y - $min),
							$this->color($max - $min - ($y2 - $y1)),
							0
						);
						
	
						$gradientDrawer = new awGradientDrawer($this->drawer);
						$gradientDrawer->drawFilledFlatTriangle(
							$gradient,
							new awPoint($prev->x, min($prev->y, $current->y)),
							$top,
							new awPoint($current->x, min($prev->y, $current->y))
						);
						unset($gradientDrawer);
						
						$sum += $current->x - $prev->x;
						
					}
					
					$prev = $current;
					$prev->x += $interval;
				
				}
			
			} else if($gradient->angle === 90) {
				
				$width = $right->x - $left->x;
				$this->init($gradient, $width);
				
				$pos = 1;
				$next = $polygon->get($pos++);
				
				$this->next($polygon, $pos, $prev, $next);
			
				for($i = 0; $i <= $width; $i++) {
				
					$x = $left->x + $i;
					
					$y1 = round($prev->y + ($next->y - $prev->y) * (($i + $left->x - $prev->x) / ($next->x - $prev->x)));
					$y2 = $left->y;
				
					// Draw line
					$color = $this->color($i);
					// YaPB : PHP does not handle alpha on lines
					$this->drawer->filledRectangle($color, awLine::build($x, $y1, $x, $y2));
					$color->free();
					unset($color);
					
					// Jump to next point
					if($next->x == $i + $left->x) {
					
						$this->next($polygon, $pos, $prev, $next);
						
					}
				
				}
	
			}
			
		}
	
	}
	
	private function next($polygon, &$pos, &$prev, &$next) {
	
		do {
			$prev = $next;
			$next = $polygon->get($pos++);
		}
		while($next->x - $prev->x == 0 and $pos < $polygon->count());
		
	}
	
	/**
	 * Start colors
	 *
	 * @var int
	 */
	private $r1, $g1, $b1, $a1;
	
	/**
	 * Stop colors
	 *
	 * @var int
	 */
	private $r2, $g2, $b2, $a2;
	
	/**
	 * Gradient size in pixels
	 *
	 * @var int
	 */
	private $size;
	
	
	private function init(awGradient $gradient, $size) {
		
		list(
			$this->r1, $this->g1, $this->b1, $this->a1
		) = $gradient->from->rgba();
		
		list(
			$this->r2, $this->g2, $this->b2, $this->a2
		) = $gradient->to->rgba();
		
		$this->size = $size;
	}
	
	private function color($pos) {
	
		return new awColor(
			$this->getRed($pos),
			$this->getGreen($pos),
			$this->getBlue($pos),
			$this->getAlpha($pos)
		);
		
	}
	
	
	private function getRed($pos) {
		return (int)round($this->r1 + ($pos / $this->size) * ($this->r2 - $this->r1));
	}
	
	private function getGreen($pos) {
		return (int)round($this->g1 + ($pos / $this->size) * ($this->g2 - $this->g1));
	}
	
	private function getBlue($pos) {
		return (int)round($this->b1 + ($pos / $this->size) * ($this->b2 - $this->b1));
	}
	
	private function getAlpha($pos) {
		return (int)round(($this->a1 + ($pos / $this->size) * ($this->a2 - $this->a1)) / 127 * 100);
	}

}

registerClass('GradientDrawer');
?>
