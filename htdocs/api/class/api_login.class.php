<?php
/* Copyright (C) 2015   	Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * API that allows to log in with an user account.
 */
class Login
{
	/**
	 * @var DoliDB	Database handler
	 */
	public $db;

	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;

		//$conf->global->API_DISABLE_LOGIN_API = 1;
		if (getDolGlobalString('API_DISABLE_LOGIN_API')) {
			throw new RestException(403, "Error login APIs are disabled. You must get the token from backoffice to be able to use APIs");
		}
	}

	/**
	 * Login
	 *
	 * Request the API token for a couple username / password.
	 * WARNING: You should NEVER use this API, like you should never use the similar API that uses the POST method. This will expose your password.
	 * To use the APIs, you should instead set an API token to the user you want to allow to use API (This API token called DOLAPIKEY can be found/set on the user page) and use this token as credential for any API call.
	 * From the API explorer, you can enter directly the "DOLAPIKEY" into the field at the top right of the page to get access to any allowed APIs.
	 *
	 * @param   string  $login			User login
	 * @param   string  $password		User password
	 * @param   string  $entity			Entity (when multicompany module is used). '' means 1=first company.
	 * @param   int     $reset          Reset token (0=get current token, 1=ask a new token and canceled old token. This means access using current existing API token of user will fails: new token will be required for new access)
	 * @return  array                   Response status and user token
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 System error
	 *
	 * @url GET /
	 */
	public function loginUnsecured($login, $password, $entity = '', $reset = 0)
	{
		return $this->index($login, $password, $entity, $reset);
	}

	/**
	 * Login
	 *
	 * Request the API token for a couple username / password.
	 * WARNING: You should NEVER use this API, like you should never use the similar API that uses the POST method. This will expose your password.
	 * To use the APIs, you should instead set an API token to the user you want to allow to use API (This API token called DOLAPIKEY can be found/set on the user page) and use this token as credential for any API call.
	 * From the API explorer, you can enter directly the "DOLAPIKEY" into the field at the top right of the page to get access to any allowed APIs.
	 *
	 * @param   string  $login			User login
	 * @param   string  $password		User password
	 * @param   string  $entity			Entity (when multicompany module is used). '' means 1=first company.
	 * @param   int     $reset          Reset token (0=get current token, 1=ask a new token and canceled old token. This means access using current existing API token of user will fails: new token will be required for new access)
	 * @return  array                   Response status and user token
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 System error
	 *
	 * @url POST /
	 */
	public function index($login, $password, $entity = '', $reset = 0)
	{
		global $conf, $dolibarr_main_authentication, $dolibarr_auto_user;

		// Is the login API disabled ? The token must be generated from backoffice only.
		if (getDolGlobalString('API_DISABLE_LOGIN_API')) {
			dol_syslog("Warning: A try to use the login API has been done while the login API is disabled. You must generate or get the token from the backoffice.", LOG_WARNING);
			throw new RestException(403, "Error, the login API has been disabled for security purpose. You must generate or get the token from the backoffice.");
		}

		// Authentication mode
		if (empty($dolibarr_main_authentication)) {
			$dolibarr_main_authentication = 'dolibarr';
		}

		// Authentication mode: forceuser
		if ($dolibarr_main_authentication == 'forceuser') {
			if (empty($dolibarr_auto_user)) {
				$dolibarr_auto_user = 'auto';
			}
			if ($dolibarr_auto_user != $login) {
				dol_syslog("Warning: your instance is set to use the automatic forced login '".$dolibarr_auto_user."' that is not the requested login. API usage is forbidden in this mode.");
				throw new RestException(403, "Your instance is set to use the automatic login '".$dolibarr_auto_user."' that is not the requested login. API usage is forbidden in this mode.");
			}
		}

		// Set authmode
		$authmode = explode(',', $dolibarr_main_authentication);

		if ($entity != '' && !is_numeric($entity)) {
			throw new RestException(403, "Bad value for entity, must be the numeric ID of company.");
		}
		if ($entity == '') {
			$entity = 1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
		$login = checkLoginPassEntity($login, $password, $entity, $authmode, 'api');		// Check credentials.
		if ($login === '--bad-login-validity--') {
			$login = '';
		}
		if (empty($login)) {
			throw new RestException(403, 'Access denied');
		}

		$token = 'failedtogenerateorgettoken';

		$tmpuser = new User($this->db);
		$tmpuser->fetch(0, $login, 0, 0, $entity);
		if (empty($tmpuser->id)) {
			throw new RestException(500, 'Failed to load user');
		}

		// Renew the hash
		if (empty($tmpuser->api_key) || $reset) {
			$tmpuser->loadRights();
			if (!$tmpuser->hasRight('user', 'self', 'creer')) {
				if (empty($tmpuser->api_key)) {
					throw new RestException(403, 'No API token set for this user and user need write permission on itself to reset its API token');
				} else {
					throw new RestException(403, 'User need write permission on itself to reset its API token');
				}
			}

			// Generate token for user
			$token = dol_hash($login.uniqid().(!getDolGlobalString('MAIN_API_KEY') ? '' : $conf->global->MAIN_API_KEY), '1');

			// We store API token into database
			$sql = "UPDATE ".MAIN_DB_PREFIX."user";
			$sql .= " SET api_key = '".$this->db->escape(dolEncrypt($token, '', '', 'dolibarr'))."'";
			$sql .= " WHERE login = '".$this->db->escape($login)."'";

			dol_syslog(get_class($this)."::login", LOG_DEBUG); // No log
			$result = $this->db->query($sql);
			if (!$result) {
				throw new RestException(500, 'Error when updating api_key for user :'.$this->db->lasterror());
			}
		} else {
			$token = $tmpuser->api_key;
			if (!utf8_check($token)) {
				throw new RestException(500, 'Error, the API token of this user has a non valid value. Try to update it with a valid value.');
			}
		}

		if (!ascii_check($token)) {
			throw new RestException(500, 'Error the token for this user has not an hexa format. Try first to reset it.');
		}

		//return token
		return array(
			'success' => array(
				'code' => 200,
				'token' => $token,
				'entity' => $tmpuser->entity,
				'message' => 'Welcome '.$login.($reset ? ' - Token is new' : ' - This is your token (recorded for your user). You can use it to make any REST API call, or enter it into the DOLAPIKEY field to use the Dolibarr API explorer.')
			)
		);
	}
}
