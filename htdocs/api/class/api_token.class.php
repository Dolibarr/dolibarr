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


require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-jwt/autoload.php';

use Luracast\Restler\RestException;
use Firebase\JWT\JWT;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;

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
	 * @phan-return array{success:array{code:int,message:string,token:string,refresh-token:string,entity:int}}
	 * @phpstan-return array{success:array{code:int,message:string,token:string,refresh-token:string,entity:int}}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 System error
	 *
	 * @url POST /
	 */
	public function post($login, $password, $entity = '')
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
		$now = dol_now();
		$payloadToken = [
			"iss" => $dolibarr_main_url_root,
			"aud" => $url,
			"name" => $tmpuser->getFullName($langs),
			"iat" => $now,
			"nbf" => $now,
			"exp" => $now + getDolGlobalInt('MAIN_SESSION_TIMEOUT', 3600), // Expire in 1 hour by default
			"data" => [
				"user_id" => $tmpuser->id,
				"email" => $tmpuser->email,
			]
		];
		$expire_at = $now + 86400;
		$payloadRefreshToken = [
			'iss' => $dolibarr_main_url_root,
			'iat' => $now,
			'exp' => $expire_at,
			'data' => [
				"user_id" => $tmpuser->id,
				"email" => $tmpuser->email,
				'random_string' => bin2hex(random_bytes(32)) // For more security
			]
		];
		$accessToken = JWT::encode($payloadToken, $dolibarr_main_instance_unique_id, 'HS256');
		// TODO store user refresh-token in db for control
		$refreshToken = JWT::encode($payloadRefreshToken, $dolibarr_main_instance_unique_id, 'HS256');
		// we store a md5 of the token to avoid to store non crypted and to make easy retrieve when checking
		$sql = "INSERT INTO " . $this->db->prefix() . "oauth_token (service, token, tokenstring, fk_user, expire_at) VALUES ('dolibarr_refresh_token_api', '" . $this->db->escape(dolEncrypt($refreshToken)) . "', '" . md5($refreshToken) . "', " . (int) $tmpuser->id . ", '" . $this->db->idate($expire_at) . "')";
		$this->db->query($sql);

		return [
			'success' => [
				'code' => 200,
				'token' => $accessToken,
				'refresh-token' => $refreshToken,
				'entity' => $tmpuser->entity,
				'message' => 'Welcome ' . $login . ' - This is your jwt token. You can use it to make any REST API call.',
			]
		];
	}

	/**
	 * Token
	 *
	 * Request the API refreshened jwt token for a refreshtoken.
	 *
	 * @param   string  $refreshtoken	the non expired refresh token
	 * @return  array                   Response status and user jwt token and new refresh-token
	 * @phan-return array{success:array{code:int,message:string,token:string,refresh-token:string,entity:int}}
	 * @phpstan-return array{success:array{code:int,message:string,token:string,refresh-token:string,entity:int}}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 System error
	 *
	 * @url PUT /
	 */
	public function put($refreshtoken)
	{
		global $dolibarr_main_instance_unique_id, $dolibarr_main_url_root, $langs;

		// Is the login API disabled ? The token must be generated from backoffice only.
		if (getDolGlobalString('API_DISABLE_JWT_TOKEN')) {
			dol_syslog("Warning: A try to use the token API has been done while the token API is disabled.", LOG_WARNING);
			throw new RestException(403, "Error, the token API has been disabled for security purpose.");
		}
		$jwtexpire = true;
		$useridjwt = 0;
		$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
		$url = $urlwithouturlroot . DOL_URL_ROOT . '/api/index.php/';
		$now = dol_now();
		// remove expired refresh token from db
		$sql = "DELETE FROM " . $this->db->prefix() . "oauth_token WHERE service = 'dolibarr_refresh_token_api' AND expire_at < '" . $this->db->idate($now) . "'";
		$this->db->query($sql);

		try {
			$decoded = JWT::decode($refreshtoken, new \Firebase\JWT\Key($dolibarr_main_instance_unique_id, 'HS256'));
			// Token is valid, continue with Token verification
			$decoded_array = json_decode(json_encode($decoded), true);
			if (!empty($decoded_array['data']['user_id'])) {
				$useridjwt = (int) $decoded_array['data']['user_id'];
			}
			$jwtexpire = false;
		} catch (BeforeValidException $e) {
			// provided JWT is trying to be used before "nbf" claim OR
			// provided JWT is trying to be used before "iat" claim.
			$jwtexpire = true;
		} catch (ExpiredException $e) {
			$jwtexpire = true;
		} catch (UnexpectedValueException $e) {
			//
		} catch (Exception $e) {
			throw new RestException(403, 'Failed to validate refresh token.');
		}
		// check if we find refresh token for the user in db (use md5 to find it) so we can invalidate it if needed
		// TODO

		if ($jwtexpire) {
			throw new RestException(500, 'Token refresh has expired.');
		}

		$tmpuser = new User($this->db);
		$tmpuser->fetch($useridjwt);
		if (empty($tmpuser->id)) {
			throw new RestException(500, 'Failed to load user');
		}

		$payloadToken = [
			"iss" => $dolibarr_main_url_root,
			"aud" => $url,
			"name" => $tmpuser->getFullName($langs),
			"iat" => $now,
			"nbf" => $now,
			"exp" => $now + getDolGlobalInt('MAIN_SESSION_TIMEOUT', 3600), // Expire in 1 hour by default
			"data" => [
				"user_id" => $tmpuser->id,
				"email" => $tmpuser->email,
			]
		];
		$expire_at = $now + 86400;
		$payloadRefreshToken = [
			'iss' => $dolibarr_main_url_root,
			'iat' => $now,
			'exp' => $expire_at,
			'data' => [
				"user_id" => $tmpuser->id,
				"email" => $tmpuser->email,
				'random_string' => bin2hex(random_bytes(32)) // For more security
			]
		];
		$accessToken = JWT::encode($payloadToken, $dolibarr_main_instance_unique_id, 'HS256');
		// store user refresh-token in db for control
		$refreshToken = JWT::encode($payloadRefreshToken, $dolibarr_main_instance_unique_id, 'HS256');
		// we store a md5 of the token to avoid to store non crypted and to make easy retrieve when checking
		$sql = "INSERT INTO " . $this->db->prefix() . "oauth_token (service, token, tokenstring, fk_user, expire_at) VALUES ('dolibarr_refresh_token_api', '" . $this->db->escape(dolEncrypt($refreshToken)) . "', '" . md5($refreshToken) . "', " . (int) $tmpuser->id . ", '" . $this->db->idate($expire_at) . "')";
		$this->db->query($sql);

		return [
			'success' => [
				'code' => 200,
				'token' => $accessToken,
				'refresh-token' => $refreshToken,
				'entity' => $tmpuser->entity,
				'message' => 'Welcome ' . $tmpuser->login . ' - This is your new jwt token. You can use it to make any REST API call.',
			]
		];
	}
}
