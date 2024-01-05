<?php
/* Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
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

$id = GETPOST('fk_element', 'int');
$element = GETPOST('element', 'alpha');	// 'myobject' (myobject=mymodule) or 'myobject@mymodule' or 'myobject_mysubobject' (myobject=mymodule)
$elementupload = $element;

// Load object according to $id and $element
$object = fetchObjectByElement($id, $element);

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
	if (!empty($object->socid) && $socid != $object->socid) {
		httponly_accessforbidden("Access on object not allowed for this external user.");	// This includes the exit.
	}
}

$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	httponly_accessforbidden('Not allowed by restrictArea (module='.$object->module.' table_element='.$object->table_element.')');
}


/*
 * View
 */

$upload_handler = new FileUpload(null, $id, $elementupload);


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
	case 'HEAD':
	case 'GET':
		$upload_handler->get();
		break;
	case 'POST':
		if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
			$upload_handler->delete();
		} else {
			$upload_handler->post();
			// Note: even if this return an error on 1 file in post(), we will return http code 200 because error must be managed by the caller (some files may be ok and some in error)
		}
		break;
	case 'DELETE':
		$upload_handler->delete();
		break;
	default:
		header('HTTP/1.0 405 Method Not Allowed');
		exit;
}

$db->close();
