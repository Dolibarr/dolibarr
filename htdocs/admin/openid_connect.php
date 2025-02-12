<?php
/* Copyright (C) 2023		Maximilien Rozniecki	<mrozniecki@easya.solutions>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/openid_connect.php
 *		\ingroup    openid_connect
 *		\brief      Page to setup openid_connect module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/openid_connect.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/openid_connect.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(["admin", "openidconnect"]);

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'alpha');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'set') {
	$client_id = GETPOST('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM', $client_id, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$client_id = GETPOST('MAIN_AUTHENTICATION_OIDC_CLIENT_ID', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_CLIENT_ID', $client_id, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$client_secret = GETPOST('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET', $client_secret, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$scopes = GETPOST('MAIN_AUTHENTICATION_OIDC_SCOPES', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_SCOPES', $scopes, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$authorize_url = GETPOST('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL', $authorize_url, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('MAIN_AUTHENTICATION_OIDC_TOKEN_URL', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_TOKEN_URL', $value, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('MAIN_AUTHENTICATION_OIDC_USERINFO_URL', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_USERINFO_URL', $value, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$logout_url = GETPOST('MAIN_AUTHENTICATION_OIDC_LOGOUT_URL', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_LOGOUT_URL', $logout_url, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}
}

if ($action != '') {
	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"));
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages('', $errors, 'errors');
	}
}


/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("OpenIDconnectSetup"), $linkback, 'title_setup');

$head = openid_connect_prepare_head();

print dol_get_fiche_head($head, 'settings', $langs->trans("Parameters"), -1, 'action');

print $langs->trans("SeeWikiDocForHelpInSetupOpenIDCOnnect");
print ' - ';
print img_picto('', 'url', 'class="pictofixedwidth"').'<a target="_blank" href="https://wiki.dolibarr.org/index.php?title=Authentication,_SSO_and_SSL#Mode_openid_connect">';
print $langs->trans("SeeHere");
print '</a>';

print dol_get_fiche_end();

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable noborder liste nobottomiftotal">';
print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans("Parameters").'</th>'."\n";
print '<th class="liste_titre"></th>'."\n";
print '<th class="liste_titre"></th>'."\n";
print "</tr>\n";

// MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcLoginClaimName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcLoginClaimDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM" id="MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM') ? GETPOST('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_CLIENT_ID
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcClientIdName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcClientIdDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_CLIENT_ID" id="MAIN_AUTHENTICATION_OIDC_CLIENT_ID" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLIENT_ID') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLIENT_ID', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_CLIENT_ID) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLIENT_ID") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcClientSecretName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcClientSecretDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input type="password" name="MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET" id="MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_SCOPES
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcScopesName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcScopesDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_SCOPES" id="MAIN_AUTHENTICATION_OIDC_SCOPES" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_SCOPES') ? GETPOST('MAIN_AUTHENTICATION_OIDC_SCOPES', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_SCOPES) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_SCOPES") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcAuthorizeUrlName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcAuthorizeUrlDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL" id="MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_TOKEN_URL
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcTokenUrlName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcTokenUrlDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_TOKEN_URL" id="MAIN_AUTHENTICATION_OIDC_TOKEN_URL" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_TOKEN_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_TOKEN_URL', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_TOKEN_URL) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_TOKEN_URL") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_USERINFO_URL
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcUserinfoUrlName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcUserinfoUrlDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_USERINFO_URL" id="MAIN_AUTHENTICATION_OIDC_USERINFO_URL" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_USERINFO_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_USERINFO_URL', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_USERINFO_URL) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_USERINFO_URL") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// MAIN_AUTHENTICATION_OIDC_LOGOUT_URL
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcLogoutUrlName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcLogoutUrlDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input name="MAIN_AUTHENTICATION_OIDC_LOGOUT_URL" id="MAIN_AUTHENTICATION_OIDC_LOGOUT_URL" class="minwidth300" value="'.dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_LOGOUT_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_LOGOUT_URL', 'nohtml') : (!empty($conf->global->MAIN_AUTHENTICATION_OIDC_LOGOUT_URL) ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_LOGOUT_URL") : ''))).'"></td></tr>';
print '</td></tr>' . "\n";

// REDIRECT_URL
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcRedirectUrlName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcRedirectUrlDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input class="minwidth300" value="'.dol_escape_htmltag(openid_connect_get_redirect_url()).'" disabled></td></tr>';
print '</td></tr>' . "\n";

// LOGOUT_URL
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("MainAuthenticationOidcLogoutRedirectUrlName").'</td>'."\n";
print '<td>'.$langs->trans("MainAuthenticationOidcLogoutRedirectUrlDesc").'</td>'."\n";
print '<td align="right">' . "\n";
print '<input class="minwidth300" value="'.dol_escape_htmltag(getDolGlobalString('MAIN_LOGOUT_GOTO_URL', DOL_MAIN_URL_ROOT . "/index.php")).'" disabled></td></tr>';
print '</td></tr>' . "\n";

print '</table>'."\n";
print '</div>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print '<br>';

llxFooter();
$db->close();
