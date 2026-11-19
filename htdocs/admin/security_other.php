<?php
/* Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Juanjo Menent 		<jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/security_other.php
 *      \ingroup    core
 *      \brief      Security options setup
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("users", "admin", "other"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'updateform') {
	$res1 = 1;
	$res2 = 1;
	$res3 = 1;
	$res4 = 1;
	$res5 = 1;
	$res6 = 1;
	if (GETPOSTISSET('MAIN_SESSION_TIMEOUT')) {
		$res2 = dolibarr_set_const($db, "MAIN_SESSION_TIMEOUT", max(120, min(3600 * 24, GETPOSTINT("MAIN_SESSION_TIMEOUT"))), 'chaine', 0, '', $conf->entity);	// Between 120 and 86400
	}
	if (GETPOSTISSET('MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT')) {
		$res3 = dolibarr_set_const($db, "MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT", GETPOST("MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT", 'alphanohtml'), 'int', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS')) {
		$res4 = dolibarr_set_const($db, "MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", GETPOST("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 'alphanohtml'), 'int', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS')) {
		$res5 = dolibarr_set_const($db, "MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS", GETPOST("MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS", 'alphanohtml'), 'int', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH')) {
		$res6 = dolibarr_set_const($db, "MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH", GETPOST("MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH", 'alphanohtml'), 'int', 0, '', $conf->entity);
	}
	$res7 = 1;
	if (GETPOSTISSET('totprequiredgroupsshown')) {	// A multiselect with nothing selected posts nothing, so the hidden field tells the list was in the form
		$groupids = array_filter(array_map('intval', GETPOST('MAIN_SECURITY_TOTP_REQUIRED_FOR_GROUPS', 'array:int')));
		if (!empty($groupids)) {
			$res7 = dolibarr_set_const($db, 'MAIN_SECURITY_TOTP_REQUIRED_FOR_GROUPS', implode(',', $groupids), 'chaine', 0, '', $conf->entity);
		} else {
			$res7 = (dolibarr_del_const($db, 'MAIN_SECURITY_TOTP_REQUIRED_FOR_GROUPS', $conf->entity) >= 0 ? 1 : 0);
		}
	}
	if ($res1 && $res2 && $res3 && $res4 && $res5 && $res6 && $res7 > 0) {
		setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
	}
}



/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Miscellaneous"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-security_other');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<div class="info">'.$langs->trans("MiscellaneousDesc")."</div>\n";



print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateform">';

$head = security_prepare_head();

print dol_get_fiche_head($head, 'misc', '', -1);

print '<br>';

// Other Options
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td class="right" width="100">'.$langs->trans("Status").'</td>';
print '</tr>';

// Enable advanced perms
print '<tr class="oddeven">';
print '<td colspan="3">'.$langs->trans("UseAdvancedPerms").'</td>';
print '<td class="right">';
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('MAIN_USE_ADVANCED_PERMS');
} else {
	if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_USE_ADVANCED_PERMS&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_USE_ADVANCED_PERMS&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print "</td></tr>";

print '</table>';


print '<br>';


// Two-factor authentication
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("2FA").'</td>';
print '<td class="right" width="100">'.$langs->trans("Status").'</td>';
print '</tr>';

$totpconsts = array('MAIN_SECURITY_ALLOW_TOTP' => 'TOTPAllow', 'MAIN_SECURITY_TOTP_REQUIRED_FOR_INTERNAL' => 'TOTPRequiredForInternal', 'MAIN_SECURITY_TOTP_REQUIRED_FOR_EXTERNAL' => 'TOTPRequiredForExternal');
foreach ($totpconsts as $constname => $label) {
	if ($constname != 'MAIN_SECURITY_ALLOW_TOTP' && !getDolGlobalString('MAIN_SECURITY_ALLOW_TOTP')) {
		continue;	// A TOTP can be required only once users can set one up
	}
	print '<tr class="oddeven">';
	print '<td colspan="3">'.$langs->trans($label).'</td>';
	print '<td class="right">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff($constname, array(), null, 0, 0, 1);	// The page is reloaded, so the lines that depend on the option follow it
	} elseif (!getDolGlobalString($constname)) {
		print '<a href="'.dolPrintHTMLForAttributeUrl($_SERVER['PHP_SELF'].'?action=set_'.$constname.'&token='.newToken()).'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.dolPrintHTMLForAttributeUrl($_SERVER['PHP_SELF'].'?action=del_'.$constname.'&token='.newToken()).'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
	print '</td></tr>';
}

if (getDolGlobalString('MAIN_SECURITY_ALLOW_TOTP')) {
	print '<tr class="oddeven">';
	print '<td colspan="2">'.$langs->trans("TOTPRequiredForGroups").'</td>';
	print '<td colspan="2" class="right">';
	print '<input type="hidden" name="totprequiredgroupsshown" value="1">';
	$selectedgroups = array_filter(array_map('intval', explode(',', getDolGlobalString('MAIN_SECURITY_TOTP_REQUIRED_FOR_GROUPS'))));
	print $form->select_dolgroups($selectedgroups, 'MAIN_SECURITY_TOTP_REQUIRED_FOR_GROUPS', 0, '', 0, '', array(), '0', true, 'minwidth300');
	print '</td></tr>';
}

print '</table>';

if (getDolGlobalString('MAIN_SECURITY_ALLOW_TOTP')) {
	print '<div class="opacitymedium margintoponly">'.$langs->trans("TOTPRequiredDesc").'</div>';
	if (strpos(dolEncrypt('totp'), 'dolcrypt:') !== 0) {	// Without it no TOTP can be saved, and dolTotpIsRequired() requires none
		print info_admin($langs->trans("TOTPNoEncryptionKey"), 0, 0, 'warning');
	}
}


print '<br>';


// Timeout
print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td></td>';
print "</tr>\n";


$sessiontimeout = ini_get("session.gc_maxlifetime");
if (!getDolGlobalString('MAIN_SESSION_TIMEOUT')) {
	$conf->global->MAIN_SESSION_TIMEOUT = $sessiontimeout;
}
print '<tr class="oddeven">';
print '<td>'.$langs->trans("SessionTimeOut").'</td><td class="right">';
if (ini_get("session.gc_probability") == 0) {
	// For external cleaning of session, the delay used may be the one into the ini file, so get_cfg_var("session.gc_maxlifetime"), not the one overloaded in runtime.
	print $form->textwithpicto('', $langs->trans("SessionsPurgedByExternalSystem", get_cfg_var("session.gc_maxlifetime")));
} else {
	print $form->textwithpicto('', $langs->trans("SessionExplanation", ini_get("session.gc_probability"), ini_get("session.gc_divisor"), ini_get("session.gc_maxlifetime")));
}
print '</td>';
print '<td class="nowrap">';
print '<input class="flat right width75" name="MAIN_SESSION_TIMEOUT" type="text" value="'.getDolGlobalInt('MAIN_SESSION_TIMEOUT').'"> '.strtolower($langs->trans("Seconds"));
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("MaxNumberOfImagesInGetPost").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat right width50" name="MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT" type="text" value="'.getDolGlobalInt('MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT').'"> '.strtolower($langs->trans("Images"));
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("MaxNumberOfPostOnPublicPagesByIP").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat right width50" name="MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS" type="text" value="'.getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200).'"> '.strtolower($langs->trans("Posts"));
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("MaxNumberOfAttachementOnForms").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat right width50" name="MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS" type="text" value="'.getDolGlobalInt("MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS", 10).'"> '.strtolower($langs->trans("Files"));
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("MaxNumberOfFailedAuth"), $langs->trans("MaxNumberOfFailedAuthHelp", 'USER_LOGIN_FAILED', $langs->transnoentitiesnoconv("Audit"))).'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat right width50" name="MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH" type="text" value="'.getDolGlobalInt("MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH", 100).'"> '.$langs->trans("FailedAuth");
print '</td>';
print '</tr>';

print '</table>';

print $form->buttonsSaveCancel("Modify", '');

print dol_get_fiche_end();

print '</form>';

// End of page
llxFooter();
$db->close();
