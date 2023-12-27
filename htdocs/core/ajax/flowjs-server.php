<?php
/* Copyright (C) 2023 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/flowjs-server.php
 *       \brief      File to upload very large file, higher than PHP limit. Using flowjs library.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');    // Required to know date format for dol_print_date

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$action = GETPOST('action', 'aZ09');

$module = GETPOST('module', 'aZ09arobase');

$flowFilename = GETPOST('flowFilename', 'alpha');
$flowIdentifier = GETPOST('flowIdentifier', 'alpha');
$flowChunkNumber = GETPOST('flowChunkNumber', 'alpha');
$flowChunkSize = GETPOST('flowChunkSize', 'alpha');
$flowTotalSize = GETPOST('flowTotalSize', 'alpha');

$result = restrictedArea($user, $module, 0, '', 0, 'fk_soc', 'rowid', 0, 1);	// Call with mode return

if ($action != 'upload') {
	httponly_accessforbidden("Param action must be 'upload'");
}

if (!empty($conf->$module->dir_temp)) {
	$upload_dir = $conf->$module->dir_temp;
} else {
	httponly_accessforbidden("Param module does not has a dir_temp directory. Module does not exists or is not activated.");
}

/*
 * Action
 */

top_httphead();

dol_syslog(join(',', $_GET));

$result = false;

if (!empty($upload_dir)) {
	$temp_dir = $upload_dir.'/'.$flowIdentifier;
} else {
	$temp_dir = DOL_DATA_ROOT.'/'.$module.'/temp/'.$flowIdentifier;
	$upload_dir = DOL_DATA_ROOT.'/'.$module.'/temp/';
}

if ($module != "test" && !isModEnabled($module)) {
	echo json_encode("The module ".$module." is not enabled");
	header("HTTP/1.0 400");
	die();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$chunk_file = $temp_dir.'/'.$flowFilename.'.part'.$flowChunkNumber;
	if (file_exists($chunk_file)) {
		header("HTTP/1.0 200 Ok");
	} else {
		header("HTTP/1.0 404 Not Found");
	}
} else {
	// loop through files and move the chunks to a temporarily created directory
	if (file_exists($upload_dir.'/'.$flowFilename)) {
		echo json_encode('File '.$flowIdentifier.' was already uploaded');
		header("HTTP/1.0 200 Ok");
		die();
	} elseif (!empty($_FILES)) {
		foreach ($_FILES as $file) {
			// check the error status
			if ($file['error'] != 0) {
				dol_syslog('error '.$file['error'].' in file '.$flowFilename);
				continue;
			}

			// init the destination file (format <filename.ext>.part<#chunk>
			// the file is stored in a temporary directory
			$dest_file = $temp_dir.'/'.$flowFilename.'.part'.$flowChunkNumber;

			// create the temporary directory
			if (!dol_is_dir($temp_dir)) {
				dol_mkdir($temp_dir);
			}

			// move the temporary file
			if (!dol_move_uploaded_file($file['tmp_name'], $dest_file, 0)) {
				dol_syslog('Error saving (move_uploaded_file) chunk '.$flowChunkNumber.' for file '.$flowFilename);
			} else {
				// check if all the parts present, and create the final destination file
				$result = createFileFromChunks($temp_dir, $upload_dir, $flowFilename, $flowChunkSize, $flowTotalSize);
			}
		}
	}
}
if ($result) {
	echo json_encode('File '.$flowIdentifier.' uploaded');
} else {
	echo json_encode('Error while uploading file '.$flowIdentifier);
}


/**
 * Check if all the parts exist, and gather all the parts of the file together.
 *
 * @param string    $temp_dir 		the temporary directory holding all the parts of the file
 * @param string    $upload_dir 	the temporary directory to create file
 * @param string    $fileName 		the original file name
 * @param string    $chunkSize 		each chunk size (in bytes)
 * @param string    $totalSize 		original file size (in bytes)
 * @return bool     				true if Ok false else
 */
function createFileFromChunks($temp_dir, $upload_dir, $fileName, $chunkSize, $totalSize)
{
	dol_syslog(__FUNCTION__, LOG_DEBUG);

	// count all the parts of this file
	$total_files = 0;
	$files = dol_dir_list($temp_dir, 'files');
	foreach ($files as $file) {
		if (stripos($file["name"], $fileName) !== false) {
			$total_files++;
		}
	}

	// check that all the parts are present
	// the size of the last part is between chunkSize and 2*$chunkSize
	if ($total_files * $chunkSize >=  ($totalSize - $chunkSize + 1)) {
		// create the final destination file
		if (($fp = fopen($upload_dir.'/'.$fileName, 'w')) !== false) {
			for ($i=1; $i<=$total_files; $i++) {
				fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
				dol_syslog('writing chunk '.$i);
			}
			fclose($fp);
		} else {
			dol_syslog('cannot create the destination file');
			return false;
		}

		// rename the temporary directory (to avoid access from other
		// concurrent chunks uploads)
		@rename($temp_dir, $temp_dir.'_UNUSED');
	}

	return true;
}
