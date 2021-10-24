<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**	    \file       htdocs/install/inc.php
 * 		\ingroup	core
 *		\brief      File that define environment for install pages
 *		\version    $Id$
 */

define('DOL_VERSION','2.8.1');	// Also defined in htdocs/master.inc.php

require_once('../translate.class.php');
require_once('../lib/functions.lib.php');
require_once('../lib/admin.lib.php');
require_once('../lib/files.lib.php');

// DOL_DOCUMENT_ROOT has been defined in function.inc.php to '..'

// Define $_REQUEST["logtohtml"]
$_REQUEST["logtohtml"]=1;

// Correction PHP_SELF (ex pour apache via caudium) car PHP_SELF doit valoir URL relative
// et non path absolu.
if (isset($_SERVER["DOCUMENT_URI"]) && $_SERVER["DOCUMENT_URI"])
{
	$_SERVER["PHP_SELF"]=$_SERVER["DOCUMENT_URI"];
}


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

$includeconferror='';
$conffile = "../conf/conf.php";

if (! defined('DONOTLOADCONF') && file_exists($conffile))
{
	$result=include_once($conffile);	// Load conf file
	if ($result)
	{
		//if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// For backward compatibility

		// Remove last / or \ on directories or url value
		if (! empty($dolibarr_main_document_root) && ! preg_match('/^[\\/]+$/',$dolibarr_main_document_root)) $dolibarr_main_document_root=preg_replace('/[\\/]+$/','',$dolibarr_main_document_root);
		if (! empty($dolibarr_main_url_root)      && ! preg_match('/^[\\/]+$/',$dolibarr_main_url_root))      $dolibarr_main_url_root=preg_replace('/[\\/]+$/','',$dolibarr_main_url_root);
		if (! empty($dolibarr_main_data_root)     && ! preg_match('/^[\\/]+$/',$dolibarr_main_data_root))     $dolibarr_main_data_root=preg_replace('/[\\/]+$/','',$dolibarr_main_data_root);

		// Create conf object
		if (! empty($dolibarr_main_document_root))
		{
			$result=conf($dolibarr_main_document_root);
		}
		// Load database driver
		if ($result)
		{
			if (! empty($dolibarr_main_document_root) && ! empty($dolibarr_main_db_type))
			{
				$result=include_once($dolibarr_main_document_root . "/lib/databases/".$dolibarr_main_db_type.".lib.php");
				if ($result)
				{
					// OK
				}
				else
				{
					$includeconferror='ErrorBadValueForDolibarrMainDBType';
				}
			}
		}
		else
		{
			$includeconferror='ErrorBadValueForDolibarrMainDocumentRoot';
		}
	}
	else
	{
		$includeconferror='ErrorBadFormatForConfFile';
	}
}
$conf->global->MAIN_LOGTOHTML=1;


// Define prefix
if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_';
define('MAIN_DB_PREFIX',(isset($dolibarr_main_db_prefix)?$dolibarr_main_db_prefix:''));

define('DOL_DATA_ROOT',(isset($dolibarr_main_data_root)?$dolibarr_main_data_root:''));
if (empty($conf->file->character_set_client))      	$conf->file->character_set_client="UTF-8";
if (empty($conf->db->character_set))  				$conf->db->character_set='utf8';
if (empty($conf->db->dolibarr_main_db_collation))  	$conf->db->dolibarr_main_db_collation='utf8_general_ci';
if (empty($conf->db->dolibarr_main_db_encryption)) 	$conf->db->dolibarr_main_db_encryption=0;
if (empty($conf->db->dolibarr_main_db_cryptkey))   	$conf->db->dolibarr_main_db_cryptkey='';
if (empty($conf->db->user)) $conf->db->user='';


// Security check
if (preg_match('/install.lock/i',$_SERVER["SCRIPT_FILENAME"]))
{
	print 'Install pages have been disabled for security reason (directory renamed with .lock).';
	print '<a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
	print 'Click here to go to Dolibarr';
	print '</a>';
	exit;
}
if (file_exists('../../install.lock'))
{
	print 'Install pages have been disabled for security reason (by lock file install.lock in dolibarr root directory. Remove it manually if following link loops to this page).<br>';
	print '<a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
	print 'Click here to go to Dolibarr';
	print '</a>';
	exit;
}


// Forcage du log pour les install et mises a jour
$conf->syslog->enabled=1;
$conf->global->SYSLOG_LEVEL=constant('LOG_DEBUG');
if (! defined('SYSLOG_FILE'))	// To avoid warning on systems with constant already defined
{
	if (@is_writable('/tmp')) define('SYSLOG_FILE','/tmp/dolibarr_install.log');
	else if (! empty($_ENV["TMP"])  && @is_writable($_ENV["TMP"]))  define('SYSLOG_FILE',$_ENV["TMP"].'/dolibarr_install.log');
	else if (! empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"])) define('SYSLOG_FILE',$_ENV["TEMP"].'/dolibarr_install.log');
	else if (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat')) define('SYSLOG_FILE','../../../../dolibarr_install.log');	// For DoliWamp
	else if (@is_writable('../../')) define('SYSLOG_FILE','../../dolibarr_install.log');				// For others
	//print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
}
if (! defined('SYSLOG_FILE_NO_ERROR'))
{
	define('SYSLOG_FILE_NO_ERROR',1);
}

// Forcage du parametrage PHP magic_quotes_gpc et nettoyage des parametres
// (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande il faut juste faire addslashes au moment d'un insert/update.
function stripslashes_deep($value)
{
	return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}
//if (! preg_match('/PHP\/6/i', $_SERVER['SERVER_SOFTWARE']))
if (function_exists('get_magic_quotes_gpc'))	// magic_quotes_* plus pris en compte dans PHP6
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

// Defini objet langs
$langs = new Translate('..',$conf);
$langs->setDefaultLang('auto');

$bc[false]=' class="bg1"';
$bc[true]=' class="bg2"';


/**
 *	\brief		Load conf file (file must exists)
 *	\param		dolibarr_main_document_root		Root directory of Dolibarr bin files
 *	\return		int								<0 if KO, >0 if OK
 */
function conf($dolibarr_main_document_root)
{
	global $conf;
	global $dolibarr_main_db_type;
	global $dolibarr_main_db_host;
	global $dolibarr_main_db_port;
	global $dolibarr_main_db_name;
	global $dolibarr_main_db_user;
	global $dolibarr_main_db_pass;
	global $character_set_client;

	$return=include_once($dolibarr_main_document_root."/core/conf.class.php");
	if (! $return) return -1;

	$conf=new Conf();
	$conf->db->type = trim($dolibarr_main_db_type);
	$conf->db->host = trim($dolibarr_main_db_host);
	$conf->db->port = trim($dolibarr_main_db_port);
	$conf->db->name = trim($dolibarr_main_db_name);
	$conf->db->user = trim($dolibarr_main_db_user);
	$conf->db->pass = trim($dolibarr_main_db_pass);

	if (empty($character_set_client)) $character_set_client="UTF-8";
	$conf->file->character_set_client=strtoupper($character_set_client);
	if (empty($dolibarr_main_db_character_set)) $dolibarr_main_db_character_set='latin1';		// Old installation
	$conf->db->character_set=$dolibarr_main_db_character_set;
	if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation='latin1_swedish_ci';	// Old installation
	$conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;
	if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
	$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
	if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
	$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

	// Forcage du log pour les install et mises a jour
	$conf->syslog->enabled=1;
	$conf->global->SYSLOG_LEVEL=constant('LOG_DEBUG');
	if (! defined('SYSLOG_FILE'))	// To avoid warning on systems with constant already defined
	{
		if (@is_writable('/tmp')) define('SYSLOG_FILE','/tmp/dolibarr_install.log');
		else if (! empty($_ENV["TMP"])  && @is_writable($_ENV["TMP"]))  define('SYSLOG_FILE',$_ENV["TMP"].'/dolibarr_install.log');
		else if (! empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"])) define('SYSLOG_FILE',$_ENV["TEMP"].'/dolibarr_install.log');
		else if (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat')) define('SYSLOG_FILE','../../../../dolibarr_install.log');	// For DoliWamp
		else if (@is_writable('../../')) define('SYSLOG_FILE','../../dolibarr_install.log');				// For others
		//print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
	}
	if (! defined('SYSLOG_FILE_NO_ERROR'))
	{
		define('SYSLOG_FILE_NO_ERROR',1);
	}

	return 1;
}


/**
 * Show header of install pages
 *
 * @param unknown_type $soutitre
 * @param unknown_type $next
 * @param unknown_type $action
 * @param unknown_type $param
 */
function pHeader($soutitre,$next,$action='set',$param='')
{
	global $conf;
	global $langs;
	$langs->load("main");
	$langs->load("admin");

	// On force contenu dans format sortie
	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
	print '<html>'."\n";
	print '<head>'."\n";
	print '<meta http-equiv="content-type" content="text/html; charset='.$conf->file->character_set_client.'">'."\n";
	print '<link rel="stylesheet" type="text/css" href="./default.css">'."\n";
	print '<title>'.$langs->trans("DolibarrSetup").'</title>'."\n";
	print '</head>'."\n";
	print '<body>'."\n";
	print '<span class="titre">'.$langs->trans("DolibarrSetup");
	if ($soutitre) {
		print ' - '.$soutitre;
	}
	print '</span>'."\n";

	print '<form name="forminstall" action="'.$next.'.php'.($param?'?'.$param:'').'" method="POST">'."\n";
	print '<input type="hidden" name="testpost" value="ok">'."\n";
	print '<input type="hidden" name="action" value="'.$action.'">'."\n";

	print '<table class="main" width="100%"><tr><td>'."\n";

	print '<table class="main-inside" width="100%"><tr><td>'."\n";
}

/**
 * Output footer of install pages
 *
 * @param unknown_type $nonext
 * @param unknown_type $setuplang
 * @param unknown_type $jscheckfunction
 */
function pFooter($nonext=0,$setuplang='',$jscheckfunction='')
{
	global $conf,$langs;

	$langs->load("main");
	$langs->load("admin");

	print '</td></tr></table>'."\n";
	print '</td></tr></table>'."\n";

	if (! $nonext)
	{
		print '<div class="barrebottom"><input type="submit" value="'.$langs->trans("NextStep").' ->"';
		if ($jscheckfunction) print ' onClick="return '.$jscheckfunction.'();"';
		print '></div>';
	}
	if ($setuplang)
	{
		print '<input type="hidden" name="selectlang" value="'.$setuplang.'">';
	}

	print '</form>'."\n";

	// If there is some logs in buffer to show
	if (isset($conf->logbuffer) && sizeof($conf->logbuffer))
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
		print "\n";
	}

	print '</body>'."\n";
	print '</html>'."\n";
}

/**
 * Log function for install pages
 *
 * @param unknown_type $message
 * @param unknown_type $level
 */
function dolibarr_install_syslog($message, $level=LOG_DEBUG)
{
	if (! defined('LOG_DEBUG')) define('LOG_DEBUG',6);
	dol_syslog($message,$level);
}

?>