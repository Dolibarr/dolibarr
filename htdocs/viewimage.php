<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@inodbox.com>
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
 *		\file       htdocs/viewimage.php
 *		\brief      Wrapper to show images into Dolibarr screens.
 *		\remarks    Call to wrapper is :
 *					DOL_URL_ROOT.'/viewimage.php?modulepart=diroffile&file=relativepathofofile&cache=0
 *					DOL_URL_ROOT.'/viewimage.php?hashp=sharekey
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (!defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

// Some value of modulepart can be used to get resources that are public so no login are required.
// Note that only directory logo is free to access without login.
if (isset($_GET["modulepart"]) && $_GET["modulepart"] == 'mycompany' && preg_match('/^\/?logos\//', $_GET['file']))
{
	if (!defined("NOLOGIN"))		define("NOLOGIN", 1);
	if (!defined("NOCSRFCHECK"))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if (!defined("NOIPCHECK"))		define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
// For direct external download link, we don't need to load/check we are into a login session
if (isset($_GET["hashp"]) && !defined("NOLOGIN"))
{
	if (!defined("NOLOGIN"))		define("NOLOGIN", 1);
	if (!defined("NOCSRFCHECK"))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if (!defined("NOIPCHECK"))		define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
// Some value of modulepart can be used to get resources that are public so no login are required.
if (isset($_GET["modulepart"]) && $_GET["modulepart"] == 'medias')
{
	if (!defined("NOLOGIN"))		define("NOLOGIN", 1);
	if (!defined("NOCSRFCHECK"))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if (!defined("NOIPCHECK"))		define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// Used by TakePOS Auto Order
if (isset($_GET["modulepart"]) && $_GET["modulepart"] == 'product' && isset($_GET["publictakepos"]))
{
	if (!defined("NOLOGIN"))		define("NOLOGIN", 1);
	if (!defined("NOCSRFCHECK"))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if (!defined("NOIPCHECK"))		define("NOIPCHECK", 1); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// For multicompany
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

/**
 * Header empty
 *
 * @ignore
 * @return	void
 */
function llxHeader()
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

$action = GETPOST('action', 'aZ09');
$original_file = GETPOST('file', 'alphanohtml'); // Do not use urldecode here ($_GET are already decoded by PHP).
$hashp = GETPOST('hashp', 'aZ09');
$modulepart = GETPOST('modulepart', 'alpha');
$urlsource = GETPOST('urlsource', 'alpha');
$entity = GETPOST('entity', 'int') ?GETPOST('entity', 'int') : $conf->entity;

// Security check
if (empty($modulepart) && empty($hashp)) accessforbidden('Bad link. Bad value for parameter modulepart', 0, 0, 1);
if (empty($original_file) && empty($hashp) && $modulepart != 'barcode') accessforbidden('Bad link. Missing identification to find file (param file or hashp)', 0, 0, 1);
if ($modulepart == 'fckeditor') $modulepart = 'medias'; // For backward compatibility



/*
 * Actions
 */

// None



/*
 * View
 */

if (GETPOST("cache", 'alpha'))
{
	// Important: Following code is to avoid page request by browser and PHP CPU at
	// each Dolibarr page access.
	if (empty($dolibarr_nocache))
	{
		header('Cache-Control: max-age=3600, public, must-revalidate');
		header('Pragma: cache'); // This is to avoid having Pragma: no-cache
	} else header('Cache-Control: no-cache');
	//print $dolibarr_nocache; exit;
}

// If we have a hash public (hashp), we guess the original_file.
if (!empty($hashp))
{
	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	$ecmfile = new EcmFiles($db);
	$result = $ecmfile->fetch(0, '', '', '', $hashp);
	if ($result > 0)
	{
		$tmp = explode('/', $ecmfile->filepath, 2); // $ecmfile->filepath is relative to document directory
		// filepath can be 'users/X' or 'X/propale/PR11111'
		if (is_numeric($tmp[0])) // If first tmp is numeric, it is subdir of company for multicompany, we take next part.
		{
			$tmp = explode('/', $tmp[1], 2);
		}
		$moduleparttocheck = $tmp[0]; // moduleparttocheck is first part of path

		if ($modulepart)	// Not required, so often not defined, for link using public hashp parameter.
		{
			if ($moduleparttocheck == $modulepart)
			{
				// We remove first level of directory
				$original_file = (($tmp[1] ? $tmp[1].'/' : '').$ecmfile->filename); // this is relative to module dir
				//var_dump($original_file); exit;
			} else {
				accessforbidden('Bad link. File is from another module part.', 0, 0, 1);
			}
		} else {
			$modulepart = $moduleparttocheck;
			$original_file = (($tmp[1] ? $tmp[1].'/' : '').$ecmfile->filename); // this is relative to module dir
		}
	} else {
		$langs->load("errors");
		accessforbidden($langs->trans("ErrorFileNotFoundWithSharedLink"), 0, 0, 1);
	}
}

// Define mime type
$type = 'application/octet-stream';
if (GETPOST('type', 'alpha')) $type = GETPOST('type', 'alpha');
else $type = dol_mimetype($original_file);

// Security: This wrapper is for images. We do not allow type/html
if (preg_match('/html/i', $type)) accessforbidden('Error: Using the image wrapper to output a file with a mime type HTML is not possible.', 0, 0, 1);
// Security: This wrapper is for images. We do not allow files ending with .noexe
if (preg_match('/\.noexe$/i', $original_file)) accessforbidden('Error: Using the image wrapper to output a file ending with .noexe is not allowed.', 0, 0, 1);

// Security: Delete string ../ into $original_file
$original_file = str_replace("../", "/", $original_file);

// Find the subdirectory name as the reference
$refname = basename(dirname($original_file)."/");

// Security check
if (empty($modulepart)) accessforbidden('Bad value for parameter modulepart', 0, 0, 1);

$check_access = dol_check_secure_access_document($modulepart, $original_file, $entity, $refname);
$accessallowed              = $check_access['accessallowed'];
$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
$fullpath_original_file     = $check_access['original_file']; // $fullpath_original_file is now a full path name

if (!empty($hashp)) {
	$accessallowed = 1; // When using hashp, link is public so we force $accessallowed
	$sqlprotectagainstexternals = '';
} elseif (isset($_GET["publictakepos"])) {
	if (!empty($conf->global->TAKEPOS_AUTO_ORDER)) {
		$accessallowed = 1; // Only if TakePOS Public Auto Order is enabled and received publictakepos variable
	}
} else {
	// Basic protection (against external users only)
	if ($user->socid > 0)
	{
		if ($sqlprotectagainstexternals)
		{
			$resql = $db->query($sqlprotectagainstexternals);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					if ($user->socid != $obj->fk_soc)
					{
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
if (!$accessallowed)
{
	accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers.
if (preg_match('/\.\./', $fullpath_original_file) || preg_match('/[<>|]/', $fullpath_original_file))
{
	dol_syslog("Refused to deliver file ".$fullpath_original_file);
	print "ErrorFileNameInvalid: ".dol_escape_htmltag($original_file);
	exit;
}



if ($modulepart == 'barcode')
{
	$generator = GETPOST("generator", "alpha");
	$code = GETPOST("code", 'none'); // This can be rich content (qrcode, datamatrix, ...)
	$encoding = GETPOST("encoding", "alpha");
	$readable = GETPOST("readable", 'alpha') ?GETPOST("readable", "alpha") : "Y";

	if (empty($generator) || empty($encoding))
	{
		print 'Error: Parameter "generator" or "encoding" not defined';
		exit;
	}

	$dirbarcode = array_merge(array("/core/modules/barcode/doc/"), $conf->modules_parts['barcode']);

	$result = 0;

	foreach ($dirbarcode as $reldir)
	{
		$dir = dol_buildpath($reldir, 0);
		$newdir = dol_osencode($dir);

		// Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php)
		if (!is_dir($newdir)) continue;

		$result = @include_once $newdir.$generator.'.modules.php';
		if ($result) break;
	}

	// Load barcode class
	$classname = "mod".ucfirst($generator);
	$module = new $classname($db);
	if ($module->encodingIsSupported($encoding))
	{
		$result = $module->buildBarCode($code, $encoding, $readable);
	}
} else {
	// Open and return file
	clearstatcache();

	$filename = basename($fullpath_original_file);

	// Output files on browser
	dol_syslog("viewimage.php return file $fullpath_original_file filename=$filename content-type=$type");

	// This test is to avoid error images when image is not available (for example thumbs).
	if (!dol_is_file($fullpath_original_file) && empty($_GET["noalt"]))
	{
		$fullpath_original_file = DOL_DOCUMENT_ROOT.'/public/theme/common/nophoto.png';
		/*$error='Error: File '.$_GET["file"].' does not exists or filesystems permissions are not allowed';
        print $error;
        exit;*/
	}

	// Permissions are ok and file found, so we return it
	if ($type)
	{
		top_httphead($type);
		header('Content-Disposition: inline; filename="'.basename($fullpath_original_file).'"');
	} else {
		top_httphead('image/png');
		header('Content-Disposition: inline; filename="'.basename($fullpath_original_file).'"');
	}

	$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file);

	readfile($fullpath_original_file_osencoded);
}


if (is_object($db)) $db->close();
