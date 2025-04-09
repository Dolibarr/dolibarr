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
 *	\file       htdocs/debugbar/class/DataCollector/DolexceptionsCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\ExceptionsCollector;

/**
 * DolExceptionsCollector class
 */

class DolExceptionsCollector extends ExceptionsCollector
{
	/**
	 *	Return widget settings
	 *
	 *  @return array<string,array{icon?:string,widget?:string,tooltip?:string,map:string,default:string}>      Array
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
