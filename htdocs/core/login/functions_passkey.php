<?php
/* Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
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
 *      \file       htdocs/core/login/functions_passkey.php
 *      \ingroup    core
 *      \brief      Authentication functions for passkey mode (WebAuthn)
 */


/**
 * Check validity of user for the passkey mode.
 * The signature of the passkey was already checked by core/ajax/passkeylogin.php, that saved the login
 * into the session. Here we only accept this login, once, if it is the one submitted by the login page.
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password (not used)
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @param	string	$context		Context of the authentication ('api', 'dav', 'ws' or '' for the login page)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_passkey($usertotest, $passwordtotest, $entitytotest = 1, $context = '')
{
	global $langs;

	// A passkey can only be used from the login page of a browser
	if ($context !== '') {
		return '';
	}

	dol_syslog("functions_passkey::check_user_password_passkey usertotest=".$usertotest." entitytotest=".$entitytotest);

	$login = '';

	$receivedlogin = (empty($_SESSION['passkey_receivedlogin']) ? array() : $_SESSION['passkey_receivedlogin']);
	unset($_SESSION['passkey_receivedlogin']);	// A validated passkey can be used for one login only

	if (!empty($receivedlogin['login']) && is_string($usertotest) && $usertotest !== ''
		&& hash_equals((string) $receivedlogin['login'], $usertotest)
		&& $receivedlogin['date'] >= (dol_now() - 60)) {
		dol_syslog("Login validated by a passkey and saved into session. This login is ".$usertotest);
		$login = $usertotest;
	} else {
		$langs->loadLangs(array('main', 'errors', 'users'));
		if (!empty($_SESSION['passkey_loginerror'])) {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("PasskeyLoginFailed").' '.$_SESSION['passkey_loginerror'];
		} else {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("PasskeyLoginFailed");
		}
		dol_syslog("functions_passkey::check_user_password_passkey Authentication KO", LOG_NOTICE);
	}
	unset($_SESSION['passkey_loginerror']);

	return $login;
}
