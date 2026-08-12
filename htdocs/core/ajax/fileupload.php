<?php
/* Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

/**
 *       \file       htdocs/core/ajax/fileupload.php
 *       \brief      File to return Ajax response on common file upload. For large files, see flowjs-server.php
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fileupload.class.php';	// Class to upload common files
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$id = GETPOSTINT('fk_element');
$element = GETPOST('element', 'alpha');	// 'myobject' (myobject=mymodule) or 'myobject@mymodule' or 'myobject_mysubobject' (myobject=mymodule)
$elementupload = $element;

// Load object according to $id and $element
$object = fetchObjectByElement($id, $element);

// fetchObjectByElement() returns an object even when the record was not found, and it returns a
// GenericObject with no module when the element is unknown. In both cases restrictedArea() is then called
// with an empty feature, and it grants the access without checking any permission, so we must stop here.
// Note: fetchObjectByElement() may also return an int instead of an object when the module is disabled.
// We answer the same http code and the same message than a refusal by restrictedArea() below, so that a
// user can't tell an object that exists but is not allowed from an object that does not exist.
if (!is_object($object) || empty($object->id) || empty($object->module)) {
	dol_syslog("fileupload.php object ".$element." with id ".$id." was not found or its element is not supported", LOG_WARNING);
	httponly_accessforbidden('Not allowed');
}

$module = $object->module;
$element = $object->element;

$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}

//print 'fileupload.php: '.$object->id.' - '.$object->module.' - '.$object->element.' - '.$object->table_element.' - '.$usesublevelpermission."\n";

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
	// socid is not declared on CommonObject, which is the type fetchObjectByElement() answers, and an object
	// that has no third party does not own the property at all.
	// @phan-suppress-next-line PhanUndeclaredProperty
	if (!empty($object->socid) && $socid != $object->socid) {
		// Same message than every other refusal of this page: a distinct one tells an external user that the
		// object exists but belongs to another third party, which lets him enumerate the records of the others.
		dol_syslog("fileupload.php object ".$element." with id ".$id." belongs to another third party than the external user", LOG_WARNING);
		httponly_accessforbidden('Not allowed');	// This includes the exit.
	}
}

$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	// The module and the table are reported into the log only, they must not be disclosed to the caller
	dol_syslog("fileupload.php not allowed by restrictedArea (module=".$object->module." table_element=".$object->table_element.")", LOG_WARNING);
	httponly_accessforbidden('Not allowed');
}


/*
 * View
 */

top_httphead();

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: '.getRootURLFromURL(DOL_MAIN_URL_ROOT));
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch ($_SERVER['REQUEST_METHOD']) {
	case 'OPTIONS':
		break;
	/*case 'HEAD':
	case 'GET':
		$upload_handler->get();
		break;
	*/
	case 'POST':
		// The constructor throws an exception when the element does not support file uploading, or when the
		// object was not found. Answer with the same json contract than post() so the caller can show the
		// error, instead of letting a fatal error return an http code 500 with no usable content.
		try {
			$upload_handler = new FileUpload(null, $id, $elementupload);
		} catch (Exception $e) {
			// Same http code 200 and same content type negotiation than post(), the error is reported into the
			// json content as the caller expects. Forcing 'application/json' would make jQuery parse the content
			// by itself, and the JSON.parse() of the caller would then fail on an already parsed array.
			if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
				header('Content-type: application/json');
			} else {
				header('Content-type: text/plain');
			}
			echo json_encode(array(array('name' => '', 'error' => $e->getMessage())));
			$db->close();
			exit;
		}

		/*if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
			$file = GETPOST('file');
			$upload_handler->delete($file);
		} else {*/
		$upload_handler->post();
		// Note: even if this return an error on 1 file in post(), we will return http code 200 because error must be managed by the caller (some files may be ok and some in error)
		//}
		break;
	/*case 'DELETE':
		$file = GETPOST('file');
		$upload_handler->delete($file);
		break;*/
	default:
		header('HTTP/1.0 405 Method Not Allowed');
		exit;
}

$db->close();
