<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2022	    Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/public/ticket/document.php
 *  \brief      Wrapper to download data files for tickets
 *  \remarks    Call of this wrapper is made with URL:
 * 				DOL_URL_ROOT.'/public/ticket/document.php?modulepart=repfichierconcerne&file=relativepathoffile'
 * 				DOL_URL_ROOT.'/public/ticket/document.php?modulepart=logs&file=dolibarr.log'
 * 				DOL_URL_ROOT.'/public/ticket/document.php?hashp=sharekey'
 */

define('MAIN_SECURITY_FORCECSP', "default-src 'none'");

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

if (!defined("NOLOGIN")) {
	define("NOLOGIN", 1);
}
if (!defined("NOCSRFCHECK")) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined("NOIPCHECK")) {
	define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

/**
 * Header empty
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @param 	string 			$head				Optional head lines
 * @param 	string 			$title				HTML title
 * @param	string			$help_url			Url links to help page
 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 *                                  			For other external page: http://server/url
 * @param	string			$target				Target to use on links
 * @param 	int    			$disablejs			More content into html header
 * @param 	int    			$disablehead		More content into html header
 * @param 	string[]|string	$arrayofjs			Array of complementary js files
 * @param 	string[]|string	$arrayofcss			Array of complementary css files
 * @param	string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param   string  		$morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * @param	string			$replacemainareaby	Replace call to main_area() by a print of this string
 * @param	int				$disablenofollow	Disable the "nofollow" on meta robot header
 * @param	int				$disablenoindex		Disable the "noindex" on meta robot header
 * @return	void
 * @phan-suppress PhanRedefineFunction
 */
function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
{
}
/**
 * Footer empty
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @ignore
 * @param	string	$comment    				A text to add as HTML comment into HTML generated page
 * @param	string	$zone						'private' (for private pages) or 'public' (for public pages)
 * @param	int		$disabledoutputofmessages	Clear all messages stored into session without displaying them
 * @return	void
 * @phan-suppress PhanRedefineFunction
 */
function llxFooter($comment = '', $zone = 'private', $disabledoutputofmessages = 0)
{
}

require '../../main.inc.php'; // Load $user and permissions
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';

$encoding = '';
$original_file = GETPOST('file', 'alphanohtml');
$modulepart = 'ticket';		// Forced to be sure wrapper is not used for something else
$entity = GETPOSTISSET('entity') ? GETPOSTINT('entity') : $conf->entity;

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

// Check access to Module(s)
if (!isModEnabled('ticket')) {
	httponly_accessforbidden('Module Ticket is not enabled');
}

if (!getDolGlobalString('TICKET_ENABLE_PUBLIC_INTERFACE')) {
	print $langs->trans('TicketPublicInterfaceForbidden');
	exit;
}

global $dolibarr_main_instance_unique_id;
$calcsecurekey = dol_hash('dolibarr-'.$original_file.'-'.$dolibarr_main_instance_unique_id, 'sha256');

$securekey = GETPOST('securekey');

if (!hash_equals($calcsecurekey, $securekey)) {
	httponly_accessforbidden('Invalid securekey');
}

$object = new Ticket($db);


/*
 * Actions
 */

// None



/*
 * View
 */


// Define attachment (attachment=true to force choice popup 'open'/'save as')
$attachment = true;
if (preg_match('/\.(html|htm)$/i', $original_file)) {
	$attachment = false;
}
if (isset($_GET["attachment"])) {
	$attachment = GETPOST("attachment", 'alpha') ? true : false;
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

$accessallowed = 1;

// Use dol_check_secure_access_document(); instead or not ?
/*
$sql = "SELECT rowid, src_object_id, src_object_type FROM ".MAIN_DB_PREFIX.'ecm_files';
$sql .= " WHERE filename = '".$this->db->escape(basename($original_file))."'";
$sql .= " AND filepath = '".$this->db->escape(basename($tmparray['dir_output']).'/'.dirname($original_file))."'";
$resql = $this->db->query($sql);
if ($resql) {
	$obj = $this->db->fetch_object($resql);

	if ($obj->src_object_id && $obj->src_object_type) {
		// Create the virtual user
		$tmpuser = new User($this->db);
		$tmpuser->socid = $socId;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Use dol_check_secure_access_document(); instead or not ?
		$ok = checkUserAccessToObject($tmpuser, array($obj->src_object_type), $obj->src_object_id, '', '', 'fk_soc');

		$accessallowed = ($ok ? 1 : 0);
		$pathdir = $tmparray['dir_output'];
	}
} else {
	dol_print_error($this->db);
}

$ok = checkUserAccessToObject($user, array($obj->src_object_type), $obj->src_object_id, '', '', 'fk_soc');
*/

$fullpath_original_file = getMultidirOutput($object, 'ticket').'/'.$original_file;


// Security:
// Limit access if permissions are wrong
if (!$accessallowed) { // @phpstan-ignore-line as value is set to 1 just before
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
	print $langs->trans("ErrorFileDoesNotExists") . ' : ' . dol_escape_htmltag($original_file);
	exit;
}

// Set this for test
//$type = 'text/html'; $attachment = -1;

// Permissions are ok and file found, so we return it
top_httphead($type);

header('Content-Description: File Transfer');
if ($encoding) { // @phpstan-ignore-line as variable is set to '' and never change
	header('Content-Encoding: '.$encoding);
}
// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)

if ($attachment > 0) {
	header('Content-Disposition: attachment; filename="'.$filename.'"');
} elseif (empty($attachment)) {
	header('Content-Disposition: inline; filename="'.$filename.'"');
}
// Ajout directives pour resoudre bug IE
header('Cache-Control: Public, must-revalidate');
header('Pragma: public');
$readfile = true;

if (is_object($db)) {
	$db->close();
}

// Send file now
if ($readfile) { // @phpstan-ignore-line as value is set to true just before
	header('Content-Length: '.dol_filesize($fullpath_original_file));

	readfileLowMemory($fullpath_original_file_osencoded);
}
