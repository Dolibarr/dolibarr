<?PHP
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 * 				This script reads the conf.php file, init $lang, $db and empty $user
 *  \version    $Id$
 */

define('DOL_VERSION','3.0.0-alpha');	// Also defined in htdocs/install/inc.php (Ex: x.y.z-alpha, x.y.z)
define('EURO',chr(128));

// Definition des constantes syslog
if (function_exists("define_syslog_variables"))
{
	if (version_compare(PHP_VERSION, '5.3.0', '<'))
	{
		define_syslog_variables(); // Deprecated since php 5.3.0, syslog variables no longer need to be initialized
	}
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
if (! $result && ! empty($_SERVER["GATEWAY_INTERFACE"]))    // If install not done and we are in a web session
{
    header("Location: install/index.php");
    exit;
}

// Security: CSRF protection
// This test check if referrer ($_SERVER['HTTP_REFERER']) is same web site than Dolibarr ($_SERVER['HTTP_HOST'])
// when we post forms (we allow GET to allow direct link to access a particular page).
if (! defined('NOCSRFCHECK') && empty($dolibarr_nocsrfcheck) && ! empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'GET' && ! empty($_SERVER['HTTP_HOST']) && ! empty($_SERVER['HTTP_REFERER']) && ! preg_match('/'.preg_quote($_SERVER['HTTP_HOST'],'/').'/i', $_SERVER['HTTP_REFERER']))
{
    //print 'HTTP_POST='.$_SERVER['HTTP_HOST'].' HTTP_REFERER='.$_SERVER['HTTP_REFERER'];
    print "Access refused by CSRF protection in main.inc.php.\n";
    print "If you access your server behind a proxy using url rewriting, you might add the line \$dolibarr_nocsrfcheck=1 into your conf.php file.\n";
    die;
}
if (empty($dolibarr_main_db_host))
{
	print 'Dolibarr setup was run but was not completed.<br>'."\n";
	print 'Please, click <a href="install/index.php">here to finish Dolibarr install process</a> ...'."\n";
	die;
}
if (empty($dolibarr_main_url_root))
{
	print 'Value for parameter \'dolibarr_main_url_root\' is not defined in your \'htdocs\conf\conf.php\' file.<br>'."\n";
	print 'You must add this parameter with your full Dolibarr root Url (Example: http://myvirtualdomain/ or http://mydomain/mydolibarrurl/)'."\n";
	die;
}
if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';   // Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
if (empty($dolibarr_main_data_root))
{
	// Si repertoire documents non defini, on utilise celui par defaut
	$dolibarr_main_data_root=str_replace("/htdocs","",$dolibarr_main_document_root);
	$dolibarr_main_data_root.="/documents";
}

// Define some constants
define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);			// Filesystem core php (htdocs)
define('DOL_DATA_ROOT', $dolibarr_main_data_root);					// Filesystem data (documents)
define('DOL_CLASS_PATH', 'class/');									// Filesystem path to class dir
define('DOL_CUSTOM_PATH', DOL_DOCUMENT_ROOT . '/custom');			// Filesystem path to custom dir
define('DOL_DOCUMENT_EXTMODULE', DOL_CUSTOM_PATH . '/modules');			// Filesystem path to external modules dir
// If dolibarr_main_url_root = auto (Hidden feature for developers only), we try to forge it.
if ($dolibarr_main_url_root == 'auto' && ! empty($_SERVER["SCRIPT_URL"]) && ! empty($_SERVER["SCRIPT_URI"]))
{
	$dolibarr_main_url_root=str_replace($_SERVER["SCRIPT_URL"],'',$_SERVER["SCRIPT_URI"]);
}
define('DOL_MAIN_URL_ROOT', $dolibarr_main_url_root);			// URL relative root
$uri=preg_replace('/^http(s?):\/\//i','',constant('DOL_MAIN_URL_ROOT'));	// $uri contains url without http*
$suburi = strstr ($uri, '/');		// $suburi contains url without domain
if ($suburi == '/') $suburi = '';	// If $suburi is /, it is now ''
define('DOL_URL_ROOT', $suburi);	// URL relative root ('', '/dolibarr', ...)
if (! empty($dolibarr_main_url_root_static)) define('DOL_URL_ROOT_FULL_STATIC', $dolibarr_main_url_root_static);	// Used to put static images on another domain
define('DOL_URL_EXTMODULE', DOL_URL_ROOT . '/custom/modules');	// URL relative for external modules

/*
 * Include functions
 */

if (! file_exists(DOL_DOCUMENT_ROOT ."/lib/functions.lib.php"))
{
	print "Error: Dolibarr config file content seems to be not correctly defined.<br>\n";
	print "Please run dolibarr setup by calling page <b>/install</b>.<br>\n";
	exit;
}

require_once(DOL_DOCUMENT_ROOT ."/lib/functions.lib.php");	// Need 970ko memory (1.1 in 2.2)


// If password is encoded, we decode it
if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
{
	require_once(DOL_DOCUMENT_ROOT ."/lib/security.lib.php");
	if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
	{
		$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
		$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
	}
	else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
}
//print memory_get_usage();


/*
 * Create $conf object
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/conf.class.php");

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
if (empty($dolibarr_main_limit_users)) $dolibarr_main_limit_users=0;
$conf->file->main_limit_users = $dolibarr_main_limit_users;
if (defined('TEST_DB_FORCE_TYPE')) $conf->db->type=constant('TEST_DB_FORCE_TYPE');	// For test purpose
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
$conf->file->dol_document_root=array(DOL_DOCUMENT_ROOT, DOL_CUSTOM_PATH);
if (! empty($dolibarr_main_document_root_alt))
{
	// dolibarr_main_document_root_alt contains several directories
	$values=preg_split('/[;,]/',$dolibarr_main_document_root_alt);
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
	// If phone/smartphone, we set phone os name.
	if (preg_match('/android/i',$_SERVER["HTTP_USER_AGENT"]))			$conf->browser->phone='android';
	elseif (preg_match('/blackberry/i',$_SERVER["HTTP_USER_AGENT"]))	$conf->browser->phone='blackberry';
	elseif (preg_match('/iphone/i',$_SERVER["HTTP_USER_AGENT"]))		$conf->browser->phone='iphone';
	elseif (preg_match('/ipod/i',$_SERVER["HTTP_USER_AGENT"]))			$conf->browser->phone='iphone';
	elseif (preg_match('/palm/i',$_SERVER["HTTP_USER_AGENT"]))			$conf->browser->phone='palm';
	elseif (preg_match('/symbian/i',$_SERVER["HTTP_USER_AGENT"]))		$conf->browser->phone='symbian';
	elseif (preg_match('/webos/i',$_SERVER["HTTP_USER_AGENT"]))			$conf->browser->phone='webos';
	elseif (preg_match('/maemo/i',$_SERVER["HTTP_USER_AGENT"]))			$conf->browser->phone='maemo';
	// MS products at end
	elseif (preg_match('/iemobile/i',$_SERVER["HTTP_USER_AGENT"]))		$conf->browser->phone='windowsmobile';
	elseif (preg_match('/windows ce/i',$_SERVER["HTTP_USER_AGENT"]))	$conf->browser->phone='windowsmobile';
	// Name
	if (preg_match('/firefox/i',$_SERVER["HTTP_USER_AGENT"]))       $conf->browser->name='firefox';
	elseif (preg_match('/chrome/i',$_SERVER["HTTP_USER_AGENT"]))    $conf->browser->name='chrome';
	elseif (preg_match('/iceweasel/i',$_SERVER["HTTP_USER_AGENT"])) $conf->browser->name='iceweasel';
	elseif ((empty($conf->browser->phone) || preg_match('/iphone/i',$_SERVER["HTTP_USER_AGENT"])) && preg_match('/safari/i',$_SERVER["HTTP_USER_AGENT"]))    $conf->browser->name='safari';	// Safari is often present in string but its not.
	elseif (preg_match('/opera/i',$_SERVER["HTTP_USER_AGENT"]))     $conf->browser->name='opera';
	// MS products at end
	elseif (preg_match('/msie/i',$_SERVER["HTTP_USER_AGENT"]))      $conf->browser->name='ie';
	else $conf->browser->name='unknown';
	// Other
	if (in_array($conf->browser->name,array('firefox','iceweasel'))) $conf->browser->firefox=1;
}

// Chargement des includes principaux de librairies communes
if (! defined('NOREQUIREUSER')) require_once(DOL_DOCUMENT_ROOT ."/user/class/user.class.php");		// Need 500ko memory
if (! defined('NOREQUIRETRAN')) require_once(DOL_DOCUMENT_ROOT ."/core/class/translate.class.php");
if (! defined('NOREQUIRESOC'))  require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");
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
	// By default conf->entity is 1, but we change this if we ask another value.
	if (session_id() && ! empty($_SESSION["dol_entity"]))				// Entity inside an opened session
	{
		$conf->entity = $_SESSION["dol_entity"];
	}
	elseif (! empty($_ENV["dol_entity"]))								// Entity inside a CLI script
	{
		$conf->entity = $_ENV["dol_entity"];
	}
	elseif (isset($_POST["loginfunction"]) && ! empty($_POST["entity"]))	// Just after a login page
	{
		$conf->entity = $_POST["entity"];
	}
	else
	{
		// Add real path in session name
		$realpath='';
		if ( preg_match('/^([^.]+)\/htdocs\//i', realpath($_SERVER["SCRIPT_FILENAME"]), $regs))	$realpath = isset($regs[1])?$regs[1]:'';

		$entityCookieName = 'DOLENTITYID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].$realpath);
		if (! empty($_COOKIE[$entityCookieName]) && ! empty($conf->file->cookie_cryptkey)) 						// Just for view specific login page
		{
			include_once(DOL_DOCUMENT_ROOT."/core/class/cookie.class.php");

			$lastuser = '';
			$lastentity = '';

			$entityCookie = new DolCookie($conf->file->cookie_cryptkey);
			$cookieValue = $entityCookie->_getCookie($entityCookieName);
			list($lastuser, $lastentity) = explode('|', $cookieValue);
			$conf->entity = $lastentity;
		}
	}

	//print "Will work with data into entity instance number '".$conf->entity."'";

	// Here we read database (llx_const table) and define $conf->global->XXX var.
	$conf->setValues($db);
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
	require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");
	$mysoc=new Societe($db);

	$mysoc->id=0;
	$mysoc->nom=$conf->global->MAIN_INFO_SOCIETE_NOM; 			// TODO deprecated
	$mysoc->name=$conf->global->MAIN_INFO_SOCIETE_NOM;
	$mysoc->adresse=$conf->global->MAIN_INFO_SOCIETE_ADRESSE; 	// TODO deprecated
	$mysoc->address=$conf->global->MAIN_INFO_SOCIETE_ADRESSE;
	$mysoc->cp=$conf->global->MAIN_INFO_SOCIETE_CP; 			// TODO deprecated
	$mysoc->zip=$conf->global->MAIN_INFO_SOCIETE_CP;
	$mysoc->ville=$conf->global->MAIN_INFO_SOCIETE_VILLE; 		// TODO deprecated
	$mysoc->town=$conf->global->MAIN_INFO_SOCIETE_VILLE;
	$mysoc->state_id=$conf->global->MAIN_INFO_SOCIETE_DEPARTEMENT;
	$mysoc->departement_id=$conf->global->MAIN_INFO_SOCIETE_DEPARTEMENT;	// TODO deprecated
	$mysoc->note=empty($conf->global->MAIN_INFO_SOCIETE_NOTE)?'':$conf->global->MAIN_INFO_SOCIETE_NOTE;

    // We define pays_id, pays_code and pays_label
    $tmp=explode(':',$conf->global->MAIN_INFO_SOCIETE_PAYS);
    $country_id=$tmp[0];
    if (! empty($tmp[1]))   // If $conf->global->MAIN_INFO_SOCIETE_PAYS is "id:code:label"
    {
        $country_code=$tmp[1];
        $country_label=$tmp[2];
    }
    else                    // For backward compatibility
    {
        dol_syslog("Your country setup use an old syntax. Reedit it in setup area.", LOG_WARNING);
        include_once(DOL_DOCUMENT_ROOT.'/lib/company.lib.php');
        $country_code=getCountry($country_id,2,$db);  // This need a SQL request, but it's the old feature
        $country_label=getCountry($country_id,0,$db);  // This need a SQL request, but it's the old feature
    }
    $mysoc->pays_id=$country_id;		// TODO deprecated
    $mysoc->country_id=$country_id;
    $mysoc->pays_code=$country_code;	// TODO deprecated
    $mysoc->country_code=$country_code;
    $mysoc->country=$country_label;
    if (is_object($langs)) $mysoc->country=($langs->trans('Country'.$country_code)!='Country'.$country_code)?$langs->trans('Country'.$country_code):$country_label;
    $mysoc->pays=$mysoc->country;    	// TODO deprecated

	$mysoc->tel=empty($conf->global->MAIN_INFO_SOCIETE_TEL)?'':$conf->global->MAIN_INFO_SOCIETE_TEL;   // TODO deprecated
	$mysoc->phone=empty($conf->global->MAIN_INFO_SOCIETE_TEL)?'':$conf->global->MAIN_INFO_SOCIETE_TEL;
	$mysoc->fax=empty($conf->global->MAIN_INFO_SOCIETE_FAX)?'':$conf->global->MAIN_INFO_SOCIETE_FAX;
	$mysoc->url=empty($conf->global->MAIN_INFO_SOCIETE_WEB)?'':$conf->global->MAIN_INFO_SOCIETE_WEB;
	// Anciens id prof
	$mysoc->siren=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
	$mysoc->siret=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
	$mysoc->ape=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
	$mysoc->rcs=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
	// Id prof generiques
	$mysoc->idprof1=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
	$mysoc->idprof2=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
	$mysoc->idprof3=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
	$mysoc->idprof4=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
	$mysoc->tva_intra=$conf->global->MAIN_INFO_TVAINTRA;	// VAT number, not necessarly INTRA.
	$mysoc->capital=$conf->global->MAIN_INFO_CAPITAL;
	$mysoc->forme_juridique_code=$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
	$mysoc->email=$conf->global->MAIN_INFO_SOCIETE_MAIL;
	$mysoc->logo=$conf->global->MAIN_INFO_SOCIETE_LOGO;
	$mysoc->logo_small=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
	$mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;

	// Define if company use vat or not (Do not use conf->global->FACTURE_TVAOPTION anymore)
	$mysoc->tva_assuj=((isset($conf->global->FACTURE_TVAOPTION) && $conf->global->FACTURE_TVAOPTION=='franchise')?0:1);

	// Define if company use local taxes
	$mysoc->localtax1_assuj=((isset($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')?1:0);
	$mysoc->localtax2_assuj=((isset($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')?1:0);

	// For some countries, we need to invert our address with customer address
	if ($mysoc->pays_code == 'DE' && ! isset($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $conf->global->MAIN_INVERT_SENDER_RECIPIENT=1;
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
if (! defined('MAGPIE_DIR'))          { define('MAGPIE_DIR',         MAGPIERSS_PATH); }
if (! defined('MAGPIE_CACHE_DIR'))    { define('MAGPIE_CACHE_DIR',   $conf->externalrss->dir_temp); }


if (! defined('MAIN_LABEL_MENTION_NPR') ) define('MAIN_LABEL_MENTION_NPR','NPR');

?>
