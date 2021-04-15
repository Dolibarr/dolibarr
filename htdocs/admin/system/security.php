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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

if (!$user->admin) {
	accessforbidden();
}

if (GETPOST('action', 'aZ09') == 'donothing') {
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
print "<strong>PHP</strong>: ".$langs->trans("Version").": ".$phpversion;
if (function_exists('php_ini_loaded_file')) {
	$inipath = php_ini_loaded_file();
	print " - <strong>INI</strong>: ".$inipath;
}
print "<br>\n";

// Get versionof web server
print "<br><strong>Web server</strong> - ".$langs->trans("Version").": ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
print '<br>';

print "<strong>PHP safe_mode</strong> = ".(ini_get('safe_mode') ? ini_get('safe_mode') : yn(0)).' &nbsp; <span class="opacitymedium">'.$langs->trans("Deprecated")." (removed in PHP 5.4)</span><br>\n";
print "<strong>PHP open_basedir</strong> = ".(ini_get('open_basedir') ? ini_get('open_basedir') : yn(0).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("ARestrictedPath")).')</span>')."<br>\n";
print "<strong>PHP allow_url_fopen</strong> = ".(ini_get('allow_url_fopen') ? img_picto($langs->trans("YouShouldSetThisToOff"), 'warning').' '.ini_get('allow_url_fopen') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).")</span><br>\n";
print "<strong>PHP allow_url_include</strong> = ".(ini_get('allow_url_include') ? img_picto($langs->trans("YouShouldSetThisToOff"), 'warning').' '.ini_get('allow_url_include') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).")</span><br>\n";
print "<strong>PHP disable_functions</strong> = ";
$arrayoffunctionsdisabled = explode(',', ini_get('disable_functions'));
$arrayoffunctionstodisable = explode(',', 'pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_get_handler,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,pcntl_async_signals');
$arrayoffunctionstodisable2 = explode(',', 'exec,passthru,shell_exec,system,proc_open,popen');
$i = 0;
foreach ($arrayoffunctionsdisabled as $functionkey) {
	if ($i > 0) {
		print ', ';
	}
	print '<span class="opacitymedium">'.$functionkey.'</span>';
	$i++;
}
print "<br>\n";
$todisabletext = '';
$i = 0;
foreach ($arrayoffunctionstodisable as $functiontodisable) {
	if (! in_array($functiontodisable, $arrayoffunctionsdisabled)) {
		if ($i > 0) {
			$todisabletext .= ', ';
		}
		$todisabletext .= img_picto($langs->trans("YouShouldSetThisToOff"), 'warning').' <span class="opacitymedium">'.$functiontodisable.'</span>';
		$i++;
	}
}
if ($todisabletext) {
	print $langs->trans("YouShouldDisablePHPFunctions").': '.$todisabletext;
	print '<br>';
}
$todisabletext = '';
$i = 0;
foreach ($arrayoffunctionstodisable2 as $functiontodisable) {
	if (! in_array($functiontodisable, $arrayoffunctionsdisabled)) {
		if ($i > 0) {
			$todisabletext .= ', ';
		}
		$todisabletext .= img_picto($langs->trans("YouShouldSetThisToOff"), 'warning').' <span class="opacitymedium">'.$functiontodisable.'</span>';
		$i++;
	}
}
if ($todisabletext) {
	print $langs->trans("IfCLINotRequiredYouShouldDisablePHPFunctions").': '.$todisabletext;
	print '<br>';
}

print '<br>';

// XDebug
print '<strong>'.$langs->trans("XDebug").'</strong>: ';
$test = !function_exists('xdebug_is_enabled');
if ($test) {
	print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("XDebug"));
	print ' - '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php">XDebug admin page</a>';
}
print '<br>';

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("ConfigurationFile").' ('.$conffile.')', '', 'folder');

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
print '<br>';
print load_fiche_titre($langs->trans("PermissionsOnFiles"), '', 'folder');

print '<strong>'.$langs->trans("PermissionsOnFilesInWebRoot").'</strong>: ';
$arrayoffilesinroot = dol_dir_list(DOL_DOCUMENT_ROOT, 'all', 1, '', array('\/custom'), 'name', SORT_ASC, 4, 1, '', 1);
$fileswithwritepermission = array();
foreach ($arrayoffilesinroot as $fileinroot) {
	// Test permission on file
	if ($fileinroot['perm'] & 0222) {
		$fileswithwritepermission[] = $fileinroot['relativename'];
	}
}
if (empty($fileswithwritepermission)) {
	print img_picto('', 'tick').' '.$langs->trans("NoWritableFilesFoundIntoRootDir");
} else {
	print img_warning().' '.$langs->trans("SomeFilesOrDirInRootAreWritable");
	print '<br>'.$langs->trans("Example").': ';
	$i = 0;
	foreach ($fileswithwritepermission as $filewithwritepermission) {
		if ($i > 0) {
			print ', ';
		}
		print '<span class="opacitymedium">'.$filewithwritepermission.'</span>';
		if ($i > 20) {
			print ' ...';
			break;
		}
		$i++;
	}
}
print '<br>';

print '<strong>'.$langs->trans("PermissionsOnFile", $conffile).'</strong>: ';		// $conffile is defined into filefunc.inc.php
$perms = fileperms($dolibarr_main_document_root.'/'.$conffile);
if ($perms) {
	if (($perms & 0x0004) || ($perms & 0x0002)) {
		print img_warning().' '.$langs->trans("ConfFileIsReadableOrWritableByAnyUsers");
		// Web user group by default
		$labeluser = dol_getwebuser('user');
		$labelgroup = dol_getwebuser('group');
		print ' '.$langs->trans("User").': '.$labeluser.':'.$labelgroup;
		if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
			$arrayofinfoofuser = posix_getpwuid(posix_geteuid());
			print ' <span class="opacitymedium">(POSIX '.$arrayofinfoofuser['name'].':'.$arrayofinfoofuser['gecos'].':'.$arrayofinfoofuser['dir'].':'.$arrayofinfoofuser['shell'].')</span>';
		}
	} else {
		print img_picto('', 'tick');
	}
} else {
	print img_warning().' '.$langs->trans("FailedToReadFile", $conffile);
}
print '<br>';

print '<br>';

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("Modules"), '', 'folder');

// Module log
print '<strong>'.$langs->trans("Syslog").'</strong>: ';
$test = empty($conf->syslog->enabled);
if ($test) {
	print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	if ($conf->global->SYSLOG_LEVEL > LOG_NOTICE) {
		print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("Syslog"));
	} else {
		print img_picto('', 'tick.png').' '.$langs->trans("ModuleSyslogActivatedButLevelNotTooVerbose", $langs->transnoentities("Syslog"), $conf->global->SYSLOG_LEVEL);
	}
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

// Module debugbar
print '<strong>'.$langs->trans("DebugBar").'</strong>: ';
$test = empty($conf->debugbar->enabled);
if ($test) {
	print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	print img_picto('', 'error').' '.$langs->trans("ModuleActivatedDoNotUseInProduction", $langs->transnoentities("DebugBar"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

print '<br>';
print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("Menu").' '.$langs->trans("SecuritySetup"), '', 'folder');

//print '<strong>'.$langs->trans("PasswordEncryption").'</strong>: ';
print '<strong>MAIN_SECURITY_HASH_ALGO</strong> = '.(empty($conf->global->MAIN_SECURITY_HASH_ALGO) ? $langs->trans("Undefined") : '')." &nbsp; ";
print '<span class="opacitymedium"> &nbsp; &nbsp; If unset: \'md5\'</span><br>';
if ($conf->global->MAIN_SECURITY_HASH_ALGO != 'password_hash') {
	print '<strong>MAIN_SECURITY_SALT</strong> = '.(empty($conf->global->MAIN_SECURITY_SALT) ? $langs->trans("Undefined") : $conf->global->MAIN_SECURITY_SALT).'<br>';
}
if ($conf->global->MAIN_SECURITY_HASH_ALGO != 'password_hash') {
	print '<div class="info">The recommanded value for MAIN_SECURITY_HASH_ALGO is now \'password_hash\' but setting it now will make ALL existing passwords of all users not valid, so update is not possible.<br>';
	print 'If you really want to switch, you must:<br>';
	print '- Go on home - setup - other and add constant MAIN_SECURITY_HASH_ALGO to value \'password_hash\'<br>';
	print '- In same session, WITHOUT LOGGING OUT, go into your admin user record and set a new password<br>';
	print '- You can now logout and login with this new password. You must now reset password of all other users.<br>';
	print '</div><br>';
}
print '<br>';

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

print '<strong>'.$langs->trans("AuditedSecurityEvents").'</strong>: ';
if (!empty($eventstolog) && is_array($eventstolog)) {
	// Loop on each event type
	$i = 0;
	foreach ($eventstolog as $key => $arr) {
		if ($arr['id']) {
			$key = 'MAIN_LOGEVENTS_'.$arr['id'];
			$value = empty($conf->global->$key) ? '' : $conf->global->$key;
			if ($value) {
				if ($i > 0) {
					print ', ';
				}
				print '<span class="opacitymedium">'.$key.'</span>';
				$i++;
			}
		}
	}
} else {
	print img_warning().' '.$langs->trans("NoSecurityEventsAreAduited", $langs->transnoentities("Home").' - '.$langs->transnoentities("Setup").' - '.$langs->transnoentities("Audit"));
}


// End of page
llxFooter();
$db->close();
