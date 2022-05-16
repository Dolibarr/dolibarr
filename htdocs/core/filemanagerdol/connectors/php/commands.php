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
	if (!isset($_GET)) {
		global $_GET;
	}
	$sErrorNumber = '0';
	$sErrorMsg = '';

	if (isset($_GET['NewFolderName'])) {
		$sNewFolderName = $_GET['NewFolderName'];
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

// @CHANGE
//function FileUpload( $resourceType, $currentFolder, $sCommand )
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
	if (!isset($_FILES)) {
		global $_FILES;
	}
	$sErrorNumber = '0';
	$sFileName = '';

	if (isset($_FILES['NewFile']) && !is_null($_FILES['NewFile']['tmp_name'])
	   // This is for the QuickUpload tab box
		or (isset($_FILES['upload']) && !is_null($_FILES['upload']['tmp_name']))) {
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

		//var_dump($Config);
		/*
		if (isset($Config['SecureImageUploads'])) {
			if (($isImageValid = IsImageValid($oFile['tmp_name'], $sExtension)) === false) {
				$sErrorNumber = '202';
			}
		}

		if (isset($Config['HtmlExtensions'])) {
			if (!IsHtmlExtension($sExtension, $Config['HtmlExtensions']) &&
				($detectHtml = DetectHtml($oFile['tmp_name'])) === true) {
				$sErrorNumber = '202';
			}
		}
		*/

		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		$isImageValid = image_format_supported($sFileName) > 0 ? true : false;
		if (!$isImageValid) {
			$sErrorNumber = '202';
		}


		// Check if it is an allowed extension.
		if (!$sErrorNumber && IsAllowedExt($sExtension, $resourceType)) {
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
						dol_syslog("commands.php permission = ".$permissions." ".$permissionsdec." ".decoct($permissionsdec));
						$oldumask = umask(0);
						chmod($sFilePath, $permissionsdec);
						umask($oldumask);
					}

					break;
				}
			}

			if (file_exists($sFilePath)) {
				//previous checks failed, try once again
				if (isset($isImageValid) && $isImageValid === -1 && IsImageValid($sFilePath, $sExtension) === false) {
					@unlink($sFilePath);
					$sErrorNumber = '202';
				} elseif (isset($detectHtml) && $detectHtml === -1 && DetectHtml($sFilePath) === true) {
					@unlink($sFilePath);
					$sErrorNumber = '202';
				}
			}
		} else {
			$sErrorNumber = '202';
		}
	} else {
		$sErrorNumber = '202';
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
