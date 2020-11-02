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

$form = new Form($db);
$nowstring = dol_print_date(dol_now(), 'dayhourlog');

llxHeader();

print load_fiche_titre($langs->trans("Security"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("YouMayFindSecurityAdviceHere", 'hhttps://wiki.dolibarr.org/index.php/Security_information').'</span> (<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Reload").'</a>)<br>';
print '<br>';

print load_fiche_titre($langs->trans("PHPSetup"), '', '');

// Get version of PHP
$phpversion = version_php();
print "<strong>PHP</strong> - ".$langs->trans("Version").": ".$phpversion."<br>\n";

// Get versionof web server
print "<br><strong>Web server</strong> - ".$langs->trans("Version").": ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
print '<br>';

print "<strong>PHP safe_mode</strong> = ".(ini_get('safe_mode') ? ini_get('safe_mode') : yn(0))."<br>\n";
print "<strong>PHP open_basedir</strong> = ".(ini_get('open_basedir') ? ini_get('open_basedir') : yn(0))."<br>\n";
print '<br>';

print load_fiche_titre($langs->trans("ConfigFile"), '', '');

print '<strong>'.$langs->trans("dolibarr_main_prod").'</strong>: '.$dolibarr_main_prod;
// dolibarr_main_prod


print '<br>';
print '<br>';

print load_fiche_titre($langs->trans("PermissionsOnFiles"), '', '');

print '<strong>'.$langs->trans("PermissionOnFileInWebRoot").'</strong>: ';
// TODO
print 'TODO';


print '<br>';
print '<br>';


print load_fiche_titre($langs->trans("Modules"), '', '');

// XDebug
print '<strong>'.$langs->trans("XDebug").'</strong>: ';
$test = !function_exists('xdebug_is_enabled');
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled");
else {
	print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("XDebug"));
	print ' - '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php">XDebug admin page</a>';
}
print '<br>';

// Module log
print '<br>';
print '<strong>'.$langs->trans("Syslog").'</strong>: ';
$test = empty($conf->syslog->enabled);
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled");
else {
	print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("Syslog"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

// Module debugbar
print '<br>';
print '<strong>'.$langs->trans("DebugBar").'</strong>: ';
$test = empty($conf->debugbar->enabled);
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled");
else {
	print img_picto('', 'error').' '.$langs->trans("ModuleActivatedDoNotUseInProduction", $langs->transnoentities("DebugBar"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';
print '<br>';

print load_fiche_titre($langs->trans("SecuritySetup"), '', '');

//print '<strong>'.$langs->trans("PasswordEncryption").'</strong>: ';
print '<strong>MAIN_SECURITY_HASH_ALGO</strong> = '.$conf->global->MAIN_SECURITY_HASH_ALGO." &nbsp; (Recommanded value: 'password_hash')<br>";
print '<strong>MAIN_SECURITY_SALT</strong> = '.$conf->global->MAIN_SECURITY_SALT.'<br>';
print '<br>';
// TODO

print '<strong>'.$langs->trans("AntivirusEnabledOnUpload").'</strong>: ';
// TODO
print '<br>';

print '<br>';

print '<strong>'.$langs->trans("SecurityAudit").'</strong>: ';
// TODO Disabled or enabled ?
print '<br>';









// End of page
llxFooter();
$db->close();
