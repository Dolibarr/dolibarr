<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

/**
 * Handle axis
 *
 * @package Artichow
 */
class awAxis {

	/**
	 * Axis line
	 *
	 * @var Line
	 */
	var $line;

	/**
	 * Axis labels
	 *
	 * @var Label
	 */
	var $label;
	
	/**
	 * Axis title
	 *
	 * @var Label
	 */
	var $title;
	
	/**
	 * Title position
	 *
	 * @var float
	 */
	var $titlePosition = 0.5;

	/**
	 * Labels number
	 *
	 * @var int
	 */
	var $labelNumber;
	
	/**
	 * Axis ticks
	 *
	 * @var array
	 */
	var $ticks = array();

	/**
	 * Axis and ticks color
	 *
	 * @var Color
	 */
	var $color;

	/**
	 * Axis left and right padding
	 *
	 * @var Side
	 */
	var $padding;

	/**
	 * Axis range
	 *
	 * @var array
	 */
	var $range;

	/**
	 * Hide axis
	 *
	 * @var bool
	 */
	var $hide = FALSE;

	/**
	 * Auto-scaling mode
	 *
	 * @var bool
	 */
	var $auto = TRUE;

	/**
	 * Axis range callback function
	 *
	 * @var array
	 */
	var $rangeCallback = array(
		'toValue' => 'toProportionalValue',
		'toPosition' => 'toProportionalPosition'
	);
	
	/**
	 * Build the axis
	 *
	 * @param float $min Begin of the range of the axis
	 * @param float $max End of the range of the axis
	 */
	 function awAxis($min = NULL, $max = NULL) {
	
		$this->line = new awVector(
			new awPoint(0, 0),
			new awPoint(0, 0)
		);
		
		$this->label = new awLabel;
		$this->padding = new awSide;
		
		$this->title = new awLabel(
			NULL,
			NULL,
			NULL,
			0
		);
		
		$this->setColor(new awBlack);
		
		if($min !== NULL and $max !== NULL) {
			$this->setRange($min, $max);
		}
	
	}
	
	/**
	 * Enable/disable auto-scaling mode
	 *
	 * @param bool $auto
	 */
	 function auto($auto) {
		$this->auto = (bool)$auto;
	}
	
	/**
	 * Get auto-scaling mode status
	 *
	 * @return bool
	 */
	 function isAuto() {
		return $this->auto;
	}
	
	/**
	 * Hide axis
	 *
	 * @param bool $hide
	 */
	 function hide($hide = TRUE) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Show axis
	 *
	 * @param bool $show
	 */
	 function show($show = TRUE) {
		$this->hide = !(bool)$show;
	}
	
	/**
	 * Return a tick object from its name
	 *
	 * @param string $name Tick object name
	 * @return Tick
	 */
	 function tick($name) {
		
		if(array_key_exists($name, $this->ticks)) {
			return $tick = &$this->ticks[$name];
		} else {
			return NULL;
		}
		
	}
	
	/**
	 * Add a tick object
	 *
	 * @param string $name Tick object name
	 * @param &$tick Tick object
	 */
	 function addTick($name, &$tick) {
		
		$this->ticks[$name] = &$tick;
		
	}
	
	/**
	 * Delete a tick object
	 *
	 * @param string $name Tick object name
	 */
	 function deleteTick($name) {
		if(array_key_exists($name, $this->ticks)) {
			unset($this->ticks[$name]);
		}
	}
	
	/**
	 * Hide all ticks
	 *
	 * @param bool $hide Hide or not ?
	 */
	 function hideTicks($hide = TRUE) {
		
		foreach($this->ticks as $key => $tick) {
			$this->ticks[$key]->hide($hide);
		}
		
	}
	
	/**
	 * Change ticks style
	 *
	 * @param int $style Ticks style
	 */
	 function setTickStyle($style) {
		
		foreach($this->ticks as $key => $tick) {
			$this->ticks[$key]->setStyle($style);
		}
		
	}
	
	/**
	 * Change ticks interval
	 *
	 * @param int $interval Ticks interval
	 */
	 function setTickInterval($interval) {
		
		foreach($this->ticks as $key => $tick) {
			$this->ticks[$key]->setInterval($interval);
		}
		
	}
	
	/**
	 * Change number of ticks relative to others ticks
	 *
	 * @param &$to Change number of theses ticks
	 * @param &$from Ticks reference
	 * @param float $number Number of ticks by the reference
	 */
	 function setNumberByTick($to, $from, $number) {
		$this->ticks[$to]->setNumberByTick($this->ticks[$from], $number);
	}
	
	/**
	 * Reverse ticks style
	 */
	 function reverseTickStyle() {
		
		foreach($this->ticks as $key => $tick) {
			if($this->ticks[$key]->getStyle() === TICK_IN) {
				$this->ticks[$key]->setStyle(TICK_OUT);
			} else if($this->ticks[$key]->getStyle() === TICK_OUT) {
				$this->ticks[$key]->setStyle(TICK_IN);
			}
		}
		
	}
	
	/**
	 * Change interval of labels
	 *
	 * @param int $interval Interval
	 */
	 function setLabelInterval($interval) {
		$this->auto(FALSE);
		$this->setTickInterval($interval);
		$this->label->setInterval($interval);
	}
	
	/**
	 * Change number of labels
	 *
	 * @param int $number Number of labels to display (can be NULL)
	 */
	 function setLabelNumber($number) {
		$this->auto(FALSE);
		$this->labelNumber = is_null($number) ? NULL : (int)$number;
	}
	
	/**
	 * Get number of labels
	 *
	 * @return int
	 */
	 function getLabelNumber() {
		return $this->labelNumber;
	}
	
	/**
	 * Change precision of labels
	 *
	 * @param int $precision Precision
	 */
	 function setLabelPrecision($precision) {
		$this->auto(FALSE);
		$function = 'axis'.time().'_'.(microtime() * 1000000);
		eval('function '.$function.'($value) {
			return sprintf("%.'.(int)$precision.'f", $value);
		}');
		$this->label->setCallbackFunction($function);
	}
	
	/**
	 * Change text of labels
	 *
	 * @param array $texts Some texts
	 */
	 function setLabelText($texts) {
		if(is_array($texts)) {
			$this->auto(FALSE);
			$function = 'axis'.time().'_'.(microtime() * 1000000);
			eval('function '.$function.'($value) {
				$texts = '.var_export($texts, TRUE).';
				return isset($texts[$value]) ? $texts[$value] : \'?\';
			}');
			$this->label->setCallbackFunction($function);
		}
	}

	/**
	 * Get the position of a point
	 *
	 * @param &$xAxis X axis
	 * @param &$yAxis Y axis
	 * @param $p Position of the point
	 * @return Point Position on the axis
	 */
	  function toPosition(&$xAxis, &$yAxis, $p) {

		$p1 = $xAxis->getPointFromValue($p->x);
		$p2 = $yAxis->getPointFromValue($p->y);
		
		return new awPoint(
			round($p1->x),
			round($p2->y)
		);
		
	}
	
	/**
	 * Change title alignment
	 *
	 * @param int $alignment New Alignment
	 */
	 function setTitleAlignment($alignment) {
	
		switch($alignment) {
		
			case LABEL_TOP :
				$this->setTitlePosition(1);
				$this->title->setAlign(NULL, LABEL_BOTTOM);
				break;
		
			case LABEL_BOTTOM :
				$this->setTitlePosition(0);
				$this->title->setAlign(NULL, LABEL_TOP);
				break;
		
			case LABEL_LEFT :
				$this->setTitlePosition(0);
				$this->title->setAlign(LABEL_LEFT);
				break;
		
			case LABEL_RIGHT :
				$this->setTitlePosition(1);
				$this->title->setAlign(LABEL_RIGHT);
				break;
		
		}
	
	}
	
	/**
	 * Change title position on the axis
	 *
	 * @param float $position A new awposition between 0 and 1
	 */
	 function setTitlePosition($position) {
		$this->titlePosition = (float)$position;
	}
	
	/**
	 * Change axis and axis title color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->color = $color;
		$this->title->setColor($color);
	}
	
	/**
	 * Change axis padding
	 *
	 * @param int $left Left padding in pixels
	 * @param int $right Right padding in pixels
	 */
	 function setPadding($left, $right) {
		$this->padding->set($left, $right);
	}
	
	/**
	 * Get axis padding
	 *
	 * @return Side
	 */
	 function getPadding() {
		return $this->padding;
	}
	
	/**
	 * Change axis range
	 *
	 * @param float $min
	 * @param float $max
	 */
	 function setRange($min, $max) {
		if($min !== NULL) {
			$this->range[0] = (float)$min;
		}
		if($max !== NULL) {
			$this->range[1] = (float)$max;
		}
	}
	
	/**
	 * Get axis range
	 *
	 * @return array
	 */
	 function getRange() {
		return $this->range;
	}
	
	/**
	 * Change axis range callback function
	 *
	 * @param string $toValue Transform a position between 0 and 1 to a value
	 * @param string $toPosition Transform a value to a position between 0 and 1 on the axis
	 */
	 function setRangeCallback($toValue, $toPosition) {
		$this->rangeCallback = array(
			'toValue' => (string)$toValue,
			'toPosition' => (string)$toPosition
		);
	}
	
	/**
	 * Center X values of the axis 
	 *
	 * @param &$axis An axis
	 * @param float $value The reference value on the axis
	 */
	 function setXCenter(&$axis, $value) {
		
		// Check vector angle
		if($this->line->isVertical() === FALSE) {
			trigger_error("setXCenter() can only be used on vertical axes", E_USER_ERROR);
		}
		
		$p = $axis->getPointFromValue($value);
		
		$this->line->setX(
			$p->x,
			$p->x
		);
		
	}
	
	/**
	 * Center Y values of the axis 
	 *
	 * @param &$axis An axis
	 * @param float $value The reference value on the axis
	 */
	 function setYCenter(&$axis, $value) {
		
		// Check vector angle
		if($this->line->isHorizontal() === FALSE) {
			trigger_error("setYCenter() can only be used on horizontal axes", E_USER_ERROR);
		}
		
		$p = $axis->getPointFromValue($value);
		
		$this->line->setY(
			$p->y,
			$p->y
		);
		
	}
	
	/**
	 * Get the distance between to values on the axis
	 *
	 * @param float $from The first value
	 * @param float $to The last value
	 * @return Point
	 */
	 function getDistance($from, $to) {
	
		$p1 = $this->getPointFromValue($from);
		$p2 = $this->getPointFromValue($to);
		
		return $p1->getDistance($p2);
	
	}
	
	/**
	 * Get a point on the axis from a value
	 *
	 * @param float $value
	 * @return Point
	 */
	 function getPointFromValue($value) {
	
		$callback = $this->rangeCallback['toPosition'];
		
		list($min, $max) = $this->range;
		$position = $callback($value, $min, $max);
		
		return $this->getPointFromPosition($position);
		
	}
	
	/**
	 * Get a point on the axis from a position
	 *
	 * @param float $position A position between 0 and 1
	 * @return Point
	 */
	 function getPointFromPosition($position) {
		
		$vector = $this->getVector();
		
		$angle = $vector->getAngle();
		$size = $vector->getSize();
		
		return $vector->p1->move(
			cos($angle) * $size * $position,
			-1 * sin($angle) * $size * $position
		);
		
	}
	
	/**
	 * Draw axis
	 *
	 * @param $drawer A drawer
	 */
	 function draw($drawer) {
	
		if($this->hide) {
			return;
		}
	
		$vector = $this->getVector();
		
		// Draw axis ticks
		$this->drawTicks($drawer, $vector);
	
		// Draw axis line
		$this->line($drawer);
		
		// Draw labels
		$this->drawLabels($drawer);
		
		// Draw axis title
		$p = $this->getPointFromPosition($this->titlePosition);
		$this->title->draw($drawer, $p);
	
	}
	
	 function autoScale() {
	
		if($this->isAuto() === FALSE) {
			return;
		}
	
		list($min, $max) = $this->getRange();
		$interval = $max - $min;
		
		if($interval > 0) {
			$partMax = $max / $interval;
			$partMin = $min / $interval;
		} else {
			$partMax = 0;
			$partMin = 0;
		}
		
		$difference = log($interval) / log(10);
		$difference = floor($difference);
		
		$pow = pow(10, $difference);
		
		if($pow > 0) {
			$intervalNormalize = $interval / $pow;
		} else {
			$intervalNormalize = 0;
		}
		
		if($difference <= 0) {
		
			$precision = $difference * -1 + 1;
		
			if($intervalNormalize > 2) {
				$precision--;
			}
			
		} else {
			$precision = 0;
		}
		
		if($min != 0 and $max != 0) {
			$precision++;
		}
		
		$this->setLabelPrecision($precision);
		
		if($intervalNormalize <= 1.5) {
			$intervalReal = 1.5;
			$labelNumber = 4;
		} else if($intervalNormalize <= 2) {
			$intervalReal = 2;
			$labelNumber = 5;
		} else if($intervalNormalize <= 3) {
			$intervalReal = 3;
			$labelNumber = 4;
		} else if($intervalNormalize <= 4) {
			$intervalReal = 4;
			$labelNumber = 5;
		} else if($intervalNormalize <= 5) {
			$intervalReal = 5;
			$labelNumber = 6;
		} else if($intervalNormalize <= 8) {
			$intervalReal = 8;
			$labelNumber = 5;
		} else if($intervalNormalize <= 10) {
			$intervalReal = 10;
			$labelNumber = 6;
		}
		
		if($min == 0) {
		
			$this->setRange(
				$min,
				$intervalReal * $pow
			);
			
		} else if($max == 0) {
		
			$this->setRange(
				$intervalReal * $pow * -1,
				0
			);
			
		}
		
		$this->setLabelNumber($labelNumber);
	
	}
	
	 function line($drawer) {
		
		$drawer->line(
			$this->color,
			$this->line
		);
		
	}
	
	 function drawTicks($drawer, &$vector) {
		
		foreach($this->ticks as $tick) {
			$tick->setColor($this->color);
			$tick->draw($drawer, $vector);
		}
		
	}
	
	 function drawLabels($drawer) {
		
		if($this->labelNumber !== NULL) {
			list($min, $max) = $this->range;
			$number = $this->labelNumber - 1;
			if($number < 1) {
				return;
			}
			$function = $this->rangeCallback['toValue'];
			$labels = array();
			for($i = 0; $i <= $number; $i++) {
				$labels[] = $function($i / $number, $min, $max);
			}
			$this->label->set($labels);
		}
		
		$labels = $this->label->count();
		
		for($i = 0; $i < $labels; $i++) {
		
			$p = $this->getPointFromValue($this->label->get($i));
			$this->label->draw($drawer, $p, $i);
		
		}
		
	}
	
	 function getVector() {
	
		$angle = $this->line->getAngle();
		
		// Compute paddings
		$vector = new awVector(
			$this->line->p1->move(
				cos($angle) * $this->padding->left,
				-1 * sin($angle) * $this->padding->left
			),
			$this->line->p2->move(
				-1 * cos($angle) * $this->padding->right,
				-1 * -1 * sin($angle) * $this->padding->right
			)
		);
		
		return $vector;
		
	}
	
	 function __clone() {
	
		$this->label = $this->label;
		$this->line = $this->line;
		$this->title = $this->title;
		
		foreach($this->ticks as $name => $tick) {
			$this->ticks[$name] = $tick;
		}
	
	}

}

registerClass('Axis');

function toProportionalValue($position, $min, $max) {
	return $min + ($max - $min) * $position;
}

function toProportionalPosition($value, $min, $max) {
	if($max - $min == 0) {
		return 0;
	}
	return ($value - $min) / ($max - $min);
}
?>