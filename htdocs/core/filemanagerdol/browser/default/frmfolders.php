<?php
/* Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * Source modified from part of fckeditor (http://www.fckeditor.net)
 * retrieved as GPL v2 or later
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
 * This page shows the list of folders available in the parent folder
 * of the current folder.
-->
<?php
//$arrayofjs=array('js/common.js');
//echo top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers
?>
<html>
	<head>
		<title>Folders</title>
		<link href="browser.css" type="text/css" rel="stylesheet">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript">

var sActiveFolder ;

var bIsLoaded = false ;
var iIntervalId ;

var oListManager = new Object();

oListManager.Init = function()
{
	this.Table = document.getElementById('tableFiles');
	this.UpRow = document.getElementById('trUp');

	this.TableRows = new Object();
}

oListManager.Clear = function()
{
	// Remove all other rows available.
	while ( this.Table.rows.length > 1 )
		this.Table.deleteRow(1);

	// Reset the TableRows collection.
	this.TableRows = new Object();
}

oListManager.AddItem = function( folderName, folderPath )
{
	// Create the new row.
	var oRow = this.Table.insertRow(-1);
	oRow.className = 'FolderListFolder' ;

	// Build the link to view the folder.
	var sLink = '<a href="#" onclick="OpenFolder(\'' + folderPath + '\');return false;">' ;

	// Add the folder icon cell.
	var oCell = oRow.insertCell(-1);
	oCell.width = 16 ;
	oCell.innerHTML = sLink + '<img alt="" src="spacer.gif" width="16" height="16" border="0"><\/a>' ;

	// Add the folder name cell.
	oCell = oRow.insertCell(-1);
	oCell.noWrap = true ;
	oCell.innerHTML = '&nbsp;' + sLink + folderName + '<\/a>' ;

	this.TableRows[ folderPath ] = oRow ;
}

oListManager.ShowUpFolder = function( upFolderPath )
{
	this.UpRow.style.display = ( upFolderPath != null ? '' : 'none' );

	if ( upFolderPath != null )
	{
		document.getElementById('linkUpIcon').onclick = document.getElementById('linkUp').onclick = function()
		{
			LoadFolders( upFolderPath );
			return false ;
		}
	}
}

function CheckLoaded()
{
	if ( window.top.IsLoadedActualFolder
		&& window.top.IsLoadedCreateFolder
		&& window.top.IsLoadedUpload
		&& window.top.IsLoadedResourcesList )
	{
		window.clearInterval( iIntervalId );
		bIsLoaded = true ;
		OpenFolder( sActiveFolder );
	}
}

function OpenFolder( folderPath )
{
	sActiveFolder = folderPath ;

	if ( ! bIsLoaded )
	{
		if ( ! iIntervalId )
			iIntervalId = window.setInterval( CheckLoaded, 100 );
		return ;
	}

	// Change the style for the select row (to show the opened folder).
	for ( var sFolderPath in oListManager.TableRows )
	{
		oListManager.TableRows[ sFolderPath ].className =
			( sFolderPath == folderPath ? 'FolderListCurrentFolder' : 'FolderListFolder' );
	}

	// Set the current folder in all frames.
	window.parent.frames['frmActualFolder'].SetCurrentFolder( oConnector.ResourceType, folderPath );
	window.parent.frames['frmCreateFolder'].SetCurrentFolder( oConnector.ResourceType, folderPath );
	window.parent.frames['frmUpload'].SetCurrentFolder( oConnector.ResourceType, folderPath );

	// Load the resources list for this folder.
	window.parent.frames['frmResourcesList'].LoadResources( oConnector.ResourceType, folderPath );
}

function LoadFolders( folderPath )
{
	// Clear the folders list.
	oListManager.Clear();

	// Get the parent folder path.
	var sParentFolderPath ;
	if ( folderPath != '/' )
		sParentFolderPath = folderPath.substring( 0, folderPath.lastIndexOf( '/', folderPath.length - 2 ) + 1 );

	// Show/Hide the Up Folder.
	oListManager.ShowUpFolder( sParentFolderPath );

	if ( folderPath != '/' )
	{
		sActiveFolder = folderPath ;
		oConnector.CurrentFolder = sParentFolderPath ;
		oConnector.SendCommand( 'GetFolders', null, GetFoldersCallBack );
	}
	else
		OpenFolder( '/' );
}

function GetFoldersCallBack( fckXml )
{
	if ( oConnector.CheckError( fckXml ) != 0 )
		return ;

	// Get the current folder path.
	var oNode = fckXml.SelectSingleNode( 'Connector/CurrentFolder' );
	var sCurrentFolderPath = oNode.attributes.getNamedItem('path').value ;

	var oNodes = fckXml.SelectNodes( 'Connector/Folders/Folder' );

	for ( var i = 0 ; i < oNodes.length ; i++ )
	{
		var sFolderName = oNodes[i].attributes.getNamedItem('name').value ;
		oListManager.AddItem( sFolderName, sCurrentFolderPath + sFolderName + '/' );
	}

	OpenFolder( sActiveFolder );
}

function SetResourceType( type )
{
	oConnector.ResourceType = type ;
	LoadFolders( '/' );
}

window.onload = function()
{
	oListManager.Init();
	LoadFolders( '/' );
}
		</script>
	</head>
	<body class="FileArea">
		<table id="tableFiles" cellSpacing="0" cellPadding="0" width="100%" border="0">
			<tr id="trUp" style="DISPLAY: none">
				<td width="16"><a id="linkUpIcon" href="#"><img alt="" src="images/FolderUp.gif" width="16" height="16" border="0"></a></td>
				<td class="nowrap" width="100%">&nbsp;<a id="linkUp" href="#">..</a></td>
			</tr>
		</table>
	</body>
</html>
