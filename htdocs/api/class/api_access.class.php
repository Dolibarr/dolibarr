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

use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\Resources;
use \Luracast\Restler\Defaults;
use \Luracast\Restler\RestException;


/**
 * Dolibarr API access class
 *
 */
class DolibarrApiAccess implements iAuthenticate
{
	const REALM = 'Restricted Dolibarr API';

	/**
	 * @var array $requires	role required by API method		user / external / admin
	 */
	public static $requires = array('user','external','admin');

	/**
	 * @var string $role		user role
	 */
    public static $role = 'user';

	/**
	 * @var User		$user	Loggued user
	 */
	public static $user = '';

    // @codingStandardsIgnoreStart

	/**
	 * Check access
	 *
	 * @return bool
	 * @throws RestException
	 */
	public function __isAllowed()
	{
		global $db;

		$stored_key = '';

		$userClass = Defaults::$userIdentifierClass;

		if (isset($_GET['api_key'])) {
			$sql = "SELECT u.login, u.datec, u.api_key, ";
			$sql.= " u.tms as date_modification, u.entity";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.api_key = '".$db->escape($_GET['api_key'])."'";

			$result = $db->query($sql);
			if ($result)
			{
				if ($db->num_rows($result))
				{
					$obj = $db->fetch_object($result);
					$login = $obj->login;
					$stored_key = $obj->api_key;
				}
			}
			else {
				throw new RestException(503, 'Error when fetching user api_key :'.$db->error_msg);
			}

			if ( $stored_key != $_GET['api_key']) {
				$userClass::setCacheIdentifier($_GET['api_key']);
				return false;
			}

			$fuser = new User($db);
			if(! $fuser->fetch('',$login)) {
				throw new RestException(503, 'Error when fetching user :'.$fuser->error);
			}
			$fuser->getrights();
			static::$user = $fuser;

			if($fuser->societe_id)
				static::$role = 'external';

			if($fuser->admin)
				static::$role = 'admin';
        }
		else
		{
			return false;
		}

        $userClass::setCacheIdentifier(static::$role);
        Resources::$accessControlFunction = 'DolibarrApiAccess::verifyAccess';
        return in_array(static::$role, (array) static::$requires) || static::$role == 'admin';
	}

	/**
	 * @return string string to be used with WWW-Authenticate header
	 * @example Basic
	 * @example Digest
	 * @example OAuth
	 */
	public function __getWWWAuthenticateString()
    {
        return '';
    }
    // @codingStandardsIgnoreEnd

	/**
	 * Verify access
	 *
	 * @param   array $m Properties of method
	 *
	 * @access private
	 * @return bool
	 */
    public static function verifyAccess(array $m)
    {
        $requires = isset($m['class']['DolibarrApiAccess']['properties']['requires'])
                ? $m['class']['DolibarrApiAccess']['properties']['requires']
                : false;


        return $requires
            ? static::$role == 'admin' || in_array(static::$role, (array) $requires)
            : true;

    }
}
