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
 *	\file       htdocs/debugbar/class/DataCollector/DolTimeDataCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\TimeDataCollector;

/**
 * DolTimeDataCollector class
 */
class DolTimeDataCollector extends TimeDataCollector
{
	/**
	 *	Return widget settings
	 *
	 *  @return array  Array
	 */
	public function getWidgets()
	{
		global $langs;

		return array(
			"time" => array(
				"icon" => "clock-o",
				"tooltip" => $langs->transnoentities('RequestDuration'),
				"map" => "time.duration_str",
				"default" => "'0ms'"
			),
			$langs->transnoentities('Timeline') => array(
				"icon" => "tasks",
				"widget" => "PhpDebugBar.Widgets.TimelineWidget",
				"map" => "time",
				"default" => "{}"
			)
		);
	}
}
