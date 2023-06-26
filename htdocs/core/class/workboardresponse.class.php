<?php
/* Copyright (C) 2015   Marcos García   <marcosgdf@gmail.com>
 * Copyright (C) 2018   Charlene Benke  <charlie@patas-monkey.com>
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
 *	\file       htdocs/core/class/workboardresponse.class.php
 *	\brief      Class that represents response of load_board functions
 */

class WorkboardResponse
{
	/**
	 * Unique key of the workboard
	 * @var string
	 */
	public $id;

	/**
	 * Image URL to represent the board item
	 * @var string
	 */
	public $img;

	/**
	 * Label of the warning
	 * @var string
	 */
	public $label;

	/**
	 * Short Label of the warning
	 * @var string
	 */
	public $labelShort;

	/**
	 * infoKey of the warning
	 * @var string
	 */
	public $infoKey = 'todo';

	/**
	 * URL to list to do items
	 * @var string
	 */
	public $url;

	/**
	 * (optional) If set, to do late items will link to this url
	 * @var string
	 */
	public $url_late;

	/**
	 * Delay time to mark an item as late. In number of days.
	 * @var double
	 */
	public $warning_delay;

	/**
	 * Number of items to do
	 * @var int
	 */
	public $nbtodo = 0;

	/**
	 * Number of to do items which are late
	 * @var int
	 */
	public $nbtodolate = 0;

	/**
	 * total price of items
	 * @var int
	 */
	public $total = 0;
}
