<?php
/* Copyright (C) 2023		Maximilien Rozniecki	<mrozniecki@easya.solutions>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/openid_connect.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_authentication
 */

$langs->loadLangs(array("users", "admin", "other"));

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

	$openid_url_img = GETPOST('MAIN_AUTHENTICATION_OPENID_URL_IMG', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OPENID_URL_IMG', $openid_url_img, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP', 'int');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP', $value, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOSTINT('MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR', $value, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME', $value, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME', $value, 'chaine', 0, '', 0);
	if (!$res > 0) {
		$errors[] = $db->lasterror();
		$error++;
	}

	$value = GETPOST('MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL', 'alpha');
	$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL', $value, 'chaine', 0, '', 0);
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

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Miscellaneous"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-security_other');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">' . $langs->trans("OpenIDDesc") . "</span><br>\n";
print "<br>\n";

$head = security_prepare_head();

print dol_get_fiche_head($head, 'openid', '', -1);

$urlforwikidoc = img_picto('', 'url', 'class="pictofixedwidth"') . '<a target="_blank" href="https://wiki.dolibarr.org/index.php?title=Authentication,_SSO_and_SSL#Mode_openid_connect">';
$urlforwikidoc .= $langs->trans("SeeHere");
$urlforwikidoc .= '</a>';
/*
print $langs->trans("SeeWikiDocForHelpInSetupOpenIDCOnnect");
print ' - ';
print $urlforwikidoc;
*/
print dol_get_fiche_end();


print $langs->trans("EnableOpenIDConnectAuthentication");
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('MAIN_AUTHENTICATION_OIDC_ON', array(), null, 0, 0, 1);
} else {
	if (!getDolGlobalString('MAIN_AUTHENTICATION_OIDC_ON')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_AUTHENTICATION_OIDC_ON&token=' . newToken() . '">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_AUTHENTICATION_OIDC_ON&token=' . newToken() . '">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}

print '<br><br>';


if (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_ON')) {
	if (!preg_match('/openid_connect/', $dolibarr_main_authentication)) {
		$langs->load("errors");
		print info_admin($langs->trans("ErrorOpenIDSetupConfNotComplete") . ':  ' . $urlforwikidoc, 0, 0, 1, 'warning');
	} else {
		print info_admin('In conf.php file: dolibarr_main_authentication is ' . $dolibarr_main_authentication);
	}

	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="tagtable noborder liste nobottomiftotal">';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre" colspan="3">' . $langs->trans("Parameters") . '</th>' . "\n";
	print "</tr>\n";

	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcAutofillWithWellknowUrl") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="oidc_wellknow_url" id="oidc_wellknow_url" class="minwidth400 centpercent" value="">';
	print '</td><td>' . "\n";
	print '<input type="button" class="button smallpaddingimp reposition" id="oidc_wellknow_populate" value="'.$langs->trans("MainAuthenticationOidcAutofillButton").'"';
	print '</td></tr>' . "\n";
	print '</table>' . "\n";
	print '</div>';

	print '<br>';

	print '<form method="post" action="' . dolBuildUrl($_SERVER["PHP_SELF"]) . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="set">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="tagtable noborder liste nobottomiftotal">';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre">' . $langs->trans("Parameters") . '</th>' . "\n";
	print '<th class="liste_titre"></th>' . "\n";
	print '<th class="liste_titre"></th>' . "\n";
	print "</tr>\n";

	// MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcLoginClaimName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcLoginClaimDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM" id="MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM') ? GETPOST('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_CLIENT_ID
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcClientIdName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcClientIdDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_CLIENT_ID" id="MAIN_AUTHENTICATION_OIDC_CLIENT_ID" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLIENT_ID') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLIENT_ID', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLIENT_ID') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLIENT_ID") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcClientSecretName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcClientSecretDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input type="password" name="MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET" id="MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_SCOPES
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcScopesName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcScopesDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_SCOPES" id="MAIN_AUTHENTICATION_OIDC_SCOPES" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_SCOPES') ? GETPOST('MAIN_AUTHENTICATION_OIDC_SCOPES', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_SCOPES') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_SCOPES") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcAuthorizeUrlName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcAuthorizeUrlDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL" id="MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_TOKEN_URL
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcTokenUrlName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcTokenUrlDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_TOKEN_URL" id="MAIN_AUTHENTICATION_OIDC_TOKEN_URL" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_TOKEN_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_TOKEN_URL', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_TOKEN_URL') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_TOKEN_URL") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_USERINFO_URL
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcUserinfoUrlName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcUserinfoUrlDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_USERINFO_URL" id="MAIN_AUTHENTICATION_OIDC_USERINFO_URL" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_USERINFO_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_USERINFO_URL', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_USERINFO_URL') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_USERINFO_URL") : ''))) . '">';
	print '</td></tr>' . "\n";

	// MAIN_AUTHENTICATION_OIDC_LOGOUT_URL
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcLogoutUrlName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcLogoutUrlDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OIDC_LOGOUT_URL" id="MAIN_AUTHENTICATION_OIDC_LOGOUT_URL" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_LOGOUT_URL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_LOGOUT_URL', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_LOGOUT_URL') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_LOGOUT_URL") : ''))) . '">';
	print '</td></tr>' . "\n";

	// REDIRECT_URL
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcRedirectUrlName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcRedirectUrlDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input class="minwidth400 centpercent" value="' . dol_escape_htmltag(openid_connect_get_redirect_url()) . '" disabled>';
	print '</td></tr>' . "\n";

	// LOGOUT_URL
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcLogoutRedirectUrlName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOidcLogoutRedirectUrlDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input class="minwidth400 centpercent" value="' . dol_escape_htmltag(getDolGlobalString('MAIN_LOGOUT_GOTO_URL', DOL_MAIN_URL_ROOT . "/index.php")) . '" disabled>';
	print '</td></tr>' . "\n";

	// OPENID_URL_IMG
	print '<tr class="oddeven">' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOpenIDUrlImgName") . '</td>' . "\n";
	print '<td>' . $langs->trans("MainAuthenticationOpenIDUrlImgDesc") . '</td>' . "\n";
	print '<td align="right">' . "\n";
	print '<input name="MAIN_AUTHENTICATION_OPENID_URL_IMG" id="MAIN_AUTHENTICATION_OPENID_URL_IMG" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OPENID_URL_IMG') ? GETPOST('MAIN_AUTHENTICATION_OPENID_URL_IMG', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OPENID_URL_IMG') ? getDolGlobalString("MAIN_AUTHENTICATION_OPENID_URL_IMG") : ''))) . '">';
	print '</td></tr>' . "\n";

	// --- User Auto-Creation Settings ---
	print '</table></div>' . "\n";

	$langs->load("errors");
	global $dolibarr_main_authentication_autocreateuser;
	if (empty($dolibarr_main_authentication_autocreateuser)) {
		print info_admin($langs->trans("OIDCAutocreateUserDisabled"), 0, 0, 1, 'warning');
	} else {
		print info_admin($langs->trans("OIDCAutocreateUserEnabled"), 0, 0, 1, 'success');
	}

	if (!empty($dolibarr_main_authentication_autocreateuser)) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="tagtable noborder liste nobottomiftotal">';
		print '<tr class="liste_titre">';
		print '<th class="liste_titre" colspan="3">' . $langs->trans("MainAuthenticationOidcAutoCreateTitle") . '</th>' . "\n";
		print "</tr>\n";

		// MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR
		$form = new Form($db);
		print '<tr class="oddeven">' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcDefaultCreatorName") . '</td>' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcDefaultCreatorDesc") . '</td>' . "\n";
		print '<td align="right">' . "\n";
		$creator_val = GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR') ? GETPOSTINT('MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR') : getDolGlobalInt('MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR');
		print $form->select_dolusers($creator_val, 'MAIN_AUTHENTICATION_OIDC_DEFAULT_CREATOR', 1, null, 0, '', '', '', 0, 0, '(admin:=:1) AND (statut:=:1)', 0, '', 'minwidth200 maxwidth500');
		print '</td></tr>' . "\n";

		// MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP
		print '<tr class="oddeven">' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcDefaultGroupName") . '</td>' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcDefaultGroupDesc") . '</td>' . "\n";
		print '<td align="right">' . "\n";
		$defaultgroup_val = GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP') ? GETPOSTINT('MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP') : getDolGlobalInt('MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP');
		$form->select_dolgroups($defaultgroup_val, 'MAIN_AUTHENTICATION_OIDC_DEFAULT_GROUP', 1);
		print '</td></tr>' . "\n";

		// MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME
		print '<tr class="oddeven">' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcClaimFirstnameName") . '</td>' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcClaimFirstnameDesc") . '</td>' . "\n";
		print '<td align="right">' . "\n";
		print '<input name="MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME" id="MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLAIM_FIRSTNAME") : ''))) . '" placeholder="given_name">';
		print '</td></tr>' . "\n";

		// MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME
		print '<tr class="oddeven">' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcClaimLastnameName") . '</td>' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcClaimLastnameDesc") . '</td>' . "\n";
		print '<td align="right">' . "\n";
		print '<input name="MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME" id="MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLAIM_LASTNAME") : ''))) . '" placeholder="family_name">';
		print '</td></tr>' . "\n";

		// MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL
		print '<tr class="oddeven">' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcClaimEmailName") . '</td>' . "\n";
		print '<td>' . $langs->trans("MainAuthenticationOidcClaimEmailDesc") . '</td>' . "\n";
		print '<td align="right">' . "\n";
		print '<input name="MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL" id="MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL" class="minwidth400 centpercent" value="' . dol_escape_htmltag((GETPOSTISSET('MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL') ? GETPOST('MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL', 'nohtml') : (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL') ? getDolGlobalString("MAIN_AUTHENTICATION_OIDC_CLAIM_EMAIL") : ''))) . '" placeholder="email">';
		print '</td></tr>' . "\n";

		print '</table>' . "\n";
		print '</div>';
	} // end if autocreateuser

	print '<br>';
	print '<div align="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '</div>';

	print '</form>';
}

print '<br>';

llxFooter();
$db->close();
?>
<script type="text/javascript">
	$(document).ready(function() {
		$('#oidc_wellknow_populate').on('click', function() {
			const url = $('#oidc_wellknow_url').val().trim();
			if (!url) return;

			// Ensure URL ends with /.well-known/openid-configuration
			let wellKnownUrl = url;
			if (!wellKnownUrl.endsWith('/.well-known/openid-configuration')) {
				if (!wellKnownUrl.endsWith('/')) wellKnownUrl += '/';
				wellKnownUrl += '.well-known/openid-configuration';
			}

			$.getJSON(wellKnownUrl)
				.done(function(data) {
					if (data.authorization_endpoint) {
						$('#MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL').val(data.authorization_endpoint);
					}
					if (data.token_endpoint) {
						$('#MAIN_AUTHENTICATION_OIDC_TOKEN_URL').val(data.token_endpoint);
					}
					if (data.userinfo_endpoint) {
						$('#MAIN_AUTHENTICATION_OIDC_USERINFO_URL').val(data.userinfo_endpoint);
					}
					if (data.end_session_endpoint) {
						$('#MAIN_AUTHENTICATION_OIDC_LOGOUT_URL').val(data.end_session_endpoint);
					}
					if (data.scopes_supported) {
						$('#MAIN_AUTHENTICATION_OIDC_SCOPES').val(data.scopes_supported.join(' '));
					}
				})
				.fail(function() {
					alert('Failed to fetch OIDC well-known configuration from: ' + wellKnownUrl);
				});
		});
	});
</script>
