<?php
/* Copyright (C) 2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/commondict.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other dictionary classes
 */


/**
 *	Parent class of all other dictionary classes
 */
abstract class CommonDict
{
	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var int 		The object identifier
	 */
	public $id;

	/**
	 * @var int 		The environment ID when using a multicompany module
	 */
	public $entity;

	/**
	 * @var string 		The code
	 */
	public $code;

	/**
	 * @var string 		The label
	 */
	public $label;

	/**
	 * @var int			Is the entry active
	 */
	public $active;
}
