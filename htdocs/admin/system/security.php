<?php
/* Copyright (C) 2013-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *  \file       htdocs/admin/system/security.php
 *  \brief      Page to show Security information
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

if (!$user->admin)
	accessforbidden();

if (GETPOST('action', 'aZ09') == 'donothing')
{
	exit;
}


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("Security"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("YouMayFindSecurityAdviceHere", 'hhttps://wiki.dolibarr.org/index.php/Security_information').'</span> (<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Reload").'</a>)<br>';
print '<br>';

print load_fiche_titre($langs->trans("PHPSetup"), '', 'folder');

// Get version of PHP
$phpversion = version_php();
print "<strong>PHP</strong> - ".$langs->trans("Version").": ".$phpversion."<br>\n";

// Get versionof web server
print "<br><strong>Web server</strong> - ".$langs->trans("Version").": ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
print '<br>';

print "<strong>PHP safe_mode</strong> = ".(ini_get('safe_mode') ? ini_get('safe_mode') : yn(0))."<br>\n";
print "<strong>PHP open_basedir</strong> = ".(ini_get('open_basedir') ? ini_get('open_basedir') : yn(0))."<br>\n";
print '<br>';

// XDebug
print '<strong>'.$langs->trans("XDebug").'</strong>: ';
$test = !function_exists('xdebug_is_enabled');
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
else {
	print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("XDebug"));
	print ' - '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php">XDebug admin page</a>';
}
print '<br>';

print '<br>';
print load_fiche_titre($langs->trans("ConfigurationFile"), '', 'folder');

print '<strong>'.$langs->trans("dolibarr_main_prod").'</strong>: '.$dolibarr_main_prod;
if (empty($dolibarr_main_prod)) {
	print ' &nbsp; '.img_picto('', 'warning').' '.$langs->trans("IfYouAreOnAProductionSetThis", 1);
}
print '<br>';

print '<strong>'.$langs->trans("dolibarr_nocsrfcheck").'</strong>: '.$dolibarr_nocsrfcheck;
if (!empty($dolibarr_nocsrfcheck)) {
	print img_picto('', 'warning').' &nbsp;  '.$langs->trans("IfYouAreOnAProductionSetThis", 0);
}
print '<br>';

print '<strong>'.$langs->trans("dolibarr_main_restrict_ip").'</strong>: '.$dolibarr_main_restrict_ip;
/*if (empty($dolibarr_main_restrict_ip)) {
	print ' &nbsp; '.img_picto('', 'warning').' '.$langs->trans("IfYouAreOnAProductionSetThis", 1);
}*/
print '<br>';

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("Permissions"), '', 'folder');

print '<strong>'.$langs->trans("PermissionsOnFilesInWebRoot").'</strong>: ';
// TODO Check permission are read only except for custom dir
print 'TODO';
print '<br>';

print '<strong>'.$langs->trans("PermissionsOnFile", $conffile).'</strong>: ';		// $conffile is defined into filefunc.inc.php
$perms = fileperms($dolibarr_main_document_root.'/'.$conffile);
if ($perms) {
	if (($perms & 0x0004) || ($perms & 0x0002)) {
		print img_warning().' '.$langs->trans("ConfFileIsReadableOrWritableByAnyUsers");
	} else {
		print img_picto('', 'tick');
	}
} else {
	print img_warning().' '.$langs->trans("FailedToReadFile", $conffile);
}
print '<br>';

print '<br>';

print '<br>';
print load_fiche_titre($langs->trans("Modules"), '', 'folder');

// Module log
print '<strong>'.$langs->trans("Syslog").'</strong>: ';
$test = empty($conf->syslog->enabled);
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
else {
	print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("Syslog"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

// Module debugbar
print '<strong>'.$langs->trans("DebugBar").'</strong>: ';
$test = empty($conf->debugbar->enabled);
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
else {
	print img_picto('', 'error').' '.$langs->trans("ModuleActivatedDoNotUseInProduction", $langs->transnoentities("DebugBar"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("Menu").' '.$langs->trans("SecuritySetup"), '', 'folder');

//print '<strong>'.$langs->trans("PasswordEncryption").'</strong>: ';
print '<strong>MAIN_SECURITY_HASH_ALGO</strong> = '.(empty($conf->global->MAIN_SECURITY_HASH_ALGO) ? $langs->trans("Undefined") : '')." &nbsp; ";
print '<span class="opacitymedium"> &nbsp; If unset: \'md5\'</span> ';
print '<span class="opacitymedium"> - Recommanded value: \'password_hash\'</span><br>';
print '<strong>MAIN_SECURITY_SALT</strong> = '.(empty($conf->global->MAIN_SECURITY_SALT) ? $langs->trans("Undefined") : '').'<br>';
print '<br>';
// TODO

print '<strong>'.$langs->trans("AntivirusEnabledOnUpload").'</strong>: ';
print empty($conf->global->MAIN_ANTIVIRUS_COMMAND) ? '' : img_picto('', 'tick').' ';
print yn($conf->global->MAIN_ANTIVIRUS_COMMAND ? 1 : 0);
if (!empty($conf->global->MAIN_ANTIVIRUS_COMMAND)) {
	print ' &nbsp; - '.$conf->global->MAIN_ANTIVIRUS_COMMAND;
	if (defined('MAIN_ANTIVIRUS_COMMAND')) {
		print ' - <span class="opacitymedium">'.$langs->trans("ValueIsForcedBySystem").'</span>';
	}
}
print '<br>';

print '<br>';

$securityevent = new Events($db);
$eventstolog = $securityevent->eventstolog;

print '<strong>'.$langs->trans("LogEvents").'</strong>: ';
// Loop on each event type
foreach ($eventstolog as $key => $arr)
{
	if ($arr['id'])
	{
		$key = 'MAIN_LOGEVENTS_'.$arr['id'];
		$value = empty($conf->global->$key) ? '' : $conf->global->$key;
		if ($value) print $key.', ';
	}
}








// End of page
llxFooter();
$db->close();
