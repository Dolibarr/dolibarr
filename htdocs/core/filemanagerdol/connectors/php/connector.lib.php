<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * These functions are used by the connector.php script.
 */

/**
 * SetXmlHeaders
 *
 * @return	void
 */
function SetXmlHeaders()
{
	ob_end_clean();

	// Prevent the browser from caching the result.
	// Date in the past
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	// always modified
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	// HTTP/1.1
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
	// HTTP/1.0
	header('Pragma: no-cache');

	// Set the response format.
	header('Content-Type: text/xml; charset=utf-8');
}

/**
 * CreateXmlHeader
 *
 * @param string	$command		Command
 * @param string	$resourceType	Resource type
 * @param string	$currentFolder	Current folder
 * @return void
 */
function CreateXmlHeader($command, $resourceType, $currentFolder)
{
	SetXmlHeaders();

	// Create the XML document header.
	echo '<?xml version="1.0" encoding="utf-8" ?>';

	// Create the main "Connector" node.
	echo '<Connector command="'.$command.'" resourceType="'.$resourceType.'">';

	// Add the current folder node.
	echo '<CurrentFolder path="'.ConvertToXmlAttribute($currentFolder).'" url="'.ConvertToXmlAttribute(GetUrlFromPath($resourceType, $currentFolder, $command)).'" />';

	$GLOBALS['HeaderSent'] = true;
}

/**
 * CreateXmlFooter
 *
 * @return void
 */
function CreateXmlFooter()
{
	echo '</Connector>';
}

/**
 * SendError
 *
 * @param 	integer $number		Number
 * @param 	string 	$text		Text
 * @return	void
 */
function SendError($number, $text)
{
	if ($_GET['Command'] == 'FileUpload') {
		SendUploadResults((string) $number, "", "", $text);
	}

	if (isset($GLOBALS['HeaderSent']) && $GLOBALS['HeaderSent']) {
		SendErrorNode($number, $text);
		CreateXmlFooter();
	} else {
		SetXmlHeaders();

		dol_syslog('Error: '.$number.' '.$text, LOG_ERR);

		// Create the XML document header
		echo '<?xml version="1.0" encoding="utf-8" ?>';

		echo '<Connector>';

		SendErrorNode($number, $text);

		echo '</Connector>';
	}
	exit;
}

/**
 * SendErrorNode
 *
 * @param 	integer $number		Number
 * @param	string	$text		Text of error
 * @return 	string				Error node
 */
function SendErrorNode($number, $text)
{
	if ($text) {
		echo '<Error number="'.$number.'" text="'.htmlspecialchars($text).'" />';
	} else {
		echo '<Error number="'.$number.'" />';
	}
	return '';
}



/**
 * GetFolders
 *
 * @param	string	$resourceType		Resource type
 * @param 	string 	$currentFolder		Current folder
 * @return 	void
 */
function GetFolders($resourceType, $currentFolder)
{
	// Map the virtual path to the local server path.
	$sServerDir = ServerMapFolder($resourceType, $currentFolder, 'GetFolders');

	// Array that will hold the folders names.
	$aFolders = array();

	$oCurrentFolder = @opendir($sServerDir);

	if ($oCurrentFolder !== false) {
		while ($sFile = readdir($oCurrentFolder)) {
			if ($sFile != '.' && $sFile != '..' && is_dir($sServerDir.$sFile)) {
				$aFolders[] = '<Folder name="'.ConvertToXmlAttribute($sFile).'" />';
			}
		}
		closedir($oCurrentFolder);
	}

	// Open the "Folders" node.
	echo "<Folders>";

	natcasesort($aFolders);
	foreach ($aFolders as $sFolder) {
		echo $sFolder;
	}

	// Close the "Folders" node.
	echo "</Folders>";
}

/**
 * GetFoldersAndFiles
 *
 * @param	string	$resourceType	Resource type
 * @param	string	$currentFolder	Current folder
 * @return void
 */
function GetFoldersAndFiles($resourceType, $currentFolder)
{
	// Map the virtual path to the local server path.
	$sServerDir = ServerMapFolder($resourceType, $currentFolder, 'GetFoldersAndFiles');

	// Arrays that will hold the folders and files names.
	$aFolders = array();
	$aFiles = array();

	$oCurrentFolder = @opendir($sServerDir);

	if ($oCurrentFolder !== false) {
		while ($sFile = readdir($oCurrentFolder)) {
			if ($sFile != '.' && $sFile != '..') {
				if (is_dir($sServerDir.$sFile)) {
					$aFolders[] = '<Folder name="'.ConvertToXmlAttribute($sFile).'" />';
				} else {
					$iFileSize = @filesize($sServerDir.$sFile);
					if (!$iFileSize) {
						$iFileSize = 0;
					}
					if ($iFileSize > 0) {
						$iFileSize = round($iFileSize / 1024);
						if ($iFileSize < 1) {
							$iFileSize = 1;
						}
					}

					$aFiles[] = '<File name="'.ConvertToXmlAttribute($sFile).'" size="'.$iFileSize.'" />';
				}
			}
		}
		closedir($oCurrentFolder);
	}

	// Send the folders
	natcasesort($aFolders);
	echo '<Folders>';

	foreach ($aFolders as $sFolder) {
		echo $sFolder;
	}

	echo '</Folders>';

	// Send the files
	natcasesort($aFiles);
	echo '<Files>';

	foreach ($aFiles as $sFiles) {
		echo $sFiles;
	}

	echo '</Files>';
}

/**
 * Create folder
 *
 * @param   string $resourceType    Resource type
 * @param   string $currentFolder   Current folder
 * @return void
 */
function CreateFolder($resourceType, $currentFolder)
{
	$sErrorNumber = '0';
	$sErrorMsg = '';

	if (isset($_GET['NewFolderName'])) {
		$sNewFolderName = GETPOST('NewFolderName');
		$sNewFolderName = SanitizeFolderName($sNewFolderName);

		if (strpos($sNewFolderName, '..') !== false) {
			$sErrorNumber = '102'; // Invalid folder name.
		} else {
			// Map the virtual path to the local server path of the current folder.
			$sServerDir = ServerMapFolder($resourceType, $currentFolder, 'CreateFolder');

			if (is_writable($sServerDir)) {
				$sServerDir .= $sNewFolderName;

				$sErrorMsg = CreateServerFolder($sServerDir);

				switch ($sErrorMsg) {
					case '':
						$sErrorNumber = '0';
						break;
					case 'Invalid argument':
					case 'No such file or directory':
						$sErrorNumber = '102'; // Path too long.
						break;
					default:
						$sErrorNumber = '110';
						break;
				}
			} else {
				$sErrorNumber = '103';
			}
		}
	} else {
		$sErrorNumber = '102';
	}

	// Create the "Error" node.
	echo '<Error number="'.$sErrorNumber.'" />';
}

/**
 * FileUpload
 *
 * @param	string	$resourceType	Resource type
 * @param 	string 	$currentFolder	Current folder
 * @param	string	$sCommand		Command
 * @param	string	$CKEcallback	Callback
 * @return	null
 */
function FileUpload($resourceType, $currentFolder, $sCommand, $CKEcallback = '')
{
	global $user;

	if (!isset($_FILES)) {
		global $_FILES;
	}
	$sErrorNumber = '0';
	$sFileName = '';

	if (isset($_FILES['NewFile']) && !is_null($_FILES['NewFile']['tmp_name']) || (isset($_FILES['upload']) && !is_null($_FILES['upload']['tmp_name']))) {
		global $Config;

		$oFile = isset($_FILES['NewFile']) ? $_FILES['NewFile'] : $_FILES['upload'];

		// $resourceType should be 'Image';
		$detectHtml = 0;

		// Map the virtual path to the local server path.
		$sServerDir = ServerMapFolder($resourceType, $currentFolder, $sCommand);

		// Get the uploaded file name.
		$sFileName = $oFile['name'];

		//$sFileName = SanitizeFileName($sFileName);
		$sFileName = dol_sanitizeFileName($sFileName);

		$sOriginalFileName = $sFileName;

		// Get the extension.
		$sExtension = substr($sFileName, (strrpos($sFileName, '.') + 1));
		$sExtension = strtolower($sExtension);

		// Check permission
		$permissiontouploadmediaisok = 1;
		if (!empty($user->socid)) {
			$permissiontouploadmediaisok = 0;
		}
		/*if (!$user->hasRight('website', 'write') && !$user->hasRight('mailing', 'write')) {
			$permissiontouploadmediaisok = 0;
		}*/
		if (!$permissiontouploadmediaisok) {
			dol_syslog("connector.lib.php Try to upload a file with no permission");
			$sErrorNumber = '202';
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		//var_dump($sFileName); var_dump(image_format_supported($sFileName));exit;
		$imgsupported = image_format_supported($sFileName);
		$isImageValid = ($imgsupported >= 0);
		if (!$isImageValid) {
			$sErrorNumber = '202';
		}


		// Check if it is an allowed extension.
		if (!$sErrorNumber) {
			if (IsAllowedExt($sExtension, $resourceType)) {
				$iCounter = 0;

				while (true) {
					$sFilePath = $sServerDir.$sFileName;

					if (is_file($sFilePath)) {
						$iCounter++;
						$sFileName = RemoveExtension($sOriginalFileName).'('.$iCounter.').'.$sExtension;
						$sErrorNumber = '201';
					} else {
						include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
						dol_move_uploaded_file($oFile['tmp_name'], $sFilePath, 0, 0);

						if (is_file($sFilePath)) {
							if (isset($Config['ChmodOnUpload']) && !$Config['ChmodOnUpload']) {
								break;
							}

							$permissions = '0777';
							if (isset($Config['ChmodOnUpload']) && $Config['ChmodOnUpload']) {
								$permissions = (string) $Config['ChmodOnUpload'];
							}
							$permissionsdec = octdec($permissions);
							dol_syslog("connector.lib.php permission = ".$permissions." ".$permissionsdec." ".decoct($permissionsdec));
							$oldumask = umask(0);
							chmod($sFilePath, $permissionsdec);
							umask($oldumask);
						}

						break;
					}
				}

				if (file_exists($sFilePath)) {
					//previous checks failed, try once again
					if (isset($isImageValid) && $imgsupported === -1 && IsImageValid($sFilePath, $sExtension) === false) {
						dol_syslog("connector.lib.php IsImageValid is ko");
						@unlink($sFilePath);
						$sErrorNumber = '202';
					} else {
						$detectHtml = DetectHtml($sFilePath);
						if ($detectHtml === true || $detectHtml == -1) {
							// Note that is is a simple test and not reliable. Security does not rely on this.
							dol_syslog("connector.lib.php DetectHtml is ko");
							@unlink($sFilePath);
							$sErrorNumber = '202';
						}
					}
				}
			} else {
				$sErrorNumber = '202';
			}
		}
	} else {
		$sErrorNumber = '203';
	}


	$sFileUrl = CombinePaths(GetResourceTypePath($resourceType, $sCommand), $currentFolder);
	$sFileUrl = CombinePaths($sFileUrl, $sFileName);


	// @CHANGE
	//SendUploadResults( $sErrorNumber, $sFileUrl, $sFileName );
	if ($CKEcallback == '') {
		// this line already exists so wrap the if block around it
		SendUploadResults($sErrorNumber, $sFileUrl, $sFileName);
	} else {
		//issue the CKEditor Callback
		SendCKEditorResults(
			$CKEcallback,
			$sFileUrl,
			($sErrorNumber != 0 ? 'Error '.$sErrorNumber.' upload failed.' : 'Upload Successful')
		);
	}

	exit;
}



/**
 * CombinePaths
 *
 * @param   string $sBasePath     sBasePath
 * @param   string $sFolder       sFolder
 * @return  string                Combined path
 */
function CombinePaths($sBasePath, $sFolder)
{
	return RemoveFromEnd($sBasePath, '/').'/'.RemoveFromStart($sFolder, '/');
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
	global $Config;

	if ($sCommand == "QuickUpload") {
		return $Config['QuickUploadPath'][$resourceType];
	} else {
		return $Config['FileTypesPath'][$resourceType];
	}
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
	global $Config;
	if ($sCommand == "QuickUpload") {
		if (strlen($Config['QuickUploadAbsolutePath'][$resourceType]) > 0) {
			return $Config['QuickUploadAbsolutePath'][$resourceType];
		}

		// Map the "UserFiles" path to a local directory.
		return Server_MapPath($Config['QuickUploadPath'][$resourceType]);
	} else {
		if (strlen($Config['FileTypesAbsolutePath'][$resourceType]) > 0) {
			return $Config['FileTypesAbsolutePath'][$resourceType];
		}

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
	if ($sErrorMsg != '') {
		SendError(1, "Error creating folder \"$sResourceTypePath\" ($sErrorMsg)");
	}

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
	$sPattern = "-[/\\\\][^/\\\\]+[/\\\\]?$-";
	return preg_replace($sPattern, '', $folderPath);
}

/**
 * CreateServerFolder
 *
 * @param 	string	$folderPath		Folder - Folder to create (recursively)
 * @param 	?string	$lastFolder		Internal - Child Folder we are creating, prevents recursion
 * @return	string					''=success, error message otherwise
 */
function CreateServerFolder($folderPath, $lastFolder = null)
{
	global $user;
	global $Config;

	$sParent = GetParentFolder($folderPath);

	// Ensure the folder path has no double-slashes, or mkdir may fail on certain platforms
	while (strpos($folderPath, '//') !== false) {
		$folderPath = str_replace('//', '/', $folderPath);
	}

	$permissiontouploadmediaisok = 1;
	if (!empty($user->socid)) {
		$permissiontouploadmediaisok = 0;
	}
	/*if (!$user->hasRight('website', 'write') && !$user->hasRight('mailing', 'write')) {
	 $permissiontouploadmediaisok = 0;
	 }*/
	if (!$permissiontouploadmediaisok) {
		return 'Bad permissions to create a folder in media directory';
	}

	// Check if the parent exists, or create it.
	if (!empty($sParent) && !file_exists($sParent)) {
		//prevents against infinite loop when we can't create root folder
		if (!is_null($lastFolder) && $lastFolder === $sParent) {
			return "Can't create $folderPath directory";
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$sErrorMsg = CreateServerFolder($sParent, $folderPath);
		if ($sErrorMsg != '') {
			return $sErrorMsg;
		}
	}

	if (!file_exists($folderPath)) {
		// Turn off all error reporting.
		error_reporting(0);

		$php_errormsg = '';
		// Enable error tracking to catch the error.
		ini_set('track_errors', '1');

		if (isset($Config['ChmodOnFolderCreate']) && !$Config['ChmodOnFolderCreate']) {
			mkdir($folderPath);
		} else {
			$permissions = '0777';
			if (isset($Config['ChmodOnFolderCreate']) && $Config['ChmodOnFolderCreate']) {
				$permissions = (string) $Config['ChmodOnFolderCreate'];
			}
			$permissionsdec = octdec($permissions);
			$permissionsdec |= octdec('0111'); // Set x bit required for directories
			dol_syslog("connector.lib.php permission = ".$permissions." ".$permissionsdec." ".decoct($permissionsdec));
			// To create the folder with 0777 permissions, we need to set umask to zero.
			$oldumask = umask(0);
			mkdir($folderPath, $permissionsdec);
			umask($oldumask);
		}

		$sErrorMsg = $php_errormsg;

		// Restore the configurations.
		ini_restore('track_errors');
		ini_restore('error_reporting');

		return $sErrorMsg;
	} else {
		return '';
	}
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

	$sSelfPath = $_SERVER['PHP_SELF'];
	$sSelfPath = substr($sSelfPath, 0, strrpos($sSelfPath, '/'));

	$sSelfPath = str_replace('/', DIRECTORY_SEPARATOR, $sSelfPath);

	$position = strpos($sRealPath, $sSelfPath);

	// This can check only that this script isn't run from a virtual dir
	// But it avoids the problems that arise if it isn't checked
	if ($position === false || $position != strlen($sRealPath) - strlen($sSelfPath)) {
		SendError(1, 'Sorry, can\'t map "UserFilesPath" to a physical path. You must set the "UserFilesAbsolutePath" value in "editor/filemanager/connectors/php/config.inc.php".');
	}

	return substr($sRealPath, 0, $position);
}

/**
 *  Emulate the asp Server.mapPath function.
 *  @param	string		$path		given an url path return the physical directory that it corresponds to
 *  @return	string					Path
 */
function Server_MapPath($path)
{
	// This function is available only for Apache
	if (function_exists('apache_lookup_uri')) {
		$info = apache_lookup_uri($path);
		return $info->filename.$info->path_info;
	}

	// This isn't correct but for the moment there's no other solution
	// If this script is under a virtual directory or symlink it will detect the problem and stop
	return GetRootPath().$path;
}

/**
 * Is Allowed Extension
 *
 * @param   string $sExtension      File extension
 * @param   string $resourceType    resource type
 * @return  boolean                 true or false
 */
function IsAllowedExt($sExtension, $resourceType)
{
	global $Config;
	// Get the allowed and denied extensions arrays.
	$arAllowed = $Config['AllowedExtensions'][$resourceType];
	$arDenied = $Config['DeniedExtensions'][$resourceType];

	if (count($arAllowed) > 0 && !in_array($sExtension, $arAllowed)) {
		return false;
	}

	if (count($arDenied) > 0 && in_array($sExtension, $arDenied)) {
		return false;
	}

	return true;
}

/**
 * Is Allowed Type
 *
 * @param   string $resourceType    resource type
 * @return  boolean                 true or false
 */
function IsAllowedType($resourceType)
{
	global $Config;
	if (!in_array($resourceType, $Config['ConfigAllowedTypes'])) {
		return false;
	}

	return true;
}

/**
 * IsAllowedCommand
 *
 * @param   string		$sCommand		Command
 * @return  boolean						True or false
 */
function IsAllowedCommand($sCommand)
{
	global $Config;

	if (!in_array($sCommand, $Config['ConfigAllowedCommands'])) {
		return false;
	}

	return true;
}

/**
 * GetCurrentFolder
 *
 * @return	string		current folder
 */
function GetCurrentFolder()
{
	$sCurrentFolder = isset($_GET['CurrentFolder']) ? GETPOST('CurrentFolder', '', 1) : '/';

	// Check the current folder syntax (must begin and start with a slash).
	if (!preg_match('|/$|', $sCurrentFolder)) {
		$sCurrentFolder .= '/';
	}
	if (strpos($sCurrentFolder, '/') !== 0) {
		$sCurrentFolder = '/'.$sCurrentFolder;
	}

	// Ensure the folder path has no double-slashes
	while (strpos($sCurrentFolder, '//') !== false) {
		$sCurrentFolder = str_replace('//', '/', $sCurrentFolder);
	}

	// Check for invalid folder paths (..)
	if (strpos($sCurrentFolder, '..') || strpos($sCurrentFolder, "\\")) {
		SendError(102, '');
	}

	if (preg_match(",(/\.)|[[:cntrl:]]|(//)|(\\\\)|([\:\*\?\"\<\>\|]),", $sCurrentFolder)) {
		SendError(102, '');
	}

	return $sCurrentFolder;
}

/**
 * Do a cleanup of the folder name to avoid possible problems
 *
 * @param	string	$sNewFolderName		Folder
 * @return	string						Folder sanitized
 */
function SanitizeFolderName($sNewFolderName)
{
	$sNewFolderName = stripslashes($sNewFolderName);

	// Remove . \ / | : ? * " < >
	$sNewFolderName = preg_replace('/\\.|\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/', '_', $sNewFolderName);

	return $sNewFolderName;
}

/**
 * Do a cleanup of the file name to avoid possible problems
 *
 * @param	string	$sNewFileName		Folder
 * @return	string						Folder sanitized
 */
function SanitizeFileName($sNewFileName)
{
	global $Config;

	$sNewFileName = stripslashes($sNewFileName);

	// Replace dots in the name with underscores (only one dot can be there... security issue).
	if ($Config['ForceSingleExtension']) {
		$sNewFileName = preg_replace('/\\.(?![^.]*$)/', '_', $sNewFileName);
	}

	// Remove \ / | : ? * " < >
	$sNewFileName = preg_replace('/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/', '_', $sNewFileName);

	return $sNewFileName;
}

/**
 * This is the function that sends the results of the uploading process.
 *
 * @param	string 		$errorNumber	errorNumber
 * @param	string		$fileUrl		fileUrl
 * @param	string		$fileName		fileName
 * @param	string		$customMsg		customMsg
 * @return	void
 */
function SendUploadResults($errorNumber, $fileUrl = '', $fileName = '', $customMsg = '')
{
	// Minified version of the document.domain automatic fix script (#1919).
	// The original script can be found at _dev/domain_fix_template.js
	echo <<<EOF
<script type="text/javascript">
(function(){var d=document.domain;while (true){try{var A=window.parent.document.domain;break;}catch(e) {};d=d.replace(/.*?(?:\.|$)/,'');if (d.length==0) break;try{document.domain=d;}catch (e){break;}}})();
EOF;

	if ($errorNumber && $errorNumber != '201') {
		$fileUrl = "";
		$fileName = "";
	}

	$rpl = array('\\' => '\\\\', '"' => '\\"');
	echo 'console.log('.$errorNumber.');';
	echo 'window.parent.OnUploadCompleted('.$errorNumber.', "'.strtr($fileUrl, $rpl).'", "'.strtr($fileName, $rpl).'", "'.strtr($customMsg, $rpl).'");';
	echo '</script>';
	exit;
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

	$rpl = array('\\' => '\\\\', '"' => '\\"');

	echo 'window.parent.CKEDITOR.tools.callFunction("'.$callback.'","'.strtr($sFileUrl, $rpl).'", "'.strtr($customMsg, $rpl).'");';

	echo '</script>';
}



/**
 * RemoveFromStart
 *
 * @param 	string		$sourceString	Source
 * @param 	string		$charToRemove	Char to remove
 * @return	string		Result
 */
function RemoveFromStart($sourceString, $charToRemove)
{
	$sPattern = '|^'.$charToRemove.'+|';
	return preg_replace($sPattern, '', $sourceString);
}

/**
 * RemoveFromEnd
 *
 * @param 	string		$sourceString	Source
 * @param 	string		$charToRemove	Rhar to remove
 * @return	string		Result
 */
function RemoveFromEnd($sourceString, $charToRemove)
{
	$sPattern = '|'.$charToRemove.'+$|';
	return preg_replace($sPattern, '', $sourceString);
}

/**
 * FindBadUtf8
 *
 * @param 	string $string		String
 * @return	boolean
 */
function FindBadUtf8($string)
{
	$regex = '([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]';
	$regex .= '|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}|(.{1}))';

	$matches = array();
	while (preg_match('/'.$regex.'/S', $string, $matches)) {
		if (isset($matches[2])) {
			return true;
		}
		$string = substr($string, strlen($matches[0]));
	}

	return false;
}

/**
 * ConvertToXmlAttribute
 *
 * @param 	string		$value		Value
 * @return	string
 */
function ConvertToXmlAttribute($value)
{
	if (defined('PHP_OS')) {
		$os = PHP_OS;
	} else {
		$os = php_uname();
	}

	if (strtoupper(substr($os, 0, 3)) === 'WIN' || FindBadUtf8($value)) {
		return (mb_convert_encoding(htmlspecialchars($value), 'UTF-8', 'ISO-8859-1'));
	} else {
		return (htmlspecialchars($value));
	}
}

/**
 * Check whether given extension is in html extensions list
 *
 * @param 	string 		$ext				Extension
 * @param 	array 		$formExtensions		Array of extensions
 * @return 	boolean
 */
function IsHtmlExtension($ext, $formExtensions)
{
	if (!$formExtensions || !is_array($formExtensions)) {
		return false;
	}
	$lcaseHtmlExtensions = array();
	foreach ($formExtensions as $key => $val) {
		$lcaseHtmlExtensions[$key] = strtolower($val);
	}
	return in_array($ext, $lcaseHtmlExtensions);
}

/**
 * Detect HTML in the first KB to prevent against potential security issue with
 * IE/Safari/Opera file type auto detection bug.
 *
 * @param 	string 	$filePath 	Absolute path to file
 * @return 	bool|-1				Returns true if the file contains insecure HTML code at the beginning or false, or -1 if error
 */
function DetectHtml($filePath)
{
	$fp = @fopen($filePath, 'rb');

	//open_basedir restriction, see #1906
	if ($fp === false || !flock($fp, LOCK_SH)) {
		return -1;
	}

	$chunk = fread($fp, 1024);
	flock($fp, LOCK_UN);
	fclose($fp);

	$chunk = strtolower($chunk);

	if (!$chunk) {
		return false;
	}

	$chunk = trim($chunk);

	if (preg_match("/<!DOCTYPE\W*X?HTML/sim", $chunk)) {
		return true;
	}

	$tags = array('<body', '<head', '<html', '<img', '<pre', '<script', '<table', '<title');

	foreach ($tags as $tag) {
		if (false !== strpos($chunk, $tag)) {
			return true;
		}
	}

	//type = javascript
	if (preg_match('!type\s*=\s*[\'"]?\s*(?:\w*/)?(?:ecma|java)!sim', $chunk)) {
		return true;
	}

	//href = javascript
	//src = javascript
	//data = javascript
	if (preg_match('!(?:href|src|data)\s*=\s*[\'"]?\s*(?:ecma|java)script:!sim', $chunk)) {
		return true;
	}

	//url(javascript
	if (preg_match('!url\s*\(\s*[\'"]?\s*(?:ecma|java)script:!sim', $chunk)) {
		return true;
	}

	return false;
}

/**
 * Check file content.
 * Currently this function validates only image files.
 *
 * @param 	string 	$filePath 		Absolute path to file
 * @param 	string 	$extension 		File extension
 * @return 	bool|-1					Returns true if the file is valid, false if the file is invalid, -1 if error.
 */
function IsImageValid($filePath, $extension)
{
	if (!@is_readable($filePath)) {
		return -1;
	}

	$imageCheckExtensions = array(
		'gif',
		'jpeg',
		'jpg',
		'png',
		'swf',
		'psd',
		'bmp',
		'iff',
		'tiff',
		'tif',
		'swc',
		'jpc',
		'jp2',
		'jpx',
		'jb2',
		'xbm',
		'wbmp'
	);

	if (!in_array($extension, $imageCheckExtensions)) {
		return true;
	}

	if (@getimagesize($filePath) === false) {
		return false;
	}

	return true;
}
