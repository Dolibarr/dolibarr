<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 * Copyright (C) 2024		MDW	<mdeweerd@users.noreply.github.com>
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
 * This is the File Manager Connector for PHP. It returns a XML file used by browser.php
 */

ob_start();

require 'config.inc.php';	// This include the main.inc.php
require 'connector.lib.php';

if (!$Config['Enabled']) {
	SendError(1, 'This connector is disabled. Please check the "editor/filemanager/connectors/php/config.inc.php" file');
}

DoResponse();

/**
 * DoResponse
 *
 * @return void
 */
function DoResponse()
{
	if (!isset($_GET['Command']) || !isset($_GET['Type']) || !isset($_GET['CurrentFolder'])) {
		return;
	}

	// Get the main request information.
	$sCommand = GETPOST('Command');
	$sResourceType = GETPOST('Type');
	$sCurrentFolder = GetCurrentFolder();

	// Check if it is an allowed command
	if (!IsAllowedCommand($sCommand)) {
		SendError(1, 'The "'.$sCommand.'" command isn\'t allowed');
	}
	// Check if it is an allowed type.
	if (!IsAllowedType($sResourceType)) {
		SendError(1, 'Invalid type specified');
	}

	// File Upload doesn't have to Return XML, so it must be intercepted before anything.
	if ($sCommand == 'FileUpload') {
		FileUpload($sResourceType, $sCurrentFolder, $sCommand);
		// @phan-suppress-next-line PhanPluginUnreachableCode
		return;  // FileUpload exits @phpstan-ignore-line
	}

	CreateXmlHeader($sCommand, $sResourceType, $sCurrentFolder);

	// Execute the required command.
	switch ($sCommand) {
		case 'GetFolders':
			GetFolders($sResourceType, $sCurrentFolder);
			break;
		case 'GetFoldersAndFiles':
			GetFoldersAndFiles($sResourceType, $sCurrentFolder);
			break;
		case 'CreateFolder':
			CreateFolder($sResourceType, $sCurrentFolder);
			break;
	}

	CreateXmlFooter();

	exit;
}
