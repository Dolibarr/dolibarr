<?php
/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Xavier Dutoit           <doli@sydesy.com>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2015  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2008       Matteli
 * Copyright (C) 2011-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2020       Demarest Maxime         <maxime@indelog.fr>
 * Copyright (C) 2020       Charlene Benke         <charlie@patas-monkey.com>
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
 *	\file       htdocs/main.inc.php
 *	\ingroup	core
 *	\brief      File that defines environment for Dolibarr GUI pages only (file not required by scripts)
 */

//@ini_set('memory_limit', '128M');	// This may be useless if memory is hard limited by your PHP

// For optional tuning. Enabled if environment variable MAIN_SHOW_TUNING_INFO is defined.
$micro_start_time = 0;
if (!empty($_SERVER['MAIN_SHOW_TUNING_INFO']))
{
	list($usec, $sec) = explode(" ", microtime());
	$micro_start_time = ((float) $usec + (float) $sec);
	// Add Xdebug code coverage
	//define('XDEBUGCOVERAGE',1);
	if (defined('XDEBUGCOVERAGE')) {
		xdebug_start_code_coverage();
	}
}

/**
 * Security: WAF layer for SQL Injection and XSS Injection (scripts) protection (Filters on GET, POST, PHP_SELF).
 *
 * @param		string		$val		Value brut found int $_GET, $_POST or PHP_SELF
 * @param		string		$type		1=GET, 0=POST, 2=PHP_SELF, 3=GET without sql reserved keywords (the less tolerant test)
 * @return		int						>0 if there is an injection, 0 if none
 */
function testSqlAndScriptInject($val, $type)
{
	// Decode string first
	// So <svg o&#110;load='console.log(&quot;123&quot;)' become <svg onload='console.log(&quot;123&quot;)'
	// So "&colon;&apos;" become ":'" (due to ENT_HTML5)
	$val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5);

	// TODO loop to decode until no more thing to decode ?

	// We clean string because some hacks try to obfuscate evil strings by inserting non printable chars. Example: 'java(ascci09)scr(ascii00)ipt' is processed like 'javascript' (whatever is place of evil ascii char)
	// We should use dol_string_nounprintableascii but function is not yet loaded/available
	$val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val); // /u operator makes UTF8 valid characters being ignored so are not included into the replace
	// We clean html comments because some hacks try to obfuscate evil strings by inserting HTML comments. Example: on<!-- -->error=alert(1)
	$val = preg_replace('/<!--[^>]*-->/', '', $val);

	$inj = 0;
	// For SQL Injection (only GET are used to be included into bad escaped SQL requests)
	if ($type == 1 || $type == 3)
	{
		$inj += preg_match('/delete\s+from/i', $val);
		$inj += preg_match('/create\s+table/i', $val);
		$inj += preg_match('/insert\s+into/i', $val);
		$inj += preg_match('/select\s+from/i', $val);
		$inj += preg_match('/into\s+(outfile|dumpfile)/i', $val);
		$inj += preg_match('/user\s*\(/i', $val); // avoid to use function user() that return current database login
		$inj += preg_match('/information_schema/i', $val); // avoid to use request that read information_schema database
		$inj += preg_match('/<svg/i', $val); // <svg can be allowed in POST
	}
	if ($type == 3)
	{
		$inj += preg_match('/select|update|delete|truncate|replace|group\s+by|concat|count|from|union/i', $val);
	}
	if ($type != 2)	// Not common key strings, so we can check them both on GET and POST
	{
		$inj += preg_match('/updatexml\(/i', $val);
		$inj += preg_match('/update.+set.+=/i', $val);
		$inj += preg_match('/union.+select/i', $val);
		$inj += preg_match('/(\.\.%2f)+/i', $val);
	}
	// For XSS Injection done by closing textarea to execute content into a textarea field
	$inj += preg_match('/<\/textarea/i', $val);
	// For XSS Injection done by adding javascript with script
	// This is all cases a browser consider text is javascript:
	// When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
	// All examples on page: http://ha.ckers.org/xss.html#XSScalc
	// More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
	$inj += preg_match('/<audio/i', $val);
	$inj += preg_match('/<embed/i', $val);
	$inj += preg_match('/<iframe/i', $val);
	$inj += preg_match('/<object/i', $val);
	$inj += preg_match('/<script/i', $val);
	$inj += preg_match('/Set\.constructor/i', $val); // ECMA script 6
	if (!defined('NOSTYLECHECK')) $inj += preg_match('/<style/i', $val);
	$inj += preg_match('/base\s+href/si', $val);
	$inj += preg_match('/=data:/si', $val);
	// List of dom events is on https://www.w3schools.com/jsref/dom_obj_event.asp
	$inj += preg_match('/onmouse([a-z]*)\s*=/i', $val); // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
	$inj += preg_match('/ondrag([a-z]*)\s*=/i', $val); //
	$inj += preg_match('/ontouch([a-z]*)\s*=/i', $val); //
	$inj += preg_match('/on(abort|afterprint|beforeprint|beforeunload|blur|canplay|canplaythrough|change|click|contextmenu|copy|cut)\s*=/i', $val);
	$inj += preg_match('/on(dblclick|drop|durationchange|ended|error|focus|focusin|focusout|hashchange|input|invalid)\s*=/i', $val);
	$inj += preg_match('/on(keydown|keypress|keyup|load|loadeddata|loadedmetadata|loadstart|offline|online|pagehide|pageshow)\s*=/i', $val);
	$inj += preg_match('/on(paste|pause|play|playing|progress|ratechange|resize|reset|scroll|search|seeking|select|show|stalled|start|submit|suspend)\s*=/i', $val);
	$inj += preg_match('/on(timeupdate|toggle|unload|volumechange|waiting)\s*=/i', $val);
	//$inj += preg_match('/on[A-Z][a-z]+\*=/', $val);   // To lock event handlers onAbort(), ...
	$inj += preg_match('/&#58;|&#0000058|&#x3A/i', $val); // refused string ':' encoded (no reason to have it encoded) to lock 'javascript:...'
	$inj += preg_match('/javascript\s*:/i', $val);
	$inj += preg_match('/vbscript\s*:/i', $val);
	// For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
	if ($type == 1) {
		$val = str_replace('enclosure="', 'enclosure=X', $val); // We accept enclosure="
		$inj += preg_match('/"/i', $val); // We refused " in GET parameters value.
	}
	if ($type == 2) $inj += preg_match('/[;"]/', $val); // PHP_SELF is a file system path. It can contains spaces.
	return $inj;
}

/**
 * Return true if security check on parameters are OK, false otherwise.
 *
 * @param		string			$var		Variable name
 * @param		string			$type		1=GET, 0=POST, 2=PHP_SELF
 * @return		boolean|null				true if there is no injection. Stop code if injection found.
 */
function analyseVarsForSqlAndScriptsInjection(&$var, $type)
{
	if (is_array($var))
	{
		foreach ($var as $key => $value)	// Warning, $key may also be used for attacks
		{
			if (analyseVarsForSqlAndScriptsInjection($key, $type) && analyseVarsForSqlAndScriptsInjection($value, $type))
			{
				//$var[$key] = $value;	// This is useless
			} else {
				// Get remote IP: PS: We do not use getRemoteIP(), function is not yet loaded and we need a value that can't be spoofed
				$ip = (empty($_SERVER['REMOTE_ADDR']) ? 'unknown' : $_SERVER['REMOTE_ADDR']);
				$errormessage = 'Access refused to '.$ip.' by SQL or Script injection protection in main.inc.php (type='.htmlentities($type).' key='.htmlentities($key).' value='.htmlentities($value).' page='.htmlentities($_SERVER["REQUEST_URI"]).')';
				print $errormessage;
				// Add entry into error log
				if (function_exists('error_log')) {
					error_log($errormessage);
				}
				// TODO Add entry into security audit table
				exit;
			}
		}
		return true;
	} else {
		return (testSqlAndScriptInject($var, $type) <= 0);
	}
}


// Check consistency of NOREQUIREXXX DEFINES
if ((defined('NOREQUIREDB') || defined('NOREQUIRETRAN')) && !defined('NOREQUIREMENU'))
{
	print 'If define NOREQUIREDB or NOREQUIRETRAN are set, you must also set NOREQUIREMENU or not set them';
	exit;
}

// Sanity check on URL
if (!empty($_SERVER["PHP_SELF"]))
{
	$morevaltochecklikepost = array($_SERVER["PHP_SELF"]);
	analyseVarsForSqlAndScriptsInjection($morevaltochecklikepost, 2);
}
// Sanity check on GET parameters
if (!defined('NOSCANGETFORINJECTION') && !empty($_SERVER["QUERY_STRING"]))
{
	// Note: QUERY_STRING is url encoded, but $_GET and $_POST are already decoded
	// Because the analyseVarsForSqlAndScriptsInjection is designed for already url decoded value, we must decode QUERY_STRING
	// Another solution is to provide $_GET as parameter
	$morevaltochecklikeget = array(urldecode($_SERVER["QUERY_STRING"]));
	analyseVarsForSqlAndScriptsInjection($morevaltochecklikeget, 1);
}
// Sanity check on POST
if (!defined('NOSCANPOSTFORINJECTION'))
{
	analyseVarsForSqlAndScriptsInjection($_POST, 0);
}

// This is to make Dolibarr working with Plesk
if (!empty($_SERVER['DOCUMENT_ROOT']) && substr($_SERVER['DOCUMENT_ROOT'], -6) !== 'htdocs')
{
	set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
}

// Include the conf.php and functions.lib.php. This defined the constants like DOL_DOCUMENT_ROOT, DOL_DATA_ROOT, DOL_URL_ROOT...
require_once 'filefunc.inc.php';

// If there is a POST parameter to tell to save automatically some POST parameters into cookies, we do it.
// This is used for example by form of boxes to save personalization of some options.
// DOL_AUTOSET_COOKIE=cookiename:val1,val2 and  cookiename_val1=aaa cookiename_val2=bbb will set cookie_name with value json_encode(array('val1'=> , ))
if (!empty($_POST["DOL_AUTOSET_COOKIE"]))
{
	$tmpautoset = explode(':', $_POST["DOL_AUTOSET_COOKIE"], 2);
	$tmplist = explode(',', $tmpautoset[1]);
	$cookiearrayvalue = array();
	foreach ($tmplist as $tmpkey)
	{
		$postkey = $tmpautoset[0].'_'.$tmpkey;
		//var_dump('tmpkey='.$tmpkey.' postkey='.$postkey.' value='.$_POST[$postkey]);
		if (!empty($_POST[$postkey])) $cookiearrayvalue[$tmpkey] = $_POST[$postkey];
	}
	$cookiename = $tmpautoset[0];
	$cookievalue = json_encode($cookiearrayvalue);
	//var_dump('setcookie cookiename='.$cookiename.' cookievalue='.$cookievalue);
	setcookie($cookiename, empty($cookievalue) ? '' : $cookievalue, empty($cookievalue) ? 0 : (time() + (86400 * 354)), '/', null, false, true); // keep cookie 1 year and add tag httponly
	if (empty($cookievalue)) unset($_COOKIE[$cookiename]);
}


// Set the handler of session
if (ini_get('session.save_handler') == 'user') {
	require_once 'core/lib/phpsessionindb.lib.php';
}

// Init session. Name of session is specific to Dolibarr instance.
// Must be done after the include of filefunc.inc.php so global variables of conf file are defined (like $dolibarr_main_instance_unique_id or $dolibarr_main_force_https).
// Note: the function dol_getprefix is defined into functions.lib.php but may have been defined to return a different key to manage another area to protect.
$prefix = dol_getprefix('');
$sessionname = 'DOLSESSID_'.$prefix;
$sessiontimeout = 'DOLSESSTIMEOUT_'.$prefix;
if (!empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime', $_COOKIE[$sessiontimeout]);
// This create lock, released by session_write_close() or end of page.
// We need this lock as long as we read/write $_SESSION ['vars']. We can remove lock when finished.
if (!defined('NOSESSION'))
{
	session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie (same as setting session.cookie_httponly into php.ini). Must be called before the session_start.
	session_name($sessionname);
	session_start();
}


// Init the 5 global objects, this include will make the 'new Xxx()' and set properties for: $conf, $db, $langs, $user, $mysoc
require_once 'master.inc.php';


// If software has been locked. Only login $conf->global->MAIN_ONLY_LOGIN_ALLOWED is allowed.
if (!empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
{
	$ok = 0;
	if ((!session_id() || !isset($_SESSION["dol_login"])) && !isset($_POST["username"]) && !empty($_SERVER["GATEWAY_INTERFACE"])) $ok = 1; // We let working pages if not logged and inside a web browser (login form, to allow login by admin)
	elseif (isset($_POST["username"]) && $_POST["username"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) $ok = 1; // We let working pages that is a login submission (login submit, to allow login by admin)
	elseif (defined('NOREQUIREDB'))   $ok = 1; // We let working pages that don't need database access (xxx.css.php)
	elseif (defined('EVEN_IF_ONLY_LOGIN_ALLOWED')) $ok = 1; // We let working pages that ask to work even if only login enabled (logout.php)
	elseif (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) $ok = 1; // We let working if user is allowed admin
	if (!$ok)
	{
		if (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] != $conf->global->MAIN_ONLY_LOGIN_ALLOWED)
		{
			print 'Sorry, your application is offline.'."\n";
			print 'You are logged with user "'.$_SESSION["dol_login"].'" and only administrator user "'.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'" is allowed to connect for the moment.'."\n";
			$nexturl = DOL_URL_ROOT.'/user/logout.php';
			print 'Please try later or <a href="'.$nexturl.'">click here to disconnect and change login user</a>...'."\n";
		} else {
			print 'Sorry, your application is offline. Only administrator user "'.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'" is allowed to connect for the moment.'."\n";
			$nexturl = DOL_URL_ROOT.'/';
			print 'Please try later or <a href="'.$nexturl.'">click here to change login user</a>...'."\n";
		}
		exit;
	}
}


// Activate end of page function
register_shutdown_function('dol_shutdown');

// Load debugbar
if (!empty($conf->debugbar->enabled) && !GETPOST('dol_use_jmobile') && empty($_SESSION['dol_use_jmobile']))
{
	global $debugbar;
	include_once DOL_DOCUMENT_ROOT.'/debugbar/class/DebugBar.php';
	$debugbar = new DolibarrDebugBar();
	$renderer = $debugbar->getRenderer();
	if (empty($conf->global->MAIN_HTML_HEADER)) $conf->global->MAIN_HTML_HEADER = '';
	$conf->global->MAIN_HTML_HEADER .= $renderer->renderHead();

	$debugbar['time']->startMeasure('pageaftermaster', 'Page generation (after environment init)');
}

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"]))
{
	$tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
	$conf->browser->name = $tmp['browsername'];
	$conf->browser->os = $tmp['browseros'];
	$conf->browser->version = $tmp['browserversion'];
	$conf->browser->layout = $tmp['layout']; // 'classic', 'phone', 'tablet'
	//var_dump($conf->browser);

	if ($conf->browser->layout == 'phone') $conf->dol_no_mouse_hover = 1;
}

// Set global MAIN_OPTIMIZEFORTEXTBROWSER (must be before login part)
if (GETPOST('textbrowser', 'int') || (!empty($conf->browser->name) && $conf->browser->name == 'lynxlinks'))   // If we must enable text browser
{
	$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = 1;
}

// Force HTTPS if required ($conf->file->main_force_https is 0/1 or 'https dolibarr root url')
// $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
if (!empty($conf->file->main_force_https) && (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on'))
{
	$newurl = '';
	if (is_numeric($conf->file->main_force_https))
	{
		if ($conf->file->main_force_https == '1' && !empty($_SERVER["SCRIPT_URI"]))	// If SCRIPT_URI supported by server
		{
			if (preg_match('/^http:/i', $_SERVER["SCRIPT_URI"]) && !preg_match('/^https:/i', $_SERVER["SCRIPT_URI"]))	// If link is http
			{
				$newurl = preg_replace('/^http:/i', 'https:', $_SERVER["SCRIPT_URI"]);
			}
		} else {
			// Check HTTPS environment variable (Apache/mod_ssl only)
			$newurl = preg_replace('/^http:/i', 'https:', DOL_MAIN_URL_ROOT).$_SERVER["REQUEST_URI"];
		}
	} else {
		// Check HTTPS environment variable (Apache/mod_ssl only)
		$newurl = $conf->file->main_force_https.$_SERVER["REQUEST_URI"];
	}
	// Start redirect
	if ($newurl)
	{
		header_remove(); // Clean header already set to be sure to remove any header like "Set-Cookie: DOLSESSID_..." from non HTTPS answers
		dol_syslog("main.inc: dolibarr_main_force_https is on, we make a redirect to ".$newurl);
		header("Location: ".$newurl);
		exit;
	} else {
		dol_syslog("main.inc: dolibarr_main_force_https is on but we failed to forge new https url so no redirect is done", LOG_WARNING);
	}
}

if (!defined('NOLOGIN') && !defined('NOIPCHECK') && !empty($dolibarr_main_restrict_ip))
{
	$listofip = explode(',', $dolibarr_main_restrict_ip);
	$found = false;
	foreach ($listofip as $ip)
	{
		$ip = trim($ip);
		if ($ip == $_SERVER['REMOTE_ADDR'])
		{
			$found = true;
			break;
		}
	}
	if (!$found)
	{
		print 'Access refused by IP protection. Your detected IP is '.$_SERVER['REMOTE_ADDR'];
		exit;
	}
}

// Loading of additional presentation includes
if (!defined('NOREQUIREHTML')) require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php'; // Need 660ko memory (800ko in 2.2)
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php'; // Need 22ko memory

// If install or upgrade process not done or not completely finished, we call the install page.
if (!empty($conf->global->MAIN_NOT_INSTALLED) || !empty($conf->global->MAIN_NOT_UPGRADED))
{
	dol_syslog("main.inc: A previous install or upgrade was not complete. Redirect to install page.", LOG_WARNING);
	header("Location: ".DOL_URL_ROOT."/install/index.php");
	exit;
}
// If an upgrade process is required, we call the install page.
if ((!empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && ($conf->global->MAIN_VERSION_LAST_UPGRADE != DOL_VERSION))
|| (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && !empty($conf->global->MAIN_VERSION_LAST_INSTALL) && ($conf->global->MAIN_VERSION_LAST_INSTALL != DOL_VERSION)))
{
	$versiontocompare = empty($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_INSTALL : $conf->global->MAIN_VERSION_LAST_UPGRADE;
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	$dolibarrversionlastupgrade = preg_split('/[.-]/', $versiontocompare);
	$dolibarrversionprogram = preg_split('/[.-]/', DOL_VERSION);
	$rescomp = versioncompare($dolibarrversionprogram, $dolibarrversionlastupgrade);
	if ($rescomp > 0)   // Programs have a version higher than database. We did not add "&& $rescomp < 3" because we want upgrade process for build upgrades
	{
		dol_syslog("main.inc: database version ".$versiontocompare." is lower than programs version ".DOL_VERSION.". Redirect to install page.", LOG_WARNING);
		header("Location: ".DOL_URL_ROOT."/install/index.php");
		exit;
	}
}

// Creation of a token against CSRF vulnerabilities
if (!defined('NOTOKENRENEWAL'))
{
	// Rolling token at each call ($_SESSION['token'] contains token of previous page)
	if (isset($_SESSION['newtoken'])) $_SESSION['token'] = $_SESSION['newtoken'];

	// Save in $_SESSION['newtoken'] what will be next token. Into forms, we will add param token = $_SESSION['newtoken']
	$token = dol_hash(uniqid(mt_rand(), true)); // Generates a hash of a random number
	$_SESSION['newtoken'] = $token;
}

//dol_syslog("aaaa - ".defined('NOCSRFCHECK')." - ".$dolibarr_nocsrfcheck." - ".$conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN." - ".$_SERVER['REQUEST_METHOD']." - ".GETPOST('token', 'alpha').' '.$_SESSION['token']);
//$dolibarr_nocsrfcheck=1;
// Check token
if ((!defined('NOCSRFCHECK') && empty($dolibarr_nocsrfcheck) && !empty($conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN))
	|| defined('CSRFCHECK_WITH_TOKEN'))	// Check validity of token, only if option MAIN_SECURITY_CSRF_WITH_TOKEN enabled or if constant CSRFCHECK_WITH_TOKEN is set into page
{
	// Check all cases that need a token (all POST actions, all actions and mass actions on pages with CSRFCHECK_WITH_TOKEN set, all sensitive GET actions)
	if ($_SERVER['REQUEST_METHOD'] == 'POST' ||
		((GETPOSTISSET('action') || GETPOSTISSET('massaction')) && defined('CSRFCHECK_WITH_TOKEN')) ||
		in_array(GETPOST('action', 'aZ09'), array('add', 'addtimespent', 'update', 'install', 'delete', 'deletefilter', 'deleteoperation', 'deleteprof', 'deletepayment', 'confirm_create_user', 'confirm_create_thirdparty', 'confirm_reject_check')))
	{
		if (!GETPOSTISSET('token')) {
			if (GETPOST('uploadform', 'int')) {
				dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]." refused. File size too large.");
				$langs->loadLangs(array("errors", "install"));
				print $langs->trans("ErrorFileSizeTooLarge").' ';
				print $langs->trans("ErrorGoBackAndCorrectParameters");
				die;
			} else {
				dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]." refused by CSRFCHECK_WITH_TOKEN protection. Token not provided.");
				if (defined('CSRFCHECK_WITH_TOKEN')) {
					print "Access to a page that needs a token (constant CSRFCHECK_WITH_TOKEN is defined) is refused by CSRF protection in main.inc.php. Token not provided.\n";
				} else {
					print "Access to this page this way (POST method or GET with a sensible value for 'action' parameter) is refused by CSRF protection in main.inc.php. Token not provided.\n";
					print "If you access your server behind a proxy using url rewriting and the parameter is provided by caller, you might check that all HTTP header are propagated (or add the line \$dolibarr_nocsrfcheck=1 into your conf.php file or MAIN_SECURITY_CSRF_WITH_TOKEN to 0 into setup).\n";
				}
				die;
			}
		}
	}

	if (GETPOSTISSET('token') && GETPOST('token', 'alpha') != $_SESSION['token'])
	{
		dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]." refused due to invalid token, so we disable POST and some GET parameters - referer=".$_SERVER['HTTP_REFERER'].", action=".GETPOST('action', 'aZ09').", _GET|POST['token']=".GETPOST('token', 'alpha').", _SESSION['token']=".$_SESSION['token'], LOG_WARNING);
		//print 'Unset POST by CSRF protection in main.inc.php.';	// Do not output anything because this create problems when using the BACK button on browsers.
		setEventMessages('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry', null, 'warnings');
		//if ($conf->global->MAIN_FEATURES_LEVEL >= 1) setEventMessages('Unset POST and GET params by CSRF protection in main.inc.php (Token provided was not generated by the previous page).'."<br>\n".'$_SERVER[REQUEST_URI] = '.$_SERVER['REQUEST_URI'].' $_SERVER[REQUEST_METHOD] = '.$_SERVER['REQUEST_METHOD'].' GETPOST(token) = '.GETPOST('token', 'alpha').' $_SESSION[token] = '.$_SESSION['token'], null, 'warnings');
		$savid = ((int) $_POST['id']);
		unset($_POST);
		//unset($_POST['action']); unset($_POST['massaction']);
		//unset($_POST['confirm']); unset($_POST['confirmmassaction']);
		unset($_GET['confirm']);
		unset($_GET['action']);
		unset($_GET['confirmmassaction']);
		unset($_GET['massaction']);
		$_POST['id'] = ((int) $savid);
	}
}

// Disable modules (this must be after session_start and after conf has been loaded)
if (GETPOSTISSET('disablemodules'))  $_SESSION["disablemodules"] = GETPOST('disablemodules', 'alpha');
if (!empty($_SESSION["disablemodules"]))
{
	$modulepartkeys = array('css', 'js', 'tabs', 'triggers', 'login', 'substitutions', 'menus', 'theme', 'sms', 'tpl', 'barcode', 'models', 'societe', 'hooks', 'dir', 'syslog', 'tpllinkable', 'contactelement', 'moduleforexternal');

	$disabled_modules = explode(',', $_SESSION["disablemodules"]);
	foreach ($disabled_modules as $module)
	{
		if ($module)
		{
			if (empty($conf->$module)) $conf->$module = new stdClass(); // To avoid warnings
			$conf->$module->enabled = false;
			foreach ($modulepartkeys as $modulepartkey)
			{
				unset($conf->modules_parts[$modulepartkey][$module]);
			}
			if ($module == 'fournisseur')		// Special case
			{
				$conf->supplier_order->enabled = 0;
				$conf->supplier_invoice->enabled = 0;
			}
		}
	}
}

// Set current modulepart
$modulepart = explode("/", $_SERVER["PHP_SELF"]);
if (is_array($modulepart) && count($modulepart) > 0)
{
	foreach ($conf->modules as $module)
	{
		if (in_array($module, $modulepart))
		{
			$conf->modulepart = $module;
			break;
		}
	}
}

/*
 * Phase authentication / login
 */
$login = '';
if (!defined('NOLOGIN'))
{
	// $authmode lists the different method of identification to be tested in order of preference.
	// Example: 'http', 'dolibarr', 'ldap', 'http,forceuser', '...'

	if (defined('MAIN_AUTHENTICATION_MODE'))
	{
		$dolibarr_main_authentication = constant('MAIN_AUTHENTICATION_MODE');
	} else {
		// Authentication mode
		if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication = 'http,dolibarr';
		// Authentication mode: forceuser
		if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user = 'auto';
	}
	// Set authmode
	$authmode = explode(',', $dolibarr_main_authentication);

	// No authentication mode
	if (!count($authmode))
	{
		$langs->load('main');
		dol_print_error('', $langs->trans("ErrorConfigParameterNotDefined", 'dolibarr_main_authentication'));
		exit;
	}

	// If login request was already post, we retrieve login from the session
	// Call module if not realized that his request.
	// At the end of this phase, the variable $login is defined.
	$resultFetchUser = '';
	$test = true;
	if (!isset($_SESSION["dol_login"]))
	{
		// It is not already authenticated and it requests the login / password
		include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		$dol_dst_observed = GETPOST("dst_observed", 'int', 3);
		$dol_dst_first = GETPOST("dst_first", 'int', 3);
		$dol_dst_second = GETPOST("dst_second", 'int', 3);
		$dol_screenwidth = GETPOST("screenwidth", 'int', 3);
		$dol_screenheight = GETPOST("screenheight", 'int', 3);
		$dol_hide_topmenu = GETPOST('dol_hide_topmenu', 'int', 3);
		$dol_hide_leftmenu = GETPOST('dol_hide_leftmenu', 'int', 3);
		$dol_optimize_smallscreen = GETPOST('dol_optimize_smallscreen', 'int', 3);
		$dol_no_mouse_hover = GETPOST('dol_no_mouse_hover', 'int', 3);
		$dol_use_jmobile = GETPOST('dol_use_jmobile', 'int', 3); // 0=default, 1=to say we use app from a webview app, 2=to say we use app from a webview app and keep ajax
		//dol_syslog("POST key=".join(array_keys($_POST),',').' value='.join($_POST,','));

		// If in demo mode, we check we go to home page through the public/demo/index.php page
		if (!empty($dolibarr_main_demo) && $_SERVER['PHP_SELF'] == DOL_URL_ROOT.'/index.php')  // We ask index page
		{
			if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/public/', $_SERVER['HTTP_REFERER']))
			{
				dol_syslog("Call index page from another url than demo page (call is done from page ".$_SERVER['HTTP_REFERER'].")");
				$url = '';
				$url .= ($url ? '&' : '').($dol_hide_topmenu ? 'dol_hide_topmenu='.$dol_hide_topmenu : '');
				$url .= ($url ? '&' : '').($dol_hide_leftmenu ? 'dol_hide_leftmenu='.$dol_hide_leftmenu : '');
				$url .= ($url ? '&' : '').($dol_optimize_smallscreen ? 'dol_optimize_smallscreen='.$dol_optimize_smallscreen : '');
				$url .= ($url ? '&' : '').($dol_no_mouse_hover ? 'dol_no_mouse_hover='.$dol_no_mouse_hover : '');
				$url .= ($url ? '&' : '').($dol_use_jmobile ? 'dol_use_jmobile='.$dol_use_jmobile : '');
				$url = DOL_URL_ROOT.'/public/demo/index.php'.($url ? '?'.$url : '');
				header("Location: ".$url);
				exit;
			}
		}

		// Hooks for security access
		$action = '';
		$hookmanager->initHooks(array('login'));
		$parameters = array();
		$reshook = $hookmanager->executeHooks('beforeLoginAuthentication', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
		    $test = false;
		    $error++;
		}

		// Verification security graphic code
		if ($test && GETPOST("username", "alpha", 2) && !empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA) && !isset($_SESSION['dol_bypass_antispam']))
		{
			$sessionkey = 'dol_antispam_value';
			$ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));

			// Check code
			if (!$ok)
			{
				dol_syslog('Bad value for code, connexion refused');
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->trans("ErrorBadValueForCode");
				$test = false;

				// Call trigger for the "security events" log
				$user->trigger_mesg = 'ErrorBadValueForCode - login='.GETPOST("username", "alpha", 2);

				// Call trigger
				$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
				if ($result < 0) $error++;
				// End call triggers

				// Hooks on failed login
				$action = '';
				$hookmanager->initHooks(array('login'));
				$parameters = array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
				$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;

				// Note: exit is done later
			}
		}

		$allowedmethodtopostusername = 2;
		if (defined('MAIN_AUTHENTICATION_POST_METHOD')) $allowedmethodtopostusername = constant('MAIN_AUTHENTICATION_POST_METHOD');
		$usertotest = (!empty($_COOKIE['login_dolibarr']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE['login_dolibarr']) : GETPOST("username", "alpha", $allowedmethodtopostusername));
		$passwordtotest = GETPOST('password', 'none', $allowedmethodtopostusername);
		$entitytotest = (GETPOST('entity', 'int') ? GETPOST('entity', 'int') : (!empty($conf->entity) ? $conf->entity : 1));

		// Define if we received data to test the login.
		$goontestloop = false;
		if (isset($_SERVER["REMOTE_USER"]) && in_array('http', $authmode)) $goontestloop = true;
		if ($dolibarr_main_authentication == 'forceuser' && !empty($dolibarr_auto_user)) $goontestloop = true;
		if (GETPOST("username", "alpha", $allowedmethodtopostusername) || !empty($_COOKIE['login_dolibarr']) || GETPOST('openid_mode', 'alpha', 1)) $goontestloop = true;

		if (!is_object($langs)) // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate("", $conf);
			$langcode = (GETPOST('lang', 'aZ09', 1) ?GETPOST('lang', 'aZ09', 1) : (empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT));
			if (defined('MAIN_LANG_DEFAULT')) $langcode = constant('MAIN_LANG_DEFAULT');
			$langs->setDefaultLang($langcode);
		}

		// Validation of login/pass/entity
		// If ok, the variable login will be returned
		// If error, we will put error message in session under the name dol_loginmesg
		if ($test && $goontestloop && (GETPOST('actionlogin', 'aZ09') == 'login' || $dolibarr_main_authentication != 'dolibarr'))
		{
			$login = checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $authmode);
			if ($login === '--bad-login-validity--') {
				$login = '';
			}

			if ($login)
			{
				$dol_authmode = $conf->authmode; // This properties is defined only when logged, to say what mode was successfully used
				$dol_tz = $_POST["tz"];
				$dol_tz_string = $_POST["tz_string"];
				$dol_tz_string = preg_replace('/\s*\(.+\)$/', '', $dol_tz_string);
				$dol_tz_string = preg_replace('/,/', '/', $dol_tz_string);
				$dol_tz_string = preg_replace('/\s/', '_', $dol_tz_string);
				$dol_dst = 0;
				// Keep $_POST here. Do not use GETPOSTISSET
				if (isset($_POST["dst_first"]) && isset($_POST["dst_second"]))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
					$datenow = dol_now();
					$datefirst = dol_stringtotime($_POST["dst_first"]);
					$datesecond = dol_stringtotime($_POST["dst_second"]);
					if ($datenow >= $datefirst && $datenow < $datesecond) $dol_dst = 1;
				}
				//print $datefirst.'-'.$datesecond.'-'.$datenow.'-'.$dol_tz.'-'.$dol_tzstring.'-'.$dol_dst; exit;
			}

			if (!$login)
			{
				dol_syslog('Bad password, connexion refused', LOG_DEBUG);
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				// Bad password. No authmode has found a good password.
				// We set a generic message if not defined inside function checkLoginPassEntity or subfunctions
				if (empty($_SESSION["dol_loginmesg"])) $_SESSION["dol_loginmesg"] = $langs->trans("ErrorBadLoginPassword");

				// Call trigger for the "security events" log
				$user->trigger_mesg = $langs->trans("ErrorBadLoginPassword").' - login='.GETPOST("username", "alpha", 2);

				// Call trigger
				$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
				if ($result < 0) $error++;
				// End call triggers

				// Hooks on failed login
				$action = '';
				$hookmanager->initHooks(array('login'));
				$parameters = array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
				$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;

				// Note: exit is done in next chapter
			}
		}

		// End test login / passwords
		if (!$login || (in_array('ldap', $authmode) && empty($passwordtotest)))	// With LDAP we refused empty password because some LDAP are "opened" for anonymous access so connexion is a success.
		{
			// No data to test login, so we show the login page.
			dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]." - action=".GETPOST('action', 'aZ09')." - actionlogin=".GETPOST('actionlogin', 'aZ09')." - showing the login form and exit");
			if (defined('NOREDIRECTBYMAINTOLOGIN')) return 'ERROR_NOT_LOGGED';
			else {
				if ($_SERVER["HTTP_USER_AGENT"] == 'securitytest') {
					http_response_code(401); // It makes easier to understand if session was broken during security tests
				}
				dol_loginfunction($langs, $conf, (!empty($mysoc) ? $mysoc : ''));
			}
			exit;
		}

		$resultFetchUser = $user->fetch('', $login, '', 1, ($entitytotest > 0 ? $entitytotest : -1)); // login was retrieved previously when checking password.
		if ($resultFetchUser <= 0)
		{
			dol_syslog('User not found, connexion refused');
			session_destroy();
			session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
			session_name($sessionname);
			session_start();

			if ($resultFetchUser == 0)
			{
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->trans("ErrorCantLoadUserFromDolibarrDatabase", $login);

				$user->trigger_mesg = 'ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
			}
			if ($resultFetchUser < 0)
			{
				$_SESSION["dol_loginmesg"] = $user->error;

				$user->trigger_mesg = $user->error;
			}

			// Call trigger
			$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
			if ($result < 0) $error++;
			// End call triggers


			// Hooks on failed login
			$action = '';
			$hookmanager->initHooks(array('login'));
			$parameters = array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
			$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) $error++;

			$paramsurl = array();
			if (GETPOST('textbrowser', 'int')) $paramsurl[] = 'textbrowser='.GETPOST('textbrowser', 'int');
			if (GETPOST('nojs', 'int'))        $paramsurl[] = 'nojs='.GETPOST('nojs', 'int');
			if (GETPOST('lang', 'aZ09'))       $paramsurl[] = 'lang='.GETPOST('lang', 'aZ09');
			header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl) ? '?'.implode('&', $paramsurl) : ''));
			exit;
		} else {
			// User is loaded, we may need to change language for him according to its choice
			if (!empty($user->conf->MAIN_LANG_DEFAULT)) {
				$langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
			}
		}
	} else {
		// We are already into an authenticated session
		$login = $_SESSION["dol_login"];
		$entity = $_SESSION["dol_entity"];
		dol_syslog("- This is an already logged session. _SESSION['dol_login']=".$login." _SESSION['dol_entity']=".$entity, LOG_DEBUG);

		$resultFetchUser = $user->fetch('', $login, '', 1, ($entity > 0 ? $entity : -1));
		if ($resultFetchUser <= 0)
		{
			// Account has been removed after login
			dol_syslog("Can't load user even if session logged. _SESSION['dol_login']=".$login, LOG_WARNING);
			session_destroy();
			session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
			session_name($sessionname);
			session_start();

			if ($resultFetchUser == 0)
			{
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->trans("ErrorCantLoadUserFromDolibarrDatabase", $login);

				$user->trigger_mesg = 'ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
			}
			if ($resultFetchUser < 0)
			{
				$_SESSION["dol_loginmesg"] = $user->error;

				$user->trigger_mesg = $user->error;
			}

			// Call trigger
			$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
			if ($result < 0) $error++;
			// End call triggers

			// Hooks on failed login
			$action = '';
			$hookmanager->initHooks(array('login'));
			$parameters = array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
			$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) $error++;

			$paramsurl = array();
			if (GETPOST('textbrowser', 'int')) $paramsurl[] = 'textbrowser='.GETPOST('textbrowser', 'int');
			if (GETPOST('nojs', 'int'))        $paramsurl[] = 'nojs='.GETPOST('nojs', 'int');
			if (GETPOST('lang', 'aZ09'))       $paramsurl[] = 'lang='.GETPOST('lang', 'aZ09');
			header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl) ? '?'.implode('&', $paramsurl) : ''));
			exit;
		} else {
			// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
			$hookmanager->initHooks(array('main'));

			// Code for search criteria persistence.
			if (!empty($_GET['save_lastsearch_values']))    // We must use $_GET here
			{
				$relativepathstring = preg_replace('/\?.*$/', '', $_SERVER["HTTP_REFERER"]);
				$relativepathstring = preg_replace('/^https?:\/\/[^\/]*/', '', $relativepathstring); // Get full path except host server
				// Clean $relativepathstring
   				if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
				$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
				$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
				//var_dump($relativepathstring);

				// We click on a link that leave a page we have to save search criteria, contextpage, limit and page. We save them from tmp to no tmp
				if (!empty($_SESSION['lastsearch_values_tmp_'.$relativepathstring]))
				{
					$_SESSION['lastsearch_values_'.$relativepathstring] = $_SESSION['lastsearch_values_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_values_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]))
				{
					$_SESSION['lastsearch_contextpage_'.$relativepathstring] = $_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_page_tmp_'.$relativepathstring]) && $_SESSION['lastsearch_page_tmp_'.$relativepathstring] > 0)
				{
					$_SESSION['lastsearch_page_'.$relativepathstring] = $_SESSION['lastsearch_page_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_page_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]) && $_SESSION['lastsearch_limit_tmp_'.$relativepathstring] != $conf->liste_limit)
				{
					$_SESSION['lastsearch_limit_'.$relativepathstring] = $_SESSION['lastsearch_limit_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]);
				}
			}

			$action = '';
			$reshook = $hookmanager->executeHooks('updateSession', array(), $user, $action);
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}
		}
	}

	// Is it a new session that has started ?
	// If we are here, this means authentication was successfull.
	if (!isset($_SESSION["dol_login"]))
	{
		// New session for this login has started.
		$error = 0;

		// Store value into session (values always stored)
		$_SESSION["dol_login"] = $user->login;
		$_SESSION["dol_authmode"] = isset($dol_authmode) ? $dol_authmode : '';
		$_SESSION["dol_tz"] = isset($dol_tz) ? $dol_tz : '';
		$_SESSION["dol_tz_string"] = isset($dol_tz_string) ? $dol_tz_string : '';
		$_SESSION["dol_dst"] = isset($dol_dst) ? $dol_dst : '';
		$_SESSION["dol_dst_observed"] = isset($dol_dst_observed) ? $dol_dst_observed : '';
		$_SESSION["dol_dst_first"] = isset($dol_dst_first) ? $dol_dst_first : '';
		$_SESSION["dol_dst_second"] = isset($dol_dst_second) ? $dol_dst_second : '';
		$_SESSION["dol_screenwidth"] = isset($dol_screenwidth) ? $dol_screenwidth : '';
		$_SESSION["dol_screenheight"] = isset($dol_screenheight) ? $dol_screenheight : '';
		$_SESSION["dol_company"] = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$_SESSION["dol_entity"] = $conf->entity;
		// Store value into session (values stored only if defined)
		if (!empty($dol_hide_topmenu))         $_SESSION['dol_hide_topmenu'] = $dol_hide_topmenu;
		if (!empty($dol_hide_leftmenu))        $_SESSION['dol_hide_leftmenu'] = $dol_hide_leftmenu;
		if (!empty($dol_optimize_smallscreen)) $_SESSION['dol_optimize_smallscreen'] = $dol_optimize_smallscreen;
		if (!empty($dol_no_mouse_hover))       $_SESSION['dol_no_mouse_hover'] = $dol_no_mouse_hover;
		if (!empty($dol_use_jmobile))          $_SESSION['dol_use_jmobile'] = $dol_use_jmobile;

		dol_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"]." Session id=".session_id());

		$db->begin();

		$user->update_last_login_date();

		$loginfo = 'TZ='.$_SESSION["dol_tz"].';TZString='.$_SESSION["dol_tz_string"].';Screen='.$_SESSION["dol_screenwidth"].'x'.$_SESSION["dol_screenheight"];

		// Call triggers for the "security events" log
		$user->trigger_mesg = $loginfo;

		// Call trigger
		$result = $user->call_trigger('USER_LOGIN', $user);
		if ($result < 0) $error++;
		// End call triggers

		// Hooks on successfull login
		$action = '';
		$hookmanager->initHooks(array('login'));
		$parameters = array('dol_authmode'=>$dol_authmode, 'dol_loginfo'=>$loginfo);
		$reshook = $hookmanager->executeHooks('afterLogin', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) $error++;

		if ($error)
		{
			$db->rollback();
			session_destroy();
			dol_print_error($db, 'Error in some triggers USER_LOGIN or in some hooks afterLogin');
			exit;
		} else {
			$db->commit();
		}

		// Change landing page if defined.
		$landingpage = (empty($user->conf->MAIN_LANDING_PAGE) ? (empty($conf->global->MAIN_LANDING_PAGE) ? '' : $conf->global->MAIN_LANDING_PAGE) : $user->conf->MAIN_LANDING_PAGE);
		if (!empty($landingpage))    // Example: /index.php
		{
			$newpath = dol_buildpath($landingpage, 1);
			if ($_SERVER["PHP_SELF"] != $newpath)   // not already on landing page (avoid infinite loop)
			{
				header('Location: '.$newpath);
				exit;
			}
		}
	}


	// If user admin, we force the rights-based modules
	if ($user->admin)
	{
		$user->rights->user->user->lire = 1;
		$user->rights->user->user->creer = 1;
		$user->rights->user->user->password = 1;
		$user->rights->user->user->supprimer = 1;
		$user->rights->user->self->creer = 1;
		$user->rights->user->self->password = 1;
	}

	/*
     * Overwrite some configs globals (try to avoid this and have code to use instead $user->conf->xxx)
     */

	// Set liste_limit
	if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT))	$conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT; // Can be 0
	if (isset($user->conf->PRODUIT_LIMIT_SIZE))	$conf->product->limit_size = $user->conf->PRODUIT_LIMIT_SIZE; // Can be 0

	// Replace conf->css by personalized value if theme not forced
	if (empty($conf->global->MAIN_FORCETHEME) && !empty($user->conf->MAIN_THEME))
	{
		$conf->theme = $user->conf->MAIN_THEME;
		$conf->css = "/theme/".$conf->theme."/style.css.php";
	}
}

// Case forcing style from url
if (GETPOST('theme', 'alpha'))
{
	$conf->theme = GETPOST('theme', 'alpha', 1);
	$conf->css = "/theme/".$conf->theme."/style.css.php";
}

// Set javascript option
if (GETPOST('nojs', 'int')) {  // If javascript was not disabled on URL
	$conf->use_javascript_ajax = 0;
} else {
	if (!empty($user->conf->MAIN_DISABLE_JAVASCRIPT)) {
		$conf->use_javascript_ajax = !$user->conf->MAIN_DISABLE_JAVASCRIPT;
	}
}

// Set MAIN_OPTIMIZEFORTEXTBROWSER for user (must be after login part)
if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && !empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = $user->conf->MAIN_OPTIMIZEFORTEXTBROWSER;
}

// set MAIN_OPTIMIZEFORCOLORBLIND for user
$conf->global->MAIN_OPTIMIZEFORCOLORBLIND = empty($user->conf->MAIN_OPTIMIZEFORCOLORBLIND) ? '' : $user->conf->MAIN_OPTIMIZEFORCOLORBLIND;

// Set terminal output option according to conf->browser.
if (GETPOST('dol_hide_leftmenu', 'int') || !empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu = 1;
if (GETPOST('dol_hide_topmenu', 'int') || !empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu = 1;
if (GETPOST('dol_optimize_smallscreen', 'int') || !empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen = 1;
if (GETPOST('dol_no_mouse_hover', 'int') || !empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover = 1;
if (GETPOST('dol_use_jmobile', 'int') || !empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile = 1;
if (!empty($conf->browser->layout) && $conf->browser->layout != 'classic') $conf->dol_no_mouse_hover = 1;
if ((!empty($conf->browser->layout) && $conf->browser->layout == 'phone')
	|| (!empty($_SESSION['dol_screenwidth']) && $_SESSION['dol_screenwidth'] < 400)
	|| (!empty($_SESSION['dol_screenheight']) && $_SESSION['dol_screenheight'] < 400)
)
{
	$conf->dol_optimize_smallscreen = 1;
}
// If we force to use jmobile, then we reenable javascript
if (!empty($conf->dol_use_jmobile)) $conf->use_javascript_ajax = 1;
// Replace themes bugged with jmobile with eldy
if (!empty($conf->dol_use_jmobile) && in_array($conf->theme, array('bureau2crea', 'cameleo', 'amarok')))
{
	$conf->theme = 'eldy';
	$conf->css = "/theme/".$conf->theme."/style.css.php";
}

if (!defined('NOREQUIRETRAN'))
{
	if (!GETPOST('lang', 'aZ09'))	// If language was not forced on URL
	{
		// If user has chosen its own language
		if (!empty($user->conf->MAIN_LANG_DEFAULT))
		{
			// If different than current language
			//print ">>>".$langs->getDefaultLang()."-".$user->conf->MAIN_LANG_DEFAULT;
			if ($langs->getDefaultLang() != $user->conf->MAIN_LANG_DEFAULT)
			{
				$langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
			}
		}
	}
}

if (!defined('NOLOGIN'))
{
	// If the login is not recovered, it is identified with an account that does not exist.
	// Hacking attempt?
	if (!$user->login) accessforbidden();

	// Check if user is active
	if ($user->statut < 1)
	{
		// If not active, we refuse the user
		$langs->load("other");
		dol_syslog("Authentication KO as login is disabled", LOG_NOTICE);
		accessforbidden($langs->trans("ErrorLoginDisabled"));
		exit;
	}

	// Load permissions
	$user->getrights();
}

dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"])?'':$_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"].' - action='.GETPOST('action', 'aZ09').', massaction='.GETPOST('massaction', 'aZ09').' NOTOKENRENEWAL='.(defined('NOTOKENRENEWAL') ?constant('NOTOKENRENEWAL') : ''));
//Another call for easy debugg
//dol_syslog("Access to ".$_SERVER["PHP_SELF"].' GET='.join(',',array_keys($_GET)).'->'.join(',',$_GET).' POST:'.join(',',array_keys($_POST)).'->'.join(',',$_POST));

// Load main languages files
if (!defined('NOREQUIRETRAN'))
{
	// Load translation files required by page
	$langs->loadLangs(array('main', 'dict'));
}

// Define some constants used for style of arrays
$bc = array(0=>'class="impair"', 1=>'class="pair"');
$bcdd = array(0=>'class="drag drop oddeven"', 1=>'class="drag drop oddeven"');
$bcnd = array(0=>'class="nodrag nodrop nohover"', 1=>'class="nodrag nodrop nohoverpair"'); // Used for tr to add new lines
$bctag = array(0=>'class="impair tagtr"', 1=>'class="pair tagtr"');

// Define messages variables
$mesg = ''; $warning = ''; $error = 0;
// deprecated, see setEventMessages() and dol_htmloutput_events()
$mesgs = array(); $warnings = array(); $errors = array();

// Constants used to defined number of lines in textarea
if (empty($conf->browser->firefox))
{
	define('ROWS_1', 1);
	define('ROWS_2', 2);
	define('ROWS_3', 3);
	define('ROWS_4', 4);
	define('ROWS_5', 5);
	define('ROWS_6', 6);
	define('ROWS_7', 7);
	define('ROWS_8', 8);
	define('ROWS_9', 9);
} else {
	define('ROWS_1', 0);
	define('ROWS_2', 1);
	define('ROWS_3', 2);
	define('ROWS_4', 3);
	define('ROWS_5', 4);
	define('ROWS_6', 5);
	define('ROWS_7', 6);
	define('ROWS_8', 7);
	define('ROWS_9', 8);
}

$heightforframes = 50;

// Init menu manager
if (!defined('NOREQUIREMENU'))
{
	if (empty($user->socid))    // If internal user or not defined
	{
		$conf->standard_menu = (empty($conf->global->MAIN_MENU_STANDARD_FORCED) ? (empty($conf->global->MAIN_MENU_STANDARD) ? 'eldy_menu.php' : $conf->global->MAIN_MENU_STANDARD) : $conf->global->MAIN_MENU_STANDARD_FORCED);
	} else {
		// If external user
		$conf->standard_menu = (empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED) ? (empty($conf->global->MAIN_MENUFRONT_STANDARD) ? 'eldy_menu.php' : $conf->global->MAIN_MENUFRONT_STANDARD) : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
	}

	// Load the menu manager (only if not already done)
	$file_menu = $conf->standard_menu;
	if (GETPOST('menu', 'alpha')) $file_menu = GETPOST('menu', 'alpha'); // example: menu=eldy_menu.php
	if (!class_exists('MenuManager'))
	{
		$menufound = 0;
		$dirmenus = array_merge(array("/core/menus/"), (array) $conf->modules_parts['menus']);
		foreach ($dirmenus as $dirmenu)
		{
			$menufound = dol_include_once($dirmenu."standard/".$file_menu);
			if (class_exists('MenuManager')) break;
		}
		if (!class_exists('MenuManager'))	// If failed to include, we try with standard eldy_menu.php
		{
			dol_syslog("You define a menu manager '".$file_menu."' that can not be loaded.", LOG_WARNING);
			$file_menu = 'eldy_menu.php';
			include_once DOL_DOCUMENT_ROOT."/core/menus/standard/".$file_menu;
		}
	}
	$menumanager = new MenuManager($db, empty($user->socid) ? 0 : 1);
	$menumanager->loadMenu();
}



// Functions

if (!function_exists("llxHeader"))
{
	/**
	 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
	 *
	 * @param 	string 			$head				Optionnal head lines
	 * @param 	string 			$title				HTML title
	 * @param	string			$help_url			Url links to help page
	 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
	 *                                  			For other external page: http://server/url
	 * @param	string			$target				Target to use on links
	 * @param 	int    			$disablejs			More content into html header
	 * @param 	int    			$disablehead		More content into html header
	 * @param 	array|string  	$arrayofjs			Array of complementary js files
	 * @param 	array|string  	$arrayofcss			Array of complementary css files
	 * @param	string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
	 * @param   string  		$morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
	 * @param	string			$replacemainareaby	Replace call to main_area() by a print of this string
	 * @param	int				$disablenofollow	Disable the "nofollow" on page
	 * @return	void
	 */
	function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0)
	{
		global $conf;

		// html header
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, $disablenofollow);

		$tmpcsstouse = 'sidebar-collapse'.($morecssonbody ? ' '.$morecssonbody : '');
		// If theme MD and classic layer, we open the menulayer by default.
		if ($conf->theme == 'md' && !in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			global $mainmenu;
			if ($mainmenu != 'website') $tmpcsstouse = $morecssonbody; // We do not use sidebar-collpase by default to have menuhider open by default.
		}

		if (!empty($conf->global->MAIN_OPTIMIZEFORCOLORBLIND)) {
			$tmpcsstouse .= ' colorblind-'.strip_tags($conf->global->MAIN_OPTIMIZEFORCOLORBLIND);
		}

		print '<body id="mainbody" class="'.$tmpcsstouse.'">'."\n";

		// top menu and left menu area
		if (empty($conf->dol_hide_topmenu) || GETPOST('dol_invisible_topmenu', 'int'))
		{
			top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $help_url);
		}

		if (empty($conf->dol_hide_leftmenu))
		{
			left_menu('', $help_url, '', '', 1, $title, 1); // $menumanager is retrieved with a global $menumanager inside this function
		}

		// main area
		if ($replacemainareaby)
		{
			print $replacemainareaby;
			return;
		}
		main_area($title);
	}
}


/**
 *  Show HTTP header
 *
 *  @param  string  $contenttype    Content type. For example, 'text/html'
 *  @param	int		$forcenocache	Force disabling of cache for the page
 *  @return	void
 */
function top_httphead($contenttype = 'text/html', $forcenocache = 0)
{
	global $db, $conf, $hookmanager;

	if ($contenttype == 'text/html') header("Content-Type: text/html; charset=".$conf->file->character_set_client);
	else header("Content-Type: ".$contenttype);

	// Security options
	header("X-Content-Type-Options: nosniff"); // With the nosniff option, if the server says the content is text/html, the browser will render it as text/html (note that most browsers now force this option to on)
	if (!defined('XFRAMEOPTIONS_ALLOWALL')) header("X-Frame-Options: SAMEORIGIN"); // Frames allowed only if on same domain (stop some XSS attacks)
	else header("X-Frame-Options: ALLOWALL");
	//header("X-XSS-Protection: 1");      		// XSS filtering protection of some browsers (note: use of Content-Security-Policy is more efficient). Disabled as deprecated.
	if (!defined('FORCECSP'))
	{
		//if (! isset($conf->global->MAIN_HTTP_CONTENT_SECURITY_POLICY))
		//{
		//	// A default security policy that keep usage of js external component like ckeditor, stripe, google, working
		//	$contentsecuritypolicy = "font-src *; img-src *; style-src * 'unsafe-inline' 'unsafe-eval'; default-src 'self' *.stripe.com 'unsafe-inline' 'unsafe-eval'; script-src 'self' *.stripe.com 'unsafe-inline' 'unsafe-eval'; frame-src 'self' *.stripe.com; connect-src 'self';";
		//}
		//else
		$contentsecuritypolicy = empty($conf->global->MAIN_HTTP_CONTENT_SECURITY_POLICY) ? '' : $conf->global->MAIN_HTTP_CONTENT_SECURITY_POLICY;

		if (!is_object($hookmanager)) $hookmanager = new HookManager($db);
		$hookmanager->initHooks(array("main"));

		$parameters = array('contentsecuritypolicy'=>$contentsecuritypolicy);
		$result = $hookmanager->executeHooks('setContentSecurityPolicy', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) $contentsecuritypolicy = $hookmanager->resPrint; // Replace CSP
		else $contentsecuritypolicy .= $hookmanager->resPrint; // Concat CSP

		if (!empty($contentsecuritypolicy))
		{
			// For example, to restrict 'script', 'object', 'frames' or 'img' to some domains:
			// script-src https://api.google.com https://anotherhost.com; object-src https://youtube.com; frame-src https://youtube.com; img-src: https://static.example.com
			// For example, to restrict everything to one domain, except 'object', ...:
			// default-src https://cdn.example.net; object-src 'none'
			// For example, to restrict everything to itself except img that can be on other servers:
			// default-src 'self'; img-src *;
			// Pre-existing site that uses too much inline code to fix but wants to ensure resources are loaded only over https and disable plugins:
			// default-src http: https: 'unsafe-eval' 'unsafe-inline'; object-src 'none'
			header("Content-Security-Policy: ".$contentsecuritypolicy);
		}
	} elseif (constant('FORCECSP'))
	{
		header("Content-Security-Policy: ".constant('FORCECSP'));
	}
	if ($forcenocache)
	{
		header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
	}
}

/**
 * Ouput html header of a page.
 * This code is also duplicated into security2.lib.php::dol_loginfunction
 *
 * @param 	string 	$head			 Optionnal head lines
 * @param 	string 	$title			 HTML title
 * @param 	int    	$disablejs		 Disable js output
 * @param 	int    	$disablehead	 Disable head output
 * @param 	array  	$arrayofjs		 Array of complementary js files
 * @param 	array  	$arrayofcss		 Array of complementary css files
 * @param 	int    	$disablejmobile	 Disable jmobile (No more used)
 * @param   int     $disablenofollow Disable no follow tag
 * @return	void
 */
function top_htmlhead($head, $title = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $disablejmobile = 0, $disablenofollow = 0)
{
	global $db, $conf, $langs, $user, $mysoc, $hookmanager;

	top_httphead();

	if (empty($conf->css)) $conf->css = '/theme/eldy/style.css.php'; // If not defined, eldy by default

	print '<!doctype html>'."\n";

	if (!empty($conf->global->MAIN_USE_CACHE_MANIFEST)) print '<html lang="'.substr($langs->defaultlang, 0, 2).'" manifest="'.DOL_URL_ROOT.'/cache.manifest">'."\n";
	else print '<html lang="'.substr($langs->defaultlang, 0, 2).'">'."\n";
	//print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">'."\n";
	if (empty($disablehead))
	{
		if (!is_object($hookmanager)) $hookmanager = new HookManager($db);
		$hookmanager->initHooks(array("main"));

		$ext = 'layout='.$conf->browser->layout.'&amp;version='.urlencode(DOL_VERSION);

		print "<head>\n";

		if (GETPOST('dol_basehref', 'alpha')) print '<base href="'.dol_escape_htmltag(GETPOST('dol_basehref', 'alpha')).'">'."\n";

		// Displays meta
		print '<meta charset="utf-8">'."\n";
		print '<meta name="robots" content="noindex'.($disablenofollow ? '' : ',nofollow').'">'."\n"; // Do not index
		print '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n"; // Scale for mobile device
		print '<meta name="author" content="Dolibarr Development Team">'."\n";

		// Favicon
		$favicon = DOL_URL_ROOT.'/theme/dolibarr_256x256_color.png';
		if (!empty($mysoc->logo_squarred_mini)) $favicon = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
		if (!empty($conf->global->MAIN_FAVICON_URL)) $favicon = $conf->global->MAIN_FAVICON_URL;
		if (empty($conf->dol_use_jmobile)) print '<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>'."\n"; // Not required into an Android webview

		//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="top" title="'.$langs->trans("Home").'" href="'.(DOL_URL_ROOT?DOL_URL_ROOT:'/').'">'."\n";
		//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="copyright" title="GNU General Public License" href="https://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
		//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="author" title="Dolibarr Development Team" href="https://www.dolibarr.org">'."\n";

		// Mobile appli like icon
		$manifest = DOL_URL_ROOT.'/theme/'.$conf->theme.'/manifest.json.php';
		if (!empty($manifest)) {
			print '<link rel="manifest" href="'.$manifest.'" />'."\n";
		}

		if (!empty($conf->global->THEME_ELDY_TOPMENU_BACK1)) {
			// TODO: use auto theme color switch
			print '<meta name="theme-color" content="rgb('.$conf->global->THEME_ELDY_TOPMENU_BACK1.')">'."\n";
		}

		// Auto refresh page
		if (GETPOST('autorefresh', 'int') > 0) print '<meta http-equiv="refresh" content="'.GETPOST('autorefresh', 'int').'">';

		// Displays title
		$appli = constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli = $conf->global->MAIN_APPLICATION_TITLE;

		print '<title>';
		$titletoshow = '';
		if ($title && !empty($conf->global->MAIN_HTML_TITLE) && preg_match('/noapp/', $conf->global->MAIN_HTML_TITLE)) $titletoshow = dol_htmlentities($title);
		elseif ($title) $titletoshow = dol_htmlentities($appli.' - '.$title);
		else $titletoshow = dol_htmlentities($appli);

		$parameters = array('title'=>$titletoshow);
		$result = $hookmanager->executeHooks('setHtmlTitle', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) $titletoshow = $hookmanager->resPrint; // Replace Title to show
		else $titletoshow .= $hookmanager->resPrint; // Concat to Title to show

		print $titletoshow;
		print '</title>';

		print "\n";

		if (GETPOST('version', 'int')) $ext = 'version='.GETPOST('version', 'int'); // usefull to force no cache on css/js

		$themeparam = '?lang='.$langs->defaultlang.'&amp;theme='.$conf->theme.(GETPOST('optioncss', 'aZ09') ? '&amp;optioncss='.GETPOST('optioncss', 'aZ09', 1) : '').'&amp;userid='.$user->id.'&amp;entity='.$conf->entity;
		$themeparam .= ($ext ? '&amp;'.$ext : '').'&amp;revision='.$conf->global->MAIN_IHM_PARAMS_REV;
		if (!empty($_SESSION['dol_resetcache'])) $themeparam .= '&amp;dol_resetcache='.$_SESSION['dol_resetcache'];
		if (GETPOSTISSET('dol_hide_topmenu')) { $themeparam .= '&amp;dol_hide_topmenu='.GETPOST('dol_hide_topmenu', 'int'); }
		if (GETPOSTISSET('dol_hide_leftmenu')) { $themeparam .= '&amp;dol_hide_leftmenu='.GETPOST('dol_hide_leftmenu', 'int'); }
		if (GETPOSTISSET('dol_optimize_smallscreen')) { $themeparam .= '&amp;dol_optimize_smallscreen='.GETPOST('dol_optimize_smallscreen', 'int'); }
		if (GETPOSTISSET('dol_no_mouse_hover')) { $themeparam .= '&amp;dol_no_mouse_hover='.GETPOST('dol_no_mouse_hover', 'int'); }
		if (GETPOSTISSET('dol_use_jmobile')) { $themeparam .= '&amp;dol_use_jmobile='.GETPOST('dol_use_jmobile', 'int'); $conf->dol_use_jmobile = GETPOST('dol_use_jmobile', 'int'); }
		if (GETPOSTISSET('THEME_DARKMODEENABLED')) { $themeparam .= '&amp;THEME_DARKMODEENABLED='.GETPOST('THEME_DARKMODEENABLED', 'int'); }
		if (GETPOSTISSET('THEME_SATURATE_RATIO')) { $themeparam .= '&amp;THEME_SATURATE_RATIO='.GETPOST('THEME_SATURATE_RATIO', 'int'); }

		if (!empty($conf->global->MAIN_ENABLE_FONT_ROBOTO)) {
			print '<link rel="preconnect" href="https://fonts.gstatic.com">'."\n";
			print '<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@200;300;400;500;600&display=swap" rel="stylesheet">'."\n";
		}

		if (!defined('DISABLE_JQUERY') && !$disablejs && $conf->use_javascript_ajax)
		{
			print '<!-- Includes CSS for JQuery (Ajax library) -->'."\n";
			$jquerytheme = 'base';
			if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
			if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css'.($ext ? '?'.$ext : '').'">'."\n"; // Forced JQuery
			else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui.css'.($ext ? '?'.$ext : '').'">'."\n"; // JQuery
			if (!defined('DISABLE_JQUERY_JNOTIFY')) print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css'.($ext ? '?'.$ext : '').'">'."\n"; // JNotify
			if (!defined('DISABLE_SELECT2') && (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')))     // jQuery plugin "mutiselect", "multiple-select", "select2"...
			{
				$tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ?constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
				print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/css/'.$tmpplugin.'.css'.($ext ? '?'.$ext : '').'">'."\n";
			}
		}

		if (!defined('DISABLE_FONT_AWSOME'))
		{
			print '<!-- Includes CSS for font awesome -->'."\n";
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/fontawesome-5/css/all.min.css'.($ext ? '?'.$ext : '').'">'."\n";
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/fontawesome-5/css/v4-shims.min.css'.($ext ? '?'.$ext : '').'">'."\n";
		}

		print '<!-- Includes CSS for Dolibarr theme -->'."\n";
		// Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
		$themepath = dol_buildpath($conf->css, 1);
		$themesubdir = '';
		if (!empty($conf->modules_parts['theme']))	// This slow down
		{
			foreach ($conf->modules_parts['theme'] as $reldir)
			{
				if (file_exists(dol_buildpath($reldir.$conf->css, 0)))
				{
					$themepath = dol_buildpath($reldir.$conf->css, 1);
					$themesubdir = $reldir;
					break;
				}
			}
		}

		//print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
		print '<link rel="stylesheet" type="text/css" href="'.$themepath.$themeparam.'">'."\n";
		if (!empty($conf->global->MAIN_FIX_FLASH_ON_CHROME)) print '<!-- Includes CSS that does not exists as a workaround of flash bug of chrome -->'."\n".'<link rel="stylesheet" type="text/css" href="filethatdoesnotexiststosolvechromeflashbug">'."\n";

		// CSS forced by modules (relative url starting with /)
		if (!empty($conf->modules_parts['css']))
		{
			$arraycss = (array) $conf->modules_parts['css'];
			foreach ($arraycss as $modcss => $filescss)
			{
				$filescss = (array) $filescss; // To be sure filecss is an array
				foreach ($filescss as $cssfile)
				{
					if (empty($cssfile)) dol_syslog("Warning: module ".$modcss." declared a css path file into its descriptor that is empty.", LOG_WARNING);
					// cssfile is a relative path
					print '<!-- Includes CSS added by module '.$modcss.' -->'."\n".'<link rel="stylesheet" type="text/css" href="'.dol_buildpath($cssfile, 1);
					// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters, so browser cache is not used.
					if (!preg_match('/\.css$/i', $cssfile)) print $themeparam;
					print '">'."\n";
				}
			}
		}
		// CSS forced by page in top_htmlhead call (relative url starting with /)
		if (is_array($arrayofcss))
		{
			foreach ($arrayofcss as $cssfile)
			{
				if (preg_match('/^(http|\/\/)/i', $cssfile))
				{
					$urltofile = $cssfile;
				} else {
					$urltofile = dol_buildpath($cssfile, 1);
				}
				print '<!-- Includes CSS added by page -->'."\n".'<link rel="stylesheet" type="text/css" title="default" href="'.$urltofile;
				// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters and browser cache is not used.
				if (!preg_match('/\.css$/i', $cssfile)) print $themeparam;
				print '">'."\n";
			}
		}

		// Output standard javascript links
		if (!defined('DISABLE_JQUERY') && !$disablejs && !empty($conf->use_javascript_ajax))
		{
			// JQuery. Must be before other includes
			print '<!-- Includes JS for JQuery -->'."\n";
			if (defined('JS_JQUERY') && constant('JS_JQUERY')) print '<script src="'.JS_JQUERY.'jquery.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			else print '<script src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			/*if (! empty($conf->global->MAIN_FEATURES_LEVEL) && ! defined('JS_JQUERY_MIGRATE_DISABLED'))
			{
				if (defined('JS_JQUERY_MIGRATE') && constant('JS_JQUERY_MIGRATE')) print '<script src="'.JS_JQUERY_MIGRATE.'jquery-migrate.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				else print '<script src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-migrate.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			}*/
			if (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI')) print '<script src="'.JS_JQUERY_UI.'jquery-ui.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			else print '<script src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			if (!defined('DISABLE_JQUERY_TABLEDND')) print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			// jQuery jnotify
			if (empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && !defined('DISABLE_JQUERY_JNOTIFY')) {
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			// Chart
			if (empty($conf->global->MAIN_JS_GRAPH) || $conf->global->MAIN_JS_GRAPH == 'chart') {
				print '<script src="'.DOL_URL_ROOT.'/includes/nnnick/chartjs/dist/Chart.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}

			// jQuery jeditable for Edit In Place features
			if (!empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && !defined('DISABLE_JQUERY_JEDITABLE')) {
				print '<!-- JS to manage editInPlace feature -->'."\n";
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-datepicker.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-autocomplete.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script>'."\n";
				print 'var urlSaveInPlace = \''.DOL_URL_ROOT.'/core/ajax/saveinplace.php\';'."\n";
				print 'var urlLoadInPlace = \''.DOL_URL_ROOT.'/core/ajax/loadinplace.php\';'."\n";
				print 'var tooltipInPlace = \''.$langs->transnoentities('ClickToEdit').'\';'."\n"; // Added in title attribute of span
				print 'var placeholderInPlace = \'&nbsp;\';'."\n"; // If we put another string than $langs->trans("ClickToEdit") here, nothing is shown. If we put empty string, there is error, Why ?
				print 'var cancelInPlace = \''.$langs->trans("Cancel").'\';'."\n";
				print 'var submitInPlace = \''.$langs->trans('Ok').'\';'."\n";
				print 'var indicatorInPlace = \'<img src="'.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'">\';'."\n";
				print 'var withInPlace = 300;'; // width in pixel for default string edit
				print '</script>'."\n";
				print '<script src="'.DOL_URL_ROOT.'/core/js/editinplace.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ckeditor.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			// jQuery Timepicker
			if (!empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER')) {
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script src="'.DOL_URL_ROOT.'/core/js/timepicker.js.php?lang='.$langs->defaultlang.($ext ? '&amp;'.$ext : '').'"></script>'."\n";
			}
			if (!defined('DISABLE_SELECT2') && (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))) {
				// jQuery plugin "mutiselect", "multiple-select", "select2", ...
				$tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ?constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/js/'.$tmpplugin.'.full.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n"; // We include full because we need the support of containerCssClass
			}
			if (!defined('DISABLE_MULTISELECT'))     // jQuery plugin "mutiselect" to select with checkboxes. Can be removed once we have an enhanced search tool
			{
				print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/multiselect/jquery.multi-select.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
		}

		if (!$disablejs && !empty($conf->use_javascript_ajax)) {
			// CKEditor
			if ((!empty($conf->fckeditor->enabled) && (empty($conf->global->FCKEDITOR_EDITORNAME) || $conf->global->FCKEDITOR_EDITORNAME == 'ckeditor') && !defined('DISABLE_CKEDITOR')) || defined('FORCE_CKEDITOR'))
			{
				print '<!-- Includes JS for CKEditor -->'."\n";
				$pathckeditor = DOL_URL_ROOT.'/includes/ckeditor/ckeditor/';
				$jsckeditor = 'ckeditor.js';
				if (constant('JS_CKEDITOR')) {
					// To use external ckeditor 4 js lib
					$pathckeditor = constant('JS_CKEDITOR');
				}
				print '<script><!-- enable ckeditor by main.inc.php -->';
				print 'var CKEDITOR_BASEPATH = \''.$pathckeditor.'\';'."\n";
				print 'var ckeditorConfig = \''.dol_buildpath($themesubdir.'/theme/'.$conf->theme.'/ckeditor/config.js'.($ext ? '?'.$ext : ''), 1).'\';'."\n"; // $themesubdir='' in standard usage
				print 'var ckeditorFilebrowserBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
				print 'var ckeditorFilebrowserImageBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Type=Image&Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
				print '</script>'."\n";
				print '<script src="'.$pathckeditor.$jsckeditor.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script>';
				if (GETPOST('mode', 'aZ09') == 'Full_inline')
				{
					print 'CKEDITOR.disableAutoInline = false;'."\n";
				} else {
					print 'CKEDITOR.disableAutoInline = true;'."\n";
				}
				print '</script>'."\n";
			}

			// Browser notifications (if NOREQUIREMENU is on, it is mostly a page for popup, so we do not enable notif too. We hide also for public pages).
			if (!defined('NOBROWSERNOTIF') && !defined('NOREQUIREMENU') && !defined('NOLOGIN'))
			{
				$enablebrowsernotif = false;
				if (!empty($conf->agenda->enabled) && !empty($conf->global->AGENDA_REMINDER_BROWSER)) $enablebrowsernotif = true;
				if ($conf->browser->layout == 'phone') $enablebrowsernotif = false;
				if ($enablebrowsernotif)
				{
					print '<!-- Includes JS of Dolibarr (browser layout = '.$conf->browser->layout.')-->'."\n";
					print '<script src="'.DOL_URL_ROOT.'/core/js/lib_notification.js.php'.($ext ? '?'.$ext : '').'"></script>'."\n";
				}
			}

			// Global js function
			print '<!-- Includes JS of Dolibarr -->'."\n";
			print '<script src="'.DOL_URL_ROOT.'/core/js/lib_head.js.php?lang='.$langs->defaultlang.($ext ? '&'.$ext : '').'"></script>'."\n";

			// JS forced by modules (relative url starting with /)
			if (!empty($conf->modules_parts['js']))		// $conf->modules_parts['js'] is array('module'=>array('file1','file2'))
			{
				$arrayjs = (array) $conf->modules_parts['js'];
				foreach ($arrayjs as $modjs => $filesjs)
				{
					$filesjs = (array) $filesjs; // To be sure filejs is an array
					foreach ($filesjs as $jsfile)
					{
						// jsfile is a relative path
						print '<!-- Include JS added by module '.$modjs.'-->'."\n".'<script src="'.dol_buildpath($jsfile, 1).'"></script>'."\n";
					}
				}
			}
			// JS forced by page in top_htmlhead (relative url starting with /)
			if (is_array($arrayofjs))
			{
				print '<!-- Includes JS added by page -->'."\n";
				foreach ($arrayofjs as $jsfile)
				{
					if (preg_match('/^(http|\/\/)/i', $jsfile))
					{
						print '<script src="'.$jsfile.'"></script>'."\n";
					} else {
						print '<script src="'.dol_buildpath($jsfile, 1).'"></script>'."\n";
					}
				}
			}
		}

		if (!empty($head)) print $head."\n";
		if (!empty($conf->global->MAIN_HTML_HEADER)) print $conf->global->MAIN_HTML_HEADER."\n";

		$parameters = array();
		$result = $hookmanager->executeHooks('addHtmlHeader', $parameters); // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint; // Replace Title to show

		print "</head>\n\n";
	}

	$conf->headerdone = 1; // To tell header was output
}


/**
 *  Show an HTML header + a BODY + The top menu bar
 *
 *  @param      string	$head    			Lines in the HEAD
 *  @param      string	$title   			Title of web page
 *  @param      string	$target  			Target to use in menu links (Example: '' or '_top')
 *	@param		int		$disablejs			Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *	@param		int		$disablehead		Do not output head section
 *	@param		array	$arrayofjs			Array of js files to add in header
 *	@param		array	$arrayofcss			Array of css files to add in header
 *  @param		string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 *  @param      string	$helppagename    	Name of wiki page for help ('' by default).
 * 				     		                Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 * 						                    For other external page: http://server/url
 *  @return		void
 */
function top_menu($head, $title = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $helppagename = '')
{
	global $user, $conf, $langs, $db;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $hookmanager, $menumanager;

	$searchform = '';
	$bookmarks = '';

	// Instantiate hooks for external modules
	$hookmanager->initHooks(array('toprightmenu'));

	$toprightmenu = '';

	// For backward compatibility with old modules
	if (empty($conf->headerdone))
	{
		$disablenofollow = 0;
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, $disablenofollow);
		print '<body id="mainbody">';
	}

	/*
     * Top menu
     */
	if ((empty($conf->dol_hide_topmenu) || GETPOST('dol_invisible_topmenu', 'int')) && (!defined('NOREQUIREMENU') || !constant('NOREQUIREMENU')))
	{
		if (!isset($form) || !is_object($form)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($db);
		}

		print "\n".'<!-- Start top horizontal -->'."\n";

		print '<div class="side-nav-vert'.(GETPOST('dol_invisible_topmenu', 'int') ? ' hidden' : '').'"><div id="id-top">'; // dol_invisible_topmenu differs from dol_hide_topmenu: dol_invisible_topmenu means we output menu but we make it invisible.

		// Show menu entries
		print '<div id="tmenu_tooltip'.(empty($conf->global->MAIN_MENU_INVERT) ? '' : 'invert').'" class="tmenu">'."\n";
		$menumanager->atarget = $target;
		$menumanager->showmenu('top', array('searchform'=>$searchform, 'bookmarks'=>$bookmarks)); // This contains a \n
		print "</div>\n";

		// Define link to login card
		$appli = constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE))
		{
			$appli = $conf->global->MAIN_APPLICATION_TITLE;
			if (preg_match('/\d\.\d/', $appli))
			{
				if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
			} else $appli .= " ".DOL_VERSION;
		} else $appli .= " ".DOL_VERSION;

		if (!empty($conf->global->MAIN_FEATURES_LEVEL)) $appli .= "<br>".$langs->trans("LevelOfFeature").': '.$conf->global->MAIN_FEATURES_LEVEL;

		$logouttext = '';
		$logouthtmltext = '';
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			//$logouthtmltext=$appli.'<br>';
			if ($_SESSION["dol_authmode"] != 'forceuser' && $_SESSION["dol_authmode"] != 'http')
			{
				$logouthtmltext .= $langs->trans("Logout").'<br>';

				$logouttext .= '<a accesskey="l" href="'.DOL_URL_ROOT.'/user/logout.php">';
				$logouttext .= img_picto($langs->trans('Logout'), 'sign-out', '', false, 0, 0, '', 'atoplogin');
				$logouttext .= '</a>';
			} else {
				$logouthtmltext .= $langs->trans("NoLogoutProcessWithAuthMode", $_SESSION["dol_authmode"]);
				$logouttext .= img_picto($langs->trans('Logout'), 'sign-out', '', false, 0, 0, '', 'atoplogin opacitymedium');
			}
		}

		print '<div class="login_block usedropdown">'."\n";

		$toprightmenu .= '<div class="login_block_other">';

		// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
		$parameters = array();
		$result = $hookmanager->executeHooks('printTopRightMenu', $parameters); // Note that $action and $object may have been modified by some hooks
		if (is_numeric($result))
		{
			if ($result == 0)
				$toprightmenu .= $hookmanager->resPrint; // add
			else {
				$toprightmenu = $hookmanager->resPrint; // replace
			}
		} else {
			$toprightmenu .= $result; // For backward compatibility
		}

		// Link to module builder
		if (!empty($conf->modulebuilder->enabled))
		{
			$text = '<a href="'.DOL_URL_ROOT.'/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="modulebuilder">';
			//$text.= img_picto(":".$langs->trans("ModuleBuilder"), 'printer_top.png', 'class="printer"');
			$text .= '<span class="fa fa-bug atoplogin valignmiddle"></span>';
			$text .= '</a>';
			$toprightmenu .= $form->textwithtooltip('', $langs->trans("ModuleBuilder"), 2, 1, $text, 'login_block_elem', 2);
		}

		// Link to print main content area
		if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $conf->browser->layout != 'phone')
		{
			$qs = dol_escape_htmltag($_SERVER["QUERY_STRING"]);

			if (is_array($_POST))
			{
				foreach ($_POST as $key=>$value) {
					if ($key !== 'action' && $key !== 'password' && !is_array($value)) $qs .= '&'.$key.'='.urlencode($value);
				}
			}
			$qs .= (($qs && $morequerystring) ? '&' : '').$morequerystring;
			$text = '<a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.$qs.($qs ? '&' : '').'optioncss=print" target="_blank">';
			//$text.= img_picto(":".$langs->trans("PrintContentArea"), 'printer_top.png', 'class="printer"');
			$text .= '<span class="fa fa-print atoplogin valignmiddle"></span>';
			$text .= '</a>';
			$toprightmenu .= $form->textwithtooltip('', $langs->trans("PrintContentArea"), 2, 1, $text, 'login_block_elem', 2);
		}

		// Link to Dolibarr wiki pages
		if (empty($conf->global->MAIN_HELP_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			$langs->load("help");

			$helpbaseurl = '';
			$helppage = '';
			$mode = '';
			$helppresent = '';

			if (empty($helppagename)) {
				$helppagename = 'EN:User_documentation|FR:Documentation_utilisateur|ES:Documentación_usuarios';
			} else {
				$helppresent = 'helppresent';
			}

			// Get helpbaseurl, helppage and mode from helppagename and langs
			$arrayres = getHelpParamFor($helppagename, $langs);
			$helpbaseurl = $arrayres['helpbaseurl'];
			$helppage = $arrayres['helppage'];
			$mode = $arrayres['mode'];

			// Link to help pages
			if ($helpbaseurl && $helppage)
			{
				$text = '';
				$title = $langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage' : 'GoToHelpPage').'...';
				if ($mode == 'wiki') {
					$title .= '<br>'.$langs->trans("PageWiki").' '.dol_escape_htmltag('"'.strtr($helppage, '_', ' ').'"');
					if ($helppresent) $title .= ' <span class="opacitymedium">('.$langs->trans("DedicatedPageAvailable").')</span>';
					else $title .= ' <span class="opacitymedium">('.$langs->trans("HomePage").')</span>';
				}
				$text .= '<a class="help" target="_blank" rel="noopener" href="';
				if ($mode == 'wiki') $text .= sprintf($helpbaseurl, urlencode(html_entity_decode($helppage)));
				else $text .= sprintf($helpbaseurl, $helppage);
				$text .= '">';
				$text .= '<span class="fa fa-question-circle atoplogin valignmiddle'.($helppresent ? ' '.$helppresent : '').'"></span>';
				if ($helppresent) $text .= '<span class="fa fa-circle helppresentcircle"></span>';
				$text .= '</a>';
				$toprightmenu .= $form->textwithtooltip('', $title, 2, 1, $text, 'login_block_elem', 2);
			}

			// Version
			if (!empty($conf->global->MAIN_SHOWDATABASENAMEINHELPPAGESLINK)) {
				$langs->load('admin');
				$appli .= '<br>'.$langs->trans("Database").': '.$db->database_name;
			}
		}

		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$text = '<span class="aversion"><span class="hideonsmartphone small">'.DOL_VERSION.'</span></span>';
			$toprightmenu .= $form->textwithtooltip('', $appli, 2, 1, $text, 'login_block_elem', 2);
		}

		// Logout link
		$toprightmenu .= $form->textwithtooltip('', $logouthtmltext, 2, 1, $logouttext, 'login_block_elem logout-btn', 2);

		$toprightmenu .= '</div>'; // end div class="login_block_other"


		// Add login user link
		$toprightmenu .= '<div class="login_block_user">';

		// Login name with photo and tooltip
		$mode = -1;
		$toprightmenu .= '<div class="inline-block nowrap"><div class="inline-block login_block_elem login_block_elem_name" style="padding: 0px;">';

		if (!empty($conf->global->MAIN_USE_TOP_MENU_SEARCH_DROPDOWN)) {
			// Add search dropdown
			$toprightmenu .= top_menu_search();
		}

		if (!empty($conf->global->MAIN_USE_TOP_MENU_QUICKADD_DROPDOWN)) {
			// Add search dropdown
			$toprightmenu .= top_menu_quickadd();
		}

		// Add bookmark dropdown
		$toprightmenu .= top_menu_bookmark();

		// Add user dropdown
		$toprightmenu .= top_menu_user();

		$toprightmenu .= '</div></div>';

		$toprightmenu .= '</div>'."\n";


		print $toprightmenu;

		print "</div>\n"; // end div class="login_block"

		print '</div></div>';

		print '<div style="clear: both;"></div>';
		print "<!-- End top horizontal menu -->\n\n";
	}

	if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) print '<!-- Begin div id-container --><div id="id-container" class="id-container">';
}


/**
 * Build the tooltip on user login
 *
 * @param	int			$hideloginname		Hide login name. Show only the image.
 * @param	string		$urllogout			URL for logout
 * @return  string                  		HTML content
 */
function top_menu_user($hideloginname = 0, $urllogout = '')
{
	global $langs, $conf, $db, $hookmanager, $user;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $menumanager;

	$userImage = $userDropDownImage = '';
	if (!empty($user->photo))
	{
		$userImage          = Form::showphoto('userphoto', $user, 0, 0, 0, 'photouserphoto userphoto', 'small', 0, 1);
		$userDropDownImage  = Form::showphoto('userphoto', $user, 0, 0, 0, 'dropdown-user-image', 'small', 0, 1);
	} else {
		$nophoto = '/public/theme/common/user_anonymous.png';
		if ($user->gender == 'man') $nophoto = '/public/theme/common/user_man.png';
		if ($user->gender == 'woman') $nophoto = '/public/theme/common/user_woman.png';

		$userImage = '<img class="photo photouserphoto userphoto" alt="No photo" src="'.DOL_URL_ROOT.$nophoto.'">';
		$userDropDownImage = '<img class="photo dropdown-user-image" alt="No photo" src="'.DOL_URL_ROOT.$nophoto.'">';
	}

	$dropdownBody = '';
	$dropdownBody .= '<span id="topmenuloginmoreinfo-btn"><i class="fa fa-caret-right"></i> '.$langs->trans("ShowMoreInfos").'</span>';
	$dropdownBody .= '<div id="topmenuloginmoreinfo" >';

	// login infos
	if (!empty($user->admin)) {
		$dropdownBody .= '<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	}
	if (!empty($user->socid))	// Add thirdparty for external users
	{
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($user->socid);
		$companylink = ' '.$thirdpartystatic->getNomUrl(2); // picto only of company
		$company = ' ('.$langs->trans("Company").': '.$thirdpartystatic->name.')';
	}
	$type = ($user->socid ? $langs->trans("External").$company : $langs->trans("Internal"));
	$dropdownBody .= '<br><b>'.$langs->trans("Type").':</b> '.$type;
	$dropdownBody .= '<br><b>'.$langs->trans("Status").'</b>: '.$user->getLibStatut(0);
	$dropdownBody .= '<br>';

	$dropdownBody .= '<br><u>'.$langs->trans("Session").'</u>';
	$dropdownBody .= '<br><b>'.$langs->trans("IPAddress").'</b>: '.dol_escape_htmltag($_SERVER["REMOTE_ADDR"]);
	if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $dropdownBody .= '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (user entity '.$user->entity.')';
	$dropdownBody .= '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.$_SESSION["dol_authmode"].(empty($dolibarr_main_demo) ? '' : ' (demo)');
	$dropdownBody .= '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($user->datelastlogin, "dayhour", 'tzuser');
	$dropdownBody .= '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser');
	$dropdownBody .= '<br><b>'.$langs->trans("CurrentTheme").':</b> '.$conf->theme;
	$dropdownBody .= '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.$menumanager->name;
	$langFlag = picto_from_langcode($langs->getDefaultLang());
	$dropdownBody .= '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.($langFlag ? $langFlag.' ' : '').$langs->getDefaultLang();
	$dropdownBody .= '<br><b>'.$langs->trans("Browser").':</b> '.$conf->browser->name.($conf->browser->version ? ' '.$conf->browser->version : '').' ('.dol_escape_htmltag($_SERVER['HTTP_USER_AGENT']).')';
	$dropdownBody .= '<br><b>'.$langs->trans("Layout").':</b> '.$conf->browser->layout;
	$dropdownBody .= '<br><b>'.$langs->trans("Screen").':</b> '.$_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];
	if ($conf->browser->layout == 'phone') $dropdownBody .= '<br><b>'.$langs->trans("Phone").':</b> '.$langs->trans("Yes");
	if (!empty($_SESSION["disablemodules"])) $dropdownBody .= '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.join(', ', explode(',', $_SESSION["disablemodules"]));
	$dropdownBody .= '</div>';

	// Execute hook
	$parameters = array('user'=>$user, 'langs' => $langs);
	$result = $hookmanager->executeHooks('printTopRightMenuLoginDropdownBody', $parameters); // Note that $action and $object may have been modified by some hooks
	if (is_numeric($result))
	{
		if ($result == 0) {
			$dropdownBody .= $hookmanager->resPrint; // add
		} else {
			$dropdownBody = $hookmanager->resPrint; // replace
		}
	}

	if (empty($urllogout)) {
		$urllogout = DOL_URL_ROOT.'/user/logout.php';
	}
	$logoutLink = '<a accesskey="l" href="'.$urllogout.'" class="button-top-menu-dropdown" ><i class="fa fa-sign-out-alt"></i> '.$langs->trans("Logout").'</a>';
	$profilLink = '<a accesskey="l" href="'.DOL_URL_ROOT.'/user/card.php?id='.$user->id.'" class="button-top-menu-dropdown" ><i class="fa fa-user"></i>  '.$langs->trans("Card").'</a>';


	$profilName = $user->getFullName($langs).' ('.$user->login.')';

	if (!empty($user->admin)) {
		$profilName = '<i class="far fa-star classfortooltip" title="'.$langs->trans("Administrator").'" ></i> '.$profilName;
	}

	// Define version to show
	$appli = constant('DOL_APPLICATION_TITLE');
	if (!empty($conf->global->MAIN_APPLICATION_TITLE))
	{
		$appli = $conf->global->MAIN_APPLICATION_TITLE;
		if (preg_match('/\d\.\d/', $appli))
		{
			if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
		} else $appli .= " ".DOL_VERSION;
	} else $appli .= " ".DOL_VERSION;

	if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
		$btnUser = '<!-- div for user link -->
	    <div id="topmenu-login-dropdown" class="userimg atoplogin dropdown user user-menu inline-block">
	        <a href="'.DOL_URL_ROOT.'/user/card.php?id='.$user->id.'" class="dropdown-toggle login-dropdown-a" data-toggle="dropdown">
	            '.$userImage.'
	            <span class="hidden-xs maxwidth200 atoploginusername hideonsmartphone paddingleft">'.dol_trunc($user->firstname ? $user->firstname : $user->login, 10).'</span>
	        </a>
	        <div class="dropdown-menu">
	            <!-- User image -->
	            <div class="user-header">
	                '.$userDropDownImage.'
	                <p>
	                    '.$profilName.'<br>';
		if ($user->datepreviouslogin) {
			$btnUser .= '<small class="classfortooltip" title="'.$langs->trans("PreviousConnexion").'" ><i class="fa fa-user-clock"></i> '.dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser').'</small><br>';
		}
		//$btnUser .= '<small class="classfortooltip"><i class="fa fa-cog"></i> '.$langs->trans("Version").' '.$appli.'</small>';
		$btnUser .= '
	                </p>
	            </div>

	            <!-- Menu Body -->
	            <div class="user-body">'.$dropdownBody.'</div>

	            <!-- Menu Footer-->
	            <div class="user-footer">
	                <div class="pull-left">
	                    '.$profilLink.'
	                </div>
	                <div class="pull-right">
	                    '.$logoutLink.'
	                </div>
	                <div style="clear:both;"></div>
	            </div>

	        </div>
	    </div>';
	} else {
		$btnUser = '<!-- div for user link -->
	    <div id="topmenu-login-dropdown" class="userimg atoplogin dropdown user user-menu  inline-block">
	    	<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$user->id.'">
	    	'.$userImage.'
	    		<span class="hidden-xs maxwidth200 atoploginusername hideonsmartphone">'.dol_trunc($user->firstname ? $user->firstname : $user->login, 10).'</span>
	    		</a>
		</div>';
	}

	if (!defined('JS_JQUERY_DISABLE_DROPDOWN') && !empty($conf->use_javascript_ajax))    // This may be set by some pages that use different jquery version to avoid errors
	{
		$btnUser .= '
        <!-- Code to show/hide the user drop-down -->
        <script>
        $( document ).ready(function() {
            $(document).on("click", function(event) {
                if (!$(event.target).closest("#topmenu-login-dropdown").length) {
					//console.log("close login dropdown");
					// Hide the menus.
                    $("#topmenu-login-dropdown").removeClass("open");
                }
            });
			';

		if ($conf->theme != 'md') {
			$btnUser .= '
	            $("#topmenu-login-dropdown .dropdown-toggle").on("click", function(event) {
					console.log("toggle login dropdown");
					event.preventDefault();
	                $("#topmenu-login-dropdown").toggleClass("open");
	            });

	            $("#topmenuloginmoreinfo-btn").on("click", function() {
	                $("#topmenuloginmoreinfo").slideToggle();
	            });';
		}

		$btnUser .= '
        });
        </script>
        ';
	}

	return $btnUser;
}

/**
 * Build the tooltip on top menu quick add
 *
 * @return  string                  HTML content
 */
function top_menu_quickadd()
{
	global $langs, $conf, $db, $hookmanager, $user;
	global $menumanager;
	$html = '';
	// Define $dropDownQuickAddHtml
	$dropDownQuickAddHtml = '<div class="dropdown-header bookmark-header center">';
	$dropDownQuickAddHtml .= $langs->trans('QuickAdd');
	$dropDownQuickAddHtml .= '</div>';

	$dropDownQuickAddHtml .= '<div class="quickadd-body dropdown-body">';
	$dropDownQuickAddHtml .= '<div class="quickadd">';
	if (!empty($conf->societe->enabled) && $user->rights->societe->creer) {
		$langs->load("companies");
		$dropDownQuickAddHtml .= '
                <!-- Thirdparty link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/societe/card.php?action=create" title="'.$langs->trans("MenuNewThirdParty").'">
                        <i class="fa fa-building"></i><br>
                        '.$langs->trans("ThirdParty").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->societe->enabled) && $user->rights->societe->contact->creer) {
		$langs->load("companies");
		$dropDownQuickAddHtml .= '
                <!-- Contact link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/contact/card.php?action=create" title="'.$langs->trans("NewContactAddress").'">
                        <i class="fa fa-address-book"></i><br>
                        '.$langs->trans("Contact").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->propal->enabled) && $user->rights->propale->creer) {
		$langs->load("propal");
		$dropDownQuickAddHtml .= '
                <!-- Propal link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/comm/propal/card.php?action=create" title="'.$langs->trans("NewPropal").'">
                        <i class="fa fa-suitcase"></i><br>
                        '.$langs->trans("Proposal").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->commande->enabled) && $user->rights->commande->creer) {
		$langs->load("orders");
		$dropDownQuickAddHtml .= '
                <!-- Order link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/commande/card.php?action=create" title="'.$langs->trans("NewOrder").'">
                        <i class="fa fa-file-alt"></i><br>
                        '.$langs->trans("Order").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->facture->enabled) && $user->rights->facture->creer) {
		$langs->load("bills");
		$dropDownQuickAddHtml .= '
                <!-- Invoice link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create" title="'.$langs->trans("NewBill").'">
                        <i class="fa fa-coins"></i><br>
                        '.$langs->trans("Bill").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->contrat->enabled) && $user->rights->contrat->creer) {
		$langs->load("contracts");
		$dropDownQuickAddHtml .= '
                <!-- Contract link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create" title="'.$langs->trans("NewContractSubscription").'">
                        <i class="fa fa-file-contract"></i><br>
                        '.$langs->trans("Contract").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->creer) {
		$langs->load("supplier_proposal");
		$dropDownQuickAddHtml .= '
                <!-- Supplier proposal link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/supplier_proposal/card.php?action=create" title="'.$langs->trans("NewAskPrice").'">
                        <i class="fa fa-suitcase"></i><br>
                        '.$langs->trans("AskPrice").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->creer) {
		$langs->load("orders");
		$dropDownQuickAddHtml .= '
                <!-- Supplier order link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?action=create" title="'.$langs->trans("NewOrder").'">
                        <i class="fa fa-file-alt"></i><br>
                        '.$langs->trans("SupplierOrder").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->creer) {
		$langs->load("bills");
		$dropDownQuickAddHtml .= '
                <!-- Supplier invoice link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create" title="'.$langs->trans("NewBill").'">
                        <i class="fa fa-coins"></i><br>
                        '.$langs->trans("SupplierBill").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->product->enabled) && $user->rights->produit->creer) {
		$langs->load("products");
		$dropDownQuickAddHtml .= '
                <!-- Product link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/product/card.php?action=create&amp;type=0" title="'.$langs->trans("NewProduct").'">
                        <i class="fa fa-cube"></i><br>
                        '.$langs->trans("Product").'
                    </a>
                </div>
                ';
	}

	if (!empty($conf->service->enabled) && $user->rights->service->creer) {
		$langs->load("products");
		$dropDownQuickAddHtml .= '
                <!-- Service link -->
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="'.DOL_URL_ROOT.'/product/card.php?action=create&amp;type=1" title="'.$langs->trans("NewService").'">
                        <i class="fa fa-concierge-bell"></i><br>
                        '.$langs->trans("Service").'
                    </a>
                </div>
                ';
	}

	// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
	$parameters = array();
	$result = $hookmanager->executeHooks('printQuickAddBlock', $parameters); // Note that $action and $object may have been modified by some hooks
	if (is_numeric($result)) {
		if ($result == 0) {
			$dropDownQuickAddHtml .= $hookmanager->resPrint; // add
		} else {
			$dropDownQuickAddHtml = $hookmanager->resPrint; // replace
		}
	} else {
		$dropDownQuickAddHtml .= $result; // For backward compatibility
	}

	$dropDownQuickAddHtml .= '</div>';
	$dropDownQuickAddHtml .= '</div>';

	$html .= '<!-- div for quick add link -->
    <div id="topmenu-quickadd-dropdown" class="atoplogin dropdown inline-block">
        <a class="dropdown-toggle login-dropdown-a" data-toggle="dropdown" href="#" title="'.$langs->trans('QuickAdd').' ('.$langs->trans('QuickAddMenuShortCut').')">
            <i class="fa fa-plus-circle" ></i>
        </a>

        <div class="dropdown-menu">
            '.$dropDownQuickAddHtml.'
        </div>
    </div>';
	$html .= '
        <!-- Code to show/hide the user drop-down -->
        <script>
        $( document ).ready(function() {
            $(document).on("click", function(event) {
                if (!$(event.target).closest("#topmenu-quickadd-dropdown").length) {
                    // Hide the menus.
                    $("#topmenu-quickadd-dropdown").removeClass("open");
                }
            });
            $("#topmenu-quickadd-dropdown .dropdown-toggle").on("click", function(event) {
                openQuickAddDropDown();
            });
            // Key map shortcut
            $(document).keydown(function(e){
                  if( e.which === 76 && e.ctrlKey && e.shiftKey ){
                     console.log(\'control + shift + l : trigger open quick add dropdown\');
                     openQuickAddDropDown();
                  }
            });


            var openQuickAddDropDown = function() {
                event.preventDefault();
                $("#topmenu-quickadd-dropdown").toggleClass("open");
                //$("#top-quickadd-search-input").focus();
            }
        });
        </script>
        ';
	return $html;
}

/**
 * Build the tooltip on top menu bookmark
 *
 * @return  string                  HTML content
 */
function top_menu_bookmark()
{
	global $langs, $conf, $db, $user;

	$html = '';

	// Define $bookmarks
	if (empty($conf->bookmark->enabled) || empty($user->rights->bookmark->lire)) return $html;

	if (!defined('JS_JQUERY_DISABLE_DROPDOWN') && !empty($conf->use_javascript_ajax))	    // This may be set by some pages that use different jquery version to avoid errors
	{
		include_once DOL_DOCUMENT_ROOT.'/bookmarks/bookmarks.lib.php';
		$langs->load("bookmarks");

		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$html .= '<div id="topmenu-bookmark-dropdown" class="dropdown inline-block">';
			$html .= printDropdownBookmarksList();
			$html .= '</div>';
		} else {
			$html .= '<!-- div for bookmark link -->
	        <div id="topmenu-bookmark-dropdown" class="dropdown inline-block">
	            <a class="dropdown-toggle login-dropdown-a" data-toggle="dropdown" href="#" title="'.$langs->trans('Bookmarks').' ('.$langs->trans('BookmarksMenuShortCut').')">
	                <i class="fa fa-star" ></i>
	            </a>
	            <div class="dropdown-menu">
	                '.printDropdownBookmarksList().'
	            </div>
	        </div>';

			$html .= '
	        <!-- Code to show/hide the bookmark drop-down -->
	        <script>
	        $( document ).ready(function() {
	            $(document).on("click", function(event) {
	                if (!$(event.target).closest("#topmenu-bookmark-dropdown").length) {
						//console.log("close bookmark dropdown - we click outside");
	                    // Hide the menus.
	                    $("#topmenu-bookmark-dropdown").removeClass("open");
	                }
	            });

	            $("#topmenu-bookmark-dropdown .dropdown-toggle").on("click", function(event) {
					console.log("toggle bookmark dropdown");
					openBookMarkDropDown();
	            });

	            // Key map shortcut
	            $(document).keydown(function(e){
	                  if( e.which === 77 && e.ctrlKey && e.shiftKey ){
	                     console.log(\'control + shift + m : trigger open bookmark dropdown\');
	                     openBookMarkDropDown();
	                  }
	            });


	            var openBookMarkDropDown = function() {
	                event.preventDefault();
	                $("#topmenu-bookmark-dropdown").toggleClass("open");
	                $("#top-bookmark-search-input").focus();
	            }

	        });
	        </script>
	        ';
		}
	}
	return $html;
}

/**
 * Build the tooltip on top menu tsearch
 *
 * @return  string                  HTML content
 */
function top_menu_search()
{
	global $langs, $conf, $db, $user, $hookmanager;

	$html = '';

	$usedbyinclude = 1;
	$arrayresult = null;
	include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php'; // This set $arrayresult

	$defaultAction = '';
	$buttonList = '<div class="dropdown-global-search-button-list" >';
	// Menu with all searchable items
	foreach ($arrayresult as $keyItem => $item)
	{
		if (empty($defaultAction)) {
			$defaultAction = $item['url'];
		}
		$buttonList .= '<button class="dropdown-item global-search-item" data-target="'.dol_escape_htmltag($item['url']).'" >';
		$buttonList .= $item['text'];
		$buttonList .= '</button>';
	}
	$buttonList .= '</div>';


	$searchInput = '<input name="sall" id="top-global-search-input" class="dropdown-search-input" placeholder="'.$langs->trans('Search').'" autocomplete="off" >';

	$dropDownHtml = '<form id="top-menu-action-search" name="actionsearch" method="GET" action="'.$defaultAction.'" >';

	$dropDownHtml .= '
        <!-- search input -->
        <div class="dropdown-header search-dropdown-header">
            ' . $searchInput.'
        </div>
    ';

	$dropDownHtml .= '
        <!-- Menu Body -->
        <div class="dropdown-body search-dropdown-body">
        '.$buttonList.'
        </div>
        ';

	$dropDownHtml .= '</form>';


	$html .= '<!-- div for Global Search -->
    <div id="topmenu-global-search-dropdown" class="atoplogin dropdown inline-block">
        <a class="dropdown-toggle login-dropdown-a" data-toggle="dropdown" href="#" title="'.$langs->trans('Search').' ('.$langs->trans('SearchMenuShortCut').')">
            <i class="fa fa-search" ></i>
        </a>
        <div class="dropdown-search">
            '.$dropDownHtml.'
        </div>
    </div>';

	$html .= '
    <!-- Code to show/hide the user drop-down -->
    <script>
    $( document ).ready(function() {

        // prevent submiting form on press ENTER
        $("#top-global-search-input").keydown(function (e) {
            if (e.keyCode == 13) {
                var inputs = $(this).parents("form").eq(0).find(":button");
                if (inputs[inputs.index(this) + 1] != null) {
                    inputs[inputs.index(this) + 1].focus();
                }
                e.preventDefault();
                return false;
            }
        });


        // submit form action
        $(".dropdown-global-search-button-list .global-search-item").on("click", function(event) {
            $("#top-menu-action-search").attr("action", $(this).data("target"));
            $("#top-menu-action-search").submit();
        });

        // close drop down
        $(document).on("click", function(event) {
			if (!$(event.target).closest("#topmenu-global-search-dropdown").length) {
				console.log("click close search - we click outside");
                // Hide the menus.
                $("#topmenu-global-search-dropdown").removeClass("open");
            }
        });

        // Open drop down
        $("#topmenu-global-search-dropdown .dropdown-toggle").on("click", function(event) {
			console.log("toggle search dropdown");
            openGlobalSearchDropDown();
        });

        // Key map shortcut
        $(document).keydown(function(e){
              if( e.which === 70 && e.ctrlKey && e.shiftKey ){
                 console.log(\'control + shift + f : trigger open global-search dropdown\');
                 openGlobalSearchDropDown();
              }
        });


        var openGlobalSearchDropDown = function() {
            event.preventDefault();
            $("#topmenu-global-search-dropdown").toggleClass("open");
            $("#top-global-search-input").focus();
        }

    });
    </script>
    ';

	return $html;
}

/**
 *  Show left menu bar
 *
 *  @param  array	$menu_array_before 	       	Table of menu entries to show before entries of menu handler. This param is deprectaed and must be provided to ''.
 *  @param  string	$helppagename    	       	Name of wiki page for help ('' by default).
 * 				     		                   	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 * 									         	For other external page: http://server/url
 *  @param  string	$notused             		Deprecated. Used in past to add content into left menu. Hooks can be used now.
 *  @param  array	$menu_array_after           Table of menu entries to show after entries of menu handler
 *  @param  int		$leftmenuwithoutmainarea    Must be set to 1. 0 by default for backward compatibility with old modules.
 *  @param  string	$title                      Title of web page
 *  @param  string  $acceptdelayedhtml          1 if caller request to have html delayed content not returned but saved into global $delayedhtmlcontent (so caller can show it at end of page to avoid flash FOUC effect)
 *  @return	void
 */
function left_menu($menu_array_before, $helppagename = '', $notused = '', $menu_array_after = '', $leftmenuwithoutmainarea = 0, $title = '', $acceptdelayedhtml = 0)
{
	global $user, $conf, $langs, $db, $form;
	global $hookmanager, $menumanager;

	$searchform = '';
	$bookmarks = '';

	if (!empty($menu_array_before)) dol_syslog("Deprecated parameter menu_array_before was used when calling main::left_menu function. Menu entries of module should now be defined into module descriptor and not provided when calling left_menu.", LOG_WARNING);

	if (empty($conf->dol_hide_leftmenu) && (!defined('NOREQUIREMENU') || !constant('NOREQUIREMENU')))
	{
		// Instantiate hooks for external modules
		$hookmanager->initHooks(array('searchform', 'leftblock'));

		print "\n".'<!-- Begin side-nav id-left -->'."\n".'<div class="side-nav"><div id="id-left">'."\n";

		if ($conf->browser->layout == 'phone') $conf->global->MAIN_USE_OLD_SEARCH_FORM = 1; // Select into select2 is awfull on smartphone. TODO Is this still true with select2 v4 ?

		print "\n";

		if (!is_object($form)) $form = new Form($db);
		$selected = -1;
		if (empty($conf->global->MAIN_USE_TOP_MENU_SEARCH_DROPDOWN)) {
			$usedbyinclude = 1;
			$arrayresult = null;
			include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php'; // This set $arrayresult

			if ($conf->use_javascript_ajax && empty($conf->global->MAIN_USE_OLD_SEARCH_FORM)) {
				$searchform .= $form->selectArrayFilter('searchselectcombo', $arrayresult, $selected, '', 1, 0, (empty($conf->global->MAIN_SEARCHBOX_CONTENT_LOADED_BEFORE_KEY) ? 1 : 0), 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);
			} else {
				if (is_array($arrayresult)) {
					foreach ($arrayresult as $key => $val) {
						$searchform .= printSearchForm($val['url'], $val['url'], $val['label'], 'maxwidth125', 'sall', $val['shortcut'], 'searchleft'.$key, $val['img']);
					}
				}
			}

			// Execute hook printSearchForm
			$parameters = array('searchform' => $searchform);
			$reshook = $hookmanager->executeHooks('printSearchForm', $parameters); // Note that $action and $object may have been modified by some hooks
			if (empty($reshook)) {
				$searchform .= $hookmanager->resPrint;
			} else $searchform = $hookmanager->resPrint;

			// Force special value for $searchform
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) || empty($conf->use_javascript_ajax)) {
				$urltosearch = DOL_URL_ROOT.'/core/search_page.php?showtitlebefore=1';
				$searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="'.$urltosearch.'" accesskey="s" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div></div>';
			} elseif ($conf->use_javascript_ajax && !empty($conf->global->MAIN_USE_OLD_SEARCH_FORM)) {
				$searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="#" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div><div id="divsearchforms2" style="display: none">'.$searchform.'</div>';
				$searchform .= '<script>
            	jQuery(document).ready(function () {
            		jQuery("#divsearchforms1").click(function(){
	                   jQuery("#divsearchforms2").toggle();
	               });
            	});
                </script>' . "\n";
				$searchform .= '</div>';
			}
		}

		// Left column
		print '<!-- Begin left menu -->'."\n";

		print '<div class="vmenu"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' title="Left menu"').'>'."\n\n";

		// Show left menu with other forms
		$menumanager->menu_array = $menu_array_before;
		$menumanager->menu_array_after = $menu_array_after;
		$menumanager->showmenu('left', array('searchform'=>$searchform, 'bookmarks'=>$bookmarks)); // output menu_array and menu found in database

		// Dolibarr version + help + bug report link
		print "\n";
		print "<!-- Begin Help Block-->\n";
		print '<div id="blockvmenuhelp" class="blockvmenuhelp">'."\n";

		// Version
		if (!empty($conf->global->MAIN_SHOW_VERSION))    // Version is already on help picto and on login page.
		{
			$doliurl = 'https://www.dolibarr.org';
			//local communities
			if (preg_match('/fr/i', $langs->defaultlang)) $doliurl = 'https://www.dolibarr.fr';
			if (preg_match('/es/i', $langs->defaultlang)) $doliurl = 'https://www.dolibarr.es';
			if (preg_match('/de/i', $langs->defaultlang)) $doliurl = 'https://www.dolibarr.de';
			if (preg_match('/it/i', $langs->defaultlang)) $doliurl = 'https://www.dolibarr.it';
			if (preg_match('/gr/i', $langs->defaultlang)) $doliurl = 'https://www.dolibarr.gr';

			$appli = constant('DOL_APPLICATION_TITLE');
			if (!empty($conf->global->MAIN_APPLICATION_TITLE))
			{
				$appli = $conf->global->MAIN_APPLICATION_TITLE; $doliurl = '';
				if (preg_match('/\d\.\d/', $appli))
				{
					if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
				} else $appli .= " ".DOL_VERSION;
			} else $appli .= " ".DOL_VERSION;
			print '<div id="blockvmenuhelpapp" class="blockvmenuhelp">';
			if ($doliurl) print '<a class="help" target="_blank" rel="noopener" href="'.$doliurl.'">';
			else print '<span class="help">';
			print $appli;
			if ($doliurl) print '</a>';
			else print '</span>';
			print '</div>'."\n";
		}

		// Link to bugtrack
		if (!empty($conf->global->MAIN_BUGTRACK_ENABLELINK))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

			$bugbaseurl = 'https://github.com/Dolibarr/dolibarr/issues/new?labels=Bug';
			$bugbaseurl .= '&title=';
			$bugbaseurl .= urlencode("Bug: ");
			$bugbaseurl .= '&body=';
			$bugbaseurl .= urlencode("# Instructions\n");
			$bugbaseurl .= urlencode("*This is a template to help you report good issues. You may use [Github Markdown](https://help.github.com/articles/getting-started-with-writing-and-formatting-on-github/) syntax to format your issue report.*\n");
			$bugbaseurl .= urlencode("*Please:*\n");
			$bugbaseurl .= urlencode("- *replace the bracket enclosed texts with meaningful information*\n");
			$bugbaseurl .= urlencode("- *remove any unused sub-section*\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("# Bug\n");
			$bugbaseurl .= urlencode("[*Short description*]\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("## Environment\n");
			$bugbaseurl .= urlencode("- **Version**: ".DOL_VERSION."\n");
			$bugbaseurl .= urlencode("- **OS**: ".php_uname('s')."\n");
			$bugbaseurl .= urlencode("- **Web server**: ".$_SERVER["SERVER_SOFTWARE"]."\n");
			$bugbaseurl .= urlencode("- **PHP**: ".php_sapi_name().' '.phpversion()."\n");
			$bugbaseurl .= urlencode("- **Database**: ".$db::LABEL.' '.$db->getVersion()."\n");
			$bugbaseurl .= urlencode("- **URL(s)**: ".$_SERVER["REQUEST_URI"]."\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("## Expected and actual behavior\n");
			$bugbaseurl .= urlencode("[*Verbose description*]\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("## Steps to reproduce the behavior\n");
			$bugbaseurl .= urlencode("[*Verbose description*]\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("## [Attached files](https://help.github.com/articles/issue-attachments) (Screenshots, screencasts, dolibarr.log, debugging informations…)\n");
			$bugbaseurl .= urlencode("[*Files*]\n");
			$bugbaseurl .= urlencode("\n");


			// Execute hook printBugtrackInfo
			$parameters = array('bugbaseurl'=>$bugbaseurl);
			$reshook = $hookmanager->executeHooks('printBugtrackInfo', $parameters); // Note that $action and $object may have been modified by some hooks
			if (empty($reshook))
			{
				$bugbaseurl .= $hookmanager->resPrint;
			} else $bugbaseurl = $hookmanager->resPrint;

			$bugbaseurl .= urlencode("\n");
			$bugbaseurl .= urlencode("## Report\n");
			print '<div id="blockvmenuhelpbugreport" class="blockvmenuhelp">';
			print '<a class="help" target="_blank" rel="noopener" href="'.$bugbaseurl.'">'.$langs->trans("FindBug").'</a>';
			print '</div>';
		}

		print "</div>\n";
		print "<!-- End Help Block-->\n";
		print "\n";

		print "</div>\n";
		print "<!-- End left menu -->\n";
		print "\n";

		// Execute hook printLeftBlock
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printLeftBlock', $parameters); // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint;

		print '</div></div> <!-- End side-nav id-left -->'; // End div id="side-nav" div id="id-left"
	}

	print "\n";
	print '<!-- Begin right area -->'."\n";

	if (empty($leftmenuwithoutmainarea)) main_area($title);
}


/**
 *  Begin main area
 *
 *  @param	string	$title		Title
 *  @return	void
 */
function main_area($title = '')
{
	global $conf, $langs;

	if (empty($conf->dol_hide_leftmenu)) print '<div id="id-right">';

	print "\n";

	print '<!-- Begin div class="fiche" -->'."\n".'<div class="fiche">'."\n";

	if (!empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED)) print info_admin($langs->trans("WarningYouAreInMaintenanceMode", $conf->global->MAIN_ONLY_LOGIN_ALLOWED), 0, 0, 1, 'warning maintenancemode');

	// Permit to add user company information on each printed document by setting SHOW_SOCINFO_ON_PRINT
	if (!empty($conf->global->SHOW_SOCINFO_ON_PRINT) && GETPOST('optioncss', 'aZ09') == 'print' && empty(GETPOST('disable_show_socinfo_on_print', 'az09')))
	{
		global $hookmanager;
		$hookmanager->initHooks(array('main'));
		$parameters = array();
		$reshook = $hookmanager->executeHooks('showSocinfoOnPrint', $parameters);
		if (empty($reshook))
		{
			print '<!-- Begin show mysoc info header -->'."\n";
			print '<div id="mysoc-info-header">'."\n";
			print '<table class="centpercent div-table-responsive">'."\n";
			print '<tbody>';
			print '<tr><td rowspan="0" class="width20p">';
			if ($conf->global->MAIN_SHOW_LOGO && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && !empty($conf->global->MAIN_INFO_SOCIETE_LOGO)) {
				print '<img id="mysoc-info-header-logo" style="max-width:100%" alt="" src="'.DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_LOGO)).'">';
			}
			print '</td><td  rowspan="0" class="width50p"></td></tr>'."\n";
			print '<tr><td class="titre bold">'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM).'</td></tr>'."\n";
			print '<tr><td>'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_ADDRESS).'<br>'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_ZIP).' '.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TOWN).'</td></tr>'."\n";
			if (!empty($conf->global->MAIN_INFO_SOCIETE_TEL)) print '<tr><td style="padding-left: 1em" class="small">'.$langs->trans("Phone").' : '.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TEL).'</td></tr>';
			if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) print '<tr><td style="padding-left: 1em" class="small">'.$langs->trans("Email").' : '.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'</td></tr>';
			if (!empty($conf->global->MAIN_INFO_SOCIETE_WEB)) print '<tr><td style="padding-left: 1em" class="small">'.$langs->trans("Web").' : '.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'</td></tr>';
			print '</tbody>';
			print '</table>'."\n";
			print '</div>'."\n";
			print '<!-- End show mysoc info header -->'."\n";
		}
	}
}


/**
 *  Return helpbaseurl, helppage and mode
 *
 *  @param	string		$helppagename		Page name ('EN:xxx,ES:eee,FR:fff...' or 'http://localpage')
 *  @param  Translate	$langs				Language
 *  @return	array		Array of help urls
 */
function getHelpParamFor($helppagename, $langs)
{
	$helpbaseurl = '';
	$helppage = '';
	$mode = '';

	if (preg_match('/^http/i', $helppagename))
	{
		// If complete URL
		$helpbaseurl = '%s';
		$helppage = $helppagename;
		$mode = 'local';
	} else {
		// If WIKI URL
		$reg = array();
		if (preg_match('/^es/i', $langs->defaultlang))
		{
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/ES:([^|]+)/i', $helppagename, $reg)) $helppage = $reg[1];
		}
		if (preg_match('/^fr/i', $langs->defaultlang))
		{
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/FR:([^|]+)/i', $helppagename, $reg)) $helppage = $reg[1];
		}
		if (empty($helppage))	// If help page not already found
		{
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/EN:([^|]+)/i', $helppagename, $reg)) $helppage = $reg[1];
		}
		$mode = 'wiki';
	}
	return array('helpbaseurl'=>$helpbaseurl, 'helppage'=>$helppage, 'mode'=>$mode);
}


/**
 *  Show a search area.
 *  Used when the javascript quick search is not used.
 *
 *  @param  string	$urlaction          Url post
 *  @param  string	$urlobject          Url of the link under the search box
 *  @param  string	$title              Title search area
 *  @param  string	$htmlmorecss        Add more css
 *  @param  string	$htmlinputname      Field Name input form
 *  @param	string	$accesskey			Accesskey
 *  @param  string  $prefhtmlinputname  Complement for id to avoid multiple same id in the page
 *  @param	string	$img				Image to use
 *  @param	string	$showtitlebefore	Show title before input text instead of into placeholder. This can be set when output is dedicated for text browsers.
 *  @param	string	$autofocus			Set autofocus on field
 *  @return	string
 */
function printSearchForm($urlaction, $urlobject, $title, $htmlmorecss, $htmlinputname, $accesskey = '', $prefhtmlinputname = '', $img = '', $showtitlebefore = 0, $autofocus = 0)
{
	global $conf, $langs, $user;

	$ret = '';
	$ret .= '<form action="'.$urlaction.'" method="post" class="searchform nowraponall tagtr">';
	$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
	$ret .= '<input type="hidden" name="mode" value="search">';
	$ret .= '<input type="hidden" name="savelogin" value="'.dol_escape_htmltag($user->login).'">';
	if ($showtitlebefore) $ret .= '<div class="tagtd left">'.$title.'</div> ';
	$ret .= '<div class="tagtd">';
	$ret .= img_picto('', $img, '', false, 0, 0, '', 'paddingright width20');
	$ret .= '<input type="text" class="flat '.$htmlmorecss.'"';
	$ret .= ' style="background-repeat: no-repeat; background-position: 3px;"';
	$ret .= ($accesskey ? ' accesskey="'.$accesskey.'"' : '');
	$ret .= ' placeholder="'.strip_tags($title).'"';
	$ret .= ($autofocus ? ' autofocus' : '');
	$ret .= ' name="'.$htmlinputname.'" id="'.$prefhtmlinputname.$htmlinputname.'" />';
	$ret .= '<button type="submit" class="button bordertransp" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px">';
	$ret .= '<span class="fa fa-search"></span>';
	$ret .= '</button>';
	$ret .= '</div>';
	$ret .= "</form>\n";
	return $ret;
}


if (!function_exists("llxFooter"))
{
	/**
	 * Show HTML footer
	 * Close div /DIV class=fiche + /DIV id-right + /DIV id-container + /BODY + /HTML.
	 * If global var $delayedhtmlcontent was filled, we output it just before closing the body.
	 *
	 * @param	string	$comment    				A text to add as HTML comment into HTML generated page
	 * @param	string	$zone						'private' (for private pages) or 'public' (for public pages)
	 * @param	int		$disabledoutputofmessages	Clear all messages stored into session without diplaying them
	 * @return	void
	 */
	function llxFooter($comment = '', $zone = 'private', $disabledoutputofmessages = 0)
	{
		global $conf, $db, $langs, $user, $mysoc, $object;
		global $delayedhtmlcontent;
		global $contextpage, $page, $limit;
		global $dolibarr_distrib;

		$ext = 'layout='.$conf->browser->layout.'&version='.urlencode(DOL_VERSION);

		// Global html output events ($mesgs, $errors, $warnings)
		dol_htmloutput_events($disabledoutputofmessages);

		// Code for search criteria persistence.
		// $user->lastsearch_values was set by the GETPOST when form field search_xxx exists
		if (is_object($user) && !empty($user->lastsearch_values_tmp) && is_array($user->lastsearch_values_tmp))
		{
			// Clean and save data
			foreach ($user->lastsearch_values_tmp as $key => $val)
			{
				unset($_SESSION['lastsearch_values_tmp_'.$key]); // Clean array to rebuild it just after
				if (count($val) && empty($_POST['button_removefilter']))	// If there is search criteria to save and we did not click on 'Clear filter' button
				{
					if (empty($val['sortfield'])) unset($val['sortfield']);
					if (empty($val['sortorder'])) unset($val['sortorder']);
					dol_syslog('Save lastsearch_values_tmp_'.$key.'='.json_encode($val, 0)." (systematic recording of last search criterias)");
					$_SESSION['lastsearch_values_tmp_'.$key] = json_encode($val);
					unset($_SESSION['lastsearch_values_'.$key]);
				}
			}
		}


		$relativepathstring = $_SERVER["PHP_SELF"];
		// Clean $relativepathstring
		if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
		$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
		$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
		if (preg_match('/list\.php$/', $relativepathstring))
		{
			unset($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_page_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]);

			if (!empty($contextpage))                     $_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring] = $contextpage;
			if (!empty($page) && $page > 0)               $_SESSION['lastsearch_page_tmp_'.$relativepathstring] = $page;
			if (!empty($limit) && $limit != $conf->liste_limit) $_SESSION['lastsearch_limit_tmp_'.$relativepathstring] = $limit;

			unset($_SESSION['lastsearch_contextpage_'.$relativepathstring]);
			unset($_SESSION['lastsearch_page_'.$relativepathstring]);
			unset($_SESSION['lastsearch_limit_'.$relativepathstring]);
		}

		// Core error message
		if (!empty($conf->global->MAIN_CORE_ERROR))
		{
			// Ajax version
			if ($conf->use_javascript_ajax)
			{
				$title = img_warning().' '.$langs->trans('CoreErrorTitle');
				print ajax_dialog($title, $langs->trans('CoreErrorMessage'));
			} else {
				// html version
				$msg = img_warning().' '.$langs->trans('CoreErrorMessage');
				print '<div class="error">'.$msg.'</div>';
			}

			//define("MAIN_CORE_ERROR",0);      // Constant was defined and we can't change value of a constant
		}

		print "\n\n";

		print '</div> <!-- End div class="fiche" -->'."\n"; // End div fiche

		if (empty($conf->dol_hide_leftmenu)) print '</div> <!-- End div id-right -->'."\n"; // End div id-right

		if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) print '</div> <!-- End div id-container -->'."\n"; // End div container

		print "\n";
		if ($comment) print '<!-- '.$comment.' -->'."\n";

		printCommonFooter($zone);

		if (!empty($delayedhtmlcontent)) print $delayedhtmlcontent;

		if (!empty($conf->use_javascript_ajax))
		{
			print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
			print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext ? '&'.$ext : '').'"></script>'."\n";
		}

		// Wrapper to add log when clicking on download or preview
		if (!empty($conf->blockedlog->enabled) && is_object($object) && $object->id > 0 && $object->statut > 0)
		{
			if (in_array($object->element, array('facture')))       // Restrict for the moment to element 'facture'
			{
				print "\n<!-- JS CODE TO ENABLE log when making a download or a preview of a document -->\n";
				?>
    			<script>
    			jQuery(document).ready(function () {
    				$('a.documentpreview').click(function() {
    					$.post('<?php echo DOL_URL_ROOT."/blockedlog/ajax/block-add.php" ?>'
    							, {
    								id:<?php echo $object->id; ?>
    								, element:'<?php echo $object->element ?>'
    								, action:'DOC_PREVIEW'
    							}
    					);
    				});
    				$('a.documentdownload').click(function() {
    					$.post('<?php echo DOL_URL_ROOT."/blockedlog/ajax/block-add.php" ?>'
    							, {
    								id:<?php echo $object->id; ?>
    								, element:'<?php echo $object->element ?>'
    								, action:'DOC_DOWNLOAD'
    							}
    					);
    				});
    			});
    			</script>
				<?php
			}
	   	}

		// A div for the address popup
		print "\n<!-- A div to allow dialog popup -->\n";
		print '<div id="dialogforpopup" style="display: none;"></div>'."\n";

		// Add code for the asynchronous anonymous first ping (for telemetry)
		// You can use &forceping=1 in parameters to force the ping if the ping was already sent.
		$forceping = GETPOST('forceping', 'alpha');
		if (($_SERVER["PHP_SELF"] == DOL_URL_ROOT.'/index.php') || $forceping)
		{
			//print '<!-- instance_unique_id='.$conf->file->instance_unique_id.' MAIN_FIRST_PING_OK_ID='.$conf->global->MAIN_FIRST_PING_OK_ID.' -->';
			$hash_unique_id = md5('dolibarr'.$conf->file->instance_unique_id);
			if (empty($conf->global->MAIN_FIRST_PING_OK_DATE)
				|| (!empty($conf->file->instance_unique_id) && ($hash_unique_id != $conf->global->MAIN_FIRST_PING_OK_ID) && ($conf->global->MAIN_FIRST_PING_OK_ID != 'disabled'))
			|| $forceping)
			{
				// No ping done if we are into an alpha version
				if (strpos('alpha', DOL_VERSION) > 0 && !$forceping) {
					print "\n<!-- NO JS CODE TO ENABLE the anonymous Ping. It is an alpha version -->\n";
				} elseif (empty($_COOKIE['DOLINSTALLNOPING_'.$hash_unique_id]) || $forceping)	// Cookie is set when we uncheck the checkbox in the installation wizard.
				{
					// MAIN_LAST_PING_KO_DATE
					// Disable ping if MAIN_LAST_PING_KO_DATE is set and is recent
					if (!empty($conf->global->MAIN_LAST_PING_KO_DATE) && substr($conf->global->MAIN_LAST_PING_KO_DATE, 0, 6) == dol_print_date(dol_now(), '%Y%m') && !$forceping) {
						print "\n<!-- NO JS CODE TO ENABLE the anonymous Ping. An error already occured this month, we will try later. -->\n";
					} else {
						include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

						print "\n".'<!-- Includes JS for Ping of Dolibarr forceping='.$forceping.' MAIN_FIRST_PING_OK_DATE='.$conf->global->MAIN_FIRST_PING_OK_DATE.' MAIN_FIRST_PING_OK_ID='.$conf->global->MAIN_FIRST_PING_OK_ID.' MAIN_LAST_PING_KO_DATE='.$conf->global->MAIN_LAST_PING_KO_DATE.' -->'."\n";
						print "\n<!-- JS CODE TO ENABLE the anonymous Ping -->\n";
						$url_for_ping = (empty($conf->global->MAIN_URL_FOR_PING) ? "https://ping.dolibarr.org/" : $conf->global->MAIN_URL_FOR_PING);
						// Try to guess the distrib used
						$distrib = 'standard';
						if ($_SERVER["SERVER_ADMIN"] == 'doliwamp@localhost') $distrib = 'doliwamp';
						if (!empty($dolibarr_distrib)) $distrib = $dolibarr_distrib;
						?>
			    			<script>
			    			jQuery(document).ready(function (tmp) {
			    				$.ajax({
			    					  method: "POST",
			    					  url: "<?php echo $url_for_ping ?>",
			    					  timeout: 500,     // timeout milliseconds
			    					  cache: false,
			    					  data: {
				    					  hash_algo: 'md5',
				    					  hash_unique_id: '<?php echo dol_escape_js($hash_unique_id); ?>',
				    					  action: 'dolibarrping',
				    					  version: '<?php echo (float) DOL_VERSION; ?>',
				    					  entity: '<?php echo (int) $conf->entity; ?>',
				    					  dbtype: '<?php echo dol_escape_js($db->type); ?>',
				    					  country_code: '<?php echo $mysoc->country_code ? dol_escape_js($mysoc->country_code) : 'unknown'; ?>',
				    					  php_version: '<?php echo dol_escape_js(phpversion()); ?>',
				    					  os_version: '<?php echo dol_escape_js(version_os('smr')); ?>',
				    					  distrib: '<?php echo $distrib ? dol_escape_js($distrib) : 'unknown'; ?>'
				    				  },
			    					  success: function (data, status, xhr) {   // success callback function (data contains body of response)
			      					    	console.log("Ping ok");
			        	    				$.ajax({
			      	    					  method: 'GET',
			      	    					  url: '<?php echo DOL_URL_ROOT.'/core/ajax/pingresult.php'; ?>',
			      	    					  timeout: 500,     // timeout milliseconds
			      	    					  cache: false,
			      	        				  data: { hash_algo: 'md5', hash_unique_id: '<?php echo dol_escape_js($hash_unique_id); ?>', action: 'firstpingok' },	// for update
			    					  		});
			    					  },
			    					  error: function (data,status,xhr) {   // error callback function
			        					    console.log("Ping ko: " + data);
			        	    				$.ajax({
			        	    					  method: 'GET',
			        	    					  url: '<?php echo DOL_URL_ROOT.'/core/ajax/pingresult.php'; ?>',
			        	    					  timeout: 500,     // timeout milliseconds
			        	    					  cache: false,
			        	        				  data: { hash_algo: 'md5', hash_unique_id: '<?php echo dol_escape_js($hash_unique_id); ?>', action: 'firstpingko' },
			      					  		});
			    					  }
			    				});
			    			});
			    			</script>
						<?php
					}
				} else {
					$now = dol_now();
					print "\n<!-- NO JS CODE TO ENABLE the anonymous Ping. It was disabled -->\n";
					include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
					dolibarr_set_const($db, 'MAIN_FIRST_PING_OK_DATE', dol_print_date($now, 'dayhourlog', 'gmt'));
					dolibarr_set_const($db, 'MAIN_FIRST_PING_OK_ID', 'disabled');
				}
			}
		}

		print "</body>\n";
		print "</html>\n";
	}
}
