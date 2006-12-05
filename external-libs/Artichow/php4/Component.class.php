<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
require_once dirname(__FILE__)."/Graph.class.php";


/**
 * A graph can contain some groups of components
 *
 * @package Artichow
 */
 class awComponentGroup extends awComponent {

	/**
	 * Components of this group
	 *
	 * @var array
	 */
	var $components;
	
	/**
	 * Build the component group
	 */
	 function awComponentGroup() {
		parent::awComponent();
		$this->components = array();
	}

	/**
	 * Add a component to the group
	 *
	 * @param &$component A component
	 */
	 function add(&$component) {
		$this->components[] = $component;
	}

}

registerClass('ComponentGroup', TRUE);

 class awComponent {

	/**
	 * Component drawer
	 *
	 * @var Drawer
	 */
	var $drawer;

	/**
	 * Component width
	 *
	 * @var float
	 */
	var $width = 1.0;

	/**
	 * Component height
	 *
	 * @var float
	 */
	var $height = 1.0;

	/**
	 * Position X of the center the graph (from 0 to 1)
	 *
	 * @var float
	 */
	var $x = 0.5;

	/**
	 * Position Y of the center the graph (from 0 to 1)
	 *
	 * @var float
	 */
	var $y = 0.5;
	
	/**
	 * Component absolute width (in pixels)
	 *
	 *
	 * @var int
	 */
	var $w;
	
	/**
	 * Component absolute height (in pixels)
	 *
	 *
	 * @var int
	 */
	var $h;

	/**
	 * Left-top corner Y position
	 *
	 * @var float
	 */
	var $top;

	/**
	 * Left-top corner X position
	 *
	 * @var float
	 */
	var $left;
	
	/**
	 * Component background color
	 *
	 * @var Color
	 */
	var $background;
	
	/**
	 * Component padding
	 *
	 * @var Side
	 */
	var $padding;
	
	/**
	 * Component space
	 *
	 * @var Side
	 */
	var $space;
	
	/**
	 * Component title
	 *
	 * @var Label
	 */
	var $title;
	
	/**
	 * Adjust automatically the component ?
	 *
	 * @var bool
	 */
	var $auto = TRUE;
	
	/**
	 * Legend
	 *
	 * @var Legend
	 */
	var $legend;
	
	/**
	 * Build the component
	 */
	 function awComponent() {
		
		// Component legend
		$this->legend = new awLegend();
		
		$this->padding = new awSide(25, 25, 25, 25);
		$this->space = new awSide(0, 0, 0, 0);
		
		// Component title
		$this->title = new awLabel(
			NULL,
			new awTuffy(10),
			NULL,
			0
		);
		$this->title->setAlign(LABEL_CENTER, LABEL_TOP);
		
	}
	
	/**
	 * Adjust automatically the component ?
	 *
	 * @param bool $auto
	 */
	 function auto($auto) {
		$this->auto = (bool)$auto;
	}
	
	/**
	 * Change the size of the component
	 *
	 * @param int $width Component width (from 0 to 1)
	 * @param int $height Component height (from 0 to 1)
	 */
	 function setSize($width, $height) {
	
		$this->width = (float)$width;
		$this->height = (float)$height;
		
	}
	
	/**
	 * Change the absolute size of the component
	 *
	 * @param int $w Component width (in pixels)
	 * @param int $h Component height (in pixels)
	 */
	 function setAbsSize($w, $h) {
	
		$this->w = (int)$w;
		$this->h = (int)$h;
		
	}
	
	/**
	 * Change component background color
	 *
	 * @param $color (can be null)
	 */
	 function setBackgroundColor($color) {
		if($color === NULL or is_a($color, 'awColor')) {
			$this->background = $color;
		}
	}
	
	/**
	 * Change component background gradient
	 *
	 * @param $gradient (can be null)
	 */
	 function setBackgroundGradient($gradient) {
		if($gradient === NULL or is_a($gradient, 'awGradient')) {
			$this->background = $gradient;
		}
	}
	
	/**
	 * Change component background image
	 *
	 * @param &$image (can be null)
	 */
	 function setBackgroundImage($image) {
		if($image === NULL or is_a($image, 'awImage')) {
			$this->background = $image;
		}
	}
	
	/**
	 * Return the component background
	 *
	 * @return Color, Gradient
	 */
	 function getBackground() {
		return $this->background;
	}
	
	/**
	 * Change component padding
	 *
	 * @param int $left Padding in pixels (NULL to keep old value)
	 * @param int $right Padding in pixels (NULL to keep old value)
	 * @param int $top Padding in pixels (NULL to keep old value)
	 * @param int $bottom Padding in pixels (NULL to keep old value)
	 */
	 function setPadding($left = NULL, $right = NULL, $top = NULL, $bottom = NULL) {
		$this->padding->set($left, $right, $top, $bottom);
	}
	
	/**
	 * Change component space
	 *
	 * @param float $left Space in % (NULL to keep old value)
	 * @param float $right Space in % (NULL to keep old value)
	 * @param float $bottom Space in % (NULL to keep old value)
	 * @param float $top Space in % (NULL to keep old value)
	 */
	 function setSpace($left = NULL, $right = NULL, $bottom = NULL, $top = NULL) {
		$this->space->set($left, $right, $bottom, $top);
	}
	
	/**
	 * Change the absolute position of the component on the graph
	 *
	 * @var int $x Left-top corner X position
	 * @var int $y Left-top corner Y position
	 */
	 function setAbsPosition($left, $top) {
	
		$this->left = (int)$left;
		$this->top = (int)$top;
		
	}
	
	/**
	 * Set the center of the component
	 *
	 * @param int $x Position on X axis of the center of the component
	 * @param int $y Position on Y axis of the center of the component
	 */
	 function setCenter($x, $y) {
	
		$this->x = (float)$x;
		$this->y = (float)$y;
		
	}
	
	/**
	 * Get component coords with its padding
	 *
	 * @return array Coords of the component
	 */
	 function getPosition() {
		
		// Get component coords
		$x1 = $this->padding->left;
		$y1 = $this->padding->top;
		$x2 = $this->w - $this->padding->right;
		$y2 = $this->h - $this->padding->bottom;
	
		return array($x1, $y1, $x2, $y2);
	
	}
	
	/**
	 * Init the drawing of the component
	 */
	 function init($drawer) {

		// Set component background
		$background = $this->getBackground();
		
		if($background !== NULL) {
			
			$p1 = new awPoint(0, 0);
			$p2 = new awPoint($this->w - 1, $this->h - 1);
			
			if(is_a($background, 'awImage')) {
	
				$drawer->copyImage(
					$background,
					$p1,
					$p2
				);
				
			} else {
			
				$drawer->filledRectangle(
					$background,
					new awLine($p1, $p2)
				);
				
			}
			
		}
	}
	
	/**
	 * Finalize the drawing of the component
	 */
	 function finalize($drawer) {
		
		// Draw component title
		$point = new awPoint(
			$this->w / 2,
			$this->padding->top - 8
		);
		$this->title->draw($drawer, $point);
		
		// Draw legend
		$this->legend->draw($drawer);
		
	}
	
	/**
	 * Draw the grid around your component
	 *
	 * @param Drawer A drawer
	 * @return array Coords for the component
	 */
	  
	
	/**
	 * Draw the component on the graph
	 * Component should be drawed into specified coords
	 *
	 * @param Drawer A drawer
	 * @param int $x1
	 * @param int $y1
	 * @param int $x2
	 * @param int $y2
	 * @param bool $aliasing Use anti-aliasing to draw the component ?
	 */
	  
	
	/**
	 * Get space width in pixels
	 *
	 * @param int $width Component width
	 * @param int $height Component height
	 * @return array
	 */
	 function getSpace($width, $height) {
		
		$left = (int)($width * $this->space->left / 100);
		$right = (int)($width * $this->space->right / 100);
		$top = (int)($height * $this->space->top / 100);
		$bottom = (int)($height * $this->space->bottom / 100);
		
		return array($left, $right, $top, $bottom);
		
	}
	
}

registerClass('Component', TRUE);
?>
