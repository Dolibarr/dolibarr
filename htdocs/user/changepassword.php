<?php
/* Copyright (C) 2026		Lilian Sonzogni			<lsonzogni@ircf.fr>
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
 *  \file       htdocs/user/changepassword.php
 *  \ingroup    core
 *  \brief      Page where a user who must change their password at next login sets a new one. main.inc.php sends this user here.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var Societe $mysoc
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('users', 'other', 'errors'));

$action = GETPOST('action', 'aZ09');

// Only a user who must change their password comes here, with the mode main.inc.php enforces it for. The others change it from their user card.
if (empty($user->force_pass_change) || (empty($_SESSION['dol_authmode']) ? '' : $_SESSION['dol_authmode']) != 'dolibarr') {
	header('Location: '.DOL_URL_ROOT.'/');
	exit;
}

$passwordchanged = false;
$message = '';


/*
 * Actions
 */

if ($action == 'update') {	// Test on permission not required: the page is reached only by the logged user, for their own password
	$newpassword = GETPOST('newpassword', 'password');
	$newpassword2 = GETPOST('newpassword2', 'password');
	if ($newpassword === '') {
		$message = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewPassword"));
	} elseif ($newpassword !== $newpassword2) {
		$message = $langs->trans("ErrorPasswordsMustMatch");
	} else {
		// setPassword() checks the rules of the password setup, clears force_pass_change and ends the sessions opened before, this one included
		$result = $user->setPassword($user, $newpassword);
		if (is_int($result) && $result <= 0) {
			$message = $user->error ? $user->error : implode(', ', $user->errors);
		} else {
			$passwordchanged = true;
		}
	}
}


/*
 * View
 */

// Same look as the login page: application title, logo, background. No menu, since nothing else can be reached until the password is changed.
$title = constant('DOL_APPLICATION_TITLE');
$applicustom = getDolGlobalString('MAIN_APPLICATION_TITLE');
if ($applicustom) {
	$title = (preg_match('/^\+/', $applicustom) ? $title : '').$applicustom;
} else {
	$title .= ' '.DOL_VERSION;
}

// Logo: small company logo, large company logo, theme logo, common logo, as on the login page
$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';
if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/'.$mysoc->logo);
} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.svg')) {
	$urllogo = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.svg';
} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
	$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
}

$colorbackhmenu1 = '60,70,100'; // topmenu
$colorbackhmenu1 = getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED') ? getDolUserString('THEME_ELDY_TOPMENU_BACK1', $colorbackhmenu1) : getDolGlobalString('THEME_ELDY_TOPMENU_BACK1', $colorbackhmenu1);
$colorbackhmenu1 = implode(',', colorStringToArray($colorbackhmenu1)); // Normalize value to 'x,y,z'

top_htmlhead('', $langs->trans("NewPassword"), 0, 0, array(), array(), 1, 1);

print '<body class="body bodylogin"'.(!getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background-image: url(\''.DOL_URL_ROOT.'/viewimage.php?cache=1&noalt=1&modulepart=mycompany&file='.urlencode('logos/'.getDolGlobalString('MAIN_LOGIN_BACKGROUND')).'\')"').'>'."\n";

$backstyle = 'background: linear-gradient('.($conf->browser->layout == 'phone' ? '0deg' : '4deg').', var(--colorbackbody) 52%, rgb('.$colorbackhmenu1.') 52.1%);';
$backstyle = getDolGlobalString('MAIN_LOGIN_BACKGROUND_STYLE', $backstyle);
print '<div class="login_center center"'.(getDolGlobalString('MAIN_LOGIN_BACKGROUND') || getDolGlobalString('ADD_UNSPLASH_LOGIN_BACKGROUND') ? '' : ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; '.$backstyle.'"').'>'."\n";
print '<div class="login_vertical_align">'."\n";

print '<form id="login" name="login" method="POST" action="'.dolPrintHTMLForAttributeUrl($_SERVER["PHP_SELF"]).'">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="update">'."\n";

print '<div class="login_table_title center" title="'.dolPrintHTMLForAttribute($title).'">'.dolPrintLabel($title).'</div>'."\n";
print '<div class="login_table">'."\n";
print '<div id="login_line1">'."\n";
print '<div id="login_left"><img alt="" title="" src="'.dolPrintHTMLForAttributeUrl($urllogo).'" id="img_logo" /></div>'."\n";
print '<br>'."\n";
// Why the user is here, between the logo and the fields: they expected the application, not a form. Outside login_right, which keeps the width of the fields, so they stay centered.
print '<div class="'.($passwordchanged ? 'ok' : 'warning').' center inline-block nomarginbottom">'.$langs->trans($passwordchanged ? "PasswordChangedLogInAgain" : "YouMustChangeYourPassword").'</div>'."\n";
print '<br>'."\n";
print '<div id="login_right">'."\n";
if (!$passwordchanged) {
	print '<div class="tagtable centpercent">'."\n";
	print '<div class="trinputlogin"><div class="tagtd nowraponall center valignmiddle tdinputlogin">';
	print '<span class="fa fa-key"></span>';
	print '<input type="password" id="newpassword" name="newpassword" maxlength="128" placeholder="'.dolPrintHTMLForAttribute($langs->trans("NewPassword")).'" class="flat input-icon-password minwidth150" autocomplete="new-password" spellcheck="false" tabindex="1" autofocus />';
	print '</div></div>'."\n";
	print '<div class="trinputlogin"><div class="tagtd nowraponall center valignmiddle tdinputlogin">';
	print '<span class="fa fa-key"></span>';
	print '<input type="password" id="newpassword2" name="newpassword2" maxlength="128" placeholder="'.dolPrintHTMLForAttribute($langs->trans("PasswordRetype")).'" class="flat input-icon-password minwidth150" autocomplete="new-password" spellcheck="false" tabindex="2" />';
	print '</div></div>'."\n";
	print '</div>'."\n";
}
print '</div>'."\n";	// login_right
print '</div>'."\n";	// login_line1

print '<div id="login_line2" style="clear: both">'."\n";
if ($passwordchanged) {
	// setPassword() has ended this session too: the user logs in again with the new password
	print '<br><a class="butAction butActionLogin noborderfocus" href="'.dolPrintHTMLForAttributeUrl(DOL_URL_ROOT.'/').'">'.$langs->trans("Connection").'</a>'."\n";
} else {
	print '<br><input type="submit" class="butAction butActionLogin noborderfocus" value="'.dolPrintHTMLForAttribute($langs->trans("Save")).'" tabindex="3" />'."\n";
	print '<br><div class="center" style="margin-top: 15px;"><a class="alogin" href="'.dolPrintHTMLForAttributeUrl(DOL_URL_ROOT.'/user/logout.php?token='.newToken()).'">'.$langs->trans("Logout").'</a></div>'."\n";
}
print '</div>'."\n";	// login_line2

print '</div>'."\n";	// login_table
print '</form>'."\n";

// Error under the box, as on the login page
if ($message) {
	print '<br><div class="center login_main_message"><div class="error">'.dolPrintLabel($message).'</div></div>'."\n";
}

print '</div>'."\n";	// login_vertical_align
print '</div>'."\n";	// login_center

print '</body>'."\n";
print '</html>'."\n";

$db->close();
