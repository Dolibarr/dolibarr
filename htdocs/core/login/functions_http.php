<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       htdocs/core/login/functions_http.php
 * \ingroup    core
 * \brief      Authentication functions for HTTP Basic
 */


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
*/
function check_user_password_http($usertotest, $passwordtotest, $entitytotest)
{
	global $db, $langs;

	dol_syslog("functions_http::check_user_password_http _SERVER[REMOTE_USER]=".(empty($_SERVER["REMOTE_USER"]) ? '' : $_SERVER["REMOTE_USER"]));

	$login = '';
	if (!empty($_SERVER["REMOTE_USER"])) {
		$login = $_SERVER["REMOTE_USER"];

		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		// Note: Test on date validity is done later natively with isNotIntoValidityDateRange() by core after calling checkLoginPassEntity() that call this method
	}

	return $login;
}


/**
 * Decode the value found into the Authorization HTTP header.
 * Ex: "Authorization: Basic bG9naW46cGFzcw==", $value is "Basic bG9naW46cGFzcw==" and after base64decode is "login:pass"
 * Note: the $_SERVER["REMOTE_USER"] contains only the login used in the HTTP Basic form
 * Method not used yet, but we keep it for some dev/test purposes.
 *
 * @param 	string	$value 		Ex: $_SERVER["REMOTE_USER"]
 * @return 	Object 				object.login & object.password
 */
function decodeHttpBasicAuth($value)
{
	$encoded_basic_auth = substr($value, 6);	// Remove the "Basic " string
	$decoded_basic_auth = base64_decode($encoded_basic_auth);
	$credentials_basic_auth = explode(':', $decoded_basic_auth);

	return (object) [
		'username'=> $credentials_basic_auth[0],
		'password' => $credentials_basic_auth[1]
	];
}
