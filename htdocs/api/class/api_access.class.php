<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

// Create the autoloader for Luracast
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/AutoLoader.php';
call_user_func(function () {
    $loader = Luracast\Restler\AutoLoader::instance();
    spl_autoload_register($loader);
    return $loader;
});

require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/iAuthenticate.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/iUseAuthentication.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/Resources.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/Defaults.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/RestException.php';
use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\iUseAuthentication;
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

		$login = '';
		$stored_key = '';

		$userClass = Defaults::$userIdentifierClass;

		/*foreach ($_SERVER as $key => $val)
		{
		    dol_syslog($key.' - '.$val);
		}*/
		
		// api key can be provided in url with parameter api_key=xxx or ni header with header DOLAPIKEY:xxx
		$api_key = '';
		if (isset($_GET['api_key'])) 
		{
		    // TODO Add option to disable use of api key on url. Return errors if used.
		    $api_key = $_GET['api_key'];                         // For backward compatibility
		}
		if (isset($_GET['DOLAPIKEY'])) 
		{
		    // TODO Add option to disable use of api key on url. Return errors if used.
		    $api_key = $_GET['DOLAPIKEY'];                     // With GET method
		}
		if (isset($_SERVER['HTTP_DOLAPIKEY'])) 
		{
		    $api_key = $_SERVER['HTTP_DOLAPIKEY'];     // With header method (recommanded)
		}
		
		if ($api_key) 
		{
			$sql = "SELECT u.login, u.datec, u.api_key, ";
			$sql.= " u.tms as date_modification, u.entity";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.api_key = '".$db->escape($api_key)."'";

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

			if ($stored_key != $api_key) {
				$userClass::setCacheIdentifier($api_key);
				return false;
			}

			if (! $login)
			{
			    throw new RestException(503, 'Error when searching logn user fro mapi key');
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
		    throw new RestException(401, "Failed to login to API. No parameter 'DOLAPIKEY' on HTTP header (neither in URL).");
		}

    $userClass::setCacheIdentifier(static::$role);
    Resources::$accessControlFunction = 'DolibarrApiAccess::verifyAccess';
    $requirefortest = static::$requires;
    if (! is_array($requirefortest)) $requirefortest=explode(',',$requirefortest);
    return in_array(static::$role, (array) $requirefortest) || static::$role == 'admin';
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
