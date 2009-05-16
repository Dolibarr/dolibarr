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

/**
 * 	    \file       htdocs/support/inc.php
 *		\brief      File that define environment for support pages
 *		\version    $Id$
 */

define('DOL_VERSION','2.5-dev');	// Also defined in htdocs/master.inc.php

require_once('../translate.class.php');
require_once('../lib/functions.lib.php');
require_once('../lib/admin.lib.php');

// DOL_DOCUMENT_ROOT has been defined in function.inc.php to '..'

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
$charset="UTF-8";	// If not output format found in any conf file
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

if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_';
define('MAIN_DB_PREFIX',(isset($dolibarr_main_db_prefix)?$dolibarr_main_db_prefix:''));
define('DOL_DATA_ROOT',(isset($dolibarr_main_data_root)?$dolibarr_main_data_root:''));
if (empty($conf->file->character_set_client))     	  $conf->file->character_set_client=$charset;
if (empty($conf->db->dolibarr_main_db_collation)) $conf->db->dolibarr_main_db_collation='latin1_swedish_ci';
if (empty($conf->db->user)) $conf->db->user='';



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
if (isset($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);
else $langs->setDefaultLang('auto');
$langs->setPhpLang();

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

	if (empty($conf->file->character_set_client))     	  $conf->file->character_set_client="UTF-8";
	if (empty($conf->db->dolibarr_main_db_collation)) $conf->db->dolibarr_main_db_collation='latin1_swedish_ci';

	return 1;
}


/**
 *	\brief		Affiche entete HTML
 */
function pHeader($soutitre,$next,$action='none')
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
	print '<link rel="stylesheet" type="text/css" href="default.css">'."\n";
	print '<title>'.$langs->trans("Help").'</title>'."\n";
	print '</head>'."\n";
	print '<body>'."\n";
	print '<table class="noborder"><tr valign="middle">';
	print '<td width="20">';
	print '<img src="'.DOL_URL_ROOT.'/theme/common/helpcenter.png">';
	print '</td>';
	print '<td>';
	print '<span class="titre">'.$soutitre.'</span>'."\n";
	print '</td></tr></table>';

	print '<form action="'.$next.'" method="POST">'."\n";
	print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="'.$action.'">'."\n";

//	print '<table class="main" width="100%"><tr><td>'."\n";

//	print '<table class="main-inside" width="100%"><tr><td>'."\n";
}

/**
 * Enter description here...
 *
 * @param unknown_type $nonext
 * @param unknown_type $setuplang
 */
function pFooter($nonext=0,$setuplang='')
{
	global $langs;
	$langs->load("main");
	$langs->load("admin");

	print '</form>'."\n";
	print '</body>'."\n";
	print '</html>'."\n";
}

/**
 * Enter description here...
 *
 * @param unknown_type $message
 * @param unknown_type $level
 */
function dolibarr_support_syslog($message, $level=LOG_DEBUG)
{
	if (! defined('LOG_DEBUG')) define('LOG_DEBUG',6);
	dol_syslog($message,$level);
}

?>