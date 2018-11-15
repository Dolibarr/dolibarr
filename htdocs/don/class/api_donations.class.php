<?php
/* Copyright (C) 2018   Thibault FOUCART    <support@ptibogxiv.net>
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

 use Luracast\Restler\RestException;

 require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

/**
 * API class for donations
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Donations extends DolibarrApi
{

    /**
     * Constructor
     */
    function __construct()
    {
        global $db,$conf,$langs;
        $this->db = $db;

//require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

 }
 
    /**
     * Get properties of an donation object
     *
     * Return an array with donation informations
     *
     * @param       int         $id         ID of donation
     * @return 	array|mixed data without useless information
	   *
     * @throws 	RestException
     */
    function get($id)
    {
        global $user,$conf;
       
        $user = DolibarrApiAccess::$user;
		return 'test';
	}

}


