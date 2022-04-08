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
$langs->loadLangs(array("install", "other", "admin", "errors"));

if (!$user->admin) {
	accessforbidden();
}

if (GETPOST('action', 'aZ09') == 'donothing') {
	exit;
}

$execmethod = empty($conf->global->MAIN_EXEC_USE_POPEN) ? 1 : $conf->global->MAIN_EXEC_USE_POPEN;


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

// Get version of web server
print "<br><strong>Web server - ".$langs->trans("Version")."</strong>: ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
print '<strong>'.$langs->trans("DataRootServer")."</strong>: ".DOL_DATA_ROOT."<br>\n";
// Web user group by default
$labeluser = dol_getwebuser('user');
$labelgroup = dol_getwebuser('group');
if ($labeluser && $labelgroup) {
	print '<strong>'.$langs->trans("WebUserGroup")." (env vars)</strong> : ".$labeluser.':'.$labelgroup;
	if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
		$arrayofinfoofuser = posix_getpwuid(posix_geteuid());
		print ' <span class="opacitymedium">(POSIX '.$arrayofinfoofuser['name'].':'.$arrayofinfoofuser['gecos'].':'.$arrayofinfoofuser['dir'].':'.$arrayofinfoofuser['shell'].')</span><br>'."\n";
	}
}
// Web user group real (detected by 'id' external command)
if (function_exists('exec')) {
	$arrayout = array(); $varout = 0;
	exec('id', $arrayout, $varout);
	if (empty($varout)) {	// Test command is ok. Work only on Linux OS.
		print '<strong>'.$langs->trans("WebUserGroup")." (real, 'id' command)</strong> : ".join(',', $arrayout)."<br>\n";
	}
}
print '<br>';

print "<strong>PHP session.use_strict_mode</strong> = ".(ini_get('session.use_strict_mode') ? '' : img_warning().' ').(ini_get('session.use_strict_mode') ? ini_get('session.use_strict_mode') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", '1').")</span><br>\n";
print "<strong>PHP session.use_only_cookies</strong> = ".(ini_get('session.use_only_cookies') ? '' : img_warning().' ').(ini_get('session.use_only_cookies') ? ini_get('session.use_only_cookies') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", '1').")</span><br>\n";
print "<strong>PHP session.cookie_httponly</strong> = ".(ini_get('session.cookie_httponly') ? '' : img_warning().' ').(ini_get('session.cookie_httponly') ? ini_get('session.cookie_httponly') : '').' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", '1').")</span><br>\n";
print "<strong>PHP session.cookie_samesite</strong> = ".(ini_get('session.cookie_samesite') ? ini_get('session.cookie_samesite') : 'None');
if (!ini_get('session.cookie_samesite') || ini_get('session.cookie_samesite') == 'Lax') {
	print ' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", 'Lax').")</span>";
} elseif (ini_get('session.cookie_samesite') == 'Strict') {
	print ' &nbsp; '.img_warning().' <span class="opacitymedium">'.$langs->trans("WarningPaypalPaymentNotCompatibleWithStrict")."</span>";
}
print "<br>\n";
print "<strong>PHP open_basedir</strong> = ".(ini_get('open_basedir') ? ini_get('open_basedir') : yn(0).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("ARestrictedPath").', '.$langs->transnoentitiesnoconv("Example").': '.$_SERVER["DOCUMENT_ROOT"].','.DOL_DATA_ROOT).')</span>')."<br>\n";
print "<strong>PHP short_open_tag</strong> = ".((empty(ini_get('short_open_tag')) || ini_get('short_open_tag') == 'Off') ? yn(0) : img_warning().' '.yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).')</span>'."<br>\n";
print "<strong>PHP allow_url_fopen</strong> = ".(ini_get('allow_url_fopen') ? img_picto($langs->trans("YouShouldSetThisToOff"), 'warning').' '.ini_get('allow_url_fopen') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).")</span><br>\n";
print "<strong>PHP allow_url_include</strong> = ".(ini_get('allow_url_include') ? img_picto($langs->trans("YouShouldSetThisToOff"), 'warning').' '.ini_get('allow_url_include') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).")</span><br>\n";
//print "<strong>PHP safe_mode</strong> = ".(ini_get('safe_mode') ? ini_get('safe_mode') : yn(0)).' &nbsp; <span class="opacitymedium">'.$langs->trans("Deprecated")." (removed in PHP 5.4)</span><br>\n";
print "<strong>PHP disable_functions</strong> = ";
$arrayoffunctionsdisabled = explode(',', ini_get('disable_functions'));
$arrayoffunctionstodisable = explode(',', 'pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_get_handler,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,pcntl_async_signals');
if ($execmethod == 1) {
	$arrayoffunctionstodisable2 = explode(',', 'passthru,shell_exec,system,proc_open,popen');
	$functiontokeep = 'exec';
} else {
	$arrayoffunctionstodisable2 = explode(',', 'exec,passthru,shell_exec,system,proc_open');
	$functiontokeep = 'popen';
}
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

print $langs->trans("PHPFunctionsRequiredForCLI").': ';
if (in_array($functiontokeep, $arrayoffunctionsdisabled)) {
	print img_picto($langs->trans("PHPFunctionsRequiredForCLI"), 'warning');
}
print '<span class="opacitymedium">'.$functiontokeep.'</span>';
print '<br>';

print '<br>';

// XDebug
print '<strong>'.$langs->trans("XDebug").'</strong>: ';
$test = !function_exists('xdebug_is_enabled') && !extension_loaded('xdebug');
if ($test) {
	print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("XDebug"));
	print ' - '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php">XDebug admin page</a>';
}
print '<br>';


// OS Permissions

print '<br>';
print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("OSSetup").' - '.$langs->trans("PermissionsOnFiles"), '', 'folder');

print '<strong>'.$langs->trans("PermissionsOnFilesInWebRoot").'</strong>: ';
$arrayoffilesinroot = dol_dir_list(DOL_DOCUMENT_ROOT, 'all', 1, '', array('\/custom'), 'name', SORT_ASC, 4, 1, '', 1);
$fileswithwritepermission = array();
foreach ($arrayoffilesinroot as $fileinroot) {
	// Test if there is at least one write permission file. If yes, add the entry into array $fileswithwritepermission
	if (isset($fileinroot['perm']) && ($fileinroot['perm'] & 0222)) {
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

$installlock = DOL_DATA_ROOT.'/install.lock';
print '<strong>'.$langs->trans("DolibarrSetup").'</strong>: ';
if (file_exists($installlock)) {
	print img_picto('', 'tick').' '.$langs->trans("InstallAndUpgradeLockedBy", $installlock);
} else {
	print img_warning().' '.$langs->trans("WarningLockFileDoesNotExists", DOL_DATA_ROOT);
}
print '<br>';


// File conf.php

print '<br>';
print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("ConfigurationFile").' ('.$conffile.')', '', 'folder');

print '<strong>$dolibarr_main_prod</strong>: '.($dolibarr_main_prod ? $dolibarr_main_prod : '0');
if (empty($dolibarr_main_prod)) {
	print ' &nbsp; '.img_picto('', 'warning').' '.$langs->trans("IfYouAreOnAProductionSetThis", 1);
}
print '<br>';

print '<strong>$dolibarr_nocsrfcheck</strong>: '.(empty($dolibarr_nocsrfcheck) ? '0' : $dolibarr_nocsrfcheck);
if (!empty($dolibarr_nocsrfcheck)) {
	print ' &nbsp; '.img_picto('', 'warning').' '.$langs->trans("IfYouAreOnAProductionSetThis", 0);
} else {
	print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 0)</span>';
}
print '<br>';

print '<strong>$dolibarr_main_restrict_ip</strong>: ';
if (empty($dolibarr_main_restrict_ip)) {
	print $langs->trans("None");
	//print ' <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("IPsOfUsers")).')</span>';
} else {
	print $dolibarr_main_restrict_ip;
}
print '<br>';

print '<strong>$dolibarr_main_restrict_os_commands</strong>: ';
if (empty($dolibarr_main_restrict_os_commands)) {
	print $langs->trans("None");
} else {
	print $dolibarr_main_restrict_os_commands;
}
print ' <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", 'mysqldump, mysql, pg_dump, pgrestore').')</span>';
print '<br>';

if (empty($conf->global->SECURITY_DISABLE_TEST_ON_OBFUSCATED_CONF)) {
	print '<strong>$dolibarr_main_db_pass</strong>: ';
	if (!empty($dolibarr_main_db_pass) && empty($dolibarr_main_db_encrypted_pass)) {
		print img_picto('', 'warning').' '.$langs->trans("DatabasePasswordNotObfuscated").' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("SetOptionTo", $langs->transnoentitiesnoconv("MainDbPasswordFileConfEncrypted"), yn(1)).')</span>';
		//print ' <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("IPsOfUsers")).')</span>';
	} else {
		print img_picto('', 'tick').' '.$langs->trans("DatabasePasswordObfuscated");
	}

	print '<br>';
}



// Menu security

print '<br>';
print '<br>';
print '<br>';

print load_fiche_titre($langs->trans("Menu").' '.$langs->trans("SecuritySetup"), '', 'folder');


print '<strong>'.$langs->trans("UseCaptchaCode").'</strong>: ';
print empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA) ? '' : img_picto('', 'tick').' ';
print yn(empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA) ? 0 : 1);
print '<br>';
print '<br>';


print '<strong>'.$langs->trans("AntivirusEnabledOnUpload").'</strong>: ';
print empty($conf->global->MAIN_ANTIVIRUS_COMMAND) ? '' : img_picto('', 'tick').' ';
print yn(empty($conf->global->MAIN_ANTIVIRUS_COMMAND) ? 0 : 1);
if (!empty($conf->global->MAIN_ANTIVIRUS_COMMAND)) {
	print ' &nbsp; - '.$conf->global->MAIN_ANTIVIRUS_COMMAND;
	if (defined('MAIN_ANTIVIRUS_COMMAND') && !defined('MAIN_ANTIVIRUS_BYPASS_COMMAND_AND_PARAM')) {
		print ' - <span class="opacitymedium">'.$langs->trans("ValueIsForcedBySystem").'</span>';
	}
}
print '<br>';
print '<br>';

$securityevent = new Events($db);
$eventstolog = $securityevent->eventstolog;

print '<strong>'.$langs->trans("AuditedSecurityEvents").'</strong>: ';
$out = '';
if (!empty($eventstolog) && is_array($eventstolog)) {
	// Loop on each event type
	$i = 0;
	foreach ($eventstolog as $key => $arr) {
		if ($arr['id']) {
			$key = 'MAIN_LOGEVENTS_'.$arr['id'];
			$value = empty($conf->global->$key) ? '' : $conf->global->$key;
			if ($value) {
				if ($i > 0) {
					$out .= ', ';
				}
				$out .= '<span class="opacitymedium">'.$key.'</span>';
				$i++;
			}
		}
	}
	print $out;
}

if (empty($out)) {
	print img_warning().' '.$langs->trans("NoSecurityEventsAreAduited", $langs->transnoentities("Home").' - '.$langs->transnoentities("Setup").' - '.$langs->transnoentities("Security").' - '.$langs->transnoentities("Audit")).'<br>';
}

print '<br>';


// Modules/Applications

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
		print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedWithTooHighLogLevel", $langs->transnoentities("Syslog"));
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


// APIs

print '<br>';
print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("API"), '', 'folder');

if (empty($conf->api->enabled) && empty($conf->webservices->enabled)) {
	print $langs->trans("APIsAreNotEnabled");
} else {
	if (!empty($conf->webservices->enabled)) {
		print $langs->trans('YouEnableDeprecatedWSAPIsUseRESTAPIsInstead')."<br>\n";
		print '<br>';
	}
	if (!empty($conf->api->enabled)) {
		print '<strong>API_ENDPOINT_RULES</strong> = '.(empty($conf->global->API_ENDPOINT_RULES) ? '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Example").': login:0,users:0,setup:1,status:1,tickets:1,...)</span>' : $conf->global->API_ENDPOINT_RULES)."<br>\n";
		print '<br>';
	}
}


print '<br><br>';


print '<br>';


print load_fiche_titre($langs->trans("OtherSetup"), '', 'folder');


//print '<strong>'.$langs->trans("PasswordEncryption").'</strong>: ';
print '<strong>MAIN_SECURITY_HASH_ALGO</strong> = '.(empty($conf->global->MAIN_SECURITY_HASH_ALGO) ? '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>' : $conf->global->MAIN_SECURITY_HASH_ALGO)." &nbsp; ";
if (empty($conf->global->MAIN_SECURITY_HASH_ALGO)) {
	print '<span class="opacitymedium"> &nbsp; &nbsp; If unset: \'md5\'</span>';
}
if ($conf->global->MAIN_SECURITY_HASH_ALGO != 'password_hash') {
	print '<br><strong>MAIN_SECURITY_SALT</strong> = '.(empty($conf->global->MAIN_SECURITY_SALT) ? '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>' : $conf->global->MAIN_SECURITY_SALT).'<br>';
} else {
	print '<span class="opacitymedium">('.$langs->trans("Recommended").': password_hash)</span>';
	print '<br>';
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

print '<strong>MAIN_SECURITY_ANTI_SSRF_SERVER_IP</strong> = '.(empty($conf->global->MAIN_SECURITY_ANTI_SSRF_SERVER_IP) ? '<span class="opacitymedium">'.$langs->trans("Undefined").'</span> &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': List of static IPs of server separated with coma - '.$langs->trans("Note").': common loopback ip like 127.*.*.*, [::1] are already added)</span>' : $conf->global->MAIN_SECURITY_ANTI_SSRF_SERVER_IP)."<br>";
print '<br>';

print '<strong>MAIN_ALLOW_SVG_FILES_AS_IMAGES</strong> = '.(empty($conf->global->MAIN_ALLOW_SVG_FILES_AS_IMAGES) ? '0' : $conf->global->MAIN_ALLOW_SVG_FILES_AS_IMAGES).' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 0)</span><br>';
print '<br>';

print '<strong>MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE</strong> = '.(empty($conf->global->MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE) ? '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>' : $conf->global->MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE).' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 1)</span><br>';
print '<br>';

print '<strong>MAIN_SECURITY_CSRF_WITH_TOKEN</strong> = '.(empty($conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN) ? '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>' : $conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN).' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 2)</span>'."<br>";
print '<br>';

print '<br>';
print '<br>';


print load_fiche_titre($langs->trans("OtherSetup").' ('.$langs->trans("Experimental").')', '', 'folder');

print '<strong>MAIN_RESTRICTHTML_ONLY_VALID_HTML</strong> = '.(empty($conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML) ? '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': 1)</span>' : $conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML)."<br>";
print '<br>';

print '<strong>MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES</strong> = '.(empty($conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES) ? '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': 1)</span>' : $conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES)."<br>";
print '<br>';

print '<strong>MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL</strong> = '.(empty($conf->global->MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL) ? '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 0)</span>' : $conf->global->MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL)."<br>";
print '<br>';

print '<strong>MAIN_EXEC_USE_POPEN</strong> = ';
if (empty($conf->global->MAIN_EXEC_USE_POPEN)) {
	print '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>';
} else {
	print $conf->global->MAIN_EXEC_USE_POPEN;
}
if ($execmethod == 1) {
	print '<span class="opacitymedium">, "exec" PHP method will be used for shell commands';
	print ' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 1)';
	print '</span>';
}
if ($execmethod == 2) {
	print '<span class="opacitymedium">, "popen" PHP method will be used for shell commands';
	print ' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 1)';
	print '</span>';
}
print "<br>";
print '<br>';


// End of page
llxFooter();
$db->close();
