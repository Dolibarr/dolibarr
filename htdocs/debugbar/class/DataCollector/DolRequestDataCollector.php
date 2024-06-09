<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *	\file       htdocs/debugbar/class/DataCollector/DolRequestDataCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\RequestDataCollector;

/**
 * DolRequestDataCollector class
 */

class DolRequestDataCollector extends RequestDataCollector
{
	/**
	 * Collects the data from the collectors
	 *
	 * @return array	Array of collected data
	 */
	public function collect()
	{
		$vars = array('_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER');
		$data = array();

		foreach ($vars as $var) {
			if (isset($GLOBALS[$var])) {
				$arrayofvalues = $GLOBALS[$var];

				if ($var == '_COOKIE') {
					foreach ($arrayofvalues as $key => $val) {
						if (preg_match('/^DOLSESSID_/', $key)) {
							$arrayofvalues[$key] = '*****hidden*****';
						}
					}
					//var_dump($arrayofvalues);
				}
				if ($var == '_SERVER') {
					foreach ($arrayofvalues as $key => $val) {
						if (preg_match('/^PHP_AUTH_PW/', $key)) {
							$arrayofvalues[$key] = '*****hidden*****';
						}
					}
				}
				$data["$".$var] = $this->getDataFormatter()->formatVar($arrayofvalues);
			}
		}

		return $data;
	}

	/**
	 *	Return widget settings
	 *
	 *  @return array	Array
	 */
	public function getWidgets()
	{
		global $langs;

		$langs->load("other");

		return array(
			$langs->transnoentities('Variables') => array(
				"icon" => "tags",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => "request",
				"default" => "{}"
			)
		);
	}
}
