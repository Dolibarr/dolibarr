<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
/**
 * Draw border
 *
 * @package Artichow
 */
class awBorder {

	/**
	 * Border color
	 *
	 * @var Color
	 */
	var $color;

	/**
	 * Hide border ?
	 *
	 * @var bool
	 */
	var $hide = FALSE;

	/**
	 * Border line style
	 *
	 * @var int
	 */
	var $style;
	
	/**
	 * Build the border
	 *
	 * @param $color Border color
	 * @param int $style Border style
	 */
	 function awBorder($color = NULL, $style = LINE_SOLID) {
	
		$this->setStyle($style);
		
		if(is_a($color, 'awColor')) {
			$this->setColor($color);
		} else {
			$this->setColor(new awBlack);
		}
		
	}
	
	/**
	 * Change border color
	 * This method automatically shows the border if it is hidden
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->color = $color;
		$this->show();
	}
	
	/**
	 * Change border style
	 *
	 * @param int $style
	 */
	 function setStyle($style) {
		$this->style = (int)$style;
	}
	
	/**
	 * Hide border ?
	 *
	 * @param bool $hide
	 */
	 function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show border ?
	 *
	 * @param bool $show
	 */
	 function show($show = TRUE) {
		$this->hide = (bool)!$show;
	}
	
	/**
	 * Is the border visible ?
	 *
	 * @return bool
	 */
	 function visible() {
		return !$this->hide;
	}
	
	/**
	 * Draw border as a rectangle
	 *
	 * @param $drawer
	 * @param $p1 Top-left corner
	 * @param $p2 Bottom-right corner
	 */
	 function rectangle($drawer, $p1, $p2) {
	
		// Border is hidden
		if($this->hide) {
			return;
		}
	
		$line = new awLine;
		$line->setStyle($this->style);
		$line->setLocation($p1, $p2);
		
		$drawer->rectangle($this->color, $line);
		
	}
	
	/**
	 * Draw border as an ellipse
	 *
	 * @param $drawer
	 * @param $center Ellipse center
	 * @param int $width Ellipse width
	 * @param int $height Ellipse height
	 */
	 function ellipse($drawer, $center, $width, $height) {
	
		// Border is hidden
		if($this->hide) {
			return;
		}
		
		switch($this->style) {
		
			case LINE_SOLID :
				$drawer->ellipse($this->color, $center, $width, $height);
				break;
			
			default :
				trigger_error("Dashed and dotted borders and not yet implemented on ellipses", E_USER_ERROR);
				break;
		
		}
		
		
	}

}

registerClass('Border');
?>