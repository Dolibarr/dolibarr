<?php

use \DebugBar\DataCollector\RequestDataCollector;

/**
 * DolRequestDataCollector class
 */

class DolRequestDataCollector extends RequestDataCollector
{
	/**
	 *	Return widget settings
	 *
	 */
	public function getWidgets()
	{
		global $langs;

		return array(
			$langs->transnoentities('Request') => array(
				"icon" => "tags",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => "request",
				"default" => "{}"
			)
		);
	}
}