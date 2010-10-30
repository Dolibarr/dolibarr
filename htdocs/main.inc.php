<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Matteli
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/main.inc.php
 *	\ingroup	core
 *	\brief      File that defines environment for Dolibarr pages only (variables not required by scripts)
 *	\version    $Id$
 */

@ini_set('memory_limit', '64M');	// This may be useless if memory is hard limited by your PHP

// For optionnal tuning. Enabled if environment variable DOL_TUNING is defined.
// A call first. Is the equivalent function dol_microtime_float not yet loaded.
$micro_start_time=0;
if (! empty($_SERVER['DOL_TUNING']))
{
	list($usec, $sec) = explode(" ", microtime());
	$micro_start_time=((float)$usec + (float)$sec);
	// Add Xdebug coverage of code
	//define('XDEBUGCOVERAGE',1);
	if (defined('XDEBUGCOVERAGE')) { xdebug_start_code_coverage(); }
}

// Forcing parameter setting magic_quotes_gpc and cleaning parameters
// (Otherwise he would have for each position, condition
// Reading stripslashes variable according to state get_magic_quotes_gpc).
// Off mode (recommended, you just do addslashes when an insert / update.
function stripslashes_deep($value)
{
	return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}
if (function_exists('get_magic_quotes_gpc'))	// magic_quotes_* removed in PHP6
{
	if (get_magic_quotes_gpc())
	{
		$_GET     = array_map('stripslashes_deep', $_GET);
		$_POST    = array_map('stripslashes_deep', $_POST);
//		$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		$_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
	}
	@set_magic_quotes_runtime(0);
}


// Security: SQL and Script Injection protection (Filters on GET, POST)
function test_sql_and_script_inject($val)
{
	$sql_inj = 0;
	$sql_inj += preg_match('/delete[\s]+from/i', $val);
	$sql_inj += preg_match('/create[\s]+table/i', $val);
	$sql_inj += preg_match('/update.+set.+=/i', $val);
	$sql_inj += preg_match('/insert[\s]+into/i', $val);
	$sql_inj += preg_match('/select.+from/i', $val);
	$sql_inj += preg_match('/<script/i', $val);
	return $sql_inj;
}
function analyse_sql_and_script(&$var)
{
	if (is_array($var))
	{
		$result = array();
		foreach ($var as $key => $value)
		{
			if (test_sql_and_script_inject($key) > 0)
			{
				print 'Access refused by SQL/Script injection protection in main.inc.php';
				exit;
			}
			else
			{
				if (analyse_sql_and_script($value))
				{
					$var[$key] = $value;
				}
				else
				{
					print 'Access refused by SQL/Script injection protection in main.inc.php';
					exit;
				}
			}
		}
		return true;
	}
	else
	{
		return (test_sql_and_script_inject($var) <= 0);
	}
}
analyse_sql_and_script($_GET);
analyse_sql_and_script($_POST);

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Add real path in session name
$realpath='';
if ( preg_match('/^([^.]+)\/htdocs\//i', realpath($_SERVER["SCRIPT_FILENAME"]), $regs))	$realpath = isset($regs[1])?$regs[1]:'';

// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].$realpath);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].$realpath);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects
require_once("master.inc.php");

// Force HTTPS if required ($conf->file->main_force_https is 0/1 or https dolibarr root url)
if (! empty($conf->file->main_force_https))
{
	$newurl='';
	if ($conf->file->main_force_https == '1')
	{
		if (! empty($_SERVER["SCRIPT_URI"]))	// If SCRIPT_URI supported by server
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
				$uri=preg_replace('/^http(s?):\/\//i','',$dolibarr_main_url_root);
				$val=explode('/',$uri);	// $val[0] contains domain name and port
				$newurl='https://'.$val[0].$_SERVER["REQUEST_URI"];
			}
		}
	}
	else
	{
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


// Chargement des includes complementaires de presentation
if (! defined('NOREQUIREMENU')) require_once(DOL_DOCUMENT_ROOT ."/core/class/menu.class.php");			// Need 10ko memory (11ko in 2.2)
if (! defined('NOREQUIREHTML')) require_once(DOL_DOCUMENT_ROOT ."/core/class/html.form.class.php");	// Need 660ko memory (800ko in 2.2)
if (! defined('NOREQUIREAJAX') && $conf->use_javascript_ajax) require_once(DOL_DOCUMENT_ROOT.'/lib/ajax.lib.php');	// Need 22ko memory
//stopwithmem();

// If install or upgrade process not done or not completely finished, we call the install page.
if (! empty($conf->global->MAIN_NOT_INSTALLED) || ! empty($conf->global->MAIN_NOT_UPGRADED))
{
	dol_syslog("main.inc: A previous install or upgrade was not complete. Redirect to install page.", LOG_WARNING);
	Header("Location: ".DOL_URL_ROOT."/install/index.php");
	exit;
}
// If an upgrade process is required, we call the install page.
if ((! empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && ($conf->global->MAIN_VERSION_LAST_UPGRADE != DOL_VERSION))
|| (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && ! empty($conf->global->MAIN_VERSION_LAST_INSTALL) && ($conf->global->MAIN_VERSION_LAST_INSTALL != DOL_VERSION)))
{
	require_once(DOL_DOCUMENT_ROOT ."/lib/admin.lib.php");
	$dolibarrversionlastupgrade=preg_split('/[.-]/',$conf->global->MAIN_VERSION_LAST_UPGRADE);
	$dolibarrversionprogram=preg_split('/[.-]/',DOL_VERSION);
	if (versioncompare($dolibarrversionprogram,$dolibarrversionlastupgrade) > 0)	// Programs have a version higher than database
	{
		dol_syslog("main.inc: database version ".$conf->global->MAIN_VERSION_LAST_UPGRADE." is lower than programs version ".DOL_VERSION.". Redirect to install page.", LOG_WARNING);
		Header("Location: ".DOL_URL_ROOT."/install/index.php");
		exit;
	}
}

// Creation of a token against CSRF vulnerabilities
if (! defined('NOTOKENRENEWAL'))
{
	$token = md5(uniqid(mt_rand(),TRUE)); // Genere un hash d'un nombre aleatoire
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
			dol_syslog("Invalid token in ".$_SERVER['HTTP_REFERER'].", action=".$_POST['action'].", _POST['token']=".$_POST['token'].", _SESSION['token']=".$_SESSION['token'],LOG_WARNING);
			//print 'Unset POST by CSRF protection in main.inc.php.';	// Do not output anything because this create problems when using the BACK button on browsers.
			unset($_POST);
		}
	}
}

// Disable modules (this must be after session_start and after conf has been loaded)
if (! empty($_GET["disablemodules"]))  $_SESSION["disablemodules"]=$_GET["disablemodules"];
if (! empty($_POST["disablemodules"])) $_SESSION["disablemodules"]=$_POST["disablemodules"];
if (! empty($_SESSION["disablemodules"]))
{
	$disabled_modules=explode(',',$_SESSION["disablemodules"]);
	foreach($disabled_modules as $module)
	{
		if ($module) $conf->$module->enabled=false;
	}
}

// Init Smarty (used by some modules like multicompany)
if (sizeof($conf->need_smarty) > 0)
{
	// Usage of const in conf.php file (deprecated) can overwrite default dir.
	$dolibarr_smarty_libs_dir=DOL_DOCUMENT_ROOT.'/includes/smarty/libs/';
	$dolibarr_smarty_compile=DOL_DATA_ROOT.'/smarty/templates/temp';
	$dolibarr_smarty_cache=DOL_DATA_ROOT.'/smarty/cache/temp';

	// Create directory if not exist
	if (! is_dir($dolibarr_smarty_compile)) create_exdir($dolibarr_smarty_compile);
	if (! is_dir($dolibarr_smarty_cache))	create_exdir($dolibarr_smarty_cache);

	$smarty_libs = $dolibarr_smarty_libs_dir. "Smarty.class.php";
	if (@include_once($smarty_libs))
	{
		$smarty = new Smarty();
		$smarty->compile_dir = $dolibarr_smarty_compile;
		$smarty->cache_dir = $dolibarr_smarty_cache;
	}
	else
	{
		dol_print_error('',"Library Smarty ".$smarty_libs." not found.");
	}
}

// Init Smartphone (for dev only)
if ($conf->global->MAIN_FEATURES_LEVEL == 2 && isset($conf->browser->phone))
{
	include_once(DOL_DOCUMENT_ROOT."/core/class/smartphone.class.php");

	$smartphone = new Smartphone($db);
	$smartphone->phone = $conf->browser->phone;
}

/*
 * Phase authentication / login
 */
$login='';
if (! defined('NOLOGIN'))
{
	// $authmode lists the different means of identification to be tested in order of preference.
	// Example: 'http'
	// Example: 'dolibarr'
	// Example: 'ldap'
	// Example: 'http,forceuser'

	// Authentication mode
	if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication='http,dolibarr';
	// Authentication mode: forceuser
	if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user='auto';

	// Set authmode
	$authmode=explode(',',$dolibarr_main_authentication);

	// No authentication mode
	if (! sizeof($authmode) && empty($conf->login_method_modules))
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
		// It is not already authenticated, it requests the login / password

		// Verification security graphic code
		if ($test && isset($_POST["username"]) && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/artichow/Artichow.cfg.php';
			require_once ARTICHOW."/AntiSpam.class.php";

			// It creates an anti-spam object
			$object = new AntiSpam();

			// Verifie code
			if (! $object->check('dol_antispam_value',$_POST['code'],true))
			{
				dol_syslog('Bad value for code, connexion refused');
				$langs->load('main');
				$langs->load('other');

				$user->trigger_mesg='ErrorBadValueForCode - login='.$_POST["username"];
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadValueForCode");
				$test=false;

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($db);
				$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
				if ($result < 0) { $error++; }
				// Fin appel triggers
			}
		}

		// Validation of third party module login method
		if (is_array($conf->login_method_modules) && !empty($conf->login_method_modules))
		{
			$login = getLoginMethod();
			if ($login)	$test=false;
		}

		// Validation tests user / password
		// If ok, the variable will be initialized login
		// If error, we will put error message in session under the name dol_loginmesg
		$goontestloop=false;
		if (isset($_SERVER["REMOTE_USER"]) && in_array('http',$authmode)) $goontestloop=true;
		if (isset($_POST["username"])) $goontestloop=true;

		if ($test && $goontestloop)
		{
			foreach($authmode as $mode)
			{
				if ($test && $mode && ! $login)
				{
					$authfile=DOL_DOCUMENT_ROOT.'/includes/login/functions_'.$mode.'.php';
					$result=include_once($authfile);
					if ($result)
					{
						// Call function to check user/password
						$usertotest=$_POST["username"];
						$passwordtotest=$_POST["password"];
						$function='check_user_password_'.$mode;
						$login=$function($usertotest,$passwordtotest);
						if ($login)
						{
							$test=false;
							$conf->authmode=$mode;	// This properties is defined only when logged
						}
					}
					else
					{
						dol_syslog("Authentification ko - failed to load file '".$authfile."'",LOG_ERR);
						sleep(1);
						$langs->load('main');
						$langs->load('other');
						$_SESSION["dol_loginmesg"]=$langs->trans("ErrorFailedToLoadLoginFileForMode",$mode);
					}
				}
			}

			if (! $login)
			{
				dol_syslog('Bad password, connexion refused',LOG_DEBUG);
				$langs->load('main');
				$langs->load('other');

				// Bad password. No authmode has found a good password.
				$user->trigger_mesg=$langs->trans("ErrorBadLoginPassword").' - login='.$_POST["username"];
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($db);
				$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
				if ($result < 0) { $error++; }
				// Fin appel triggers
			}
		}

		// End test login / passwords
		if (! $login)
		{
			// We show login page
			include_once(DOL_DOCUMENT_ROOT."/lib/security.lib.php");
			if (! is_object($langs)) // This can occurs when calling page with NOREQUIRETRAN defined
			{
                include_once(DOL_DOCUMENT_ROOT."/core/class/translate.class.php");
				$langs=new Translate("",$conf);
			}
			dol_loginfunction($langs,$conf,$mysoc);
			exit;
		}

		$resultFetchUser=$user->fetch('',$login);
		if ($resultFetchUser <= 0)
		{
			dol_syslog('User not found, connexion refused');
			session_destroy();
			session_name($sessionname);
			session_start();

			if ($resultFetchUser == 0)
			{
				$langs->load('main');
				$langs->load('other');

				$user->trigger_mesg='ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);
			}
			if ($resultFetchUser < 0)
			{
				$user->trigger_mesg=$user->error;
				$_SESSION["dol_loginmesg"]=$user->error;
			}

			// Call triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
			if ($result < 0) { $error++; }
			// End call triggers

			header('Location: '.DOL_URL_ROOT.'/index.php');
			exit;
		}
	}
	else
	{
		// It is already in a session
		$login=$_SESSION["dol_login"];
		$resultFetchUser=$user->fetch('',$login);
		dol_syslog("This is an already logged session. _SESSION['dol_login']=".$login);

		if ($resultFetchUser <= 0)
		{
			// Account has been removed after login
			dol_syslog("Can't load user even if session logged. _SESSION['dol_login']=".$login, LOG_WARNING);
			session_destroy();
			session_name($sessionname);
			session_start();

			if ($resultFetchUser == 0)
			{
				$langs->load('main');
				$langs->load('other');

				$user->trigger_mesg='ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);
			}
			if ($resultFetchUser < 0)
			{
				$user->trigger_mesg=$user->error;
				$_SESSION["dol_loginmesg"]=$user->error;
			}

			// Call triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,(isset($_POST["entity"])?$_POST["entity"]:0));
			if ($result < 0) { $error++; }
			// End call triggers

			header('Location: '.DOL_URL_ROOT.'/index.php');
			exit;
		}
		else
		{
		    if (! empty($conf->MAIN_ACTIVATE_UPDATESESSIONTRIGGER))
		    {
    			// Call triggers
    			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    			$interface=new Interfaces($db);
    			$result=$interface->run_triggers('USER_UPDATE_SESSION',$user,$user,$langs,$conf,$conf->entity);
    			if ($result < 0) { $error++; }
    			// End call triggers
		    }
		}
	}

	// Is it a new session ?
	if (! isset($_SESSION["dol_login"]))
	{
		$error=0;

		// New session for this login
		$_SESSION["dol_login"]=$user->login;
		$_SESSION["dol_authmode"]=$conf->authmode;
		$_SESSION["dol_company"]=$conf->global->MAIN_INFO_SOCIETE_NOM;
		if ($conf->multicompany->enabled) $_SESSION["dol_entity"]=$conf->entity;
		dol_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"].' Session id='.session_id());

		$db->begin();

		$user->update_last_login_date();

		// Call triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface=new Interfaces($db);
		$result=$interface->run_triggers('USER_LOGIN',$user,$user,$langs,$conf,$_POST["entity"]);
		if ($result < 0) { $error++; }
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
		if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY) && !empty($conf->global->MAIN_MULTICOMPANY_COOKIE) && isset($_POST["entity"]))
		{
			include_once(DOL_DOCUMENT_ROOT."/core/class/cookie.class.php");

			$entity = $_SESSION["dol_login"].'|'.$_POST["entity"];
			$entityCookieName = 'DOLENTITYID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].$realpath);
			// TTL : is defined in the config page multicompany
			$ttl = (! empty($conf->global->MAIN_MULTICOMPANY_COOKIE_TTL) ? $conf->global->MAIN_MULTICOMPANY_COOKIE_TTL : time()+60*60*8 );
			// Cryptkey : will be created randomly in the config page multicompany
			$cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

			$entityCookie = new DolCookie($cryptkey);
			$entityCookie->_setCookie($entityCookieName, $entity, $ttl);
		}

		// Module webcalendar
		if (! empty($conf->webcal->enabled) && $user->webcal_login != "")
		{
			$domain='';

			// Creation of a cookie to save login
			$cookiename='webcalendar_login';
			if (! isset($_COOKIE[$cookiename]))
			{
				setcookie($cookiename, $user->webcal_login, 0, "/", $domain, 0);
			}
			// Creation of a cookie to save session
			$cookiename='webcalendar_session';
			if (! isset($_COOKIE[$cookiename]))
			{
				setcookie($cookiename, 'TODO', 0, "/", $domain, 0);
			}
		}

		// Module Phenix
		if (! empty($conf->phenix->enabled) && $user->phenix_login != "" && $conf->phenix->cookie)
		{
			// Creation du cookie permettant la connexion automatique, valide jusqu'a la fermeture du browser
			if (!isset($_COOKIE[$conf->phenix->cookie]))
			{
				setcookie($conf->phenix->cookie, $user->phenix_login.":".$user->phenix_pass_crypted.":1", 0, "/", "", 0);
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
	 * Overwrite configs global par configs perso
	 * ------------------------------------------
	 */
	// Set liste_limit
	if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT))	// Can be 0
	{
		$conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT;
	}
	if (isset($user->conf->PRODUIT_LIMIT_SIZE))		// Can be 0
	{
		$conf->product->limit_size = $user->conf->PRODUIT_LIMIT_SIZE;
	}
	// Replace conf->css by personalized value
	if (isset($user->conf->MAIN_THEME) && $user->conf->MAIN_THEME)
	{
		$conf->theme=$user->conf->MAIN_THEME;
		$conf->css  = "/theme/".$conf->theme."/style.css.php";
	}
	// Set javascript option
    if (empty($_GET["nojs"]))   // If javascript was not disabled on URL
    {
    	if (! empty($user->conf->MAIN_DISABLE_JAVASCRIPT))
    	{
    		$conf->use_javascript_ajax=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
    	}
    }
    else $conf->use_javascript_ajax=0;
}


if (! defined('NOREQUIRETRAN'))
{
	if (empty($_GET["lang"]))	// If language was not forced on URL
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
	else	// If language was forced on URL
	{
		$langs->setDefaultLang($_GET["lang"]);
	}
}

// Case forcing style from url
if (! empty($_GET["theme"]))
{
	$conf->theme=$_GET["theme"];
	$conf->css  = "/theme/".$conf->theme."/style.css.php";
}

// Define menu manager to use
if (empty($user->societe_id))    // If internal user or not defined
{
	$conf->top_menu=$conf->global->MAIN_MENU_BARRETOP;
	$conf->smart_menu=$conf->global->MAIN_MENU_SMARTPHONE;
	// For backward compatibility
    if ($conf->top_menu == 'eldy.php') $conf->top_menu='eldy_backoffice.php';
	if ($conf->top_menu == 'rodolphe.php') $conf->top_menu='eldy_backoffice.php';
}
else                        // If external user
{
	$conf->top_menu=$conf->global->MAIN_MENUFRONT_BARRETOP;
	$conf->smart_menu=$conf->global->MAIN_MENUFRONT_SMARTPHONE;
	// For backward compatibility
    if ($conf->top_menu == 'eldy.php') $conf->top_menu='eldy_frontoffice.php';
	if ($conf->top_menu == 'rodolphe.php') $conf->top_menu='eldy_frontoffice.php';
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
		dol_syslog ("Authentification ko as login is disabled");
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


// Functions


/**
 *		\brief   	Show HTML header HTML + BODY + Top menu + left menu + DIV
 * 		\param   	head			Add optionnal head lines
 * 		\param   	title
 * 		\param      help_url
 * 		\param   	target
 * 		\param   	disablejs
 * 		\param   	disablehead
 * 		\param   	arrayofjs
 * 		\param   	arrayofcss
 */
if (! function_exists("llxHeader"))
{
	function llxHeader($head = '', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
	{
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers
		top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
		left_menu('', $help_url);
		main_area();
	}
}


/**
 *  Show HTML header
 */
function top_httphead()
{
    global $conf;

    //header("Content-type: text/html; charset=UTF-8");
    header("Content-type: text/html; charset=".$conf->file->character_set_client);

    // On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
    if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }
}


/**
 *  Show HTML header
 *  @param      head    	Optionnal head lines
 *  @param      title   	Web page title
 *	@param		disablejs	Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *	@param		disablehead	Do not output head section
 *	@param		arrayofjs	Array of js files to add in header
 *	@param		arrayofcss	Array of css files to add in header
 */
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $user, $conf, $langs, $db;

	top_httphead();

	if (empty($conf->css)) $conf->css = '/theme/eldy/style.css.php';	// If not defined, eldy by default

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	if (empty($disablehead))
	{
		print "<head>\n";

		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->file->character_set_client."\">\n";

		// Displays meta
		print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
		print '<meta name="author" content="Dolibarr Development Team">'."\n";
		print '<link rel="shortcut icon" type="image/x-icon" href="'.DOL_URL_ROOT.'/favicon.ico">'."\n";

		// Displays title
		$appli='Dolibarr';
		if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

		if ($title) print '<title>'.$appli.' - '.$title.'</title>';
		else print "<title>".$appli."</title>";
		print "\n";

        if (! defined('DISABLE_JQUERY'))
        {
            print '<!-- Includes for JQuery (Ajax library) -->'."\n";
            $jquerytheme = 'smoothness';
            if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
            print '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui-1.8.5.custom.css" type="text/css" />'."\n";
            print '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/tooltip/jquery.tooltip.css" type="text/css" />'."\n";
        }

        print '<!-- Includes for Dolibarr, modules or specific pages-->'."\n";
        // Output style sheets (optioncss='print' or '')
		print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang.'&theme='.$conf->theme.(! empty($_GET["optioncss"])?'&optioncss='.$_GET["optioncss"]:'').'">'."\n";
		// CSS forced by modules (relative url starting with /)
		if (is_array($conf->css_modules))
		{
			foreach($conf->css_modules as $cssfile)
			{	// cssfile is an absolute path
				print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.$cssfile.'?lang='.$langs->defaultlang.'&theme='.$conf->theme.(! empty($_GET["optioncss"])?'&optioncss='.$_GET["optioncss"]:'').'">'."\n";
			}
		}
		// CSS forced by page in top_htmlhead call (relative url starting with /)
		if (is_array($arrayofcss))
		{
			foreach($arrayofcss as $cssfile)
			{
				print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.$cssfile.'?lang='.$langs->defaultlang.'&theme='.$conf->theme.(! empty($_GET["optioncss"])?'&optioncss='.$_GET["optioncss"]:'').'">'."\n";
			}
		}

		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="top" title="'.$langs->trans("Home").'" href="'.(DOL_URL_ROOT?DOL_URL_ROOT:'/').'">'."\n";
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) print '<link rel="author" title="Dolibarr Development Team" href="http://www.dolibarr.org">'."\n";

		// Output standard javascript links
		if (! $disablejs && $conf->use_javascript_ajax)
		{
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_head.js"></script>'."\n";

			// Other external js
			require_once DOL_DOCUMENT_ROOT.'/lib/ajax.lib.php';

			$mini='';$ext='.js';
			if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x01)) { $mini='_mini'; $ext='.jgz'; }	// mini='_mini', ext='.gz'

			// JQuery. Must be before other includes (prototype/scriptaculous)
			print '<!-- Includes for JQuery -->'."\n";
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-1.4.3.min'.$ext.'"></script>'."\n";
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui-1.8.5.custom.min'.$ext.'"></script>'."\n";
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd_0_5'.$ext.'"></script>'."\n";
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tooltip/jquery.tooltip.min'.$ext.'"></script>'."\n";
			
			if ($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)
			{
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/layout/jquery.layout-latest'.$ext.'"></script>'."\n";
			}

            // This one is required for some Ajax features
			if (! defined('DISABLE_PROTOTYPE') && $conf->global->MAIN_USE_PROTOTYPE)
			{
                print '<!-- Includes for Prototype -->'."\n";
                print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype'.$mini.$ext.'"></script>'."\n";
			}
		}

		// Output module javascript
		if (is_array($arrayofjs))
		{
			print '<!-- Includes specific to page -->'."\n";
			foreach($arrayofjs as $jsfile)
			{
				if (! preg_match('/^\//',$jsfile)) $jsfile='/'.$jsfile;	// For backward compatibility
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.$jsfile.'"></script>'."\n";
			}
		}

		// Define tradMonths javascript array (we define this in datapicker AND in parent page to avoid errors with IE8)
		$tradTemp=array($langs->trans("January"),
		$langs->trans("February"),
		$langs->trans("March"),
		$langs->trans("April"),
		$langs->trans("May"),
		$langs->trans("June"),
		$langs->trans("July"),
		$langs->trans("August"),
		$langs->trans("September"),
		$langs->trans("October"),
		$langs->trans("November"),
		$langs->trans("December")
		);
		print '<script type="text/javascript">';
		print 'var tradMonths = [';
		foreach($tradTemp as $val)
		{
			print '"'.addslashes($val).'",';
		}
		print '""];';
		print '</script>'."\n";

		if (! empty($head)) print $head."\n";
		if (! empty($conf->global->MAIN_HTML_HEADER)) print $conf->global->MAIN_HTML_HEADER."\n";

		print "</head>\n\n";
	}

	$conf->headerdone=1;	// To tell header was output
}


/**
 *  \brief      Show an HTML header + a BODY + The top menu bar
 *  \param      head    	Lines in the HEAD
 *  \param      title   	Title of web page
 *  \param      target  	Target to use in menu links
 *	\param		disablejs	Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *	\param		disablehead	Do not output head section
 *	\param		arrayofjs	Array of js files to add in header
 *	\param		arrayofcss	Array of css files to add in header
 */
function top_menu($head, $title='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $user, $conf, $langs, $db, $dolibarr_main_authentication;

	$html=new Form($db);

	if (! $conf->top_menu)  $conf->top_menu ='eldy_backoffice.php';


	// For backward compatibility with old modules
	if (empty($conf->headerdone)) top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
	
	if ($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)
	{
		print '<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery("body").layout( layoutSettings );
				});
				var layoutSettings = {
					defaults: {
						size: "auto",
						//minSize: 50,
						paneClass: "pane",
						//resizerClass: "resizer",
						//togglerClass: "toggler",
						//buttonClass: "button",
						//contentSelector: ".content",
						//contentIgnoreSelector: "span",
						togglerLength_open:	35,
						togglerLength_closed: 35,
						hideTogglerOnSlide:	true,
						togglerTip_open: "Close This Pane",
						togglerTip_closed: "Open This Pane",
						resizerTip:	"Resize This Pane",
						//	effect defaults - overridden on some panes
						fxName:	"slide",
						fxSpeed_open: 750,
						fxSpeed_close: 1500,
						fxSettings_open: { easing: "easeInQuint" },
						fxSettings_close: { easing: "easeOutQuint" }
					},
					north: {
						paneClass: "none",
						resizerClass: "none",
						togglerClass: "none",
						spacing_open: 0,
						togglerLength_open:	0,
						togglerLength_closed: -1,
						resizable: false,
						slidable: false,
						fxName:	"none"
					},
					center: {
						// TODO Problem with right scrollbar
						paneClass:    "ui-layout-pane",
						//resizerClass: "ui-layout-resizer",
						//togglerClass: "ui-layout-toggler",
						paneSelector: "#mainContent"
					}
				}
    		</script>';
	}

	print '<body id="mainbody">';

    // Wrapper to show tooltips
    print '<script type="text/javascript">
                jQuery(function() {
                    jQuery(".classfortooltip").tooltip({
                        track: true,
                        delay: 0,
                        showURL: false,
/*                        extraClass: "pretty fancy",
                        fixPNG: true,*/
                        positionLeft: false,
                        bodyHandler: function() {
                        /*console.log(jQuery(this).attr("tooltipText"));*/
                        return jQuery(this).attr("tooltipText"); }
                    });
                });
    </script>';

	/*
	 * Top menu
	 */
    $top_menu=$conf->top_menu;
    if (GETPOST('top_menu')) $top_menu=GETPOST('top_menu');

	// Load the top menu manager
	$result=@include_once(DOL_DOCUMENT_ROOT ."/includes/menus/standard/".$top_menu);
	if (! $result)	// If failed to include, we try with standard
	{
		$top_menu='eldy_backoffice.php';
		include_once(DOL_DOCUMENT_ROOT ."/includes/menus/standard/".$top_menu);
	}

    print "\n".'<!-- Start top horizontal menu '.$top_menu.' -->'."\n";
    
    print '<div class="ui-layout-north"> <!-- Begin top layout -->'."\n";

    print '<div id="tmenu_tooltip" class="tmenu">'."\n";

    // Show menu
    $menutop = new MenuTop($db);
	$menutop->atarget=$target;
	$menutop->showmenu();

	// Select entity
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
	{
		if ($user->admin && ! $user->entity)
		{
			$res=@include_once(DOL_DOCUMENT_ROOT.'/multicompany/class/actions_multicompany.class.php');
			if ($res)
			{
				//$mc = new ActionsMulticompany($db);
				$entitytext = img_object('', 'globe','entity');
				$entityhtmltext = 'EntityName';
				print $html->textwithtooltip('',$entityhtmltext,2,1,$entitytext);
				//$select_entity = '<div class="loginSelectEntity">'.$mc->select_entities($conf->entity).'</div>';
				//if ($mc->numEntity >= 1) print $select_entity;
			}
		}
	}

	// Link to login card
	$loginhtmltext=''; $logintext='';
	$logintext='<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'"';
	$logintext.=$menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
	$logintext.='>'.$user->login.'</a>';
	$loginhtmltext.='<u>'.$langs->trans("User").'</u>';
	$loginhtmltext.='<br><b>'.$langs->trans("Name").'</b>: '.$user->getFullName($langs);
	$loginhtmltext.='<br><b>'.$langs->trans("Login").'</b>: '.$user->login;
	$loginhtmltext.='<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	$type=($user->societe_id?$langs->trans("External"):$langs->trans("Internal"));
	if ($user->societe_id)
	{
		$thirdpartystatic=new Societe($db);
		$thirdpartystatic->fetch($user->societe_id);
		$type.=' ('.$langs->trans("Company").': '.$thirdpartystatic->name.')';
	}
	$loginhtmltext.='<br><b>'.$langs->trans("Type").'</b>: '.$type;
	$loginhtmltext.='<br>';
	$loginhtmltext.='<br><u>'.$langs->trans("Connection").'</u>';
	if ($conf->global->MAIN_MODULE_MULTICOMPANY) $loginhtmltext.='<br><b>'.$langs->trans("ConnectedOnMultiCompany").'</b>: '.$conf->entity.' (user entity '.$user->entity.')';
	$loginhtmltext.='<br><b>'.$langs->trans("ConnectedSince").'</b>: '.dol_print_date($user->datelastlogin,"dayhour");
	$loginhtmltext.='<br><b>'.$langs->trans("PreviousConnexion").'</b>: '.dol_print_date($user->datepreviouslogin,"dayhour");
	$loginhtmltext.='<br><b>'.$langs->trans("AuthenticationMode").'</b>: '.$_SESSION["dol_authmode"];
	$loginhtmltext.='<br><b>'.$langs->trans("CurrentTheme").'</b>: '.$conf->theme;
	$s=picto_from_langcode($langs->getDefaultLang());
	$loginhtmltext.='<br><b>'.$langs->trans("CurrentUserLanguage").'</b>: '.($s?$s.' ':'').$langs->getDefaultLang();
	$loginhtmltext.='<br><b>'.$langs->trans("Browser").'</b>: '.$conf->browser->name.' ('.$_SERVER['HTTP_USER_AGENT'].')';
	if (! empty($conf->browser->phone)) $loginhtmltext.='<br><b>'.$langs->trans("Phone").'</b>: '.$conf->browser->phone;
	if (! empty($_SESSION["disablemodules"])) $loginhtmltext.='<br><b>'.$langs->trans("DisabledModules").'</b>: <br>'.join('<br>',explode(',',$_SESSION["disablemodules"]));

	// Link info
	$logouthtmltext=''; $logouttext='';
	$logouthtmltext=$langs->trans("Logout").'<br>';
	//$logouthtmltext.="<br>";
	if ($_SESSION["dol_authmode"] != 'forceuser'
	&& $_SESSION["dol_authmode"] != 'http')
	{
		$logouttext.='<a href="'.DOL_URL_ROOT.'/user/logout.php"';
		$logouttext.=$menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
		$logouttext.='>';
		$logouttext.='<img class="login" border="0" width="14" height="14" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
		$logouttext.=' alt="'.dol_escape_htmltag($langs->trans("Logout")).'" title=""';
		$logouttext.='>';
		$logouttext.='</a>';
	}
	else
	{
		$logouttext.='<img class="login" border="0" width="14" height="14" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
		$logouttext.=' alt="'.dol_escape_htmltag($langs->trans("Logout")).'" title=""';
		$logouttext.='>';
	}

	print $html->textwithtooltip('',$loginhtmltext,2,1,$logintext);

	print $html->textwithtooltip('',$logouthtmltext,2,1,$logouttext);

	// Link to print main content area
	if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->browser->phone))
	{
		$text ='<a href="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'&optioncss=print" target="_blank">';
		$text.='<img class="printer" border="0" width="14" height="14" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/printer.png"';
		$text.=' title="" alt="">';
		$text.='</a>';
		print $html->textwithtooltip('',$langs->trans("PrintContentArea"),2,1,$text);
	}

	print "\n</div>\n<!-- End top horizontal menu -->\n";
	
	print "</div><!-- End top layout -->\n";
	
	if (! $conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)
	{
		print '<table width="100%" class="notopnoleftnoright" summary="leftmenutable" id="undertopmenu"><tr>';
	}
}


/**
 *  \brief      Show left menu bar
 *  \param      menu_array_before 	Table of menu entries to show before entries of menu handler
 *  \param      helppagename    	Name of wiki page for help ('' by default).
 * 				                    Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 * 									           For other external page: http://server/url
 *  \param      moresearchform     	Search Form Permanent Supplemental
 *  \param      menu_array_after    Table of menu entries to show after entries of menu handler
 */
function left_menu($menu_array_before, $helppagename='', $moresearchform='', $menu_array_after='')
{
	global $user, $conf, $langs, $db;

	$searchform='';
	$bookmarks='';

	//    print '<div class="vmenuplusfiche">'."\n";
	
	print "\n".'<div class="ui-layout-west"> <!-- Begin left layout -->'."\n";
	
	if (! $conf->global->MAIN_MENU_USE_JQUERY_LAYOUT) print '<td class="vmenu" valign="top">';

	print "\n";


	// Define $searchform
	if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_SOCIETE && $user->rights->societe->lire)
	{
		$langs->load("companies");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/societe/societe.php', DOL_URL_ROOT.'/societe/societe.php',
		img_object('','company').' '.$langs->trans("ThirdParties"), 'soc', 'socname');
	}

	if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT && $user->rights->societe->lire)
	{
		$langs->load("companies");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/contact/index.php', DOL_URL_ROOT.'/contact/index.php',
		img_object('','contact').' '.$langs->trans("Contacts"), 'contact', 'contactname');
	}

	if ((($conf->product->enabled && $user->rights->produit->lire) || ($conf->service->enabled && $user->rights->service->lire))
	&& $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE)
	{
		$langs->load("products");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/product/liste.php', DOL_URL_ROOT.'/product/liste.php',
		img_object('','product').' '.$langs->trans("Products")."/".$langs->trans("Services"), 'products', 'sall');
	}

	if ($conf->adherent->enabled && $conf->global->MAIN_SEARCHFORM_ADHERENT && $user->rights->adherent->lire)
	{
		$langs->load("members");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/liste.php', DOL_URL_ROOT.'/adherents/liste.php',
		img_object('','user').' '.$langs->trans("Members"), 'member', 'sall');
	}

	// Define $bookmarks
	if ($conf->bookmark->enabled && $user->rights->bookmark->lire)
	{
		include_once (DOL_DOCUMENT_ROOT.'/bookmarks/bookmarks.lib.php');
		$langs->load("bookmarks");

		$bookmarks=printBookmarksList($db, $langs);
	}



    //$left_menu=$conf->left_menu;
    $left_menu=$conf->top_menu;
    if (GETPOST('left_menu')) $left_menu=GETPOST('left_menu');

    // Load the left menu manager
	$result=@include_once(DOL_DOCUMENT_ROOT ."/includes/menus/standard/".$left_menu);
	if (! $result)
	{
		$left_menu='eldy_backoffice.php';
		include_once(DOL_DOCUMENT_ROOT ."/includes/menus/standard/".$left_menu);
	}

    // Left column
    print '<!-- Begin left vertical menu '.$left_menu.' -->'."\n";
    print '<div class="vmenu">'."\n";

	$menuleft=new MenuLeft($db,$menu_array_before,$menu_array_after);
	$menuleft->showmenu(); // output menu_array and menu found in database


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
			print sprintf($helpbaseurl,urlencode(html_entity_decode($helppage)));
			print '">';
			print img_picto('',DOL_URL_ROOT.'/theme/common/helpdoc.png','',1).' ';
			print $langs->trans($mode == 'wiki' ? 'OnlineHelp': 'Help');
			//if ($mode == 'wiki') print ' ('.dol_trunc(strtr($helppage,'_',' '),8).')';
			print '</a>';
			print '</div>';
		}
	}

    // Link to bugtrack
	if (! empty($conf->global->MAIN_SHOW_BUGTRACK_LINK))
	{
		$bugbaseurl='http://savannah.nongnu.org/bugs/?';
		$bugbaseurl.='func=additem&group=dolibarr&privacy=1&';
		$bugbaseurl.="&details=";
		$bugbaseurl.=urlencode("\n\n\n\n\n-------------\n");
		$bugbaseurl.=urlencode($langs->trans("Version").": ".DOL_VERSION."\n");
		$bugbaseurl.=urlencode($langs->trans("Server").": ".$_SERVER["SERVER_SOFTWARE"]."\n");
		$bugbaseurl.=urlencode($langs->trans("Url").": ".$_SERVER["REQUEST_URI"]."\n");
		print '<div class="help"><a class="help" target="_blank" href="'.$bugbaseurl.'">'.$langs->trans("FindBug").'</a></div>';
	}
	print "\n";
	print "</div>\n";
	print "<!-- End left vertical menu -->\n";

	print "\n";
	
	if (! $conf->global->MAIN_MENU_USE_JQUERY_LAYOUT) print '</td>';
	
	print '</div> <!-- End left layout -->'."\n";

	print '<!-- End of left column, begin right area -->'."\n";
	//	    print '</div>'."\n";
	//		print '<div class="vmenuplusfiche">'."\n";
}

/**
 *  Begin main area
 */
function main_area()
{
	global $conf, $langs;
	
	print '<div id="mainContent"><div class="ui-layout-center"> <!-- begin main layout -->'."\n";
	
	if ($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT) print '<table width="100%" class="notopnoleftnoright" summary="leftmenutable" id="undertopmenu"><tr>';
	
	print '<td valign="top">'."\n";
	
	print "\n";
	
	print '<div class="fiche"> <!-- begin main area -->'."\n";

	if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED)) print info_admin($langs->trans("WarningYouAreInMaintenanceMode",$conf->global->MAIN_ONLY_LOGIN_ALLOWED));
}


/**
 *  \brief   Return helpbaseurl, helppage and mode
 *  \param   helppagename		Page name (EN:xxx,ES:eee,FR:fff...)
 *  \param   langs				Language
 */
function getHelpParamFor($helppagename,$langs)
{
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
 *  \brief   Show a search area
 *  \param   urlaction          Url post
 *  \param   urlobject          Url of the link under the search box
 *  \param   title              Title search area
 *  \param   htmlmodesearch     'search'
 *  \param   htmlinputname      Field Name input form
 */
function printSearchForm($urlaction,$urlobject,$title,$htmlmodesearch='search',$htmlinputname)
{
	global $langs;
	$ret='';
	$ret.='<div class="menu_titre">';
	$ret.='<a class="vsmenu" href="'.$urlobject.'">';
	$ret.=$title.'</a><br>';
	$ret.='</div>';
	$ret.='<form action="'.$urlaction.'" method="post">';
	$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	$ret.='<input type="hidden" name="mode" value="search">';
	$ret.='<input type="hidden" name="mode-search" value="'.$htmlmodesearch.'">';
	$ret.='<input type="text" class="flat" name="'.$htmlinputname.'" size="10">&nbsp;';
	$ret.='<input type="submit" class="button" value="'.$langs->trans("Go").'">';
	$ret.="</form>\n";
	return $ret;
}

/**
 *   Return list of login method of third party module.
 *   @return	array
 */
function getLoginMethod()
{
	global $conf,$langs;

	$login = '';

	foreach($conf->login_method_modules as $dir)
	{
		// Check if directory exists
		if (!is_dir($dir)) continue;

		$handle=opendir($dir);

		while (($file = readdir($handle))!==false)
		{
			if (is_readable($dir.'/'.$file) && preg_match('/^functions_([^_]+)\.php/',$file,$reg))
			{
				$authfile = $dir.'/'.$file;
				$mode = $reg[1];

				$result=include_once($authfile);
				if ($result)
				{
					// Call function to check user/password
					$usertotest=$_POST["username"];
					$passwordtotest=$_POST["password"];
					$function='check_user_password_'.$mode;
					$login=$function($usertotest,$passwordtotest);
					if ($login)
					{
						$conf->authmode=$mode;	// This properties is defined only when logged
					}
				}
				else
				{
					dol_syslog("Authentification ko - failed to load file '".$authfile."'",LOG_ERR);
					sleep(1);
					$langs->load('main');
					$langs->load('other');
					$_SESSION["dol_loginmesg"]=$langs->trans("ErrorFailedToLoadLoginFileForMode",$mode);
				}
			}
		}
		closedir($handle);
	}
	return $login;
}

/**
 *		\brief   	Show HTML footer DIV + BODY + HTML
 *		\remarks	Close 2 div
 * 		\param   	foot    		A text to add in HTML generated page
 */
if (! function_exists("llxFooter"))
{
	function llxFooter($foot='')
	{
		global $conf, $langs, $dolibarr_auto_user, $micro_start_time;

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

		print "\n\n".'</div> <!-- end div class="fiche" -->'."\n";

		//    print "\n".'</div> <!-- end div class="vmenuplusfiche" -->'."\n";
		print "\n".'</td></tr></table> <!-- end right area -->'."\n";
		print '</div></div> <!-- end main layout -->'."\n";

		if (! empty($_SERVER['DOL_TUNING']))
		{
			$micro_end_time=dol_microtime_float(true);
			print "\n".'<script type="text/javascript">window.status="';
			if (! empty($conf->global->MEMCACHED_SERVER)) print 'MEMCACHED_SERVER='.$conf->global->MEMCACHED_SERVER.' - ';
			print 'MAIN_OPTIMIZE_SPEED='.(isset($conf->global->MAIN_OPTIMIZE_SPEED)?$conf->global->MAIN_OPTIMIZE_SPEED:'off');
			print ' - Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
			if (function_exists("memory_get_usage"))
			{
				print ' - Mem: '.memory_get_usage();
			}
			if (function_exists("xdebug_memory_usage"))
			{
				print ' - XDebug time: '.ceil(1000*xdebug_time_index()).' ms';
				print ' - XDebug mem: '.xdebug_memory_usage();
				print ' - XDebug mem peak: '.xdebug_peak_memory_usage();
			}
			if (function_exists("zend_loader_file_encoded"))
			{
				print ' - Zend encoded file: '.(zend_loader_file_encoded()?'yes':'no');
			}
			print '"</script>'."\n";

			// Add Xdebug coverage of code
			if (defined('XDEBUGCOVERAGE')) { var_dump(xdebug_get_code_coverage()); }
		}

		// If there is some logs in buffer to show
		if (sizeof($conf->logbuffer))
		{
			print "\n";
			print "<!-- Start of log output\n";
			//print '<div class="hidden">'."\n";
			foreach($conf->logbuffer as $logline)
			{
				print $logline."<br>\n";
			}
			//print '</div>'."\n";
			print "End of log output -->\n";
		}

		print "\n";
		if ($foot) print '<!-- '.$foot.' -->'."\n";

		if (! empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER."\n";

		print "</body>\n";
		print "</html>\n";
	}
}

?>