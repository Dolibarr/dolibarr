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
 * Configuration file for the File Manager Connector for PHP.
 */

global $Config;
global $website;

define('NOTOKENRENEWAL', 1); // Disables token renewal

// We must include the main because this page is
// a web page that require security controls and
// is a security hole if anybody can access without
// being an authenticated user.
require_once '../../../../main.inc.php';
$uri = preg_replace('/^http(s?):\/\//i', '', $dolibarr_main_url_root);
$pos = strstr($uri, '/'); // $pos contient alors url sans nom domaine
if ($pos == '/') {
	$pos = ''; // si $pos vaut /, on le met a ''
}
//define('DOL_URL_ROOT', $pos);
$entity = ((!empty($_SESSION['dol_entity']) && $_SESSION['dol_entity'] > 1) ? $_SESSION['dol_entity'] : null);

// SECURITY: You must explicitly enable this "connector". (Set it to "true").
// WARNING: don't just set "$Config['Enabled'] = true ;", you must be sure that only
//		authenticated users can access this file or use some kind of session checking.
$Config['Enabled'] = true;


// Path to user files relative to the document root.
$extEntity = (empty($entity) ? 1 : $entity); // For multicompany with external access

$Config['UserFilesPath'] = DOL_URL_ROOT.'/viewimage.php?modulepart=medias'.(empty($website) ? '' : '_'.$website).'&entity='.$extEntity.'&file=';
$Config['UserFilesAbsolutePathRelative'] = (!empty($entity) ? '/'.$entity : '').(empty($website) ? '/medias/' : ('/website/'.$website));


// Fill the following value it you prefer to specify the absolute path for the
// user files directory. Useful if you are using a virtual directory, symbolic
// link or alias. Examples: 'C:\\MySite\\userfiles\\' or '/root/mysite/userfiles/'.
// Attention: The above 'UserFilesPath' must point to the same directory.
$Config['UserFilesAbsolutePath'] = $dolibarr_main_data_root.$Config['UserFilesAbsolutePathRelative'];

// Due to security issues with Apache modules, it is recommended to leave the
// following setting enabled.
$Config['ForceSingleExtension'] = true;

// Perform additional checks for image files.
// If set to true, validate image size (using getimagesize).
$Config['SecureImageUploads'] = true;

// What the user can do with this connector.
$Config['ConfigAllowedCommands'] = array('QuickUpload', 'FileUpload', 'GetFolders', 'GetFoldersAndFiles', 'CreateFolder');

// Allowed Resource Types.
$Config['ConfigAllowedTypes'] = array('File', 'Image', 'Media');

// For security, HTML is allowed in the first Kb of data for files having the
// following extensions only.
$Config['HtmlExtensions'] = array("html", "htm", "xml", "xsd", "txt", "js");

// After file is uploaded, sometimes it is required to change its permissions
// so that it was possible to access it at the later time.
// If possible, it is recommended to set more restrictive permissions, like 0755.
// Set to 0 to disable this feature.
// Note: not needed on Windows-based servers.
$newmask = '0644';
if (getDolGlobalString('MAIN_UMASK')) {
	$newmask = $conf->global->MAIN_UMASK;
}
$Config['ChmodOnUpload'] = $newmask;

// See comments above.
// Used when creating folders that does not exist.
$newmask = '0755';
$dirmaskdec = octdec($newmask);
if (getDolGlobalString('MAIN_UMASK')) {
	$dirmaskdec = octdec($conf->global->MAIN_UMASK);
}
$dirmaskdec |= octdec('0200'); // Set w bit required to be able to create content for recursive subdirs files
$newmask = decoct($dirmaskdec);

$Config['ChmodOnFolderCreate'] = $newmask;

/*
	Configuration settings for each Resource Type

	- AllowedExtensions: the possible extensions that can be allowed.
		If it is empty then any file type can be uploaded.
	- DeniedExtensions: The extensions that won't be allowed.
		If it is empty then no restrictions are done here.

	For a file to be uploaded it has to fulfill both the AllowedExtensions
	and DeniedExtensions (that's it: not being denied) conditions.

	- FileTypesPath: the virtual folder relative to the document root where
		these resources will be located.
		Attention: It must start and end with a slash: '/'

	- FileTypesAbsolutePath: the physical path to the above folder. It must be
		an absolute path.
		If it's an empty string then it will be autocalculated.
		Useful if you are using a virtual directory, symbolic link or alias.
		Examples: 'C:\\MySite\\userfiles\\' or '/root/mysite/userfiles/'.
		Attention: The above 'FileTypesPath' must point to the same directory.
		Attention: It must end with a slash: '/'

	 - QuickUploadPath: the virtual folder relative to the document root where
		these resources will be uploaded using the Upload tab in the resources
		dialogs.
		Attention: It must start and end with a slash: '/'

	 - QuickUploadAbsolutePath: the physical path to the above folder. It must be
		an absolute path.
		If it's an empty string then it will be autocalculated.
		Useful if you are using a virtual directory, symbolic link or alias.
		Examples: 'C:\\MySite\\userfiles\\' or '/root/mysite/userfiles/'.
		Attention: The above 'QuickUploadPath' must point to the same directory.
		Attention: It must end with a slash: '/'

		 NOTE: by default, QuickUploadPath and QuickUploadAbsolutePath point to
		 "userfiles" directory to maintain backwards compatibility with older versions of FCKeditor.
		 This is fine, but you in some cases you will be not able to browse uploaded files using file browser.
		 Example: if you click on "image button", select "Upload" tab and send image
		 to the server, image will appear in FCKeditor correctly, but because it is placed
		 directly in /userfiles/ directory, you'll be not able to see it in built-in file browser.
		 The more expected behaviour would be to send images directly to "image" subfolder.
		 To achieve that, simply change
			$Config['QuickUploadPath']['Image']			= $Config['UserFilesPath'] ;
			$Config['QuickUploadAbsolutePath']['Image']	= $Config['UserFilesAbsolutePath'] ;
		into:
			$Config['QuickUploadPath']['Image']			= $Config['FileTypesPath']['Image'] ;
			$Config['QuickUploadAbsolutePath']['Image'] 	= $Config['FileTypesAbsolutePath']['Image'] ;

*/

$Config['AllowedExtensions']['File']	= array('7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vsd', 'wav', 'wma', 'wmv', 'xls', 'xml', 'zip');
$Config['DeniedExtensions']['File']		= array();
$Config['FileTypesPath']['File'] = $Config['UserFilesPath'].'file/';
$Config['FileTypesAbsolutePath']['File'] = ($Config['UserFilesAbsolutePath'] == '') ? '' : $Config['UserFilesAbsolutePath'].'file/';
$Config['QuickUploadPath']['File'] = $Config['UserFilesPath'];
$Config['QuickUploadAbsolutePath']['File'] = $Config['UserFilesAbsolutePath'];

$Config['AllowedExtensions']['Image'] = array('bmp', 'gif', 'jpeg', 'jpg', 'png', 'ai');
if (getDolGlobalString('MAIN_ALLOW_SVG_FILES_AS_IMAGES')) {
	$Config['AllowedExtensions']['Image'][] = 'svg';
}
$Config['DeniedExtensions']['Image']	= array();
$Config['FileTypesPath']['Image'] = $Config['UserFilesPath'].'image/';
$Config['FileTypesAbsolutePath']['Image'] = ($Config['UserFilesAbsolutePath'] == '') ? '' : $Config['UserFilesAbsolutePath'].'image/';
$Config['QuickUploadPath']['Image']		= $Config['UserFilesPath'];
$Config['QuickUploadAbsolutePath']['Image'] = $Config['UserFilesAbsolutePath'];

$Config['AllowedExtensions']['Flash'] = array('swf', 'flv');
$Config['DeniedExtensions']['Flash']	= array();
$Config['FileTypesPath']['Flash'] = $Config['UserFilesPath'].'flash/';
$Config['FileTypesAbsolutePath']['Flash'] = ($Config['UserFilesAbsolutePath'] == '') ? '' : $Config['UserFilesAbsolutePath'].'flash/';
$Config['QuickUploadPath']['Flash']		= $Config['UserFilesPath'];
$Config['QuickUploadAbsolutePath']['Flash'] = $Config['UserFilesAbsolutePath'];

$Config['AllowedExtensions']['Media'] = array('aiff', 'asf', 'avi', 'bmp', 'fla', 'flv', 'gif', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'png', 'qt', 'ram', 'rm', 'rmi', 'rmvb', 'swf', 'tif', 'tiff', 'wav', 'wma', 'wmv');
$Config['DeniedExtensions']['Media']	= array();
$Config['FileTypesPath']['Media'] = $Config['UserFilesPath'].'media/';
$Config['FileTypesAbsolutePath']['Media'] = ($Config['UserFilesAbsolutePath'] == '') ? '' : $Config['UserFilesAbsolutePath'].'media/';
$Config['QuickUploadPath']['Media']		= $Config['UserFilesPath'];
$Config['QuickUploadAbsolutePath']['Media'] = $Config['UserFilesAbsolutePath'];
