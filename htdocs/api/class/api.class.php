<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

use Luracast\Restler\Restler;

/**
 * Class for API
 */
class DolibarrApi {
    
    /**
     * @var DoliDb        $db Database object
     */
    static protected $db;
    
    /**
     * @var Restler     $r	Restler object
     */
    var $r;
            
    /**
     * Constructor
     * 
     * @var	DoliDB	$db		Database handler
     */
    function __construct($db) {
        $this->db = $db;
        $this->r = new Restler();
    }

    /**
     * Executed method when API is called without parameter
     *
     * Display a short message an return a http code 200
     * @return array
     */
    function index()
    {
        return array(
            'success' => array(
                'code' => 200,
                'message' => __class__.' is up and running!'
            )
        );
    }


    /**
     * Clean sensible object datas
     * @var object $object	Object to clean
     * @return	array	Array of cleaned object properties
     *
     * @todo use an array for properties to clean
     *
     */
    protected function cleanObjectDatas($object){

		unset($object->db);

		return array($object);
    }
    
}

/**
 * API init
 * This class exists to show 200 code when request url root /api/
 *
 */
class DolibarrApiInit extends DolibarrApi {

	function __construct() {

	}

}