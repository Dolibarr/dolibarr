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
 *	\brief      File that defines environment for Dolibarr pages only (variables not required by scripts)
 */

//@ini_set('memory_limit', '64M');	// This may be useless if memory is hard limited by your PHP

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
if (function_exists('get_magic_quotes_gpc'))	// magic_quotes_* removed in PHP6
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
 * @param		string		$type		1=GET, 0=POST, 2=PHP_SELF
 * @return		int						>0 if there is an injection
 */
function test_sql_and_script_inject($val, $type)
{
    $sql_inj = 0;
    // For SQL Injection (only GET and POST are used to be included into bad escaped SQL requests)
    if ($type != 2)
    {
        $sql_inj += preg_match('/delete\s+from/i',	 $val);
        $sql_inj += preg_match('/create\s+table/i',	 $val);
        $sql_inj += preg_match('/update.+set.+=/i',  $val);
        $sql_inj += preg_match('/insert\s+into/i', 	 $val);
        $sql_inj += preg_match('/select.+from/i', 	 $val);
        $sql_inj += preg_match('/union.+select/i', 	 $val);
        $sql_inj += preg_match('/into\s+(outfile|dumpfile)/i',  $val);
        $sql_inj += preg_match('/(\.\.%2f)+/i',		 $val);
    }
    // For XSS Injection done by adding javascript with script
    // This is all cases a browser consider text is javascript:
    // When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
    // All examples on page: http://ha.ckers.org/xss.html#XSScalc
    $sql_inj += preg_match('/<script/i', $val);
    if (! defined('NOSTYLECHECK')) $sql_inj += preg_match('/<style/i', $val);
    $sql_inj += preg_match('/base[\s]+href/si', $val);
    $sql_inj += preg_match('/<.*onmouse/si', $val);       // onmousexxx can be set on img or any html tag like <img title='>' onmouseover=alert(1)>
    $sql_inj += preg_match('/onerror\s*=/i', $val);       // onerror can be set on img or any html tag like <img title='>' onerror = alert(1)>
    if ($type == 1)
    {
        $sql_inj += preg_match('/javascript:/i', $val);
        $sql_inj += preg_match('/vbscript:/i', $val);
    }
    // For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
    if ($type == 1) $sql_inj += preg_match('/"/i', $val);		// We refused " in GET parameters value
    if ($type == 2) $sql_inj += preg_match('/[;"]/', $val);		// PHP_SELF is a file system path. It can contains spaces.
    return $sql_inj;
}

/**
 * Return true if security check on parameters are OK, false otherwise.
 *
 * @param		string			$var		Variable name
 * @param		string			$type		1=GET, 0=POST, 2=PHP_SELF
 * @return		boolean||null				true if there is an injection. Stop code if injection found.
 */
function analyseVarsForSqlAndScriptsInjection(&$var, $type)
{
    if (is_array($var))
    {
        foreach ($var as $key => $value)
        {
            if (analyseVarsForSqlAndScriptsInjection($value,$type))
            {
                $var[$key] = $value;
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
        return (test_sql_and_script_inject($var,$type) <= 0);
    }
}


// Check consistency of NOREQUIREXXX DEFINES
if ((defined('NOREQUIREDB') || defined('NOREQUIRETRAN')) && ! defined('NOREQUIREMENU')) dol_print_error('','If define NOREQUIREDB or NOREQUIRETRAN are set, you must also set NOREQUIREMENU or not use them');

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

// If there is a POST parameter to tell to save automatically some POST parameters into a cookies, we do it
if (! empty($_POST["DOL_AUTOSET_COOKIE"]))
{
	$tmpautoset=explode(':',$_POST["DOL_AUTOSET_COOKIE"],2);
	$tmplist=explode(',',$tmpautoset[1]);
	$cookiearrayvalue='';
	foreach ($tmplist as $tmpkey)
	{
		$postkey=$tmpautoset[0].'_'.$tmpkey;
		//var_dump('tmpkey='.$tmpkey.' postkey='.$postkey.' value='.$_POST[$postkey]);
		if (! empty($_POST[$postkey])) $cookiearrayvalue[$tmpkey]=$_POST[$postkey];
	}
	$cookiename=$tmpautoset[0];
	$cookievalue=json_encode($cookiearrayvalue);
	//var_dump('setcookie cookiename='.$cookiename.' cookievalue='.$cookievalue);
	setcookie($cookiename, empty($cookievalue)?'':$cookievalue, empty($cookievalue)?0:(time()+(86400*354)), '/');	// keep cookie 1 year
	if (empty($cookievalue)) unset($_COOKIE[$cookiename]);
}

// Init session. Name of session is specific to Dolibarr instance.
$prefix=dol_getprefix();
$sessionname='DOLSESSID_'.$prefix;
$sessiontimeout='DOLSESSTIMEOUT_'.$prefix;
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();
if (ini_get('register_globals'))    // To solve bug in using $_SESSION
{
    foreach ($_SESSION as $key=>$value)
    {
        if (isset($GLOBALS[$key])) unset($GLOBALS[$key]);
    }
}

// Init the 5 global objects
// This include will make the new and set properties for: $conf, $db, $langs, $user, $mysoc objects
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
    $token = dol_hash(uniqid(mt_rand(),TRUE)); // Generates a hash of a random number
    // roulement des jetons car cree a chaque appel
    if (isset($_SESSION['newtoken'])) $_SESSION['token'] = $_SESSION['newtoken'];
    $_SESSION['newtoken'] = $token;
}
if (! defined('NOCSRFCHECK') && empty($dolibarr_nocsrfcheck) && ! empty($conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN))	// Check validity of token, only if option enabled (this option breaks some features sometimes)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        if (GETPOST('token') != $_SESSION['token'])
        {
            dol_syslog("Invalid token in ".$_SERVER['HTTP_REFERER'].", action=".GETPOST('action').", _POST['token']=".GETPOST('token').", _SESSION['token']=".$_SESSION['token'], LOG_WARNING);
            //print 'Unset POST by CSRF protection in main.inc.php.';	// Do not output anything because this create problems when using the BACK button on browsers.
            unset($_POST);
        }
    }
}

// Disable modules (this must be after session_start and after conf has been loaded)
if (GETPOST('disablemodules'))  $_SESSION["disablemodules"]=GETPOST('disablemodules');
if (! empty($_SESSION["disablemodules"]))
{
    $disabled_modules=explode(',',$_SESSION["disablemodules"]);
    foreach($disabled_modules as $module)
    {
        if ($module)
        {
        	if (empty($conf->$module)) $conf->$module=new stdClass();
        	$conf->$module->enabled=false;
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
    // Example: 'http', 'dolibarr', 'ldap', 'http,forceuser'

    // Authentication mode
    if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication='http,dolibarr';
    // Authentication mode: forceuser
    if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user='auto';
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
                dol_syslog("Call index page from another url than demo page");
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
            $ok=(array_key_exists($sessionkey, $_SESSION) === TRUE && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));

            // Check code
            if (! $ok)
            {
                dol_syslog('Bad value for code, connexion refused');
                $langs->load('main');
                $langs->load('errors');

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

        $usertotest		= (! empty($_COOKIE['login_dolibarr']) ? $_COOKIE['login_dolibarr'] : GETPOST("username","alpha",2));
        $passwordtotest	= GETPOST('password','',2);
        $entitytotest	= (GETPOST('entity','int') ? GETPOST('entity','int') : (!empty($conf->entity) ? $conf->entity : 1));

        // Validation of login/pass/entity
        // If ok, the variable login will be returned
        // If error, we will put error message in session under the name dol_loginmesg
        $goontestloop=false;
        if (isset($_SERVER["REMOTE_USER"]) && in_array('http',$authmode)) $goontestloop=true;
        if ($dolibarr_main_authentication == 'forceuser' && ! empty($dolibarr_auto_user)) $goontestloop=true;
        if (GETPOST("username","alpha",2) || ! empty($_COOKIE['login_dolibarr']) || GETPOST('openid_mode','alpha',1)) $goontestloop=true;

        if (! is_object($langs)) // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
        {
            include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
            $langs=new Translate("",$conf);
    		$langcode=(GETPOST('lang')?GETPOST('lang','alpha',1):(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT));
        	$langs->setDefaultLang($langcode);
        }

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
                $langs->load('main');
                $langs->load('errors');

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
            // We show login page
			dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]." showing the login form and exit");
        	dol_loginfunction($langs,$conf,(! empty($mysoc)?$mysoc:''));
            exit;
        }

        $resultFetchUser=$user->fetch('', $login, '', 1, ($entitytotest ? $entitytotest : -1));
        if ($resultFetchUser <= 0)
        {
            dol_syslog('User not found, connexion refused');
            session_destroy();
            session_name($sessionname);
            session_start();    // Fixing the bug of register_globals here is useless since session is empty

            if ($resultFetchUser == 0)
            {
                $langs->load('main');
                $langs->load('errors');

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
	        if (GETPOST('textbrowser')) $paramsurl[]='textbrowser='.GETPOST('textbrowser','int');
	        if (GETPOST('nojs')) $paramsurl[]='nojs='.GETPOST('nojs','int');
	        if (GETPOST('lang')) $paramsurl[]='lang='.GETPOST('lang','alpha');
            header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl)?'?'.implode('&',$paramsurl):''));
            exit;
        }
    }
    else
    {
        // We are already into an authenticated session
        $login=$_SESSION["dol_login"];
        $entity=$_SESSION["dol_entity"];
        dol_syslog("This is an already logged session. _SESSION['dol_login']=".$login." _SESSION['dol_entity']=".$entity, LOG_DEBUG);

        $resultFetchUser=$user->fetch('',$login,'',1,($entity > 0 ? $entity : -1));
        if ($resultFetchUser <= 0)
        {
            // Account has been removed after login
            dol_syslog("Can't load user even if session logged. _SESSION['dol_login']=".$login, LOG_WARNING);
            session_destroy();
            session_name($sessionname);
            session_start();    // Fixing the bug of register_globals here is useless since session is empty

            if ($resultFetchUser == 0)
            {
                $langs->load('main');
                $langs->load('errors');

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
	        if (GETPOST('textbrowser')) $paramsurl[]='textbrowser='.GETPOST('textbrowser','int');
	        if (GETPOST('nojs')) $paramsurl[]='nojs='.GETPOST('nojs','int');
	        if (GETPOST('lang')) $paramsurl[]='lang='.GETPOST('lang','alpha');
            header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl)?'?'.implode('&',$paramsurl):''));
            exit;
        }
        else
		{
	       // Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
	       $hookmanager->initHooks(array('main'));

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
     * Overwrite configs global by personal configs
     */

    // Set liste_limit
    if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT))	$conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT;		// Can be 0
    if (isset($user->conf->PRODUIT_LIMIT_SIZE))	$conf->product->limit_size = $user->conf->PRODUIT_LIMIT_SIZE;	// Can be 0

    // Replace conf->css by personalized value if theme not forced
    if (empty($conf->global->MAIN_FORCETHEME) && ! empty($user->conf->MAIN_THEME))
    {
        $conf->theme=$user->conf->MAIN_THEME;
        $conf->css  = "/theme/".$conf->theme."/style.css.php";
    }
}

// Case forcing style from url
if (GETPOST('theme'))
{
	$conf->theme=GETPOST('theme','alpha',1);
	$conf->css  = "/theme/".$conf->theme."/style.css.php";
}


// Set javascript option
if (! GETPOST('nojs'))   // If javascript was not disabled on URL
{
	if (! empty($user->conf->MAIN_DISABLE_JAVASCRIPT))
	{
		$conf->use_javascript_ajax=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
	}
}
else $conf->use_javascript_ajax=0;
// Set MAIN_OPTIMIZEFORTEXTBROWSER
if (GETPOST('textbrowser') || (! empty($conf->browser->name) && $conf->browser->name == 'lynxlinks') || ! empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER))   // If we must enable text browser
{
    $conf->global->MAIN_OPTIMIZEFORTEXTBROWSER=1;
}

// Set terminal output option according to conf->browser.
if (GETPOST('dol_hide_leftmenu') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;
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
    if (! GETPOST('lang'))	// If language was not forced on URL
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


dol_syslog("--- Access to ".$_SERVER["PHP_SELF"]);
//Another call for easy debugg
//dol_syslog("Access to ".$_SERVER["PHP_SELF"].' GET='.join(',',array_keys($_GET)).'->'.join(',',$_GET).' POST:'.join(',',array_keys($_POST)).'->'.join(',',$_POST));

// Load main languages files
if (! defined('NOREQUIRETRAN'))
{
    $langs->load("main");
    $langs->load("dict");
}

// Define some constants used for style of arrays
$bc=array(0=>'class="impair"',1=>'class="pair"');
$bcdd=array(0=>'class="impair drag drop"',1=>'class="pair drag drop"');
$bcnd=array(0=>'class="impair nodrag nodrop nohover"',1=>'class="pair nodrag nodrop nohoverpair"');		// Used for tr to add new lines
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
	if (GETPOST('menu')) $file_menu=GETPOST('menu');     // example: menu=eldy_menu.php
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
     * @return	void
     */
	function llxHeader($head='', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='')
	{
	    global $conf;

	    // html header
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

		// top menu and left menu area
		if (empty($conf->dol_hide_topmenu))
		{
			top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $help_url);
		}
		if (empty($conf->dol_hide_leftmenu))
		{
			left_menu('', $help_url, '', '', 1, $title, 1);
		}

		// main area
		main_area($title);
	}
}


/**
 *  Show HTTP header
 *
 *  @return	void
 */
function top_httphead()
{
    global $conf;

    //header("Content-type: text/html; charset=UTF-8");
    header("Content-type: text/html; charset=".$conf->file->character_set_client);

    // On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
    if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) {
        ob_start("ob_gzhandler");
    }
}

/**
 * Ouput html header of a page.
 * This code is also duplicated into security2.lib.php::dol_loginfunction
 *
 * @param 	string 	$head			Optionnal head lines
 * @param 	string 	$title			HTML title
 * @param 	int    	$disablejs		Disable js output
 * @param 	int    	$disablehead	Disable head output
 * @param 	array  	$arrayofjs		Array of complementary js files
 * @param 	array  	$arrayofcss		Array of complementary css files
 * @param 	int    	$disablejmobile	Disable jmobile
 * @param   int     $disablenofollow Disable no follow tag
 * @return	void
 */
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $disablejmobile=0, $disablenofollow=0)
{
    global $user, $conf, $langs, $db;

    top_httphead();

    if (empty($conf->css)) $conf->css = '/theme/eldy/style.css.php';	// If not defined, eldy by default

    if (! empty($conf->global->MAIN_ACTIVATE_HTML4)) {
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    }else {
        $doctype = '<!doctype html>';
    }
    print $doctype."\n";
    if (! empty($conf->global->MAIN_USE_CACHE_MANIFEST)) print '<html lang="'.substr($langs->defaultlang,0,2).'" manifest="'.DOL_URL_ROOT.'/cache.manifest">'."\n";
    else print '<html lang="'.substr($langs->defaultlang,0,2).'">'."\n";
    //print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">'."\n";
    if (empty($disablehead))
    {
        print "<head>\n";
		if (GETPOST('dol_basehref')) print '<base href="'.dol_escape_htmltag(GETPOST('dol_basehref')).'">'."\n";
        // Displays meta
        print '<meta name="robots" content="noindex'.($disablenofollow?'':',nofollow').'">'."\n";      				// Do not index
        print '<meta name="viewport" content="width=device-width, initial-scale=1.0">';	// Scale for mobile device
        print '<meta name="author" content="Dolibarr Development Team">'."\n";
		$favicon=dol_buildpath('/theme/'.$conf->theme.'/img/favicon.ico',1);
        if (! empty($conf->global->MAIN_FAVICON_URL)) $favicon=$conf->global->MAIN_FAVICON_URL;
        print '<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>'."\n";
        //if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && ! GETPOST('textbrowser')) print '<link rel="top" title="'.$langs->trans("Home").'" href="'.(DOL_URL_ROOT?DOL_URL_ROOT:'/').'">'."\n";
        if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && ! GETPOST('textbrowser')) print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
        if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && ! GETPOST('textbrowser')) print '<link rel="author" title="Dolibarr Development Team" href="https://www.dolibarr.org">'."\n";

        // Displays title
        $appli=constant('DOL_APPLICATION_TITLE');
        if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

        if ($title && ! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/noapp/',$conf->global->MAIN_HTML_TITLE)) print '<title>'.dol_htmlentities($title).'</title>';
        else if ($title) print '<title>'.dol_htmlentities($appli.' - '.$title).'</title>';
        else print "<title>".dol_htmlentities($appli)."</title>";
        print "\n";

        //$ext='';
        //if (! empty($conf->dol_use_jmobile)) $ext='version='.urlencode(DOL_VERSION);
        $ext='version='.urlencode(DOL_VERSION);
        if (GETPOST('version')) $ext='version='.GETPOST('version','int');	// usefull to force no cache on css/js
        if (GETPOST('testmenuhider') || ! empty($conf->global->MAIN_TESTMENUHIDER)) $ext='testmenuhider='.GETPOST('testmenuhider','int');
        
        $themeparam='?lang='.$langs->defaultlang.'&amp;theme='.$conf->theme.(GETPOST('optioncss')?'&amp;optioncss='.GETPOST('optioncss','alpha',1):'').'&amp;userid='.$user->id.'&amp;entity='.$conf->entity;
        $themeparam.=($ext?'&amp;'.$ext:'');
        if (! empty($_SESSION['dol_resetcache'])) $themeparam.='&amp;dol_resetcache='.$_SESSION['dol_resetcache'];
        if (GETPOST('dol_hide_topmenu'))           { $themeparam.='&amp;dol_hide_topmenu='.GETPOST('dol_hide_topmenu','int'); }
        if (GETPOST('dol_hide_leftmenu'))          { $themeparam.='&amp;dol_hide_leftmenu='.GETPOST('dol_hide_leftmenu','int'); }
        if (GETPOST('dol_optimize_smallscreen'))   { $themeparam.='&amp;dol_optimize_smallscreen='.GETPOST('dol_optimize_smallscreen','int'); }
        if (GETPOST('dol_no_mouse_hover'))         { $themeparam.='&amp;dol_no_mouse_hover='.GETPOST('dol_no_mouse_hover','int'); }
        if (GETPOST('dol_use_jmobile'))            { $themeparam.='&amp;dol_use_jmobile='.GETPOST('dol_use_jmobile','int'); $conf->dol_use_jmobile=GETPOST('dol_use_jmobile','int'); }
        
        if (! defined('DISABLE_JQUERY') && ! $disablejs && $conf->use_javascript_ajax)
        {
            print '<!-- Includes CSS for JQuery (Ajax library) -->'."\n";
            $jquerytheme = 'smoothness';
            if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
            if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css'.($ext?'?'.$ext:'').'">'."\n";  // JQuery
            else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui.css'.($ext?'?'.$ext:'').'">'."\n";    // JQuery
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/tiptip/tipTip.css'.($ext?'?'.$ext:'').'">'."\n";                           // Tooltip
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css'.($ext?'?'.$ext:'').'">'."\n";          // JNotify
            /* Removed a old hidden problematic feature never used in Dolibarr. If an external module need datatable, the module must provide all lib it needs and manage version problems with other dolibarr components
            if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES) || (defined('REQUIRE_JQUERY_DATATABLES') && constant('REQUIRE_JQUERY_DATATABLES')))     // jQuery datatables
            {
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/media/css/jquery.dataTables.min.css'.($ext?'?'.$ext:'').'">'."\n";
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/Buttons/css/buttons.dataTables.min.css'.($ext?'?'.$ext:'').'">'."\n";
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/ColReorder/css/colReorder.dataTables.min.css'.($ext?'?'.$ext:'').'"></script>'."\n";
            }*/
            if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))     // jQuery plugin "mutiselect", "multiple-select", "select2"...
            {
            	$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
            	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/'.$tmpplugin.'.css'.($ext?'?'.$ext:'').'">'."\n";
            }
            // jQuery Timepicker
            if (! empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER'))
            {
            	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.css'.($ext?'?'.$ext:'').'">'."\n";
            }
            // jQuery jMobile
            if (! $disablejmobile && (! empty($conf->global->MAIN_USE_JQUERY_JMOBILE) || defined('REQUIRE_JQUERY_JMOBILE') || ! empty($conf->dol_use_jmobile)))
            {
                $tmp=basename($_SERVER["PHP_SELF"]);
                if ($tmp == 'get_menudiv.php' || $conf->dol_use_jmobile == 4)
                {
            	   print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/mobile/jquery.mobile-latest.min.css'.($ext?'?'.$ext:'').'">'."\n";
                }
            }
        }

        print '<!-- Includes CSS for Dolibarr theme -->'."\n";
        // Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
        //$themepath=dol_buildpath((empty($conf->global->MAIN_FORCETHEMEDIR)?'':$conf->global->MAIN_FORCETHEMEDIR).$conf->css,1);
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
	    if (! empty($conf->global->MAIN_FIX_FLASH_ON_CHROME)) print '<!-- Includes CSS that does not exists as workaround of flash bug of chrome -->'."\n".'<link rel="stylesheet" type="text/css" href="filethatdoesnotexiststosolvechromeflashbug">'."\n";

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
            if (constant('JS_JQUERY')) print '<script type="text/javascript" src="'.JS_JQUERY.'jquery.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            if (constant('JS_JQUERY_UI')) print '<script type="text/javascript" src="'.JS_JQUERY_UI.'jquery-ui.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd.0.6.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tiptip/jquery.tipTip.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            // jQuery Layout (still used by ECM module)
            if (defined('REQUIRE_JQUERY_LAYOUT'))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/layout/jquery.layout.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            }
            // jQuery jnotify
            if (empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && ! defined('DISABLE_JQUERY_JNOTIFY'))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/jnotify.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            }
            // jQuery blockUI
            if (! empty($conf->global->MAIN_USE_JQUERY_BLOCKUI) || defined('REQUIRE_JQUERY_BLOCKUI'))
            {
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/blockUI/jquery.blockUI.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            	print '<script type="text/javascript">'."\n";
            	print 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working2.gif".'\';'."\n";
            	print '</script>'."\n";
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/blockUI.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            }
            // Flot
            if (empty($conf->global->MAIN_DISABLE_JQUERY_FLOT))
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
            if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE))
            {
            	print '<!-- JS to manage editInPlace feature -->'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
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
            // jQuery File Upload
            /*
            if (! empty($conf->global->MAIN_USE_JQUERY_FILEUPLOAD) || (defined('REQUIRE_JQUERY_FILEUPLOAD') && constant('REQUIRE_JQUERY_FILEUPLOAD')))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/template/tmpl.min'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.iframe-transport'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload-fp'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload-ui'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload-jui'.$ext.'"></script>'."\n";
                print '<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->'."\n";
                print '<!--[if gte IE 8]><script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/cors/jquery.xdr-transport'.$ext.'"></script><![endif]-->'."\n";
            }*/
            // jQuery DataTables
            /* Removed a old hidden problematic feature never used in Dolibarr. If an external module need datatable, the module must provide all lib it needs and manage version problems with other dolibarr components
            if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES) || (defined('REQUIRE_JQUERY_DATATABLES') && constant('REQUIRE_JQUERY_DATATABLES')))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/media/js/jquery.dataTables.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/Buttons/js/dataTables.buttons.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.colVis.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.html5.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.flash.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.print.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extensions/ColReorder/js/dataTables.colReorder.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jszip/jszip.min.js"></script>'."\n";
            }*/
            // jQuery Timepicker
            if (! empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER'))
            {
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js'.($ext?'?'.$ext:'').'"></script>'."\n";
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/timepicker.js.php?lang='.$langs->defaultlang.($ext?'&amp;'.$ext:'').'"></script>'."\n";
            }
            if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))     // jQuery plugin "mutiselect", "multiple-select", "select2", ...
            {
            	$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/'.$tmpplugin.'.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/select2_locale.js.php?lang='.$langs->defaultlang.($ext?'&amp;'.$ext:'').'"></script>'."\n";
            }
            // jQuery jMobile
            if (! $disablejmobile && (! empty($conf->global->MAIN_USE_JQUERY_JMOBILE) || defined('REQUIRE_JQUERY_JMOBILE') || (! empty($conf->dol_use_jmobile) && $conf->dol_use_jmobile > 0)))
            {
            	// We must force not using ajax because cache of jquery does not load js of other pages.
            	// This also increase seriously speed onto mobile device where complex js code is very slow and memory very low.
            	// Note: dol_use_jmobile=1 use jmobile without ajax, dol_use_jmobile=2 use jmobile with ajax
            	// 3=???
            	// 4=all pages, not only menu
                $tmp=basename($_SERVER["PHP_SELF"]);
                if ($tmp == 'get_menudiv.php' || $conf->dol_use_jmobile == 4)
                {
                    if (empty($conf->dol_use_jmobile) || ($conf->dol_use_jmobile != 2 && $conf->dol_use_jmobile != 3))
                	{
                		print '<script type="text/javascript">
    	            		$(document).bind("mobileinit", function() {
                		    ';
                		if ($conf->theme == 'md')
                		{
                   		    // Disabled decoration for some css
                    		print '
                    		    $.mobile.keepNative = \'input[type="submit"]\';
                    		   ';
                		}
                        print '
                   				$.extend(  $.mobile , {
                   					autoInitializePage : true,
                   					touchOverflowEnabled : true,
                   					defaultPageTransition : \'none\',
                   					defaultDialogTransition : \'none\',
                   					ajaxEnabled : false
                   					});
               				});
                			</script>';
                	}
                	if (empty($conf->dol_use_jmobile) || $conf->dol_use_jmobile != 3) print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/mobile/jquery.mobile-latest.min.js'.($ext?'?'.$ext:'').'"></script>'."\n";
                }
            }
        }

        if (! $disablejs && ! empty($conf->use_javascript_ajax))
        {
            // CKEditor
            if (! empty($conf->fckeditor->enabled) && (empty($conf->global->FCKEDITOR_EDITORNAME) || $conf->global->FCKEDITOR_EDITORNAME == 'ckeditor'))
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

            // Global js function
            print '<!-- Includes JS of Dolibarr -->'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/lib_head.js.php'.($ext?'?'.$ext:'').'"></script>'."\n";

            // Add datepicker default options
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/datepicker.js.php'.($ext?'?'.$ext:'').'"></script>'."\n";

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
    if (empty($conf->headerdone)) top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

    print '<body id="mainbody">' . "\n";

    /*
     * Top menu
     */
    print "\n".'<!-- Start top horizontal -->'."\n";

    if (empty($conf->dol_hide_topmenu))
    {
    	print '<div class="side-nav-vert"><div id="id-top">';

	    // Show menu entries
    	print '<div id="tmenu_tooltip'.(empty($conf->global->MAIN_MENU_INVERT)?'':'invert').'" class="tmenu">'."\n";
	    $menumanager->atarget=$target;
	    $menumanager->showmenu('top', array('searchform'=>$searchform, 'bookmarks'=>$bookmarks));      // This contains a \n
	    print "</div>\n";

	    //$form=new Form($db);

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
    	    $logouthtmltext=$appli.'<br>';
    	    if ($_SESSION["dol_authmode"] != 'forceuser' && $_SESSION["dol_authmode"] != 'http')
    	    {
    	    	$logouthtmltext.=$langs->trans("Logout").'<br>';

    	    	$logouttext .='<a href="'.DOL_URL_ROOT.'/user/logout.php">';
    	        $logouttext .= img_picto($langs->trans('Logout').":".$langs->trans('Logout'), 'logout_top.png', 'class="login"', 0, 0, 1);
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
        $toprightmenu.=$user->getNomUrl($mode, '', true, 0, 11, 0, ($user->firstname ? 'firstname' : -1),'atoplogin');
        $toprightmenu.='</div></div>';

		$toprightmenu.='</div>';

	    $toprightmenu.='<div class="login_block_other">';
		// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
	    $parameters=array();
	    $result=$hookmanager->executeHooks('printTopRightMenu',$parameters);    // Note that $action and $object may have been modified by some hooks
		if (is_numeric($result))
		{
			if (empty($result)) $toprightmenu.=$hookmanager->resPrint;		// add
			else  $toprightmenu=$hookmanager->resPrint;						// replace
		}
		else $toprightmenu.=$result;	// For backward compatibility

	    // Link to print main content area
	    if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && empty($conf->browser->phone))
	    {
	        $qs=$_SERVER["QUERY_STRING"];

			foreach($_POST as $key=>$value) {
				if($key!=='action' && !is_array($value))$qs.='&'.$key.'='.urlencode($value);
			}

			$qs.=(($qs && $morequerystring)?'&':'').$morequerystring;
	        $text ='<a href="'.$_SERVER["PHP_SELF"].'?'.$qs.($qs?'&':'').'optioncss=print" target="_blank">';
	        $text.= img_picto(":".$langs->trans("PrintContentArea"), 'printer_top.png', 'class="printer"');
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
	            $title='';
	            //$text.='<div id="blockvmenuhelpwiki" class="blockvmenuhelp">';
	            $title.=$langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage');
	            if ($mode == 'wiki') $title.=' - '.$langs->trans("PageWiki").' &quot;'.dol_escape_htmltag(strtr($helppage,'_',' ')).'&quot;';
	            $text.='<a class="help" target="_blank" href="';
	            if ($mode == 'wiki') $text.=sprintf($helpbaseurl,urlencode(html_entity_decode($helppage)));
	            else $text.=sprintf($helpbaseurl,$helppage);
	            $text.='">';
	            $text.=img_picto('', 'helpdoc_top').' ';
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

	    print "</div>\n";
		print '</div></div>';

	    //unset($form);
    }

	print '<div style="clear: both;"></div>';
    print "<!-- End top horizontal menu -->\n\n";

    if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) print '<div id="id-container">';
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

    if (empty($conf->dol_hide_leftmenu))
    {
	    // Instantiate hooks of thirdparty module
	    $hookmanager->initHooks(array('searchform','leftblock'));

		print "\n".'<!-- Begin id-left -->'."\n".'<div class="side-nav"><div id="id-left">'."\n";

	    print "\n";

	    if ($conf->use_javascript_ajax && $conf->browser->layout != 'phone' && empty($conf->global->MAIN_USE_OLD_SEARCH_FORM))
	    {
    	    if (! is_object($form)) $form=new Form($db);
    	    $selected=-1;
            $searchform.=$form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT.'/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);
	    }
	    else
	    {
    	    // Define $searchform
    	    if ((( ! empty($conf->societe->enabled) && (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) || empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))) || ! empty($conf->fournisseur->enabled)) && $user->rights->societe->lire)
    	    {
    	        $langs->load("companies");
    	        $searchform.=printSearchForm(DOL_URL_ROOT.'/societe/list.php', DOL_URL_ROOT.'/societe/list.php', $langs->trans("ThirdParties"), 'maxwidth100', 'sall', 'T', 'searchleftt', img_object('','company'));
    	    }

    	    if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
    	    {
    	        $langs->load("companies");
    	        $searchform.=printSearchForm(DOL_URL_ROOT.'/contact/list.php', DOL_URL_ROOT.'/contact/list.php', $langs->trans("Contacts"), 'maxwidth100', 'sall', 'A', 'searchleftc', img_object('','contact'));
    	    }

    	    if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
    	    )
    	    {
    	        $langs->load("products");
    	        $searchform.=printSearchForm(DOL_URL_ROOT.'/product/list.php', DOL_URL_ROOT.'/product/list.php', $langs->trans("Products")."/".$langs->trans("Services"), 'maxwidth100', 'sall', 'P', 'searchleftp', img_object('','product'));
    	    }

            if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
    	    {
    	        $langs->load("projects");
    	        $searchform.=printSearchForm(DOL_URL_ROOT.'/projet/list.php', DOL_URL_ROOT.'/projet/list.php', $langs->trans("Projects"), 'maxwidth100', 'search_all', 'Q', 'searchleftproj', img_object('','projectpub'));
    	    }

    	    if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire)
    	    {
    	        $langs->load("members");
    	        $searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/list.php', DOL_URL_ROOT.'/adherents/list.php', $langs->trans("Members"), 'maxwidth100', 'sall', 'M', 'searchleftm', img_object('','user'));
    	    }

    		if (! empty($conf->user->enabled) && $user->rights->user->user->lire)
    	    {
    	        $langs->load("users");
    	        $searchform.=printSearchForm(DOL_URL_ROOT.'/user/list.php', DOL_URL_ROOT.'/user/list.php', $langs->trans("Users"), 'maxwidth100', 'sall', 'M', 'searchleftuser', img_object('','user'));
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

		if ($conf->use_javascript_ajax && $conf->browser->layout == 'phone')
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
        $doliurl='http://www.dolibarr.org';
		//local communities
		if (preg_match('/fr/i',$langs->defaultlang)) $doliurl='http://www.dolibarr.fr';
		if (preg_match('/es/i',$langs->defaultlang)) $doliurl='http://www.dolibarr.es';
		if (preg_match('/de/i',$langs->defaultlang)) $doliurl='http://www.dolibarr.de';
		if (preg_match('/it/i',$langs->defaultlang)) $doliurl='http://www.dolibarr.it';
		if (preg_match('/gr/i',$langs->defaultlang)) $doliurl='http://www.dolibarr.gr';

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
	    if ($doliurl) print '<a class="help" target="_blank" href="'.$doliurl.'">';
	    else print '<span class="help">';
	    print $appli;
	    if ($doliurl) print '</a>';
	    else print '</span>';
	    print '</div>'."\n";

		// Link to bugtrack
		if (! empty($conf->global->MAIN_BUGTRACK_ENABLELINK))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

			$bugbaseurl = 'https://github.com/Dolibarr/dolibarr/issues/new';
			$bugbaseurl.= '?title=';
			$bugbaseurl.= urlencode("Bug: ");
			$bugbaseurl.= '&body=';
			// FIXME: use .github/ISSUE_TEMPLATE.md to generate?
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
			print '<a class="help" target="_blank" href="'.$bugbaseurl.'">'.$langs->trans("FindBug").'</a>';
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

	    print '</div></div> <!-- end id-left -->';	// End div id="id-left"
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

    if (! empty($conf->dol_use_jmobile)) print '<div data-role="page">';
    print '<div class="fiche"> <!-- begin div class="fiche" -->'."\n";
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
 *  Show a search area
 *
 *  @param  string	$urlaction          Url post
 *  @param  string	$urlobject          Url of the link under the search box
 *  @param  string	$title              Title search area
 *  @param  string	$htmlmorecss        Add more css
 *  @param  string	$htmlinputname      Field Name input form
 *  @param	string	$accesskey			Accesskey
 *  @param  string  $prefhtmlinputname  Complement for id to avoid multiple same id in the page
 *  @param	string	$img				Image to use
 *  @return	string
 */
function printSearchForm($urlaction, $urlobject, $title, $htmlmorecss, $htmlinputname, $accesskey='', $prefhtmlinputname='',$img='')
{
    global $conf,$langs;

    if (empty($htmlinputid)) {
        $htmlinputid = $htmlinputname;
    }

    $ret='';
    $ret.='<form action="'.$urlaction.'" method="post" class="searchform">';
	if (empty($conf->global->MAIN_HTML5_PLACEHOLDER))
	{
        $ret.='<div class="menu_titre menu_titre_search"';
    	if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $ret.=' style="display: inline-block"';
    	$ret.='>';
    	$ret.='<label for="'.$prefhtmlinputname.$htmlinputname.'">';
    	$ret.='<a class="vsmenu" href="'.$urlobject.'">';
       	if ($img && ! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $ret.=$img;
       	else if ($img || $title) $ret.=$img.' '.$title;
    	$ret.='</a>';
    	$ret.='</label>';
    	$ret.='</div>';
	}
    $ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    $ret.='<input type="hidden" name="mode" value="search">';
    $ret.='<input type="text" class="flat '.$htmlmorecss.'"';
    if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $ret.=' style="text-indent: 22px; background-image: url(\''.$img.'\'); background-repeat: no-repeat; background-position: 3px;"';
    $ret.=($accesskey?' accesskey="'.$accesskey.'"':'');
    if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $ret.=' placeholder="'.strip_tags($title).'"';		// Will work only if MAIN_HTML5_PLACEHOLDER is set to 1
    else $ret.=' title="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
    $ret.=' name="'.$htmlinputname.'" id="'.$prefhtmlinputname.$htmlinputname.'" />';
    $ret.='<input type="submit" class="button" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px" value="'.$langs->trans("Go").'">';
    $ret.="</form>\n";
    return $ret;
}


if (! function_exists("llxFooter"))
{
    /**
     * Show HTML footer
     * Close div /DIV data-role=page + /DIV class=fiche + /DIV /DIV main layout + /BODY + /HTML.
     * If global var $delayedhtmlcontent was filled, we output it just before closing the body.
     *
     * @param	string	$comment    A text to add as HTML comment into HTML generated page
	 * @param	string	$zone		'private' (for private pages) or 'public' (for public pages)
     * @return	void
     */
    function llxFooter($comment='',$zone='private')
    {
        global $conf, $langs;
        global $delayedhtmlcontent;

        // Global html output events ($mesgs, $errors, $warnings)
        dol_htmloutput_events();

        // Core error message
        if (defined("MAIN_CORE_ERROR") && constant("MAIN_CORE_ERROR") == 1)
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
        print '</div> <!-- End div class="fiche" -->'."\n";
        if (! empty($conf->dol_use_jmobile)) print '</div>';	// end data-role="page"

		if (empty($conf->dol_hide_leftmenu)) print '</div> <!-- End div id-right -->'; // End div id-right

        print "\n";
        if ($comment) print '<!-- '.$comment.' -->'."\n";

        printCommonFooter($zone);
        //var_dump($langs);		// Uncommment to see the property _tab_loaded to see which language file were loaded

        if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) print '</div> <!-- End div id-container -->'."\n";	// End div container

        if (! empty($delayedhtmlcontent)) print $delayedhtmlcontent;

		// Wrapper to show tooltips
        if (! empty($conf->use_javascript_ajax) && empty($conf->dol_no_mouse_hover))
        {
    		print "\n<!-- JS CODE TO ENABLE tipTip on all object with class classfortooltip -->\n";
    		print '<script type="text/javascript">
            	jQuery(document).ready(function () {
            		jQuery(".classfortooltip").tipTip({maxWidth: "'.dol_size(($conf->browser->layout == 'phone' ? 400 : 700),'width').'px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
            	});
            </script>' . "\n";
        }
        
        // Wrapper to manage dropdown
        if ($conf->use_javascript_ajax)
        {
            print "\n<!-- JS CODE TO ENABLE dropdown -->\n";
            print '<script type="text/javascript">
                jQuery(document).ready(function () {
                  $(".dropdown dt a").on(\'click\', function () {
                      //console.log($(this).parent().parent().find(\'dd ul\'));
                      $(this).parent().parent().find(\'dd ul\').slideToggle(\'fast\');
                      // Note: Did not find a way to get exact height (value is update at exit) so i calculate a generic from nb of lines
                      heigthofcontent = 21 * $(this).parent().parent().find(\'dd div ul li\').length;
                      if (heigthofcontent > 300) heigthofcontent = 300; // limited by max-height on css .dropdown dd ul
                      posbottom = $(this).parent().parent().find(\'dd\').offset().top + heigthofcontent + 8;
                      //console.log(posbottom);
                      var scrollBottom = $(window).scrollTop() + $(window).height();
                      //console.log(scrollBottom);
                      diffoutsidebottom = (posbottom - scrollBottom);
                      console.log("heigthofcontent="+heigthofcontent+", diffoutsidebottom (posbottom="+posbottom+" - scrollBottom="+scrollBottom+") = "+diffoutsidebottom);
                      if (diffoutsidebottom > 0)
                      {
                            pix = "-"+(diffoutsidebottom+8)+"px";
                            console.log("We reposition top by "+pix);
                            $(this).parent().parent().find(\'dd\').css("top", pix);
                      }
                      // $(".dropdown dd ul").slideToggle(\'fast\');
                  });
                  $(".dropdowncloseonclick").on(\'click\', function () {
                     console.log("Link has class dropdowncloseonclick, so we close/hide the popup ul");
                     $(this).parent().parent().hide();
                  });
            
                  $(document).bind(\'click\', function (e) {
                      var $clicked = $(e.target);
                      if (!$clicked.parents().hasClass("dropdown")) $(".dropdown dd ul").hide();
                  });
                });
                </script>';
        }
                
		// A div for the address popup
		print "\n<!-- A div to allow dialog popup -->\n";
		print '<div id="dialogforpopup" style="display: none;"></div>'."\n";

        print "</body>\n";
        print "</html>\n";
    }
}

