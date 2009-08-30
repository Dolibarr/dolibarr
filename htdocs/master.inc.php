<?PHP
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
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
 *	\file       htdocs/master.inc.php
 * 	\ingroup	core
 *  \brief      File that defines environment for all Dolibarr process (pages or scripts)
 *  \version    $Id$
 */

define('DOL_VERSION','2.7.0-beta');	// Also defined in htdocs/install/inc.php
define('EURO',chr(128));

// Definition des constantes syslog
if (function_exists("define_syslog_variables"))
{
	define_syslog_variables();
}
else
{
	// Pour PHP sans syslog (comme sous Windows)
	define('LOG_EMERG',0);
	define('LOG_ALERT',1);
	define('LOG_CRIT',2);
	define('LOG_ERR',3);
	define('LOG_WARNING',4);
	define('LOG_NOTICE',5);
	define('LOG_INFO',6);
	define('LOG_DEBUG',7);
}

// Forcage du parametrage PHP error_reporting (Dolibarr non utilisable en mode error E_ALL)
error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ALL);


// Include configuration
$result=@include_once("conf/conf.php");
if (! $result && $_SERVER["GATEWAY_INTERFACE"])	// If install not done and we are in a web session
{
	header("Location: install/index.php");
	exit;
}
if (empty($dolibarr_main_db_host))
{
	print 'Error: Dolibarr setup was run but was not completed.<br>'."\n";
	print 'Please, run <a href="install/index.php">Dolibarr install process</a> until the end...'."\n";
	exit;
}
if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';   // Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
if (empty($dolibarr_main_data_root))
{
	// Si repertoire documents non defini, on utilise celui par defaut
	$dolibarr_main_data_root=ereg_replace("/htdocs","",$dolibarr_main_document_root);
	$dolibarr_main_data_root.="/documents";
}
// Define some constants
define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);		// Filesystem pages php (htdocs)
define('DOL_DATA_ROOT', $dolibarr_main_data_root);				// Filesystem donnes (documents)
define('DOL_MAIN_URL_ROOT', $dolibarr_main_url_root);			// URL relative root
$uri=eregi_replace('^http(s?)://','',$dolibarr_main_url_root);	// $suburi contains url without http*
$suburi = strstr ($uri, '/');		// $suburi contains url without domain
if ($suburi == '/') $suburi = '';	// If $suburi is /, it is now ''
define('DOL_URL_ROOT', $suburi);	// URL relative root ('/', '/dolibarr', ...)
if (! empty($dolibarr_main_url_root_static)) define('DOL_URL_ROOT_FULL_STATIC', $dolibarr_main_url_root_static);	// Used to put static images on another domain


/*
 * Controle validite fichier conf
 */
if (! file_exists(DOL_DOCUMENT_ROOT ."/lib/functions.lib.php"))
{
	print "Error: Dolibarr config file content seems to be not correctly defined.<br>\n";
	print "Please run dolibarr setup by calling page <b>/install</b>.<br>\n";
	exit;
}


/*
 * Create $conf object
 */

require_once(DOL_DOCUMENT_ROOT ."/lib/functions.lib.php");	// Need 970ko memory (1.1 in 2.2)

// If password is encoded, we decode it
if (eregi('crypted:',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
{
	require_once(DOL_DOCUMENT_ROOT ."/lib/security.lib.php");
	if (eregi('crypted:',$dolibarr_main_db_pass))
	{
		$dolibarr_main_db_pass = eregi_replace('crypted:', '', $dolibarr_main_db_pass);
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
		$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
	}
	else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
}
//print memory_get_usage();

require_once(DOL_DOCUMENT_ROOT."/core/conf.class.php");

$conf = new Conf();

// Identifiant propres au serveur base de donnee
$conf->db->host   = $dolibarr_main_db_host;
if (empty($dolibarr_main_db_port)) $dolibarr_main_db_port=0;		// Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
$conf->db->port   = $dolibarr_main_db_port;
$conf->db->name   = $dolibarr_main_db_name;
$conf->db->user   = $dolibarr_main_db_user;
$conf->db->pass   = $dolibarr_main_db_pass;
if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
$conf->db->type   = $dolibarr_main_db_type;
if (empty($dolibarr_main_db_prefix)) $dolibarr_main_db_prefix='llx_';
$conf->db->prefix = $dolibarr_main_db_prefix;
if (empty($dolibarr_main_db_character_set)) $dolibarr_main_db_character_set='latin1';		// Old installation
$conf->db->character_set=$dolibarr_main_db_character_set;
if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation='latin1_swedish_ci';	// Old installation
$conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;
if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;
// Identifiant autres
$conf->file->main_authentication = empty($dolibarr_main_authentication)?'':$dolibarr_main_authentication;
// Force https
$conf->file->main_force_https = empty($dolibarr_main_force_https)?'':$dolibarr_main_force_https;
// Define charset for HTML Output (can set hidden value force_charset in conf.php file)
if (empty($force_charset_do_notuse)) $force_charset_do_notuse='UTF-8';
$conf->file->character_set_client=strtoupper($force_charset_do_notuse);
// Cookie cryptkey
$conf->file->cookie_cryptkey = empty($dolibarr_main_cookie_cryptkey)?'':$dolibarr_main_cookie_cryptkey;

// Define array of document root directories
$conf->file->dol_document_root=array(DOL_DOCUMENT_ROOT);
if (! empty($dolibarr_main_document_root_alt))
{
	// dolibarr_main_document_root_alt contains several directories
	$values=split('[;,]',$dolibarr_main_document_root_alt);
	foreach($values as $value)
	{
		$conf->file->dol_document_root[]=$value;
	}
}

// Define prefix
if (isset($_SERVER["LLX_DBNAME"])) $dolibarr_main_db_prefix=$_SERVER["LLX_DBNAME"];
define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"]))
{
	if (eregi('firefox',$_SERVER["HTTP_USER_AGENT"]))       $conf->browser->name='firefox';
	elseif (eregi('iceweasel',$_SERVER["HTTP_USER_AGENT"])) $conf->browser->name='iceweasel';
	elseif (eregi('safari',$_SERVER["HTTP_USER_AGENT"]))    $conf->browser->name='safari';
	elseif (eregi('chrome',$_SERVER["HTTP_USER_AGENT"]))    $conf->browser->name='chrome';
	elseif (eregi('opera',$_SERVER["HTTP_USER_AGENT"]))     $conf->browser->name='opera';
	elseif (eregi('msie',$_SERVER["HTTP_USER_AGENT"]))      $conf->browser->name='ie';
	else $conf->browser->name='unknown';
	if (in_array($conf->browser->name,array('firefox','iceweasel'))) $conf->browser->firefox=1;
}

// Chargement des includes principaux de librairies communes
if (! defined('NOREQUIREUSER')) require_once(DOL_DOCUMENT_ROOT ."/user.class.php");		// Need 500ko memory
if (! defined('NOREQUIRETRAN')) require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
if (! defined('NOREQUIRESOC'))  require_once(DOL_DOCUMENT_ROOT ."/societe.class.php");
if (! defined('NOREQUIREDB'))   require_once(DOL_DOCUMENT_ROOT ."/lib/databases/".$conf->db->type.".lib.php");

/*
 * Creation objet $langs (must be before all other code)
 */
if (! defined('NOREQUIRETRAN'))
{
	$langs = new Translate("",$conf);	// A mettre apres lecture de la conf
}

/*
 * Creation objet $db
 */
if (! defined('NOREQUIREDB'))
{
	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

	if ($db->error)
	{
		dol_print_error($db,"host=".$conf->db->host.", port=".$conf->db->port.", user=".$conf->db->user.", databasename=".$conf->db->name.", ".$db->error);
		exit;
	}
}
// Now database connexion is known, so we can forget password
//$dolibarr_main_db_pass=''; 	// Comment this because this constant is used in a lot of pages
$conf->db->pass='';				// This is to avoid password to be shown in memory/swap dump

/*
 * Creation objet $user
 */
if (! defined('NOREQUIREUSER'))
{
	$user = new User($db);
}

/*
 * Load object $conf
 * After this, all parameters conf->global->CONSTANTS are loaded
 */
if (! defined('NOREQUIREDB'))
{
	if (session_id() && isset($_SESSION["dol_entity"]))				// Entity inside an opened session
	{
		$conf->entity = $_SESSION["dol_entity"];
	}
	elseif (isset($_ENV["dol_entity"]))								// Entity inside a CLI script
	{
		$conf->entity = $_ENV["dol_entity"];
	}
	elseif (isset($_POST["loginfunction"]) && isset($_POST["entity"]))	// Just after a login page
	{
		$conf->entity = $_POST["entity"];
	}
	else
	{
		$entityCookieName = 'DOLENTITYID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
		if (isset($_COOKIE[$entityCookieName]) && ! empty($conf->file->cookie_cryptkey)) 						// Just for view specific login page
		{
			include_once(DOL_DOCUMENT_ROOT."/core/cookie.class.php");

			$lastuser = '';
			$lastentity = '';

			$entityCookie = new DolCookie($conf->file->cookie_cryptkey);
			$cookieValue = $entityCookie->_getCookie($entityCookieName);
			list($lastuser, $lastentity) = split('\|', $cookieValue);
			$conf->entity = $lastentity;
		}
	}

	$conf->setValues($db);	// Here we read database (llx_const table) and define $conf->global->XXX var.
}

// If software has been locked. Only login $conf->global->MAIN_ONLY_LOGIN_ALLOWED is allowed.
if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
{
	/*print '$_SERVER["GATEWAY_INTERFACE"]='.$_SERVER["GATEWAY_INTERFACE"].'<br>';
	print 'session_id()='.session_id().'<br>';
	print '$_SESSION["dol_login"]='.$_SESSION["dol_login"].'<br>';
	print '$conf->global->MAIN_ONLY_LOGIN_ALLOWED='.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'<br>';
	exit;*/
	$ok=0;
	if ((! session_id() || ! isset($_SESSION["dol_login"])) && ! isset($_POST["username"]) && ! empty($_SERVER["GATEWAY_INTERFACE"])) $ok=1;	// We let working pages if not logged and inside a web browser (login form, to allow login by admin)
	elseif (isset($_POST["username"]) && $_POST["username"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) $ok=1;				// We let working pages that is a login submission (login submit, to allow login by admin)
	elseif (defined('NOREQUIREDB'))   $ok=1;				// We let working pages that don't need database access (xxx.css.php)
	elseif (defined('EVEN_IF_ONLY_LOGIN_ALLOWED')) $ok=1;	// We let working pages that ask to work even if only login enabled (logout.php)
	elseif (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) $ok=1;	// We let working if user is allowed admin
	if (! $ok)
	{
		if (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] != $conf->global->MAIN_ONLY_LOGIN_ALLOWED)
		{
			print 'Sorry, your application is offline.'."\n";
			print 'You are logged with user "'.$_SESSION["dol_login"].'" and only administrator user "'.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'" is allowed to connect for the moment.'."\n";
			$nexturl=DOL_URL_ROOT.'/user/logout.php';
			print 'Please try later or <a href="'.$nexturl.'">click here to disconnect and change login user</a>...'."\n";
		}
		else
		{
			print 'Sorry, your application is offline. Only administrator user "'.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'" is allowed to connect for the moment.'."\n";
			$nexturl=DOL_URL_ROOT.'/';
			print 'Please try later or <a href="'.$nexturl.'">click here to change login user</a>...'."\n";
		}
		exit;
	}
}

/*
 * Create object $mysoc (A "Societe" object that contains properties of companies managed by Dolibarr.
 */
if (! defined('NOREQUIREDB') && ! defined('NOREQUIRESOC'))
{
	require_once(DOL_DOCUMENT_ROOT ."/societe.class.php");
	$mysoc=new Societe($db);

	$mysoc->id=0;
	$mysoc->nom=$conf->global->MAIN_INFO_SOCIETE_NOM;
	$mysoc->adresse=$conf->global->MAIN_INFO_SOCIETE_ADRESSE;
	$mysoc->cp=$conf->global->MAIN_INFO_SOCIETE_CP;
	$mysoc->ville=$conf->global->MAIN_INFO_SOCIETE_VILLE;
	// Si dans MAIN_INFO_SOCIETE_PAYS on a un id de pays, on recupere code
	if (is_numeric($conf->global->MAIN_INFO_SOCIETE_PAYS))
	{
		$mysoc->pays_id=$conf->global->MAIN_INFO_SOCIETE_PAYS;
		$sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
		$sql .= " WHERE rowid = ".$conf->global->MAIN_INFO_SOCIETE_PAYS;
		$result=$db->query($sql);
		if ($result)
		{
			$obj = $db->fetch_object();
			$mysoc->pays_code=$obj->code;
		}
		else {
			dol_print_error($db);
		}
	}
	// Si dans MAIN_INFO_SOCIETE_PAYS on a deja un code, tout est fait
	else
	{
		$mysoc->pays_code=$conf->global->MAIN_INFO_SOCIETE_PAYS;
	}
	$mysoc->tel=$conf->global->MAIN_INFO_SOCIETE_TEL;
	$mysoc->fax=$conf->global->MAIN_INFO_SOCIETE_FAX;
	$mysoc->url=$conf->global->MAIN_INFO_SOCIETE_WEB;
	// Anciens id prof
	$mysoc->siren=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
	$mysoc->siret=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
	$mysoc->ape=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
	$mysoc->rcs=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
	// Id prof g�n�riques
	$mysoc->profid1=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
	$mysoc->profid2=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
	$mysoc->profid3=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
	$mysoc->profid4=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
	$mysoc->tva_intra=$conf->global->MAIN_INFO_TVAINTRA;
	$mysoc->capital=$conf->global->MAIN_INFO_CAPITAL;
	$mysoc->forme_juridique_code=$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
	$mysoc->email=$conf->global->MAIN_INFO_SOCIETE_MAIL;
	$mysoc->adresse_full=$mysoc->adresse."\n".$mysoc->cp." ".$mysoc->ville;
	$mysoc->logo=$conf->global->MAIN_INFO_SOCIETE_LOGO;
	$mysoc->logo_small=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
	$mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;

	// Define if company use vat or not (Do not use conf->global->FACTURE_TVAOPTION anymore)
	$mysoc->tva_assuj=($conf->global->FACTURE_TVAOPTION=='franchise'?0:1);
}


/*
 * Set default language (must be after the setValues of $conf)
 */
if (! defined('NOREQUIRETRAN'))
{
	$langs->setDefaultLang($conf->global->MAIN_LANG_DEFAULT);
}

/*
 * Pour utiliser d'autres versions des librairies externes que les
 * versions embarquees dans Dolibarr, definir les constantes adequates:
 * Pour FPDF:           FPDF_PATH
 * Pour PHP_WriteExcel: PHP_WRITEEXCEL_PATH
 * Pour MagpieRss:      MAGPIERSS_PATH
 * Pour PHPlot:         PHPLOT_PATH
 * Pour JPGraph:        JPGRAPH_PATH
 * Pour NuSOAP:         NUSOAP_PATH
 * Pour TCPDF:          TCPDF_PATH
 */
// Les path racines
if (! defined('FPDF_PATH'))           { define('FPDF_PATH',          DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdf/'); }
if (! defined('FPDFI_PATH'))          { define('FPDFI_PATH',         DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdfi/'); }
if (! defined('MAGPIERSS_PATH'))      { define('MAGPIERSS_PATH',     DOL_DOCUMENT_ROOT .'/includes/magpierss/'); }
if (! defined('JPGRAPH_PATH'))        { define('JPGRAPH_PATH',       DOL_DOCUMENT_ROOT .'/includes/jpgraph/'); }
if (! defined('NUSOAP_PATH'))         { define('NUSOAP_PATH',        DOL_DOCUMENT_ROOT .'/includes/nusoap/lib/'); }
if (! defined('PHP_WRITEEXCEL_PATH')) { define('PHP_WRITEEXCEL_PATH',DOL_DOCUMENT_ROOT .'/includes/php_writeexcel/'); }
if (! defined('PHPEXCELREADER'))      { define('PHPEXCELREADER',     DOL_DOCUMENT_ROOT .'/includes/phpexcelreader/'); }
// Les autres path
if (! defined('FPDF_FONTPATH'))       { define('FPDF_FONTPATH',      FPDF_PATH . 'font/'); }
if (! defined('MAGPIE_DIR'))          { define('MAGPIE_DIR',         MAGPIERSS_PATH); }
if (! defined('MAGPIE_CACHE_DIR'))    { define('MAGPIE_CACHE_DIR',   $conf->externalrss->dir_temp); }


if (! defined('MAIN_LABEL_MENTION_NPR') ) define('MAIN_LABEL_MENTION_NPR','NPR');
?>
