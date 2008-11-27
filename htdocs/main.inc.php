<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *	\brief      Fichier de formatage generique des ecrans Dolibarr
 *	\version    $Id$
 */

// For optionnal tuning. Enabled if environment variable DOL_TUNING is defined.
// A appeler avant tout. Fait l'equivalent de la fonction dol_microtime_float pas encore chargee.
$micro_start_time=0;
if (! empty($_SERVER['DOL_TUNING']))
{
	list($usec, $sec) = explode(" ", microtime());
	$micro_start_time=((float)$usec + (float)$sec);
}


// Forcage du parametrage PHP magic_quotes_gpc et nettoyage des parametres
// (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande il faut juste faire addslashes au moment d'un insert/update.
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
		$_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
		$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}
	@set_magic_quotes_runtime(0);
}

// Filtre les GET et POST pour supprimer les SQL INJECTION
function test_sql_inject($val)
{
	$sql_inj = 0;
	$sql_inj += eregi('delete[[:space:]]+from', $val);
	$sql_inj += eregi('create[[:space:]]+table', $val);
	$sql_inj += eregi('update.+set.+=', $val);
	$sql_inj += eregi('insert[[:space:]]+into', $val);
	$sql_inj += eregi('select.+from', $val);
	return $sql_inj;
}
// Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/patch-dolibarr-fix-sql-injection-check-in-array.html)
function analyse_sql_injection(&$var)
{
	if (is_array($var))
	{
		$result = array();
		foreach ($var as $key => $value)
		{
		  if (test_sql_inject($key) > 0)
		  {
		  	unset($var[$key]);
		  }
		  else
		  {
		  	if (analyse_sql_injection($value))
		  	{
		  		$var[$key] = $value;
		  	}
		  	else
		  	{
		  		unset($var[$key]);
		  	}
		  }
		}
		return true;
	}
	else
	{
		return (test_sql_inject($var) <= 0);
	}
}
analyse_sql_injection($_GET);
analyse_sql_injection($_POST);
// Fin filtre des GET et POST


// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Set and init common variables
require_once("master.inc.php");

// Check if HTTPS
if ($conf->main_force_https)
{
	if (eregi('^http:',$_SERVER["SCRIPT_URI"]) && ! eregi('^https:',$_SERVER["SCRIPT_URI"]))
	{
		if ($_SERVER["HTTPS"] != 'on')
		{
			dolibarr_syslog("dolibarr_main_force_https is on but https disabled on serveur. We ignore option.",LOG_ERR);
		}
		else
		{
			dolibarr_syslog("dolibarr_main_force_https is on, we make a redirect",LOG_DEBUG);
			$newurl=eregi_replace('^http:','https:',$_SERVER["SCRIPT_URI"]);
				
			header("Location: ".$newurl);
			exit;
		}
	}
}

// Chargement des includes complementaire de presentation
if (! defined('NOREQUIREMENU')) require_once(DOL_DOCUMENT_ROOT ."/menu.class.php");			// Need 11ko memory (11ko in 2.2)
if (! defined('NOREQUIREHTML')) require_once(DOL_DOCUMENT_ROOT ."/html.form.class.php");	// Need 690ko memory (800ko in 2.2)
if (! defined('NOREQUIREAJAX') && $conf->use_javascript_ajax) require_once(DOL_DOCUMENT_ROOT.'/lib/ajax.lib.php');	// Need 20ko memory
//stopwithmem();

// Init session
$sessionname="DOLSESSID_".$dolibarr_main_db_name;
if (! empty($conf->global->MAIN_SESSION_TIMEOUT)) ini_set('session.gc_maxlifetime',$conf->global->MAIN_SESSION_TIMEOUT);
session_name($sessionname);
session_start();
dolibarr_syslog("Start session name=".$sessionname." Session id()=".session_id().", _SESSION['dol_login']=".$_SESSION["dol_login"].", ".ini_get("session.gc_maxlifetime"));

/*
 * Phase identification
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
	dolibarr_print_error('',$langs->trans("ErrorConfigParameterNotDefined",'dolibarr_main_authentication'));
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
	if ($test && isset($_POST["username"]) && $conf->global->MAIN_SECURITY_ENABLECAPTCHA)
	{
		require_once DOL_DOCUMENT_ROOT.'/../external-libs/Artichow/Artichow.cfg.php';
		require_once ARTICHOW."/AntiSpam.class.php";

		// On cree l'objet anti-spam
		$object = new AntiSpam();

		// Verifie code
		if (! $object->check('dol_antispam_value',$_POST['code'],true))
		{
			dolibarr_syslog('Bad value for code, connexion refused');
			$langs->load('main');
			$langs->load('other');
				
			$user->trigger_mesg='ErrorBadValueForCode - login='.$_POST["username"];
			$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadValueForCode");
			$test=false;
				
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
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
						$conf->authmode=$mode;	// This properties is defined only when login
					}
				}
				else
				{
					dolibarr_syslog("Authentification ko - failed to load file '".$authfile."'",LOG_ERR);
					sleep(1);
					$langs->load('main');
					$langs->load('other');
					$_SESSION["dol_loginmesg"]=$langs->trans("ErrorFailedToLoadLoginFileForMode",$mode);
				}
			}
		}

		if (! $login)
		{
			dolibarr_syslog('Bad password, connexion refused',LOG_DEBUG);
			$langs->load('main');
			$langs->load('other');

			// Bad password. No authmode has found a good password.
			$user->trigger_mesg=$langs->trans("ErrorBadLoginPassword").' - login='.$_POST["username"];
			$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
				
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
			if ($result < 0) { $error++; }
			// Fin appel triggers
		}
	}

	// Fin des tests de login/passwords
	if (! $login)
	{
		// We show login page
		dol_loginfunction($langs,$conf,$mysoc);
		exit;
	}

	$resultFetchUser=$user->fetch($login);
	if ($resultFetchUser <= 0)
	{
		dolibarr_syslog('User not found, connexion refused');
		session_destroy();
		session_name($sessionname);
		session_start();

		$langs->load('main');
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
		$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
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
	dolibarr_syslog("This is an already logged session. _SESSION['dol_login']=".$login);
	if ($resultFetchUser <= 0)
	{
		// Account has been removed after login
		dolibarr_syslog("Can't load user even if session logged. _SESSION['dol_login']=".$login, LOG_WARNING);
		session_destroy();
		session_name($sessionname);
		session_start();

		$langs->load('main');
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
		$result=$interface->run_triggers('USER_LOGIN_FAILED',$user,$user,$langs,$conf);
		if ($result < 0) { $error++; }
		// Fin appel triggers

		header('Location: '.DOL_URL_ROOT.'/index.php');
		exit;
	}
}

// Est-ce une nouvelle session
if (! isset($_SESSION["dol_login"]))
{
	// Nouvelle session pour ce login
	$_SESSION["dol_login"]=$user->login;
	$_SESSION["dol_password"]=$user->pass_crypted;
	$_SESSION["dol_authmode"]=$conf->authmode;
	dolibarr_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"].' Session id='.session_id());

	$db->begin();

	$user->update_last_login_date();

	// Appel des triggers
	include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	$interface=new Interfaces($db);
	$result=$interface->run_triggers('USER_LOGIN',$user,$user,$langs,$conf);
	if ($result < 0) { $error++; }
	// Fin appel triggers

	if ($error)
	{
		$db->rollback();
		session_destroy();
		dolibarr_print_error($db,'Error in some triggers on action USER_LOGIN',LOG_ERR);
		exit;
	}
	else
	{
		$db->commit();
	}

	// Module webcalendar
	if ($conf->webcal->enabled && $user->webcal_login != "")
	{
		$domain='';
		// Extract domain from url (Useless because only cookie on same domain are authorized by browser
		//if (eregi('^(https:[\\\/]+[^\\\/]+)',$conf->global->PHPWEBCALENDAR_URL,$reg)) $domain=$reg[1];

		// Creation du cookie permettant de sauver le login
		$cookiename='webcalendar_login';
		if (! isset($HTTP_COOKIE_VARS[$cookiename]))
		{
			setcookie($cookiename, $user->webcal_login, 0, "/", $domain, 0);
		}
		// Creation du cookie permettant de sauver la session
		$cookiename='webcalendar_session';
		if (! isset($HTTP_COOKIE_VARS[$cookiename]))
		{
			setcookie($cookiename, 'TODO', 0, "/", $domain, 0);
		}
	}

	// Module Phenix
	if ($conf->phenix->enabled && $user->phenix_login != "" && $conf->phenix->cookie)
	{
		// Creation du cookie permettant la connexion automatique, valide jusqu'a la fermeture du browser
		if (!isset($HTTP_COOKIE_VARS[$conf->phenix->cookie]))
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

// If user has choosed its own language
if (! empty($user->conf->MAIN_LANG_DEFAULT))
{
	// If different than current language
	if ($langs->getDefaultLang() != $user->conf->MAIN_LANG_DEFAULT)
	{
		$langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
		$langs->setPhpLang();
	}
}
// If language was forced on URL
if (! empty($_GET["lang"]))
{
	$langs->setDefaultLang($_GET["lang"]);
	$langs->setPhpLang();
}


// Remplace conf->css par valeur personnalise
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

// Only rodolphe and auguria menu manage canvas menu (auguria not correctly yet)
if (! eregi('^rodolphe',$conf->left_menu) && ! eregi('^auguria',$conf->left_menu)) $conf->global->PRODUCT_CANVAS_ABILITY=0;

// Si besoin de smarty
if ($conf->global->PRODUCT_CANVAS_ABILITY)
{
	// SMARTY
	// Definit dans le fichier de conf
	// $dolibarr_smarty_libs_dir="/home/www/dolibarr/external-libs/smarty/libs/";
	// $dolibarr_smarty_compile="/home/www/dolibarr/documents/smarty/templates/temp";
	// $dolibarr_smarty_cache="/home/www/dolibarr/documents/smarty/cache/temp";

	if (empty($dolibarr_smarty_libs_dir)) $dolibarr_smarty_libs_dir=$dolibarr_main_document_root.'/../external-libs/smarty/libs/';
	if (empty($dolibarr_smarty_compile))  $dolibarr_smarty_compile=$dolibarr_main_data_root.'/smarty/templates/temp';
	if (empty($dolibarr_smarty_cache))    $dolibarr_smarty_cache=$dolibarr_main_data_root.'/smarty/cache/temp';

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
		dolibarr_print_error('',"Library Smarty ".$smarty_libs." not found. Check parameter dolibarr_smarty_libs_dir in conf file.");
	}
}

// Si le login n'a pu etre recupere, on est identifie avec un compte qui n'existe pas.
// Tentative de hacking ?
if (! $user->login) accessforbidden();

// Verifie si user actif
if ($user->statut < 1)
{
	// Si non actif, on delogue le user
	$langs->load("other");
	dolibarr_syslog ("Authentification ko car login desactive");
	accessforbidden($langs->trans("ErrorLoginDisabled"));
	exit;
}
	

dolibarr_syslog("Access to ".$_SERVER["PHP_SELF"],LOG_INFO);

// For backward compatibility
if (! defined('MAIN_INFO_SOCIETE_PAYS')) define('MAIN_INFO_SOCIETE_PAYS','1');

// If install not finished, we start again.
if (defined("MAIN_NOT_INSTALLED"))
{
	Header("Location: ".DOL_URL_ROOT."/install/index.php");
	exit;
}


// On charge les fichiers lang principaux
// TODO Optimisation a faire ici
$langs->load("main");
$langs->load("dict");

// On charge les fichiers lang principaux
$user->getrights();

// Define some constants used for style of arrays
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";

// Constantes utilisees pour definir le nombre de lignes des textarea
if (! eregi("firefox",$_SERVER["HTTP_USER_AGENT"]))
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
 \brief      Affiche formulaire de login
 \param		langs		Lang object
 \param		conf		Conf object
 \param		mysoc		Company object
 \remarks    Il faut changer le code html dans cette fonction pour changer le design de la logon
 */
function dol_loginfunction($langs,$conf,$mysoc)
{
	$langs->load("main");
	$langs->load("other");

	$conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
	// Si feuille de style en php existe
	if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

	header('Cache-Control: Public, must-revalidate');

	// Ce DTD est KO car inhibe document.body.scrollTop
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	// Ce DTD est OK
	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";

	// En tete html
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
	print "<title>".$langs->trans("Login")."</title>\n";

	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";

	print '<style type="text/css">'."\n";
	print '<!--'."\n";
	print '#login {';
	print '  margin-top: 70px;';
	print '  margin-bottom: 30px;';
	print '  text-align: center;';
	print '  font: 12px arial,helvetica;';
	print '}'."\n";
	print '#login table {';
	print '  border: 1px solid #C0C0C0;';
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
	{
		print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png) repeat-x;';
	}
	else
	{
		print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/login_background.png) repeat-x;';
	}
	print 'font-size: 12px;';
	print '}'."\n";
	print '-->'."\n";
	print '</style>'."\n";
	print '<script type="text/javascript">'."\n";
	print "function donnefocus() {\n";
	if (! $_REQUEST["username"]) print "document.getElementById('username').focus();\n";
	else print "document.getElementById('password').focus();\n";
	print "}\n";
	print '</script>'."\n";
	print '</head>'."\n";

	// Body
	print '<body class="body" onload="donnefocus();">';

	// Start Form
	print '<form id="login" name="login" method="post" action="';
	print $_SERVER['PHP_SELF'];
	print $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';
	print '">';

	// Table 1
	print '<table cellpadding="0" cellspacing="0" border="0" align="center" width="450">';
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;
	print '<tr class="vmenu"><td align="center">'.$title.'</td></tr>';
	print '</table>';
	print '<br>';

	// Table 2
	print '<table cellpadding="2" align="center" width="450">';

	print '<tr><td colspan="3">&nbsp;</td></tr>';

	print '<tr>';

	// Login field
	print '<td align="left" valign="bottom"> &nbsp; <b>'.$langs->trans("Login").'</b>  &nbsp;</td>';
	print '<td valign="bottom"><input type="text" id="username" name="username" class="flat" size="15" maxlength="25" value="'.(isset($_REQUEST["username"])?$_REQUEST["username"]:'').'" tabindex="1" /></td>';

	$title=$langs->trans("SessionName").': '.session_name();
	if ($conf->main_authentication) $title.=", ".$langs->trans("AuthenticationMode").': '.$conf->main_authentication;

	// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
	$width=0;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
	if (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=96;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_logo.png';
	}
	print '<td rowspan="2" align="center"><img title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></td>';
	print '</tr>'."\n";

	// Password field
	print '<tr><td align="left" valign="top" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("Password").'</b> &nbsp; </td>';
	print '<td valign="top" nowrap="nowrap"><input id="password" name="password" class="flat" type="password" size="15" maxlength="30" tabindex="2">';
	print '</td></tr>';

	print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";

	// Code de sécurité
	$disabled=! $conf->global->MAIN_SECURITY_ENABLECAPTCHA;
	if (function_exists("imagecreatefrompng") && ! $disabled)
	{
		//print "Info session: ".session_name().session_id();print_r($_SESSION);
		print '<tr><td align="left" valign="middle" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("SecurityCode").'</b></td>';
		print '<td valign="top" nowrap="nowrap" align="left" class="e">';

		print '<table><tr>';
		print '<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="3"></td>';
		print '<td><img src="'.DOL_URL_ROOT.'/lib/antispamimage.php" border="0" width="128" height="36"></td>';
		print '<td><a href="'.$_SERVER["PHP_SELF"].'">'.img_refresh().'</a></td>';
		print '</tr></table>';

		print '</td>';
		print '</tr>';
	}

	print '<tr><td colspan="3" style="text-align:center;"><br>';
	print '<input type="submit" class="button" value="&nbsp; '.$langs->trans("Connection").' &nbsp;" tabindex="4" />';
	print '</td></tr>';

	if (! $conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK)
	{
		print '<tr><td colspan="3" align="center"><a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/user/passwordforgotten.php">('.$langs->trans("PasswordForgotten").')</a></td></tr>';
	}

	print '</table>';
	print '<input type="hidden" name="loginfunction" value="loginfunction" />';

	print '</form>';

	// Message
	if ($_SESSION["dol_loginmesg"])
	{
		print '<center><table width="60%"><tr><td align="center" class="small"><div class="error">';
		print $_SESSION["dol_loginmesg"];
		$_SESSION["dol_loginmesg"]="";
		print '</div></td></tr></table></center>';
	}
	if ($conf->global->MAIN_HOME)
	{
		print '<center><table cellpadding="0" cellspacing="0" border="0" align="center" width="750"><tr><td align="center">';
		print nl2br($conf->global->MAIN_HOME);
		print '</td></tr></table></center><br>';
	}

	// Fin entete html
	print "\n</body>\n</html>";
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

	if (empty($conf->css))  $conf->css ='/theme/eldy/eldy.css.php';
	//header("Content-type: text/html; charset=UTF-8");
	header("Content-type: text/html; charset=".$conf->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	if ($disablehead == 0)
	{
		print "<head>\n";

        print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->character_set_client."\">\n";

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
		print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
		// CSS forced by modules
		if (is_array($conf->css_modules))
		{
			foreach($conf->css_modules as $cssfile)
			{	// cssfile is an absolute path
				print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.$cssfile.'">'."\n";
			}
		}
		// CSS forced by page
		if (is_array($arrayofcss))
		{
			foreach($arrayofcss as $cssfile)
			{
				print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$cssfile.'">'."\n";
			}
		}

		// Definition en alternate style sheet des feuilles de styles les plus maintenues
		// Les navigateurs qui supportent sont rares. Plus aucun connu.
		/*
		print '<link rel="alternate stylesheet" type="text/css" title="Eldy" href="'.DOL_URL_ROOT.'/theme/eldy/eldy.css.php">'."\n";
		print '<link rel="alternate stylesheet" type="text/css" title="Freelug" href="'.DOL_URL_ROOT.'/theme/freelug/freelug.css.php">'."\n";
		print '<link rel="alternate stylesheet" type="text/css" title="Yellow" href="'.DOL_URL_ROOT.'/theme/yellow/yellow.css">'."\n";
		*/

		print '<link rel="top" title="'.$langs->trans("Home").'" href="'.DOL_URL_ROOT.'/">'."\n";
		print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
		print '<link rel="author" title="Dolibarr Development Team" href="http://www.dolibarr.org">'."\n";

		// Output javascript links
		if (! $disablejs && $conf->use_javascript_ajax)
		{
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_head.js"></script>'."\n";
		}
		if (! $disablejs && $conf->use_javascript_ajax)
		{
			require_once DOL_DOCUMENT_ROOT.'/lib/ajax.lib.php';

			// This one is required for all Ajax features
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype.js"></script>'."\n";
			// This one is required fox boxes
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js"></script>'."\n";

			// Those ones are required only with option "confirm by ajax popup"
			if ($conf->global->MAIN_CONFIRM_AJAX)
			{
				// PWC css
				print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/alert.css">'."\n";
				// Scriptaculous used by PWC
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/effects.js"></script>'."\n";
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/controls.js"></script>'."\n";
				// PWC js
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/pwc/window.js"></script>'."\n";
			}
		}
		if (is_array($arrayofjs))
		{
			foreach($arrayofjs as $jsfile)
			{
				print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/'.$jsfile.'"></script>'."\n";
			}
		}

		print "</head>\n";
	}
}

/**
 *  \brief      Affiche en-tete HTML + BODY + Barre de menu superieure
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      target  target du menu Accueil
 */
function top_menu($head, $title='', $target='')
{
	global $user, $conf, $langs, $db, $dolibarr_main_authentication;

	if (! $conf->top_menu)  $conf->top_menu ='eldy_backoffice.php';
	if (! $conf->left_menu) $conf->left_menu='eldy_backoffice.php';

	top_htmlhead($head, $title);

	print '<body id="mainbody"><div id="dhtmltooltip"></div>';

	/*
	 * Si la constante MAIN_NEED_UPDATE est definie (par le script de migration sql en general), c'est que
	 * les donnees ont besoin d'un remaniement. Il faut passer le update.php
	 */
	if (! empty($conf->global->MAIN_NEED_UPDATE))
	{
		$langs->load("admin");
		print '<div class="fiche">'."\n";
		print '<table class="noborder" width="100%">';
		print '<tr><td>';
		print $langs->trans("UpdateRequired",DOL_URL_ROOT.'/install/index.php');
		print '</td></tr>';
		print "</table>";
		llxFooter();
		exit;
	}


	/*
	 * Barre de menu superieure
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

	// Lien sur fiche du login
	print '<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'"';
	print $menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
	print '>'.$user->login.'</a>';

	// Lien info
	$htmltext=''; $text='';
	if ($_SESSION["dol_authmode"] != 'forceuser'
	&& $_SESSION["dol_authmode"] != 'http')
	{
		$htmltext=$langs->trans("Logout").'<br>';
		$htmltext.="<br>";

		$text.='<a href="'.DOL_URL_ROOT.'/user/logout.php"';
		$text.=$menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
		$text.='>';
		$text.='<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
		$text.=' alt="" title=""';
		$text.='>';
		$text.='</a>';
	}
	else
	{
		$text.='<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
		$text.=' alt="" title=""';
		$text.='>';
	}
	$htmltext.='<u>'.$langs->trans("User").'</u>';
	$htmltext.='<br><b>'.$langs->trans("Name").'</b>: '.$user->fullname;
	$htmltext.='<br><b>'.$langs->trans("Login").'</b>: '.$user->login;
	$htmltext.='<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	$htmltext.='<br><b>'.$langs->trans("Type").'</b>: '.($user->societe_id?$langs->trans("External"):$langs->trans("Internal"));
	$htmltext.='<br>';
	$htmltext.='<br><u>'.$langs->trans("Connection").'</u>';
	$htmltext.='<br><b>'.$langs->trans("ConnectedSince").'</b>: '.dolibarr_print_date($user->datelastlogin,"dayhour");
	$htmltext.='<br><b>'.$langs->trans("PreviousConnexion").'</b>: '.dolibarr_print_date($user->datepreviouslogin,"dayhour");
	$htmltext.='<br><b>'.$langs->trans("AuthenticationMode").'</b>: '.$_SESSION["dol_authmode"];
	$htmltext.='<br><b>'.$langs->trans("CurrentTheme").'</b>: '.$conf->theme;
	$htmltext.='<br><b>'.$langs->trans("CurrentUserLanguage").'</b>: '.$langs->getDefaultLang();

	$html=new Form($db);
	print $html->textwithtooltip('',$htmltext,2,1,$text);

	//        print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
	//        print ' alt="'.$title.'" title="'.$title.'"';
	//        print '>';

	print "\n</div>\n<!-- End top horizontal menu -->\n";
}


/**
 *  \brief      Affiche barre de menu gauche
 *  \param      menu_array      Tableau des entrees de menu
 *  \param      helppagename    Url pour le lien aide ('' par defaut)
 *  \param      form_search     Formulaire de recherche permanant supplementaire
 */
function left_menu($menu_array, $helppagename='', $form_search='')
{
	global $user, $conf, $langs, $db;

	//    print '<div class="vmenuplusfiche">'."\n";
	print '<table width="100%" class="notopnoleftnoright"><tr><td class="vmenu" valign="top">';

	print "\n";

	// Colonne de gauche
	print '<!-- Debut left vertical menu -->'."\n";
	print '<div class="vmenu">'."\n";


	// Autres entrees du menu par le gestionnaire
	if (! file_exists(DOL_DOCUMENT_ROOT ."/includes/menus/barre_left/".$conf->left_menu))
	{
		$conf->left_menu='eldy_backoffice.php';
	}
	require_once(DOL_DOCUMENT_ROOT ."/includes/menus/barre_left/".$conf->left_menu);
	$menuleft=new MenuLeft($db,$menu_array);
	$menuleft->showmenu();

	// Affichage des zones de recherche permanantes
	$addzonerecherche=0;
	if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_SOCIETE) $addzonerecherche=1;
	if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT) $addzonerecherche=1;
	if (($conf->produit->enabled || $conf->service->enabled) && $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE) $addzonerecherche=1;

	if ($addzonerecherche  && ($user->rights->societe->lire || $user->rights->produit->lire))
	{
		print '<div class="blockvmenupair">';

		if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_SOCIETE && $user->rights->societe->lire)
		{
			$langs->load("companies");
			printSearchForm(DOL_URL_ROOT.'/societe.php',DOL_URL_ROOT.'/societe.php',
			img_object($langs->trans("List"),'company').' '.$langs->trans("Companies"),'soc','socname');
		}

		if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT && $user->rights->societe->lire)
		{
			$langs->load("companies");
			printSearchForm(DOL_URL_ROOT.'/contact/index.php',DOL_URL_ROOT.'/contact/index.php',
			img_object($langs->trans("List"),'contact').' '.$langs->trans("Contacts"),'contact','contactname','contact');
		}

		if (($conf->produit->enabled || $conf->service->enabled) && $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE && $user->rights->produit->lire)
		{
			$langs->load("products");
			printSearchForm(DOL_URL_ROOT.'/product/liste.php',DOL_URL_ROOT.'/product/index.php',
			img_object($langs->trans("List"),'product').' '.$langs->trans("Products")."/".$langs->trans("Services"),'products','sall','product');
		}

		print '</div>';
	}

	// Zone de recherche supplementaire
	if ($form_search)
	{
		print $form_search;
	}

	// Lien vers l'aide en ligne (uniquement si langue fr_FR)
	if ($helppagename)
	{
		$langs->load("help");

		$helpbaseurl='';
		if ($langs->defaultlang == "fr_FR") $helpbaseurl='http://wiki.dolibarr.org/index.php/%s';

		$helppage=$langs->trans($helppagename);

		if ($helpbaseurl)
		{
			print '<div class="help">';
			print '<a class="help" target="_blank" href="';
			print sprintf($helpbaseurl,$helppage);
			print '">'.$langs->trans("Help").'</a>';
			print '</div>';
		}
	}

	if ($conf->global->MAIN_SHOW_BUGTRACK_LINK == 1)
	{
		// Lien vers le bugtrack
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
	print "<!-- Fin left vertical menu -->\n";

	print "\n";

	print '<!-- fin de zone gauche, debut zone droite -->'."\n";
	//	    print '</div>'."\n";
	//		print '<div class="vmenuplusfiche">'."\n";
	print '</td><td valign="top">'."\n";


	print "\n";
	print '<div class="fiche"> <!-- begin fiche area -->'."\n";

}



/**
 *  \brief   Affiche une zone de recherche
 *  \param   urlaction          Url du post
 *  \param   urlobject          Url du lien sur titre de la zone de recherche
 *  \param   title              Titre de la zone de recherche
 *  \param   htmlmodesearch     'search'
 *  \param   htmlinputname      Nom du champ input du formulaire
 */

function printSearchForm($urlaction,$urlobject,$title,$htmlmodesearch='search',$htmlinputname)
{
	global $langs;
	print '<form action="'.$urlaction.'" method="post">';
	print '<div class="menu_titre">';
	print '<a class="vsmenu" href="'.$urlobject.'">';
	print $title.'</a><br>';
	print '</div>';
	print '<input type="hidden" name="mode" value="search">';
	print '<input type="hidden" name="mode-search" value="'.$htmlmodesearch.'">';
	print '<input type="text" class="flat" name="'.$htmlinputname.'" size="10">&nbsp;';
	print '<input type="submit" class="button" value="'.$langs->trans("Go").'">';
	print "</form>";
}


/**
 *		\brief   	Impression du pied de page DIV + BODY + HTML
 *		\remarks	Ferme 2 div
 * 		\param   	foot    Non utilise
 */

function llxFooter($foot='',$limitIEbug=1)
{
	global $conf, $dolibarr_auto_user, $micro_start_time;

	print "\n".'</div> <!-- end div class="fiche" -->'."\n";

	//    print "\n".'</div> <!-- end div class="vmenuplusfiche" -->'."\n";
	print "\n".'</td></tr></table> <!-- end right area -->'."\n";

	if (! empty($_SERVER['DOL_TUNING']))
	{
		$micro_end_time=dol_microtime_float(true);
		print '<script type="text/javascript">window.status="Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
		if (function_exists("memory_get_usage"))
		{
			print ' - Memory usage: '.memory_get_usage();
		}
		if (function_exists("zend_loader_file_encoded"))
		{
			print ' - Zend encoded file: '.(zend_loader_file_encoded()?'yes':'no');
		}
		print '"</script>';
		print "\n";
	}

	if ($conf->use_javascript_ajax)
	{
		print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_foot.js"></script>';
	}

	// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
	if ($limitIEbug && ! $conf->browser->firefox) print "\n".'<div class="tabsAction">&nbsp;</div>'."\n";

	print "</body>\n";
	print "</html>\n";
}

?>
