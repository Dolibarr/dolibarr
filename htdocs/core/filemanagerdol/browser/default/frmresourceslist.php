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
 * This page shows all resources available in a folder in the File Browser.
-->
<html>
<head>
	<title>Resources</title>
	<link href="browser.css" type="text/css" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="js/common.js"></script>
	<script type="text/javascript">

var oListManager = new Object();

oListManager.Clear = function()
{
	document.body.innerHTML = '' ;
}

function ProtectPath(path)
{
	path = path.replace( /\\/g, '\\\\');
	path = path.replace( /'/g, '\\\'');
	return path ;
}

oListManager.GetFolderRowHtml = function( folderName, folderPath )
{
	// Build the link to view the folder.
	var sLink = '<a href="#" onclick="OpenFolder(\'' + ProtectPath(folderPath) + '\');return false;">' ;

	return '<tr>' +
			'<td width="16">' +
				sLink +
				'<img alt="" src="images/Folder.gif" width="16" height="16" border="0"><\/a>' +
			'<\/td><td class="nowrap" colspan="2">&nbsp;' +
				sLink +
				folderName +
				'<\/a>' +
		'<\/td><\/tr>' ;
}

// Note: fileUrl must be already "URL encoded"
oListManager.GetFileRowHtml = function( fileName, fileUrl, fileSize )
{
	// Build the link to view the folder.
	var sLink = '<a href="#" onclick="OpenFile(\'' + ProtectPath(fileUrl) + '\');return false;">' ;

	// Get the file icon.
	var sIcon = oIcons.GetIcon( fileName );

	return '<tr>' +
			'<td width="16">' +
				sLink +
				'<img alt="" src="images/icons/' + sIcon + '.gif" width="16" height="16" border="0"><\/a>' +
			'<\/td><td>&nbsp;' +
				sLink +
				fileName +
				'<\/a>' +
			'<\/td><td align="right" class="nowrap">&nbsp;' +
				fileSize +
				' KB' +
		'<\/td><\/tr>' ;
}

function OpenFolder( folderPath )
{
	// Load the resources list for this folder.
	window.parent.frames['frmFolders'].LoadFolders( folderPath );
}

function GetUrlParam( paramName )
{
    var oRegex = new RegExp( '[\?&]' + paramName + '=([^&]+)', 'i' );
    var oMatch = oRegex.exec( window.top.location.search );

    if ( oMatch && oMatch.length > 1 )
        return decodeURIComponent( oMatch[1] );
    else
        return '' ;
}

// Note fileUrl must be already "URL encoded"
function OpenFile( fileUrl )
{
    funcNum = GetUrlParam('CKEditorFuncNum');
    //window.top.opener.CKEDITOR.tools.callFunction(funcNum, encodeURI( fileUrl ).replace( '#', '%23' ));
	window.top.opener.CKEDITOR.tools.callFunction(funcNum, fileUrl.replace( '#', '%23' ));
    
    ///////////////////////////////////
    window.top.close();
    window.top.opener.focus();
}

function LoadResources( resourceType, folderPath )
{
	oListManager.Clear();
	oConnector.ResourceType = resourceType ;
	oConnector.CurrentFolder = folderPath ;
	oConnector.SendCommand( 'GetFoldersAndFiles', null, GetFoldersAndFilesCallBack );
}

function Refresh()
{
	LoadResources( oConnector.ResourceType, oConnector.CurrentFolder );
}

function GetFoldersAndFilesCallBack( fckXml )
{
	if ( oConnector.CheckError( fckXml ) != 0 )
		return ;

	// Get the current folder path.
	var oFolderNode = fckXml.SelectSingleNode( 'Connector/CurrentFolder' );
	if ( oFolderNode == null )
	{
		alert( 'The server didn\'t reply with a proper XML data. Please check your configuration.' );
		return ;
	}
	var sCurrentFolderPath	= oFolderNode.attributes.getNamedItem('path').value ;
	var sCurrentFolderUrl	= oFolderNode.attributes.getNamedItem('url').value ;

//	var dTimer = new Date();

	var oHtml = new StringBuilder( '<table id="tableFiles" cellspacing="1" cellpadding="0" width="100%" border="0">' );

	// Add the Folders.
	var oNodes ;
	oNodes = fckXml.SelectNodes( 'Connector/Folders/Folder' );
	for ( var i = 0 ; i < oNodes.length ; i++ )
	{
		var sFolderName = oNodes[i].attributes.getNamedItem('name').value ;
		oHtml.Append( oListManager.GetFolderRowHtml( sFolderName, sCurrentFolderPath + sFolderName + "/" ) );
	}

	// Add the Files.
	oNodes = fckXml.SelectNodes( 'Connector/Files/File' );
	for ( var j = 0 ; j < oNodes.length ; j++ )
	{
		var oNode = oNodes[j] ;
		var sFileName = oNode.attributes.getNamedItem('name').value ;
		var sFileSize = oNode.attributes.getNamedItem('size').value ;

		// Get the optional "url" attribute. If not available, build the url.
		var oFileUrlAtt = oNodes[j].attributes.getNamedItem('url');
		var sFileUrl = oFileUrlAtt != null ? oFileUrlAtt.value : encodeURI( sCurrentFolderUrl + sFileName ).replace( /#/g, '%23' );

		oHtml.Append( oListManager.GetFileRowHtml( sFileName, sFileUrl, sFileSize ) );
	}

	oHtml.Append( '<\/table>' );

	document.body.innerHTML = oHtml.ToString();

//	window.top.document.title = 'Finished processing in ' + ( ( ( new Date() ) - dTimer ) / 1000 ) + ' seconds' ;

}

window.onload = function()
{
	window.top.IsLoadedResourcesList = true ;
}
	</script>
</head>
<body class="FileArea">
</body>
</html>
