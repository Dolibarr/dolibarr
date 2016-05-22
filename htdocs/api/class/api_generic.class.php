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
use Luracast\Restler\Defaults;

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';


/**
 * API generic (login, status, ...)
 *
 */
class GenericApi extends DolibarrApi
{

	function __construct() {
		global $db;
		$this->db = $db;
	}

	/**
	 * Login
	 *
	 * Log user with username and password
	 *
	 * @param   string  $login			Username
	 * @param   string  $password		User password
	 * @param   int     $entity			User entity
     * @return  array   Response status and user token
     *
	 * @throws RestException
	 */
	public function login($login, $password, $entity = 0) {

	    global $conf, $dolibarr_main_authentication, $dolibarr_auto_user;
	    
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

		// Generate token for user
		$token = dol_hash($login.uniqid().$conf->global->MAIN_API_KEY,1);

		// We store API token into database
		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
		$sql.= " SET api_key = '".$this->db->escape($token)."'";
		$sql.= " WHERE login = '".$this->db->escape($login)."'";

		dol_syslog(get_class($this)."::login", LOG_DEBUG);	// No log
		$result = $this->db->query($sql);
		if (!$result)
		{
			throw new RestException(500, 'Error when updating user :'.$this->db->error_msg);
		}

		//return token
		return array(
			'success' => array(
				'code' => 200,
				'token' => $token,
				'message' => 'Welcome ' . $login
			)
		);
	}

	/**
     * Get status (Dolibarr version)
     *
	 * @access protected
	 * @class  DolibarrApiAccess {@requires admin}
	 */
	function status() {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
		return array(
			'success' => array(
				'code' => 200,
				'dolibarr_version' => DOL_VERSION
			)
		);
    }
}
