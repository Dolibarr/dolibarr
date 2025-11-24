<?php
/*
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
* \file        htdocs/webportal/controllers/viewimage.controller.class.php
* \ingroup     webportal
* \brief       This file is a controller for documents
*/

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';


/**
 * Class for ViewImageController
 */
class ViewImageController extends Controller
{
	/**
	 * @var string Action
	 */
	public $action;

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
		global $conf, $hookmanager, $dolibarr_nocache, $user;

		define('MAIN_SECURITY_FORCECSP', "default-src: 'none'");

		if (!defined('NOREQUIRESOC')) {
			define('NOREQUIRESOC', '1');
		}
		if (!defined('NOREQUIRETRAN')) {
			define('NOREQUIRETRAN', '1');
		}
		if (!defined('NOCSRFCHECK')) {
			define('NOCSRFCHECK', '1');
		}
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

		// For MultiCompany module.
		// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
		// Because 2 entities can have the same ref.
		$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
		define("DOLENTITY", $entity);

		$context = Context::getInstance();

		$action = GETPOST('action', 'aZ09');
		$original_file = GETPOST('file', 'alphanohtml');
		$hashp = GETPOST('hashp', 'aZ09', 1);
		$extname = GETPOST('extname', 'alpha', 1);
		$modulepart = GETPOST('modulepart', 'alpha', 1);
		$urlsource = GETPOST('urlsource', 'alpha');
		$entity = (GETPOSTINT('entity') ? GETPOSTINT('entity') : $conf->entity);

		// Security check
		if (empty($modulepart) && empty($hashp)) {
			httponly_accessforbidden('Bad link. Bad value for parameter modulepart', 400);
		}
		if (empty($original_file) && empty($hashp) && $modulepart != 'barcode') {
			httponly_accessforbidden('Bad link. Missing identification to find file (param file or hashp)', 400);
		}
		if ($modulepart == 'fckeditor') {
			$modulepart = 'medias'; // For backward compatibility
		}

		$cachestring = GETPOST("cache", 'aZ09');    // May be 1, or an int, or a hash
		if ($cachestring) {
			// Important: The following code is to avoid a page request by the browser and PHP CPU at each Dolibarr page access.
			// We are here when param cache=xxx to force a cache policy:
			//  xxx=1 means cache of 3600s
			//  xxx=abcdef or 123456789 means a cache of 1 week (the key will be modified to get break cache use)
			if (empty($dolibarr_nocache)) {
				$delaycache = ((is_numeric($cachestring) && (int) $cachestring > 1 && (int) $cachestring < 999999) ? $cachestring : '3600');
				header('Cache-Control: max-age=' . $delaycache . ', public, must-revalidate');
				header('Pragma: cache'); // This is to avoid to have Pragma: no-cache set by proxy or web server
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (int) $delaycache) . ' GMT');    // This is to avoid to have Expires set by proxy or web server
			} else {
				// If any cache on files were disable by config file (for test purpose)
				header('Cache-Control: no-cache');
			}
			//print $dolibarr_nocache; exit;
		}

		// Define mime type
		$type = 'application/octet-stream';
		if (GETPOST('type', 'alpha')) {
			$type = GETPOST('type', 'alpha');
		} else {
			$type = dol_mimetype($original_file);
		}

		// Security: This wrapper is for images. We do not allow type/html
		if (preg_match('/html/i', $type)) {
			httponly_accessforbidden('Error: Using the image wrapper to output a file with a mime type HTML is not possible.');
		}
		// Security: This wrapper is for images. We do not allow files ending with .noexe
		if (preg_match('/\.noexe$/i', $original_file)) {
			httponly_accessforbidden('Error: Using the image wrapper to output a file ending with .noexe is not allowed.');
		}

		// Security: Delete string ../ or ..\ into $original_file
		$original_file = preg_replace('/\.\.+/', '..', $original_file);    // Replace '... or more' with '..'
		$original_file = str_replace('../', '/', $original_file);
		$original_file = str_replace('..\\', '/', $original_file);

		// Find the subdirectory name as the reference
		$refname = basename(dirname($original_file) . "/");
		if ($refname == 'thumbs') {
			// If we get the thumbs directory, we must go one step higher. For example original_file='10/thumbs/myfile_small.jpg' -> refname='10'
			$refname = basename(dirname(dirname($original_file)) . "/");
		}

		// Check that file is allowed for view with viewimage.php
		if (!empty($original_file) && !dolIsAllowedForPreview($original_file)) {
			httponly_accessforbidden('This file extension is not qualified for preview', 403);
		}

		// Security check
		if (empty($modulepart)) {
			httponly_accessforbidden('Bad value for parameter modulepart', 400);
		}

		// When logged in a different entity, medias cannot be accessed because $conf->$module->multidir_output
		// is not set on the requested entity, but they are public documents, so reset entity
		if ($modulepart === 'medias' && $entity != $conf->entity) {
			$conf->entity = $entity;
			$conf->setValues($this->db);
		}

		$sqlprotectagainstexternals = '';
		$fullpath_original_file = '';
		$accessallowed = 0;

		// Hooks
		$hookmanager->initHooks(array('viewimage'));
		$parameters = array('modulepart' => $modulepart, 'original_file' => &$original_file,
			'sqlprotectagainstexternals' => &$sqlprotectagainstexternals, 'fullpath_original_file' => &$fullpath_original_file,
			'entity' => $entity, 'accessallowed' => &$accessallowed);
		$object = new stdClass();
		$reshook = $hookmanager->executeHooks('accessViewImage', $parameters, $object, $action); // Note that $action and $object may have been
		if ($reshook < 0) {
			$errors = $hookmanager->error . (is_array($hookmanager->errors) ? (!empty($hookmanager->error) ? ', ' : '') . implode(', ', $hookmanager->errors) : '');
			dol_syslog("document.php - Errors when executing the hook 'accessViewImage' : " . $errors);
			print "ErrorViewImageHooks: " . $errors;
			exit;
		} elseif (empty($reshook)) {
			$check_access = dol_check_secure_access_document($modulepart, $original_file, $entity, $user, $refname);
			$accessallowed = $check_access['accessallowed'];
			$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
			$fullpath_original_file = $check_access['original_file']; // $fullpath_original_file is now a full path name
		}

		if (!empty($hashp)) {
			$accessallowed = 1; // When using hashp, link is public so we force $accessallowed
			$sqlprotectagainstexternals = '';
		} else {
			// Basic protection (against external users only)
			if ($user->socid > 0) {
				if ($sqlprotectagainstexternals) {
					$resql = $this->db->query($sqlprotectagainstexternals);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$obj = $this->db->fetch_object($resql);
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
		// On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers.
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
		$fileSizeMax = getDolGlobalInt('MAIN_SECURITY_MAXFILESIZE_DOWNLOADED');
		if ($fileSizeMax && $fileSize > $fileSizeMax) {
			dol_syslog('ErrorFileSizeTooLarge: ' . $fileSize);
			print 'ErrorFileSizeTooLarge: ' . $fileSize . ' (max ' . $fileSizeMax . ' Kb)';
			exit;
		}

		// Hooks
		$hookmanager->initHooks(array('document'));
		$parameters = array('modulepart' => $modulepart, 'original_file' => $original_file,
			'entity' => $entity, 'refname' => $refname, 'fullpath_original_file' => $fullpath_original_file,
			'filename' => $filename, 'fullpath_original_file_osencoded' => $fullpath_original_file_osencoded);
		$object = new stdClass();
		$reshook = $hookmanager->executeHooks('viewImage', $parameters, $object, $action); // Note that $action and $object may have been
		if ($reshook < 0) {
			$errors = $hookmanager->error . (is_array($hookmanager->errors) ? (!empty($hookmanager->error) ? ', ' : '') . implode(', ', $hookmanager->errors) : '');
			dol_syslog("document.php - Errors when executing the hook 'viewImage' : " . $errors);
			print "ErrorViewImageHooks: " . $errors;
			exit;
		}

		$this->action = $action;
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
		global $conf;

		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			$this->display404();
			return;
		}

		// initialize
		$modulepart = $this->modulepart;
		$fullpath_original_file = $this->fullpath_original_file;
		$fullpath_original_file_osencoded = $this->fullpath_original_file_osencoded;
		$type = $this->type;

		if ($modulepart == 'barcode') {
			$generator = GETPOST("generator", "aZ09");
			$encoding = GETPOST("encoding", "aZ09");
			$readable = GETPOST("readable", 'aZ09') ? GETPOST("readable", "aZ09") : "Y";
			if (in_array($encoding, array('EAN8', 'EAN13'))) {
				$code = GETPOST("code", 'alphanohtml');
			} else {
				$code = GETPOST("code", 'restricthtml'); // This can be rich content (qrcode, datamatrix, ...)
			}

			// If $code is virtualcard_xxx_999.vcf, it is a file to read to get code
			$reg = array();
			if (preg_match('/^virtualcard_([^_]+)_(\d+)\.vcf$/', $code, $reg)) {
				$vcffile = '';
				$id = 0;
				$login = '';
				if ($reg[1] == 'user' && (int) $reg[2] > 0) {
					$vcffile = $conf->user->dir_temp . '/' . $code;
					$id = (int) $reg[2];
					$tmpuser = new User($this->db);
					$tmpuser->fetch($id);
					$login = $tmpuser->login;
				} elseif ($reg[1] == 'contact' && (int) $reg[2] > 0) {
					$vcffile = $conf->contact->dir_temp . '/' . $code;
					$id = (int) $reg[2];
				}

				$code = '';
				if ($vcffile && $id) {
					// Case of use of viewimage to get the barcode for user pubic profile,
					// we must check the securekey that protet against forging url
					if ($reg[1] == 'user' && (int) $reg[2] > 0) {
						$encodedsecurekey = dol_hash($conf->file->instance_unique_id . 'uservirtualcard' . $id . '-' . $login, 'md5');
						if ($encodedsecurekey != GETPOST('securekey')) {
							$code = 'badvalueforsecurekey';
						}
					}
					if (empty($code)) {
						$code = file_get_contents($vcffile);
					}
				}
			}


			if (empty($generator) || empty($encoding)) {
				print 'Error: Parameter "generator" or "encoding" not defined';
				exit;
			}

			$dirbarcode = array_merge(array("/core/modules/barcode/doc/"), $conf->modules_parts['barcode']);

			$result = 0;

			foreach ($dirbarcode as $reldir) {
				$dir = dol_buildpath($reldir, 0);
				$newdir = dol_osencode($dir);

				// Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php)
				if (!is_dir($newdir)) {
					continue;
				}

				$result = @include_once $newdir . $generator . '.modules.php';
				if ($result) {
					break;
				}
			}

			// Load barcode class
			$classname = "mod" . ucfirst($generator);

			$module = new $classname($this->db);
			'@phan-var-force ModeleBarCode $module';
			/** @var ModeleBarCode $module */
			if ($module->encodingIsSupported($encoding)) {
				$result = $module->buildBarCode($code, $encoding, $readable);
			}
		} else {
			// Open and return file
			clearstatcache();

			$filename = basename($fullpath_original_file);

			// Output files on browser
			dol_syslog("viewimage.php return file $fullpath_original_file filename=$filename content-type=$type");

			if (!dol_is_file($fullpath_original_file) && !GETPOSTINT("noalt", 1)) {
				// This test is to replace error images with a nice "notfound image" when image is not available (for example when thumbs not yet generated).
				$fullpath_original_file = Context::getRootConfigUrl() . 'img/nophoto.png';
				/*$error='Error: File '.$_GET["file"].' does not exists or filesystems permissions are not allowed';
				print $error;
				exit;*/
			}

			// Permissions are ok and file found, so we return it
			if ($type) {
				top_httphead($type);
				header('Content-Disposition: inline; filename="' . basename($fullpath_original_file) . '"');
			} else {
				top_httphead('image/png');
				header('Content-Disposition: inline; filename="' . basename($fullpath_original_file) . '"');
			}

			$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file);

			readfile($fullpath_original_file_osencoded);
		}
	}
}
