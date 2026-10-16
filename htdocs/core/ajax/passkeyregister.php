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
 *       \file       htdocs/core/ajax/passkeyregister.php
 *       \brief      Ajax component to register a new passkey (WebAuthn credential) for the logged user.
 *       			 action=getoptions returns the options for navigator.credentials.create(),
 *       			 action=verify checks the answer of the authenticator and saves the passkey.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/passkey.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/userpasskey.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');

$langs->loadLangs(array('users', 'errors'));


// Security check
// A passkey can be added by the user himself only: the private key is created on his own device.
if (!passkeyIsEnabled()) {
	httponly_accessforbidden('The authentication mode passkey is not enabled', 403);
}
if (!empty($user->socid) || (!$user->hasRight('user', 'self', 'password') && !passkeyIsRequiredForUser($user))) {
	httponly_accessforbidden('Not allowed to manage your passkeys', 403);
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	httponly_accessforbidden('Method must be POST', 405);
}


/*
 * Actions
 */

top_httphead('application/json');

$result = array('success' => false);

try {
	if ($action == 'getoptions') {
		$setuperror = passkeyCheckSetup();
		if ($setuperror) {
			throw new Exception($setuperror);
		}

		$passkeystatic = new UserPasskey($db);

		// All passkeys of a user share the same random user handle, so a synced passkey replaces the
		// previous one of the same user on the same password manager instead of adding a duplicate.
		$userhandle = $passkeystatic->getUserHandleOfUser($user->id);
		if ($userhandle === '') {
			$userhandle = bin2hex(random_bytes(32));
		}

		// Existing passkeys are excluded, so the same authenticator can't be registered twice
		$excludecredentialids = array();
		$listofpasskeys = $passkeystatic->fetchAllForUser($user->id);
		if (is_array($listofpasskeys)) {
			foreach ($listofpasskeys as $passkey) {
				$excludecredentialids[] = hex2bin($passkey->credential_id);
			}
		}

		$server = passkeyGetServer();
		// Discoverable credential (resident key) is required to log in without typing a login,
		// user verification (biometrics or PIN) is required because the passkey replaces the password.
		$args = $server->getCreateArgs(hex2bin($userhandle), $user->login, $user->getFullName($langs), 120, 'required', 'required', null, $excludecredentialids);

		$_SESSION['passkey_register'] = array(
			'challenge' => $server->getChallenge()->getBinaryString(),
			'user_handle' => $userhandle,
			'fk_user' => $user->id,
			'date' => dol_now(),
		);

		$result = array('success' => true, 'options' => $args);
	} elseif ($action == 'verify') {
		$sessiondata = (empty($_SESSION['passkey_register']) ? array() : $_SESSION['passkey_register']);
		unset($_SESSION['passkey_register']);	// A challenge can be used only once

		if (empty($sessiondata['challenge']) || $sessiondata['fk_user'] != $user->id || $sessiondata['date'] < (dol_now() - 300)) {
			throw new Exception($langs->trans("PasskeyErrorExpired"));
		}

		$clientdatajson = passkeyBase64UrlDecode(GETPOST('clientDataJSON', 'aZ09'));
		$attestationobject = passkeyBase64UrlDecode(GETPOST('attestationObject', 'aZ09'));
		$label = dol_trunc(GETPOST('label', 'alphanohtml'), 250, 'right', 'UTF-8', 1);
		if ($clientdatajson === '' || $attestationobject === '') {
			throw new Exception($langs->trans("ErrorBadParameters"));
		}

		if (!passkeyCheckClientOrigin($clientdatajson)) {
			throw new Exception($langs->trans("PasskeyErrorBadOrigin", passkeyGetOrigin()));
		}

		$server = passkeyGetServer();
		$data = $server->processCreate($clientdatajson, $attestationobject, $sessiondata['challenge'], true, true);

		$credentialid = bin2hex($data->credentialId);
		if (strlen($credentialid) > 255) {
			throw new Exception($langs->trans("PasskeyErrorCredentialIdTooLong"));
		}

		$passkey = new UserPasskey($db);
		if ($passkey->fetch(0, $credentialid) != 0) {
			throw new Exception($langs->trans("PasskeyErrorAlreadyRegistered"));
		}

		$passkey->fk_user = $user->id;
		$passkey->label = $label;
		$passkey->credential_id = $credentialid;
		$passkey->user_handle = $sessiondata['user_handle'];
		$passkey->public_key = $data->credentialPublicKey;
		$passkey->sign_count = (int) $data->signatureCounter;
		$aaguid = bin2hex($data->AAGUID);
		$passkey->aaguid = (strlen($aaguid) == 32 && $aaguid !== str_repeat('0', 32)) ? vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($aaguid, 4)) : '';
		$passkey->backup_eligible = ($data->isBackupEligible ? 1 : 0);
		$passkey->backup_state = ($data->isBackedUp ? 1 : 0);

		if ($passkey->create($user) < 0) {
			throw new Exception($passkey->error);
		}

		dol_syslog("Passkey id=".$passkey->id." added for user id=".$user->id, LOG_NOTICE);

		$result = array('success' => true, 'id' => $passkey->id);
	} else {
		throw new Exception($langs->trans("ErrorBadParameters"));
	}
} catch (Exception $e) {
	dol_syslog("passkeyregister action=".$action." error: ".$e->getMessage(), LOG_WARNING);
	$result = array('success' => false, 'error' => $e->getMessage());
}

print json_encode($result);

$db->close();
