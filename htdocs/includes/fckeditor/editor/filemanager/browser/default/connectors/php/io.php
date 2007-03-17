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
 * File Name: io.php
 * 	This is the File Manager Connector for ASP.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (www.fckeditor.net)
 */

function GetUrlFromPath( $resourceType, $folderPath )
{
	if ( $resourceType == '' )
		return RemoveFromEnd( $GLOBALS["UserFilesPath"], '/' ) . $folderPath ;
	else
		return $GLOBALS["UserFilesPath"] . strtolower( $resourceType ) . $folderPath ;
}

function RemoveExtension( $fileName )
{
	return substr( $fileName, 0, strrpos( $fileName, '.' ) ) ;
}

function ServerMapFolder( $resourceType, $folderPath )
{
	// Get the resource type directory.
	$sResourceTypePath = $GLOBALS["UserFilesDirectory"] . strtolower( $resourceType ) . '/' ;

	// Ensure that the directory exists.
	CreateServerFolder( $sResourceTypePath ) ;

	// Return the resource type directory combined with the required path.
	return $sResourceTypePath . RemoveFromStart( $folderPath, '/' ) ;
}

function GetParentFolder( $folderPath )
{
	$sPattern = "-[/\\\\][^/\\\\]+[/\\\\]?$-" ;
	return preg_replace( $sPattern, '', $folderPath ) ;
}

function CreateServerFolder( $folderPath )
{
	$sParent = GetParentFolder( $folderPath ) ;

	// Check if the parent exists, or create it.
	if ( !file_exists( $sParent ) )
	{
		$sErrorMsg = CreateServerFolder( $sParent ) ;
		if ( $sErrorMsg != '' )
			return $sErrorMsg ;
	}

	if ( !file_exists( $folderPath ) )
	{
		// Turn off all error reporting.
		error_reporting( 0 ) ;
		// Enable error tracking to catch the error.
		ini_set( 'track_errors', '1' ) ;

		// To create the folder with 0777 permissions, we need to set umask to zero.
		$oldumask = umask(0) ;
		mkdir( $folderPath, 0777 ) ;
		umask( $oldumask ) ;

		$sErrorMsg = $php_errormsg ;

		// Restore the configurations.
		ini_restore( 'track_errors' ) ;
		ini_restore( 'error_reporting' ) ;

		return $sErrorMsg ;
	}
	else
		return '' ;
}

function GetRootPath()
{
	$sRealPath = realpath( './' ) ;

	$sSelfPath = $_SERVER['PHP_SELF'] ;
	$sSelfPath = substr( $sSelfPath, 0, strrpos( $sSelfPath, '/' ) ) ;

	return substr( $sRealPath, 0, strlen( $sRealPath ) - strlen( $sSelfPath ) ) ;
}
?>