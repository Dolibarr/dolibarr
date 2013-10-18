<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2008      Matteli
 * Copyright (C) 2011-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
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

// For optionnal tuning. Enabled if environment variable DOL_TUNING is defined.
// A call first. Is the equivalent function dol_microtime_float not yet loaded.
$micro_start_time=0;
if (! empty($_SERVER['DOL_TUNING']))
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
        $sql_inj += preg_match('/delete[\s]+from/i', $val);
        $sql_inj += preg_match('/create[\s]+table/i', $val);
        $sql_inj += preg_match('/update.+set.+=/i', $val);
        $sql_inj += preg_match('/insert[\s]+into/i', $val);
        $sql_inj += preg_match('/select.+from/i', $val);
        $sql_inj += preg_match('/union.+select/i', $val);
        $sql_inj += preg_match('/(\.\.%2f)+/i', $val);
    }
    // For XSS Injection done by adding javascript with script
    // This is all cases a browser consider text is javascript:
    // When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
    // All examples on page: http://ha.ckers.org/xss.html#XSScalc
    $sql_inj += preg_match('/<script/i', $val);
    if (! defined('NOSTYLECHECK')) $sql_inj += preg_match('/<style/i', $val);
    $sql_inj += preg_match('/base[\s]+href/i', $val);
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
 * Security: Return true if OK, false otherwise.
 *
 * @param		string		&$var		Variable name
 * @param		string		$type		1=GET, 0=POST, 2=PHP_SELF
 * @return		boolean					true if there is an injection
 */
function analyse_sql_and_script(&$var, $type)
{
    if (is_array($var))
    {
        foreach ($var as $key => $value)
        {
            if (analyse_sql_and_script($value,$type))
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


// Check consitency of NOREQUIREXXX DEFINES
if ((defined('NOREQUIREDB') || defined('NOREQUIRETRAN')) && ! defined('NOREQUIREMENU')) dol_print_error('','If define NOREQUIREDB or NOREQUIRETRAN are set, you must also set NOREQUIREMENU or not use them');

// Sanity check on URL
if (! empty($_SERVER["PHP_SELF"]))
{
    $morevaltochecklikepost=array($_SERVER["PHP_SELF"]);
    analyse_sql_and_script($morevaltochecklikepost,2);
}
// Sanity check on GET parameters
if (! empty($_SERVER["QUERY_STRING"]))
{
    $morevaltochecklikeget=array($_SERVER["QUERY_STRING"]);
    analyse_sql_and_script($morevaltochecklikeget,1);
}
// Sanity check on POST
analyse_sql_and_script($_POST,0);

// This is to make Dolibarr working with Plesk
if (! empty($_SERVER['DOCUMENT_ROOT'])) set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Include the conf.php and functions.lib.php
require_once 'filefunc.inc.php';

// If there is a POST parameter to tell to save automatically some POST params into a cookies, we do it
if (! empty($_POST["DOL_AUTOSET_COOKIE"]))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
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
	$cookievalue=dol_json_encode($cookiearrayvalue);
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
// This include will set: $conf, $db, $langs, $user, $mysoc objects
require_once 'master.inc.php';

// Activate end of page function
register_shutdown_function('dol_shutdown');

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"]))
{
    $tmp=getBrowserInfo();
    $conf->browser->phone=$tmp['phone'];
    $conf->browser->name=$tmp['browsername'];
    $conf->browser->os=$tmp['browseros'];
    $conf->browser->firefox=$tmp['browserfirefox'];
    $conf->browser->version=$tmp['browserversion'];
}


// Force HTTPS if required ($conf->file->main_force_https is 0/1 or https dolibarr root url)
if (! empty($conf->file->main_force_https))
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
            // $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
            if (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on')		// If link is http
            {
                $newurl=preg_replace('/^http:/i','https:',DOL_MAIN_URL_ROOT).$_SERVER["REQUEST_URI"];
            }
        }
    }
    else
    {
        // Check HTTPS environment variable (Apache/mod_ssl only)
        // $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
        if (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on')		// If link is http
        {
            $newurl=$conf->file->main_force_https.$_SERVER["REQUEST_URI"];
        }
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


// Chargement des includes complementaires de presentation
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
    $token = dol_hash(uniqid(mt_rand(),TRUE)); // Genere un hash d'un nombre aleatoire
    // roulement des jetons car cree a chaque appel
    if (isset($_SESSION['newtoken'])) $_SESSION['token'] = $_SESSION['newtoken'];
    $_SESSION['newtoken'] = $token;
}
if (! empty($conf->global->MAIN_SECURITY_CSRF))	// Check validity of token, only if option enabled (this option breaks some features sometimes)
{
    if (isset($_POST['token']) && isset($_SESSION['token']))
    {
        if (($_POST['token'] != $_SESSION['token']))
        {
            dol_syslog("Invalid token in ".$_SERVER['HTTP_REFERER'].", action=".GETPOST('action').", _POST['token']=".GETPOST('token').", _SESSION['token']=".$_SESSION['token'],LOG_WARNING);
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

    // If requested by the login has already occurred, it is retrieved from the session
    // Call module if not realized that his request.
    // At the end of this phase, the variable $login is defined.
    $resultFetchUser='';
    $test=true;
    if (! isset($_SESSION["dol_login"]))
    {
        // It is not already authenticated and it requests the login / password
        include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

        $dol_dst_observed=GETPOST("dst_observed",3);
        $dol_dst_first=GETPOST("dst_first",3);
        $dol_dst_second=GETPOST("dst_second",3);
        $dol_screenwidth=GETPOST("screenwidth",3);
        $dol_screenheight=GETPOST("screenheight",3);
        $dol_hide_topmenu=GETPOST('dol_hide_topmenu',3);
        $dol_hide_leftmenu=GETPOST('dol_hide_leftmenu',3);
        $dol_optimize_smallscreen=GETPOST('dol_optimize_smallscreen',3);
        $dol_no_mouse_hover=GETPOST('dol_no_mouse_hover',3);
        $dol_use_jmobile=GETPOST('dol_use_jmobile',3);
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

            // Verifie code
            if (! $ok)
            {
                dol_syslog('Bad value for code, connexion refused');
                $langs->load('main');
                $langs->load('errors');

                $user->trigger_mesg='ErrorBadValueForCode - login='.GETPOST("username","alpha",2);
                $_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadValueForCode");
                $test=false;

                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($db);
                $result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,GETPOST('entity','int'));
                if ($result < 0) {
                    $error++;
                }
                // Fin appel triggers
            }
        }

        $usertotest		= (! empty($_COOKIE['login_dolibarr']) ? $_COOKIE['login_dolibarr'] : GETPOST("username","alpha",2));
        $passwordtotest	= (! empty($_COOKIE['password_dolibarr']) ? $_COOKIE['password_dolibarr'] : GETPOST('password'));
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
                $dol_dst=0;
                if (isset($_POST["dst_first"]) && isset($_POST["dst_second"]))
                {
                    include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
                    $datenow=dol_now();
                    $datefirst=dol_stringtotime($_POST["dst_first"]);
                    $datesecond=dol_stringtotime($_POST["dst_second"]);
                    if ($datenow >= $datefirst && $datenow < $datesecond) $dol_dst=1;
                }
                //print $datefirst.'-'.$datesecond.'-'.$datenow; exit;
            }

            if (! $login)
            {
                dol_syslog('Bad password, connexion refused',LOG_DEBUG);
                $langs->load('main');
                $langs->load('errors');

                // Bad password. No authmode has found a good password.
                $user->trigger_mesg=$langs->trans("ErrorBadLoginPassword").' - login='.GETPOST("username","alpha",2);
                $_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");

                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
                $interface=new Interfaces($db);
                $result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,GETPOST("username","alpha",2));
                if ($result < 0) {
                    $error++;
                }
                // Fin appel triggers
            }
        }

        // End test login / passwords
        if (! $login)
        {
            // We show login page
            dol_loginfunction($langs,$conf,(! empty($mysoc)?$mysoc:''));
            exit;
        }

        $resultFetchUser=$user->fetch('',$login);
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

                $user->trigger_mesg='ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
                $_SESSION["dol_loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);
            }
            if ($resultFetchUser < 0)
            {
                $user->trigger_mesg=$user->error;
                $_SESSION["dol_loginmesg"]=$user->error;
            }

            // Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($db);
            $result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
            if ($result < 0) {
                $error++;
            }
            // End call triggers

            header('Location: '.DOL_URL_ROOT.'/index.php');
            exit;
        }
    }
    else
    {
        // We are already into an authenticated session
        $login=$_SESSION["dol_login"];
        dol_syslog("This is an already logged session. _SESSION['dol_login']=".$login);

        $resultFetchUser=$user->fetch('',$login);
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

                $user->trigger_mesg='ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
                $_SESSION["dol_loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);
            }
            if ($resultFetchUser < 0)
            {
                $user->trigger_mesg=$user->error;
                $_SESSION["dol_loginmesg"]=$user->error;
            }

            // Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($db);
            $result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,(isset($_POST["entity"])?$_POST["entity"]:0));
            if ($result < 0) {
                $error++;
            }
            // End call triggers

            header('Location: '.DOL_URL_ROOT.'/index.php');
            exit;
        }
        else
       {
            if (! empty($conf->global->MAIN_ACTIVATE_UPDATESESSIONTRIGGER))	// We do not execute such trigger at each page load by default (triggers are time consuming)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($db);
                $result=$interface->run_triggers('USER_UPDATE_SESSION',$user,$user,$langs,$conf,$conf->entity);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }
    }

    // Is it a new session that has started ?
    // If we are here, this means authentication was successfull.
    if (! isset($_SESSION["dol_login"]))
    {
        // New session for this login.
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

        // Call triggers
        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('USER_LOGIN',$user,$user,$langs,$conf,GETPOST('entity','int'));
        if ($result < 0) {
            $error++;
        }
        // End call triggers

        if ($error)
        {
            $db->rollback();
            session_destroy();
            dol_print_error($db,'Error in some triggers on action USER_LOGIN',LOG_ERR);
            exit;
        }
        else
        {
            $db->commit();
        }

        // Create entity cookie, just used for login page
        // TODO Multicompany Move this into hook
        if (! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_COOKIE_ENABLED) && isset($_POST["entity"]))
        {
            include_once DOL_DOCUMENT_ROOT.'/core/class/cookie.class.php';

            $entity = $_SESSION["dol_login"].'|'.$_POST["entity"];

            $prefix=dol_getprefix();
            $entityCookieName = 'DOLENTITYID_'.$prefix;
            // TTL : is defined in the config page multicompany
            $ttl = (! empty($conf->global->MULTICOMPANY_COOKIE_TTL) ? dol_now()+$conf->global->MULTICOMPANY_COOKIE_TTL : dol_now()+60*60*8 );
            // Cryptkey : will be created randomly in the config page multicompany
            $cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

            $entityCookie = new DolCookie($cryptkey);
            $entityCookie->_setCookie($entityCookieName, $entity, $ttl);
        }

        // Hooks on successfull login
        $action='';
        $hookmanager->initHooks(array('login'));
        $parameters=array('dol_authmode'=>$dol_authmode);
        $reshook=$hookmanager->executeHooks('afterLogin',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) $error++;
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

    // Replace conf->css by personalized value
    if (isset($user->conf->MAIN_THEME) && $user->conf->MAIN_THEME)
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

// Set terminal output option
if (GETPOST('dol_hide_leftmenu') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;
if (! empty($conf->browser->phone))
{
	$conf->dol_optimize_smallscreen=1;
	$conf->dol_no_mouse_hover=1;
}

// Disabled bugged themes
if (! empty($conf->dol_use_jmobile) && in_array($conf->theme,array('bureau2crea','cameleo')))
{
	$conf->theme='eldy';
	$conf->css  =  "/theme/".$conf->theme."/style.css.php";
}

// Disabled bugged themes
if (! empty($conf->dol_use_jmobile) && in_array($conf->theme,array('bureau2crea','cameleo')))
{
	$conf->theme='eldy';
	$conf->css  =  "/theme/".$conf->theme."/style.css.php";
}

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
$bcnd=array(0=>'class="impair nodrag nodrop"',1=>'class="pair nodrag nodrop"');

// Define messages variables
$mesg=''; $warning=''; $error=0;
// deprecated, see setEventMessage() and dol_htmloutput_events()
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

$heightforframes=52;

// Switch to another entity
// TODO Multicompany Remove this
if (! empty($conf->multicompany->enabled) && GETPOST('action') == 'switchentity')
{
    if ($mc->switchEntity(GETPOST('entity','int')) > 0)
    {
        header("Location: ".DOL_URL_ROOT.'/');
        exit;
    }
}


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
	function llxHeader($head = '', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='')
	{
	    global $conf;

	    // html header
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

		// top menu and left menu area
		if (empty($conf->global->MAIN_HIDE_TOP_MENU))
		{
			top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring);
		}
		if (empty($conf->global->MAIN_HIDE_LEFT_MENU))
		{
			left_menu('', $help_url, '', '', 1, $title);
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
 * @param 	int    	$disablejs		More content into html header
 * @param 	int    	$disablehead	More content into html header
 * @param 	array  	$arrayofjs		Array of complementary js files
 * @param 	array  	$arrayofcss		Array of complementary css files
 * @return	void
 */
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
    global $user, $conf, $langs, $db;

    top_httphead();

    if (empty($conf->css)) $conf->css = '/theme/eldy/style.css.php';	// If not defined, eldy by default

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    //print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd">';
    //print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    //print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    //print '<!DOCTYPE HTML>';
    print "\n";
    if (! empty($conf->global->MAIN_USE_CACHE_MANIFEST)) print '<html manifest="'.DOL_URL_ROOT.'/cache.manifest">'."\n";
    else print '<html>'."\n";
    //print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">'."\n";
    if (empty($disablehead))
    {
        print "<head>\n";
		if (GETPOST('dol_basehref')) print '<base href="'.dol_escape_htmltag(GETPOST('dol_basehref')).'">'."\n";
        // Displays meta
        print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
        print '<meta name="author" content="Dolibarr Development Team">'."\n";
        $favicon=dol_buildpath('/theme/'.$conf->theme.'/img/favicon.ico',1);
        print '<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>'."\n";
        if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="top" title="'.$langs->trans("Home").'" href="'.(DOL_URL_ROOT?DOL_URL_ROOT:'/').'">'."\n";
        if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
        if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="author" title="Dolibarr Development Team" href="http://www.dolibarr.org">'."\n";

        // Displays title
        $appli='Dolibarr';
        if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

        if ($title) print '<title>'.$appli.' - '.$title.'</title>';
        else print "<title>".$appli."</title>";
        print "\n";

        if (! defined('DISABLE_JQUERY') && ! $disablejs && $conf->use_javascript_ajax)
        {
            print '<!-- Includes CSS for JQuery (Ajax library) -->'."\n";
            $jquerytheme = 'smoothness';
            if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
            if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css" />'."\n";  // JQuery
            else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui-latest.custom.css" />'."\n";    // JQuery
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/tiptip/tipTip.css" />'."\n";                           // Tooltip
            print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css" />'."\n";          // JNotify
            /*if (! empty($conf->global->MAIN_USE_JQUERY_FILEUPLOAD) || (defined('REQUIRE_JQUERY_FILEUPLOAD') && constant('REQUIRE_JQUERY_FILEUPLOAD')))     // jQuery fileupload
            {
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/css/jquery.fileupload-ui.css" />'."\n";
            }*/
            if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES) || (defined('REQUIRE_JQUERY_DATATABLES') && constant('REQUIRE_JQUERY_DATATABLES')))     // jQuery datatables
            {
                //print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/css/jquery.dataTables.css" />'."\n";
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/css/jquery.dataTables_jui.css" />'."\n";
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/ColReorder/css/ColReorder.css" />'."\n";
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/ColVis/css/ColVis.css" />'."\n";
                //print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/ColVis/css/ColVisAlt.css" />'."\n";
                print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/TableTools/css/TableTools.css" />'."\n";
            }
            if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || (defined('REQUIRE_JQUERY_MULTISELECT') && constant('REQUIRE_JQUERY_MULTISELECT')))     // jQuery multiselect
            {
            	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/multiselect/css/ui.multiselect.css" />'."\n";
            }
            // jQuery Timepicker
            if (! empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER'))
            {
            	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.css" />'."\n";
            }
            // jQuery jMobile
            if (! empty($conf->global->MAIN_USE_JQUERY_JMOBILE) || defined('REQUIRE_JQUERY_JMOBILE') || ! empty($conf->dol_use_jmobile))
            {
            	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/mobile/jquery.mobile-latest.min.css" />'."\n";
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
        $themeparam='?lang='.$langs->defaultlang.'&amp;theme='.$conf->theme.(GETPOST('optioncss')?'&amp;optioncss='.GETPOST('optioncss','alpha',1):'').'&amp;userid='.$user->id.'&amp;entity='.$conf->entity;
        if (! empty($_SESSION['dol_resetcache'])) $themeparam.='&amp;dol_resetcache='.$_SESSION['dol_resetcache'];
        if (GETPOST('dol_hide_topmenu'))           { $themeparam.='&amp;dol_hide_topmenu='.GETPOST('dol_hide_topmenu'); }
        if (GETPOST('dol_hide_leftmenu'))          { $themeparam.='&amp;dol_hide_leftmenu='.GETPOST('dol_hide_leftmenu'); }
        if (GETPOST('dol_optimize_smallscreen'))   { $themeparam.='&amp;dol_optimize_smallscreen='.GETPOST('dol_optimize_smallscreen'); }
        if (GETPOST('dol_no_mouse_hover'))         { $themeparam.='&amp;dol_no_mouse_hover='.GETPOST('dol_no_mouse_hover'); }
        if (GETPOST('dol_use_jmobile'))            { $themeparam.='&amp;dol_use_jmobile='.GETPOST('dol_use_jmobile'); $conf->dol_use_jmobile=GETPOST('dol_use_jmobile'); }
        //print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
        print '<link rel="stylesheet" type="text/css" title="default" href="'.$themepath.$themeparam.'">'."\n";

        // CSS forced by modules (relative url starting with /)
        if (! empty($conf->modules_parts['css']))
        {
        	$arraycss=(array) $conf->modules_parts['css'];
        	foreach($arraycss as $modcss => $filescss)
        	{
        		$filescss=(array) $filescss;	// To be sure filecss is an array
        		foreach($filescss as $cssfile)
        		{
        			// cssfile is a relative path
	        		print '<!-- Includes CSS added by module '.$modcss. ' -->'."\n".'<link rel="stylesheet" type="text/css" title="default" href="'.dol_buildpath($cssfile,1);
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

        $ext='.js';

        // Output standard javascript links
        if (! defined('DISABLE_JQUERY') && ! $disablejs && ! empty($conf->use_javascript_ajax))
        {
            // JQuery. Must be before other includes
            print '<!-- Includes JS for JQuery -->'."\n";
            if (constant('JS_JQUERY')) print '<script type="text/javascript" src="'.JS_JQUERY.'jquery.min'.$ext.'"></script>'."\n";
            else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-latest.min'.$ext.'"></script>'."\n";
            if (constant('JS_JQUERY_UI')) print '<script type="text/javascript" src="'.JS_JQUERY_UI.'jquery-ui.min.js"></script>'."\n";
            else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui-latest.custom.min'.$ext.'"></script>'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd.0.6.min'.$ext.'"></script>'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tiptip/jquery.tipTip.min'.$ext.'"></script>'."\n";
            // jQuery Layout
            if (! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT) || defined('REQUIRE_JQUERY_LAYOUT'))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/layout/jquery.layout-latest'.$ext.'"></script>'."\n";
            }
            // jQuery jnotify
            if (empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && ! defined('DISABLE_JQUERY_JNOTIFY'))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/jnotify.js"></script>'."\n";
            }
            // jQuery blockUI
            if (! empty($conf->global->MAIN_USE_JQUERY_BLOCKUI) || defined('REQUIRE_JQUERY_BLOCKUI'))
            {
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/blockUI/jquery.blockUI.js"></script>'."\n";
            	print '<script type="text/javascript">'."\n";
            	print 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working2.gif".'\';'."\n";
            	print '</script>'."\n";
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/blockUI.js"></script>'."\n";
            }
            // Flot
            if (empty($conf->global->MAIN_DISABLE_JQUERY_FLOT))
            {
                if (constant('JS_JQUERY_FLOT'))
                {
                    print '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="/javascript/excanvas/excanvas.min.js"></script><![endif]-->'."\n";
                    print '<script type="text/javascript" src="'.JS_JQUERY_FLOT.'jquery.flot.js"></script>'."\n";
                    print '<script type="text/javascript" src="'.JS_JQUERY_FLOT.'jquery.flot.pie.js"></script>'."\n";
                    print '<script type="text/javascript" src="'.JS_JQUERY_FLOT.'jquery.flot.stack.js"></script>'."\n";
                }
                else
                {
                    print '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/excanvas.min.js"></script><![endif]-->'."\n";
                    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/jquery.flot.min.js"></script>'."\n";
                    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/jquery.flot.pie.min.js"></script>'."\n";
                    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/flot/jquery.flot.stack.min.js"></script>'."\n";
                }
            }
            // jQuery jeditable
            if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.min'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-datepicker.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-autocomplete.js"></script>'."\n";
                print '<script type="text/javascript">'."\n";
                print 'var urlSaveInPlace = \''.DOL_URL_ROOT.'/core/ajax/saveinplace.php\';'."\n";
                print 'var urlLoadInPlace = \''.DOL_URL_ROOT.'/core/ajax/loadinplace.php\';'."\n";
                print 'var tooltipInPlace = \''.$langs->transnoentities('ClickToEdit').'\';'."\n";
                print 'var placeholderInPlace = \''.$langs->trans('ClickToEdit').'\';'."\n";
                print 'var cancelInPlace = \''.$langs->trans('Cancel').'\';'."\n";
                print 'var submitInPlace = \''.$langs->trans('Ok').'\';'."\n";
                print 'var indicatorInPlace = \'<img src="'.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'">\';'."\n";
                print '</script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/editinplace.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ckeditor.js"></script>'."\n";
            }
            // jQuery File Upload
            /*
            if (! empty($conf->global->MAIN_USE_JQUERY_FILEUPLOAD) || (defined('REQUIRE_JQUERY_FILEUPLOAD') && constant('REQUIRE_JQUERY_FILEUPLOAD')))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/template/tmpl.min.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.iframe-transport.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload-fp.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload-ui.js"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/jquery.fileupload-jui.js"></script>'."\n";
                print '<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->'."\n";
                print '<!--[if gte IE 8]><script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/fileupload/js/cors/jquery.xdr-transport.js"></script><![endif]-->'."\n";
            }*/
            // jQuery DataTables
            if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES) || (defined('REQUIRE_JQUERY_DATATABLES') && constant('REQUIRE_JQUERY_DATATABLES')))
            {
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/js/jquery.dataTables.min'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/ColReorder/js/ColReorder.min'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/ColVis/js/ColVis.min'.$ext.'"></script>'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/TableTools/js/TableTools.min'.$ext.'"></script>'."\n";
            }
            // jQuery Multiselect
            if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || (defined('REQUIRE_JQUERY_MULTISELECT') && constant('REQUIRE_JQUERY_MULTISELECT')))
            {
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/multiselect/js/ui.multiselect.js"></script>'."\n";
            }
            // jQuery Timepicker
            if (! empty($conf->global->MAIN_USE_JQUERY_TIMEPICKER) || defined('REQUIRE_JQUERY_TIMEPICKER'))
            {
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js"></script>'."\n";
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/timepicker.js.php?lang='.$langs->defaultlang.'"></script>'."\n";
            }
            // jQuery jMobile
            if (! empty($conf->global->MAIN_USE_JQUERY_JMOBILE) || defined('REQUIRE_JQUERY_JMOBILE') || (! empty($conf->dol_use_jmobile) && $conf->dol_use_jmobile > 0))
            {
            	// We must force not using ajax because cache of jquery does not load js of other pages.
            	// This also increase seriously speed onto mobile device where complex js code is very slow and memory very low.
            	if (empty($conf->dol_use_jmobile) || $conf->dol_use_jmobile != 2)
            	{
            		print '<script type="text/javascript">
	            		$(document).bind("mobileinit", function(){
           				$.extend(  $.mobile , {
           					autoInitializePage : true,	/* We need this to run jmobile */
           					/* loadingMessage : \'xxxxx\', */
           					touchOverflowEnabled : true,
           					defaultPageTransition : \'none\',
           					defaultDialogTransition : \'none\',
           					ajaxEnabled : false			/* old param was ajaxFormsEnabled and ajaxLinksEnabled */
           					});
           				});
            			</script>';
            	}
            	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/mobile/jquery.mobile-latest.min.js"></script>'."\n";
            }
        }

        if (! $disablejs && ! empty($conf->use_javascript_ajax))
        {
            // CKEditor
            if (! empty($conf->fckeditor->enabled) && (empty($conf->global->FCKEDITOR_EDITORNAME) || $conf->global->FCKEDITOR_EDITORNAME == 'ckeditor'))
            {
                print '<!-- Includes JS for CKEditor -->'."\n";
                $pathckeditor=DOL_URL_ROOT.'/includes/ckeditor/';
                if (constant('JS_CKEDITOR')) $pathckeditor=JS_CKEDITOR;    // To use external ckeditor js lib
                print '<script type="text/javascript">';
                print 'var CKEDITOR_BASEPATH = \''.$pathckeditor.'\';'."\n";
                print 'var ckeditorConfig = \''.dol_buildpath($themesubdir.'/theme/'.$conf->theme.'/ckeditor/config.js',1).'\';'."\n";		// $themesubdir='' in standard usage
                print 'var ckeditorFilebrowserBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
                print 'var ckeditorFilebrowserImageBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Type=Image&Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
                print '</script>'."\n";
                print '<script type="text/javascript" src="'.$pathckeditor.'ckeditor_basic.js"></script>'."\n";
            }

            // Global js function
            print '<!-- Includes JS of Dolibarr -->'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/lib_head.js"></script>'."\n";

            // Add datepicker default options
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/datepicker.js.php?lang='.$langs->defaultlang.'"></script>'."\n";

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
 *  @param      string	$target  			Target to use in menu links
 *	@param		int		$disablejs			Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *	@param		int		$disablehead		Do not output head section
 *	@param		array	$arrayofjs			Array of js files to add in header
 *	@param		array	$arrayofcss			Array of css files to add in header
 *  @param		string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 *  @return		void
 */
function top_menu($head, $title='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='')
{
    global $user, $conf, $langs, $db;
    global $dolibarr_main_authentication;
    global $hookmanager,$menumanager;

    // Instantiate hooks of thirdparty module
    $hookmanager->initHooks(array('toprightmenu'));

    $toprightmenu='';

    // For backward compatibility with old modules
    if (empty($conf->headerdone)) top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

    print '<body id="mainbody">';

    if ($conf->use_javascript_ajax)
    {
        if (! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))
        {
            print '<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery("body").layout(layoutSettings);
				});
				var layoutSettings = {
					name: "mainlayout",
					defaults: {
						useStateCookie: true,
						size: "auto",
						resizable: false,
						//paneClass: "none",
						//resizerClass: "resizer",
						//togglerClass: "toggler",
						//buttonClass: "button",
						//contentSelector: ".content",
						//contentIgnoreSelector: "span",
						togglerTip_open: "Close This Pane",
						togglerTip_closed: "Open This Pane",
						resizerTip:	"Resize This Pane",
						fxSpeed: "fast"
					},
					west: {
						paneClass: "leftContent",
						//spacing_closed:	14,
						//togglerLength_closed: 14,
						//togglerAlign_closed: "auto",
						//togglerLength_open: 0,
						//	effect defaults - overridden on some panes
						//slideTrigger_open:	"mouseover",
						initClosed:	'.(empty($conf->dol_optimize_smallscreen)?'false':'true').',
						fxName:	"drop",
						fxSpeed: "fast",
						fxSettings: { easing: "" }
					},
					north: {
						paneClass: "none",
						resizerClass: "none",
						togglerClass: "none",
						spacing_open: 0,
						togglerLength_open:	0,
						togglerLength_closed: -1,
						slidable: false,
						fxName:	"none",
						fxSpeed: "fast"
					},
					center: {
						paneSelector: "#mainContent"
					}
				}
    		</script>';
        }

        if (! empty($conf->global->MAIN_MENU_USE_JQUERY_ACCORDION))
        {
            print "\n".'<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery( ".vmenu" ).accordion({
							autoHeight: false,
							event: "mouseover",
							//collapsible: true,
							//active: 2,
							header: "> .blockvmenupair > .menu_titre"
						});
					});
					</script>';
        }

        // Wrapper to show tooltips
        print "\n".'<script type="text/javascript">
                    jQuery(document).ready(function () {
                    	jQuery(function() {
                        	jQuery(".classfortooltip").tipTip({maxWidth: "'.dol_size(600,'width').'px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
                        });
                    });
                </script>';
    }

    /*
     * Top menu
     */
    print "\n".'<!-- Start top horizontal -->'."\n";

    if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)) print '<div class="ui-layout-north"> <!-- Begin top layout -->'."\n";

    if (empty($conf->dol_hide_topmenu))
    {
	    // Show menu entries
    	print '<div id="tmenu_tooltip" class="tmenu">'."\n";
	    $menumanager->atarget=$target;
	    $menumanager->showmenu('top');      // This contains a \n
	    print "</div>\n";

	    $form=new Form($db);

	    // Define link to login card
	    $loginhtmltext=''; $logintext='';
	    if ($user->societe_id)
	    {
	        $thirdpartystatic=new Societe($db);
	        $thirdpartystatic->fetch($user->societe_id);
	        $companylink=' ('.$thirdpartystatic->getNomUrl('','').')';
	        $company=' ('.$langs->trans("Company").': '.$thirdpartystatic->name.')';
	    }
	    $logintext='<div class="login"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'"';
	    $logintext.=$target?(' target="'.$target.'"'):'';
	    $logintext.='>'.$user->login.'</a>';
	    if ($user->societe_id) $logintext.=$companylink;
	    $logintext.='</div>';
	    $loginhtmltext.='<u>'.$langs->trans("User").'</u>';
	    $loginhtmltext.='<br><b>'.$langs->trans("Name").'</b>: '.$user->getFullName($langs);
	    $loginhtmltext.='<br><b>'.$langs->trans("Login").'</b>: '.$user->login;
	    $loginhtmltext.='<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	    $type=($user->societe_id?$langs->trans("External").$company:$langs->trans("Internal"));
	    $loginhtmltext.='<br><b>'.$langs->trans("Type").'</b>: '.$type;
	    $loginhtmltext.='<br><b>'.$langs->trans("IPAddress").'</b>: '.$_SERVER["REMOTE_ADDR"];
	    $loginhtmltext.='<br>';
	    $loginhtmltext.='<br><u>'.$langs->trans("Connection").'</u>';
	    if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $loginhtmltext.='<br><b>'.$langs->trans("ConnectedOnMultiCompany").'</b>: '.$conf->entity.' (user entity '.$user->entity.')';
	    $loginhtmltext.='<br><b>'.$langs->trans("ConnectedSince").'</b>: '.dol_print_date($user->datelastlogin,"dayhour");
	    $loginhtmltext.='<br><b>'.$langs->trans("PreviousConnexion").'</b>: '.dol_print_date($user->datepreviouslogin,"dayhour");
	    $loginhtmltext.='<br><b>'.$langs->trans("AuthenticationMode").'</b>: '.$_SESSION["dol_authmode"];
	    $loginhtmltext.='<br><b>'.$langs->trans("CurrentTheme").'</b>: '.$conf->theme;
	    $loginhtmltext.='<br><b>'.$langs->trans("CurrentMenuManager").'</b>: '.$menumanager->name;
	    $s=picto_from_langcode($langs->getDefaultLang());
	    $loginhtmltext.='<br><b>'.$langs->trans("CurrentUserLanguage").'</b>: '.($s?$s.' ':'').$langs->getDefaultLang();
	    $loginhtmltext.='<br><b>'.$langs->trans("Browser").'</b>: '.$conf->browser->name.($conf->browser->version?' '.$conf->browser->version:'').' ('.$_SERVER['HTTP_USER_AGENT'].')';
	    if (! empty($conf->browser->phone)) $loginhtmltext.='<br><b>'.$langs->trans("Phone").'</b>: '.$conf->browser->phone;
	    if (! empty($_SESSION["disablemodules"])) $loginhtmltext.='<br><b>'.$langs->trans("DisabledModules").'</b>: <br>'.join(', ',explode(',',$_SESSION["disablemodules"]));

	    $appli='Dolibarr';
	    if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

	    // Link info
	    $logouttext='';
	    $logouthtmltext=$appli.' '.DOL_VERSION.'<br>';
	    $logouthtmltext.=$langs->trans("Logout").'<br>';
	    //$logouthtmltext.="<br>";
	    if ($_SESSION["dol_authmode"] != 'forceuser' && $_SESSION["dol_authmode"] != 'http')
	    {
	        $logouttext .='<a href="'.DOL_URL_ROOT.'/user/logout.php"';
	        //$logouttext .=empty($atarget?(' target="'.$atarget.'"'):'';
	        $logouttext .='>';
	        $logouttext .= img_picto($langs->trans('Logout'), 'logout.png', 'class="login"', 0, 0, 1);
	        $logouttext .='</a>';
	    }
	    else
	    {
	        $logouttext .= img_picto($langs->trans('Logout'), 'logout.png', 'class="login"', 0, 0, 1);
	    }

	    print '<div class="login_block">'."\n";
	    //print '<table class="nobordernopadding" summary=""><tr>';

	    $toprightmenu.='<div class="login_block_user">';
	    // Add login user link
	    $toprightmenu.=$form->textwithtooltip('',$loginhtmltext,2,1,$logintext,'login_block_elem2',2);	// This include div class="login"
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

	    // Logout link
	    $toprightmenu.=$form->textwithtooltip('',$logouthtmltext,2,1,$logouttext,'login_block_elem',2);

	    // Link to print main content area
	    if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->browser->phone))
	    {
	        $qs=$_SERVER["QUERY_STRING"].($_SERVER["QUERY_STRING"]?'&':'').$morequerystring;
	        $text ='<a href="'.$_SERVER["PHP_SELF"].'?'.$qs.($qs?'&amp;':'').'optioncss=print" target="_blank">';
	        $text.= img_picto('', 'printer.png', 'class="printer"');
	        $text.='</a>';
	        $toprightmenu.=$form->textwithtooltip('',$langs->trans("PrintContentArea"),2,1,$text,'login_block_elem',2);
	    }
		$toprightmenu.='</div>';

	    print $toprightmenu;

	    //print '</tr></table>'."\n";
	    print "</div>\n";

	    unset($form);
    }

    if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)) print "</div><!-- End top layout -->\n";
	print '<div style="clear: both;"></div>';
    print "<!-- End top horizontal menu -->\n\n";

    if (empty($conf->dol_hide_leftmenu) && (empty($conf->use_javascript_ajax) || empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))) print '<div id="id-container">';
}


/**
 *  Show left menu bar
 *
 *  @param  array	$menu_array_before 	       	Table of menu entries to show before entries of menu handler
 *  @param  string	$helppagename    	       	Name of wiki page for help ('' by default).
 * 				     		                   	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 * 									         		       For other external page: http://server/url
 *  @param  string	$moresearchform             Search Form Permanent Supplemental
 *  @param  array	$menu_array_after           Table of menu entries to show after entries of menu handler
 *  @param  int		$leftmenuwithoutmainarea    Must be set to 1. 0 by default for backward compatibility with old modules.
 *  @param  string	$title                      Title of web page
 *  @return	void
 */
function left_menu($menu_array_before, $helppagename='', $moresearchform='', $menu_array_after='', $leftmenuwithoutmainarea=0, $title='')
{
    global $user, $conf, $langs, $db;
    global $hookmanager, $menumanager;

    $searchform='';
    $bookmarks='';

    if (empty($conf->dol_hide_leftmenu))
    {
	    // Instantiate hooks of thirdparty module
	    $hookmanager->initHooks(array('searchform','leftblock'));

    	if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)) print "\n".'<div class="ui-layout-west"> <!-- Begin left layout -->'."\n";
		else print '<div id="id-left">';

	    print "\n";

	    // Define $searchform
	    if ((( ! empty($conf->societe->enabled) && (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) || empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))) || ! empty($conf->fournisseur->enabled)) && ! empty($conf->global->MAIN_SEARCHFORM_SOCIETE) && $user->rights->societe->lire)
	    {
	        $langs->load("companies");
	        $searchform.=printSearchForm(DOL_URL_ROOT.'/societe/societe.php', DOL_URL_ROOT.'/societe/societe.php', img_object('','company').' '.$langs->trans("ThirdParties"), 'soc', 'socname');
	    }

	    if (! empty($conf->societe->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_CONTACT) && $user->rights->societe->lire)
	    {
	        $langs->load("companies");
	        $searchform.=printSearchForm(DOL_URL_ROOT.'/contact/list.php', DOL_URL_ROOT.'/contact/list.php', img_object('','contact').' '.$langs->trans("Contacts"), 'contact', 'contactname');
	    }

	    if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
	    && ! empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE))
	    {
	        $langs->load("products");
	        $searchform.=printSearchForm(DOL_URL_ROOT.'/product/liste.php', DOL_URL_ROOT.'/product/liste.php', img_object('','product').' '.$langs->trans("Products")."/".$langs->trans("Services"), 'products', 'sall');
	    }

	    if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire)) && ! empty($conf->fournisseur->enabled)
	    && ! empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE_SUPPLIER))
	    {
	        $langs->load("products");
	        $searchform.=printSearchForm(DOL_URL_ROOT.'/fourn/product/liste.php', DOL_URL_ROOT.'/fourn/product/liste.php', img_object('','product').' '.$langs->trans("SupplierRef"), 'products', 'srefsupplier');
	    }

	    if (! empty($conf->adherent->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_ADHERENT) && $user->rights->adherent->lire)
	    {
	        $langs->load("members");
	        $searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/liste.php', DOL_URL_ROOT.'/adherents/liste.php', img_object('','user').' '.$langs->trans("Members"), 'member', 'sall');
	    }

	    // Execute hook printSearchForm
	    $parameters=array();
	    $reshook=$hookmanager->executeHooks('printSearchForm',$parameters);    // Note that $action and $object may have been modified by some hooks
		if (empty($reshook))
		{
			$searchform.=$hookmanager->resPrint;
		}
		else $searchform=$hookmanager->resPrint;

	    // Define $bookmarks
	    if (! empty($conf->bookmark->enabled) && $user->rights->bookmark->lire)
	    {
	        include_once (DOL_DOCUMENT_ROOT.'/bookmarks/bookmarks.lib.php');
	        $langs->load("bookmarks");

	        $bookmarks=printBookmarksList($db, $langs);
	    }

	    // Left column
	    print '<!-- Begin left menu -->'."\n";

	    print '<div class="vmenu">'."\n\n";

	    $menumanager->menu_array = $menu_array_before;
    	$menumanager->menu_array_after = $menu_array_after;
	    $menumanager->showmenu('left'); // output menu_array and menu found in database


	    // Show other forms
	    if ($searchform)
	    {
	        print "\n";
	        print "<!-- Begin SearchForm -->\n";
	        print '<div id="blockvmenusearch" class="blockvmenusearch">'."\n";
	        print $searchform;
	        print '</div>'."\n";
	        print "<!-- End SearchForm -->\n";
	    }

	    // More search form
	    if ($moresearchform)
	    {
	        print $moresearchform;
	    }

	    // Bookmarks
	    if ($bookmarks)
	    {
	        print "\n";
	        print "<!-- Begin Bookmarks -->\n";
	        print '<div id="blockvmenubookmarks" class="blockvmenubookmarks">'."\n";
	        print $bookmarks;
	        print '</div>'."\n";
	        print "<!-- End Bookmarks -->\n";
	    }

	    //Dolibarr version
	    $doliurl='http://www.dolibarr.org';
	    $appli='Dolibarr';
	    if (! empty($conf->global->MAIN_APPLICATION_TITLE)) {
	    	$appli=$conf->global->MAIN_APPLICATION_TITLE; $doliurl='';
	    }
	    $appli.=" ".DOL_VERSION;
	    print '<div id="blockvmenuhelp" class="blockvmenuhelp">';
	    if ($doliurl) print '<a class="help" target="_blank" href="'.$doliurl.'">';
	    print $appli;
	    if ($doliurl) print '</a>';
	    print '</div>';

	    // Link to Dolibarr wiki pages
	    if ($helppagename && empty($conf->global->MAIN_HELP_DISABLELINK))
	    {
	        $langs->load("help");

	        $helpbaseurl='';
	        $helppage='';
	        $mode='';

	        // Get helpbaseurl, helppage and mode from helppagename and langs
	        $arrayres=getHelpParamFor($helppagename,$langs);
	        $helpbaseurl=$arrayres['helpbaseurl'];
	        $helppage=$arrayres['helppage'];
	        $mode=$arrayres['mode'];

	        // Link to help pages
	        if ($helpbaseurl && $helppage)
	        {
	            print '<div id="blockvmenuhelp" class="blockvmenuhelp">';
	            print '<a class="help" target="_blank" title="'.$langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage');
	            if ($mode == 'wiki') print ' - '.$langs->trans("PageWiki").' &quot;'.dol_escape_htmltag(strtr($helppage,'_',' ')).'&quot;';
	            print '" href="';
	            if ($mode == 'wiki') print sprintf($helpbaseurl,urlencode(html_entity_decode($helppage)));
	            else print sprintf($helpbaseurl,$helppage);
	            print '">';
	            print img_picto('', 'helpdoc').' ';
	            print $langs->trans($mode == 'wiki' ? 'OnlineHelp': 'Help');
	            //if ($mode == 'wiki') print ' ('.dol_trunc(strtr($helppage,'_',' '),8).')';
	            print '</a>';
	            print '</div>';
	        }
	    }

	    // Link to bugtrack
	    if (! empty($conf->global->MAIN_BUGTRACK_ENABLELINK))
	    {
	    	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	        $bugbaseurl='https://doliforge.org/tracker/?';
	        $bugbaseurl.='func=add&group_id=144&atid=246';
	        $bugbaseurl.="&details=";
	        $bugbaseurl.=urlencode("\n\n\n\n\n-------------\n");
	        $bugbaseurl.=urlencode($langs->trans("Version").": ".DOL_VERSION."\n");
	        $bugbaseurl.=urlencode($langs->trans("Server").": ".$_SERVER["SERVER_SOFTWARE"]."\n");
	        $bugbaseurl.=urlencode($langs->trans("PHP").": ".version_php()."\n");
	        $bugbaseurl.=urlencode($langs->trans("Url").": ".$_SERVER["REQUEST_URI"]."\n");
	        print '<div id="blockvmenubugtracker" class="blockvmenuhelp"><a class="help" target="_blank" href="'.$bugbaseurl.'">'.$langs->trans("FindBug").'</a></div>';
	    }
	    print "\n";

	    print "</div>\n";
	    print "<!-- End left menu -->\n";

	    print "\n";

	    // Execute hook printLeftBlock
	    $parameters=array();
	    $leftblock=$hookmanager->executeHooks('printLeftBlock',$parameters);    // Note that $action and $object may have been modified by some hooks
	    print $leftblock;

	    if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)) print '</div> <!-- End left layout -->'."\n";
		//XXX	    else print '</td>';
	    else print '</div>';	// End div id="id-left"
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

    if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))
    {
        print '<div id="mainContent"><div class="ui-layout-center"> <!-- begin main layout -->'."\n";
        //print '<table width="100%" class="notopnoleftnoright" summary="centermenutable" id="undertopmenu"><tr>';
    }
	//XXX print '<td valign="top">'."\n";
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
 *  @param  string	$htmlmodesearch     Value to set into parameter "mode_search" ('soc','contact','products','member',...)
 *  @param  string	$htmlinputname      Field Name input form
 *  @return	void
 */
function printSearchForm($urlaction,$urlobject,$title,$htmlmodesearch,$htmlinputname)
{
    global $conf,$langs;

    $ret='';
    $ret.='<div class="menu_titre">';
    $ret.='<a class="vsmenu" href="'.$urlobject.'">';
    $ret.=$title.'</a><br>';
    $ret.='</div>';
    $ret.='<form action="'.$urlaction.'" method="post">';
    $ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    $ret.='<input type="hidden" name="mode" value="search">';
    $ret.='<input type="hidden" name="mode_search" value="'.$htmlmodesearch.'">';
    $ret.='<input type="text" class="flat" ';
    if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $ret.=' placeholder="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
    else $ret.=' title="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
    $ret.=' name="'.$htmlinputname.'" size="10" />';
    $ret.='<input type="submit" class="button" value="'.$langs->trans("Go").'">';
    $ret.="</form>\n";
    return $ret;
}


if (! function_exists("llxFooter"))
{
    /**
     * Show HTML footer
     * Close div /DIV data-role=page + /DIV class=fiche + /DIV /DIV main layout + /BODY + /HTML.
     *
     * @param	string	$comment    A text to add as HTML comment into HTML generated page
	 * @param	string	$zone		'private' (for private pages) or 'public' (for public pages)
     * @return	void
     */
    function llxFooter($comment='',$zone='pivate')
    {
        global $conf, $langs;

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

            define("MAIN_CORE_ERROR",0);
        }

        print "\n\n";
        print '</div> <!-- end div class="fiche" -->'."\n";
        if (! empty($conf->dol_use_jmobile)) print '</div>';	// end data-role="page"

		//XXX print "\n".'</td></tr></table> <!-- end right area -->'."\n";
        if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)) print '</div></div> <!-- end main layout -->'."\n";
		if (empty($conf->dol_hide_leftmenu)) print '</div>'; // End div id-right

        print "\n";
        if ($comment) print '<!-- '.$comment.' -->'."\n";

        printCommonFooter($zone);

        if (empty($conf->dol_hide_leftmenu)) print '</div>';	// End div container

        print "</body>\n";
        print "</html>\n";
    }
}

?>
