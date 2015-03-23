<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/commonobjectline.class.php
 *  \ingroup    core
 *  \brief      File of the superclass of classes of lines of business objects (invoice, contract, PROPAL, commands, etc. ...)
 */


/**
 *  Parent class for class inheritance lines of business objects
 *  This class is useless for the moment so no inherit are done on it
 */
abstract class CommonObjectLine extends CommonObject
{
	/**
	 * Id of the line
	 * @var int
	 */
	public $id;

	/**
	 * Id of the line
	 * @var int
	 * @deprecated Try to use id property as possible
	 */
	public $rowid;

	// TODO

	// Currently we need function at end of file CommonObject for all object lines. Should find a way to avoid duplicate code.

	// For the moment we use the extends on CommonObject until PHP min is 5.4 so use Traits.
}

