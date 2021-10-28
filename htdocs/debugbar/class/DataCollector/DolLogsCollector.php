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

		$this->nboflines = 0;
		$this->maxnboflines = empty($conf->global->DEBUGBAR_LOGS_LINES_NUMBER) ? 250 : $conf->global->DEBUGBAR_LOGS_LINES_NUMBER; // High number slows seriously output

		$this->path = $path ?: $this->getLogsFile();
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
		global $conf;

		$uselogfile = $conf->global->DEBUGBAR_USE_LOGFILE;

		if ($uselogfile) {
			$this->getStorageLogs($this->path);
		} else {
			$log_levels = $this->getLevels();

			foreach ($conf->logbuffer as $line) {
				if ($this->nboflines >= $this->maxnboflines) {
					break;
				}
				foreach ($log_levels as $level_key => $level) {
					if (strpos(strtolower($line), strtolower($level_key)) == 20) {
						$this->nboflines++;
						$this->addMessage($line, $level, false);
					}
				}
			}
		}

		return parent::collect();
	}

	/**
	 * Get the path to the logs file
	 *
	 * @return string
	 */
	public function getLogsFile()
	{
		// default dolibarr log file
		$path = DOL_DATA_ROOT.'/dolibarr.log';
		return $path;
	}

	/**
	 * Get logs
	 *
	 * @param string $path     Path
	 * @return array
	 */
	public function getStorageLogs($path)
	{
		if (!file_exists($path)) {
			return;
		}

		// Load the latest lines
		$file = implode("", $this->tailFile($path, $this->maxnboflines));

		foreach ($this->getLogs($file) as $log) {
			$this->addMessage($log['line'], $log['level'], false);
		}
	}

	/**
	 * Get latest file lines
	 *
	 * @param string       $file       File
	 * @param int          $lines      Lines
	 * @return array       Array
	 */
	protected function tailFile($file, $lines)
	{
		$handle = fopen($file, "r");
		$linecounter = $lines;
		$pos = -2;
		$beginning = false;
		$text = array();
		while ($linecounter > 0) {
			$t = " ";
			while ($t != "\n") {
				if (fseek($handle, $pos, SEEK_END) == -1) {
					$beginning = true;
					break;
				}
				$t = fgetc($handle);
				$pos--;
			}
			$linecounter--;
			if ($beginning) {
				rewind($handle);
			}
			$text[$lines - $linecounter - 1] = fgets($handle);
			if ($beginning) {
				break;
			}
		}
		fclose($handle);
		return array_reverse($text);
	}

	/**
	 * Search a string for log entries
	 *
	 * @param  string  $file       File
	 * @return array               Lines of logs
	 */
	public function getLogs($file)
	{
		$pattern = "/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.*/";
		$log_levels = $this->getLevels();
		preg_match_all($pattern, $file, $matches);
		$log = array();
		foreach ($matches as $lines) {
			foreach ($lines as $line) {
				foreach ($log_levels as $level_key => $level) {
					if (strpos(strtolower($line), strtolower($level_key)) == 20) {
						$log[] = array('level' => $level, 'line' => $line);
					}
				}
			}
		}
		$log = array_reverse($log);
		return $log;
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
		$levels['WARN'] = 'warning';

		return $levels;
	}
}
