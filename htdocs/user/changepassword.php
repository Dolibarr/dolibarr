<?php
/* Copyright (C) 2026		Eric Seigne				<eric.seigne@cap-rel.fr>
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
 *       \file       htdocs/user/changepassword.php
 *       \brief      Page to allow a logged user to change his own password
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->loadLangs(array('users', 'other', 'errors'));

$action = GETPOST('action', 'aZ09');

// Security check - user must be logged in
if (!$user->id) {
	accessforbidden('', 0, 0);
}

// Check permission to change own password
// note eric: maybe we have to add that check on card where there is the checkbox on in user.class.php ?
if (!$user->hasRight('user', 'self', 'password')) {
	accessforbidden($langs->trans("ErrorForbidden"), 0, 0);
}

// Check authentication mode - only dolibarr auth can change password here
if ($_SESSION['dol_authmode'] !== 'dolibarr') {
	accessforbidden($langs->trans("ErrorForbidden").' - '.$langs->trans("YouMustUseDolibarrAuthModeToChangePassword"), 0, 0);
}

$error = 0;


/*
 * Actions
 */

if ($action == 'update' && GETPOST('token', 'alpha') == newToken()) {	// Test on pemrission already done
	$oldpassword = GETPOST('oldpassword', 'password');
	$newpassword = GETPOST('newpassword', 'password');
	$confirmpassword = GETPOST('confirmpassword', 'password');

	// Check all fields are filled
	if (empty($oldpassword) || empty($newpassword) || empty($confirmpassword)) {
		setEventMessages($langs->trans("ErrorFieldRequired"), null, 'errors');
		$error++;
	}

	// Check new password matches confirmation
	if (!$error && $newpassword !== $confirmpassword) {
		setEventMessages($langs->trans("PasswordsMismatch"), null, 'errors');
		$error++;
	}

	// Check new password is different from old
	if (!$error && $oldpassword === $newpassword) {
		setEventMessages($langs->trans("NewPasswordMustBeDifferent"), null, 'errors');
		$error++;
	}

	// Verify current password
	if (!$error) {
		$passcrypted = $user->pass_indatabase_crypted;

		if (!$passcrypted || !dol_verifyHash($oldpassword, $passcrypted, '0')) {
			setEventMessages($langs->trans("ErrorCurrentPasswordWrong"), null, 'errors');
			$error++;
			sleep(1); // Anti brute force protection
		}
	}

	// Set new password
	if (!$error) {
		$result = $user->setPassword($user, $newpassword, 0, 0, 0, 0, 1);

		if (is_int($result) && $result < 0) {
			setEventMessages($user->error, $user->errors, 'errors');
			$error++;
		} else {
			// Reset force_pass_change flag if it was set
			if ($user->force_pass_change) {
				$user->setForcePasswordChange($user, 0);
			}

			setEventMessages($langs->trans("PasswordSuccessfullyChanged"), null, 'mesgs');

			// Redirect to home page or back to where user came from
			$url = DOL_URL_ROOT.'/index.php';
			header('Location: '.$url);
			exit;
		}
	}
}


/*
 * View
 */

$dol_url_root = DOL_URL_ROOT;

// Application title
$appli = constant('DOL_APPLICATION_TITLE');
$applicustom = getDolGlobalString('MAIN_APPLICATION_TITLE');
if ($applicustom) {
	$appli = (preg_match('/^\+/', $applicustom) ? $appli : '').$applicustom;
} else {
	$appli .= " ".DOL_VERSION;
}
$title = $appli;

// Select templates dir
$template_dir = '';
if (!empty($conf->modules_parts['tpl'])) {
	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl/'));
	foreach ($dirtpls as $reldir) {
		$tmp = dol_buildpath($reldir.'changepassword.tpl.php');
		if (file_exists($tmp)) {
			$template_dir = preg_replace('/changepassword\.tpl\.php$/', '', $tmp);
			break;
		}
	}
} elseif (file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/changepassword.tpl.php")) {
	$template_dir = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/";
} else {
	$template_dir = DOL_DOCUMENT_ROOT."/core/tpl/";
}

// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
$width = 0;
$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';
if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
} elseif (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
	$width = 128;
} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.svg')) {
	$urllogo = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.svg';
} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
	$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
}

include $template_dir.'changepassword.tpl.php';

$db->close();
