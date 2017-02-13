<?php
/* Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * Source modified from part of fckeditor (http://www.fckeditor.net)
 * retreived as GPL v2 or later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

define('NOTOKENRENEWAL',1); // Disables token renewal

require '../../../../main.inc.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!--
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * Page used to upload new files in the current folder.
-->
<html>
	<head>
		<title>File Upload</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="browser.css" type="text/css" rel="stylesheet" >
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript">

function SetCurrentFolder( resourceType, folderPath )
{
	var sUrl = oConnector.ConnectorUrl + 'Command=FileUpload' ;
	sUrl += '&Type=' + resourceType ;
	sUrl += '&CurrentFolder=' + encodeURIComponent( folderPath );

	document.getElementById('frmUpload').action = sUrl ;
}

function OnSubmit()
{
	if ( document.getElementById('NewFile').value.length == 0 )
	{
		alert( 'Please select a file from your computer' );
		return false ;
	}

	// Set the interface elements.
	document.getElementById('eUploadMessage').innerHTML = 'Upload a new file in this folder (Upload in progress, please wait...)' ;
	document.getElementById('btnUpload').disabled = true ;

	return true ;
}

function OnUploadCompleted( errorNumber, data )
{
	// Reset the Upload Worker Frame.
	window.parent.frames['frmUploadWorker'].location = 'javascript:void(0)' ;

	// Reset the upload form (On IE we must do a little trick to avoid problems).
	if ( document.all )
		document.getElementById('NewFile').outerHTML = '<input id="NewFile" name="NewFile" style="WIDTH: 100%" type="file">' ;
	else
		document.getElementById('frmUpload').reset();

	// Reset the interface elements.
	document.getElementById('eUploadMessage').innerHTML = 'Upload a new file in this folder' ;
	document.getElementById('btnUpload').disabled = false ;

	switch ( errorNumber )
	{
		case 0:
			window.parent.frames['frmResourcesList'].Refresh();
			break;
		case 1:	// Custom error.
			alert( data );
			break;
		case 201:
			window.parent.frames['frmResourcesList'].Refresh();
			alert( 'A file with the same name is already available. The uploaded file has been renamed to "' + data + '"' );
			break;
		case 202:
			alert( 'Invalid file' );
			break;
		default:
			alert( 'Error on file upload. Error number: ' + errorNumber );
			break;
	}
}

window.onload = function()
{
	window.top.IsLoadedUpload = true ;
}
		</script>
	</head>
	<body>
		<form id="frmUpload" action="" target="frmUploadWorker" method="post" enctype="multipart/form-data" onsubmit="return OnSubmit();">
			<table class="fullHeight" cellspacing="0" cellpadding="0" width="100%" border="0">
				<tr>
					<td class="nowrap">
						<span id="eUploadMessage">Upload a new file in this folder</span><br>
						<table cellspacing="0" cellpadding="0" width="100%" border="0">
							<tr>
								<td width="100%"><input id="NewFile" name="NewFile" style="WIDTH: 100%" type="file"></td>
								<td class="nowrap">&nbsp;<input id="btnUpload" type="submit" value="Upload"></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</form>
	</body>
</html>
