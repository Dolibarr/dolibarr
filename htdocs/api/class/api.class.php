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
use Luracast\Restler\RestException;


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
 *
 */
class DolibarrApiInit extends DolibarrApi {

	function __construct() {

	}
	
	/**
	 * Log user with login and password
	 * @todo : to finish!
	 * 
	 * @param string $login
	 * @param string $password
	 * @param int $entity
	 * @throws RestException
	 */
	public function login($login, $password, $entity = '') {

		// Authentication mode
		if (empty($dolibarr_main_authentication))
			$dolibarr_main_authentication = 'http,dolibarr';
		// Authentication mode: forceuser
		if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user))
			$dolibarr_auto_user = 'auto';
		// Set authmode
		$authmode = explode(',', $dolibarr_main_authentication);

		include_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
		$login = checkLoginPassEntity($login, $password, $entity, $authmode);
		if (empty($login))
		{
			throw new RestException(403, 'Access denied');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Welcome ' . $login
			)
		);
	}

	/**
	 * @access protected
	 * @class  DolibarrApiAccess {@requires admin}
	 */
	public function status() {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
		return array(
			'success' => array(
				'code' => 200,
				'dolibarr_version' => DOL_VERSION
			)
		);
	}

}
