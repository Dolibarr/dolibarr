<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
/* <php4> */

define("IMAGE_JPEG", 1);
define("IMAGE_PNG", 2);
define("IMAGE_GIF", 3);

/* </php4> */

/*
 * Check for GD2
 */
if(function_exists('imagecreatetruecolor') === FALSE) {
	trigger_error("You must compile PHP with GD2 support to use Artichow", E_USER_ERROR);
}

require_once ARTICHOW."/inc/Shadow.class.php";
require_once ARTICHOW."/inc/Border.class.php";
 
/**
 * An image for a graph
 *
 * @package Artichow
 */
class awImage {

	/**
	 * Graph width
	 *
	 * @var int
	 */
	public $width;

	/**
	 * Graph height
	 *
	 * @var int
	 */
	public $height;
	
	/**
	 * Use anti-aliasing ?
	 *
	 * @var bool
	 */
	protected $antiAliasing = FALSE;
	
	/**
	 * Image format
	 *
	 * @var int
	 */
	protected $format = awImage::PNG;
	
	/**
	 * Image background color
	 *
	 * @var Color
	 */
	protected $background;
	
	/**
	 * GD resource
	 *
	 * @var resource
	 */
	protected $resource;
	
	/**
	 * Image drawer
	 *
	 * @var Drawer
	 */
	protected $drawer;
	
	/**
	 * Shadow
	 *
	 * @var Shadow
	 */
	public $shadow;
	
	/**
	 * Image border
	 *
	 * @var Border
	 */
	public $border;
	
	/**
	 * Use JPEG for image
	 *
	 * @var int
	 */
	const JPEG = IMG_JPG;
	
	/**
	 * Use PNG for image
	 *
	 * @var int
	 */
	const PNG = IMG_PNG;
	
	/**
	 * Use GIF for image
	 *
	 * @var int
	 */
	const GIF = IMG_GIF;
	
	/**
	 * Build the image
	 */
	public function __construct() {
		
		$this->background = new awColor(255, 255, 255);
		$this->shadow = new awShadow(awShadow::RIGHT_BOTTOM);
		$this->border = new awBorder;
		
	}
	
	/**
	 * Get drawer of the image
	 *
	 * @param int $w Drawer width (from 0 to 1) (default to 1)
	 * @param int $h Drawer height (from 0 to 1) (default to 1)
	 * @param float $x Position on X axis of the center of the drawer (default to 0.5)
	 * @param float $y Position on Y axis of the center of the drawer (default to 0.5)
	 * @return Drawer
	 */
	public function getDrawer($w = 1, $h = 1, $x = 0.5, $y = 0.5) {
		$this->create();
		$this->drawer->setSize($w, $h);
		$this->drawer->setPosition($x, $y);
		return $this->drawer;
	}
	
	/**
	 * Change the image size
	 *
	 * @var int $width Image width
	 * @var int $height Image height
	 */
	public function setSize($width, $height) {
	
		if($width !== NULL) {
			$this->width = (int)$width;
		}
		if($height !== NULL) {
			$this->height = (int)$height;
		}
	
	}
	
	/**
	 * Change image background color
	 *
	 * @param awColor $color
	 */
	public function setBackgroundColor(awColor $color) {
		$this->background = $color;
	}
	
	/**
	 * Change image background gradient
	 *
	 * @param awGradient $gradient
	 */
	public function setBackgroundGradient(awGradient $gradient) {
		$this->background = $gradient;
	}
	
	/**
	 * Can we use anti-aliasing ?
	 *
	 * @var bool $bool
	 */
	public function setAntiAliasing($bool) {
		$this->antiAliasing = (bool)$bool;
	}
	
	/**
	 * Change image format
	 *
	 * @var int $format New image format
	 */
	public function setFormat($format) {
		if($format === awImage::JPEG or $format === awImage::PNG or $format === awImage::GIF) {
			$this->format = $format;
		}
	}
	
	/**
	 * Create a new awimage
	 */
	public function create() {
	
		if($this->resource === NULL) {
	
			// Create image
			
			$this->resource = imagecreatetruecolor($this->width, $this->height);
			if(!$this->resource) {
				trigger_error("Unable to create a graph", E_USER_ERROR);
			}
			
			imagealphablending($this->resource, TRUE);
			
			if($this->antiAliasing and function_exists('imageantialias')) {
				imageantialias($this->resource, TRUE);
			}
			
			$this->drawer = new awDrawer($this->resource);
			$this->drawer->setImageSize($this->width, $this->height);
			
			// Original color
			$this->drawer->filledRectangle(
				new awWhite,
				new awLine(
					new awPoint(0, 0),
					new awPoint($this->width, $this->height)
				)
			);
		
			$shadow = $this->shadow->getSpace();
			
			$p1 = new awPoint($shadow->left, $shadow->top);
			$p2 = new awPoint($this->width - $shadow->right - 1, $this->height - $shadow->bottom - 1);
		
			// Draw image background
			$this->drawer->filledRectangle($this->background, new awLine($p1, $p2));
			$this->background->free();
			
			// Draw image border
			$this->border->rectangle($this->drawer, $p1, $p2);
			
		}
		
	}
	
	/**
	 * Draw a component on the image
	 *
	 * @var awComponent $component A component
	 */
	public function drawComponent(awComponent $component) {
		
		$shadow = $this->shadow->getSpace(); // Image shadow
		$border = $this->border->visible() ? 1 : 0; // Image border size
	
		$drawer = clone $this->drawer;
		$drawer->setImageSize(
			$this->width - $shadow->left - $shadow->right - $border * 2,
			$this->height - $shadow->top - $shadow->bottom - $border * 2
		);
	
		// No absolute size specified
		if($component->w === NULL and $component->h === NULL) {
		
			list($width, $height) = $drawer->setSize($component->width, $component->height);
	
			// Set component size in pixels
			$component->setAbsSize($width, $height);
			
		} else {
		
			$drawer->setAbsSize($component->w, $component->h);
		
		}
		
		if($component->top !== NULL and $component->left !== NULL) {
			$drawer->setAbsPosition(
				$border + $shadow->left + $component->left,
				$border + $shadow->top + $component->top
			);
		} else {
			$drawer->setPosition($component->x, $component->y);
		}
		
		$drawer->movePosition($border + $shadow->left, $border + $shadow->top);
		
		list($x1, $y1, $x2, $y2) = $component->getPosition();
		
		$component->init($drawer);
		
		$component->drawComponent($drawer, $x1, $y1, $x2, $y2, $this->antiAliasing);
		$component->drawEnvelope($drawer, $x1, $y1, $x2, $y2);
		
		$component->finalize($drawer);
	
	}
	
	protected function drawShadow() {
	
		$drawer = $this->getDrawer();
		
		$this->shadow->draw(
			$drawer,
			new awPoint(0, 0),
			new awPoint($this->width, $this->height),
			awShadow::IN
		);
	
	}
	
	/**
	 * Send the image into a file or to the user browser
	 *
	 * @var string $file Save image into a file if you provide a file name
	 */
	public function send($file = NULL) {
	
		// Test if format is available
		if((imagetypes() & $this->format) === FALSE) {
			trigger_error("Format '".$this->format."' is not available on your system. Check that your PHP has been compiled with the good libraries.");
		}
	
		// Get some infos about this image
		
		switch($this->format) {
			case awImage::JPEG :
				$function = 'imagejpeg';
				break;
			case awImage::PNG :
				$function = 'imagepng';
				break;
			case awImage::GIF :
				$function = 'imagegif';
				break;
		}
		
		// Create image
		
		if($file !== NULL) {
		
			$function($this->resource, $file);
			
		} else {
	
			// Send headers to the browser
			if(headers_sent() === FALSE) {
				header("Content-type: image/".$this->getFormat());
			}
			
			$function($this->resource);
			
		}
	
	}
	
	protected function getFormat() {
		
		switch($this->format) {
			case awImage::JPEG :
				return 'jpeg';
			case awImage::PNG :
				return 'png';
			case awImage::GIF :
				return 'gif';
		}
		
	}

}

registerClass('Image');

 
/**
 * Load an image from a file
 *
 * @package Artichow
 */
class awFileImage extends awImage {

	/**
	 * Build a new awimage
	 *
	 * @param string $file Image file name
	 */
	public function __construct($file) {
	
		$image = @getimagesize($file);
		
		if($image and in_array($image[2], array(2, 3))) {
		
			$this->setSize($image[0], $image[1]);
			
			switch($image[2]) {
			
				case 2 :
					$this->resource = imagecreatefromjpeg($file);
					break;
			
				case 3 :
					$this->resource = imagecreatefrompng($file);
					break;
			
			}
		
			$this->drawer = new awDrawer($this->resource);
			$this->drawer->setImageSize($this->width, $this->height);
			
		} else {
			trigger_error("Artichow does not support this image (must be in PNG or JPEG)", E_USER_ERROR);
		}
	
	}

}

registerClass('FileImage');
?>
