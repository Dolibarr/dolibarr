<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/selectobject.php
 *       \brief      File to return Ajax response on a selection list request
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$objectdesc = GETPOST('objectdesc', 'alpha');
$htmlname = GETPOST('htmlname', 'aZ09');
$outjson = (GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$id = GETPOST('id', 'int');
$filter = GETPOST('filter', 'alphanohtml');	// Universal Syntax filter

if (empty($htmlname)) {
	httponly_accessforbidden('Bad value for param htmlname');
}

$InfoFieldList = explode(":", $objectdesc);
$classname = $InfoFieldList[0];
$classpath = $InfoFieldList[1];
if (!empty($classpath)) {
	dol_include_once($classpath);
	if ($classname && class_exists($classname)) {
		$objecttmp = new $classname($db);
	}
}
if (!is_object($objecttmp)) {
	httponly_accessforbidden('Bad value for param objectdesc');
}

/*
// Load object according to $id and $element
$object = fetchObjectByElement($id, $element);

$module = $object->module;
$element = $object->element;
$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !isset($user->rights->$module->$element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}
*/

// When used from jQuery, the search term is added as GET param "term".
$searchkey = (($id && GETPOST($id, 'alpha')) ? GETPOST($id, 'alpha') : (($htmlname && GETPOST($htmlname, 'alpha')) ? GETPOST($htmlname, 'alpha') : ''));

// Add a security test to avoid to get content of all tables
if (!empty($objecttmp->module)) {
	restrictedArea($user, $objecttmp->module, $id, $objecttmp->table_element, $objecttmp->element);
} else {
	restrictedArea($user, $objecttmp->element, $id);
}


/*
 * View
 */

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";
//print_r($_GET);

//$langs->load("companies");

$form = new Form($db);

top_httphead($outjson ? 'application/json' : 'text/html');

$arrayresult = $form->selectForFormsList($objecttmp, $htmlname, '', 0, $searchkey, '', '', '', 0, 1, 0, '', $filter);

$db->close();

if ($outjson) {
	print json_encode($arrayresult);
}
