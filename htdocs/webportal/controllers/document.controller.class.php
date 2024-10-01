<?php
/*
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
 */

 /**
 * \file        htdocs/webportal/controllers/document.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for documents
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';


/**
 * Class for DocumentController
 */
class DocumentController extends Controller
{
	/**
	 * @var string Action
	 */
	public $action;

	/**
	 * @var	boolean	Is Attachment
	 */
	public $attachment;

	/**
	 * @var string Encoding
	 */
	public $encoding;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string File name
	 */
	public $filename;

	/**
	 * @var string Full path of original file
	 */
	public $fullpath_original_file;

	/**
	 * @var string Full path of original file with encoded for OS
	 */
	public $fullpath_original_file_osencoded;

	/**
	 * @var string Module of document ('module', 'module_user_temp', 'module_user' or 'module_temp'). Example: 'medias', 'invoice', 'logs', 'tax-vat', ...
	 */
	public $modulepart;

	/**
	 * @var string Relative path with filename, relative to modulepart.
	 */
	public $original_file;

	/**
	 * @var string Mime type of file
	 */
	public $type;


	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		global $conf, $hookmanager;

		define('MAIN_SECURITY_FORCECSP', "default-src: 'none'");

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

		$context = Context::getInstance();

		$encoding = '';
		$action = GETPOST('action', 'aZ09');
		$original_file = GETPOST('file', 'alphanohtml'); // Do not use urldecode here ($_GET are already decoded by PHP).
		$modulepart = GETPOST('modulepart', 'alpha');
		$entity = GETPOSTINT('entity') ? GETPOSTINT('entity') : $conf->entity;
		$socId = GETPOSTINT('soc_id');

		// Security check
		if (empty($modulepart)) {
			httponly_accessforbidden('Bad link. Bad value for parameter modulepart', 400);
			exit;
		}
		if (empty($original_file)) {
			httponly_accessforbidden('Bad link. Missing identification to find file (original_file)', 400);
			exit;
		}

		// get original file
		$ecmfile = '';

		// Define attachment (attachment=true to force choice popup 'open'/'save as')
		$attachment = true;
		if (preg_match('/\.(html|htm)$/i', $original_file)) {
			$attachment = false;
		}
		if (GETPOSTISSET("attachment")) {
			$attachment = GETPOST("attachment", 'alpha') ? true : false;
		}
		if (getDolGlobalString('MAIN_DISABLE_FORCE_SAVEAS')) {
			$attachment = false;
		}

		// Define mime type
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
		$original_file = preg_replace('/\.\.+/', '..', $original_file);    // Replace '... or more' with '..'
		$original_file = str_replace('../', '/', $original_file);
		$original_file = str_replace('..\\', '/', $original_file);

		// Check security and set return info with full path of file
		$accessallowed = 0; // not allowed by default
		$moduleName = $modulepart;
		$moduleNameEn = $moduleName;
		if ($moduleName == 'commande') {
			$moduleNameEn = 'order';
		} elseif ($moduleName == 'facture') {
			$moduleNameEn = 'invoice';
		}
		$moduleNameUpperEn = strtoupper($moduleNameEn);
		// check config access
		// and file mime type (only PDF)
		// and check login access
		if (getDolGlobalInt('WEBPORTAL_' . $moduleNameUpperEn . '_LIST_ACCESS')
			&& in_array($type, array('application/pdf'))
			&& ($context->logged_thirdparty && $context->logged_thirdparty->id > 0)
			&& $context->logged_thirdparty->id == $socId
		) {
			if (isModEnabled($moduleName) && isset($conf->{$moduleName}->multidir_output[$entity])) {
				$original_file = $conf->{$moduleName}->multidir_output[$entity] . '/' . $original_file;
				$accessallowed = 1;
			}
		}
		$fullpath_original_file = $original_file; // $fullpath_original_file is now a full path name

		// Security:
		// Limit access if permissions are wrong
		if (!$accessallowed) {
			accessforbidden();
		}

		// Security:
		// We refuse directory transversal change and pipes in file names
		if (preg_match('/\.\./', $fullpath_original_file) || preg_match('/[<>|]/', $fullpath_original_file)) {
			dol_syslog("Refused to deliver file " . $fullpath_original_file);
			print "ErrorFileNameInvalid: " . dol_escape_htmltag($original_file);
			exit;
		}

		// Find the subdirectory name as the reference
		$refname = basename(dirname($original_file) . "/");

		$filename = basename($fullpath_original_file);
		$filename = preg_replace('/\.noexe$/i', '', $filename);

		// Output file on browser
		dol_syslog("document controller download $fullpath_original_file filename=$filename content-type=$type");
		$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file); // New file name encoded in OS encoding charset

		// This test if file exists should be useless. We keep it to find bug more easily
		if (!file_exists($fullpath_original_file_osencoded)) {
			dol_syslog("ErrorFileDoesNotExists: " . $fullpath_original_file);
			print "ErrorFileDoesNotExists: " . $original_file;
			exit;
		}

		$fileSize = dol_filesize($fullpath_original_file);
		$fileSizeMaxDefault = 20 * 1024; // 20 Mo by default
		$fileSizeMax = getDolGlobalInt('MAIN_SECURITY_MAXFILESIZE_DOWNLOADED', $fileSizeMaxDefault);
		if ($fileSize > $fileSizeMax) {
			dol_syslog('ErrorFileSizeTooLarge: ' . $fileSize);
			print 'ErrorFileSizeTooLarge: ' . $fileSize . ' (max ' . $fileSizeMax . ')';
			exit;
		}

		// Hooks
		$hookmanager->initHooks(array('document'));
		$parameters = array('ecmfile' => $ecmfile, 'modulepart' => $modulepart, 'original_file' => $original_file,
			'entity' => $entity, 'refname' => $refname, 'fullpath_original_file' => $fullpath_original_file,
			'filename' => $filename, 'fullpath_original_file_osencoded' => $fullpath_original_file_osencoded);
		$object = new stdClass();
		$reshook = $hookmanager->executeHooks('downloadDocument', $parameters, $object, $action); // Note that $action and $object may have been
		if ($reshook < 0) {
			$errors = $hookmanager->error . (is_array($hookmanager->errors) ? (!empty($hookmanager->error) ? ', ' : '') . implode(', ', $hookmanager->errors) : '');
			dol_syslog("document.php - Errors when executing the hook 'downloadDocument' : " . $errors);
			print "ErrorDownloadDocumentHooks: " . $errors;
			exit;
		}

		$this->action = $action;
		$this->attachment = $attachment;
		$this->encoding = $encoding;
		$this->entity = $entity;
		$this->filename = $filename;
		$this->fullpath_original_file = $fullpath_original_file;
		$this->fullpath_original_file_osencoded = $fullpath_original_file_osencoded;
		$this->modulepart = $modulepart;
		$this->original_file = $original_file;
		$this->type = $type;
	}

	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = true;

		return parent::checkAccess();
	}

	/**
	 * Action method is called before html output
	 * can be used to manage security and change context
	 *
	 * @return  int     Return integer < 0 on error, > 0 on success
	 */
	public function action()
	{
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return -1;
		}

		//$context = Context::getInstance();
		//$context->title = $langs->trans('WebPortalDocumentTitle');
		//$context->desc = $langs->trans('WebPortalDocumentDesc');
		//$context->doNotDisplayHeaderBar=1;// hide default header

		$this->init();

		return 1;
	}

	/**
	 * Display
	 *
	 * @return  void
	 */
	public function display()
	{
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			$this->display404();
			return;
		}

		// initialize
		$attachment = $this->attachment;
		$encoding = $this->encoding;
		$filename = $this->filename;
		$fullpath_original_file = $this->fullpath_original_file;
		$fullpath_original_file_osencoded = $this->fullpath_original_file_osencoded;
		$type = $this->type;

		clearstatcache();

		// Permissions are ok and file found, so we return it
		top_httphead($type);
		header('Content-Description: File Transfer');
		if ($encoding) {
			header('Content-Encoding: ' . $encoding);
		}
		// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)
		if ($attachment) {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		} else {
			header('Content-Disposition: inline; filename="' . $filename . '"');
		}
		header('Cache-Control: Public, must-revalidate');
		header('Pragma: public');

		// Send file now
		header('Content-Length: ' . dol_filesize($fullpath_original_file));
		readfileLowMemory($fullpath_original_file_osencoded);
	}
}
