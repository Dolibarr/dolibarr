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
 *       \brief      File to return Ajax response on file upload
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
/*if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}*/

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fileupload.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

error_reporting(E_ALL | E_STRICT);

//print_r($_POST);
//print_r($_GET);
//print 'upload_dir='.GETPOST('upload_dir');

$id = GETPOST('fk_element', 'int');
$elementupload = GETPOST('element', 'alpha');
$element = $elementupload;

if ($element == "invoice_supplier") {
	$element = "fournisseur";
}

$object = new GenericObject($db);
$tmparray = explode('@', $element);

if (empty($tmparray[1])) {
	$subelement = '';

	$object->module = $element;
	$object->element = $element;
	$object->table_element = $element;

	// Special case for compatibility
	if ($object->table_element == 'websitepage') {
		$object->table_element = 'website_page';
	}
} else {
	$element = $tmparray[0];
	$subelement = $tmparray[1];

	$object->module = $element;
	$object->element = $subelement;
	$object->table_element = $object->module.'_'.$object->element;
}
$object->id = $id;

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$module = $object->module;
$element = $object->element;
$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !isset($user->rights->$module->$element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}
$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);
if (!$result) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}
$upload_handler = new FileUpload(null, $id, $elementupload);


/*
 * View
 */

top_httphead();

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
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
