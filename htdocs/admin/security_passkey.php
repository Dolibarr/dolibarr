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
 *	    \file       htdocs/admin/security_passkey.php
 *		\ingroup    core
 *		\brief      Page to check and setup the authentication mode passkey (WebAuthn)
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_authentication
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/passkey.lib.php';

$langs->loadLangs(array("users", "admin", "other", "errors"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 *	Actions
 */

if ($action == 'set') {
	$rpid = strtolower(trim(GETPOST('MAIN_AUTHENTICATION_PASSKEY_RPID', 'alphanohtml')));
	if ($rpid !== '' && (!preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/', $rpid) || filter_var($rpid, FILTER_VALIDATE_IP) !== false)) {
		setEventMessages($langs->trans("ErrorBadValueForParameter", $rpid, $langs->transnoentitiesnoconv("PasskeyRpId")), null, 'errors');
	} else {
		if ($rpid === '') {
			$res = dolibarr_del_const($db, 'MAIN_AUTHENTICATION_PASSKEY_RPID', 0);
		} else {
			$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_PASSKEY_RPID', $rpid, 'chaine', 0, '', 0);
		}
		if ($res > 0) {
			$res = dolibarr_set_const($db, 'MAIN_AUTHENTICATION_PASSKEY_REQUIRED', (string) min(2, max(0, GETPOSTINT('MAIN_AUTHENTICATION_PASSKEY_REQUIRED'))), 'chaine', 0, '', $conf->entity);
		}
		if ($res > 0) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}
	}
}


/*
 *	View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Passkeys"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-security_passkey');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<div class="info">'.$langs->trans("PasskeySetupDesc").'</div>'."\n";

$head = security_prepare_head();

print dol_get_fiche_head($head, 'passkey', '', -1);
print dol_get_fiche_end();

// Status of the setup
if (!passkeyIsEnabled() && passkeyIsAvailable()) {
	print info_admin($langs->trans("PasskeyModeNotEnabled", 'passkey', '$dolibarr_main_authentication').'<br>'.$langs->trans("PasskeyModeHowToEnable", "\$dolibarr_main_authentication='".dol_escape_htmltag($dolibarr_main_authentication).",passkey';"), 0, 0, 'warning', '');
} else {
	$setuperror = passkeyCheckSetup();
	if ($setuperror) {
		print info_admin($setuperror, 0, 0, 'error', '');
	} else {
		print info_admin($langs->trans("PasskeySetupOk", 'conf.php', 'dolibarr_main_authentication').' : <b>'.dol_escape_htmltag($dolibarr_main_authentication).'</b>', 0, 0, '1', '');
	}
}

// A Permissions-Policy can forbid the use of passkeys
$pp = defined('MAIN_SECURITY_FORCEPP') ? constant('MAIN_SECURITY_FORCEPP') : getDolGlobalString('MAIN_SECURITY_FORCEPP');
if (preg_match('/publickey-credentials-(get|create)\s*=\s*\(\s*\)/i', $pp)) {
	print info_admin($langs->trans("PasskeyErrorPermissionsPolicy", 'MAIN_SECURITY_FORCEPP', 'publickey-credentials-get=(self), publickey-credentials-create=(self)'), 0, 0, 'warning', '');
}

print '<br>';

print '<form method="post" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable noborder liste nobottomiftotal">';
print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans("Parameters").'</th>'."\n";
print '<th class="liste_titre">'.$langs->trans("Value").'</th>'."\n";
print "</tr>\n";

// Origin, from the conf.php file
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("PasskeyOrigin"), $langs->trans("PasskeyOriginHelp", '$dolibarr_main_url_root')).'</td>';
print '<td>'.dol_escape_htmltag(passkeyGetOrigin()).'</td>';
print '</tr>';

// Relying party ID
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("PasskeyRpId"), $langs->trans("PasskeyRpIdHelp")).'</td>';
print '<td>';
print '<input type="text" name="MAIN_AUTHENTICATION_PASSKEY_RPID" class="minwidth300" value="'.dolPrintHTMLForAttribute(getDolGlobalString('MAIN_AUTHENTICATION_PASSKEY_RPID')).'" placeholder="'.dolPrintHTMLForAttribute(strtolower((string) parse_url(passkeyGetOrigin(), PHP_URL_HOST))).'">';
print '</td>';
print '</tr>';

// Users that must log in with a passkey
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("PasskeyRequiredFor"), $langs->trans("PasskeyRequiredHelp")).'</td>';
print '<td>';
$arrayofvalues = array(0 => $langs->trans("PasskeyRequiredNobody"), 1 => $langs->trans("PasskeyRequiredAdmins"), 2 => $langs->trans("PasskeyRequiredAllUsers"));
print $form->selectarray('MAIN_AUTHENTICATION_PASSKEY_REQUIRED', $arrayofvalues, getDolGlobalInt('MAIN_AUTHENTICATION_PASSKEY_REQUIRED'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
print '</td>';
print '</tr>';

// Requirements
print '<tr class="oddeven">';
print '<td>'.$langs->trans("PasskeyRequirements").'</td>';
print '<td>';
print 'PHP '.PHP_VERSION.' '.(PHP_VERSION_ID >= 80000 ? img_picto('', 'tick') : img_warning());
print ' &nbsp; openssl '.(extension_loaded('openssl') ? img_picto('', 'tick') : img_warning());
print ' &nbsp; sodium '.(function_exists('sodium_crypto_sign_verify_detached') ? img_picto('', 'tick') : '<span class="opacitymedium">('.$langs->trans("Optional").')</span>');
print '<br><span class="opacitymedium">'.$langs->trans("PasskeyRequirementCertificate").'</span>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';

// End of page
llxFooter();
$db->close();
