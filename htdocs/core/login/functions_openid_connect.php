<?php
/* Copyright (C) 2022 Jeritiana Ravelojaona <jeritiana.rav@smartone.ai>
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

/**
 *      \file       htdocs/core/login/functions_openid_connect.php
 *      \ingroup    core
 *      \brief      OpenID Connect: Authorization Code flow authentication
 *
 *      See https://github.com/Dolibarr/dolibarr/issues/22740 for more information about setup openid_connect
 */

include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_openid_connect($usertotest, $passwordtotest, $entitytotest)
{
	global $db, $conf, $langs;

	// Force master entity in transversal mode
	$entity = $entitytotest;
	if (isModEnabled('multicompany') && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		$entity = 1;
	}

	$login = '';

	dol_syslog("functions_openid_connect::check_user_password_openid_connect usertotest=".$usertotest." passwordtotest=".preg_replace('/./', '*', $passwordtotest)." entitytotest=".$entitytotest);

	// Step 1 is done by user: request an authorization code

	if (GETPOSTISSET('username')) {
		// OIDC does not require credentials here: pass on to next auth handler
		$_SESSION["dol_loginmesg"] = "Not an OpenID Connect flow";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect not an OIDC flow");
	} elseif (GETPOSTISSET('code')) {
		$auth_code = GETPOST('code', 'aZ09');
		dol_syslog("functions_openid_connect::check_user_password_openid_connect code=".$auth_code);

		// Step 2: turn the authorization code into an access token, using client_secret
		$auth_param = [
			'grant_type'    => 'authorization_code',
			'client_id'     => $conf->global->MAIN_AUTHENTICATION_OIDC_CLIENT_ID,
			'client_secret' => $conf->global->MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET,
			'code'          => $auth_code,
			'redirect_uri'  => $conf->global->MAIN_AUTHENTICATION_OIDC_REDIRECT_URL
		];

		$token_response = getURLContent($conf->global->MAIN_AUTHENTICATION_OIDC_TOKEN_URL, 'POST', http_build_query($auth_param));
		$token_content = json_decode($token_response['content']);
		dol_syslog("functions_openid_connect::check_user_password_openid_connect /token=".print_r($token_response, true), LOG_DEBUG);

		if (property_exists($token_content, 'access_token')) {
			// Step 3: retrieve user info using token
			$userinfo_headers = array('Authorization: Bearer '.$token_content->access_token);
			$userinfo_response = getURLContent($conf->global->MAIN_AUTHENTICATION_OIDC_USERINFO_URL, 'GET', '', 1, $userinfo_headers);
			$userinfo_content = json_decode($userinfo_response['content']);
			dol_syslog("functions_openid_connect::check_user_password_openid_connect /userinfo=".print_r($userinfo_response, true), LOG_DEBUG);

			// Get the user attribute (claim) matching the Dolibarr login
			$login_claim = 'email'; // default
			if (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM)) {
				$login_claim = $conf->global->MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM;
			}

			if (property_exists($userinfo_content, $login_claim)) {
				// Success: retrieve claim to return to Dolibarr as login
				$sql = 'SELECT login, entity, datestartvalidity, dateendvalidity';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'user';
				$sql .= " WHERE login = '".$db->escape($userinfo_content->$login_claim)."'";
				$sql .= ' AND entity IN (0,'.(array_key_exists('dol_entity', $_SESSION) ? ((int) $_SESSION["dol_entity"]) : 1).')';

				dol_syslog("functions_openid::check_user_password_openid", LOG_DEBUG);
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						// TODO I think we can remove this because this is done now natively by core after calling check_user_password_openid_connect()
						$now = dol_now();
						if ($obj->datestartvalidity && $db->jdate($obj->datestartvalidity) > $now) {
							// Load translation files required by the page
							$langs->loadLangs(array('main', 'errors'));
							$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorLoginDateValidity");
							return '--bad-login-validity--';
						}
						if ($obj->dateendvalidity && $db->jdate($obj->dateendvalidity) < dol_get_first_hour($now)) {
							// Load translation files required by the page
							$langs->loadLangs(array('main', 'errors'));
							$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorLoginDateValidity");
							return '--bad-login-validity--';
						}

						$login = $obj->login;
					}
				}
			} elseif ($userinfo_content->error) {
				// Got user info response but content is an error
				$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (".$userinfo_content->error_description.")";
			} elseif ($userinfo_response['http_code'] == 200) {
				// Claim does not exist
				$_SESSION["dol_loginmesg"] = "OpenID Connect claim not found: ".$login_claim;
			} elseif ($userinfo_response['curl_error_no']) {
				// User info request error
				$_SESSION["dol_loginmesg"] = "Network error: ".$userinfo_response['curl_error_msg']." (".$userinfo_response['curl_error_no'].")";
			} else {
				// Other user info request error
				$_SESSION["dol_loginmesg"] = "Userinfo request error (".$userinfo_response['http_code'].")";
			}
		} elseif ($token_content->error) {
			// Got token response but content is an error
			$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (".$token_content->error_description.")";
		} elseif ($token_response['curl_error_no']) {
			// Token request error
			$_SESSION["dol_loginmesg"] = "Network error: ".$token_response['curl_error_msg']." (".$token_response['curl_error_no'].")";
		} else {
			// Other token request error
			$_SESSION["dol_loginmesg"] = "Token request error (".$token_response['http_code'].")";
		}
	} else {
		// No code received
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (no code received)";
	}

	dol_syslog("functions_openid_connect::check_user_password_openid_connect END");

	return !empty($login) ? $login : false;
}
