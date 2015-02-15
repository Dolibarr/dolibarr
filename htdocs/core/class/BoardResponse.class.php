<?php

/* Copyright (C) 2015   Marcos García   <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/BoardResponse.class.php
 *	\brief      Class that represents response of load_board functions
 */

class BoardResponse
{

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
	 * URL to list to do items
	 * @var string
	 */
	public $url;

	/**
	 * Delay time to mark an item as late
	 * @var int
	 */
	public $warning_delay;

	/**
	 * (optional) If set, to do late items will link to this url
	 * @var string
	 */
	public $lateurl;

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

}