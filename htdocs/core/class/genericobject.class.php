<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/genericobject.class.php
 *	\ingroup    core
 *	\brief      File of class of generic business class
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
<<<<<<< HEAD
 *	Class of a generic business object
=======
 *  Class of a generic business object
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */

class GenericObject extends CommonObject
{
<<<<<<< HEAD
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
	    $this->db=$db;
	}

}

=======
    /**
     * Constructor
     *
     * @param       DoliDB      $db     Database handler
     */
    public function __construct($db)
    {
        $this->db=$db;
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
