<?php

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DebugBarException;

dol_include_once('/debugbar/class/TraceableDB.php');

/**
 * DolQueryCollector class
 */
class DolQueryCollector extends DataCollector implements Renderable, AssetProvider
{
	/**
	 * @var object Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$db = new TraceableDB($db);

		$this->db = $db;
	}

	/**
	 * Return collected data
	 *
	 * @return array  Array
	 */
	public function collect()
	{
		$queries = array();
		$totalExecTime = 0;
		$totalMemoryUsage = 0;
		$totalFailed = 0;
		foreach ($this->db->queries as $query) {
			$queries[] = array(
				'sql' => $query['sql'],
				'duration' => $query['duration'],
				'duration_str' => $this->formatDuration($query['duration']),
				'memory' => $query['memory_usage'],
				'memory_str' => $this->formatBytes($query['memory_usage']),
				'is_success' => $query['is_success'],
				'error_code' => $query['error_code'],
				'error_message' => $query['error_message']
			);
			$totalExecTime += $query['duration'];
			$totalMemoryUsage += $query['memory_usage'];
			if (! $query['is_success']) {
				$totalFailed += 1;
			}
		}

		return array(
			'nb_statements' => count($queries),
			'nb_failed_statements' => $totalFailed,
			'accumulated_duration' => $totalExecTime,
			'accumulated_duration_str' => $this->formatDuration($totalExecTime),
			'memory_usage' => $totalMemoryUsage,
			'memory_usage_str' => $this->formatBytes($totalMemoryUsage),
			'statements' => $queries
		);
	}

	/**
	 *	Return collector name
	 *
	 *  @return string  Name
	 */
	public function getName()
	{
		return 'query';
	}

	/**
	 *	Return widget settings
	 *
	 *  @return array      Array
	 */
	public function getWidgets()
	{
		global $langs;

		$title = $langs->transnoentities('Database');

		return array(
			"$title" => array(
				"icon" => "arrow-right",
				"widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
				"map" => "query",
				"default" => "[]"
			),
			"$title:badge" => array(
				"map" => "query.nb_statements",
				"default" => 0
			)
		);
	}

	/**
	 *	Return assets
	 *
	 *  @return array   Array
	 */
	public function getAssets()
	{
		return array(
			'css' => 'widgets/sqlqueries/widget.css',
			'js' => 'widgets/sqlqueries/widget.js'
		);
	}
}
