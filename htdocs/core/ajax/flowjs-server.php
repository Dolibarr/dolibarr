<?php
/* Copyright (C) 2012 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/bankconciliate.php
 *       \brief      File to set data for bank concilation
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
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');    // Required to know date format for dol_print_date

// Load Dolibarr environment
require '../../main.inc.php';


$action = GETPOST('action', 'aZ09');
$module = GETPOST('module', 'aZ09');
$flowFilename = GETPOST('flowFilename', 'alpha');
$flowIdentifier = GETPOST('flowIdentifier', 'alpha');
$flowChunkNumber = GETPOST('flowChunkNumber', 'alpha');
$flowChunkSize = GETPOST('flowChunkSize', 'alpha');
$flowTotalSize = GETPOST('flowTotalSize', 'alpha');

/*
 * Action
 */


top_httphead();
dol_syslog(join(',', $_GET));

if ($_SERVER['REQUEST_METHOD'] === 'GET' ) {
	$temp_dir = DOL_DATA_ROOT.'/'.$module.'/temp/'.$flowIdentifier;
	$chunk_file = $temp_dir.'/'.$flowFilename.'.part'.$flowChunkNumber;
	if (file_exists($chunk_file)) {
		 header("HTTP/1.0 200 Ok");
	} else {
		header("HTTP/1.0 404 Not Found");
	}
}



// loop through files and move the chunks to a temporarily created directory
if (!empty($_FILES)) foreach ($_FILES as $file) {
	// check the error status
	if ($file['error'] != 0) {
		dol_syslog('error '.$file['error'].' in file '.$flowFilename);
		continue;
	}

	// init the destination file (format <filename.ext>.part<#chunk>
	// the file is stored in a temporary directory
	$temp_dir = DOL_DATA_ROOT.'/'.$module.'/temp/'.$flowIdentifier;
	$dest_file = $temp_dir.'/'.$flowFilename.'.part'.$flowChunkNumber;

	// create the temporary directory
	if (!dol_is_dir($temp_dir)) {
		dol_mkdir($temp_dir, '', 0777);
	}

	// move the temporary file
	if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
		dol_syslog('Error saving (move_uploaded_file) chunk '.$flowChunkNumber.' for file '.$flowFilename);
	} else {
		// check if all the parts present, and create the final destination file
		createFileFromChunks($temp_dir, $flowFilename, $flowChunkSize, $flowTotalSize);
	}
}


/**
 * Check if all the parts exist, and
 * gather all the parts of the file together
 * @param string    $temp_dir - the temporary directory holding all the parts of the file
 * @param string    $fileName - the original file name
 * @param string    $chunkSize - each chunk size (in bytes)
 * @param string    $totalSize - original file size (in bytes)
 * @return bool     true if Ok false else
 */
function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize)
{

	dol_syslog(__METHOD__, LOG_DEBUG);
	// count all the parts of this file
	$total_files = 0;
	$files = dol_dir_list($temp_dir, 'files');
	foreach ($files as $file) {
		if (stripos($file, $fileName) !== false) {
			$total_files++;
		}
	}

	// check that all the parts are present
	// the size of the last part is between chunkSize and 2*$chunkSize
	if ($total_files * $chunkSize >=  ($totalSize - $chunkSize + 1)) {
		// create the final destination file
		if (($fp = fopen($temp_dir.$fileName, 'w')) !== false) {
			for ($i=1; $i<=$total_files; $i++) {
				fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
				dol_syslog('writing chunk '.$i);
			}
			fclose($fp);
		} else {
			dol_syslog('cannot create the destination file');
			return false;
		}

		/*// rename the temporary directory (to avoid access from other
		// concurrent chunks uploads) and than delete it
		if (rename($temp_dir, $temp_dir.'_UNUSED')) {
			rrmdir($temp_dir.'_UNUSED');
		} else {
			rrmdir($temp_dir);
		}*/
	}
	return true;
}
