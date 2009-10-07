<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

/**
 * To handle text
 *
 * @package Artichow
 */
class awText {

	/**
	 * Your text
	 *
	 * @var string
	 */
	var $text;

	/**
	 * Text font
	 *
	 * @var Font
	 */
	var $font;

	/**
	 * Text angle
	 * Can be 0 or 90
	 *
	 * @var int
	 */
	var $angle;

	/**
	 * Text color
	 *
	 * @var Color
	 */
	var $color;

	/**
	 * Text background
	 *
	 * @var Color, Gradient
	 */
	var $background;

	/**
	 * Padding
	 *
	 * @var array Array for left, right, top and bottom paddings
	 */
	var $padding;

	/**
	 * Text border
	 *
	 * @var Border
	 */
	var $border;
	
	/**
	 * Build a new awtext
	 *
	 * @param string $text Your text
	 */
	 function awText($text, $font = NULL, $color = NULL, $angle = 0) {
	
		if(is_null($font)) {
			$font = new awFont2;
		}
		
		$this->setText($text);
		$this->setFont($font);
		
		// Set default color to black
		if($color === NULL) {
			$color = new awColor(0, 0, 0);
		}
		
		$this->setColor($color);
		$this->setAngle($angle);
		
		$this->border = new awBorder;
		$this->border->hide();
	
	}
	
	/**
	 * Get text
	 *
	 * @return string
	 */
	 function getText() {
		return $this->text;
	}
	
	/**
	 * Change text
	 *
	 * @param string $text New text
	 */
	 function setText($text) {
		$this->text = (string)$text;
	}

	/**
	 * Change text font
	 *
	 * @param Font
	 */
	 function setFont(&$font) {
		$this->font = $font;
	}
	
	/**
	 * Get text font
	 *
	 * @return int
	 */
	 function getFont() {
		return $this->font;
	}

	/**
	 * Change text angle
	 *
	 * @param int
	 */
	 function setAngle($angle) {
		$this->angle = (int)$angle;
	}
	
	/**
	 * Get text angle
	 *
	 * @return int
	 */
	 function getAngle() {
		return $this->angle;
	}

	/**
	 * Change text color
	 *
	 * @param Color
	 */
	 function setColor($color) {
		$this->color = $color;
	}
	
	/**
	 * Get text color
	 *
	 * @return Color
	 */
	 function getColor() {
		return $this->color;
	}
	
	/**
	 * Change text background color
	 *
	 * @param $color
	 */
	 function setBackgroundColor($color) {
		$this->background = $color;
	}
	
	/**
	 * Change text background gradient
	 *
	 * @param $gradient
	 */
	 function setBackgroundGradient($gradient) {
		$this->background = $gradient;
	}
	
	/**
	 * Get text background
	 *
	 * @return Color, Gradient
	 */
	 function getBackground() {
		return $this->background;
	}

	/**
	 * Change padding
	 *
	 * @param int $left Left padding
	 * @param int $right Right padding
	 * @param int $top Top padding
	 * @param int $bottom Bottom padding
	 */
	 function setPadding($left, $right, $top, $bottom) {
		$this->padding = array((int)$left, (int)$right, (int)$top, (int)$bottom);
	}
	
	/**
	 * Get current padding
	 *
	 * @return array
	 */
	 function getPadding() {
		return $this->padding;
	}

}

registerClass('Text');
?>
