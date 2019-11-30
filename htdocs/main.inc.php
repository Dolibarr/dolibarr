<?php
/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Xavier Dutoit           <doli@sydesy.com>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2015  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2008       Matteli
 * Copyright (C) 2011-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/main.inc.php
 *	\ingroup	core
 *	\brief      File that defines environment for Dolibarr GUI pages only (file not required by scripts)
 */

//@ini_set('memory_limit', '128M');	// This may be useless if memory is hard limited by your PHP

// For optional tuning. Enabled if environment variable MAIN_SHOW_TUNING_INFO is defined.
$micro_start_time=0;
if (! empty($_SERVER['MAIN_SHOW_TUNING_INFO']))
{
	list($usec, $sec) = explode(" ", microtime());
	$micro_start_time=((float) $usec + (float) $sec);
	// Add Xdebug code coverage
	//define('XDEBUGCOVERAGE',1);
	if (defined('XDEBUGCOVERAGE')) {
		xdebug_start_code_coverage();
	}
}

// Removed magic_quotes
if (function_exists('get_magic_quotes_gpc'))	// magic_quotes_* deprecated in PHP 5.0 and removed in PHP 5.5
{
	if (get_magic_quotes_gpc())
	{
		// Forcing parameter setting magic_quotes_gpc and cleaning parameters
		// (Otherwise he would have for each position, condition
		// Reading stripslashes variable according to state get_magic_quotes_gpc).
		// Off mode recommended (just do $db->escape for insert / update).
		function stripslashes_deep($value)
		{
			return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
		}
		$_GET     = array_map('stripslashes_deep', $_GET);
		$_POST    = array_map('stripslashes_deep', $_POST);
		$_FILES   = array_map('stripslashes_deep', $_FILES);
		//$_COOKIE  = array_map('stripslashes_deep', $_COOKIE); // Useless because a cookie should never be outputed on screen nor used into sql
		@set_magic_quotes_runtime(0);
	}
}

/**
 * Security: SQL Injection and XSS Injection (scripts) protection (Filters on GET, POST, PHP_SELF).
 *
 * @param		string		$val		Value
 * @param		string		$type		1=GET, 0=POST, 2=PHP_SELF, 3=GET without sql reserved keywords (the less tolerant test)
 * @return		int						>0 if there is an injection, 0 if none
 */
function test_sql_and_script_inject($val, $type)
{
	$inj = 0;
	// For SQL Injection (only GET are used to be included into bad escaped SQL requests)
	if ($type == 1 || $type == 3)
	{
		$inj += preg_match('/delete\s+from/i',	 $val);
		$inj += preg_match('/create\s+table/i',	 $val);
		$inj += preg_match('/insert\s+into/i', 	 $val);
		$inj += preg_match('/select\s+from/i', 	 $val);
		$inj += preg_match('/into\s+(outfile|dumpfile)/i',  $val);
		$inj += preg_match('/user\s*\(/i',  $val);						// avoid to use function user() that return current database login
		$inj += preg_match('/information_schema/i',  $val);				// avoid to use request that read information_schema database
	}
	if ($type == 3)
	{
		$inj += preg_match('/select|update|delete|replace|group\s+by|concat|count|from/i',	 $val);
	}
	if ($type != 2)	// Not common key strings, so we can check them both on GET and POST
	{
		$inj += preg_match('/updatexml\(/i', 	 $val);
		$inj += preg_match('/update.+set.+=/i',  $val);
		$inj += preg_match('/union.+select/i', 	 $val);
		$inj += preg_match('/(\.\.%2f)+/i',		 $val);
	}
	// For XSS Injection done by adding javascript with script
	// This is all cases a browser consider text is javascript:
	// When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
	// All examples on page: http://ha.ckers.org/xss.html#XSScalc
	// More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
	$inj += preg_match('/<script/i', $val);
	$inj += preg_match('/<iframe/i', $val);
	$inj += preg_match('/<audio/i', $val);
	$inj += preg_match('/Set\.constructor/i', $val);	// ECMA script 6
	if (! defined('NOSTYLECHECK')) $inj += preg_match('/<style/i', $val);
	$inj += preg_match('/base[\s]+href/si', $val);
	$inj += preg_match('/<.*onmouse/si', $val);       // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
	$inj += preg_match('/onerror\s*=/i', $val);       // onerror can be set on img or any html tag like <img title='...' onerror = alert(1)>
	$inj += preg_match('/onfocus\s*=/i', $val);       // onfocus can be set on input text html tag like <input type='text' value='...' onfocus = alert(1)>
	$inj += preg_match('/onload\s*=/i', $val);        // onload can be set on svg tag <svg/onload=alert(1)> or other tag like body <body onload=alert(1)>
	$inj += preg_match('/onloadstart\s*=/i', $val);   // onload can be set on audio tag <audio onloadstart=alert(1)>
	$inj += preg_match('/onclick\s*=/i', $val);       // onclick can be set on img text html tag like <img onclick = alert(1)>
	$inj += preg_match('/onscroll\s*=/i', $val);      // onscroll can be on textarea
	//$inj += preg_match('/on[A-Z][a-z]+\*=/', $val);   // To lock event handlers onAbort(), ...
	$inj += preg_match('/&#58;|&#0000058|&#x3A/i', $val);		// refused string ':' encoded (no reason to have it encoded) to lock 'javascript:...'
	//if ($type == 1)
	//{
		$inj += preg_match('/javascript:/i', $val);
		$inj += preg_match('/vbscript:/i', $val);
	//}
	// For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
	if ($type == 1) $inj += preg_match('/"/i', $val);		// We refused " in GET parameters value
	if ($type == 2) $inj += preg_match('/[;"]/', $val);		// PHP_SELF is a file system path. It can contains spaces.
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
			}
			else
			{
				print 'Access refused by SQL/Script injection protection in main.inc.php (type='.htmlentities($type).' key='.htmlentities($key).' value='.htmlentities($value).' page='.htmlentities($_SERVER["REQUEST_URI"]).')';
				exit;
			}
		}
		return true;
	}
	else
	{
		return (test_sql_and_script_inject($var, $type) <= 0);
	}
}


// Check consistency of NOREQUIREXXX DEFINES
if ((defined('NOREQUIREDB') || defined('NOREQUIRETRAN')) && ! defined('NOREQUIREMENU'))
{
	print 'If define NOREQUIREDB or NOREQUIRETRAN are set, you must also set NOREQUIREMENU or not set them';
	exit;
}

// Sanity check on URL
if (! empty($_SERVER["PHP_SELF"]))
{
	$morevaltochecklikepost=array($_SERVER["PHP_SELF"]);
	analyseVarsForSqlAndScriptsInjection($morevaltochecklikepost,2);
}
// Sanity check on GET parameters
if (! defined('NOSCANGETFORINJECTION') && ! empty($_SERVER["QUERY_STRING"]))
{
	$morevaltochecklikeget=array($_SERVER["QUERY_STRING"]);
	analyseVarsForSqlAndScriptsInjection($morevaltochecklikeget,1);
}
// Sanity check on POST
if (! defined('NOSCANPOSTFORINJECTION'))
{
	analyseVarsForSqlAndScriptsInjection($_POST,0);
}

// This is to make Dolibarr working with Plesk
if (! empty($_SERVER['DOCUMENT_ROOT']) && substr($_SERVER['DOCUMENT_ROOT'], -6) !== 'htdocs')
{
	set_include_path($_SERVER['DOCUMENT_ROOT'] . '/htdocs');
}

// Include the conf.php and functions.lib.php
require_once 'filefunc.inc.php';

// If there is a POST parameter to tell to save automatically some POST parameters into cookies, we do it.
// This is used for example by form of boxes to save personalization of some options.
// DOL_AUTOSET_COOKIE=cookiename:val1,val2 and  cookiename_val1=aaa cookiename_val2=bbb will set cookie_name with value json_encode(array('val1'=> , ))
if (! empty($_POST["DOL_AUTOSET_COOKIE"]))
{
	$tmpautoset=explode(':',$_POST["DOL_AUTOSET_COOKIE"],2);
	$tmplist=explode(',',$tmpautoset[1]);
	$cookiearrayvalue=array();
	foreach ($tmplist as $tmpkey)
	{
		$postkey=$tmpautoset[0].'_'.$tmpkey;
		//var_dump('tmpkey='.$tmpkey.' postkey='.$postkey.' value='.$_POST[$postkey]);
		if (! empty($_POST[$postkey])) $cookiearrayvalue[$tmpkey]=$_POST[$postkey];
	}
	$cookiename=$tmpautoset[0];
	$cookievalue=json_encode($cookiearrayvalue);
	//var_dump('setcookie cookiename='.$cookiename.' cookievalue='.$cookievalue);
	setcookie($cookiename, empty($cookievalue)?'':$cookievalue, empty($cookievalue)?0:(time()+(86400*354)), '/', null, false, true);	// keep cookie 1 year and add tag httponly
	if (empty($cookievalue)) unset($_COOKIE[$cookiename]);
}


// Init session. Name of session is specific to Dolibarr instance.
// Note: the function dol_getprefix may have been redefined to return a different key to manage another area to protect.
$prefix=dol_getprefix('');

$sessionname='DOLSESSID_'.$prefix;
$sessiontimeout='DOLSESSTIMEOUT_'.$prefix;
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_set_cookie_params(0, '/', null, false, true);   // Add tag httponly on session cookie (same as setting session.cookie_httponly into php.ini). Must be called before the session_start.
// This create lock, released when session_write_close() or end of page.
// We need this lock as long as we read/write $_SESSION ['vars']. We can remove lock when finished.
if (! defined('NOSESSION'))
{
	session_start();
	/*if (ini_get('register_globals'))    // Deprecated in 5.3 and removed in 5.4. To solve bug in using $_SESSION
	{
		foreach ($_SESSION as $key=>$value)
		{
			if (isset($GLOBALS[$key])) unset($GLOBALS[$key]);
		}
	}*/
}

// Init the 5 global objects, this include will make the new and set properties for: $conf, $db, $langs, $user, $mysoc
require_once 'master.inc.php';

// Activate end of page function
register_shutdown_function('dol_shutdown');

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"]))
{
	$tmp=getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
	$conf->browser->name=$tmp['browsername'];
	$conf->browser->os=$tmp['browseros'];
	$conf->browser->version=$tmp['browserversion'];
	$conf->browser->layout=$tmp['layout'];     // 'classic', 'phone', 'tablet'
	$conf->browser->phone=$tmp['phone'];	   // TODO deprecated, use ->layout
	$conf->browser->tablet=$tmp['tablet'];	   // TODO deprecated, use ->layout
	//var_dump($conf->browser);

	if ($conf->browser->layout == 'phone') $conf->dol_no_mouse_hover=1;
	if ($conf->browser->layout == 'phone') $conf->global->MAIN_TESTMENUHIDER=1;
}

// Force HTTPS if required ($conf->file->main_force_https is 0/1 or https dolibarr root url)
// $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
if (! empty($conf->file->main_force_https) && (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on'))
{
	$newurl='';
	if (is_numeric($conf->file->main_force_https))
	{
		if ($conf->file->main_force_https == '1' && ! empty($_SERVER["SCRIPT_URI"]))	// If SCRIPT_URI supported by server
		{
			if (preg_match('/^http:/i',$_SERVER["SCRIPT_URI"]) && ! preg_match('/^https:/i',$_SERVER["SCRIPT_URI"]))	// If link is http
			{
				$newurl=preg_replace('/^http:/i','https:',$_SERVER["SCRIPT_URI"]);
			}
		}
		else	// Check HTTPS environment variable (Apache/mod_ssl only)
		{
			$newurl=preg_replace('/^http:/i','https:',DOL_MAIN_URL_ROOT).$_SERVER["REQUEST_URI"];
		}
	}
	else
	{
		// Check HTTPS environment variable (Apache/mod_ssl only)
		$newurl=$conf->file->main_force_https.$_SERVER["REQUEST_URI"];
	}
	// Start redirect
	if ($newurl)
	{
		dol_syslog("main.inc: dolibarr_main_force_https is on, we make a redirect to ".$newurl);
		header("Location: ".$newurl);
		exit;
	}
	else
	{
		dol_syslog("main.inc: dolibarr_main_force_https is on but we failed to forge new https url so no redirect is done", LOG_WARNING);
	}
}

if (! defined('NOLOGIN') && ! defined('NOIPCHECK') && ! empty($dolibarr_main_restrict_ip))
{
	$listofip=explode(',', $dolibarr_main_restrict_ip);
	$found = false;
	foreach($listofip as $ip)
	{
		$ip=trim($ip);
		if ($ip == $_SERVER['REMOTE_ADDR'])
		{
			$found = true;
			break;
		}
	}
	if (! $found)
	{
		print 'Access refused by IP protection';
		exit;
	}
}

// Loading of additional presentation includes
if (! defined('NOREQUIREHTML')) require_once DOL_DOCUMENT_ROOT .'/core/class/html.form.class.php';	    // Need 660ko memory (800ko in 2.2)
if (! defined('NOREQUIREAJAX') && $conf->use_javascript_ajax) require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';	// Need 22ko memory

// If install or upgrade process not done or not completely finished, we call the install page.
if (! empty($conf->global->MAIN_NOT_INSTALLED) || ! empty($conf->global->MAIN_NOT_UPGRADED))
{
	dol_syslog("main.inc: A previous install or upgrade was not complete. Redirect to install page.", LOG_WARNING);
	header("Location: ".DOL_URL_ROOT."/install/index.php");
	exit;
}
// If an upgrade process is required, we call the install page.
if ((! empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && ($conf->global->MAIN_VERSION_LAST_UPGRADE != DOL_VERSION))
|| (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && ! empty($conf->global->MAIN_VERSION_LAST_INSTALL) && ($conf->global->MAIN_VERSION_LAST_INSTALL != DOL_VERSION)))
{
	$versiontocompare=empty($conf->global->MAIN_VERSION_LAST_UPGRADE)?$conf->global->MAIN_VERSION_LAST_INSTALL:$conf->global->MAIN_VERSION_LAST_UPGRADE;
	require_once DOL_DOCUMENT_ROOT .'/core/lib/admin.lib.php';
	$dolibarrversionlastupgrade=preg_split('/[.-]/',$versiontocompare);
	$dolibarrversionprogram=preg_split('/[.-]/',DOL_VERSION);
	$rescomp=versioncompare($dolibarrversionprogram,$dolibarrversionlastupgrade);
	if ($rescomp > 0)   // Programs have a version higher than database. We did not add "&& $rescomp < 3" because we want upgrade process for build upgrades
	{
		dol_syslog("main.inc: database version ".$versiontocompare." is lower than programs version ".DOL_VERSION.". Redirect to install page.", LOG_WARNING);
		header("Location: ".DOL_URL_ROOT."/install/index.php");
		exit;
	}
}

// Creation of a token against CSRF vulnerabilities
if (! defined('NOTOKENRENEWAL'))
{
	// Rolling token at each call ($_SESSION['token'] contains token of previous page)
	if (isset($_SESSION['newtoken'])) $_SESSION['token'] = $_SESSION['newtoken'];

	// Save in $_SESSION['newtoken'] what will be next token. Into forms, we will add param token = $_SESSION['newtoken']
	$token = dol_hash(uniqid(mt_rand(), true)); // Generates a hash of a random number
	$_SESSION['newtoken'] = $token;
}
if ((! defined('NOCSRFCHECK') && empty($dolibarr_nocsrfcheck) && ! empty($conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN))
	|| defined('CSRFCHECK_WITH_TOKEN'))	// Check validity of token, only if option MAIN_SECURITY_CSRF_WITH_TOKEN enabled or if constant CSRFCHECK_WITH_TOKEN is set
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! GETPOST('token','alpha')) // Note, offender can still send request by GET
	{
		print "Access refused by CSRF protection in main.inc.php. Token not provided.\n";
		print "If you access your server behind a proxy using url rewriting, you might check that all HTTP header is propagated (or add the line \$dolibarr_nocsrfcheck=1 into your conf.php file).\n";
		die;
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST')  // This test must be after loading $_SESSION['token'].
	{
		if (GETPOST('token', 'alpha') != $_SESSION['token'])
		{
			dol_syslog("Invalid token in ".$_SERVER['HTTP_REFERER'].", action=".GETPOST('action','aZ09').", _POST['token']=".GETPOST('token','alpha').", _SESSION['token']=".$_SESSION['token'], LOG_WARNING);
			//print 'Unset POST by CSRF protection in main.inc.php.';	// Do not output anything because this create problems when using the BACK button on browsers.
			unset($_POST);
		}
	}
}

// Disable modules (this must be after session_start and after conf has been loaded)
if (GETPOST('disablemodules','alpha'))  $_SESSION["disablemodules"]=GETPOST('disablemodules','alpha');
if (! empty($_SESSION["disablemodules"]))
{
    $modulepartkeys = array('css', 'js', 'tabs', 'triggers', 'login', 'substitutions', 'menus', 'theme', 'sms', 'tpl', 'barcode', 'models', 'societe', 'hooks', 'dir', 'syslog', 'tpllinkable', 'contactelement', 'moduleforexternal');

    $disabled_modules=explode(',',$_SESSION["disablemodules"]);
	foreach($disabled_modules as $module)
	{
		if ($module)
		{
			if (empty($conf->$module)) $conf->$module=new stdClass();    // To avoid warnings
			$conf->$module->enabled=false;
			foreach($modulepartkeys as $modulepartkey)
			{
			    unset($conf->modules_parts[$modulepartkey][$module]);
			}
			if ($module == 'fournisseur')		// Special case
			{
				$conf->supplier_order->enabled=0;
				$conf->supplier_invoice->enabled=0;
			}
		}
	}
}

/*
 * Phase authentication / login
 */
$login='';
if (! defined('NOLOGIN'))
{
	// $authmode lists the different means of identification to be tested in order of preference.
	// Example: 'http', 'dolibarr', 'ldap', 'http,forceuser', '...'

	if (defined('MAIN_AUTHENTICATION_MODE'))
	{
		$dolibarr_main_authentication = constant('MAIN_AUTHENTICATION_MODE');
	}
	else
	{
		// Authentication mode
		if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication='http,dolibarr';
		// Authentication mode: forceuser
		if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user='auto';
	}
	// Set authmode
	$authmode=explode(',',$dolibarr_main_authentication);

	// No authentication mode
	if (! count($authmode))
	{
		$langs->load('main');
		dol_print_error('',$langs->trans("ErrorConfigParameterNotDefined",'dolibarr_main_authentication'));
		exit;
	}

	// If login request was already post, we retrieve login from the session
	// Call module if not realized that his request.
	// At the end of this phase, the variable $login is defined.
	$resultFetchUser='';
	$test=true;
	if (! isset($_SESSION["dol_login"]))
	{
		// It is not already authenticated and it requests the login / password
		include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		$dol_dst_observed=GETPOST("dst_observed",'int',3);
		$dol_dst_first=GETPOST("dst_first",'int',3);
		$dol_dst_second=GETPOST("dst_second",'int',3);
		$dol_screenwidth=GETPOST("screenwidth",'int',3);
		$dol_screenheight=GETPOST("screenheight",'int',3);
		$dol_hide_topmenu=GETPOST('dol_hide_topmenu','int',3);
		$dol_hide_leftmenu=GETPOST('dol_hide_leftmenu','int',3);
		$dol_optimize_smallscreen=GETPOST('dol_optimize_smallscreen','int',3);
		$dol_no_mouse_hover=GETPOST('dol_no_mouse_hover','int',3);
		$dol_use_jmobile=GETPOST('dol_use_jmobile','int',3);
		//dol_syslog("POST key=".join(array_keys($_POST),',').' value='.join($_POST,','));

		// If in demo mode, we check we go to home page through the public/demo/index.php page
		if (! empty($dolibarr_main_demo) && $_SERVER['PHP_SELF'] == DOL_URL_ROOT.'/index.php')  // We ask index page
		{
			if (empty($_SERVER['HTTP_REFERER']) || ! preg_match('/public/',$_SERVER['HTTP_REFERER']))
			{
				dol_syslog("Call index page from another url than demo page (call is done from page ".$_SERVER['HTTP_REFERER'].")");
				$url='';
				$url.=($url?'&':'').($dol_hide_topmenu?'dol_hide_topmenu='.$dol_hide_topmenu:'');
				$url.=($url?'&':'').($dol_hide_leftmenu?'dol_hide_leftmenu='.$dol_hide_leftmenu:'');
				$url.=($url?'&':'').($dol_optimize_smallscreen?'dol_optimize_smallscreen='.$dol_optimize_smallscreen:'');
				$url.=($url?'&':'').($dol_no_mouse_hover?'dol_no_mouse_hover='.$dol_no_mouse_hover:'');
				$url.=($url?'&':'').($dol_use_jmobile?'dol_use_jmobile='.$dol_use_jmobile:'');
				$url=DOL_URL_ROOT.'/public/demo/index.php'.($url?'?'.$url:'');
				header("Location: ".$url);
				exit;
			}
		}

		// Verification security graphic code
		if (GETPOST("username","alpha",2) && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
		{
			$sessionkey = 'dol_antispam_value';
			$ok=(array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));

			// Check code
			if (! $ok)
			{
				dol_syslog('Bad value for code, connexion refused');
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadValueForCode");
				$test=false;

				// TODO @deprecated Remove this. Hook must be used, not this trigger.
				$user->trigger_mesg='ErrorBadValueForCode - login='.GETPOST("username","alpha",2);
				// Call of triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($db);
				$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
				if ($result < 0) {
					$error++;
				}
				// End Call of triggers

				// Hooks on failed login
				$action='';
				$hookmanager->initHooks(array('login'));
				$parameters=array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
				$reshook=$hookmanager->executeHooks('afterLoginFailed',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;

				// Note: exit is done later
			}
		}

		$allowedmethodtopostusername = 2;
		if (defined('MAIN_AUTHENTICATION_POST_METHOD')) $allowedmethodtopostusername = constant('MAIN_AUTHENTICATION_POST_METHOD');
		$usertotest		= (! empty($_COOKIE['login_dolibarr']) ? $_COOKIE['login_dolibarr'] : GETPOST("username","alpha",$allowedmethodtopostusername));
		$passwordtotest	= GETPOST('password','none',$allowedmethodtopostusername);
		$entitytotest	= (GETPOST('entity','int') ? GETPOST('entity','int') : (!empty($conf->entity) ? $conf->entity : 1));

		// Define if we received data to test the login.
		$goontestloop=false;
		if (isset($_SERVER["REMOTE_USER"]) && in_array('http',$authmode)) $goontestloop=true;
		if ($dolibarr_main_authentication == 'forceuser' && ! empty($dolibarr_auto_user)) $goontestloop=true;
		if (GETPOST("username","alpha",$allowedmethodtopostusername) || ! empty($_COOKIE['login_dolibarr']) || GETPOST('openid_mode','alpha',1)) $goontestloop=true;

		if (! is_object($langs)) // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs=new Translate("",$conf);
			$langcode=(GETPOST('lang','aZ09',1)?GETPOST('lang','aZ09',1):(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT));
			if (defined('MAIN_LANG_DEFAULT')) $langcode=constant('MAIN_LANG_DEFAULT');
			$langs->setDefaultLang($langcode);
		}

		// Validation of login/pass/entity
		// If ok, the variable login will be returned
		// If error, we will put error message in session under the name dol_loginmesg
		if ($test && $goontestloop)
		{
			$login = checkLoginPassEntity($usertotest,$passwordtotest,$entitytotest,$authmode);
			if ($login)
			{
				$dol_authmode=$conf->authmode;	// This properties is defined only when logged, to say what mode was successfully used
				$dol_tz=$_POST["tz"];
				$dol_tz_string=$_POST["tz_string"];
				$dol_tz_string=preg_replace('/\s*\(.+\)$/','',$dol_tz_string);
				$dol_tz_string=preg_replace('/,/','/',$dol_tz_string);
				$dol_tz_string=preg_replace('/\s/','_',$dol_tz_string);
				$dol_dst=0;
				if (isset($_POST["dst_first"]) && isset($_POST["dst_second"]))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
					$datenow=dol_now();
					$datefirst=dol_stringtotime($_POST["dst_first"]);
					$datesecond=dol_stringtotime($_POST["dst_second"]);
					if ($datenow >= $datefirst && $datenow < $datesecond) $dol_dst=1;
				}
				//print $datefirst.'-'.$datesecond.'-'.$datenow.'-'.$dol_tz.'-'.$dol_tzstring.'-'.$dol_dst; exit;
			}

			if (! $login)
			{
				dol_syslog('Bad password, connexion refused',LOG_DEBUG);
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				// Bad password. No authmode has found a good password.
				// We set a generic message if not defined inside function checkLoginPassEntity or subfunctions
				if (empty($_SESSION["dol_loginmesg"])) $_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");

				// TODO @deprecated Remove this. Hook must be used, not this trigger.
				$user->trigger_mesg=$langs->trans("ErrorBadLoginPassword").' - login='.GETPOST("username","alpha",2);
				// Call of triggers
				include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
				$interface=new Interfaces($db);
				$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,GETPOST("username","alpha",2));
				if ($result < 0) {
					$error++;
				}
				// End Call of triggers

				// Hooks on failed login
				$action='';
				$hookmanager->initHooks(array('login'));
				$parameters=array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
				$reshook=$hookmanager->executeHooks('afterLoginFailed',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;

				// Note: exit is done in next chapter
			}
		}

		// End test login / passwords
		if (! $login || (in_array('ldap',$authmode) && empty($passwordtotest)))	// With LDAP we refused empty password because some LDAP are "opened" for anonymous access so connexion is a success.
		{
			// No data to test login, so we show the login page
			dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]." showing the login form and exit");
			if (defined('NOREDIRECTBYMAINTOLOGIN')) return 'ERROR_NOT_LOGGED';
			else dol_loginfunction($langs,$conf,(! empty($mysoc)?$mysoc:''));
			exit;
		}

		$resultFetchUser=$user->fetch('', $login, '', 1, ($entitytotest > 0 ? $entitytotest : -1));
		if ($resultFetchUser <= 0)
		{
			dol_syslog('User not found, connexion refused');
			session_destroy();
			session_name($sessionname);
			session_set_cookie_params(0, '/', null, false, true);   // Add tag httponly on session cookie
			session_start();    // Fixing the bug of register_globals here is useless since session is empty

			if ($resultFetchUser == 0)
			{
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);

				// TODO @deprecated Remove this. Hook must be used, not this trigger.
				$user->trigger_mesg='ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
			}
			if ($resultFetchUser < 0)
			{
				$_SESSION["dol_loginmesg"]=$user->error;

				// TODO @deprecated Remove this. Hook must be used, not this trigger.
				$user->trigger_mesg=$user->error;
			}

			// Call triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			// Hooks on failed login
			$action='';
			$hookmanager->initHooks(array('login'));
			$parameters=array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
			$reshook=$hookmanager->executeHooks('afterLoginFailed',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) $error++;

			$paramsurl=array();
			if (GETPOST('textbrowser','int')) $paramsurl[]='textbrowser='.GETPOST('textbrowser','int');
			if (GETPOST('nojs','int'))        $paramsurl[]='nojs='.GETPOST('nojs','int');
			if (GETPOST('lang','aZ09'))       $paramsurl[]='lang='.GETPOST('lang','aZ09');
			header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl)?'?'.implode('&',$paramsurl):''));
			exit;
		}
	}
	else
	{
		// We are already into an authenticated session
		$login=$_SESSION["dol_login"];
		$entity=$_SESSION["dol_entity"];
		dol_syslog("- This is an already logged session. _SESSION['dol_login']=".$login." _SESSION['dol_entity']=".$entity, LOG_DEBUG);

		$resultFetchUser=$user->fetch('', $login, '', 1, ($entity > 0 ? $entity : -1));
		if ($resultFetchUser <= 0)
		{
			// Account has been removed after login
			dol_syslog("Can't load user even if session logged. _SESSION['dol_login']=".$login, LOG_WARNING);
			session_destroy();
			session_name($sessionname);
			session_set_cookie_params(0, '/', null, false, true);   // Add tag httponly on session cookie
			session_start();    // Fixing the bug of register_globals here is useless since session is empty

			if ($resultFetchUser == 0)
			{
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);

				// TODO @deprecated Remove this. Hook must be used, not this trigger.
				$user->trigger_mesg='ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
			}
			if ($resultFetchUser < 0)
			{
				$_SESSION["dol_loginmesg"]=$user->error;

				// TODO @deprecated Remove this. Hook must be used, not this trigger.
				$user->trigger_mesg=$user->error;
			}

			// TODO @deprecated Remove this. Hook must be used, not this trigger.
			// Call triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			// Hooks on failed login
			$action='';
			$hookmanager->initHooks(array('login'));
			$parameters=array('dol_authmode'=>$dol_authmode, 'dol_loginmesg'=>$_SESSION["dol_loginmesg"]);
			$reshook=$hookmanager->executeHooks('afterLoginFailed',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) $error++;

			$paramsurl=array();
			if (GETPOST('textbrowser','int')) $paramsurl[]='textbrowser='.GETPOST('textbrowser','int');
			if (GETPOST('nojs','int'))        $paramsurl[]='nojs='.GETPOST('nojs','int');
			if (GETPOST('lang','aZ09'))       $paramsurl[]='lang='.GETPOST('lang','aZ09');
			header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl)?'?'.implode('&',$paramsurl):''));
			exit;
		}
		else
		{
		    // Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		    $hookmanager->initHooks(array('main'));

		    // Code for search criteria persistence.
		    if (! empty($_GET['save_lastsearch_values']))    // We must use $_GET here
		    {
			    $relativepathstring = preg_replace('/\?.*$/','',$_SERVER["HTTP_REFERER"]);
			    $relativepathstring = preg_replace('/^https?:\/\/[^\/]*/','',$relativepathstring);     // Get full path except host server
			    // Clean $relativepathstring
   			    if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'),'/').'/', '', $relativepathstring);
			    $relativepathstring = preg_replace('/^\//', '', $relativepathstring);
			    $relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
			    //var_dump($relativepathstring);

			    // We click on a link that leave a page we have to save search criteria, contextpage, limit and page. We save them from tmp to no tmp
			    if (! empty($_SESSION['lastsearch_values_tmp_'.$relativepathstring]))
			    {
			    	$_SESSION['lastsearch_values_'.$relativepathstring]=$_SESSION['lastsearch_values_tmp_'.$relativepathstring];
				    unset($_SESSION['lastsearch_values_tmp_'.$relativepathstring]);
			    }
			    if (! empty($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]))
			    {
			    	$_SESSION['lastsearch_contextpage_'.$relativepathstring]=$_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring];
			    	unset($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]);
			    }
			    if (! empty($_SESSION['lastsearch_page_tmp_'.$relativepathstring]) && $_SESSION['lastsearch_page_tmp_'.$relativepathstring] > 1)
			    {
			    	$_SESSION['lastsearch_page_'.$relativepathstring]=$_SESSION['lastsearch_page_tmp_'.$relativepathstring];
			    	unset($_SESSION['lastsearch_page_tmp_'.$relativepathstring]);
			    }
			    if (! empty($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]) && $_SESSION['lastsearch_limit_tmp_'.$relativepathstring] != $conf->liste_limit)
			    {
			    	$_SESSION['lastsearch_limit_'.$relativepathstring]=$_SESSION['lastsearch_limit_tmp_'.$relativepathstring];
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
	if (! isset($_SESSION["dol_login"]))
	{
		// New session for this login has started.
		$error=0;

		// Store value into session (values always stored)
		$_SESSION["dol_login"]=$user->login;
		$_SESSION["dol_authmode"]=isset($dol_authmode)?$dol_authmode:'';
		$_SESSION["dol_tz"]=isset($dol_tz)?$dol_tz:'';
		$_SESSION["dol_tz_string"]=isset($dol_tz_string)?$dol_tz_string:'';
		$_SESSION["dol_dst"]=isset($dol_dst)?$dol_dst:'';
		$_SESSION["dol_dst_observed"]=isset($dol_dst_observed)?$dol_dst_observed:'';
		$_SESSION["dol_dst_first"]=isset($dol_dst_first)?$dol_dst_first:'';
		$_SESSION["dol_dst_second"]=isset($dol_dst_second)?$dol_dst_second:'';
		$_SESSION["dol_screenwidth"]=isset($dol_screenwidth)?$dol_screenwidth:'';
		$_SESSION["dol_screenheight"]=isset($dol_screenheight)?$dol_screenheight:'';
		$_SESSION["dol_company"]=$conf->global->MAIN_INFO_SOCIETE_NOM;
		$_SESSION["dol_entity"]=$conf->entity;
		// Store value into session (values stored only if defined)
		if (! empty($dol_hide_topmenu))         $_SESSION['dol_hide_topmenu']=$dol_hide_topmenu;
		if (! empty($dol_hide_leftmenu))        $_SESSION['dol_hide_leftmenu']=$dol_hide_leftmenu;
		if (! empty($dol_optimize_smallscreen)) $_SESSION['dol_optimize_smallscreen']=$dol_optimize_smallscreen;
		if (! empty($dol_no_mouse_hover))       $_SESSION['dol_no_mouse_hover']=$dol_no_mouse_hover;
		if (! empty($dol_use_jmobile))          $_SESSION['dol_use_jmobile']=$dol_use_jmobile;

		dol_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"]." Session id=".session_id());

		$db->begin();

		$user->update_last_login_date();

		$loginfo = 'TZ='.$_SESSION["dol_tz"].';TZString='.$_SESSION["dol_tz_string"].';Screen='.$_SESSION["dol_screenwidth"].'x'.$_SESSION["dol_screenheight"];

		// TODO @deprecated Remove this. Hook must be used, not this trigger.
		$user->trigger_mesg = $loginfo;
		// Call triggers
		include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		$interface=new Interfaces($db);
		$result=$interface->run_triggers('USER_LOGIN',$user,$user,$langs,$conf);
		if ($result < 0) {
			$error++;
		}
		// End call triggers

		// Hooks on successfull login
		$action='';
		$hookmanager->initHooks(array('login'));
		$parameters=array('dol_authmode'=>$dol_authmode, 'dol_loginfo'=>$loginfo);
		$reshook=$hookmanager->executeHooks('afterLogin',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) $error++;

		if ($error)
		{
			$db->rollback();
			session_destroy();
			dol_print_error($db,'Error in some hooks afterLogin (or old trigger USER_LOGIN)');
			exit;
		}
		else
		{
			$db->commit();
		}

		// Change landing page if defined.
		$landingpage=(empty($user->conf->MAIN_LANDING_PAGE)?(empty($conf->global->MAIN_LANDING_PAGE)?'':$conf->global->MAIN_LANDING_PAGE):$user->conf->MAIN_LANDING_PAGE);
		if (! empty($landingpage))    // Example: /index.php
		{
			$newpath=dol_buildpath($landingpage, 1);
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
		$user->rights->user->user->lire=1;
		$user->rights->user->user->creer=1;
		$user->rights->user->user->password=1;
		$user->rights->user->user->supprimer=1;
		$user->rights->user->self->creer=1;
		$user->rights->user->self->password=1;
	}

	/*
     * Overwrite some configs globals (try to avoid this and have code to use instead $user->conf->xxx)
     */

	// Set liste_limit
	if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT))	$conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT;	// Can be 0
	if (isset($user->conf->PRODUIT_LIMIT_SIZE))	$conf->product->limit_size = $user->conf->PRODUIT_LIMIT_SIZE;	// Can be 0

	// Replace conf->css by personalized value if theme not forced
	if (empty($conf->global->MAIN_FORCETHEME) && ! empty($user->conf->MAIN_THEME))
	{
		$conf->theme=$user->conf->MAIN_THEME;
		$conf->css  = "/theme/".$conf->theme."/style.css.php";
	}
}

// Case forcing style from url
if (GETPOST('theme','alpha'))
{
	$conf->theme=GETPOST('theme','alpha',1);
	$conf->css  = "/theme/".$conf->theme."/style.css.php";
}


// Set javascript option
if (! GETPOST('nojs','int'))   // If javascript was not disabled on URL
{
	if (! empty($user->conf->MAIN_DISABLE_JAVASCRIPT))
	{
		$conf->use_javascript_ajax=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
	}
}
else $conf->use_javascript_ajax=0;
// Set MAIN_OPTIMIZEFORTEXTBROWSER
if (GETPOST('textbrowser','int') || (! empty($conf->browser->name) && $conf->browser->name == 'lynxlinks') || ! empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER))   // If we must enable text browser
{
	$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER=1;
}
elseif (! empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER))
{
	$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER=$user->conf->MAIN_OPTIMIZEFORTEXTBROWSER;
}

// Set terminal output option according to conf->browser.
if (GETPOST('dol_hide_leftmenu','int') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu','int') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen','int') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover','int') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile','int') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;
if (! empty($conf->browser->layout) && $conf->browser->layout != 'classic') $conf->dol_no_mouse_hover=1;
if ((! empty($conf->browser->layout) && $conf->browser->layout == 'phone')
	|| (! empty($_SESSION['dol_screenwidth']) && $_SESSION['dol_screenwidth'] < 400)
	|| (! empty($_SESSION['dol_screenheight']) && $_SESSION['dol_screenheight'] < 400)
)
{
	$conf->dol_optimize_smallscreen=1;
}
// If we force to use jmobile, then we reenable javascript
if (! empty($conf->dol_use_jmobile)) $conf->use_javascript_ajax=1;
// Replace themes bugged with jmobile with eldy
if (! empty($conf->dol_use_jmobile) && in_array($conf->theme,array('bureau2crea','cameleo','amarok')))
{
	$conf->theme='eldy';
	$conf->css  =  "/theme/".$conf->theme."/style.css.php";
}
//var_dump($conf->browser->phone);

if (! defined('NOREQUIRETRAN'))
{
	if (! GETPOST('lang','aZ09'))	// If language was not forced on URL
	{
		// If user has chosen its own language
		if (! empty($user->conf->MAIN_LANG_DEFAULT))
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

if (! defined('NOLOGIN'))
{
	// If the login is not recovered, it is identified with an account that does not exist.
	// Hacking attempt?
	if (! $user->login) accessforbidden();

	// Check if user is active
	if ($user->statut < 1)
	{
		// If not active, we refuse the user
		$langs->load("other");
		dol_syslog("Authentification ko as login is disabled");
		accessforbidden($langs->trans("ErrorLoginDisabled"));
		exit;
	}

	// Load permissions
	$user->getrights();
}


dol_syslog("--- Access to ".$_SERVER["PHP_SELF"].' - action='.GETPOST('action','az09').', massaction='.GETPOST('massaction','az09'));
//Another call for easy debugg
//dol_syslog("Access to ".$_SERVER["PHP_SELF"].' GET='.join(',',array_keys($_GET)).'->'.join(',',$_GET).' POST:'.join(',',array_keys($_POST)).'->'.join(',',$_POST));

// Load main languages files
if (! defined('NOREQUIRETRAN'))
{
	// Load translation files required by page
	$langs->loadLangs(array('main', 'dict'));
}

// Define some constants used for style of arrays
$bc=array(0=>'class="impair"',1=>'class="pair"');
$bcdd=array(0=>'class="drag drop oddeven"',1=>'class="drag drop oddeven"');
$bcnd=array(0=>'class="nodrag nodrop nohover"',1=>'class="nodrag nodrop nohoverpair"');		// Used for tr to add new lines
$bctag=array(0=>'class="impair tagtr"',1=>'class="pair tagtr"');

// Define messages variables
$mesg=''; $warning=''; $error=0;
// deprecated, see setEventMessages() and dol_htmloutput_events()
$mesgs=array(); $warnings=array(); $errors=array();

// Constants used to defined number of lines in textarea
if (empty($conf->browser->firefox))
{
	define('ROWS_1',1);
	define('ROWS_2',2);
	define('ROWS_3',3);
	define('ROWS_4',4);
	define('ROWS_5',5);
	define('ROWS_6',6);
	define('ROWS_7',7);
	define('ROWS_8',8);
	define('ROWS_9',9);
}
else
{
	define('ROWS_1',0);
	define('ROWS_2',1);
	define('ROWS_3',2);
	define('ROWS_4',3);
	define('ROWS_5',4);
	define('ROWS_6',5);
	define('ROWS_7',6);
	define('ROWS_8',7);
	define('ROWS_9',8);
}

$heightforframes=48;

// Init menu manager
if (! defined('NOREQUIREMENU'))
{
	if (empty($user->societe_id))    // If internal user or not defined
	{
		$conf->standard_menu=(empty($conf->global->MAIN_MENU_STANDARD_FORCED)?(empty($conf->global->MAIN_MENU_STANDARD)?'eldy_menu.php':$conf->global->MAIN_MENU_STANDARD):$conf->global->MAIN_MENU_STANDARD_FORCED);
	}
	else                        // If external user
	{
		$conf->standard_menu=(empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED)?(empty($conf->global->MAIN_MENUFRONT_STANDARD)?'eldy_menu.php':$conf->global->MAIN_MENUFRONT_STANDARD):$conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
	}

	// Load the menu manager (only if not already done)
	$file_menu=$conf->standard_menu;
	if (GETPOST('menu','alpha')) $file_menu=GETPOST('menu','alpha');     // example: menu=eldy_menu.php
	if (! class_exists('MenuManager'))
	{
		$menufound=0;
		$dirmenus=array_merge(array("/core/menus/"),(array) $conf->modules_parts['menus']);
		foreach($dirmenus as $dirmenu)
		{
			$menufound=dol_include_once($dirmenu."standard/".$file_menu);
			if (class_exists('MenuManager')) break;
		}
		if (! class_exists('MenuManager'))	// If failed to include, we try with standard eldy_menu.php
		{
			dol_syslog("You define a menu manager '".$file_menu."' that can not be loaded.", LOG_WARNING);
			$file_menu='eldy_menu.php';
			include_once DOL_DOCUMENT_ROOT."/core/menus/standard/".$file_menu;
		}
	}
	$menumanager = new MenuManager($db, empty($user->societe_id)?0:1);
	$menumanager->loadMenu();
}



// Functions

if (! function_exists("llxHeader"))
{
	/**
	 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
	 *
	 * @param 	string 	$head				Optionnal head lines
	 * @param 	string 	$title				HTML title
	 * @param	string	$help_url			Url links to help page
	 * 		                            	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
	 *                                  	For other external page: http://server/url
	 * @param	string	$target				Target to use on links
	 * @param 	int    	$disablejs			More content into html header
	 * @param 	int    	$disablehead		More content into html header
	 * @param 	array  	$arrayofjs			Array of complementary js files
	 * @param 	array  	$arrayofcss			Array of complementary css files
	 * @param	string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
	 * @param   string  $morecssonbody      More CSS on body tag.
	 * @param	string	$replacemainareaby	Replace call to main_area() by a print of this string
	 * @return	void
	 */
	function llxHeader($head='', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='', $morecssonbody='', $replacemainareaby='')
	{
		global $conf;

		// html header
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

		print '<body id="mainbody"'.($morecssonbody?' class="'.$morecssonbody.'"':'').'>' . "\n";

		// top menu and left menu area
		if (empty($conf->dol_hide_topmenu) || GETPOST('dol_invisible_topmenu','int'))
		{
			top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $help_url);
		}

		if (empty($conf->dol_hide_leftmenu))
		{
			left_menu('', $help_url, '', '', 1, $title, 1);
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
function top_httphead($contenttype='text/html', $forcenocache=0)
{
	global $db, $conf, $hookmanager;

	if ($contenttype == 'text/html' ) header("Content-Type: text/html; charset=".$conf->file->character_set_client);
	else header("Content-Type: ".$contenttype);
	// Security options
	header("X-Content-Type-Options: nosniff");  // With the nosniff option, if the server says the content is text/html, the browser will render it as text/html (note that most browsers now force this option to on)
	header("X-Frame-Options: SAMEORIGIN");      // Frames allowed only if on same domain (stop some XSS attacks)
	//header("X-XSS-Protection: 1");      		// XSS protection of some browsers (note: use of Content-Security-Policy is more efficient). Disabled as deprecated.
	if (! defined('FORCECSP'))
	{
		//if (! isset($conf->global->MAIN_HTTP_CONTENT_SECURITY_POLICY))
		//{
		//	// A default security policy that keep usage of js external component like ckeditor, stripe, google, working
		//	$contentsecuritypolicy = "font-src *; img-src *; style-src * 'unsafe-inline' 'unsafe-eval'; default-src 'self' *.stripe.com 'unsafe-inline' 'unsafe-eval'; script-src 'self' *.stripe.com 'unsafe-inline' 'unsafe-eval'; frame-src 'self' *.stripe.com; connect-src 'self';";
		//}
		//else $contentsecuritypolicy = $conf->global->MAIN_HTTP_CONTENT_SECURITY_POLICY;
		$contentsecuritypolicy = $conf->global->MAIN_HTTP_CONTENT_SECURITY_POLICY;

		if (! is_object($hookmanager)) $hookmanager = new HookManager($db);
		$hookmanager->initHooks("main");

		$parameters=array('contentsecuritypolicy'=>$contentsecuritypolicy);
		$result=$hookmanager->executeHooks('setContentSecurityPolicy',$parameters);    // Note that $action and $object may have been modified by some hooks
		if ($result > 0) $contentsecuritypolicy = $hookmanager->resPrint;	// Replace CSP
		else $contentsecuritypolicy .= $hookmanager->resPrint;				// Concat CSP

		if (! empty($contentsecuritypolicy))
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
	}
	elseif (constant('FORCECSP'))
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
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $disablejmobile=0, $disablenofollow=0)
{
	global $db, $conf, $langs, $user, $hookmanager;

	top_httphead();

	if (empty($conf->css)) $conf->css = '/theme/eldy/style.css.php';	// If not defined, eldy by default

	print '<!doctype html>'."\n";

	if (! empty($conf->global->MAIN_USE_CACHE_MANIFEST)) print '<html lang="'.substr($langs->defaultlang,0,2).'" manifest="'.DOL_URL_ROOT.'/cache.manifest">'."\n";
	else print '<html lang="'.substr($langs->defaultlang,0,2).'">'."\n";
	//print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">'."\n";
	if (empty($disablehead))
	{
		$ext='layout='.$conf->browser->layout.'&version='.urlencode(DOL_VERSION);

		print "<head>\n";
		if (GETPOST('dol_basehref','alpha')) print '<base href="'.dol_escape_htmltag(GETPOST('dol_basehref','alpha')).'">'."\n";
		// Displays meta
		print '<meta charset="UTF-8">'."\n";
		print '<meta name="robots" content="noindex'.($disablenofollow?'':',nofollow').'">'."\n";	// Do not index
		print '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";		// Scale for mobile device
		print '<meta name="author" content="Dolibarr Development Team">'."\n";
		// Favicon
		$favicon=dol_buildpath('/theme/'.$conf->theme.'/img/favicon.ico',1);
		if (! empty($conf->global->MAIN_FAVICON_URL)) $favicon=$conf->global->MAIN_FAVICON_URL;
		if (empty($conf->dol_use_jmobile)) print '<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>'."\n";	// Not required into an Android webview
		//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="top" title="'.$langs->trans("Home").'" href="'.(DOL_URL_ROOT?DOL_URL_ROOT:'/').'">'."\n";
		//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
		//if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="author" title="Dolibarr Development Team" href="https://www.dolibarr.org">'."\n";

		// Displays title
		$appli=constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

		print '<title>';
		$titletoshow='';
		if ($title && ! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/noapp/',$conf->global->MAIN_HTML_TITLE)) $titletoshow = dol_htmlentities($title);
		else if ($title) $titletoshow = dol_htmlentities($appli.' - '.$title);
		else $titletoshow = dol_htmlentities($appli);

		if (! is_object($hookmanager)) $hookmanager = new HookManager($db);
		$hookmanager->initHooks("main");
		$parameters=array('title'=>$titletoshow);
		$result=$hookmanager->executeHooks('setHtmlTitle',$parameters);		// Note that $action and $object may have been modified by some hooks
		if ($result > 0) $titletoshow = $hookmanager->resPrint;				// Replace Title to show
		else $titletoshow .= $hookmanager->resPrint;						// Concat to Title to show

		print $titletoshow;
		print '</title>';

		print "\n";

		if (GETPOST('version','int')) $ext='version='.GETPOST('version','int');	// usefull to force no cache on css/js
		if (GETPOST('testmenuhider','int') || ! empty($conf->global->MAIN_TESTMENUHIDER)) $ext.='&testmenuhider='.(GETPOST('testmenuhider','int')?GETPOST('testmenuhider','int'):$conf->global->MAIN_TESTMENUHIDER);

		$themeparam='?lang='.$langs->defaultlang.'&amp;theme='.$conf->theme.(GETPOST('optioncss','aZ09')?'&amp;optioncss='.GETPOST('optioncss','aZ09',1):'').'&amp;userid='.$user->id.'&amp;entity='.$conf->entity;
		$themeparam.=($ext?'&amp;'.$ext:'');
		if (! empty($_SESSION['dol_resetcache'])) $themeparam.='&amp;dol_resetcache='.$_SESSION['dol_resetcache'];
		if (GETPOST('dol_hide_topmenu','int'))           { $themeparam.='&amp;dol_hide_topmenu='.GETPOST('dol_hide_topmenu','int'); }
		if (GETPOST('dol_hide_leftmenu','int'))          { $themeparam.='&amp;dol_hide_leftmenu='.GETPOST('dol_hide_leftmenu','int'); }
		if (GETPOST('dol_optimize_smallscreen','int'))   { $themeparam.='&amp;dol_optimize_smallscreen='.GETPOST('dol_optimize_smallscreen','int'); }
		if (GETPOST('dol_no_mouse_hover','int'))         { $themeparam.='&amp;dol_no_mouse_hover='.GETPOST('dol_no_mouse_hover','int'); }
		if (GETPOST('dol_use_jmobile','int'))            { $themeparam.='&amp;dol_use_jmobile='.GETPOST('dol_use_jmobile','int'); $conf->dol_use_jmobile=GETPOST('dol_use_jmobile','int'); }

		if (! defined('DISABLE_JQUERY') && ! $disablejs && $conf->use_javascript_ajax)
		{
			print '<!-- Includes CSS for JQuery (Ajax library) -->'."\n";
			$jquerytheme = 'base';
			if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
			if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css'.($ext?'?'.$ext:'').'">'."\n";  // JQuery
			else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui.css'.($ext?'?'.$ext:'').'">'."\n";    // JQuery
			if (! defined('DISABLE_JQUERY_JNOTIFY')) print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css'.($ext?'?'.$ext:'').'">'."\n";          // JNotify
			if (! defined('DISABLE_SELECT2') && (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')))     // jQuery plugin "mutiselect", "multiple-select", "select2"...
			{
				$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
				print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/css/'.$tmpplugin.'.css'.($ext?'?'.$ext:'').'">'."\n";
			}
		}

		if (! defined('DISABLE_FONT_AWSOME'))
		{
			print '<!-- Includes CSS for font awesome -->'."\n";
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/fontawesome/css/font-awesome.min.css'.($ext?'?'.$ext:'').'">'."\n";
		}

		print '<!-- Includes CSS for Dolibarr theme -->'."\n";
		// Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
		$themepath=dol_buildpath($conf->css,1);
		$themesubdir='';
		if (! empty($conf->modules_parts['theme']))	// This slow down
		{
			foreach($conf->modules_parts['theme'] as $reldir)
			{
				if (file_exists(dol_buildpath($reldir.$conf->css, 0)))
				{
					$themepath=dol_buildpath($reldir.$conf->css, 1);
					$themesubdir=$reldir;
					break;
				}
			}
		}

		//print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
		print '<link rel="stylesheet" type="text/css" href="'.$themepath.$themeparam.'">'."\n";
		if (! empty($conf->global->MAIN_FIX_FLASH_ON_CHROME)) print '<!-- Includes CSS that does not exists as a workaround of flash bug of chrome -->'."\n".'<link rel="stylesheet" type="text/css" href="filethatdoesnotexiststosolvechromeflashbug">'."\n";

		// CSS forced by modules (relative url starting with /)
		if (! empty($conf->modules_parts['css']))
		{
			$arraycss=(array) $conf->modules_parts['css'];
			foreach($arraycss as $modcss => $filescss)
			{
				$filescss=(array) $filescss;	// To be sure filecss is an array
				foreach($filescss as $cssfile)
				{
					if (empty($cssfile)) dol_syslog("Warning: module ".$modcss." declared a css path file into its descriptor that is empty.", LOG_WARNING);
					// cssfile is a relative path
					print '<!-- Includes CSS added by module '.$modcss. ' -->'."\n".'<link rel="stylesheet" type="text/css" href="'.dol_buildpath($cssfile,1);
					// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters, so browser cache is not used.
					if (!preg_match('/\.css$/i',$cssfile)) print $themeparam;
					print '">'."\n";
				}
			}
		}
		// CSS forced by page in top_htmlhead call (relative url starting with /)
		if (is_array($arrayofcss))
		{
			foreach($arrayofcss as $cssfile)
			{
				print '<!-- Includes CSS added by page -->'."\n".'<link rel="stylesheet" type="text/css" title="default" href="'.dol_buildpath($cssfile,1);
				// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters and browser cache is not used.
				if (!preg_match('/\.css$/i',$cssfile)) print $themeparam;
				print '">'."\n";
			}
		}

		// Output standard javascript links
		if (! defined('DISABLE_JQUERY') && ! $disablejs && ! empty($conf->use_javascript_ajax))
		{
			// JQuery. Must be before other includes
			print '<!-- Includes JS for JQuery -->'."\n";
			if (defined('JS_JQUERY') && constant('JS_JQUERY')) print '<script type="text/javascript" src="'.JS_JQUERY.'jquery.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			if (! empty($conf->global->MAIN_FEATURES_LEVEL) && ! defined('JS_JQUERY_MIGRATE_DISABLED'))
			{
				if (defined('JS_JQUERY_MIGRATE') && constant('JS_JQUERY_MIGRATE')) print '<script type="text/javascript" src="'.JS_JQUERY_MIGRATE.'jquery-migrate.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-migrate.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			}
			if (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI')) print '<script type="text/javascript" src="'.JS_JQUERY_UI.'jquery-ui.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			if (! defined('DISABLE_JQUERY_TABLEDND')) print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			// jQuery jnotify
			if (empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && ! defined('DISABLE_JQUERY_JNOTIFY'))
			{
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			}
			// Flot
			if (empty($conf->global->MAIN_DISABLE_JQUERY_FLOT) && ! defined('DISABLE_JQUERY_FLOT'))
			{
				if (constant('JS_JQUERY_FLOT'))
				{
					print '<script type="text/javascript" src="'.JS_JQUERY_FLOT.'jquery.flot.js'.($ext?'?'.$ext:'').'"></script>'."\n";
					print '<script type="text/javascript" src="'.JS_JQUERY_FLOT.'jquery.flot.pie.js'.($ext?'?'.$ext:'').'"></script>'."\n";
					print '<script type="text/javascript" src="'.JS_JQUERY_FLOT.'jquery.flot.stack.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				}
				else
				{
					print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/jquery.flot.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
					print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/jquery.flot.pie.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
					print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/jquery.flot.stack.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				}
			}
			// jQuery jeditable
			if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! defined('DISABLE_JQUERY_JEDITABLE'))
			{
				print '<!-- JS to manage editInPlace feature -->'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-datepicker.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-autocomplete.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				print '<script type="text/javascript">'."\n";
				print 'var urlSaveInPlace = \''.DOL_URL_ROOT.'/core/ajax/saveinplace.php\';'."\n";
				print 'var urlLoadInPlace = \''.DOL_URL_ROOT.'/core/ajax/loadinplace.php\';'."\n";
				print 'var tooltipInPlace = \''.$langs->transnoentities('ClickToEdit').'\';'."\n";	// Added in title attribute of span
				print 'var placeholderInPlace = \'&nbsp;\';'."\n";	// If we put another string than $langs->trans("ClickToEdit") here, nothing is shown. If we put empty string, there is error, Why ?
				print 'var cancelInPlace = \''.$langs->trans('Cancel').'\';'."\n";
				print 'var submitInPlace = \''.$langs->trans('Ok').'\';'."\n";
				print 'var indicatorInPlace = \'<img src="'.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'">\';'."\n";
				print 'var withInPlace = 300;';		// width in pixel for default string edit
				print '</script>'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/editinplace.js'.($ext?'?'.$ext:'').'"></script>'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ckeditor.js'.($ext?'?'.$ext:'').'"></script>'."\n";
			}
            // jQuery Timepicker
            if (! empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER'))
            {
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/timepicker.js.php?lang='.$langs->defaultlang.($ext?'&amp;'.$ext:'').'"></script>'."\n";
            }
            if (! defined('DISABLE_SELECT2') && (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')))     // jQuery plugin "mutiselect", "multiple-select", "select2", ...
            {
            	$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/js/'.$tmpplugin.'.full.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";	// We include full because we need the support of containerCssClass
            }
        }

        if (! $disablejs && ! empty($conf->use_javascript_ajax))
        {
            // CKEditor
            if (! empty($conf->fckeditor->enabled) && (empty($conf->global->FCKEDITOR_EDITORNAME) || $conf->global->FCKEDITOR_EDITORNAME == 'ckeditor') && ! defined('DISABLE_CKEDITOR'))
            {
                print '<!-- Includes JS for CKEditor -->'."\n";
                $pathckeditor = DOL_URL_ROOT . '/includes/ckeditor/ckeditor/';
                $jsckeditor='ckeditor.js';
                if (constant('JS_CKEDITOR'))	// To use external ckeditor 4 js lib
                {
                	$pathckeditor=constant('JS_CKEDITOR');
                }
                print '<script type="text/javascript">';
                print 'var CKEDITOR_BASEPATH = \''.$pathckeditor.'\';'."\n";
                print 'var ckeditorConfig = \''.dol_buildpath($themesubdir.'/theme/'.$conf->theme.'/ckeditor/config.js'.($ext?'?'.$ext:''),1).'\';'."\n";		// $themesubdir='' in standard usage
                print 'var ckeditorFilebrowserBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
                print 'var ckeditorFilebrowserImageBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Type=Image&Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
                print '</script>'."\n";
                print '<script type="text/javascript" src="'.$pathckeditor.$jsckeditor.($ext?'?'.$ext:'').'"></script>'."\n";
            }

            // Browser notifications
            if (! defined('DISABLE_BROWSER_NOTIF'))
            {
                $enablebrowsernotif=false;
                if (! empty($conf->agenda->enabled) && ! empty($conf->global->AGENDA_REMINDER_BROWSER)) $enablebrowsernotif=true;
                if ($conf->browser->layout == 'phone') $enablebrowsernotif=false;
                if ($enablebrowsernotif)
                {
                    print '<!-- Includes JS of Dolibarr (brwoser layout = '.$conf->browser->layout.')-->'."\n";
                    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/lib_notification.js.php'.($ext?'?'.$ext:'').'"></script>'."\n";
                }
            }

            // Global js function
            print '<!-- Includes JS of Dolibarr -->'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/lib_head.js.php?lang='.$langs->defaultlang.($ext?'&'.$ext:'').'"></script>'."\n";

            // JS forced by modules (relative url starting with /)
            if (! empty($conf->modules_parts['js']))		// $conf->modules_parts['js'] is array('module'=>array('file1','file2'))
        	{
        		$arrayjs=(array) $conf->modules_parts['js'];
	            foreach($arrayjs as $modjs => $filesjs)
	            {
        			$filesjs=(array) $filesjs;	// To be sure filejs is an array
		            foreach($filesjs as $jsfile)
		            {
	    	    		// jsfile is a relative path
	        	    	print '<!-- Include JS added by module '.$modjs. '-->'."\n".'<script type="text/javascript" src="'.dol_buildpath($jsfile,1).'"></script>'."\n";
		            }
	            }
        	}
            // JS forced by page in top_htmlhead (relative url starting with /)
            if (is_array($arrayofjs))
            {
                print '<!-- Includes JS added by page -->'."\n";
                foreach($arrayofjs as $jsfile)
                {
                    if (preg_match('/^http/i',$jsfile))
                    {
                        print '<script type="text/javascript" src="'.$jsfile.'"></script>'."\n";
                    }
                    else
                    {
                        if (! preg_match('/^\//',$jsfile)) $jsfile='/'.$jsfile;	// For backward compatibility
                        print '<script type="text/javascript" src="'.dol_buildpath($jsfile,1).'"></script>'."\n";
                    }
                }
            }
        }

        if (! empty($head)) print $head."\n";
        if (! empty($conf->global->MAIN_HTML_HEADER)) print $conf->global->MAIN_HTML_HEADER."\n";

        print "</head>\n\n";
    }

    $conf->headerdone=1;	// To tell header was output
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
 * 									                   For other external page: http://server/url
 *  @return		void
 */
function top_menu($head, $title='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='', $helppagename='')
{
	global $user, $conf, $langs, $db;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $hookmanager,$menumanager;

	$searchform='';
	$bookmarks='';

	// Instantiate hooks of thirdparty module
	$hookmanager->initHooks(array('toprightmenu'));

	$toprightmenu='';

	// For backward compatibility with old modules
	if (empty($conf->headerdone))
	{
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
		print '<body id="mainbody">';
	}

	/*
     * Top menu
     */
	if ((empty($conf->dol_hide_topmenu) || GETPOST('dol_invisible_topmenu','int')) && (! defined('NOREQUIREMENU') || ! constant('NOREQUIREMENU')))
	{
		print "\n".'<!-- Start top horizontal -->'."\n";

		print '<div class="side-nav-vert'.(GETPOST('dol_invisible_topmenu','int')?' hidden':'').'"><div id="id-top">';		// dol_invisible_topmenu differs from dol_hide_topmenu: dol_invisible_topmenu means we output menu but we make it invisible.

		// Show menu entries
		print '<div id="tmenu_tooltip'.(empty($conf->global->MAIN_MENU_INVERT)?'':'invert').'" class="tmenu">'."\n";
		$menumanager->atarget=$target;
		$menumanager->showmenu('top', array('searchform'=>$searchform, 'bookmarks'=>$bookmarks));      // This contains a \n
		print "</div>\n";

		// Define link to login card
		$appli=constant('DOL_APPLICATION_TITLE');
		if (! empty($conf->global->MAIN_APPLICATION_TITLE))
		{
			$appli=$conf->global->MAIN_APPLICATION_TITLE;
			if (preg_match('/\d\.\d/', $appli))
			{
				if (! preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli.=" (".DOL_VERSION.")";	// If new title contains a version that is different than core
			}
			else $appli.=" ".DOL_VERSION;
		}
		else $appli.=" ".DOL_VERSION;

		if (! empty($conf->global->MAIN_FEATURES_LEVEL)) $appli.="<br>".$langs->trans("LevelOfFeature").': '.$conf->global->MAIN_FEATURES_LEVEL;

		$logouttext='';
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			//$logouthtmltext=$appli.'<br>';
			if ($_SESSION["dol_authmode"] != 'forceuser' && $_SESSION["dol_authmode"] != 'http')
			{
				$logouthtmltext.=$langs->trans("Logout").'<br>';

				$logouttext .='<a href="'.DOL_URL_ROOT.'/user/logout.php">';
				//$logouttext .= img_picto($langs->trans('Logout').":".$langs->trans('Logout'), 'logout_top.png', 'class="login"', 0, 0, 1);
				$logouttext .='<span class="fa fa-sign-out atoplogin"></span>';
				$logouttext .='</a>';
			}
			else
			{
				$logouthtmltext.=$langs->trans("NoLogoutProcessWithAuthMode",$_SESSION["dol_authmode"]);
				$logouttext .= img_picto($langs->trans('Logout').":".$langs->trans('Logout'), 'logout_top.png', 'class="login"', 0, 0, 1);
			}
		}

		print '<div class="login_block">'."\n";

		// Add login user link
		$toprightmenu.='<div class="login_block_user">';

		// Login name with photo and tooltip
		$mode=-1;
		$toprightmenu.='<div class="inline-block nowrap"><div class="inline-block login_block_elem login_block_elem_name" style="padding: 0px;">';
		$toprightmenu.=$user->getNomUrl($mode, '', 1, 0, 11, 0, ($user->firstname ? 'firstname' : -1),'atoplogin');
		$toprightmenu.='</div></div>';

		$toprightmenu.='</div>'."\n";

		$toprightmenu.='<div class="login_block_other">';

		// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
		$parameters=array();
		$result=$hookmanager->executeHooks('printTopRightMenu',$parameters);    // Note that $action and $object may have been modified by some hooks
		if (is_numeric($result))
		{
			if ($result == 0)
				$toprightmenu.=$hookmanager->resPrint;		// add
			else
				$toprightmenu=$hookmanager->resPrint;						// replace
		}
		else
		{
			$toprightmenu.=$result;	// For backward compatibility
		}

		// Link to module builder
		if (! empty($conf->modulebuilder->enabled))
		{
			$text ='<a href="'.DOL_URL_ROOT.'/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="_modulebuilder">';
			//$text.= img_picto(":".$langs->trans("ModuleBuilder"), 'printer_top.png', 'class="printer"');
			$text.='<span class="fa fa-bug atoplogin"></span>';
			$text.='</a>';
			$toprightmenu.=@Form::textwithtooltip('',$langs->trans("ModuleBuilder"),2,1,$text,'login_block_elem',2);
		}

		// Link to print main content area
		if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && empty($conf->browser->phone))
		{
			$qs=dol_escape_htmltag($_SERVER["QUERY_STRING"]);

			if (is_array($_POST))
			{
				foreach($_POST as $key=>$value) {
					if ($key!=='action' && $key!=='password' && !is_array($value)) $qs.='&'.$key.'='.urlencode($value);
				}
			}
			$qs.=(($qs && $morequerystring)?'&':'').$morequerystring;
			$text ='<a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.$qs.($qs?'&':'').'optioncss=print" target="_blank">';
			//$text.= img_picto(":".$langs->trans("PrintContentArea"), 'printer_top.png', 'class="printer"');
			$text.='<span class="fa fa-print atoplogin"></span>';
			$text.='</a>';
			$toprightmenu.=@Form::textwithtooltip('',$langs->trans("PrintContentArea"),2,1,$text,'login_block_elem',2);
		}

		// Link to Dolibarr wiki pages
		if (empty($conf->global->MAIN_HELP_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			$langs->load("help");

			$helpbaseurl='';
			$helppage='';
			$mode='';

			if (empty($helppagename)) $helppagename='EN:User_documentation|FR:Documentation_utilisateur|ES:Documentación_usuarios';

			// Get helpbaseurl, helppage and mode from helppagename and langs
			$arrayres=getHelpParamFor($helppagename,$langs);
			$helpbaseurl=$arrayres['helpbaseurl'];
			$helppage=$arrayres['helppage'];
			$mode=$arrayres['mode'];

			// Link to help pages
			if ($helpbaseurl && $helppage)
			{
				$text='';
	            if(!empty($conf->global->MAIN_SHOWDATABASENAMEINHELPPAGESLINK)) {
                    $langs->load('admin');
                    $appli .= '<br>' . $langs->trans("Database") . ': ' . $db->database_name;
                }
				$title=$appli.'<br>';
				$title.=$langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage');
				if ($mode == 'wiki') $title.=' - '.$langs->trans("PageWiki").' &quot;'.dol_escape_htmltag(strtr($helppage,'_',' ')).'&quot;';
				$text.='<a class="help" target="_blank" rel="noopener" href="';
				if ($mode == 'wiki') $text.=sprintf($helpbaseurl,urlencode(html_entity_decode($helppage)));
				else $text.=sprintf($helpbaseurl,$helppage);
				$text.='">';
				//$text.=img_picto('', 'helpdoc_top').' ';
				$text.='<span class="fa fa-question-circle atoplogin"></span>';
				//$toprightmenu.=$langs->trans($mode == 'wiki' ? 'OnlineHelp': 'Help');
				//if ($mode == 'wiki') $text.=' ('.dol_trunc(strtr($helppage,'_',' '),8).')';
				$text.='</a>';
				//$toprightmenu.='</div>'."\n";
				$toprightmenu.=@Form::textwithtooltip('',$title,2,1,$text,'login_block_elem',2);
			}
		}

		// Logout link
		$toprightmenu.=@Form::textwithtooltip('',$logouthtmltext,2,1,$logouttext,'login_block_elem',2);

		$toprightmenu.='</div>';

		print $toprightmenu;

		print "</div>\n";		// end div class="login_block"

		print '</div></div>';

		print '<div style="clear: both;"></div>';
		print "<!-- End top horizontal menu -->\n\n";
	}

	if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) print '<!-- Begin div id-container --><div id="id-container" class="id-container'.($morecss?' '.$morecss:'').'">';
}


/**
 *  Show left menu bar
 *
 *  @param  array	$menu_array_before 	       	Table of menu entries to show before entries of menu handler. This param is deprectaed and must be provided to ''.
 *  @param  string	$helppagename    	       	Name of wiki page for help ('' by default).
 * 				     		                   	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 * 									         		       For other external page: http://server/url
 *  @param  string	$notused             		Deprecated. Used in past to add content into left menu. Hooks can be used now.
 *  @param  array	$menu_array_after           Table of menu entries to show after entries of menu handler
 *  @param  int		$leftmenuwithoutmainarea    Must be set to 1. 0 by default for backward compatibility with old modules.
 *  @param  string	$title                      Title of web page
 *  @param  string  $acceptdelayedhtml          1 if caller request to have html delayed content not returned but saved into global $delayedhtmlcontent (so caller can show it at end of page to avoid flash FOUC effect)
 *  @return	void
 */
function left_menu($menu_array_before, $helppagename='', $notused='', $menu_array_after='', $leftmenuwithoutmainarea=0, $title='', $acceptdelayedhtml=0)
{
	global $user, $conf, $langs, $db, $form;
	global $hookmanager, $menumanager;

	$searchform='';
	$bookmarks='';

	if (! empty($menu_array_before)) dol_syslog("Deprecated parameter menu_array_before was used when calling main::left_menu function. Menu entries of module should now be defined into module descriptor and not provided when calling left_menu.", LOG_WARNING);

	if (empty($conf->dol_hide_leftmenu) && (! defined('NOREQUIREMENU') || ! constant('NOREQUIREMENU')))
	{
		// Instantiate hooks of thirdparty module
		$hookmanager->initHooks(array('searchform','leftblock'));

		print "\n".'<!-- Begin side-nav id-left -->'."\n".'<div class="side-nav"><div id="id-left">'."\n";

		if ($conf->browser->layout == 'phone') $conf->global->MAIN_USE_OLD_SEARCH_FORM=1;	// Select into select2 is awfull on smartphone. TODO Is this still true with select2 v4 ?

		print "\n";

		if (! is_object($form)) $form=new Form($db);
		$selected=-1;
		$usedbyinclude=1;
		include_once DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php';	// This set $arrayresult

		if ($conf->use_javascript_ajax && empty($conf->global->MAIN_USE_OLD_SEARCH_FORM))
		{
			//$searchform.=$form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT.'/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);
			$searchform.=$form->selectArrayFilter('searchselectcombo', $arrayresult, $selected, '', 1, 0, (empty($conf->global->MAIN_SEARCHBOX_CONTENT_LOADED_BEFORE_KEY)?1:0), 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);
		}
		else
		{
			foreach($arrayresult as $key => $val)
			{
				//$searchform.=printSearchForm($val['url'], $val['url'], $val['label'], 'maxwidth100', 'sall', $val['shortcut'], 'searchleft', img_picto('',$val['img']));
				$searchform.=printSearchForm($val['url'], $val['url'], $val['label'], 'maxwidth125', 'sall', $val['shortcut'], 'searchleft', img_picto('', $val['img'], '', false, 1, 1));
			}
		}

		// Execute hook printSearchForm
		$parameters=array('searchform'=>$searchform);
		$reshook=$hookmanager->executeHooks('printSearchForm',$parameters);    // Note that $action and $object may have been modified by some hooks
		if (empty($reshook))
		{
			$searchform.=$hookmanager->resPrint;
		}
		else $searchform=$hookmanager->resPrint;

		// Force special value for $searchform
		if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) || empty($conf->use_javascript_ajax))
		{
			$urltosearch=DOL_URL_ROOT.'/core/search_page.php?showtitlebefore=1';
			$searchform='<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="'.$urltosearch.'" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div></div>';
		}
		elseif ($conf->use_javascript_ajax && ! empty($conf->global->MAIN_USE_OLD_SEARCH_FORM))
		{
			$searchform='<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="#" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div><div id="divsearchforms2" style="display: none">'.$searchform.'</div>';
			$searchform.='<script type="text/javascript">
            	jQuery(document).ready(function () {
            		jQuery("#divsearchforms1").click(function(){
	                   jQuery("#divsearchforms2").toggle();
	               });
            	});
                </script>' . "\n";
			$searchform.='</div>';
		}

		// Define $bookmarks
		if (! empty($conf->bookmark->enabled) && $user->rights->bookmark->lire)
		{
			include_once (DOL_DOCUMENT_ROOT.'/bookmarks/bookmarks.lib.php');
			$langs->load("bookmarks");

			$bookmarks=printBookmarksList($db, $langs);
		}

		// Left column
		print '<!-- Begin left menu -->'."\n";

		print '<div class="vmenu"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)?'':' title="Left menu"').'>'."\n\n";

		// Show left menu with other forms
		$menumanager->menu_array = $menu_array_before;
		$menumanager->menu_array_after = $menu_array_after;
		$menumanager->showmenu('left', array('searchform'=>$searchform, 'bookmarks'=>$bookmarks)); // output menu_array and menu found in database

		// Dolibarr version + help + bug report link
		print "\n";
		print "<!-- Begin Help Block-->\n";
		print '<div id="blockvmenuhelp" class="blockvmenuhelp">'."\n";

		// Version
		if (empty($conf->global->MAIN_HIDE_VERSION))    // Version is already on help picto and on login page.
		{
			$doliurl='https://www.dolibarr.org';
			//local communities
			if (preg_match('/fr/i',$langs->defaultlang)) $doliurl='https://www.dolibarr.fr';
			if (preg_match('/es/i',$langs->defaultlang)) $doliurl='https://www.dolibarr.es';
			if (preg_match('/de/i',$langs->defaultlang)) $doliurl='https://www.dolibarr.de';
			if (preg_match('/it/i',$langs->defaultlang)) $doliurl='https://www.dolibarr.it';
			if (preg_match('/gr/i',$langs->defaultlang)) $doliurl='https://www.dolibarr.gr';

			$appli=constant('DOL_APPLICATION_TITLE');
			if (! empty($conf->global->MAIN_APPLICATION_TITLE))
			{
				$appli=$conf->global->MAIN_APPLICATION_TITLE; $doliurl='';
				if (preg_match('/\d\.\d/', $appli))
				{
					if (! preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli.=" (".DOL_VERSION.")";	// If new title contains a version that is different than core
				}
				else $appli.=" ".DOL_VERSION;
			}
			else $appli.=" ".DOL_VERSION;
			print '<div id="blockvmenuhelpapp" class="blockvmenuhelp">';
			if ($doliurl) print '<a class="help" target="_blank" rel="noopener" href="'.$doliurl.'">';
			else print '<span class="help">';
			print $appli;
			if ($doliurl) print '</a>';
			else print '</span>';
			print '</div>'."\n";
		}

		// Link to bugtrack
		if (! empty($conf->global->MAIN_BUGTRACK_ENABLELINK))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

			$bugbaseurl = 'https://github.com/Dolibarr/dolibarr/issues/new';
			$bugbaseurl.= '?title=';
			$bugbaseurl.= urlencode("Bug: ");
			$bugbaseurl.= '&body=';
			// TODO use .github/ISSUE_TEMPLATE.md to generate?
			$bugbaseurl .= urlencode("# Bug\n");
			$bugbaseurl .= urlencode("\n");
			$bugbaseurl.= urlencode("## Environment\n");
			$bugbaseurl.= urlencode("- **Version**: " . DOL_VERSION . "\n");
			$bugbaseurl.= urlencode("- **OS**: " . php_uname('s') . "\n");
			$bugbaseurl.= urlencode("- **Web server**: " . $_SERVER["SERVER_SOFTWARE"] . "\n");
			$bugbaseurl.= urlencode("- **PHP**: " . php_sapi_name() . ' ' . phpversion() . "\n");
			$bugbaseurl.= urlencode("- **Database**: " . $db::LABEL . ' ' . $db->getVersion() . "\n");
			$bugbaseurl.= urlencode("- **URL**: " . $_SERVER["REQUEST_URI"] . "\n");
			$bugbaseurl.= urlencode("\n");
			$bugbaseurl.= urlencode("## Report\n");
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
		$parameters=array();
		$reshook=$hookmanager->executeHooks('printLeftBlock',$parameters);    // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint;

		print '</div></div> <!-- End side-nav id-left -->';	// End div id="side-nav" div id="id-left"
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
function main_area($title='')
{
	global $conf, $langs;

	if (empty($conf->dol_hide_leftmenu)) print '<div id="id-right">';

	print "\n";

	print '<!-- Begin div class="fiche" -->'."\n".'<div class="fiche">'."\n";

	if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED)) print info_admin($langs->trans("WarningYouAreInMaintenanceMode",$conf->global->MAIN_ONLY_LOGIN_ALLOWED));
}


/**
 *  Return helpbaseurl, helppage and mode
 *
 *  @param	string		$helppagename		Page name ('EN:xxx,ES:eee,FR:fff...' or 'http://localpage')
 *  @param  Translate	$langs				Language
 *  @return	array		Array of help urls
 */
function getHelpParamFor($helppagename,$langs)
{
	$helpbaseurl='';
	$helppage='';
	$mode='';

	if (preg_match('/^http/i',$helppagename))
	{
		// If complete URL
		$helpbaseurl='%s';
		$helppage=$helppagename;
		$mode='local';
	}
	else
	{
		// If WIKI URL
		if (preg_match('/^es/i',$langs->defaultlang))
		{
			$helpbaseurl='http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/ES:([^|]+)/i',$helppagename,$reg)) $helppage=$reg[1];
		}
		if (preg_match('/^fr/i',$langs->defaultlang))
		{
			$helpbaseurl='http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/FR:([^|]+)/i',$helppagename,$reg)) $helppage=$reg[1];
		}
		if (empty($helppage))	// If help page not already found
		{
			$helpbaseurl='http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/EN:([^|]+)/i',$helppagename,$reg)) $helppage=$reg[1];
		}
		$mode='wiki';
	}
	return array('helpbaseurl'=>$helpbaseurl,'helppage'=>$helppage,'mode'=>$mode);
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
 *  @return	string
 */
function printSearchForm($urlaction, $urlobject, $title, $htmlmorecss, $htmlinputname, $accesskey='', $prefhtmlinputname='',$img='', $showtitlebefore=0)
{
	global $conf,$langs,$user;

	$ret='';
	$ret.='<form action="'.$urlaction.'" method="post" class="searchform">';
	$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	$ret.='<input type="hidden" name="mode" value="search">';
	$ret.='<input type="hidden" name="savelogin" value="'.dol_escape_htmltag($user->login).'">';
	if ($showtitlebefore) $ret.=$title.' ';
	$ret.='<input type="text" class="flat '.$htmlmorecss.'"';
	$ret.=' style="text-indent: 22px; background-image: url(\''.$img.'\'); background-repeat: no-repeat; background-position: 3px;"';
	$ret.=($accesskey?' accesskey="'.$accesskey.'"':'');
	$ret.=' placeholder="'.strip_tags($title).'"';
	$ret.=' name="'.$htmlinputname.'" id="'.$prefhtmlinputname.$htmlinputname.'" />';
	//$ret.='<input type="submit" class="button" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px" value="'.$langs->trans("Go").'">';
	$ret.='<button type="submit" class="button" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px">';
	$ret.='<span class="fa fa-search"></span>';
	$ret.='</button>';
	$ret.="</form>\n";
	return $ret;
}


if (! function_exists("llxFooter"))
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
	function llxFooter($comment='',$zone='private', $disabledoutputofmessages=0)
	{
		global $conf, $langs, $user, $object;
		global $delayedhtmlcontent;
		global $contextpage, $page, $limit;

		$ext='layout='.$conf->browser->layout.'&version='.urlencode(DOL_VERSION);

		// Global html output events ($mesgs, $errors, $warnings)
		dol_htmloutput_events($disabledoutputofmessages);

		// Code for search criteria persistence.
		// $user->lastsearch_values was set by the GETPOST when form field search_xxx exists
		if (is_object($user) && ! empty($user->lastsearch_values_tmp) && is_array($user->lastsearch_values_tmp))
		{
			// Clean and save data
			foreach($user->lastsearch_values_tmp as $key => $val)
			{
				unset($_SESSION['lastsearch_values_tmp_'.$key]);			// Clean array to rebuild it just after
				if (count($val) && empty($_POST['button_removefilter']))	// If there is search criteria to save and we did not click on 'Clear filter' button
				{
					if (empty($val['sortfield'])) unset($val['sortfield']);
					if (empty($val['sortorder'])) unset($val['sortorder']);
					dol_syslog('Save lastsearch_values_tmp_'.$key.'='.json_encode($val, 0)." (systematic recording of last search criterias)");
					$_SESSION['lastsearch_values_tmp_'.$key]=json_encode($val);
					unset($_SESSION['lastsearch_values_'.$key]);
				}
			}
		}


		$relativepathstring = $_SERVER["PHP_SELF"];
		// Clean $relativepathstring
		if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'),'/').'/', '', $relativepathstring);
		$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
		$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
		if (preg_match('/list\.php$/', $relativepathstring))
		{
			unset($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_page_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]);

			if (! empty($contextpage))                     $_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]=$contextpage;
			if (! empty($page) && $page > 1)               $_SESSION['lastsearch_page_tmp_'.$relativepathstring]=$page;
			if (! empty($limit) && $limit != $conf->limit) $_SESSION['lastsearch_limit_tmp_'.$relativepathstring]=$limit;

			unset($_SESSION['lastsearch_contextpage_'.$relativepathstring]);
			unset($_SESSION['lastsearch_page_'.$relativepathstring]);
			unset($_SESSION['lastsearch_limit_'.$relativepathstring]);
		}

		// Core error message
		if (! empty($conf->global->MAIN_CORE_ERROR))
		{
			// Ajax version
			if ($conf->use_javascript_ajax)
			{
				$title = img_warning().' '.$langs->trans('CoreErrorTitle');
				print ajax_dialog($title, $langs->trans('CoreErrorMessage'));
			}
			// html version
			else
			{
				$msg = img_warning().' '.$langs->trans('CoreErrorMessage');
				print '<div class="error">'.$msg.'</div>';
			}

			//define("MAIN_CORE_ERROR",0);      // Constant was defined and we can't change value of a constant
		}

		print "\n\n";

		print '</div> <!-- End div class="fiche" -->'."\n"; // End div fiche

		if (empty($conf->dol_hide_leftmenu)) print '</div> <!-- End div id-right -->'."\n"; // End div id-right

		if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) print '</div> <!-- End div id-container -->'."\n";	// End div container

		print "\n";
		if ($comment) print '<!-- '.$comment.' -->'."\n";

		printCommonFooter($zone);

		if (! empty($delayedhtmlcontent)) print $delayedhtmlcontent;

		if (! empty($conf->use_javascript_ajax))
		{
			print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext?'&'.$ext:'').'"></script>'."\n";
		}

		// Wrapper to add log when clicking on download or preview
		if (! empty($conf->blockedlog->enabled) && is_object($object) && $object->id > 0 && $object->statut > 0)
		{
			if (in_array($object->element, array('facture')))       // Restrict for the moment to element 'facture'
			{
				print "\n<!-- JS CODE TO ENABLE log when making a download or a preview of a document -->\n";
				?>
    			<script type="text/javascript">
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

		print "</body>\n";
		print "</html>\n";
	}
}

