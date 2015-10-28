<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
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
 *	\file       htdocs/filefunc.inc.php
 * 	\ingroup	core
 *  \brief      File that include conf.php file and commons lib like functions.lib.php
 */

if (! defined('DOL_VERSION')) define('DOL_VERSION','3.9.0-beta');

if (! defined('EURO')) define('EURO',chr(128));

// Define syslog constants
if (! defined('LOG_DEBUG'))
{
	if (! function_exists("syslog")) {
		// For PHP versions without syslog (like running on Windows OS)
		define('LOG_EMERG',0);
		define('LOG_ALERT',1);
		define('LOG_CRIT',2);
		define('LOG_ERR',3);
		define('LOG_WARNING',4);
		define('LOG_NOTICE',5);
		define('LOG_INFO',6);
		define('LOG_DEBUG',7);
	}
}

// End of common declaration part
if (defined('DOL_INC_FOR_VERSION_ERROR')) return;


// Define vars
$conffiletoshowshort = "conf.php";
// Define localization of conf file
// --- Start of part replaced by Dolibarr packager makepack-dolibarr
$conffile = "conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
//$conffile = "/etc/dolibarr/conf.php";
//$conffiletoshow = "/etc/dolibarr/conf.php";


// Include configuration
// --- End of part replaced by Dolibarr packager makepack-dolibarr

// Replace conf filename with "conf" parameter on url by GET
/* Disabled. This is a serious security hole
if (! empty($_GET['conf']))
{
	$confname=basename($_GET['conf']);
    setcookie('dolconf', $confname, 0, '/');
    $conffile = 'conf/'.$confname.'.php';
} else {
	$confname=basename(empty($_COOKIE['dolconf']) ? 'conf' : $_COOKIE['dolconf']);
	$conffile = 'conf/'.$confname.'.php';
}
*/

// Include configuration
$result=@include_once $conffile;	// Keep @ because with some error reporting this break the redirect

if (! $result && ! empty($_SERVER["GATEWAY_INTERFACE"]))    // If install not done and we are in a web session
{
	// Note: If calling page was not into htdocs (index.php, ...), then this redirect will fails.
	// There is no real solution, because the only way to know the apache url relative path is to have into conf file.
	header("Location: install/index.php");
	exit;
}

// Force PHP error_reporting setup (Dolibarr may report warning without this)
if (! empty($dolibarr_strict_mode))
{
	error_reporting(E_ALL | E_STRICT);
}
else
{
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_DEPRECATED));
}

// Disable php display errors
if (! empty($dolibarr_main_prod)) ini_set('display_errors','Off');

// Clean parameters
$dolibarr_main_data_root=trim($dolibarr_main_data_root);
$dolibarr_main_url_root=trim(preg_replace('/\/+$/','',$dolibarr_main_url_root));
$dolibarr_main_url_root_alt=(empty($dolibarr_main_url_root_alt)?'':trim($dolibarr_main_url_root_alt));
$dolibarr_main_document_root=trim($dolibarr_main_document_root);
$dolibarr_main_document_root_alt=(empty($dolibarr_main_document_root_alt)?'':trim($dolibarr_main_document_root_alt));

if (empty($dolibarr_main_db_port)) $dolibarr_main_db_port=0;		// Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
if (empty($dolibarr_main_db_prefix)) $dolibarr_main_db_prefix='llx_';
if (empty($dolibarr_main_db_character_set)) $dolibarr_main_db_character_set=($dolibarr_main_db_type=='mysql'?'utf8':'');		// Old installation
if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation=($dolibarr_main_db_type=='mysql'?'utf8_general_ci':'');	// Old installation
if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
if (empty($dolibarr_main_limit_users)) $dolibarr_main_limit_users=0;
if (empty($dolibarr_mailing_limit_sendbyweb)) $dolibarr_mailing_limit_sendbyweb=0;
if (empty($dolibarr_strict_mode)) $dolibarr_strict_mode=0; // For debug in php strict mode
// TODO Multicompany Remove this. Useless.
if (empty($multicompany_transverse_mode)) $multicompany_transverse_mode=0;
if (empty($multicompany_force_entity)) $multicompany_force_entity=0; // To force entity in login page

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
	print '<div align="center">Dolibarr setup is not yet complete.<br><br>'."\n";
	print '<a href="install/index.php">Click here to finish Dolibarr install process</a> ...</div>'."\n";
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
define('DOL_CLASS_PATH', 'class/');									// Filesystem path to class dir (defined only for some code that want to be compatible with old versions without this parameter)
define('DOL_DATA_ROOT', $dolibarr_main_data_root);					// Filesystem data (documents)
define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);			// Filesystem core php (htdocs)
// Try to autodetect DOL_MAIN_URL_ROOT and DOL_URL_ROOT.
// Note: autodetect works only in case 1, 2, 3 and 4 of phpunit test CoreTest.php. For case 5, 6, only setting value into conf.php will works.
$tmp='';
$found=0;
$real_dolibarr_main_document_root=str_replace('\\','/',realpath($dolibarr_main_document_root));	// A) Value found into config file, to say where are store htdocs files. Ex: C:/xxx/dolibarr, C:/xxx/dolibarr/htdocs
$pathroot=$_SERVER["DOCUMENT_ROOT"];															// B) Value reported by web server setup, to say where is root of web server instance. Ex: C:/xxx/dolibarr, C:/xxx/dolibarr/htdocs
$paths=explode('/',str_replace('\\','/',$_SERVER["SCRIPT_NAME"]));								// C) Value reported by web server, to say full path on filesystem of a file. Ex: /dolibarr/htdocs/admin/system/phpinfo.php
// Try to detect if $_SERVER["DOCUMENT_ROOT"]+start of $_SERVER["SCRIPT_NAME"] is $dolibarr_main_document_root. If yes, relative url to add before dol files is this start part.
$concatpath='';
foreach($paths as $tmppath)	// We check to find (B+start of C)=A
{
    if (empty($tmppath)) continue;
    $concatpath.='/'.$tmppath;
    //if ($tmppath) $concatpath.='/'.$tmppath;
    //print $_SERVER["SCRIPT_NAME"].'-'.$pathroot.'-'.$concatpath.'-'.$real_dolibarr_main_document_root.'-'.realpath($pathroot.$concatpath).'<br>';
    if ($real_dolibarr_main_document_root == @realpath($pathroot.$concatpath))    // @ avoid warning when safe_mode is on.
    {
        //print "Found relative url = ".$concatpath;
    	$tmp3=$concatpath;
        $found=1;
        break;
    }
    //else print "Not found yet for concatpath=".$concatpath."<br>\n";
}
if (! $found) $tmp=$dolibarr_main_url_root; // If autodetect fails (Ie: when using apache alias that point outside default DOCUMENT_ROOT).
else $tmp='http'.(((empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on') && (empty($_SERVER["SERVER_PORT"])||$_SERVER["SERVER_PORT"]!=443))?'':'s').'://'.$_SERVER["SERVER_NAME"].((empty($_SERVER["SERVER_PORT"])||$_SERVER["SERVER_PORT"]==80||$_SERVER["SERVER_PORT"]==443)?'':':'.$_SERVER["SERVER_PORT"]).($tmp3?(preg_match('/^\//',$tmp3)?'':'/').$tmp3:'');
//print "tmp1=".$tmp1." tmp2=".$tmp2." tmp3=".$tmp3." tmp=".$tmp."\n";
if (! empty($dolibarr_main_force_https)) $tmp=preg_replace('/^http:/i','https:',$tmp);
define('DOL_MAIN_URL_ROOT', $tmp);											// URL absolute root (https://sss/dolibarr, ...)
$uri=preg_replace('/^http(s?):\/\//i','',constant('DOL_MAIN_URL_ROOT'));	// $uri contains url without http*
$suburi = strstr($uri, '/');												// $suburi contains url without domain:port
if ($suburi == '/') $suburi = '';											// If $suburi is /, it is now ''
define('DOL_URL_ROOT', $suburi);											// URL relative root ('', '/dolibarr', ...)

//print DOL_MAIN_URL_ROOT.'-'.DOL_URL_ROOT."\n";

// Define prefix MAIN_DB_PREFIX
define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);


/*
 * Define PATH to external libraries
 * To use other version than embeded libraries, define here constant to path. Use '' to use include class path autodetect.
 */
// Path to root libraries
if (! defined('ADODB_PATH'))           { define('ADODB_PATH',           (!isset($dolibarr_lib_ADODB_PATH))?DOL_DOCUMENT_ROOT.'/includes/adodbtime/':(empty($dolibarr_lib_ADODB_PATH)?'':$dolibarr_lib_ADODB_PATH.'/')); }
if (! defined('FPDF_PATH'))            { define('FPDF_PATH',            (empty($dolibarr_lib_FPDF_PATH))?DOL_DOCUMENT_ROOT.'/includes/fpdf/':$dolibarr_lib_FPDF_PATH.'/'); }	// Used only for package that can't include tcpdf
if (! defined('TCPDF_PATH'))           { define('TCPDF_PATH',           (empty($dolibarr_lib_TCPDF_PATH))?DOL_DOCUMENT_ROOT.'/includes/tecnick.com/tcpdf/':$dolibarr_lib_TCPDF_PATH.'/'); }
if (! defined('FPDI_PATH'))            { define('FPDI_PATH',            (empty($dolibarr_lib_FPDI_PATH))?DOL_DOCUMENT_ROOT.'/includes/fpdfi/':$dolibarr_lib_FPDI_PATH.'/'); }
if (! defined('TCPDI_PATH'))           { define('TCPDI_PATH',           (empty($dolibarr_lib_TCPDI_PATH))?DOL_DOCUMENT_ROOT.'/includes/tcpdi/':$dolibarr_lib_TCPDI_PATH.'/'); }
if (! defined('NUSOAP_PATH'))          { define('NUSOAP_PATH',          (!isset($dolibarr_lib_NUSOAP_PATH))?DOL_DOCUMENT_ROOT.'/includes/nusoap/lib/':(empty($dolibarr_lib_NUSOAP_PATH)?'':$dolibarr_lib_NUSOAP_PATH.'/')); }
if (! defined('PHPEXCEL_PATH'))        { define('PHPEXCEL_PATH',        (!isset($dolibarr_lib_PHPEXCEL_PATH))?DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpexcel/Classes/':(empty($dolibarr_lib_PHPEXCEL_PATH)?'':$dolibarr_lib_PHPEXCEL_PATH.'/')); }
if (! defined('GEOIP_PATH'))           { define('GEOIP_PATH',           (!isset($dolibarr_lib_GEOIP_PATH))?DOL_DOCUMENT_ROOT.'/includes/geoip/':(empty($dolibarr_lib_GEOIP_PATH)?'':$dolibarr_lib_GEOIP_PATH.'/')); }
if (! defined('ODTPHP_PATH'))          { define('ODTPHP_PATH',          (!isset($dolibarr_lib_ODTPHP_PATH))?DOL_DOCUMENT_ROOT.'/includes/odtphp/':(empty($dolibarr_lib_ODTPHP_PATH)?'':$dolibarr_lib_ODTPHP_PATH.'/')); }
if (! defined('ODTPHP_PATHTOPCLZIP'))  { define('ODTPHP_PATHTOPCLZIP',  (!isset($dolibarr_lib_ODTPHP_PATHTOPCLZIP))?DOL_DOCUMENT_ROOT.'/includes/odtphp/zip/pclzip/':(empty($dolibarr_lib_ODTPHP_PATHTOPCLZIP)?'':$dolibarr_lib_ODTPHP_PATHTOPCLZIP.'/')); }
if (! defined('JS_CKEDITOR'))          { define('JS_CKEDITOR',          (!isset($dolibarr_js_CKEDITOR))?'':(empty($dolibarr_js_CKEDITOR)?'':$dolibarr_js_CKEDITOR.'/')); }
if (! defined('JS_JQUERY'))            { define('JS_JQUERY',            (!isset($dolibarr_js_JQUERY))?'':(empty($dolibarr_js_JQUERY)?'':$dolibarr_js_JQUERY.'/')); }
if (! defined('JS_JQUERY_UI'))         { define('JS_JQUERY_UI',         (!isset($dolibarr_js_JQUERY_UI))?'':(empty($dolibarr_js_JQUERY_UI)?'':$dolibarr_js_JQUERY_UI.'/')); }
if (! defined('JS_JQUERY_FLOT'))       { define('JS_JQUERY_FLOT',       (!isset($dolibarr_js_JQUERY_FLOT))?'':(empty($dolibarr_js_JQUERY_FLOT)?'':$dolibarr_js_JQUERY_FLOT.'/')); }
// Other required path
if (! defined('DOL_DEFAULT_TTF'))      { define('DOL_DEFAULT_TTF',      (!isset($dolibarr_font_DOL_DEFAULT_TTF))?DOL_DOCUMENT_ROOT.'/includes/fonts/Aerial.ttf':(empty($dolibarr_font_DOL_DEFAULT_TTF)?'':$dolibarr_font_DOL_DEFAULT_TTF)); }
if (! defined('DOL_DEFAULT_TTF_BOLD')) { define('DOL_DEFAULT_TTF_BOLD', (!isset($dolibarr_font_DOL_DEFAULT_TTF_BOLD))?DOL_DOCUMENT_ROOT.'/includes/fonts/AerialBd.ttf':(empty($dolibarr_font_DOL_DEFAULT_TTF_BOLD)?'':$dolibarr_font_DOL_DEFAULT_TTF_BOLD)); }


/*
 * Include functions
 */

if (! defined('ADODB_DATE_VERSION')) include_once ADODB_PATH.'adodb-time.inc.php';

if (! file_exists(DOL_DOCUMENT_ROOT ."/core/lib/functions.lib.php"))
{
	print "Error: Dolibarr config file content seems to be not correctly defined.<br>\n";
	print "Please run dolibarr setup by calling page <b>/install</b>.<br>\n";
	exit;
}


// Included by default
include_once DOL_DOCUMENT_ROOT .'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT .'/core/lib/security.lib.php';
//print memory_get_usage();

// If password is encoded, we decode it
if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
{
	if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
	{
		$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
		$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
	}
	else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
}

