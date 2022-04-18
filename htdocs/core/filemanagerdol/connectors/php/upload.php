<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    https://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    https://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This is the "File Uploader" for PHP.
 */

require 'config.php';	// This include the main.inc.php
require 'util.php';
require 'io.php';
require 'commands.php';


/**
 * SendError
 *
 * @param	integer	$number		Number
 * @param	string	$text		Text
 * @return 	void
 */
function SendError($number, $text)
{
	SendUploadResults($number, '', '', $text);
}


// Check if this uploader has been enabled.
if (!$Config['Enabled']) {
	SendUploadResults('1', '', '', 'This file uploader is disabled. Please check the "filemanagerdol/connectors/php/config.php" file');
}

$sCommand = 'QuickUpload';

// The file type (from the QueryString, by default 'File').
$sType = isset($_GET['Type']) ? $_GET['Type'] : 'File';

$sCurrentFolder = "/";

// Is enabled the upload?
if (!IsAllowedCommand($sCommand)) {
	SendUploadResults('1', '', '', 'The ""'.$sCommand.'"" command isn\'t allowed');
}

// Check if it is an allowed type.
if (!IsAllowedType($sType)) {
	SendUploadResults(1, '', '', 'Invalid type specified');
}



// @CHANGE
//FileUpload( $sType, $sCurrentFolder, $sCommand )

// Get the CKEditor Callback
$CKEcallback = $_GET['CKEditorFuncNum'];

//modify the next line adding in the new param
FileUpload($sType, $sCurrentFolder, $sCommand, $CKEcallback);
