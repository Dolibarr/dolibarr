<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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

// For optionnal tuning. Enabled if environment variable DOL_TUNING is defined.
// A appeler avant tout. Fait l'equivalent de la fonction dol_microtime_float pas encore chargee.
$micro_start_time=0;
if (! empty($_SERVER['DOL_TUNING']))
{
	list($usec, $sec) = explode(" ", microtime());
	$micro_start_time=((float)$usec + (float)$sec);
	// Add Xdebug coverage of code
	//define('XDEBUGCOVERAGE',1);
	if (defined('XDEBUGCOVERAGE')) { xdebug_start_code_coverage(); }
}


// Forcage du parametrage PHP magic_quotes_gpc et nettoyage des parametres
// (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande, il faut juste faire addslashes au moment d'un insert/update.
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
		$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		$_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
	}
	@set_magic_quotes_runtime(0);
}


// Security: SQL and Script Injection protection (Filters on GET, POST)
function test_sql_and_script_inject($val)
{
	$sql_inj = 0;
	$sql_inj += eregi('delete[[:space:]]+from', $val);
	$sql_inj += eregi('create[[:space:]]+table', $val);
	$sql_inj += eregi('update.+set.+=', $val);
	$sql_inj += eregi('insert[[:space:]]+into', $val);
	$sql_inj += eregi('select.+from', $val);
	$sql_inj += eregi('<script', $val);
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

// Security: CSRF protection
// The test to do is to check if referrer ($_SERVER['HTTP_REFERER']) is same web site than Dolibarr ($_SERVER['HTTP_HOST']).
if (! defined('NOCSRFCHECK') && ! empty($_SERVER['HTTP_HOST']) && ! empty($_SERVER['HTTP_REFERER']) && ! eregi($_SERVER['HTTP_HOST'], $_SERVER['HTTP_REFERER']))
{
	//print 'HTTP_POST='.$_SERVER['HTTP_HOST'].' HTTP_REFERER='.$_SERVER['HTTP_REFERER'];
	print 'Access refused by CSRF protection in main.inc.php.';
	exit;
}

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects
require_once("master.inc.php");

// Check if HTTPS
if ($conf->file->main_force_https)
{
	if (! empty($_SERVER["SCRIPT_URI"]))	// If SCRIPT_URI supported by server
	{
		if (eregi('^http:',$_SERVER["SCRIPT_URI"]) && ! eregi('^https:',$_SERVER["SCRIPT_URI"]))	// If link is http
		{
			$newurl=eregi_replace('^http:','https:',$_SERVER["SCRIPT_URI"]);

			dol_syslog("main.inc: dolibarr_main_force_https is on, we make a redirect to ".$newurl);
			header("Location: ".$newurl);
			exit;
		}
	}
	else	// Check on HTTPS environment variable (Apache/mod_ssl only)
	{
		// $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
		if (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on')		// If link is http
		{
			$uri=eregi_replace('^http(s?)://','',$dolibarr_main_url_root);
			$val=split('/',$uri);
			$domaineport=$val[0];	// $domaineport contient nom domaine et port

			$newurl='https://'.$domaineport.$_SERVER["REQUEST_URI"];
			//print 'eee'.$newurl; 	exit;
			dol_syslog("main.inc: dolibarr_main_force_https is on, we make a redirect to ".$newurl);
			header("Location: ".$newurl);
			exit;
		}
	}
}


// Chargement des includes complementaires de presentation
if (! defined('NOREQUIREMENU')) require_once(DOL_DOCUMENT_ROOT ."/menu.class.php");			// Need 10ko memory (11ko in 2.2)
if (! defined('NOREQUIREHTML')) require_once(DOL_DOCUMENT_ROOT ."/html.form.class.php");	// Need 660ko memory (800ko in 2.2)
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
if (! empty($conf->global->MAIN_VERSION_LAST_UPGRADE) && ($conf->global->MAIN_VERSION_LAST_UPGRADE != DOL_VERSION))
{
	require_once(DOL_DOCUMENT_ROOT ."/lib/admin.lib.php");
	$dolibarrversionlastupgrade=split('[\.-]',$conf->global->MAIN_VERSION_LAST_UPGRADE);
	$dolibarrversionprogram=split('[\.-]',DOL_VERSION);
	if (versioncompare($dolibarrversionprogram,$dolibarrversionlastupgrade) > 0)	// Programs have a version higher than database
	{
		dol_syslog("main.inc: database version ".$conf->global->MAIN_VERSION_LAST_UPGRADE." is lower than programs version ".DOL_VERSION.". Redirect to install page.", LOG_WARNING);
		Header("Location: ".DOL_URL_ROOT."/install/index.php");
		exit;
	}
}

// Creation d'un jeton contre les failles CSRF
if (! defined('NOTOKENRENEWAL'))
{
	$token = md5(uniqid(mt_rand(),TRUE)); // Genere un hash d'un nombre aleatoire
	// roulement des jetons car cree a chaque appel
	if (isset($_SESSION['newtoken'])) $_SESSION['token'] = $_SESSION['newtoken'];
	$_SESSION['newtoken'] = $token;
}
if (! empty($conf->global->MAIN_SECURITY_CSRF))	// Check validity of token, only if not option enabled (this option breaks some features sometimes)
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
if (! empty($_REQUEST["disablemodules"])) $_SESSION["disablemodules"]=$_REQUEST["disablemodules"];
if (! empty($_SESSION["disablemodules"]))
{
	$disabled_modules=split(',',$_SESSION["disablemodules"]);
	foreach($disabled_modules as $module)
	{
		$conf->$module->enabled=false;
	}
}


/*
 * Phase authentication / login
 */

// $authmode contient la liste des differents modes d'identification a tester par ordre de preference.
// Example: 'http'
// Example: 'dolibarr'
// Example: 'ldap'
// Example: 'http,forceuser'

// Authentication mode
if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication='http,dolibarr';
// Authentication mode: forceuser
if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user='auto';

// Set authmode
$authmode=split(',',$dolibarr_main_authentication);

// No authentication mode
if (! sizeof($authmode))
{
	$langs->load('main');
	dol_print_error('',$langs->trans("ErrorConfigParameterNotDefined",'dolibarr_main_authentication'));
	exit;
}

// Si la demande du login a deja eu lieu, on le recupere depuis la session
// sinon appel du module qui realise sa demande.
// A l'issu de cette phase, la variable $login sera definie.
$login='';
$resultFetchUser='';
$test=true;
if (! isset($_SESSION["dol_login"]))
{
	// On est pas deja authentifie, on demande le login/mot de passe

	// Verification du code securite graphique
	if ($test && isset($_POST["username"]) && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
	{
		require_once DOL_DOCUMENT_ROOT.'/../external-libs/Artichow/Artichow.cfg.php';
		require_once ARTICHOW."/AntiSpam.class.php";

		// On cree l'objet anti-spam
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
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
			if ($result < 0) { $error++; }
			// Fin appel triggers
		}
	}

	// Tests de validation user/mot de passe
	// Si ok, la variable login sera initialisee
	// Si erreur, on a placera message erreur dans session sous le nom dol_loginmesg
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
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
			if ($result < 0) { $error++; }
			// Fin appel triggers
		}
	}

	// Fin des tests de login/passwords
	if (! $login)
	{
		// We show login page
		include_once(DOL_DOCUMENT_ROOT."/lib/security.lib.php");
		dol_loginfunction($langs,$conf,$mysoc);
		exit;
	}

	$resultFetchUser=$user->fetch($login);
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

		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
		$interface=new Interfaces($db);
		$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,$_POST["entity"]);
		if ($result < 0) { $error++; }
		// Fin appel triggers

		header('Location: '.DOL_URL_ROOT.'/index.php');
		exit;
	}
}
else
{
	// On est deja en session qui a sauvegarde login
	// Remarks: On ne sauvegarde pas objet user car pose pb dans certains cas mal identifies
	$login=$_SESSION["dol_login"];
	$resultFetchUser=$user->fetch($login);
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

		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
		$interface=new Interfaces($db);
		$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf,(isset($_POST["entity"])?$_POST["entity"]:0));
		if ($result < 0) { $error++; }
		// Fin appel triggers

		header('Location: '.DOL_URL_ROOT.'/index.php');
		exit;
	}
}

// Is it a new session ?
if (! isset($_SESSION["dol_login"]))
{
	$error=0;

	// New session for this login
	$_SESSION["dol_login"]=$user->login;
	$_SESSION["dol_authmode"]=$conf->authmode;
	if ($conf->multicompany->enabled) $_SESSION["dol_entity"]=$conf->entity;
	dol_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"].' Session id='.session_id());

	$db->begin();

	$user->update_last_login_date();

	// Appel des triggers
	include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	$interface=new Interfaces($db);
	$result=$interface->run_triggers('USER_LOGIN',$user,$user,$langs,$conf,$_POST["entity"]);
	if ($result < 0) { $error++; }
	// Fin appel triggers

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
		include_once(DOL_DOCUMENT_ROOT."/core/cookie.class.php");

		$entity = $_SESSION["dol_login"].'|'.$_POST["entity"];
		$entityCookieName = 'DOLENTITYID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
		// TTL : sera defini dans la page de config multicompany
		$ttl = (! empty($conf->global->MAIN_MULTICOMPANY_COOKIE_TTL) ? $conf->global->MAIN_MULTICOMPANY_COOKIE_TTL : time()+60*60*8 );
		// Cryptkey : sera cree aleatoirement dans la page de config multicompany
		$cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

		$entityCookie = new DolCookie($cryptkey);
		$entityCookie->_setCookie($entityCookieName, $entity, $ttl);
	}

	// Module webcalendar
	if (! empty($conf->webcal->enabled) && $user->webcal_login != "")
	{
		$domain='';

		// Creation du cookie permettant de sauver le login
		$cookiename='webcalendar_login';
		if (! isset($_COOKIE[$cookiename]))
		{
			setcookie($cookiename, $user->webcal_login, 0, "/", $domain, 0);
		}
		// Creation du cookie permettant de sauver la session
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

// Si user admin, on force droits sur les modules base
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
	$conf->produit->limit_size = $user->conf->PRODUIT_LIMIT_SIZE;
}


if (empty($_GET["lang"]))	// If language was not forced on URL
{
	// If user has choosed its own language
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


// Replace conf->css by personalized value
if (isset($user->conf->MAIN_THEME) && $user->conf->MAIN_THEME)
{
	$conf->theme=$user->conf->MAIN_THEME;
	$conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
}
// Cas de forcage du style depuis url
if (! empty($_GET["theme"]))
{
	$conf->theme=$_GET["theme"];
	$conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
}
// Si feuille de style en php existe
if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

if (! empty($user->conf->MAIN_DISABLE_JAVASCRIPT))
{
	$conf->use_javascript_ajax=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
}

// Defini gestionnaire de menu a utiliser
if (! $user->societe_id)    // Si utilisateur interne
{
	$conf->top_menu=$conf->global->MAIN_MENU_BARRETOP;
	$conf->left_menu=$conf->global->MAIN_MENU_BARRELEFT;
	// Pour compatibilite
	if ($conf->left_menu == 'eldy.php') $conf->left_menu='eldy_backoffice.php';
}
else                        // Si utilisateur externe
{
	$conf->top_menu=$conf->global->MAIN_MENUFRONT_BARRETOP;
	$conf->left_menu=$conf->global->MAIN_MENUFRONT_BARRELEFT;
}


// If there is at least one module using Smarty
if (sizeof($conf->need_smarty) > 0)
{
	// SMARTY (Defined into conf file)
	// $dolibarr_smarty_libs_dir="/home/www/dolibarr/external-libs/smarty/libs/";
	// $dolibarr_smarty_compile="/home/www/dolibarr/documents/smarty/templates/temp";
	// $dolibarr_smarty_cache="/home/www/dolibarr/documents/smarty/cache/temp";
	if (empty($dolibarr_smarty_libs_dir)) $dolibarr_smarty_libs_dir=DOL_DOCUMENT_ROOT.'/../external-libs/smarty/libs/';
	if (empty($dolibarr_smarty_compile))  $dolibarr_smarty_compile=DOL_DATA_ROOT.'/smarty/templates/temp';
	if (empty($dolibarr_smarty_cache))    $dolibarr_smarty_cache=DOL_DATA_ROOT.'/smarty/cache/temp';

	$smarty_libs = $dolibarr_smarty_libs_dir. "Smarty.class.php";
	if (file_exists ($smarty_libs))
	{
		require_once($smarty_libs);
		$smarty = new Smarty();
		$smarty->compile_dir = $dolibarr_smarty_compile;
		$smarty->cache_dir = $dolibarr_smarty_cache;
		//$smarty->config_dir = '/web/www.domain.com/smarty/configs';
	}
	else
	{
		dol_print_error('',"Library Smarty ".$smarty_libs." not found. Check parameter dolibarr_smarty_libs_dir in conf file.");
	}
}

// Si le login n'a pu etre recupere, on est identifie avec un compte qui n'existe pas.
// Tentative de hacking ?
if (! $user->login) accessforbidden();

// Check if user is active
if ($user->statut < 1)
{
	// Si non actif, on delogue le user
	$langs->load("other");
	dol_syslog ("Authentification ko as login is disbaled");
	accessforbidden($langs->trans("ErrorLoginDisabled"));
	exit;
}


dol_syslog("Access to ".$_SERVER["PHP_SELF"]);
//Another call for easy debugg
//dol_syslog("Access to ".$_SERVER["PHP_SELF"].' GET='.join(',',array_keys($_GET)).'->'.join(',',$_GET).' POST:'.join(',',array_keys($_POST)).'->'.join(',',$_POST));

// For backward compatibility
if (! defined('MAIN_INFO_SOCIETE_PAYS')) define('MAIN_INFO_SOCIETE_PAYS','1');

// On charge les fichiers lang principaux
$langs->load("main");
$langs->load("dict");

// Load permissions
$user->getrights();

// Define some constants used for style of arrays
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";

// Sert uniquement dans module telephonie
$yesno[0]="no";
$yesno[1]="yes";

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


/**
 *  \brief      Show HTML header
 *  \param      head    	Optionnal head lines
 *  \param      title   	Web page title
 *	\param		disablejs	Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *	\param		disablehead	Do not output head section
 *	\param		arrayofjs	Array of js files to add in header
 *	\param		arrayofcss	Array of css files to add in header
 */
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $user, $conf, $langs, $db;

	if (empty($conf->css)) 		$conf->css = 'theme/eldy/eldy.css.php';

	//header("Content-type: text/html; charset=UTF-8");
	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	if ($disablehead == 0)
	{
		print "<head>\n";

		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->file->character_set_client."\">\n";

		// Affiche meta
		print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
		print '<meta name="author" content="Dolibarr Development Team">'."\n";

		// Affiche title
		$appli='Dolibarr';
		if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

		if ($title) print '<title>'.$appli.' - '.$title.'</title>';
		else print "<title>".$appli."</title>";
		print "\n";

		// Output style sheets
		print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'?lang='.$langs->defaultlang.(! empty($_GET["optioncss"])?'&optioncss='.$_GET["optioncss"]:'').'">'."\n";
		// CSS forced by modules
		if (is_array($conf->css_modules))
		{
			foreach($conf->css_modules as $cssfile)
			{	// cssfile is an absolute path
				print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.$cssfile.'?lang='.$langs->defaultlang.(! empty($_GET["optioncss"])?'&optioncss='.$_GET["optioncss"]:'').'">'."\n";
			}
		}
		// CSS forced by page (in top_htmlhead call)
		if (is_array($arrayofcss))
		{
			foreach($arrayofcss as $cssfile)
			{
				print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$cssfile.'?lang='.$langs->defaultlang.(! empty($_GET["optioncss"])?'&optioncss='.$_GET["optioncss"]:'').'">'."\n";
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
			if (! empty($conf->global->MAIN_OPTIMIZE_SPEED)) { $mini='_mini'; $ext='.jgz'; }	// mini='_mini', ext='.gz'

			// This one is required for all Ajax features
			if (! defined('DISABLE_PROTOTYPE'))
			{
				print '<!-- Includes for Prototype (Used by Scriptaculous and PWC) -->'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype'.$mini.$ext.'"></script>'."\n";
			}
			// This one is required for boxes
			if (! defined('DISABLE_SCRIPTACULOUS'))
			{
				print '<!-- Includes for Scriptaculous (Used by Drag and drop and PWC) -->'."\n";
				//print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js"></script>'."\n";
				//print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js?load=builder,effects,dragdrop,controls,slider,sound"></script>'."\n";
				$listofscripts='effects,dragdrop';
				if ($conf->global->COMPANY_USE_SEARCH_TO_SELECT) $listofscripts.=',controls';	// For Ajax.Autocompleter
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js?load='.$listofscripts.'"></script>'."\n";
			}

			// Those ones are required only with option "confirm by ajax popup"
			if (! defined('DISABLE_PWC') && $conf->global->MAIN_CONFIRM_AJAX)
			{
				print '<!-- Includes for PWC (Used for confirm popup) -->'."\n";
				// PWC js
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/pwc/window'.$mini.$ext.'"></script>'."\n";
			}
		}

		// Output module javascript
		if (is_array($arrayofjs))
		{
			foreach($arrayofjs as $jsfile)
			{
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/'.$jsfile.'"></script>'."\n";
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

		print "</head>\n\n";
	}
}

/**
 *  \brief      Show an HTML header + a BODY + The top menu bar
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      target  target to add in menu links
 */
function top_menu($head, $title='', $target='')
{
	global $user, $conf, $langs, $db, $dolibarr_main_authentication;

	if (! $conf->top_menu)  $conf->top_menu ='eldy_backoffice.php';
	if (! $conf->left_menu) $conf->left_menu='eldy_backoffice.php';

	top_htmlhead($head, $title);	// Show html headers

	print '<body id="mainbody"><div id="dhtmltooltip"></div>';

	/*
	 * Top menu
	 */
	print "\n".'<!-- Start top horizontal menu -->'."\n";
	print '<div class="tmenu">'."\n";

	// Charge le gestionnaire des entrees de menu du haut
	if (! file_exists(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".$conf->top_menu))
	{
		$conf->top_menu='eldy_backoffice.php';
	}
	require_once(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".$conf->top_menu);
	$menutop = new MenuTop($db);
	$menutop->atarget=$target;

	// Affiche le menu
	$menutop->showmenu();

	// Link to login card
	print '<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'"';
	print $menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
	print '>'.$user->login.'</a>';

	// Link info
	$htmltext=''; $text='';
	if ($_SESSION["dol_authmode"] != 'forceuser'
	&& $_SESSION["dol_authmode"] != 'http')
	{
		$htmltext=$langs->trans("Logout").'<br>';
		$htmltext.="<br>";

		$text.='<a href="'.DOL_URL_ROOT.'/user/logout.php"';
		$text.=$menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
		$text.='>';
		$text.='<img class="login" border="0" width="14" height="14" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
		$text.=' alt="'.dol_escape_htmltag($langs->trans("Logout")).'" title=""';
		$text.='>';
		$text.='</a>';
	}
	else
	{
		$text.='<img class="login" border="0" width="14" height="14" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
		$text.=' alt="'.dol_escape_htmltag($langs->trans("Logout")).'" title=""';
		$text.='>';
	}
	$htmltext.='<u>'.$langs->trans("User").'</u>';
	$htmltext.='<br><b>'.$langs->trans("Name").'</b>: '.$user->fullname;
	$htmltext.='<br><b>'.$langs->trans("Login").'</b>: '.$user->login;
	$htmltext.='<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	$htmltext.='<br><b>'.$langs->trans("Type").'</b>: '.($user->societe_id?$langs->trans("External"):$langs->trans("Internal"));
	$htmltext.='<br>';
	$htmltext.='<br><u>'.$langs->trans("Connection").'</u>';
	if ($conf->global->MAIN_MODULE_MULTICOMPANY) $htmltext.='<br><b>'.$langs->trans("ConnectedOnMultiCompany").'</b>: '.$conf->entity.' (user entity '.$user->entity.')';
	$htmltext.='<br><b>'.$langs->trans("ConnectedSince").'</b>: '.dol_print_date($user->datelastlogin,"dayhour");
	$htmltext.='<br><b>'.$langs->trans("PreviousConnexion").'</b>: '.dol_print_date($user->datepreviouslogin,"dayhour");
	$htmltext.='<br><b>'.$langs->trans("AuthenticationMode").'</b>: '.$_SESSION["dol_authmode"];
	$htmltext.='<br><b>'.$langs->trans("CurrentTheme").'</b>: '.$conf->theme;
	$htmltext.='<br><b>'.$langs->trans("CurrentUserLanguage").'</b>: '.$langs->getDefaultLang();
	$htmltext.='<br><b>'.$langs->trans("Browser").'</b>: '.$conf->browser->name.' ('.$_SERVER['HTTP_USER_AGENT'].')';
	if (! empty($conf->browser->phone)) $htmltext.='<br><b>'.$langs->trans("Phone").'</b>: '.$conf->browser->phone;
	 
	if (! empty($_SESSION["disablemodules"])) $htmltext.='<br><b>'.$langs->trans("DisabledModules").'</b>: <br>'.join('<br>',split(',',$_SESSION["disablemodules"]));

	//        print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
	//        print ' alt="'.$title.'" title="'.$title.'"';
	//        print '>';
	$html=new Form($db);
	print $html->textwithtooltip('',$htmltext,2,1,$text);

	// Link to print main content area
	if (empty($conf->global->MAIN_PRINT_DISABLELINK))
	{
		$text ='<a href="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'&optioncss=print" target="_blank">';
		$text.='<img class="printer" border="0" width="14" height="14" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/printer.png"';
		$text.=' title="'.dol_escape_htmltag($langs->trans("PrintContentArea")).'" alt="'.dol_escape_htmltag($langs->trans("PrintContentArea")).'">';
		$text.='</a>';
		print $text;
	}

	print "\n</div>\n<!-- End top horizontal menu -->\n";
}


/**
 *  \brief      Show left menu bar
 *  \param      menu_array      	Tableau des entrees de menu
 *  \param      helppagename    	Name of wiki page for help ('' by default).
 * 				Syntax is: 			For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 * 									For other external page: http://server/url
 *  \param      moresearchform     	Formulaire de recherche permanant supplementaire
 */
function left_menu($menu_array, $helppagename='', $moresearchform='')
{
	global $user, $conf, $langs, $db;

	$searchform='';
	$bookmarks='';

	//    print '<div class="vmenuplusfiche">'."\n";
	print '<table width="100%" class="notopnoleftnoright" summary="leftmenutable"><tr><td class="vmenu" valign="top">';

	print "\n";


	// Define $searchform
	if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_SOCIETE && $user->rights->societe->lire)
	{
		$langs->load("companies");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/societe.php', DOL_URL_ROOT.'/societe.php',
		img_object('','company').' '.$langs->trans("Companies"), 'soc', 'socname');
	}

	if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT && $user->rights->societe->lire)
	{
		$langs->load("companies");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/contact/index.php', DOL_URL_ROOT.'/contact/index.php',
		img_object('','contact').' '.$langs->trans("Contacts"), 'contact', 'contactname');
	}

	if ((($conf->produit->enabled && $user->rights->produit->lire) || ($conf->service->enabled && $user->rights->service->lire))
	&& $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE)
	{
		$langs->load("products");
		$searchform.=printSearchForm(DOL_URL_ROOT.'/product/liste.php', DOL_URL_ROOT.'/product/index.php',
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



	// Colonne de gauche
	print '<!-- Begin left vertical menu -->'."\n";
	print '<div class="vmenu">'."\n";


	// Autres entrees du menu par le gestionnaire
	if (! file_exists(DOL_DOCUMENT_ROOT ."/includes/menus/barre_left/".$conf->left_menu))
	{
		$conf->left_menu='eldy_backoffice.php';
	}
	require_once(DOL_DOCUMENT_ROOT ."/includes/menus/barre_left/".$conf->left_menu);
	$menuleft=new MenuLeft($db,$menu_array);
	$menuleft->showmenu();


	if ($searchform)
	{
		print "\n";
		print "<!-- Begin SearchForm -->\n";
		print '<div class="blockvmenupair">'."\n";
		print $searchform;
		print '</div>'."\n";
		print "<!-- End SearchForm -->\n";
	}

	if ($moresearchform)
	{
		print $moresearchform;
	}

	if ($bookmarks)
	{
		print "\n";
		print "<!-- Begin Bookmarks -->\n";
		print '<div class="blockvmenupair">'."\n";
		print $bookmarks;
		print '</div>'."\n";
		print "<!-- End Bookmarks -->\n";
	}

	// Link to Dolibarr wiki pages
	if ($helppagename && empty($conf->global->MAIN_HELP_DISABLELINK))
	{
		$langs->load("help");

		$helpbaseurl='';
		if (eregi('^http',$helppagename))
		{
			// If complete URL
			$helpbaseurl='%s';
			$helppage=$helppagename;
			$mode='local';
		}
		else
		{
			// If WIKI URL
			$helppage='';
			if (eregi('^es',$langs->defaultlang))
			{
				$helpbaseurl='http://wiki.dolibarr.org/index.php/%s';
				if (eregi('ES:([^|]+)',$helppagename,$reg)) $helppage=$reg[1];
			}
			if (eregi('^fr',$langs->defaultlang))
			{
				$helpbaseurl='http://wiki.dolibarr.org/index.php/%s';
				if (eregi('FR:([^|]+)',$helppagename,$reg)) $helppage=$reg[1];
			}
			if (empty($helppage))	// If help page not already found
			{
				$helpbaseurl='http://wiki.dolibarr.org/index.php/%s';
				if (eregi('EN:([^|]+)',$helppagename,$reg)) $helppage=$reg[1];
			}
			$mode='wiki';
		}

		// Link to help pages
		if ($helpbaseurl && $helppage)
		{
			print '<div class="help">';
			print '<a class="help" target="_blank" title="'.$langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage');
			if ($mode == 'wiki') print ' - '.$langs->trans("PageWiki").' &quot;'.strtr($helppage,'_',' ').'&quot;';
			print '" href="';
			print sprintf($helpbaseurl,$helppage);
			print '">';
			print img_picto('',DOL_URL_ROOT.'/theme/common/helpdoc.png','',1).' ';
			print $langs->trans($mode == 'wiki' ? 'OnlineHelp': 'Help');
			//if ($mode == 'wiki') print ' ('.dol_trunc(strtr($helppage,'_',' '),8).')';
			print '</a>';
			print '</div>';
		}
	}

	if (! empty($conf->global->MAIN_SHOW_BUGTRACK_LINK))
	{
		// Link to bugtrack
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

	print '<!-- End of left column, begin right area -->'."\n";
	//	    print '</div>'."\n";
	//		print '<div class="vmenuplusfiche">'."\n";
	print '</td><td valign="top">'."\n";


	print "\n";
	print '<div class="fiche"> <!-- begin main area -->'."\n";

	if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED)) print info_admin($langs->trans("WarningYouAreInMaintenanceMode",$conf->global->MAIN_ONLY_LOGIN_ALLOWED));
}



/**
 *  \brief   Show a search area
 *  \param   urlaction          Url du post
 *  \param   urlobject          Url du lien sur titre de la zone de recherche
 *  \param   title              Titre de la zone de recherche
 *  \param   htmlmodesearch     'search'
 *  \param   htmlinputname      Nom du champ input du formulaire
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
 *		\brief   	Show HTML footer DIV + BODY + HTML
 *		\remarks	Close 2 div
 * 		\param   	foot    		A text to add in HTML generated page
 */
function llxFooter($foot='')
{
	global $conf, $dolibarr_auto_user, $micro_start_time;

	print "\n\n".'</div> <!-- end div class="fiche" -->'."\n";

	//    print "\n".'</div> <!-- end div class="vmenuplusfiche" -->'."\n";
	print "\n".'</td></tr></table> <!-- end right area -->'."\n";

	if (! empty($_SERVER['DOL_TUNING']))
	{
		$micro_end_time=dol_microtime_float(true);
		print "\n".'<script type="text/javascript">window.status="Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
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

	if ($conf->use_javascript_ajax)
	{
		print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_foot.js"></script>'."\n";
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

	print "</body>\n";
	print "</html>\n";
}

?>
