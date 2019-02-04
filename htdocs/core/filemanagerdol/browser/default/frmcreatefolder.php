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

define('NOTOKENRENEWAL', 1); // Disables token renewal

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
 * Page used to create new folders in the current folder.
-->
<html>
	<head>
		<title>Create Folder</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="browser.css" type="text/css" rel="stylesheet">
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript">

function SetCurrentFolder( resourceType, folderPath )
{
	oConnector.ResourceType = resourceType ;
	oConnector.CurrentFolder = folderPath ;
}

function CreateFolder()
{
	var sFolderName ;

	while ( true )
	{
		sFolderName = prompt( 'Type the name of the new folder:', '' );

		if ( sFolderName == null )
			return ;
		else if ( sFolderName.length == 0 )
			alert( 'Please type the folder name' );
		else
			break ;
	}

	oConnector.SendCommand( 'CreateFolder', 'NewFolderName=' + encodeURIComponent( sFolderName) , CreateFolderCallBack );
}

function CreateFolderCallBack( fckXml )
{
	if ( oConnector.CheckError( fckXml ) == 0 )
		window.parent.frames['frmResourcesList'].Refresh();

	/*
	// Get the current folder path.
	var oNode = fckXml.SelectSingleNode( 'Connector/Error' );
	var iErrorNumber = parseInt( oNode.attributes.getNamedItem('number').value );

	switch ( iErrorNumber )
	{
		case 0:
			window.parent.frames['frmResourcesList'].Refresh();
			break;
		case 101:
			alert( 'Folder already exists' );
			break;
		case 102:
			alert( 'Invalid folder name' );
			break;
		case 103:
			alert( 'You have no permissions to create the folder' );
			break;
		case 110:
			alert( 'Unknown error creating folder' );
			break;
		default:
			alert( 'Error creating folder. Error number: ' + iErrorNumber );
			break;
	}
	*/
}

window.onload = function()
{
	window.top.IsLoadedCreateFolder = true ;
}
		</script>
	</head>
	<body>
		<table class="fullHeight" cellSpacing="0" cellPadding="0" width="100%" border="0">
			<tr>
				<td>
					<button type="button" style="WIDTH: 100%" onclick="CreateFolder();">
						<table cellSpacing="0" cellPadding="0" border="0">
							<tr>
								<td><?php echo img_picto_common('', 'treemenu/folder.gif', 'width="16" height="16"'); ?></td>
								<td>&nbsp;</td>
								<td class="nowrap">Create New Folder</td>
							</tr>
						</table>
					</button>
				</td>
			</tr>
		</table>
	</body>
</html>
