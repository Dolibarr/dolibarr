<?php

dol_include_once('/debugbar/class/autoloader.php');

use \DebugBar\DebugBar;
use \DebugBar\DataCollector\PhpInfoCollector;

dol_include_once('/debugbar/class/DataCollector/DolMessagesCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolRequestDataCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolConfigCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolTimeDataCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolMemoryCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolExceptionsCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolQueryCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolibarrCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolLogsCollector.php');

/**
 * DolibarrDebugBar class
 *
 * @see http://phpdebugbar.com/docs/base-collectors.html#base-collectors
 */

class DolibarrDebugBar extends DebugBar
{
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		global $conf;

		//$this->addCollector(new PhpInfoCollector());
		//$this->addCollector(new DolMessagesCollector());
		$this->addCollector(new DolRequestDataCollector());
		//$this->addCollector(new DolConfigCollector());      // Disabled for security purpose
		$this->addCollector(new DolTimeDataCollector());
		$this->addCollector(new DolMemoryCollector());
		//$this->addCollector(new DolExceptionsCollector());
		$this->addCollector(new DolQueryCollector());
		$this->addCollector(new DolibarrCollector());
		if (isModEnabled('syslog')) {
			$this->addCollector(new DolLogsCollector());
		}
	}

	/**
	 * Returns a JavascriptRenderer for this instance
	 *
	 * @return string      String content
	 */
	public function getRenderer()
	{
		$renderer = parent::getJavascriptRenderer(DOL_URL_ROOT.'/includes/maximebf/debugbar/src/DebugBar/Resources');
		//$renderer->disableVendor('jquery');
		$renderer->disableVendor('fontawesome');
		$renderer->disableVendor('highlightjs');
		return $renderer;
	}
}
