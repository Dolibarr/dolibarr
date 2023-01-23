<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023	Ferran Marcet			<fmarcet@2byte.es>
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
	public static $requires = array('user', 'external', 'admin');

	/**
	 * @var string $role		user role
	 */
	public static $role = 'user';

	/**
	 * @var User		$user	Loggued user
	 */
	public static $user = '';


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName
	/**
	 * Check access
	 *
	 * @return bool
	 *
	 * @throws RestException 401 Forbidden
	 * @throws RestException 503 Technical error
	 */
	public function __isAllowed()
	{
		// phpcs:enable
		global $conf, $db, $user;

		$login = '';
		$stored_key = '';

		$userClass = Defaults::$userIdentifierClass;

		/*foreach ($_SERVER as $key => $val)
		{
			dol_syslog($key.' - '.$val);
		}*/

		// api key can be provided in url with parameter api_key=xxx or ni header with header DOLAPIKEY:xxx
		$api_key = '';
		if (isset($_GET['api_key'])) {	// For backward compatibility
			// TODO Add option to disable use of api key on url. Return errors if used.
			$api_key = $_GET['api_key'];
		}
		if (isset($_GET['DOLAPIKEY'])) {
			// TODO Add option to disable use of api key on url. Return errors if used.
			$api_key = $_GET['DOLAPIKEY']; // With GET method
		}
		if (isset($_SERVER['HTTP_DOLAPIKEY'])) {         // Param DOLAPIKEY in header can be read with HTTP_DOLAPIKEY
			$api_key = $_SERVER['HTTP_DOLAPIKEY']; // With header method (recommanded)
		}

		if ($api_key) {
			$userentity = 0;

			$sql = "SELECT u.login, u.datec, u.api_key, ";
			$sql .= " u.tms as date_modification, u.entity";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " WHERE u.api_key = '".$this->db->escape($api_key)."'";
			// TODO Check if 2 users has same API key.

			$result = $this->db->query($sql);
			if ($result) {
				if ($this->db->num_rows($result)) {
					$obj = $this->db->fetch_object($result);
					$login = $obj->login;
					$stored_key = $obj->api_key;
					$userentity = $obj->entity;

					if (!defined("DOLENTITY") && $conf->entity != ($obj->entity ? $obj->entity : 1)) {		// If API was not forced with HTTP_DOLENTITY, and user is on another entity, so we reset entity to entity of user
						$conf->entity = ($obj->entity ? $obj->entity : 1);
						// We must also reload global conf to get params from the entity
						dol_syslog("Entity was not set on http header with HTTP_DOLAPIENTITY (recommanded for performance purpose), so we switch now on entity of user (".$conf->entity.") and we have to reload configuration.", LOG_WARNING);
						$conf->setValues($this->db);
					}
				}
			} else {
				throw new RestException(503, 'Error when fetching user api_key :'.$this->db->error_msg);
			}

			if ($stored_key != $api_key) {		// This should not happen since we did a search on api_key
				$userClass::setCacheIdentifier($api_key);
				return false;
			}

			if (!$login) {
				throw new RestException(503, 'Error when searching login user from api key');
			}
			$fuser = new User($this->db);
			$result = $fuser->fetch('', $login, '', 0, (empty($userentity) ? -1 : $conf->entity)); // If user is not entity 0, we search in working entity $conf->entity  (that may have been forced to a different value than user entity)
			if ($result <= 0) {
				throw new RestException(503, 'Error when fetching user :'.$fuser->error.' (conf->entity='.$conf->entity.')');
			}
			if ($fuser->statut == 0) {
				throw new RestException(503, 'Error when fetching user. This user has been locked or disabled');
			}

			$fuser->getrights();

			// Set the property $user to the $user of API
			static::$user = $fuser;

			// Set also the global variable $user to the $user of API
			$user = $fuser;

			if ($fuser->socid) {
				static::$role = 'external';
			}

			if ($fuser->admin) {
				static::$role = 'admin';
			}
		} else {
			throw new RestException(401, "Failed to login to API. No parameter 'HTTP_DOLAPIKEY' on HTTP header (and no parameter DOLAPIKEY in URL).");
		}

		$userClass::setCacheIdentifier(static::$role);
		Resources::$accessControlFunction = 'DolibarrApiAccess::verifyAccess';
		$requirefortest = static::$requires;
		if (!is_array($requirefortest)) {
			$requirefortest = explode(',', $requirefortest);
		}
		return in_array(static::$role, (array) $requirefortest) || static::$role == 'admin';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName
	/**
	 * @return string string to be used with WWW-Authenticate header
	 */
	public function __getWWWAuthenticateString()
	{
		// phpcs:enable
		return '';
	}

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
