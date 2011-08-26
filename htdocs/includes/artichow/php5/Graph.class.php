<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

// Artichow configuration
/* <php5> */
if(is_file(dirname(__FILE__)."/Artichow.cfg.php")) { // For PHP 4+5 version
	require_once dirname(__FILE__)."/Artichow.cfg.php";
}
/* </php5> */

// Some useful files
require_once ARTICHOW."/common.php";
require_once ARTICHOW."/Component.class.php";
require_once ARTICHOW."/Image.class.php";

require_once ARTICHOW."/inc/Grid.class.php";
require_once ARTICHOW."/inc/Tools.class.php";
require_once ARTICHOW."/inc/Drawer.class.php";
require_once ARTICHOW."/inc/Math.class.php";
require_once ARTICHOW."/inc/Tick.class.php";
require_once ARTICHOW."/inc/Axis.class.php";
require_once ARTICHOW."/inc/Legend.class.php";
require_once ARTICHOW."/inc/Mark.class.php";
require_once ARTICHOW."/inc/Label.class.php";
require_once ARTICHOW."/inc/Text.class.php";
require_once ARTICHOW."/inc/Color.class.php";
require_once ARTICHOW."/inc/Font.class.php";
require_once ARTICHOW."/inc/Gradient.class.php";

/**
 * A graph
 *
 * @package Artichow
 */
class awGraph extends awImage {

	/**
	 * Graph name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Cache timeout
	 *
	 * @var int
	 */
	protected $timeout = 0;

	/**
	 * Graph timing ?
	 *
	 * @var bool
	 */
	protected $timing;

	/**
	 * Components
	 *
	 * @var array
	 */
	private $components = array();

	/**
	 * Some labels to add to the component
	 *
	 * @var array
	 */
	protected $labels = array();

	/**
	 * Graph title
	 *
	 * @var Label
	 */
	public $title;

	/**
	 * Construct a new graph
	 *
	 * @param int $width Graph width
	 * @param int $height Graph height
	 * @param string $name Graph name for the cache (must be unique). Let it null to not use the cache.
	 * @param int $timeout Cache timeout (unix timestamp)
	 */
	public function __construct($width = NULL, $height = NULL, $name = NULL, $timeout = 0) {

	    // DOL_CHANGE LDR Fix to allow usage of other fonts
	    global $artichow_defaultfont;
	    $classfontname='aw'.str_replace('-','_',$artichow_defaultfont);

		parent::__construct();

		$this->setSize($width, $height);

		if(ARTICHOW_CACHE) {

			$this->name = $name;
			$this->timeout = $timeout;

			// Clean sometimes all the cache
			if(mt_rand(0, 5000) ===  0) {
				awGraph::cleanCache();
			}

			if($this->name !== NULL) {

				$file = ARTICHOW_CACHE_DIRECTORY."/".$this->name."-time";

				if(is_file($file)) {

					$type = awGraph::cleanGraphCache($file);

					if($type === NULL) {
						awGraph::deleteFromCache($this->name);
					} else {
						header("Content-Type: image/".$type);
						readfile(ARTICHOW_CACHE_DIRECTORY."/".$this->name."");
						exit;
					}

				}

			}

		}

		$this->title = new awLabel(
			NULL,
			new $classfontname(16),
			NULL,
			0
		);
		$this->title->setAlign(awLabel::CENTER, awLabel::BOTTOM);

	}

	/**
	 * Delete a graph from the cache
	 *
	 * @param string $name Graph name
	 * @return bool TRUE on success, FALSE on failure
	 */
	public static function deleteFromCache($name) {

		if(ARTICHOW_CACHE) {

			if(is_file(ARTICHOW_CACHE_DIRECTORY."/".$name."-time")) {
				unlink(ARTICHOW_CACHE_DIRECTORY."/".$name."");
				unlink(ARTICHOW_CACHE_DIRECTORY."/".$name."-time");
			}

		}

	}

	/**
	 * Delete all graphs from the cache
	 */
	public static function deleteAllCache() {

		if(ARTICHOW_CACHE) {

			$dp = opendir(ARTICHOW_CACHE_DIRECTORY);

			while($file = readdir($dp)) {
				if($file !== '.' and $file != '..') {
					unlink(ARTICHOW_CACHE_DIRECTORY."/".$file);
				}
			}

		}

	}

	/**
	 * Clean cache
	 */
	public static function cleanCache() {

		if(ARTICHOW_CACHE) {

			$glob = glob(ARTICHOW_CACHE_DIRECTORY."/*-time");

			foreach($glob as $file) {

				$type = awGraph::cleanGraphCache($file);

				if($type === NULL) {
					$name = preg_replace("/.*\/(.*)\-time/", "\\1", $file);
					awGraph::deleteFromCache($name);
				}

			}

		}

	}

	/**
	 * Enable/Disable Graph timing
	 *
	 * @param bool $timing
	 */
	public function setTiming($timing) {
		$this->timing = (bool)$timing;
	}

	/**
	 * Add a component to the graph
	 *
	 * @param awComponent $component
	 */
	public function add(awComponent $component) {

		$this->components[] = $component;

	}

	/**
	 * Add a label to the component
	 *
	 * @param awLabel $label
	 * @param int $x Position on X axis of the center of the text
	 * @param int $y Position on Y axis of the center of the text
	 */
	public function addLabel(awLabel $label, $x, $y) {

		$this->labels[] = array(
			$label, $x, $y
		);

	}

	/**
	 * Add a label to the component with aboslute position
	 *
	 * @param awLabel $label
	 * @param awPoint $point Text position
	 */
	public function addAbsLabel(awLabel $label, awPoint $point) {

		$this->labels[] = array(
			$label, $point
		);

	}

	/**
	 * Build the graph and draw component on it
	 * Image is sent to the user browser
	 *
	 * @param string $file Save the image in the specified file. Let it null to print image to screen.
	 */
	public function draw($file = NULL) {

		if($this->timing) {
			$time = microtimeFloat();
		}

		$this->create();

		foreach($this->components as $component) {

			$this->drawComponent($component);

		}

		$this->drawTitle();
		$this->drawShadow();
		$this->drawLabels();

		if($this->timing) {
			$this->drawTiming(microtimeFloat() - $time);
		}

		if(ARTICHOW_CACHE and $this->name !== NULL) {
			ob_start();
		}

		$this->send($file);

		if(ARTICHOW_CACHE and $this->name !== NULL) {

			$data = ob_get_contents();

			if(is_writable(ARTICHOW_CACHE_DIRECTORY) === FALSE) {
				trigger_error("Cache directory is not writable");
			}

			$file = ARTICHOW_CACHE_DIRECTORY."/".$this->name."";
			file_put_contents($file, $data);

			$file .= "-time";
			file_put_contents($file, $this->timeout."\n".$this->getFormat());

			ob_clean();

			echo $data;

		}

	}

	private function drawLabels() {

		$drawer = $this->getDrawer();

		foreach($this->labels as $array) {

			if(count($array) === 3) {

				// Text in relative position
				list($label, $x, $y) = $array;

				$point = new awPoint(
					$x * $this->width,
					$y * $this->height
				);

			} else {

				// Text in absolute position
				list($label, $point) = $array;

			}

			$label->draw($drawer, $point);

		}

	}

	private function drawTitle() {

		$drawer = $this->getDrawer();

		$point = new awPoint(
			$this->width / 2,
			10
		);

		$this->title->draw($drawer, $point);

	}

	private function drawTiming($time) {

		$drawer = $this->getDrawer();

		$label = new awLabel;
		$label->set("(".sprintf("%.3f", $time)." s)");
		$label->setAlign(awLabel::LEFT, awLabel::TOP);
		$label->border->show();
		$label->setPadding(1, 0, 0, 0);
		$label->setBackgroundColor(new awColor(230, 230, 230, 25));

		$label->draw($drawer, new awPoint(5, $drawer->height - 5));

	}

	private static function cleanGraphCache($file) {

		list(
			$time,
			$type
		) = explode("\n", file_get_contents($file));

		$time = (int)$time;

		if($time !== 0 and $time < time()) {
			return NULL;
		} else {
			return $type;
		}


	}

}

registerClass('Graph');

/*
 * To preserve PHP 4 compatibility
 */
function microtimeFloat() {
	list($usec, $sec) = explode(" ", microtime());
	return (float)$usec + (float)$sec;
}
?>
