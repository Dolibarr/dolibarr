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
 *	\file       htdocs/core/lib/passkey.lib.php
 * 	\ingroup	core
 *	\brief      Library of functions for the passkey (WebAuthn) authentication mode
 */


/**
 * Return if the server can verify passkeys (PHP version and extensions required by the WebAuthn library)
 *
 * @return	bool
 */
function passkeyIsAvailable()
{
	return (PHP_VERSION_ID >= 80000 && extension_loaded('openssl'));
}

/**
 * Return if the authentication mode 'passkey' is enabled into $dolibarr_main_authentication and can be used
 *
 * @return	bool
 */
function passkeyIsEnabled()
{
	global $conf;

	$authentication = defined('MAIN_AUTHENTICATION_MODE') ? constant('MAIN_AUTHENTICATION_MODE') : (empty($conf->file->main_authentication) ? '' : $conf->file->main_authentication);

	return (in_array('passkey', array_map('trim', explode(',', $authentication))) && passkeyIsAvailable());
}

/**
 * Return if a user must log in with a passkey instead of his password.
 * The setup of the user (passkey_required) has priority over the global setup MAIN_AUTHENTICATION_PASSKEY_REQUIRED
 * (0=nobody, 1=administrators only, 2=all users).
 * This applies only to the login page with the authentication modes using a password (see passkeyGetPasswordAuthModes()).
 *
 * @param	User	$tmpuser	User
 * @return	bool
 */
function passkeyIsRequiredForUser($tmpuser)
{
	if (!passkeyIsEnabled()) {
		return false;
	}

	if ($tmpuser->passkey_required !== null) {
		return ($tmpuser->passkey_required == 1);
	}

	$required = getDolGlobalInt('MAIN_AUTHENTICATION_PASSKEY_REQUIRED');

	return ($required == 2 || ($required == 1 && !empty($tmpuser->admin)));
}

/**
 * Return the authentication modes that check a password, so that are refused for a user that must use a passkey
 *
 * @return	string[]
 */
function passkeyGetPasswordAuthModes()
{
	return array('dolibarr', 'ldap');
}

/**
 * Return if a login successfully checked with a password must be refused because the user must use his passkey.
 * Used for the login page and also for the API, DAV and web services, so the password can't be used anywhere.
 * A user that must use a passkey but has none yet can still log in with his password, to create his first passkey.
 *
 * @param	string	$login			Login returned by the authentication mode
 * @param	int		$entitytotest	Entity of the login
 * @param	string	$mode			Authentication mode that accepted the login
 * @return	bool
 */
function passkeyRefusePasswordLogin($login, $entitytotest, $mode)
{
	global $db;

	if (!in_array($mode, passkeyGetPasswordAuthModes()) || !passkeyIsEnabled()) {
		return false;
	}

	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/userpasskey.class.php';

	$tmpuser = new User($db);
	if ($tmpuser->fetch(0, $login, '', 0, ($entitytotest > 0 ? $entitytotest : -1)) <= 0) {
		return false;
	}
	if (!passkeyIsRequiredForUser($tmpuser)) {
		return false;
	}

	$passkeystatic = new UserPasskey($db);

	return ($passkeystatic->countForUser($tmpuser->id) > 0);
}

/**
 * Return the origin (scheme://host[:port]) the browser must report in the WebAuthn client data.
 * It is built from $dolibarr_main_url_root of the conf.php file and not from the HTTP request,
 * so it is the public URL even when Dolibarr runs behind a reverse proxy.
 *
 * @return	string		Origin, '' if it can't be found
 */
function passkeyGetOrigin()
{
	global $conf;

	$urlroot = trim(empty($conf->file->dol_main_url_root) ? '' : $conf->file->dol_main_url_root);
	if (!empty($conf->file->main_force_https)) {
		$urlroot = preg_replace('/^http:/i', 'https:', $urlroot);
	}

	$scheme = strtolower((string) parse_url($urlroot, PHP_URL_SCHEME));
	$host = strtolower((string) parse_url($urlroot, PHP_URL_HOST));
	$port = parse_url($urlroot, PHP_URL_PORT);
	if (!in_array($scheme, array('http', 'https')) || $host === '') {
		return '';
	}

	return $scheme.'://'.$host.($port ? ':'.((int) $port) : '');
}

/**
 * Return the WebAuthn relying party ID: the domain the passkeys are bound to.
 * It is the host of $dolibarr_main_url_root, or the value of MAIN_AUTHENTICATION_PASSKEY_RPID
 * (a parent domain of this host, to share passkeys between several instances).
 *
 * @return	string		Relying party ID, '' if it can't be found
 */
function passkeyGetRpId()
{
	$rpid = strtolower(trim(getDolGlobalString('MAIN_AUTHENTICATION_PASSKEY_RPID')));
	if ($rpid !== '') {
		return $rpid;
	}

	return strtolower((string) parse_url(passkeyGetOrigin(), PHP_URL_HOST));
}

/**
 * Return the reason why passkeys can't be used with the current setup
 *
 * @return	string		Translated error message, '' if the setup is correct
 */
function passkeyCheckSetup()
{
	global $langs;

	$langs->load("users");

	if (!passkeyIsAvailable()) {
		return $langs->trans("PasskeyErrorPHPRequirements", '8.0', 'openssl');
	}

	$origin = passkeyGetOrigin();
	$rpid = passkeyGetRpId();
	if ($origin === '' || $rpid === '') {
		return $langs->trans("PasskeyErrorBadUrlRoot");
	}

	$host = (string) parse_url($origin, PHP_URL_HOST);
	// WebAuthn refuses an IP address as relying party ID: browsers fail with "This is an invalid domain"
	foreach (array($host, $rpid) as $domain) {
		if (filter_var(trim($domain, '[]'), FILTER_VALIDATE_IP) !== false) {
			return $langs->trans("PasskeyErrorIpAddress", $domain);
		}
	}
	if (strpos($origin, 'https://') !== 0 && $host !== 'localhost') {
		return $langs->trans("PasskeyErrorHttpsRequired");
	}
	if ($host !== $rpid && substr($host, -strlen('.'.$rpid)) !== '.'.$rpid) {
		return $langs->trans("PasskeyErrorBadRpId", $rpid, $host);
	}

	return '';
}

/**
 * Return a WebAuthn server object for the current instance
 *
 * @return	\ReportUri\Passkeys\WebAuthn
 * @throws	\ReportUri\Passkeys\WebAuthnException
 */
function passkeyGetServer()
{
	global $mysoc;

	require_once DOL_DOCUMENT_ROOT.'/includes/report-uri/passkeys-php/src/WebAuthn.php';

	$rpname = (is_object($mysoc) && !empty($mysoc->name)) ? $mysoc->name : 'Dolibarr';

	// true = binary values are exchanged with the browser as base64url strings
	return new \ReportUri\Passkeys\WebAuthn($rpname, passkeyGetRpId(), true);
}

/**
 * Check that the origin reported by the browser is exactly the expected origin.
 * The WebAuthn library also accepts subdomains of the relying party ID, this is stricter.
 *
 * @param	string	$clientdatajson		Client data JSON (binary string) returned by the browser
 * @return	bool
 */
function passkeyCheckClientOrigin($clientdatajson)
{
	$clientdata = json_decode($clientdatajson);
	if (!is_object($clientdata) || empty($clientdata->origin) || !is_string($clientdata->origin)) {
		return false;
	}

	$expectedorigin = passkeyGetOrigin();

	return ($expectedorigin !== '' && strtolower(rtrim($clientdata->origin, '/')) === $expectedorigin);
}

/**
 * Decode a base64url string sent by the browser
 *
 * @param	string	$data		Base64url encoded string
 * @return	string				Binary string, '' if invalid
 */
function passkeyBase64UrlDecode($data)
{
	if (!is_string($data) || $data === '' || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/', $data)) {
		return '';
	}
	$result = base64_decode(strtr($data, '-_', '+/'), true);

	return ($result === false ? '' : $result);
}

/**
 * Return if the number of failed logins from the current IP during the last 24 hours is over the allowed limit.
 * Same protection than the one of the authentication mode 'dolibarr'.
 *
 * @return	bool
 */
function passkeyHasTooManyFailedLogins()
{
	global $db;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	$dateverificationauth = dol_time_plus_duree(dol_now(), -1, 'd');
	$nbevents = 0;

	$sql = "SELECT COUNT(e.rowid) as nbevent";
	$sql .= " FROM ".MAIN_DB_PREFIX."events as e";
	$sql .= " WHERE e.type = 'USER_LOGIN_FAILED'";
	$sql .= " AND e.ip = '".$db->escape(getUserRemoteIP())."'";
	$sql .= " AND e.dateevent > '".$db->idate($dateverificationauth)."'";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nbevents = (int) $obj->nbevent;
		}
	}

	return ($nbevents > getDolGlobalInt("MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH", 100));
}
