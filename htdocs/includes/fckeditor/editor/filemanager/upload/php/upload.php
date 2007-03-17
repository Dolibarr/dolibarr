<?php 
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
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
 * File Name: upload.php
 * 	This is the "File Uploader" for PHP.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
 */

require('config.php') ;
require('util.php') ;

// This is the function that sends the results of the uploading process.
function SendResults( $errorNumber, $fileUrl = '', $fileName = '', $customMsg = '' )
{
	echo '<script type="text/javascript">' ;
	echo 'window.parent.OnUploadCompleted(' . $errorNumber . ',"' . str_replace( '"', '\\"', $fileUrl ) . '","' . str_replace( '"', '\\"', $fileName ) . '", "' . str_replace( '"', '\\"', $customMsg ) . '") ;' ;
	echo '</script>' ;
	exit ;
}

// Check if this uploader has been enabled.
if ( !$Config['Enabled'] )
	SendResults( '1', '', '', 'This file uploader is disabled. Please check the "editor/filemanager/upload/php/config.php" file' ) ;

// Check if the file has been correctly uploaded.
if ( !isset( $_FILES['NewFile'] ) || is_null( $_FILES['NewFile']['tmp_name'] ) || $_FILES['NewFile']['name'] == '' )
	SendResults( '202' ) ;

// Get the posted file.
$oFile = $_FILES['NewFile'] ;

// Get the uploaded file name extension.
$sFileName = $oFile['name'] ;

// Replace dots in the name with underscores (only one dot can be there... security issue).
if ( $Config['ForceSingleExtension'] )
	$sFileName = preg_replace( '/\\.(?![^.]*$)/', '_', $sFileName ) ;

$sOriginalFileName = $sFileName ;

// Get the extension.
$sExtension = substr( $sFileName, ( strrpos($sFileName, '.') + 1 ) ) ;
$sExtension = strtolower( $sExtension ) ;

// The the file type (from the QueryString, by default 'File').
$sType = isset( $_GET['Type'] ) ? $_GET['Type'] : 'File' ;

// Check if it is an allowed type.
if ( !in_array( $sType, array('File','Image','Flash','Media') ) )
    SendResults( 1, '', '', 'Invalid type specified' ) ;

// Get the allowed and denied extensions arrays.
$arAllowed	= $Config['AllowedExtensions'][$sType] ;
$arDenied	= $Config['DeniedExtensions'][$sType] ;

// Check if it is an allowed extension.
if ( ( count($arAllowed) > 0 && !in_array( $sExtension, $arAllowed ) ) || ( count($arDenied) > 0 && in_array( $sExtension, $arDenied ) ) )
	SendResults( '202' ) ;

$sErrorNumber	= '0' ;
$sFileUrl		= '' ;

// Initializes the counter used to rename the file, if another one with the same name already exists.
$iCounter = 0 ;

// Get the target directory.
if ( isset( $Config['UserFilesAbsolutePath'] ) && strlen( $Config['UserFilesAbsolutePath'] ) > 0 )
	$sServerDir = $Config['UserFilesAbsolutePath'] ;
else 
	$sServerDir = GetRootPath() . $Config["UserFilesPath"] ;

if ( $Config['UseFileType'] )
	$sServerDir .= $sType . '/' ;

while ( true )
{
	// Compose the file path.
	$sFilePath = $sServerDir . $sFileName ;

	// If a file with that name already exists.
	if ( is_file( $sFilePath ) )
	{
		$iCounter++ ;
		$sFileName = RemoveExtension( $sOriginalFileName ) . '(' . $iCounter . ').' . $sExtension ;
		$sErrorNumber = '201' ;
	}
	else
	{
		move_uploaded_file( $oFile['tmp_name'], $sFilePath ) ;

		if ( is_file( $sFilePath ) )
		{
			$oldumask = umask(0) ;
			chmod( $sFilePath, 0777 ) ;
			umask( $oldumask ) ;
		}
		
		if ( $Config['UseFileType'] )
			$sFileUrl = $Config["UserFilesPath"] . $sType . '/' . $sFileName ;
		else
			$sFileUrl = $Config["UserFilesPath"] . $sFileName ;

		break ;
	}
}

SendResults( $sErrorNumber, $sFileUrl, $sFileName ) ;
?>