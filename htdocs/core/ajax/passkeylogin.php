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
 *       \file       htdocs/core/ajax/passkeylogin.php
 *       \brief      Ajax component called by the login page, before being logged, to log in with a passkey.
 *       			 action=getoptions returns the options for navigator.credentials.get(),
 *       			 action=verify checks the signature of the authenticator. If it is valid, the login is saved
 *       			 into the session, so the login page can then submit it to the authentication mode 'passkey'
 *       			 (see core/login/functions_passkey.php) that will make the real login.
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal, so the token of the login form stays valid
}
// Called before login, when the session may have no CSRF token yet. A forged request is useless here:
// the challenge can't be read cross-site, and the answer must be signed with the private key of the passkey.
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/passkey.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/userpasskey.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */

$action = GETPOST('action', 'aZ09');

$langs->loadLangs(array('main', 'users', 'errors'));


// Security check
if (!passkeyIsEnabled()) {
	httponly_accessforbidden('The authentication mode passkey is not enabled', 403);
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	httponly_accessforbidden('Method must be POST', 405);
}


/*
 * Actions
 */

top_httphead('application/json');

$result = array('success' => false);

// Only these messages can be shown to a visitor of the login page (same kind of messages than for a bad password).
// The other errors (wrong setup, details of the WebAuthn library) would give information to an attacker:
// they are only logged, the administrator sees the setup errors on the setup page of passkeys.
$publicerrors = array(
	$langs->transnoentitiesnoconv("PasskeyErrorExpired"),
	$langs->transnoentitiesnoconv("ErrorTooManyAttempts"),
	$langs->transnoentitiesnoconv("PasskeyErrorUnknown"),
	$langs->transnoentitiesnoconv("ErrorLoginDisabled"),
	$langs->transnoentitiesnoconv("ErrorBadParameters"),
);

try {
	if ($action == 'getoptions') {
		$setuperror = passkeyCheckSetup();
		if ($setuperror) {
			throw new Exception($setuperror);
		}

		$server = passkeyGetServer();
		// No list of allowed credentials: the browser proposes the passkeys it knows for this site
		// (discoverable credentials), so the login does not have to be typed.
		$args = $server->getGetArgs(array(), 120, true, true, true, true, true, 'required');

		$_SESSION['passkey_login'] = array(
			'challenge' => $server->getChallenge()->getBinaryString(),
			'date' => dol_now(),
		);

		$result = array('success' => true, 'options' => $args);
	} elseif ($action == 'verify') {
		$sessiondata = (empty($_SESSION['passkey_login']) ? array() : $_SESSION['passkey_login']);
		unset($_SESSION['passkey_login']);	// A challenge can be used only once
		unset($_SESSION['passkey_receivedlogin']);

		if (empty($sessiondata['challenge']) || $sessiondata['date'] < (dol_now() - 300)) {
			throw new Exception($langs->transnoentitiesnoconv("PasskeyErrorExpired"));
		}

		// Same protection against brute force than the authentication mode 'dolibarr'
		if (passkeyHasTooManyFailedLogins()) {
			throw new Exception($langs->transnoentitiesnoconv("ErrorTooManyAttempts"));
		}

		$credentialid = passkeyBase64UrlDecode(GETPOST('id', 'aZ09'));
		$clientdatajson = passkeyBase64UrlDecode(GETPOST('clientDataJSON', 'aZ09'));
		$authenticatordata = passkeyBase64UrlDecode(GETPOST('authenticatorData', 'aZ09'));
		$signature = passkeyBase64UrlDecode(GETPOST('signature', 'aZ09'));
		$userhandle = passkeyBase64UrlDecode(GETPOST('userHandle', 'aZ09'));
		$entitytotest = (GETPOSTINT('entity') ? GETPOSTINT('entity') : (!empty($conf->entity) ? $conf->entity : 1));
		if ($credentialid === '' || $clientdatajson === '' || $authenticatordata === '' || $signature === '' || $userhandle === '') {
			throw new Exception($langs->transnoentitiesnoconv("ErrorBadParameters"));
		}

		$passkey = new UserPasskey($db);
		if ($passkey->fetch(0, bin2hex($credentialid)) <= 0) {
			throw new Exception($langs->transnoentitiesnoconv("PasskeyErrorUnknown"));
		}

		// The user handle returned by the authenticator must be the one of the owner of the passkey
		if (!hash_equals($passkey->user_handle, bin2hex($userhandle))) {
			throw new Exception($langs->transnoentitiesnoconv("PasskeyErrorUnknown"));
		}

		if (!passkeyCheckClientOrigin($clientdatajson)) {
			throw new Exception($langs->transnoentitiesnoconv("PasskeyErrorBadOrigin", passkeyGetOrigin()));
		}

		// Synced passkeys always return a counter 0, so the counter is checked only if the authenticator uses one
		$prevsigncount = ($passkey->sign_count > 0 ? $passkey->sign_count : null);

		$server = passkeyGetServer();
		$server->processGet($clientdatajson, $authenticatordata, $signature, $passkey->public_key, $sessiondata['challenge'], $prevsigncount, true, true);

		// The owner of the passkey must be the user that the login page will find with this login and entity
		$tmpuser = new User($db);
		$tmpuserlogin = '';
		if ($tmpuser->fetch($passkey->fk_user) > 0) {
			$tmpuserlogin = $tmpuser->login;
		}
		$loginuser = new User($db);
		if ($tmpuserlogin === '' || $loginuser->fetch(0, $tmpuserlogin, '', 0, ($entitytotest > 0 ? $entitytotest : -1)) <= 0 || $loginuser->id != $passkey->fk_user) {
			throw new Exception($langs->transnoentitiesnoconv("PasskeyErrorUnknown"));
		}
		if ($loginuser->status != User::STATUS_ENABLED || $loginuser->isNotIntoValidityDateRange()) {
			throw new Exception($langs->transnoentitiesnoconv("ErrorLoginDisabled"));
		}

		// The flags of the authenticator data are read again to save the current backup state
		require_once DOL_DOCUMENT_ROOT.'/includes/report-uri/passkeys-php/src/WebAuthn.php';
		$authenticatordataobj = new \ReportUri\Passkeys\Attestation\AuthenticatorData($authenticatordata);
		$passkey->updateAfterLogin($authenticatordataobj->getSignCount(), $authenticatordataobj->getIsBackup() ? 1 : 0);

		// The login page will now submit this login to the authentication mode 'passkey' that will accept it once
		$_SESSION['passkey_receivedlogin'] = array(
			'login' => $loginuser->login,
			'date' => dol_now(),
		);

		dol_syslog("Passkey id=".$passkey->id." validated for login ".$loginuser->login, LOG_NOTICE);

		$result = array('success' => true, 'login' => $loginuser->login);
	} else {
		throw new Exception($langs->transnoentitiesnoconv("ErrorBadParameters"));
	}
} catch (Exception $e) {
	dol_syslog("passkeylogin action=".$action." error: ".$e->getMessage(), LOG_WARNING);
	$publicerror = (in_array($e->getMessage(), $publicerrors, true) ? $e->getMessage() : '');
	if ($action == 'verify') {
		// The reason of the failure is shown by the login page after the form is submitted, and the failed login
		// is recorded the same way than for the other authentication modes (event USER_LOGIN_FAILED).
		$_SESSION['passkey_loginerror'] = $publicerror;
	}
	$result = array('success' => false, 'error' => $publicerror);
}

print json_encode($result);

$db->close();
