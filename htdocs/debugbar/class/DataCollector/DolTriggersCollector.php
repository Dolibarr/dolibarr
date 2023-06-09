<?php
/* Copyright (C) 2023	Frédéric France <frederic.france@netlogic.fr>
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
 *	\file       htdocs/debugbar/class/DataCollector/DolTriggersDataCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use \DebugBar\DataCollector\RequestDataCollector;

/**
 * DolRequestDataCollector class
 */

class DolTriggersCollector extends RequestDataCollector
{
	/**
	 * Collects the data from the collectors
	 *
	 * @return array
	 */
	public function collect()
	{
		$data = ['triggers' => []];
		if (empty($_SESSION['triggersHistory'])) {
			return $data;
		}
		$i = 0;
		foreach ($_SESSION['triggersHistory'] as $key => $triggerHistory) {
			$i++;
			$data['triggers']["[$i] {$triggerHistory['name']}"] = $triggerHistory;
			unset($_SESSION['triggersHistory'][$key]);

			// $data["[$key] {$triggerHistory['name']}"] = "{$triggerHistory['file']} (L{$triggerHistory['line']}). Contexts: " . implode(', ', $triggerHistory['contexts']);
		}
		$data['nb_of_triggers'] = count($data['triggers']);

		return $data;
	}

	/**
	 *	Return widget settings
	 *
	 *  @return string[][]
	 */
	public function getWidgets()
	{
		global $langs;

		$langs->load("other");

		return [
			$langs->transnoentities('Triggers') => [
				"icon" => "tags",
				"widget" => "PhpDebugBar.Widgets.TriggerListWidget",
				"map" => "triggers.triggers",
				"default" => "{}"
			],
			"{$langs->transnoentities('Triggers')}:badge" => [
				"map" => "triggers.nb_of_triggers",
				"default" => 0
			]
		];
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'triggers';
	}
}
