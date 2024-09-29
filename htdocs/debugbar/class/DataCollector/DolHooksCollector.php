<?php
/* Copyright (C) 2024	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/debugbar/class/DataCollector/DolHooksCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\RequestDataCollector;

/**
 * DolRequestDataCollector class
 */
class DolHooksCollector extends RequestDataCollector
{
	/**
	 * Collects the data from the collectors
	 *
	 * @return array{nb_of_hooks:int,hooks:array<string,array{contexts:string}>}       Array of collected data
	 */
	public function collect()
	{
		/**
		 * @global $hookmanager HookManager
		 */
		global $hookmanager;

		$data = ['hooks' => [], 'nb_of_hooks' => 0];
		if (empty($hookmanager->hooksHistory)) {
			return $data;
		}
		$i = 0;
		foreach ($hookmanager->hooksHistory as $key => $hookHistory) {
			$i++;
			$hookHistory['contexts'] = implode(', ', $hookHistory['contexts']);
			$data['hooks']["[$i] {$hookHistory['name']}"] = $hookHistory;

			//            $data["[$key] {$hookHistory['name']}"] = "{$hookHistory['file']} (L{$hookHistory['line']}). Contexts: "
			//                . implode(', ', $hookHistory['contexts']);
		}
		$data['nb_of_hooks'] = count($data['hooks']);

		return $data;
	}

	/**
	 *	Return widget settings
	 *
	 *  @return		array<string,array{icon?:string,widget?:string,map:string,default:int|string}>  Array
	 */
	public function getWidgets()
	{
		global $langs;

		$langs->load("other");

		return [
			$langs->transnoentities('Hooks') => [
				"icon" => "tags",
				"widget" => "PhpDebugBar.Widgets.HookListWidget",
				"map" => "hooks.hooks",
				"default" => "{}"
			],
			"{$langs->transnoentities('Hooks')}:badge" => [
				"map" => "hooks.nb_of_hooks",
				"default" => 0
			]
		];
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'hooks';
	}
}
