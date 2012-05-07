<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/support/inc.php
 * 	\ingroup	core
 *	\brief      File that define environment for support pages
 */

// Just to define version DOL_VERSION
if (! defined('DOL_INC_FOR_VERSION_ERROR')) define('DOL_INC_FOR_VERSION_ERROR','1');
require_once('../filefunc.inc.php');

// Define DOL_DOCUMENT_ROOT and ADODB_PATH used for install/upgrade process
if (! defined('DOL_DOCUMENT_ROOT'))	    define('DOL_DOCUMENT_ROOT', '..');
if (! defined('ADODB_PATH'))
{
    $foundpath=DOL_DOCUMENT_ROOT .'/includes/adodbtime/';
    if (! is_dir($foundpath)) $foundpath='/usr/share/php/adodb/';
    define('ADODB_PATH', $foundpath);
}

require_once(DOL_DOCUMENT_ROOT.'/core/class/translate.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
require_once(ADODB_PATH.'adodb-time.inc.php');

// Avoid warnings with strict mode E_STRICT
$conf = new stdClass(); // instantiate $conf explicitely
$conf->global	= (object) array();
$conf->file		= (object) array();
$conf->db		= (object) array();
$conf->syslog	= (object) array();

// Force $_REQUEST["logtohtml"]
$_REQUEST["logtohtml"]=1;

// Correction PHP_SELF (ex pour apache via caudium) car PHP_SELF doit valoir URL relative
// et non path absolu.
if (isset($_SERVER["DOCUMENT_URI"]) && $_SERVER["DOCUMENT_URI"])
{
	$_SERVER["PHP_SELF"]=$_SERVER["DOCUMENT_URI"];
}


$includeconferror='';

// Define vars
$conffiletoshowshort = "conf.php";
// Define localization of conf file
$conffile = "../conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
//$conffile = "/etc/dolibarr/conf.php";
//$conffiletoshow = "/etc/dolibarr/conf.php";

if (! defined('DONOTLOADCONF') && file_exists($conffile))
{
	$result=include_once($conffile);	// Load conf file
	if ($result)
	{

		if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// For backward compatibility

		// Clean parameters
		$dolibarr_main_data_root        =isset($dolibarr_main_data_root)?trim($dolibarr_main_data_root):'';
		$dolibarr_main_url_root         =isset($dolibarr_main_url_root)?trim($dolibarr_main_url_root):'';
		$dolibarr_main_url_root_alt     =isset($dolibarr_main_url_root_alt)?trim($dolibarr_main_url_root_alt):'';
		$dolibarr_main_document_root    =isset($dolibarr_main_document_root)?trim($dolibarr_main_document_root):'';
		$dolibarr_main_document_root_alt=isset($dolibarr_main_document_root_alt)?trim($dolibarr_main_document_root_alt):'';

		// Remove last / or \ on directories or url value
		if (! empty($dolibarr_main_document_root) && ! preg_match('/^[\\/]+$/',$dolibarr_main_document_root)) $dolibarr_main_document_root=preg_replace('/[\\/]+$/','',$dolibarr_main_document_root);
		if (! empty($dolibarr_main_url_root)      && ! preg_match('/^[\\/]+$/',$dolibarr_main_url_root))      $dolibarr_main_url_root=preg_replace('/[\\/]+$/','',$dolibarr_main_url_root);
		if (! empty($dolibarr_main_data_root)     && ! preg_match('/^[\\/]+$/',$dolibarr_main_data_root))     $dolibarr_main_data_root=preg_replace('/[\\/]+$/','',$dolibarr_main_data_root);
		if (! empty($dolibarr_main_document_root_alt)	&& ! preg_match('/^[\\/]+$/',$dolibarr_main_document_root_alt))	$dolibarr_main_document_root_alt=preg_replace('/[\\/]+$/','',$dolibarr_main_document_root_alt);
		if (! empty($dolibarr_main_url_root_alt)		&& ! preg_match('/^[\\/]+$/',$dolibarr_main_url_root_alt))		$dolibarr_main_url_root_alt=preg_replace('/[\\/]+$/','',$dolibarr_main_url_root_alt);

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
				$result=include_once($dolibarr_main_document_root . "/core/db/".$dolibarr_main_db_type.".class.php");
				if (! $result)
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
$conf->global->MAIN_LOGTOHTML = 1;

// Define prefix
if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_';
define('MAIN_DB_PREFIX',(isset($dolibarr_main_db_prefix)?$dolibarr_main_db_prefix:''));

define('DOL_CLASS_PATH', 'class/');                             // Filsystem path to class dir
define('DOL_DATA_ROOT',(isset($dolibarr_main_data_root)?$dolibarr_main_data_root:''));
if (! empty($dolibarr_main_document_root_alt))
{
    define('DOL_DOCUMENT_ROOT_ALT', $dolibarr_main_document_root_alt);	// Filesystem paths to alternate core php (alternate htdocs)
}
define('DOL_MAIN_URL_ROOT', (isset($dolibarr_main_url_root)?$dolibarr_main_url_root:''));           // URL relative root
$uri=preg_replace('/^http(s?):\/\//i','',constant('DOL_MAIN_URL_ROOT'));  // $uri contains url without http*
$suburi = strstr($uri, '/');       // $suburi contains url without domain
if ($suburi == '/') $suburi = '';   // If $suburi is /, it is now ''
define('DOL_URL_ROOT', $suburi);    // URL relative root ('', '/dolibarr', ...)

if (empty($character_set_client)) $character_set_client="UTF-8";
$conf->file->character_set_client=strtoupper($character_set_client);
if (empty($dolibarr_main_db_character_set)) $dolibarr_main_db_character_set=($conf->db->type=='mysql'?'latin1':'');		// Old installation
$conf->db->character_set=$dolibarr_main_db_character_set;
if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation=($conf->db->type=='mysql'?'latin1_swedish_ci':'');  // Old installation
$conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;
if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

if (empty($conf->db->user)) $conf->db->user='';


// Defini objet langs
$langs = new Translate('..',$conf);
if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));
else $langs->setDefaultLang('auto');

$bc[false]=' class="bg1"';
$bc[true]=' class="bg2"';


/**
 *	Load conf file (file must exists)
 *
 *	@param	string	$dolibarr_main_document_root		Root directory of Dolibarr bin files
 *	@return	int											<0 if KO, >0 if OK
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

	$return=include_once($dolibarr_main_document_root."/core/class/conf.class.php");
	if (! $return) return -1;

	$conf=new Conf();
	$conf->db->type = trim($dolibarr_main_db_type);
	$conf->db->host = trim($dolibarr_main_db_host);
	$conf->db->port = trim($dolibarr_main_db_port);
	$conf->db->name = trim($dolibarr_main_db_name);
	$conf->db->user = trim($dolibarr_main_db_user);
	$conf->db->pass = trim($dolibarr_main_db_pass);

	if (empty($conf->db->dolibarr_main_db_collation)) $conf->db->dolibarr_main_db_collation='utf8_general_ci';

	return 1;
}


/**
 * Show HTML header
 *
 * @param	string	$soutitre	Title
 * @param	string	$next		Next
 * @param	string	$action		Action code
 * @return	void
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
	print '<meta name="robots" content="index,follow">'."\n";
	print '<meta name="keywords" content="help, center, dolibarr, doliwamp">'."\n";
	print '<meta name="description" content="Dolibarr help center">'."\n";
	print '<link rel="stylesheet" type="text/css" href="default.css">'."\n";
	print '<title>'.$langs->trans("DolibarrHelpCenter").'</title>'."\n";
	print '</head>'."\n";

	print '<body>'."\n";

	print '<table class="noborder" summary="helpcentertitle"><tr valign="middle">';
	print '<td width="20">';
	print '<img src="helpcenter.png" alt="logohelpcenter">';
	print '</td>';
	print '<td>';
	print '<span class="titre">'.$soutitre.'</span>'."\n";
	print '</td></tr></table>';
}

/**
 * Print HTML footer
 *
 * @param	string	$nonext			No button "Next step"
 * @param   string	$setuplang		Language code
 * @return	void
 */
function pFooter($nonext=0,$setuplang='')
{
	global $langs;
	$langs->load("main");
	$langs->load("admin");

	print '</body>'."\n";
	print '</html>'."\n";
}

?>
