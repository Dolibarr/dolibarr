<?php

use \DebugBar\DataCollector\ExceptionsCollector;

/**
 * DolExceptionsCollector class
 */

class DolExceptionsCollector extends ExceptionsCollector
{
	/**
	 *	Return widget settings
	 *
	 *  @return    array       Array
	 */
	public function getWidgets()
	{
		global $langs;

		$title = $langs->transnoentities('Exceptions');

		return array(
			"$title" => array(
				'icon' => 'bug',
				'widget' => 'PhpDebugBar.Widgets.ExceptionsWidget',
				'map' => 'exceptions.exceptions',
				'default' => '[]'
			),
			"$title:badge" => array(
				'map' => 'exceptions.count',
				'default' => 'null'
			)
		);
	}
}
