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

//define('NOTOKENRENEWAL',1); // Disables token renewal
//require '../../../../main.inc.php';
require '../../connectors/php/config.inc.php'; // This include the define('NOTOKENRENEWAL',1) and the require main.in.php

global $Config;

top_httphead();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
	<head>
		<title><?php echo $langs->trans("MediaBrowser").' - '.$Config['UserFilesAbsolutePathRelative']; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
print '<!-- Includes CSS for Dolibarr theme -->'."\n";
// Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
$themepath = dol_buildpath($conf->css, 1);
$themesubdir = '';
if (!empty($conf->modules_parts['theme'])) {	// This slow down
	foreach ($conf->modules_parts['theme'] as $reldir) {
		if (file_exists(dol_buildpath($reldir.$conf->css, 0))) {
			$themepath = dol_buildpath($reldir.$conf->css, 1);
			$themesubdir = $reldir;
			break;
		}
	}
}

//print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
$themeparam = '';
print '<link rel="stylesheet" type="text/css" href="'.$themepath.$themeparam.'">'."\n";
?>
		<script type="text/javascript" src="js/fckxml.js"></script>
		<script type="text/javascript">
// Automatically detect the correct document.domain (#1919).
(function()
{
	var d = document.domain ;

	while ( true )
	{
		// Test if we can access a parent property.
		try
		{
			var test = window.opener.document.domain ;
			break ;
		}
		catch( e )
		{}

		// Remove a domain part: www.mytest.example.com => mytest.example.com => example.com ...
		d = d.replace( /.*?(?:\.|$)/, '' );

		if ( d.length == 0 )
			break ;		// It was not able to detect the domain.

		try
		{
			document.domain = d ;
		}
		catch (e)
		{
			break ;
		}
	}
})();

function GetUrlParam( paramName )
{
	var oRegex = new RegExp( '[\?&]' + paramName + '=([^&]+)', 'i' );
	var oMatch = oRegex.exec( window.top.location.search );

	if ( oMatch && oMatch.length > 1 )
		return decodeURIComponent( oMatch[1] );
	else
		return '' ;
}

var oConnector = new Object();
oConnector.CurrentFolder	= '/' ;

var sConnUrl = GetUrlParam( 'Connector' );

// Gecko has some problems when using relative URLs (not starting with slash).
if ( sConnUrl.substring(0,1) != '/' && sConnUrl.indexOf( '://' ) < 0 )
	sConnUrl = window.location.href.replace( /browser.php.*$/, '' ) + sConnUrl ;

oConnector.ConnectorUrl = sConnUrl + ( sConnUrl.indexOf('?') != -1 ? '&' : '?' );

var sServerPath = GetUrlParam( 'ServerPath' );
if ( sServerPath.length > 0 )
	oConnector.ConnectorUrl += 'ServerPath=' + encodeURIComponent( sServerPath ) + '&' ;

/* @CHANGE LDR Overwrite value coming from parameters for security purpose */
oConnector.ConnectorUrl = '<?php echo DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php?'; ?>';
console.log('ConnectorUrl='+oConnector.ConnectorUrl);

oConnector.ResourceType		= GetUrlParam( 'Type' );
oConnector.ShowAllTypes		= ( oConnector.ResourceType.length == 0 );

if ( oConnector.ShowAllTypes )
	oConnector.ResourceType = 'File' ;

oConnector.SendCommand = function( command, params, callBackFunction )
{
	var sUrl = this.ConnectorUrl + 'Command=' + command ;
	sUrl += '&Type=' + this.ResourceType ;
	sUrl += '&CurrentFolder=' + encodeURIComponent( this.CurrentFolder );

	if ( params ) sUrl += '&' + params ;

	// Add a random salt to avoid getting a cached version of the command execution
	sUrl += '&uuid=' + new Date().getTime();

	var oXML = new FCKXml();

	if ( callBackFunction )
		oXML.LoadUrl( sUrl, callBackFunction );	// Asynchronous load.
	else
		return oXML.LoadUrl( sUrl );

	return null ;
}

oConnector.CheckError = function( responseXml )
{
	var iErrorNumber = 0 ;
	var oErrorNode = responseXml.SelectSingleNode( 'Connector/Error' );

	if ( oErrorNode )
	{
		iErrorNumber = parseInt( oErrorNode.attributes.getNamedItem('number').value, 10 );

		switch ( iErrorNumber )
		{
			case 0:
				break;
			case 1:	// Custom error. Message placed in the "text" attribute.
				alert( oErrorNode.attributes.getNamedItem('text').value );
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
				alert( 'Error on your request. Error number: ' + iErrorNumber );
				break;
		}
	}
	return iErrorNumber ;
}

var oIcons = new Object();

oIcons.AvailableIconsArray = [
	'ai','avi','bmp','cs','dll','doc','exe','fla','gif','htm','html','jpg','js',
	'mdb','mp3','pdf','png','ppt','rdp','swf','swt','txt','vsd','xls','xml','zip' ] ;

oIcons.AvailableIcons = new Object();

for ( var i = 0 ; i < oIcons.AvailableIconsArray.length ; i++ )
	oIcons.AvailableIcons[ oIcons.AvailableIconsArray[i] ] = true ;

oIcons.GetIcon = function( fileName )
{
	var sExtension = fileName.substr( fileName.lastIndexOf('.') + 1 ).toLowerCase();

	if ( this.AvailableIcons[ sExtension ] == true )
		return sExtension ;
	else
		return 'default.icon' ;
}

function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
{
	if (errorNumber == "1")
		window.frames['frmUpload'].OnUploadCompleted( errorNumber, customMsg );
	else
		window.frames['frmUpload'].OnUploadCompleted( errorNumber, fileName );
}

		</script>
	</head>
	<frameset cols="200,*" framespacing="3" border="1" style="border: 2px solid #CCCCCC;">
		<frame name="frmFolders" src="frmfolders.php" scrolling="auto" frameborder="1">
		<frameset rows="50,*,70" framespacing="0">
			<frame name="frmActualFolder" src="frmactualfolder.php" scrolling="no" frameborder="0">
			<frame name="frmResourcesList" src="frmresourceslist.php" scrolling="auto" frameborder="0">
			<frameset cols="200,*" framespacing="0" border="0">
				<frame name="frmCreateFolder" src="frmcreatefolder.php" scrolling="no" frameborder="0">
				<frame name="frmUpload" src="frmupload.php" scrolling="no" frameborder="0">
				<frame name="frmUploadWorker" src="javascript:void(0)" scrolling="no" frameborder="0">
			</frameset>
		</frameset>
	</frameset>
</html>
