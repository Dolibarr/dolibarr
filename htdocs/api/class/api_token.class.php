<?php
/* Copyright (C) 2015   	Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
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
use Firebase\JWT\JWT;

require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-jwt/autoload.php';

/**
 * API that allows to get a jwt token with an user account and password.
 */
class Token
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

		if (getDolGlobalString('API_DISABLE_JWT_TOKEN')) {
			throw new RestException(403, "Error token APIs are disabled. You must get the token from backoffice to be able to use APIs");
		}
	}

	/**
	 * Token
	 *
	 * Request the API jwt token for a couple username / password.
	 *
	 * @param   string  $login			User login
	 * @param   string  $password		User password
	 * @param   string  $entity			Entity (when multicompany module is used). '' means 1=first company.
	 * @return  array                   Response status and user jwt token
	 * @phan-return array{success:array{code:int,message:string,token:array{pass_encrypted:string,pass_encoding:string}|string,entity:int}}
	 * @phpstan-return array{success:array{code:int,message:string,token:array{pass_encrypted:string,pass_encoding:string}|string,entity:int}}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 System error
	 *
	 * @url POST /
	 */
	public function index($login, $password, $entity = '')
	{
		global $dolibarr_main_authentication, $dolibarr_auto_user, $dolibarr_main_instance_unique_id, $dolibarr_main_url_root, $langs;

		// Is the login API disabled ? The token must be generated from backoffice only.
		if (getDolGlobalString('API_DISABLE_JWT_TOKEN')) {
			dol_syslog("Warning: A try to use the token API has been done while the token API is disabled.", LOG_WARNING);
			throw new RestException(403, "Error, the token API has been disabled for security purpose.");
		}

		// Authentication mode
		if (empty($dolibarr_main_authentication) || $dolibarr_main_authentication == 'openid_connect') {
			$dolibarr_main_authentication = 'dolibarr';
		}

		// Authentication mode: forceuser
		if ($dolibarr_main_authentication == 'forceuser') {
			if (empty($dolibarr_auto_user)) {
				$dolibarr_auto_user = 'auto';
			}
			if ($dolibarr_auto_user != $login) {
				dol_syslog("Warning: your instance is set to use the automatic forced login '" . $dolibarr_auto_user . "' that is not the requested login. API usage is forbidden in this mode.");
				throw new RestException(403, "Your instance is set to use the automatic login '" . $dolibarr_auto_user . "' that is not the requested login. API usage is forbidden in this mode.");
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

		include_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
		$login = checkLoginPassEntity($login, $password, $entity, $authmode, 'api');		// Check credentials.
		if ($login === '--bad-login-validity--') {
			$login = '';
		}
		if (empty($login)) {
			throw new RestException(403, 'Access denied');
		}

		$tmpuser = new User($this->db);
		$tmpuser->fetch(0, $login, '0', 0, $entity);
		if (empty($tmpuser->id)) {
			throw new RestException(500, 'Failed to load user');
		}
		$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
		$url = $urlwithouturlroot . DOL_URL_ROOT . '/api/index.php/';

		$payload = [
			"iss" => $dolibarr_main_url_root,
			"aud" => $url,
			"name" => $tmpuser->getFullName($langs),
			"iat" => time(),
			"nbf" => time(),
			"exp" => time() + 3600, // Expiration dans 1 heure
			"data" => [
				"user_id" => $tmpuser->id,
				"email" => $tmpuser->email,
			]
		];
		$jwt = JWT::encode($payload, $dolibarr_main_instance_unique_id, 'HS256');

		return [
			'success' => [
				'code' => 200,
				'token' => $jwt,
				'entity' => $tmpuser->entity,
				'message' => 'Welcome ' . $login . ' - This is your jwt token. You can use it to make any REST API call.',
			]
		];
	}
}
