<?php
/* Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Juanjo Menent 		<jmenent@2byte.es>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("users", "admin", "other"));

if (!$user->admin)
	accessforbidden();

$action = GETPOST('action', 'alpha');



/*
 * Actions
 */

if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
{
	$code = $reg[1];
	$value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
} elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

elseif ($action == 'updateform')
{
	$res1 = dolibarr_set_const($db, "MAIN_APPLICATION_TITLE", GETPOST("MAIN_APPLICATION_TITLE", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, "MAIN_SESSION_TIMEOUT", GETPOST("MAIN_SESSION_TIMEOUT", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	if ($res1 && $res2) setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
}



/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Miscellaneous"), $wikihelp);

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("MiscellaneousDesc")."</span><br>\n";
print "<br>\n";



print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateform">';

$head = security_prepare_head();

dol_fiche_head($head, 'misc', $langs->trans("Security"), -1);


// Other Options
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td class="right" width="100">'.$langs->trans("Status").'</td>';
print '</tr>';

// Enable Captcha code
print '<tr class="oddeven">';
print '<td colspan="3">'.$langs->trans("UseCaptchaCode").'</td>';
print '<td class="right">';
if (function_exists("imagecreatefrompng"))
{
	if (!empty($conf->use_javascript_ajax))
	{
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA');
	}
	else
	{
		if (empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
}
else
{
    $desc = $form->textwithpicto('', $langs->transnoentities("EnableGDLibraryDesc"), 1, 'warning');
    print $desc;
}
print '</td></tr>';

// Enable advanced perms
print '<tr class="oddeven">';
print '<td colspan="3">'.$langs->trans("UseAdvancedPerms").'</td>';
print '<td class="right">';
if (!empty($conf->use_javascript_ajax))
{
	print ajax_constantonoff('MAIN_USE_ADVANCED_PERMS');
}
else
{
	if (empty($conf->global->MAIN_USE_ADVANCED_PERMS))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_USE_ADVANCED_PERMS">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_USE_ADVANCED_PERMS">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print "</td></tr>";

print '</table>';


print '<br>';


// Timeout
print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


$sessiontimeout = ini_get("session.gc_maxlifetime");
if (empty($conf->global->MAIN_SESSION_TIMEOUT)) $conf->global->MAIN_SESSION_TIMEOUT = $sessiontimeout;
print '<tr class="oddeven">';
print '<td>'.$langs->trans("SessionTimeOut").'</td><td class="right">';
if (ini_get("session.gc_probability") == 0) {
	print $form->textwithpicto('', $langs->trans("SessionsPurgedByExternalSystem", ini_get("session.gc_maxlifetime")));
} else {
	print $form->textwithpicto('', $langs->trans("SessionExplanation", ini_get("session.gc_probability"), ini_get("session.gc_divisor"), ini_get("session.gc_maxlifetime")));
}
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_SESSION_TIMEOUT" type="text" size="6" value="'.htmlentities($conf->global->MAIN_SESSION_TIMEOUT).'"> '.strtolower($langs->trans("Seconds"));
print '</td>';
print '</tr>';


if (empty($conf->global->MAIN_APPLICATION_TITLE)) $conf->global->MAIN_APPLICATION_TITLE = "";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("MAIN_APPLICATION_TITLE").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_APPLICATION_TITLE" type="text" size="20" value="'.htmlentities($conf->global->MAIN_APPLICATION_TITLE).'"> ';
print '</td>';
print '</tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
