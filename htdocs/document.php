<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2022	    Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/document.php
 *  \brief      Wrapper to download data files
 *  \remarks    Call of this wrapper is made with URL:
 * 				DOL_URL_ROOT.'/document.php?modulepart=repfichierconcerne&file=relativepathoffile'
 * 				DOL_URL_ROOT.'/document.php?modulepart=logs&file=dolibarr.log'
 * 				DOL_URL_ROOT.'/document.php?hashp=sharekey'
 */

define('MAIN_SECURITY_FORCECSP', "default-src 'none'; form-action 'none'; frame-ancestors 'self'");

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

// For MultiCompany modules, if an entity is set in query parameters (required to point an object because a ref can exists
// in 2 entities), then if user is not already into a session, the user must be loaded on this entity, so permission will
// be the one of this entity.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 0));
if ($entity > 0) {
	// An entity was forced on param, so we force the constant to allow master.inc.php to use this entity if not already logged.
	// It has no effect if already logged.
	define("DOLENTITY", $entity);
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

require 'main.inc.php'; // Load $user and permissions
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

/**
 * Print a translated plain text document error and stop.
 *
 * @param	string	$message	Message to print
 * @param	int		$status		HTTP status code
 * @return	never
 */
function dol_document_print_plain_error($message, $status = 400)
{
	top_httphead('text/plain');
	http_response_code($status);
	print $message;
	exit;
}

/**
 * Send a Content-Disposition header with an RFC 5987 UTF-8 filename.
 *
 * @param	string	$disposition	Disposition, usually attachment or inline
 * @param	string	$filename		Filename
 * @return	void
 */
function dol_document_send_content_disposition($disposition, $filename)
{
	$filenamefallback = dol_sanitizeFileName($filename);
	if ($filenamefallback === '') {
		$filenamefallback = 'document';
	}

	// Send both a sanitized fallback filename and the UTF-8 filename so non-ASCII names survive modern browsers.
	header('Content-Disposition: '.$disposition.'; filename="'.$filenamefallback.'"; filename*=UTF-8\'\''.rawurlencode($filename));
}

/**
 * Resolve and validate access to a document using Dolibarr native security rules.
 *
 * @param	string	$modulepart		Module part
 * @param	string	$original_file	Relative document path
 * @param	int		$entity			Entity id
 * @param	User	$fuser			User requesting the document
 * @param	string	$hashp			Public hash, if any
 * @return	array{original_file:string,accessallowed:int,sqlprotectagainstexternals:string,fullpath_original_file:string,fullpath_original_file_osencoded:string,filename:string,type:string}
 */
function dol_document_resolve_secure_file($modulepart, $original_file, $entity, $fuser, $hashp = '')
{
	global $db, $langs;

	// The single-file and ZIP download paths must share the same sanitizing, entity and permission checks.
	$original_file = dol_sanitizePathName($original_file);

	if (empty($modulepart)) {
		accessforbidden('Bad value for parameter modulepart');
	}

	// Check security and set return info with full path of file.
	$check_access = dol_check_secure_access_document($modulepart, $original_file, (int) $entity, $fuser, '', 'read');
	$accessallowed              = $check_access['accessallowed'];
	$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
	$fullpath_original_file     = $check_access['original_file']; // $fullpath_original_file is now a full path name

	if (!empty($hashp)) {
		$accessallowed = 1; // When using hashp, link is public so we force $accessallowed
		$sqlprotectagainstexternals = '';
	} else {
		// Keep the existing external-user SQL protection after the native resolver returns its extra check.
		if ($fuser->socid > 0) {
			if ($sqlprotectagainstexternals) {
				$resql = $db->query($sqlprotectagainstexternals);
				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						if ($fuser->socid != $obj->fk_soc) {
							$accessallowed = 0;
							break;
						}
						$i++;
					}
				}
			}
		} elseif ($modulepart == 'ticket' && !getDolGlobalString('TICKET_EMAIL_MUST_EXISTS')) {
			if ($sqlprotectagainstexternals) {
				$resql = $db->query($sqlprotectagainstexternals);
				if ($resql) {
					$num = $db->num_rows($resql);
					if ($num > 0) {
						$accessallowed = 1;
					}
				}
			}
		}
	}

	// Security: Limit access if permissions are wrong.
	if (!$accessallowed) {
		accessforbidden();
	}

	// Security: We refuse directory transversal changes and shell-sensitive characters in resolved file names.
	if (preg_match('/\.\./', $fullpath_original_file) || preg_match('/[<>|]/', $fullpath_original_file)) {
		dol_syslog("Refused to deliver file ".$fullpath_original_file);
		print "ErrorFileNameInvalid: ".dol_escape_htmltag($original_file);
		exit;
	}

	clearstatcache();

	$filename = basename($fullpath_original_file);
	$filename = preg_replace('/\.noexe$/i', '', $filename);
	$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file); // New file name encoded in OS encoding charset

	// This test if file exists should be useless. We keep it to find bug more easily.
	if (!file_exists($fullpath_original_file_osencoded)) {
		dol_syslog("ErrorFileDoesNotExists: ".$fullpath_original_file);
		print $langs->trans("ErrorFileDoesNotExists") . ' : ' . dol_escape_htmltag($original_file);
		exit;
	}

	// Define mime type after access validation so rejected files do not leak filesystem details.
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

	return array(
		'original_file' => $original_file,
		'accessallowed' => $accessallowed,
		'sqlprotectagainstexternals' => $sqlprotectagainstexternals,
		'fullpath_original_file' => $fullpath_original_file,
		'fullpath_original_file_osencoded' => $fullpath_original_file_osencoded,
		'filename' => $filename,
		'type' => $type,
	);
}

$encoding = '';
$action = GETPOST('action', 'aZ09');
$original_file = GETPOST('file', 'alphanohtml');
$hashp = GETPOST('hashp', 'aZ09');
$modulepart = GETPOST('modulepart', 'alpha');
$urlsource = GETPOST('urlsource', 'alpha');
$entity = ($entity > 0 ? $entity : $conf->entity);

// Security check
if (empty($modulepart) && empty($hashp)) {
	httponly_accessforbidden('Bad link. Bad value for parameter modulepart', 400);
}
if (empty($original_file) && empty($hashp) && $action != 'downloadselected') {
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

if ($action == 'downloadselected') { // Test on permission already done by dol_document_resolve_secure_file() for each selected file.
	// The batch action is POST-only so the CSRF token generated by the detached form is enforced by main.inc.php.
	if (empty($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
		httponly_accessforbidden('Bad request method for batch download', 405);
	}

	// The browser posts compact encoded selections, but every entry is treated as untrusted input below.
	$selecteddocuments = GETPOST('selecteddocuments', 'array:restricthtml');
	if (!is_array($selecteddocuments) || empty($selecteddocuments)) {
		dol_document_print_plain_error($langs->trans('NoDocumentSelected'), 400);
	}

	// ZipArchive is a native PHP extension, not a Dolibarr dependency, so fail explicitly when the host lacks it.
	if (!class_exists('ZipArchive')) {
		dol_document_print_plain_error($langs->trans('ZipArchiveUnavailable'), 500);
	}

	// Store the archive in Dolibarr's private documents area and remove it immediately after streaming.
	$ziptmpdir = DOL_DATA_ROOT.'/admin/temp';
	if (!dol_is_dir($ziptmpdir)) {
		dol_mkdir($ziptmpdir);
	}
	$ziptmpfile = tempnam($ziptmpdir, 'documentzip_');
	if ($ziptmpfile === false) {
		dol_document_print_plain_error($langs->trans('UnableToCreateZipArchive'), 500);
	}

	$zip = new ZipArchive();
	$zipopenresult = $zip->open($ziptmpfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	if ($zipopenresult !== true) {
		dol_delete_file($ziptmpfile, 1, 1, 1, null, false, 0, 1);
		dol_document_print_plain_error($langs->trans('UnableToCreateZipArchive'), 500);
	}

	$zipnames = array();
	$nbfilesinzippedarchive = 0;
	foreach ($selecteddocuments as $selecteddocument) {
		// Ignore malformed entries instead of trusting client-side checkboxes to be well formed.
		if (!is_string($selecteddocument) || $selecteddocument === '') {
			continue;
		}

		$decodedselection = base64_decode($selecteddocument, true);
		if ($decodedselection === false) {
			continue;
		}
		$documentselection = json_decode($decodedselection, true);
		if (!is_array($documentselection)) {
			continue;
		}

		$selectedmodulepart = empty($documentselection['modulepart']) ? $modulepart : (string) $documentselection['modulepart'];
		$selectedfile = empty($documentselection['file']) ? '' : (string) $documentselection['file'];
		$selectedentity = isset($documentselection['entity']) ? (int) $documentselection['entity'] : (int) $entity;

		if ($selectedfile === '' || !preg_match('/^[a-zA-Z0-9_\-]+$/', $selectedmodulepart)) {
			continue;
		}

		// Re-run the same secure document resolver used by individual downloads for each selected file.
		$resolvedfile = dol_document_resolve_secure_file($selectedmodulepart, $selectedfile, $selectedentity, $user);
		if (!is_file($resolvedfile['fullpath_original_file_osencoded'])) {
			continue;
		}

		// ZIP entries are flat and unique, so duplicate basenames receive a numeric suffix.
		$zipname = $resolvedfile['filename'];
		$pathinfo = pathinfo($zipname);
		$zipfilename = isset($pathinfo['filename']) ? $pathinfo['filename'] : $zipname;
		$zipextension = empty($pathinfo['extension']) ? '' : '.'.$pathinfo['extension'];
		$zipnamecandidate = $zipname;
		$counter = 2;
		while (isset($zipnames[$zipnamecandidate])) {
			$zipnamecandidate = $zipfilename.'-'.$counter.$zipextension;
			$counter++;
		}
		$zipnames[$zipnamecandidate] = 1;

		if ($zip->addFile($resolvedfile['fullpath_original_file_osencoded'], $zipnamecandidate)) {
			$nbfilesinzippedarchive++;
		}
	}

	if ($nbfilesinzippedarchive == 0) {
		// A selection may be syntactically present but resolve to zero authorized files after server-side checks.
		$zip->close();
		dol_delete_file($ziptmpfile, 1, 1, 1, null, false, 0, 1);
		dol_document_print_plain_error($langs->trans('NoDocumentSelected'), 400);
	}

	if (!$zip->close()) {
		dol_delete_file($ziptmpfile, 1, 1, 1, null, false, 0, 1);
		dol_document_print_plain_error($langs->trans('UnableToCreateZipArchive'), 500);
	}

	// The archive is generated for immediate download and must never be indexed or kept as a business document.
	$zipdownloadfilename = dol_sanitizeFileName($modulepart.'-documents-'.date('Ymd-His')).'.zip';

	top_httphead('application/zip');
	header('Content-Description: File Transfer');
	// Attachment is forced for this explicit download action. Browser preferences may still save automatically without prompting.
	dol_document_send_content_disposition('attachment', $zipdownloadfilename);
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');
	header('Content-Length: '.dol_filesize($ziptmpfile));

	// Close the database connection before streaming a potentially large file to the client.
	if (is_object($db)) {
		$db->close();
	}

	readfileLowMemory($ziptmpfile);
	// Remove the temporary archive only after readfileLowMemory has finished using it.
	dol_delete_file($ziptmpfile, 1, 1, 1, null, false, 0, 1);
	exit;
}



/*
 * View
 */

// If we have a hash public (hashp), we guess the original_file.
$ecmfile = '';
if (!empty($hashp)) {
	if (GETPOST('type', 'alpha') == 'link') {
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$link = new Link($db);
		$result = $link->fetch(0, $hashp);
		if ($result > 0 && !empty($link->url)) {
			if (preg_match('/^(http|dav)/', $link->url)) {
				header('Location: '.$link->url);
				exit;
			}
		} else {
			$langs->load("errors");
			httponly_accessforbidden($langs->trans("ErrorLinkNotFoundWithSharedLink"), 403, 1);
		}
	} else {
		include_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
		$ecmfile = new EcmFiles($db);
		$result = $ecmfile->fetch(0, '', '', '', $hashp);
		if ($result > 0) {
			$tmp = explode('/', $ecmfile->filepath, 2); // $ecmfile->filepath is relative to document directory
			// filepath can be 'users/X' or 'X/propale/PR11111'
			if (is_numeric($tmp[0])) { // If first tmp is numeric, it is subdir of company for multicompany, we take next part.
				$tmp = explode('/', $tmp[1], 2);
			}
			$moduleparttocheck = $tmp[0]; // moduleparttocheck is first part of path

			if ($modulepart) {    // Not required, so often not defined, for link using public hashp parameter.
				if ($moduleparttocheck == $modulepart) {
					// We remove first level of directory
					$original_file = (($tmp[1] ? $tmp[1] . '/' : '') . $ecmfile->filename); // this is relative to module dir
					//var_dump($original_file); exit;
				} else {
					httponly_accessforbidden('Bad link. File is from another module part.', 403);
				}
			} else {
				$modulepart = $moduleparttocheck;
				$original_file = (($tmp[1] ? $tmp[1] . '/' : '') . $ecmfile->filename); // this is relative to module dir
			}

			$entity = $ecmfile->entity;
			if (isModEnabled('multicompany') && !empty($ecmfile->src_object_type) && $ecmfile->src_object_id > 0) {
				$object = fetchObjectByElement($ecmfile->src_object_id, $ecmfile->src_object_type);
				if (is_object($object) && $object->id > 0) {
					$entity = $object->entity;
				}
			}

			if ($entity != $conf->entity) {
				$conf->entity = $entity;
				$conf->setValues($db);
				// Multicompany: Here we are switching entity and later we will check the requested object is in this entity but may be that user is not allowed to log/see entity
				// but we don't mind, we are using the public hash to get file.
			}
		} else {
			$langs->load("errors");
			httponly_accessforbidden($langs->trans("ErrorFileNotFoundWithSharedLink"), 403, 1);
		}
	}
}

// Define attachment (attachment=true to force choice popup 'open'/'save as')
// Existing document links still honor MAIN_DISABLE_FORCE_SAVEAS; only the explicit forcedownload action bypasses it.
$forceattachment = (GETPOSTINT('forcedownload') || $action == 'forcedownload');
$attachment = true;
if (preg_match('/\.(html|htm)$/i', $original_file)) {
	$attachment = false;
}
if (isset($_GET["attachment"])) {
	$attachment = GETPOST("attachment", 'alpha') ? true : false;
}
if (getDolGlobalString('MAIN_DISABLE_FORCE_SAVEAS') && !$forceattachment) {
	$attachment = false;
}
if ($forceattachment) {
	$attachment = true;
}

// Resolve access after deciding the requested disposition so both preview and forced-download links share the same file checks.
$resolvedfile = dol_document_resolve_secure_file($modulepart, $original_file, (int) $entity, $user, $hashp);
$original_file = $resolvedfile['original_file'];
$accessallowed = $resolvedfile['accessallowed'];
$sqlprotectagainstexternals = $resolvedfile['sqlprotectagainstexternals'];
$fullpath_original_file = $resolvedfile['fullpath_original_file'];
$fullpath_original_file_osencoded = $resolvedfile['fullpath_original_file_osencoded'];
$filename = $resolvedfile['filename'];
$type = $resolvedfile['type'];

// Output file on browser
dol_syslog("document.php download $fullpath_original_file filename=$filename content-type=$type");

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

// Set this for test
//$type = 'text/html'; $attachment = -1;


// If we show an invoice, we test if we must regenerate the PDF
if ($modulepart == 'facture') {
	$refname = basename(dirname($original_file)."/");
	if ($refname == 'thumbs' || $refname == 'temp') {
		// If we get the thumbs directory, we must go one step higher. For example original_file='10/thumbs/myfile_small.jpg' -> refname='10'
		$refname = basename(dirname(dirname($original_file))."/");
	}

	$invoice = fetchObjectByElement(0, $modulepart, $refname);

	if ($original_file == preg_replace('/facture\//', '', $invoice->last_main_doc)) {
		// We are on the download or print of the main document
		if ($invoice instanceOf Facture && $invoice->status > Facture::STATUS_DRAFT) {
			$action = 'DOC_DOWNLOAD';
			if (GETPOSTISSET('attachement') || GETPOST('preview')) {
				$action = 'DOC_PREVIEW';
			}

			dol_syslog("Print for action=".$action.". Current counter of this non draft invoice is already ".$invoice->id.", so file was already printed, so we regenerate the PDF to add mention DUPLICATA", LOG_DEBUG);

			// $object->pos_print_counter is current value. We increase it here.
			if ($invoice->status == Facture::STATUS_CLOSED) {
				// Increase counter by 1
				$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET pos_print_counter = pos_print_counter + 1";
				$sql .= " WHERE rowid = ".((int) $invoice->id);
				$db->query($sql);

				$invoice->pos_print_counter += 1;
				//$invoice->update($user, 1);	// disabled update, we already did a direct sql update before. We disable trigger here because we already call the trigger $action = DOC_PREVIEW or DOC_DOWNLOAD just after.
			}

			// When we reach the second print, we must regenerate the document to have the mention duplicata on PDF)
			// No need if we are at print 3, 4 or more. The PDF was regenerated when counter was 2,
			if ($invoice->pos_print_counter == 2) {
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang(GETPOST('lang'));
				$outputlangs->loadLangs(array("admin", "blockedlog"));

				$hidedetails = 0;
				$hidedesc = 0;
				$hideref = 0;
				$moreparams = '';
				$hidedetails = isset($hidedetails) ? $hidedetails : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0); // @phpstan-ignore-line as variable $hidedetails is forced
				$hidedesc = isset($hidedesc) ? $hidedesc : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0); // @phpstan-ignore-line as variable $hidedesc is forced
				$hideref = isset($hideref) ? $hideref : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0); // @phpstan-ignore-line as variable $hideref is forced
				$moreparams = isset($moreparams) ? $moreparams : null; // @phpstan-ignore-line as variable $moreparams is forced

				$result = $invoice->generateDocument($invoice->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
				if ($result < 0) {
					dol_syslog("Failed to regenerate PDF", LOG_WARNING);
				}
			}

			// Call trigger
			$result = $invoice->call_trigger($action, $user);
			if ($result < 0) {
				top_httphead();

				http_response_code(500);
				print 'Error in trigger: '.$invoice->errorsToString();
				exit;
			}
		}
	}
}



// Permissions are ok and file found, so we return it
top_httphead($type);

header('Content-Description: File Transfer');
if ($encoding) {
	header('Content-Encoding: '.$encoding);
}
// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)

if ($attachment > 0) {
	// Attachment asks the browser to download the file; browser preferences may still save automatically without prompting.
	dol_document_send_content_disposition('attachment', $filename);
} elseif (empty($attachment)) {
	dol_document_send_content_disposition('inline', $filename);
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
