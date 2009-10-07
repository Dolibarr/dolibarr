<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */
 
/* <php4> */

define("TICK_IN", 0);
define("TICK_OUT", 1);
define("TICK_IN_OUT", 2);

/* </php4> */

/**
 * Handle ticks
 *
 * @package Artichow
 */
class awTick {

	/**
	 * Ticks style
	 *
	 * @var int
	 */
	var $style = TICK_IN;

	/**
	 * Ticks size
	 *
	 * @var int
	 */
	var $size;

	/**
	 * Ticks color
	 *
	 * @var Color
	 */
	var $color;

	/**
	 * Ticks number
	 *
	 * @var int
	 */
	var $number;

	/**
	 * Ticks number by other tick
	 *
	 * @var array
	 */
	var $numberByTick;

	/**
	 * Ticks interval
	 *
	 * @var int
	 */
	var $interval = 1;

	/**
	 * Hide ticks
	 *
	 * @var bool
	 */
	var $hide = FALSE;

	/**
	 * Hide first tick
	 *
	 * @var bool
	 */
	var $hideFirst = FALSE;

	/**
	 * Hide last tick
	 *
	 * @var bool
	 */
	var $hideLast = FALSE;
	
	/**
	 * In mode
	 *
	 * @param int
	 */
	
	
	/**
	 * Out mode
	 *
	 * @param int
	 */
	
	
	/**
	 * In and out mode
	 *
	 * @param int
	 */
	
	
	/**
	 * Build the ticks
	 *
	 * @param int $number Number of ticks
	 * @param int $size Ticks size
	 */
	 function awTick($number, $size) {
		
		$this->setSize($size);
		$this->setNumber($number);
		$this->setColor(new awBlack);
		$this->style = TICK_IN;
	
	}
	
	/**
	 * Change ticks style
	 *
	 * @param int $style
	 */
	 function setStyle($style) {
		$this->style = (int)$style;
	}
	
	/**
	 * Get ticks style
	 *
	 * @return int
	 */
	 function getStyle() {
		return $this->style;
	}
	
	/**
	 * Change ticks color
	 *
	 * @param $color
	 */
	 function setColor($color) {
		$this->color = $color;
	}
	
	/**
	 * Change ticks size
	 *
	 * @param int $size
	 */
	 function setSize($size) {
		$this->size = (int)$size;
	}
	
	/**
	 * Change interval of ticks
	 *
	 * @param int $interval
	 */
	 function setInterval($interval) {
		$this->interval = (int)$interval;
	}
	
	/**
	 * Get interval between each tick
	 *
	 * @return int
	 */
	 function getInterval() {
		return $this->interval;
	}
	
	/**
	 * Change number of ticks
	 *
	 * @param int $number
	 */
	 function setNumber($number) {
		$this->number = (int)$number;
	}
	
	/**
	 * Get number of ticks
	 *
	 * @return int
	 */
	 function getNumber() {
		return $this->number;
	}
	
	/**
	 * Change number of ticks relative to others ticks
	 *
	 * @param &$tick Ticks reference
	 * @param int $number Number of ticks
	 */
	 function setNumberByTick(&$tick, $number) {
		
		$this->numberByTick = array(&$tick, (int)$number);
		
	}
	
	/**
	 * Hide ticks
	 *
	 * @param bool $hide
	 */
	 function hide($hide) {
		$this->hide = (bool)$hide;
	}
	
	/**
	 * Hide first tick
	 *
	 * @param bool $hide
	 */
	 function hideFirst($hide) {
		$this->hideFirst = (bool)$hide;
	}
	
	/**
	 * Hide last tick
	 *
	 * @param bool $hide
	 */
	 function hideLast($hide) {
		$this->hideLast = (bool)$hide;
	}
	
	/**
	 * Draw ticks on a vector
	 *
	 * @param $drawer A drawer
	 * @param &$vector A vector
	 */
	 function draw($drawer, &$vector) {
		
		if($this->numberByTick !== NULL) {
			list($tick, $number) = $this->numberByTick;
			$this->number = 1 + ($tick->getNumber() - 1) * ($number + 1);
			$this->interval = $tick->getInterval();
		}
		
		if($this->number < 2 or $this->hide) {
			return;
		}
		
		$angle = $vector->getAngle();
	//	echo "INIT:".$angle."<br>";
		switch($this->style) {
		
			case TICK_IN :
				$this->drawTicks($drawer, $vector, NULL, $angle + M_PI / 2);
				break;
		
			case TICK_OUT :
				$this->drawTicks($drawer, $vector, $angle + 3 * M_PI / 2, NULL);
				break;
		
			default :
				$this->drawTicks($drawer, $vector, $angle + M_PI / 2, $angle + 3 * M_PI / 2);
				break;
		
		}
	
	}
	
	 function drawTicks($drawer, &$vector, $from, $to) {
	
		// Draw last tick
		if($this->hideLast === FALSE) {
		
			//echo '<b>';
			if(($this->number - 1) % $this->interval === 0) {
				$this->drawTick($drawer, $vector->p2, $from, $to);
			}
			//echo '</b>';
			
		}
		
		$number = $this->number - 1;
		$size = $vector->getSize();
		
		// Get tick increment in pixels
		$inc = $size / $number;
		
		// Check if we must hide the first tick
		$start = $this->hideFirst ? $inc : 0;
		$stop = $inc * $number;
		
		$position = 0;
		
		for($i = $start; round($i, 6) < $stop; $i += $inc) {
		
			if($position % $this->interval === 0) {
				$p = $vector->p1->move(
					round($i * cos($vector->getAngle()), 6),
					round($i * sin($vector->getAngle() * -1), 6)
				);
				$this->drawTick($drawer, $p, $from, $to);
			}
			
			$position++;
			
		}
		//echo '<br><br>';
	}
	
	 function drawTick($drawer, $p, $from, $to) {
//	echo $this->size.':'.$angle.'|<b>'.cos($angle).'</b>/';
		// The round avoid some errors in the calcul
		// For example, 12.00000008575245 becomes 12
		$p1 = $p;
		$p2 = $p;
		
		if($from !== NULL) {
			$p1 = $p1->move(
				round($this->size * cos($from), 6),
				round($this->size * sin($from) * -1, 6)
			);
		}
		
		if($to !== NULL) {
			$p2 = $p2->move(
				round($this->size * cos($to), 6),
				round($this->size * sin($to) * -1, 6)
			);
		}
		//echo $p1->x.':'.$p2->x.'('.$p1->y.':'.$p2->y.')'.'/';
		$vector = new awVector(
			$p1, $p2
		);
		
		$drawer->line(
			$this->color,
			$vector
		);
		
	}

}

registerClass('Tick');
?>