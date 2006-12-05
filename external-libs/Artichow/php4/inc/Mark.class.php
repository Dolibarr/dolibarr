<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
/* <php4> */

define("MARK_CIRCLE", 1);
define("MARK_SQUARE", 2);
define("MARK_IMAGE", 3);
define("MARK_STAR", 4);
define("MARK_PAPERCLIP", 5);
define("MARK_BOOK", 6);

/* </php4> */
 
/**
 * Draw marks
 *
 * @package Artichow
 */
class awMark {

	/**
	 * Circle mark
	 *
	 * @var int
	 */
	

	/**
	 * Quare mark
	 *
	 * @var int
	 */
	

	/**
	 * Image mark
	 *
	 * @var int
	 */
	

	/**
	 * Star mark
	 *
	 * @var int
	 */
	

	/**
	 * Paperclip mark
	 *
	 * @var int
	 */
	

	/**
	 * Book mark
	 *
	 * @var int
	 */
	

	/**
	 * Must marks be hidden ?
	 *
	 * @var bool
	 */
	var $hide;

	/**
	 * Mark type
	 *
	 * @var int
	 */
	var $type;

	/**
	 * Mark size
	 *
	 * @var int
	 */
	var $size = 8;

	/**
	 * Fill mark
	 *
	 * @var Color, Gradient
	 */
	var $fill;

	/**
	 * Mark image
	 *
	 * @var Image
	 */
	var $image;

	/**
	 * To draw marks
	 *
	 * @var Drawer
	 */
	var $drawer;

	/**
	 * Move position from this vector
	 *
	 * @var Point
	 */
	var $move;
	
	/**
	 * Marks border
	 *
	 * @var Border
	 */
	var $border;

	/**
	 * Build the mark
	 */
	 function awMark() {
		
		$this->fill = new awColor(255, 0, 0, 0);
		$this->border = new awBorder;
		$this->border->hide();
		
		$this->move = new awPoint(0, 0);
	
	}
	
	/**
	 * Change mark position
	 *
	 * @param int $x Add this interval to X coord
	 * @param int $y Add this interval to Y coord
	 */
	 function move($x, $y) {
	
		$this->move = $this->move->move($x, $y);
	
	}
	
	/**
	 * Hide marks ?
	 *
	 * @param bool $hide TRUE to hide marks, FALSE otherwise
	 */
	 function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show marks ?
	 *
	 * @param bool $show
	 */
	 function show($show = TRUE) {
		$this->hide = (bool)!$show;
	}
	
	/**
	 * Change mark type
	 *
	 * @param int $size Size in pixels
	 */
	 function setSize($size) {
		$this->size = (int)$size;
	}
	
	/**
	 * Change mark type
	 *
	 * @param int $type New mark type
	 * @param int $size Mark size (can be NULL)
	 */
	 function setType($type, $size = NULL) {
		$this->type = (int)$type;
		if($size !== NULL) {
			$this->setSize($size);
		}
	}
	
	/**
	 * Fill the mark with a color or a gradient
	 *
	 * @param mixed $fill A color or a gradient
	 */
	 function setFill($fill) {
		if(is_a($fill, 'awColor') or is_a($fill, 'awGradient')) {
			$this->fill = $fill;
		}
	}
	
	/**
	 * Set an image
	 * Only for MARK_IMAGE type.
	 *
	 * @param Image An image
	 */
	 function setImage(&$image) {
		$this->image = $image;
	}
	
	/**
	 * Draw the mark
	 *
	 * @param $drawer
	 * @param $point Mark center
	 */
	 function draw($drawer, $point) {
	
		// Hide marks ?
		if($this->hide) {
			return;
		}
	
		// Check if we can print marks
		if($this->type !== NULL) {
		
			$this->drawer = $drawer;
			$realPoint = $this->move->move($point->x, $point->y);
		
			switch($this->type) {
			
				case MARK_CIRCLE :
					$this->drawCircle($realPoint);
					break;
			
				case MARK_SQUARE :
					$this->drawSquare($realPoint);
					break;
			
				case MARK_IMAGE :
					$this->drawImage($realPoint);
					break;
					
				case MARK_STAR :
					$this->changeType('star');
					$this->draw($drawer, $point);
					break;
					
				case MARK_PAPERCLIP :
					$this->changeType('paperclip');
					$this->draw($drawer, $point);
					break;
					
				case MARK_BOOK :
					$this->changeType('book');
					$this->draw($drawer, $point);
					break;
					
			}
		
		}
	
	}
	
	 function changeType($image) {
		$this->setType(MARK_IMAGE);
		$this->setImage(new awFileImage(ARTICHOW_IMAGE.DIRECTORY_SEPARATOR.$image.'.png'));
	}
	
	 function drawCircle($point) {
		
		$this->drawer->filledEllipse(
			$this->fill,
			$point,
			$this->size, $this->size
		);
	
		$this->border->ellipse(
			$this->drawer,
			$point,
			$this->size, $this->size
		);
	
	}
	
	 function drawSquare($point) {
	
		list($x, $y) = $point->getLocation();
	
		$x1 = (int)($x - $this->size / 2);
		$x2 = $x1 + $this->size;
		$y1 = (int)($y - $this->size / 2);
		$y2 = $y1 + $this->size;
		
		$this->border->rectangle($this->drawer, new awPoint($x1, $y1), new awPoint($x2, $y2));
		
		$size = $this->border->visible() ? 1 : 0;
		
		$this->drawer->filledRectangle(
			$this->fill,
			new awLine(
				new awPoint($x1 + $size, $y1 + $size),
				new awPoint($x2 - $size, $y2 - $size)
			)
		);
	
	}
	
	 function drawImage($point) {
		
		if(is_a($this->image, 'awImage')) {
		
			$width = $this->image->width;
			$height = $this->image->height;
	
			list($x, $y) = $point->getLocation();
		
			$x1 = (int)($x - $width / 2);
			$x2 = $x1 + $width;
			$y1 = (int)($y - $width / 2);
			$y2 = $y1 + $height;
		
			$this->border->rectangle($this->drawer, new awPoint($x1 - 1, $y1 - 1), new awPoint($x2 + 1, $y2 + 1));
			
			$this->drawer->copyImage($this->image, new awPoint($x1, $y1), new awPoint($x2, $y2));
			
		}
	
	}

}

registerClass('Mark');
?>