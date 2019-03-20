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
	 *  @return void
	 */
	public function getWidgets()
	{
		global $langs;

		$langs->load("other");

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
