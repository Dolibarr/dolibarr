<?php
/* Copyright (C) 2020		Tobias Sekan	<tobias.sekan@startmail.com>
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
 *    \file       htdocs/incoterms/class/incoterm.class.php
 *    \ingroup    Incoterms
 *    \brief      File of class of a Incoterm (International Commercial Term)
 */

class Incoterm
{
	/**
	 * Internal id of this Incoterm
	 *
	 * @var string
	 */
	public $id;

	/**
	 * International code of this Incoterm
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Description of this Incoterm
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Create a new Incoterm with the given values
	 *
	 * @param string $id			The internal id of this Incoterm
	 * @param string $code			The international code of this Incoterm
	 * @param string $description	The description of this Incoterm
	 */
	public function __construct($id, $code, $description)
	{
		$this->$id			= $id;
		$this->$code		= $code;
		$this->description	= $description;
	}
}
