<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/debugbar/class/DataCollector/DolQueryCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

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

		// Replace $db handler with new handler override by TraceableDB
		$db = new TraceableDB($db);

		$this->db = $db;
	}

	/**
	 * Return collected data
	 *
	 * @return array<string,mixed>  Array of collected data
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
				'duration_str' => round((float) $query['duration'] * 1000, 2),
				'memory' => $query['memory_usage'],
				'is_success' => $query['is_success'],
				'error_code' => $query['error_code'],
				'error_message' => $query['error_message']
			);
			$totalExecTime += $query['duration'];
			$totalMemoryUsage += $query['memory_usage'];
			if (!$query['is_success']) {
				$totalFailed += 1;
			}
		}

		return array(
			'nb_statements' => count($queries),
			'nb_failed_statements' => $totalFailed,
			'accumulated_duration' => $totalExecTime,
			'memory_usage' => $totalMemoryUsage,
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
	 *  @return array<string,array{icon?:string,widget?:string,tooltip?:string,map:string,default:string}>      Array
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
	 *  @return array<string,string>   Array
	 */
	public function getAssets()
	{
		return array(
			'css' => 'widgets/sqlqueries/widget.css',
			'js' => 'widgets/sqlqueries/widget.js'
		);
	}
}
