<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 */

/**
   \file       htdocs/main.inc.php
   \brief      Fichier de formatage generique des ecrans Dolibarr
   \version    $Revision$   
*/

// Forcage du parametrage PHP magic_quotes_gpc et nettoyage des parametres
// (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande il faut juste faire addslashes au moment d'un insert/update.
@set_magic_quotes_runtime(0);
function stripslashes_deep($value)
{
   return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}
if (get_magic_quotes_gpc())
{
   $_GET     = array_map('stripslashes_deep', $_GET);
   $_POST    = array_map('stripslashes_deep', $_POST);
   $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
   $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
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
foreach ($_GET as $key => $val)
{
  if (test_sql_inject($val) > 0)
    unset($_GET[$key]);
}
foreach ($_POST as $key => $val)
{
  if (test_sql_inject($val) > 0)
    unset($_POST[$key]);
}
// Fin filtre des GET et POST


require_once("master.inc.php");

$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";

// Init session
$sessionname="DOLSESSID_".$dolibarr_main_db_name;
session_name($sessionname);
session_start();
dolibarr_syslog("Session name=".$sessionname." Session id()=".session_id().", _SESSION['dol_login']=".$_SESSION["dol_login"]);

/*
 * Phase identification
 */

// $authmode contient la liste des différents modes d'identification à tester
// par ordre de préférence. Attention, rares sont les combinaisons possibles si
// plusieurs modes sont indiqués.
// Example: array('http','dolibarr');
// Example: array('http','dolibarr_mdb2');
// Example: array('ldap');
// Example: array('forceuser');
$authmode=array();

// Authentication mode: non defini (cas de compatibilite ascendante)
if (! $dolibarr_main_authentication)
{
	// Mode par defaut, on test http + dolibarr
	$authmode=array('http','dolibarr');
}

// Authentication mode: http
if ($dolibarr_main_authentication == 'http')
{
	$authmode=array('http');
}
// Authentication mode: dolibarr
if ($dolibarr_main_authentication == 'dolibarr')
{
	$authmode=array('dolibarr');
}
// Authentication mode: dolibarr_mdb2
if ($dolibarr_main_authentication == 'dolibarr_mdb2')
{
	$authmode=array('dolibarr_mdb2');
}
// Authentication mode: ldap
if ($dolibarr_main_authentication == 'ldap')
{
	$authmode=array('ldap');
}
// Authentication mode: forceuser
if ($dolibarr_main_authentication == 'forceuser' || isset($dolibarr_auto_user))
{
	$authmode=array('forceuser');
	if (! isset($dolibarr_auto_user)) $dolibarr_auto_user='auto';
}
// No authentication mode
if (! sizeof($authmode)) 
{
	$langs->load('main');
	dolibarr_print_error('',$langs->trans("ErrorConfigParameterNotDefined",'dolibarr_main_authentication'));
	exit;
}

// Si la demande du login a déjà eu lieu, on le récupère depuis la session
// sinon appel du module qui réalise sa demande.
// A l'issu de cette phase, la variable $login sera définie.
$login='';
if (! session_id() || ! isset($_SESSION["dol_login"]))
{
	// On est pas déjà authentifié, on demande le login/mot de passe
	// A l'issu de cette demande, le login et un jeton doivent avoir été placé
	// en session dans dol_login et la page rappelée.

	// MODE AUTO
	if (in_array('forceuser',$authmode) && ! $login)
	{
		$login=$dolibarr_auto_user;
	    dolibarr_syslog ("Authentification ok (en mode force, login=".$login.")");
	}

	// MODE HTTP (Basic)
	if (in_array('http',$authmode) && ! $login)
	{
		$login=$_SERVER["REMOTE_USER"];
	}

	// MODE DOLIBARR
	if (in_array('dolibarr',$authmode) && ! $login)
	{
    	require_once(PEAR_PATH."/Auth/Auth.php");

    	$pear = $dolibarr_main_db_type.'://'.$dolibarr_main_db_user.':'.$dolibarr_main_db_pass.'@'.$dolibarr_main_db_host.'/'.$dolibarr_main_db_name;

		// \TODO Virer ce test et toujours faire le test sur le champ crypté
		if ($conf->password_encrypted)
		{
			$cryptType = "md5";
			$fieldtotest="pass_crypted";
		}
		else
		{
			$cryptType = "none";
			$fieldtotest="pass";
		}
	    
	    $params = array(
		    "dsn" => $pear,
		    "table" => MAIN_DB_PREFIX."user",
		    "usernamecol" => "login",
		    "passwordcol" => $fieldtotest,
		    "cryptType" => $cryptType,
	    );

	    $aDol = new DOLIAuth("DB", $params, "dol_loginfunction");
	    $aDol->setSessionName($sessionname);
    	$aDol->start();
	    $result = $aDol->getAuth();	// Si deja logue avec succes, renvoie vrai, sinon effectue un redirect sur page loginfunction et renvoie false
	    if ($result)
	    {
	        // Authentification Auth OK, on va chercher le login
			$login=$aDol->getUsername();
	        dolibarr_syslog ("Authentification ok (en mode Pear Base Dolibarr)");
		}
		else
		{
	        if (isset($_POST["loginfunction"]))
	        {
	            // Echec authentification
	            dolibarr_syslog("Authentification ko (en mode Pear Base Dolibarr) pour '".$_POST["username"]."'");
				sleep(1);
	        }
	        else 
	        {
	            // Non authentifie, un redirect sur page logon a été envoyé, on peut finir.
	            //dolibarr_syslog("Authentification non realise");
	        }
	        exit;
        }
	}
	
	// MODE DOLIBARR MDB2
	//Todo: voir pour l'utiliser par défaut
	if (in_array('dolibarr_mdb2',$authmode) && ! $login)
	{
    	require_once(PEAR_PATH."/Auth/Auth.php");

    	$pear = $dolibarr_main_db_type.'://'.$dolibarr_main_db_user.':'.$dolibarr_main_db_pass.'@'.$dolibarr_main_db_host.'/'.$dolibarr_main_db_name;

		if ($conf->password_encrypted)
		{
			$cryptType = "md5";
			$fieldtotest="pass_crypted";
		}
		else
		{
			$cryptType = "none";
			$fieldtotest="pass";
		}
	    
	    $params = array(
		    "dsn" => $pear,
		    "table" => MAIN_DB_PREFIX."user",
		    "usernamecol" => "login",
		    "passwordcol" => $fieldtotest,
		    "cryptType" => $cryptType,
	    );

	    $aDol = new DOLIAuth("MDB2", $params, "dol_loginfunction");
	    $aDol->setSessionName($sessionname);
    	$aDol->start();
	    $result = $aDol->getAuth();	// Si deja logue avec succes, renvoie vrai, sinon effectue un redirect sur page loginfunction et renvoie false
	    if ($result)
	    {
	        // Authentification Auth OK, on va chercher le login
			$login=$aDol->getUsername();
	        dolibarr_syslog ("Authentification ok (en mode Pear Base Dolibarr_mdb2)");
		}
		else
		{
	        if (isset($_POST["loginfunction"]))
	        {
	            // Echec authentification
	            dolibarr_syslog("Authentification ko (en mode Pear Base Dolibarr_mdb2) pour '".$_POST["username"]."'");
				sleep(1);
	        }
	        else 
	        {
	            // Non authentifie
	            //dolibarr_syslog("Authentification non realise");
	        }
	        exit;
        }
	}

	// MODE LDAP
	if (in_array('ldap',$authmode) && ! $login)
	{
		// Authentification Apache KO ou non active, pas de mode force on demande le login
	    require_once(PEAR_PATH."/Auth/Auth.php");
	
		$ldapuserattr=$dolibarr_main_auth_ldap_login_attribute;
		$ldaphost=$dolibarr_main_auth_ldap_host;
		$ldapport=$dolibarr_main_auth_ldap_port;
		$ldapversion=(int) $dolibarr_main_auth_ldap_version;	// Si pas de int, PEAR LDAP plante.
		$ldapdn=$dolibarr_main_auth_ldap_dn;
		$ldapadminlogin=$dolibarr_main_auth_ldap_admin_login;
		$ldapadminpass=$dolibarr_main_auth_ldap_admin_pass;
		$ldapdebug=((! $dolibarr_main_auth_ldap_debug || $dolibarr_main_auth_ldap_debug=="false")?false:true);
		
	    if ($ldapdebug) print "DEBUG: Logging LDAP steps<br>\n";

		// Debut code pour compatibilite (prend info depuis config en base)
		if (! $ldapuserattr && $conf->ldap->enabled)
		{
			if ($conf->global->LDAP_SERVER_TYPE == "activedirectory")
			  {
			    $ldapuserattr = $conf->global->LDAP_FIELD_LOGIN_SAMBA;
			  }
			  else
			  {
			    $ldapuserattr = $conf->global->LDAP_FIELD_LOGIN;
			  }
		}
		if (! $ldaphost)       $ldaphost=$conf->global->LDAP_SERVER_HOST;
		if (! $ldapport)       $ldapport=$conf->global->LDAP_SERVER_PORT;
		if (! $ldapversion)    $ldapversion=(int) $conf->global->LDAP_SERVER_PROTOCOLVERSION;
		if (! $ldapdn)         $ldapdn=$conf->global->LDAP_SERVER_DN;
		if (! $ldapadminlogin) $ldapadminlogin=$conf->global->LDAP_ADMIN_DN;
		if (! $ldapadminpass)  $ldapadminpass=$conf->global->LDAP_ADMIN_PASS;
		// Fin code pour compatiblité
		
	    $params = array(
		    'userattr' => $ldapuserattr,
		    'host' => $ldaphost,
		    'port' => $ldapport,
		    'version' => $ldapversion,
		    'basedn' => $ldapdn,
		    'binddn' => $ldapadminlogin,
		    'bindpw' => $ldapadminpass,
		    'debug' => $ldapdebug, 
		    'userfilter' => ''
	    );
		if ($ldapdebug) print "DEBUG: params=".join(',',$params)."<br>\n";

	    $aDol = new DOLIAuth("LDAP", $params, "dol_loginfunction");
	    $aDol->setSessionName($sessionname);
	    $aDol->start();
	    $result = $aDol->getAuth();	// Si deja logue avec succes, renvoie vrai, sinon effectue un redirect sur page loginfunction et renvoie false
	    if ($result)
	    {
	    	// Authentification Auth OK, on va chercher le login
			  $login=$aDol->getUsername();
	      dolibarr_syslog ("Authentification ok (en mode Pear Base LDAP)");
		  }
	    else
	    {
	        if (isset($_POST["loginfunction"]))
	        {
	           // Echec authentification
	           dolibarr_syslog("Authentification ko (en mode Pear Base LDAP) pour '".$_POST["username"]."'");
	        }
	        else 
	        {
	            // Non authentifie
	            //dolibarr_syslog("Authentification non realise");
	        }
	        exit;
	    }
    }

	// Verification du code
	if ($conf->global->MAIN_SECURITY_ENABLECAPTCHA)
	{
		include_once(DOL_DOCUMENT_ROOT.'/includes/cryptographp/cryptographp.fct.php');
		//print "Info session: ".session_name().session_id();print_r($_SESSION);
		if (! chk_crypt($_POST['code']))
		{
			session_destroy();
			dolibarr_syslog('Bad value for code, connexion refused');

			// On repart sur page accueil
			session_name($sessionname);
			session_start();
			$langs->load('main');
			$langs->load('other');
			$_SESSION["loginmesg"]=$langs->trans("ErrorBadValueForCode");
			header('Location: '.DOL_URL_ROOT.'/index.php');
			exit;
		}
	}
	
	// Charge l'objet user depuis son login ou son SID
	$result=0;
	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
		$ldap=new Ldap();
		$result=$ldap->connect_bind();
		if ($result > 0)
		{
			// On charge les attributs du user ldap
			if ($ldapdebug) print "DEBUG: login ldap = ".$login."<br>\n";
	    $ldap->fetch($login);
	    
	    if ($ldapdebug) print "DEBUG: UACF = ".join(',',$ldap->uacf)."<br>\n";
	    if ($ldapdebug) print "DEBUG: pwdLastSet = ".dolibarr_print_date($ldap->pwdlastset,'day')."<br>\n";
	    if ($ldapdebug) print "DEBUG: badPasswordTime = ".dolibarr_print_date($ldap->badpwdtime,'day')."<br>\n";
	    
	    //TODO : doit etre géré au niveau de PEAR
/*	    
	    // On stop si le mot de passe ldap doit etre modifié
	    if ($ldap->pwdlastset == 0)
	    {
	    	session_destroy();
		    dolibarr_syslog('User '.$login.' must change password next logon');
		    if ($ldapdebug) print "DEBUG: User ".$login." must change password<br>\n";
		    $ldap->close();

		    // On repart sur page accueil
		    session_name($sessionname);
		    session_start();
		    $langs->load('ldap');
		    $_SESSION["loginmesg"]=$langs->trans("UserMustChangePassNextLogon");
		    header('Location: '.DOL_URL_ROOT.'/index.php');
		    exit;
		  }
*/	    
			// On recherche le user dolibarr en fonction de son SID ldap
		  $user->search_sid = $ldap->getObjectSid($login);
		  if ($ldapdebug) print "DEBUG: search_sid = ".$user->search_sid."<br>\n";
		  $result=$user->fetch($login);
		  if ($result)
		  {
		  	//TODO: on vérifie si le login a changé et on met à jour les attributs dolibarr
		  	if ($user->login != $ldap->login && $ldap->login)
		  	{
		  		$user->login = $ldap->login;
		  		$user->update($user);
		  	}
		    //$resultUpdate = $user->update_ldap2dolibarr();
		  }
		}
		else
		{
			session_destroy();
		  dolibarr_syslog('Synchro LDAP KO');
		  if ($ldapdebug) print "DEBUG: Error connect_bind = ".$ldap->error."<br>\n";
		  $ldap->close();

		  // On repart sur page accueil
		  session_name($sessionname);
		  session_start();
		  $langs->load('admin');
		  $_SESSION["loginmesg"]=$langs->trans("LDAPSynchroKO");
		  header('Location: '.DOL_URL_ROOT.'/index.php');
		  exit;
		}
	}
	else
	{
		$result=$user->fetch($login);
	}

	if ($result <= 0)
	{
		session_destroy();
		dolibarr_syslog('User not found, connexion refused');

		// On repart sur page accueil
		session_name($sessionname);
		session_start();
		$langs->load('main');
		if ($result == 0) $_SESSION["loginmesg"]=$langs->trans("ErrorCantLoadUserFromDolibarrDatabase",$login);
		if ($result < 0) $_SESSION["loginmesg"]=$user->error;
		header('Location: '.DOL_URL_ROOT.'/index.php');
		exit;
	}
}
else
{
	// On est déjà en session qui a sauvegardé login
	// Remarks: On ne sauvegarde pas objet user car pose pb dans certains cas mal identifiés
	$login=$_SESSION["dol_login"];
	dolibarr_syslog("This is an already user logged session. _SESSION['dol_login']=".$login);
	$result=$user->fetch($login);
	$login=$user->login;
}

// Est-ce une nouvelle session
if (! isset($_SESSION["dol_login"]))
{
    // Nouvelle session pour ce login
    $_SESSION["dol_login"]=$user->login;
    dolibarr_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"].' Session id='.session_id());
    $user->update_last_login_date();
}

// Module Phenix
if ($conf->phenix->enabled && $user->phenix_login != "" && $conf->phenix->cookie)
{
	// Création du cookie permettant la connexion automatique, valide jusqu'à la fermeture du browser
	if (!isset($HTTP_COOKIE_VARS[$conf->phenix->cookie]))
	{
		setcookie($conf->phenix->cookie, $user->phenix_login.":".$user->phenix_pass_crypted.":1", 0, "/", "", 0);
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


/**
 * Overwrite configs global par configs perso
 * ------------------------------------------
 */
if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT) && $user->conf->MAIN_SIZE_LISTE_LIMIT > 0)
{
    $conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT;
}
if (isset($user->conf->PRODUIT_LIMIT_SIZE))
{
    $conf->produit->limit_size = $user->conf->PRODUIT_LIMIT_SIZE;
}
if (isset($user->conf->MAIN_LANG_DEFAULT) && $user->conf->MAIN_LANG_DEFAULT)
{
    if ($langs->getDefaultLang() != $user->conf->MAIN_LANG_DEFAULT)
    {
        // Si on a un langage perso different du langage courant global
        $langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
        $langs->setPhpLang($user->conf->MAIN_LANG_DEFAULT);
    }
}

// Remplace conf->css par valeur personnalise
if (isset($user->conf->MAIN_THEME) && $user->conf->MAIN_THEME)
{
    $conf->theme=$user->conf->MAIN_THEME;
    $conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
}
// Cas de forcage du style depuis url
if (isset($_GET["theme"]) && $_GET["theme"])
{
    $conf->theme=$_GET["theme"];
    $conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
}
// Si feuille de style en php existe
if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

if (isset($user->conf->MAIN_DISABLE_JAVASCRIPT) && $user->conf->MAIN_DISABLE_JAVASCRIPT)
{
    $conf->use_javascript=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
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

// Only default and auguria menu manage canvas menu (auguria not correctly yet)
if (! eregi('^default',$conf->left_menu) && ! eregi('^auguria',$conf->left_menu)) $conf->global->PRODUCT_CANVAS_ABILITY=0;

// Si besoin de smarty
if ($conf->global->PRODUCT_CANVAS_ABILITY)
{
	// SMARTY
	// Definit dans le fichier de conf
	// $dolibarr_smarty_libs_dir="/home/www/dolibarr/external-libs/smarty/libs/";
	// $dolibarr_smarty_compile="/home/www/dolibarr/documents/temp/smarty_templates";
	// $dolibarr_smarty_cache="/home/www/dolibarr/documents/temp/smarty_cache";
	
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
  dolibarr_syslog ("Authentification ko (en mode Pear Base Dolibarr) car login desactive");
  accessforbidden($langs->trans("ErrorLoginDisabled"));
  exit;
}
			

dolibarr_syslog("Access to ".$_SERVER["PHP_SELF"]);


if (! defined('MAIN_INFO_SOCIETE_PAYS'))
{
  define('MAIN_INFO_SOCIETE_PAYS','1');
}

// On charge les fichiers lang principaux
$langs->load("main");
$langs->load("dict");

/*
 *
 */
if (defined("MAIN_NOT_INSTALLED"))
{
  Header("Location: ".DOL_URL_ROOT."/install/index.php");
  exit;
}

// Constantes utilise pour definir le nombre de lignes des textarea
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
 *  \brief      Affiche en-tete HTML
 *  \param      head    	Lignes d'en-tete head optionnelles
 *  \param      title   	Titre page web
 *	\param		disablejs	N'affiche pas les liens vers les js (Ex: qd fonction utilisée par sous formulaire Ajax)	
 */
function top_htmlhead($head, $title='', $disablejs=0, $disablehead=0) 
{
	global $user, $conf, $langs, $db, $micro_start_time;
	
	// Pour le tuning optionnel. Activer si la variable d'environnement DOL_TUNING
	// est positionne A appeler avant tout.
	if (isset($_SERVER['DOL_TUNING'])) $micro_start_time=dol_microtime_float(true);
	
	if (! $conf->css)  $conf->css ='/theme/eldy/eldy.css.php';

	//header("Content-type: text/html; charset=UTF-8");
	header("Content-type: text/html; charset=".$conf->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	if ($disablehead == 0)
	{
		print "<head>\n";
		
		print $langs->lang_header();
		print $head;

		// Affiche meta
		print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
		print '<meta name="author" content="Dolibarr Development Team">'."\n";

		// Affiche title
		if ($title)
		{
			print '<title>Dolibarr - '.$title.'</title>';
		}
		else
		{
			if (defined("MAIN_TITLE"))
			{
				print "<title>".MAIN_TITLE."</title>";
			}
			else
			{
				print '<title>Dolibarr</title>';
			}
		}
		print "\n";

		// Affiche style sheets et link
		print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
		print '<link rel="stylesheet" type="text/css" media="print" href="'.DOL_URL_ROOT.'/theme/print.css">'."\n";
		
		// Style sheets pour la class Window
		print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/window/default.css">'."\n";
		print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/window/alphacube.css">'."\n";
		print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/window/alert.css">'."\n";

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

		if (! $disablejs && ($conf->use_javascript || $conf->use_ajax))
		{
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_head.js"></script>'."\n";
		}
		if (! $disablejs && $conf->use_ajax)
		{
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype.js"></script>'."\n";
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js"></script>'."\n";
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/effects.js"></script>'."\n";
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/controls.js"></script>'."\n";
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/window/window.js"></script>'."\n";
			print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/window/tooltip.js"></script>'."\n";
			
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
function top_menu($head, $title="", $target="") 
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
    if (isset($conf->global->MAIN_NEED_UPDATE) && $conf->global->MAIN_NEED_UPDATE)
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

    // Lien logout
    if (! isset($_SERVER["REMOTE_USER"]) || ! $_SERVER["REMOTE_USER"])
    {
        $title=$langs->trans("Logout");
        $title.='<br><b>'.$langs->trans("ConnectedSince").'</b>: '.dolibarr_print_date($user->datelastlogin,"dayhour");
        if ($dolibarr_main_authentication) $title.='<br><b>'.$langs->trans("AuthenticationMode").'</b>: '.$dolibarr_main_authentication;

        $text='';
		$text.='<a href="'.DOL_URL_ROOT.'/user/logout.php"';
        $text.=$menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
        $text.='>';
        $text.='<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
        $text.=' alt="" title=""';
        $text.='>';
        $text.='</a>';

		$html=new Form($db);
		print $html->textwithtooltip('',$title,2,1,$text);

//        print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
//        print ' alt="'.$title.'" title="'.$title.'"';
//        print '>';
    }

    print "\n</div>\n<!-- End top horizontal menu -->\n";

}


/**
 *  \brief      Affiche barre de menu gauche
 *  \param      menu_array      Tableau des entrees de menu
 *  \param      help_url        Url pour le lien aide ('' par defaut)
 *  \param      form_search     Formulaire de recherche permanant supplementaire
 */
function left_menu($menu_array, $help_url='', $form_search='')
{
    global $user, $conf, $langs, $db;

    print '<div class="vmenuplusfiche">'."\n";
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
    $menu=new MenuLeft($db,$menu_array);
    $menu->showmenu();

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
    if ($help_url)
    {
        $helpbaseurl='';
        if ($langs->defaultlang == "fr_FR") $helpbaseurl='http://www.dolibarr.com/wikidev/index.php/%s';

        if ($helpbaseurl) print '<div class="help"><a class="help" target="_blank" href="'.sprintf($helpbaseurl,$help_url).'">'.$langs->trans("Help").'</a></div>';
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

	// Cas special pour auguria. 
	// On le met pour tous les autres styles sinon ko avec IE6 et resolution autre que 1024x768
	if ($conf->theme != 'auguria')
	{
	    print "\n";
	    print '</div>'."\n";
	    print '<!-- fin de zone gauche, debut zon droite -->';
		print '<div class="vmenuplusfiche">'."\n";
	    print "\n";
	}
    
    print '<!-- fiche -->'."\n";
    print '<div class="fiche">'."\n";

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
    print '<a class="vmenu" href="'.$urlobject.'">';
    print $title.'</a><br>';
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
    
    print "\n</div>\n".'<!-- end div class="fiche" -->'."\n";
    print "\n</div>\n".'<!-- end div class="vmenuplusfiche" -->'."\n";
    
    if (isset($_SERVER['DOL_TUNING']))
    {
		$micro_end_time=dol_microtime_float(true);
		print '<script language="javascript" type="text/javascript">window.status="Build time: '.ceil(1000*($micro_end_time-$micro_start_time)).' ms';
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

    if ($conf->use_javascript)
    {
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_foot.js"></script>';
    }

    // Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
    if ($limitIEbug && ! $conf->browser->firefox) print "\n".'<div class="tabsAction">&nbsp;</div>'."\n";
    
    print "</body>\n";
    print "</html>\n";
}

?>
