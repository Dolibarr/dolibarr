<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2022	   Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/document.php
 *  \brief      Wrapper to download data files
 *  \remarks    Call of this wrapper is made with URL:
 * 				DOL_URL_ROOT.'/document.php?modulepart=repfichierconcerne&file=relativepathoffile'
 * 				DOL_URL_ROOT.'/document.php?modulepart=logs&file=dolibarr.log'
 * 				DOL_URL_ROOT.'/document.php?hashp=sharekey'
 */

define('MAIN_SECURITY_FORCECSP', "default-src: 'none'");

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// For direct external download link, we don't need to load/check we are into a login session
if (isset($_GET["hashp"]) && !defined("NOLOGIN")) {
	if (!defined("NOLOGIN")) {
		define("NOLOGIN", 1);
	}
	if (!defined("NOCSRFCHECK")) {
		define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	}
	if (!defined("NOIPCHECK")) {
		define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
	}
}
// Some value of modulepart can be used to get resources that are public so no login are required.
// Keep $_GET here, GETPOST is not available yet
if ((isset($_GET["modulepart"]) && $_GET["modulepart"] == 'medias')) {
	if (!defined("NOLOGIN")) {
		define("NOLOGIN", 1);
	}
	if (!defined("NOCSRFCHECK")) {
		define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	}
	if (!defined("NOIPCHECK")) {
		define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
	}
}

/**
 * Header empty
 *
 * @param 	string 			$head				Optional head lines
 * @param 	string 			$title				HTML title
 * @param	string			$help_url			Url links to help page
 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 *                                  			For other external page: http://server/url
 * @param	string			$target				Target to use on links
 * @param 	int    			$disablejs			More content into html header
 * @param 	int    			$disablehead		More content into html header
 * @param 	array|string  	$arrayofjs			Array of complementary js files
 * @param 	array|string  	$arrayofcss			Array of complementary css files
 * @param	string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param   string  		$morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * @param	string			$replacemainareaby	Replace call to main_area() by a print of this string
 * @param	int				$disablenofollow	Disable the "nofollow" on meta robot header
 * @param	int				$disablenoindex		Disable the "noindex" on meta robot header
 * @return	void
 */
function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
{
}
/**
 * Footer empty
 *
 * @ignore
 * @return	void
 */
function llxFooter()
{
}

require 'main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

$encoding = '';
$action = GETPOST('action', 'aZ09');
$original_file = GETPOST('file', 'alphanohtml');
$hashp = GETPOST('hashp', 'aZ09');
$modulepart = GETPOST('modulepart', 'alpha');
$urlsource = GETPOST('urlsource', 'alpha');
$entity = GETPOSTINT('entity', $conf->entity);

// Security check
if (empty($modulepart) && empty($hashp)) {
	httponly_accessforbidden('Bad link. Bad value for parameter modulepart', 400);
}
if (empty($original_file) && empty($hashp)) {
	httponly_accessforbidden('Bad link. Missing identification to find file (original_file or hashp)', 400);
}
if ($modulepart == 'fckeditor') {
	$modulepart = 'medias'; // For backward compatibility
}

$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

// For some module part, dir may be privates
if (in_array($modulepart, array('facture_paiement', 'unpaid'))) {
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$original_file = 'private/'.$user->id.'/'.$original_file; // If user has no permission to see all, output dir is specific to user
	}
}


/*
 * Actions
 */

// None



/*
 * View
 */

// If we have a hash public (hashp), we guess the original_file.
$ecmfile='';
if (!empty($hashp)) {
	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	$ecmfile = new EcmFiles($db);
	$result = $ecmfile->fetch(0, '', '', '', $hashp);
	if ($result > 0) {
		$tmp = explode('/', $ecmfile->filepath, 2); // $ecmfile->filepath is relative to document directory
		// filepath can be 'users/X' or 'X/propale/PR11111'
		if (is_numeric($tmp[0])) { // If first tmp is numeric, it is subdir of company for multicompany, we take next part.
			$tmp = explode('/', $tmp[1], 2);
		}
		$moduleparttocheck = $tmp[0]; // moduleparttocheck is first part of path

		if ($modulepart) {	// Not required, so often not defined, for link using public hashp parameter.
			if ($moduleparttocheck == $modulepart) {
				// We remove first level of directory
				$original_file = (($tmp[1] ? $tmp[1].'/' : '').$ecmfile->filename); // this is relative to module dir
				//var_dump($original_file); exit;
			} else {
				httponly_accessforbidden('Bad link. File is from another module part.', 403);
			}
		} else {
			$modulepart = $moduleparttocheck;
			$original_file = (($tmp[1] ? $tmp[1].'/' : '').$ecmfile->filename); // this is relative to module dir
		}
		$entity = $ecmfile->entity;
		if ($entity != $conf->entity) {
			$conf->entity = $entity;
			$conf->setValues($db);
		}
	} else {
		$langs->load("errors");
		httponly_accessforbidden($langs->trans("ErrorFileNotFoundWithSharedLink"), 403, 1);
	}
}

// Define attachment (attachment=true to force choice popup 'open'/'save as')
$attachment = true;
if (preg_match('/\.(html|htm)$/i', $original_file)) {
	$attachment = false;
}
if (isset($_GET["attachment"])) {
	$attachment = GETPOST("attachment", 'alpha') ?true:false;
}
if (getDolGlobalString('MAIN_DISABLE_FORCE_SAVEAS')) {
	$attachment = false;
}

// Define mime type
$type = 'application/octet-stream'; // By default
if (GETPOST('type', 'alpha')) {
	$type = GETPOST('type', 'alpha');
} else {
	$type = dol_mimetype($original_file);
}
// Security: Force to octet-stream if file is a dangerous file. For example when it is a .noexe file
// We do not force if file is a javascript to be able to get js from website module with <script src="
// Note: Force whatever is $modulepart seems ok.
if (!in_array($type, array('text/x-javascript')) && !dolIsAllowedForPreview($original_file)) {
	$type = 'application/octet-stream';
}

// Security: Delete string ../ or ..\ into $original_file
$original_file = preg_replace('/\.\.+/', '..', $original_file);	// Replace '... or more' with '..'
$original_file = str_replace('../', '/', $original_file);
$original_file = str_replace('..\\', '/', $original_file);


// Security check
if (empty($modulepart)) {
	accessforbidden('Bad value for parameter modulepart');
}

// Check security and set return info with full path of file
$check_access = dol_check_secure_access_document($modulepart, $original_file, $entity, $user, '');
$accessallowed              = $check_access['accessallowed'];
$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
$fullpath_original_file     = $check_access['original_file']; // $fullpath_original_file is now a full path name
//var_dump($fullpath_original_file.' '.$original_file.' '.$accessallowed);exit;

if (!empty($hashp)) {
	$accessallowed = 1; // When using hashp, link is public so we force $accessallowed
	$sqlprotectagainstexternals = '';
} else {
	// Basic protection (against external users only)
	if ($user->socid > 0) {
		if ($sqlprotectagainstexternals) {
			$resql = $db->query($sqlprotectagainstexternals);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					if ($user->socid != $obj->fk_soc) {
						$accessallowed = 0;
						break;
					}
					$i++;
				}
			}
		}
	}
}

// Security:
// Limit access if permissions are wrong
if (!$accessallowed) {
	accessforbidden();
}

// Security:
// We refuse directory transversal change and pipes in file names
if (preg_match('/\.\./', $fullpath_original_file) || preg_match('/[<>|]/', $fullpath_original_file)) {
	dol_syslog("Refused to deliver file ".$fullpath_original_file);
	print "ErrorFileNameInvalid: ".dol_escape_htmltag($original_file);
	exit;
}


clearstatcache();

$filename = basename($fullpath_original_file);
$filename = preg_replace('/\.noexe$/i', '', $filename);

// Output file on browser
dol_syslog("document.php download $fullpath_original_file filename=$filename content-type=$type");
$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file); // New file name encoded in OS encoding charset

// This test if file exists should be useless. We keep it to find bug more easily
if (!file_exists($fullpath_original_file_osencoded)) {
	dol_syslog("ErrorFileDoesNotExists: ".$fullpath_original_file);
	print "ErrorFileDoesNotExists: ".dol_escape_htmltag($original_file);
	exit;
}

// Hooks
$hookmanager->initHooks(array('document'));
$parameters = array('ecmfile' => $ecmfile, 'modulepart' => $modulepart, 'original_file' => $original_file,
	'entity' => $entity, 'fullpath_original_file' => $fullpath_original_file,
	'filename' => $filename, 'fullpath_original_file_osencoded' => $fullpath_original_file_osencoded);
$object = new stdClass();
$reshook = $hookmanager->executeHooks('downloadDocument', $parameters, $object, $action); // Note that $action and $object may have been
if ($reshook < 0) {
	$errors = $hookmanager->error.(is_array($hookmanager->errors) ? (!empty($hookmanager->error) ? ', ' : '').implode(', ', $hookmanager->errors) : '');
	dol_syslog("document.php - Errors when executing the hook 'downloadDocument' : ".$errors);
	print "ErrorDownloadDocumentHooks: ".$errors;
	exit;
}


// Permissions are ok and file found, so we return it
top_httphead($type);

header('Content-Description: File Transfer');
if ($encoding) {
	header('Content-Encoding: '.$encoding);
}
// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)

if ($attachment) {
	header('Content-Disposition: attachment; filename="'.$filename.'"');
} else {
	header('Content-Disposition: inline; filename="'.$filename.'"');
}
// Ajout directives pour resoudre bug IE
header('Cache-Control: Public, must-revalidate');
header('Pragma: public');
$readfile = true;

// on view document, can output images with good orientation according to exif infos
// TODO Why this on document.php and not in viewimage.php ?
if (!$attachment && getDolGlobalString('MAIN_USE_EXIF_ROTATION') && image_format_supported($fullpath_original_file_osencoded) == 1) {
	$imgres = correctExifImageOrientation($fullpath_original_file_osencoded, null);
	$readfile = !$imgres;
}

if (is_object($db)) {
	$db->close();
}

// Send file now
if ($readfile) {
	header('Content-Length: '.dol_filesize($fullpath_original_file));

	readfileLowMemory($fullpath_original_file_osencoded);
}
