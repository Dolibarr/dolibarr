<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
 
/* <php4> */

define("LABEL_LEFT", 1);
define("LABEL_RIGHT", 2);
define("LABEL_CENTER", 3);
define("LABEL_TOP", 4);
define("LABEL_BOTTOM", 5);
define("LABEL_MIDDLE", 6);

/* </php4> */
 
/**
 * Draw labels
 *
 * @package Artichow
 */
class awLabel {

	/**
	 * Label border
	 *
	 * @var int
	 */
	var $border;

	/**
	 * Label texts
	 *
	 * @var array
	 */
	var $texts;

	/**
	 * Text font
	 *
	 * @var int
	 */
	var $font;

	/**
	 * Text angle
	 *
	 * @var int
	 */
	var $angle = 0;

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
	 * Callback function
	 *
	 * @var string
	 */
	var $function;

	/**
	 * Padding
	 *
	 * @var int
	 */
	var $padding;

	/**
	 * Move position from this vector
	 *
	 * @var Point
	 */
	var $move;

	/**
	 * Label interval
	 *
	 * @var int
	 */
	var $interval = 1;

	/**
	 * Horizontal align
	 *
	 * @var int
	 */
	var $hAlign = LABEL_CENTER;

	/**
	 * Vertical align
	 *
	 * @var int
	 */
	var $vAlign = LABEL_MIDDLE;
	
	/**
	 * Hide all labels ?
	 *
	 * @var bool
	 */
	var $hide = FALSE;
	
	/**
	 * Keys to hide
	 *
	 * @var array
	 */
	var $hideKey = array();
	
	/**
	 * Values to hide
	 *
	 * @var array
	 */
	var $hideValue = array();
	
	/**
	 * Hide first label
	 *
	 * @var bool
	 */
	var $hideFirst = FALSE;
	
	/**
	 * Hide last label
	 *
	 * @var bool
	 */
	var $hideLast = FALSE;
	
	/**
	 * Build the label
	 *
	 * @param string $label First label
	 */
	 function awLabel($label = NULL, $font = NULL, $color = NULL, $angle = 0) {
	
		if(is_array($label)) {
			$this->set($label);
		} else if(is_string($label)) {
			$this->set(array($label));
		}
		
		if($font === NULL) {
			$font = new awFont2;
		}
		
		$this->setFont($font);
		$this->setAngle($angle);
		
		if(is_a($color, 'awColor')) {
			$this->setColor($color);
		} else {
			$this->setColor(new awColor(0, 0, 0));
		}
		
		$this->move = new awPoint(0, 0);
		
		$this->border = new awBorder;
		$this->border->hide();
		
	}
	
	/**
	 * Get an element of the label from its key
	 *
	 * @param int $key Element key
	 * @return string A value
	 */
	 function get($key) {
		return array_key_exists($key, $this->texts) ? $this->texts[$key] : NULL;
	}
	
	/**
	 * Get all labels
	 *
	 * @return array
	 */
	 function all() {
		return $this->texts;
	}
	
	/**
	 * Set one or several labels
	 *
	 * @param array $labels Array of string or a string
	 */
	 function set($labels) {
	
		if(is_string($labels)) {
			$this->texts = array($labels);
		} else if(is_array($labels)) {
			$this->texts = $labels;
		}
		
	}
	
	/**
	 * Count number of texts in the label
	 *
	 * @return int
	 */
	 function count() {
		return is_array($this->texts) ? count($this->texts) : 0;
	}
	
	/**
	 * Set a callback function for labels
	 *
	 * @param string $function
	 */
	 function setCallbackFunction($function) {
		$this->function = is_null($function) ? $function : (string)$function;
	}
	
	/**
	 * Return the callback function for labels
	 *
	 * @return string
	 */
	 function getCallbackFunction() {
		return $this->function;
	}
	
	/**
	 * Change labels format
	 *
	 * @param string $format New format (printf style: %.2f for example)
	 */
	 function setFormat($format) {
		$function = 'label'.time().'_'.(microtime() * 1000000);
		eval('function '.$function.'($value) {
			return sprintf("'.addcslashes($format, '"').'", $value);
		}');
		$this->setCallbackFunction($function);
	}
	
	/**
	 * Change font for label
	 *
	 * @param &$font New font
	 * @param $color Font color (can be NULL)
	 */
	 function setFont(&$font, $color = NULL) {
		$this->font = $font;
		if(is_a($color, 'awColor')) {
			$this->setColor($color);
		}
	}
	
	/**
	 * Change font angle
	 *
	 * @param int $angle New angle
	 */
	 function setAngle($angle) {
		$this->angle = (int)$angle;
	}
	
	/**
	 * Change font color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->color = $color;
	}
	
	/**
	 * Change text background
	 *
	 * @param mixed $background
	 */
	 function setBackground($background) {
		$this->background = $background;
	}
	
	/**
	 * Change text background color
	 *
	 * @param Color
	 */
	 function setBackgroundColor($color) {
		$this->background = $color;
	}
	
	/**
	 * Change text background gradient
	 *
	 * @param Gradient
	 */
	 function setBackgroundGradient($gradient) {
		$this->background = $gradient;
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
	 * Hide all labels ?
	 *
	 * @param bool $hide
	 */
	 function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show all labels ?
	 *
	 * @param bool $show
	 */
	 function show($show = TRUE) {
		$this->hide = (bool)!$show;
	}
	
	/**
	 * Hide a key
	 *
	 * @param int $key The key to hide
	 */
	 function hideKey($key) {
		$this->hideKey[$key] = TRUE;
	}
	
	/**
	 * Hide a value
	 *
	 * @param int $value The value to hide
	 */
	 function hideValue($value) {
		$this->hideValue[] = $value;
	}
	
	/**
	 * Hide first label
	 *
	 * @param bool $hide
	 */
	 function hideFirst($hide) {
		$this->hideFirst = (bool)$hide;
	}
	
	/**
	 * Hide last label
	 *
	 * @param bool $hide
	 */
	 function hideLast($hide) {
		$this->hideLast = (bool)$hide;
	}
	
	/**
	 * Set label interval
	 *
	 * @param int
	 */
	 function setInterval($interval) {
	
		$this->interval = (int)$interval;
		
	}
	
	/**
	 * Change label position
	 *
	 * @param int $x Add this interval to X coord
	 * @param int $y Add this interval to Y coord
	 */
	 function move($x, $y) {
	
		$this->move = $this->move->move($x, $y);
	
	}
	
	/**
	 * Change alignment
	 *
	 * @param int $h Horizontal alignment
	 * @param int $v Vertical alignment
	 */
	 function setAlign($h = NULL, $v = NULL) {
		if($h !== NULL) {
			$this->hAlign = (int)$h;
		}
		if($v !== NULL) {
			$this->vAlign = (int)$v;
		}
	}
	
	/**
	 * Get a text from the labele
	 *
	 * @param mixed $key Key in the array text
	 * @return Text
	 */
	 function getText($key) {
	
		if(is_array($this->texts) and array_key_exists($key, $this->texts)) {
		
			$value = $this->texts[$key];
			
			if(is_string($this->function)) {
				$value = call_user_func($this->function, $value);
			}
		
			$text = new awText($value);
			$text->setFont($this->font);
			$text->setAngle($this->angle);
			$text->setColor($this->color);
			
			if(is_a($this->background, 'awColor')) {
				$text->setBackgroundColor($this->background);
			} else if(is_a($this->background, 'awGradient')) {
				$text->setBackgroundGradient($this->background);
			}
			
			$text->border = $this->border;
			
			if($this->padding !== NULL) {
				call_user_func_array(array($text, 'setPadding'), $this->padding);
			}
			
			return $text;
			
		} else {
			return NULL;
		}
	
	}
	
	/**
	 * Get max width of all texts
	 *
	 * @param $drawer A drawer
	 * @return int
	 */
	 function getMaxWidth($drawer) {
	
		return $this->getMax($drawer, 'getTextWidth');
	
	}
	
	/**
	 * Get max height of all texts
	 *
	 * @param $drawer A drawer
	 * @return int
	 */
	 function getMaxHeight($drawer) {
	
		return $this->getMax($drawer, 'getTextHeight');
		
	}
	
	/**
	 * Draw the label
	 *
	 * @param $drawer
	 * @param $p Label center
	 * @param int $key Text position in the array of texts (default to zero)
	 */
	 function draw($drawer, $p, $key = 0) {
	
		if(($key % $this->interval) !== 0) {
			return;
		}
	
		// Hide all labels
		if($this->hide) {
			return;
		}
		
		// Key is hidden
		if(array_key_exists($key, $this->hideKey)) {
			return;
		}
		
		// Hide first label
		if($key === 0 and $this->hideFirst) {
			return;
		}
		
		// Hide last label
		if($key === count($this->texts) - 1 and $this->hideLast) {
			return;
		}
	
		$text = $this->getText($key);
		
		if($text !== NULL) {
		
			// Value must be hidden
			if(in_array($text->getText(), $this->hideValue)) {
				return;
			}
		
			$x = $p->x;
			$y = $p->y;
			
			// Get padding
			list($left, $right, $top, $bottom) = $text->getPadding();
			
			$font = $text->getFont();
			$width = $font->getTextWidth($text);
			$height = $font->getTextHeight($text);
			
			switch($this->hAlign) {
			
				case LABEL_RIGHT :
					$x -= ($width + $right);
					break;
			
				case LABEL_CENTER :
					$x -= ($width - $left + $right) / 2;
					break;
			
				case LABEL_LEFT :
					$x += $left;
					break;
			
			}
			
			switch($this->vAlign) {
			
				case LABEL_TOP :
					$y -= ($height + $bottom);
					break;
			
				case LABEL_MIDDLE :
					$y -= ($height - $top + $bottom) / 2;
					break;
			
				case LABEL_BOTTOM :
					$y += $top;
					break;
			
			}
		
			$drawer->string($text, $this->move->move($x, $y));
			
		}
		
	}
	
	 function getMax($drawer, $function) {
	
		$max = NULL;
	
		foreach($this->texts as $key => $text) {
		
			$text = $this->getText($key);
			$font = $text->getFont();
		
			if(is_null($max)) {
				$max = $font->{$function}($text);
			} else {
				$max = max($max, $font->{$function}($text));
			}
		
		}
		
		return $max;
		
	}

}

registerClass('Label');
?>