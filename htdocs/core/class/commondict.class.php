<?php
/* Copyright (C) 2023       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
	 * @var DoliDB		Database handler (result of a new DoliDB)
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
	 * @var ?int 		The environment ID when using a multicompany module
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
	 * @var ?int<0,1>	1 if the entry is active, 0 if not
	 */
	public $active;

	/**
	 * Empty function to prevent errors on call of this function. Must be overload if useful
	 *
	 * @param  string      		$sortorder    	Sort Order
	 * @param  string      		$sortfield    	Sort field
	 * @param  int         		$limit        	Limit the number of lines returned
	 * @param  int         		$offset       	Offset
	 * @param  string|string[]	$filter       	Filter as an Universal Search string.
	 * 											Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  string      		$filtermode   	No more used
	 * @return self[]|int<-1,-1>        	    int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		return -1;  // NOK because nothing done.
	}
}
