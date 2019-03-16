<?php

use \DebugBar\DataCollector\MemoryCollector;

/**
 * DolMemoryCollector class
 */

class DolMemoryCollector extends MemoryCollector
{
	/**
	 *	Return widget settings
	 *
	 */
	public function getWidgets()
	{
		global $langs;

		return array(
			"memory" => array(
				"icon" => "cogs",
				"tooltip" => $langs->transnoentities('MemoryUsage'),
				"map" => "memory.peak_usage_str",
				"default" => "'0B'"
			)
		);
	}
}