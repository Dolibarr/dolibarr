<?php

use \DebugBar\DataCollector\MessagesCollector;

/**
 * DolMessagesCollector class
 */

class DolMessagesCollector extends MessagesCollector
{
	/**
	 *	Return widget settings
	 *
	 */
	public function getWidgets()
	{
		global $langs;

		$title = $langs->transnoentities('Messages');
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
}