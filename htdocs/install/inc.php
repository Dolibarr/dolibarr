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
		\brief      File that define environment for install pages
		\version    $Id$
*/

define('DOL_VERSION','2.7.0-beta');	// Also defined in htdocs/master.inc.php

require_once('../translate.class.php');
require_once('../lib/functions.lib.php');
require_once('../lib/admin.lib.php');

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


$includeconferror='';
$conffile = "../conf/conf.php";
if (! defined('DONOTLOADCONF') && file_exists($conffile))
{
	$result=include_once($conffile);	// Load conf file
	if ($result)
	{
		if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// For backward compatibility

		// Remove last / or \ on directories or url value
		if (! empty($dolibarr_main_document_root) && ! ereg('^[\\\/]+$',$dolibarr_main_document_root)) $dolibarr_main_document_root=ereg_replace('[\\\/]+$','',$dolibarr_main_document_root);
		if (! empty($dolibarr_main_url_root)      && ! ereg('^[\\\/]+$',$dolibarr_main_url_root))      $dolibarr_main_url_root=ereg_replace('[\\\/]+$','',$dolibarr_main_url_root);
		if (! empty($dolibarr_main_data_root)     && ! ereg('^[\\\/]+$',$dolibarr_main_data_root))     $dolibarr_main_data_root=ereg_replace('[\\\/]+$','',$dolibarr_main_data_root);

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
if (empty($conf->file->character_set_client))     	  $conf->file->character_set_client="UTF-8";
if (empty($conf->db->dolibarr_main_db_collation)) $conf->db->dolibarr_main_db_collation='latin1_swedish_ci';
if (empty($conf->db->user)) $conf->db->user='';


// Security check
if (eregi('install.lock',$_SERVER["SCRIPT_FILENAME"]))
{
	print 'Install pages have been disabled for security reason (directory renamed with .lock).';
	print '<a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
	print 'Click here to go to Dolibarr';
	print '</a>';
	exit;
}
if (file_exists('../../install.lock'))
{
	print 'Install pages have been disabled for security reason (by lock file install.lock).<br>';
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
	else if (@is_writable("/")) define('SYSLOG_FILE','/dolibarr_install.log');
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
//if (! eregi('PHP/6', $_SERVER['SERVER_SOFTWARE']))
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
$langs->setPhpLang();

$bc[false]=' class="bg1"';
$bc[true]=' class="bg2"';


/*
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
	if (empty($dolibarr_main_db_charset)) $dolibarr_main_db_charset='latin1';
	$conf->db->character_set=$dolibarr_main_db_charset;
	if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation='latin1_swedish_ci';
	$conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;

	// Forcage du log pour les install et mises a jour
	$conf->syslog->enabled=1;
	$conf->global->SYSLOG_LEVEL=constant('LOG_DEBUG');
	if (! defined('SYSLOG_FILE'))	// To avoid warning on systems with constant already defined
	{
		if (@is_writable('/tmp')) define('SYSLOG_FILE','/tmp/dolibarr_install.log');
		else if (! empty($_ENV["TMP"])  && @is_writable($_ENV["TMP"]))  define('SYSLOG_FILE',$_ENV["TMP"].'/dolibarr_install.log');
		else if (! empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"])) define('SYSLOG_FILE',$_ENV["TEMP"].'/dolibarr_install.log');
		else if (@is_writable("/")) define('SYSLOG_FILE','/dolibarr_install.log');
	}
	if (! defined('SYSLOG_FILE_NO_ERROR'))
	{
		define('SYSLOG_FILE_NO_ERROR',1);
	}

	return 1;
}


/*
*	\brief		Affiche entete HTML
*/
function pHeader($soutitre,$next,$action='set')
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

    print '<form action="'.$next.'.php" method="POST">'."\n";
    print '<input type="hidden" name="testpost" value="ok">'."\n";
    print '<input type="hidden" name="action" value="'.$action.'">'."\n";

	print '<table class="main" width="100%"><tr><td>'."\n";

	print '<table class="main-inside" width="100%"><tr><td>'."\n";
}

function pFooter($nonext=0,$setuplang='')
{
    global $conf,$langs;

    $langs->load("main");
    $langs->load("admin");

    print '</td></tr></table>'."\n";
    print '</td></tr></table>'."\n";

    if (! $nonext)
    {
        print '<div class="barrebottom"><input type="submit" value="'.$langs->trans("NextStep").' ->"></div>';
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


function dolibarr_install_syslog($message, $level=LOG_DEBUG)
{
	if (! defined('LOG_DEBUG')) define('LOG_DEBUG',6);
	dol_syslog($message,$level);
}

?>