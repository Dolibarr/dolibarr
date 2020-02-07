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
 * This is the File Manager Connector for PHP.
 */

/**
 * CombinePaths
 *
 * @param   string $sBasePath     sBasePath
 * @param   string $sFolder       sFolder
 * @return  string                Combined path
 */
function CombinePaths($sBasePath, $sFolder)
{
	return RemoveFromEnd($sBasePath, '/') . '/' . RemoveFromStart($sFolder, '/');
}
/**
 * GetResourceTypePath
 *
 * @param 	string		$resourceType	Resource type
 * @param 	string		$sCommand		Command
 * @return	string						Config
 */
function GetResourceTypePath($resourceType, $sCommand)
{
	global $Config ;

	if ($sCommand == "QuickUpload")
		return $Config['QuickUploadPath'][$resourceType] ;
	else
		return $Config['FileTypesPath'][$resourceType] ;
}

/**
 * GetResourceTypeDirectory
 *
 * @param string $resourceType	Resource type
 * @param string $sCommand		Command
 * @return string
 */
function GetResourceTypeDirectory($resourceType, $sCommand)
{
	global $Config ;
	if ($sCommand == "QuickUpload")
	{
		if ( strlen($Config['QuickUploadAbsolutePath'][$resourceType]) > 0)
			return $Config['QuickUploadAbsolutePath'][$resourceType] ;

		// Map the "UserFiles" path to a local directory.
		return Server_MapPath($Config['QuickUploadPath'][$resourceType]);
	}
	else
	{
		if ( strlen($Config['FileTypesAbsolutePath'][$resourceType]) > 0)
			return $Config['FileTypesAbsolutePath'][$resourceType] ;

		// Map the "UserFiles" path to a local directory.
		return Server_MapPath($Config['FileTypesPath'][$resourceType]);
	}
}

/**
 * GetUrlFromPath
 *
 * @param	string 	$resourceType	Resource type
 * @param 	string 	$folderPath		Path
 * @param	string	$sCommand		Command
 * @return	string					Full url
 */
function GetUrlFromPath($resourceType, $folderPath, $sCommand)
{
	return CombinePaths(GetResourceTypePath($resourceType, $sCommand), $folderPath);
}

/**
 * RemoveExtension
 *
 * @param 	string		$fileName	Filename
 * @return	string					String without extension
 */
function RemoveExtension($fileName)
{
	return substr($fileName, 0, strrpos($fileName, '.'));
}
/**
 * ServerMapFolder
 *
 * @param 	string	$resourceType	Resource type
 * @param 	string	$folderPath		Folder
 * @param 	string	$sCommand		Command
 * @return	string
 */
function ServerMapFolder($resourceType, $folderPath, $sCommand)
{
	// Get the resource type directory.
	$sResourceTypePath = GetResourceTypeDirectory($resourceType, $sCommand);

	// Ensure that the directory exists.
	$sErrorMsg = CreateServerFolder($sResourceTypePath);
	if ( $sErrorMsg != '' )
		SendError(1, "Error creating folder \"{$sResourceTypePath}\" ({$sErrorMsg})");

	// Return the resource type directory combined with the required path.
	return CombinePaths($sResourceTypePath, $folderPath);
}

/**
 * GetParentFolder
 *
 * @param	string	$folderPath		Folder path
 * @return 	string					Parent folder
 */
function GetParentFolder($folderPath)
{
    $sPattern = "-[/\\\\][^/\\\\]+[/\\\\]?$-" ;
    return preg_replace($sPattern, '', $folderPath);
}

/**
 * CreateServerFolder
 *
 * @param 	string	$folderPath		Folder
 * @param 	string	$lastFolder		Folder
 * @return	string					''=success, error message otherwise
 */
function CreateServerFolder($folderPath, $lastFolder = null)
{
	global $Config ;
	$sParent = GetParentFolder($folderPath);

	// Ensure the folder path has no double-slashes, or mkdir may fail on certain platforms
	while ( strpos($folderPath, '//') !== false )
	{
		$folderPath = str_replace('//', '/', $folderPath);
	}

	// Check if the parent exists, or create it.
	if ( !empty($sParent) && !file_exists($sParent))
	{
		//prevents agains infinite loop when we can't create root folder
		if ( !is_null($lastFolder) && $lastFolder === $sParent) {
			return "Can't create $folderPath directory" ;
		}

		$sErrorMsg = CreateServerFolder($sParent, $folderPath);
		if ( $sErrorMsg != '' )
			return $sErrorMsg ;
	}

	if ( !file_exists($folderPath))
	{
		// Turn off all error reporting.
		error_reporting(0);

		$php_errormsg = '' ;
		// Enable error tracking to catch the error.
		ini_set('track_errors', '1');

		if ( isset($Config['ChmodOnFolderCreate']) && !$Config['ChmodOnFolderCreate'] )
		{
			mkdir($folderPath);
		}
		else
		{
			$permissions = '0777';
			if ( isset($Config['ChmodOnFolderCreate']) && $Config['ChmodOnFolderCreate'])
			{
				$permissions = (string) $Config['ChmodOnFolderCreate'];
			}
			$permissionsdec = octdec($permissions);
			$permissionsdec |= octdec('0111');  // Set x bit required for directories
			dol_syslog("io.php permission = ".$permissions." ".$permissionsdec." ".decoct($permissionsdec));
			// To create the folder with 0777 permissions, we need to set umask to zero.
			$oldumask = umask(0);
			mkdir($folderPath, $permissionsdec);
			umask($oldumask);
		}

		$sErrorMsg = $php_errormsg ;

		// Restore the configurations.
		ini_restore('track_errors');
		ini_restore('error_reporting');

		return $sErrorMsg ;
	}
	else
		return '' ;
}

/**
 * Get Root Path
 *
 * @return  string              real path
 */
function GetRootPath()
{
    if (!isset($_SERVER)) {
        global $_SERVER;
    }
    $sRealPath = realpath('./');
    // #2124 ensure that no slash is at the end
    $sRealPath = rtrim($sRealPath, "\\/");

    $sSelfPath = $_SERVER['PHP_SELF'] ;
    $sSelfPath = substr($sSelfPath, 0, strrpos($sSelfPath, '/'));

    $sSelfPath = str_replace('/', DIRECTORY_SEPARATOR, $sSelfPath);

    $position = strpos($sRealPath, $sSelfPath);

    // This can check only that this script isn't run from a virtual dir
    // But it avoids the problems that arise if it isn't checked
    if ( $position === false || $position <> strlen($sRealPath) - strlen($sSelfPath) )
        SendError(1, 'Sorry, can\'t map "UserFilesPath" to a physical path. You must set the "UserFilesAbsolutePath" value in "editor/filemanager/connectors/php/config.php".');

    return substr($sRealPath, 0, $position);
}

// Emulate the asp Server.mapPath function.
// given an url path return the physical directory that it corresponds to
function Server_MapPath($path)
{
    // This function is available only for Apache
    if (function_exists('apache_lookup_uri')) {
        $info = apache_lookup_uri($path);
        return $info->filename . $info->path_info ;
    }

    // This isn't correct but for the moment there's no other solution
    // If this script is under a virtual directory or symlink it will detect the problem and stop
    return GetRootPath() . $path ;
}

/**
 * Is Allowed Extension
 *
 * @param   string $sExtension      File extension
 * @param   string $resourceType    ressource type
 * @return  boolean                 true or false
 */
function IsAllowedExt($sExtension, $resourceType)
{
	global $Config ;
	// Get the allowed and denied extensions arrays.
	$arAllowed	= $Config['AllowedExtensions'][$resourceType] ;
	$arDenied	= $Config['DeniedExtensions'][$resourceType] ;

	if ( count($arAllowed) > 0 && !in_array($sExtension, $arAllowed))
		return false ;

	if ( count($arDenied) > 0 && in_array($sExtension, $arDenied))
		return false ;

	return true ;
}

/**
 * Is Allowed Type
 *
 * @param   string $resourceType    ressource type
 * @return  boolean                 true or false
 */
function IsAllowedType($resourceType)
{
	global $Config ;
	if ( !in_array($resourceType, $Config['ConfigAllowedTypes']))
		return false ;

	return true ;
}

/**
 * IsAllowedCommand
 *
 * @param   string		$sCommand		Command
 * @return  boolean						True or false
 */
function IsAllowedCommand($sCommand)
{
	global $Config ;

	if (! in_array($sCommand, $Config['ConfigAllowedCommands']))
		return false ;

	return true ;
}

/**
 * GetCurrentFolder
 *
 * @return	string		current folder
 */
function GetCurrentFolder()
{
	if (!isset($_GET)) {
		global $_GET;
	}
	$sCurrentFolder	= isset($_GET['CurrentFolder']) ? GETPOST('CurrentFolder', '', 1) : '/' ;

	// Check the current folder syntax (must begin and start with a slash).
	if (!preg_match('|/$|', $sCurrentFolder))
		$sCurrentFolder .= '/' ;
	if (strpos($sCurrentFolder, '/') !== 0)
		$sCurrentFolder = '/' . $sCurrentFolder ;

	// Ensure the folder path has no double-slashes
	while ( strpos($sCurrentFolder, '//') !== false ) {
		$sCurrentFolder = str_replace('//', '/', $sCurrentFolder);
	}

	// Check for invalid folder paths (..)
	if ( strpos($sCurrentFolder, '..') || strpos($sCurrentFolder, "\\"))
		SendError(102, '');

	if ( preg_match(",(/\.)|[[:cntrl:]]|(//)|(\\\\)|([\:\*\?\"\<\>\|]),", $sCurrentFolder))
		SendError(102, '');

	return $sCurrentFolder ;
}

// Do a cleanup of the folder name to avoid possible problems
function SanitizeFolderName($sNewFolderName)
{
	$sNewFolderName = stripslashes($sNewFolderName);

	// Remove . \ / | : ? * " < >
	$sNewFolderName = preg_replace('/\\.|\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/', '_', $sNewFolderName);

	return $sNewFolderName ;
}

// Do a cleanup of the file name to avoid possible problems
function SanitizeFileName($sNewFileName)
{
	global $Config ;

	$sNewFileName = stripslashes($sNewFileName);

	// Replace dots in the name with underscores (only one dot can be there... security issue).
	if ( $Config['ForceSingleExtension'] )
		$sNewFileName = preg_replace('/\\.(?![^.]*$)/', '_', $sNewFileName);

	// Remove \ / | : ? * " < >
	$sNewFileName = preg_replace('/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/', '_', $sNewFileName);

	return $sNewFileName ;
}

// This is the function that sends the results of the uploading process.
function SendUploadResults($errorNumber, $fileUrl = '', $fileName = '', $customMsg = '')
{
	// Minified version of the document.domain automatic fix script (#1919).
	// The original script can be found at _dev/domain_fix_template.js
	echo <<<EOF
<script type="text/javascript">
(function(){var d=document.domain;while (true){try{var A=window.parent.document.domain;break;}catch(e) {};d=d.replace(/.*?(?:\.|$)/,'');if (d.length==0) break;try{document.domain=d;}catch (e){break;}}})();
EOF;

	if ($errorNumber && $errorNumber != 201) {
		$fileUrl = "";
		$fileName = "";
	}

	$rpl = array( '\\' => '\\\\', '"' => '\\"' );
	echo 'window.parent.OnUploadCompleted(' . $errorNumber . ',"' . strtr($fileUrl, $rpl) . '","' . strtr($fileName, $rpl) . '", "' . strtr($customMsg, $rpl) . '");' ;
	echo '</script>' ;
	exit ;
}


// @CHANGE

// This is the function that sends the results of the uploading process to CKE.
/**
 * SendCKEditorResults
 *
 * @param   string  $callback       callback
 * @param   string  $sFileUrl       sFileUrl
 * @param   string  $customMsg      customMsg
 * @return  void
 */
function SendCKEditorResults($callback, $sFileUrl, $customMsg = '')
{
    echo '<script type="text/javascript">';

    $rpl = array( '\\' => '\\\\', '"' => '\\"' );

    echo 'window.parent.CKEDITOR.tools.callFunction("'. $callback. '","'. strtr($sFileUrl, $rpl). '", "'. strtr($customMsg, $rpl). '");' ;

    echo '</script>';
}
