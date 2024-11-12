<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/debugbar/class/DataCollector/DolConfigCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\ConfigCollector;

/**
 * DolConfigCollector class
 */

class DolConfigCollector extends ConfigCollector
{
	/**
	 *	Return widget settings
	 *
	 *  @return array<string,array{icon?:string,widget?:string,tooltip?:string,map:string,default:string}>      Array
	 */
	public function getWidgets()
	{
		global $langs;

		return array(
			$langs->transnoentities('Config') => array(
				"icon" => "gear",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => $this->getName(),
				"default" => "{}"
			)
		);
	}

	/**
	 *	Return collected data
	 *
	 *  @return    array{count:int,messages:string[]}   Array of collected data
	 */
	public function collect()
	{
		$this->data = $this->getConfig();

		return parent::collect();
	}

	/**
	 * Returns an array with config data
	 *
	 * @return array<string,array<string,string|mixed[]>>       Array of config
	 */
	protected function getConfig()
	{
		global $conf, $user;

		// Get constants
		$const = get_defined_constants(true);

		$config = array(
			'Dolibarr' => array(
				'const' => $const['user'],
				'$conf' => $this->objectToArray($conf),
				'$user' => $this->objectToArray($user)
			),
			'PHP' => array(
				'version'   => PHP_VERSION,
				'interface' => PHP_SAPI,
				'os'        => PHP_OS
			)
		);

		return $config;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Convert an object to array
	 *
	 * @param  mixed   $obj        Object
	 * @return array<string,mixed> Array
	 */
	protected function objectToArray($obj)
	{
		// phpcs:enable
		$arr = array();
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		foreach ($_arr as $key => $val) {
			$val = (is_array($val) || is_object($val)) ? $this->objectToArray($val) : $val;
			$arr[$key] = $val;
		}

		return $arr;
	}
}
