<?php

use DebugBar\DataCollector\MessagesCollector;
use Psr\Log\LogLevel;
//use ReflectionClass;

/**
 * DolLogsCollector class
 */

class DolLogsCollector extends MessagesCollector
{
	/**
	 * @var string default logs file path
	 */
	protected $path;
	/**
	 * @var int number of lines to show
	 */
	protected $maxnboflines;

	/**
	 * Constructor
	 *
	 * @param string $path     Path
	 * @param string $name     Name
	 */
	public function __construct($path = null, $name = 'logs')
	{
		global $conf;

		parent::__construct($name);

		//$this->path = $path ?: $this->getLogsFile();
		$this->nboflines=0;
		$this->maxnboflines = empty($conf->global->DEBUGBAR_LOGS_LINES_NUMBER) ? 250 : $conf->global->DEBUGBAR_LOGS_LINES_NUMBER;   // High number slows seriously output
	}

	/**
	 *	Return widget settings
	 *
	 *  @return array  Array
	 */
	public function getWidgets()
	{
		global $langs;

		$title = $langs->transnoentities('Logs');
		$name = $this->getName();

		return array(
			"$title" => array(
				"icon" => "list-alt",
				"widget" => "PhpDebugBar.Widgets.MessagesWidget",
				"map" => "$name.messages",
				"default" => "[]"
			),
			"$title:badge" => array(
				"map" => "$name.count",
				"default" => "null"
			)
		);
	}

	/**
	 *	Return collected data
	 *
	 *  @return array  Array
	 */
	public function collect()
	{
		//$this->getStorageLogs($this->path);
		global $conf;
	    //var_dump($conf->logbuffer);

	    $log = array();
	    $log_levels = $this->getLevels();

	    foreach ($conf->logbuffer as $line) {
	        if ($this->nboflines >= $this->maxnboflines)
	        {
	            break;
	        }
	        foreach ($log_levels as $level_key => $level) {
	            if (strpos(strtolower($line), strtolower($level_key)) == 20) {
	                $this->nboflines++;
	                $this->addMessage($line, $level, false);
	            }
	        }
	    }

		return parent::collect();
	}

	/**
	 * Get the log levels from psr/log.
	 *
	 * @return array       Array of log level
	 */
	public function getLevels()
	{
		$class = new ReflectionClass(new LogLevel());
		$levels = $class->getConstants();
		$levels['ERR'] = 'error';

		return $levels;
	}
}
