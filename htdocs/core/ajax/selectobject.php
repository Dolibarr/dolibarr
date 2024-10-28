<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$extrafields = new ExtraFields($db);

$objectdesc = GETPOST('objectdesc', 'alphanohtml', 0, null, null, 1);
$htmlname = GETPOST('htmlname', 'aZ09');
$outjson = (GETPOSTINT('outjson') ? GETPOSTINT('outjson') : 0);
$id = GETPOSTINT('id');
$objectfield = GETPOST('objectfield', 'alpha');	// 'MyObject:field' or 'MyModule_MyObject:field' or 'MyObject:option_field' or 'MyModule_MyObject:option_field'

if (empty($htmlname)) {
	httponly_accessforbidden('Bad value for param htmlname');
}

if (!empty($objectfield)) {
	// Recommended method to call selectobject.
	// $objectfield is Object:Field that contains the definition (in table $fields or extrafield). Example: 'Societe:t.ddd' or 'Societe:options_xxx'

	$tmparray = explode(':', $objectfield);
	$objectdesc = '';

	// Load object according to $id and $element
	$objectforfieldstmp = fetchObjectByElement(0, strtolower($tmparray[0]));

	$reg = array();
	if (preg_match('/^options_(.*)$/', $tmparray[1], $reg)) {
		// For a property in extrafields
		$key = $reg[1];
		// fetch optionals attributes and labels
		$extrafields->fetch_name_optionals_label($objectforfieldstmp->table_element);

		if (!empty($extrafields->attributes[$objectforfieldstmp->table_element]['type'][$key]) && $extrafields->attributes[$objectforfieldstmp->table_element]['type'][$key] == 'link') {
			if (!empty($extrafields->attributes[$objectforfieldstmp->table_element]['param'][$key]['options'])) {
				$tmpextrafields = array_keys($extrafields->attributes[$objectforfieldstmp->table_element]['param'][$key]['options']);
				$objectdesc = $tmpextrafields[0];
			}
		}
	} else {
		// For a property in ->fields
		$objectdesc = $objectforfieldstmp->fields[$tmparray[1]]['type'];
		$objectdesc = preg_replace('/^integer[^:]*:/', '', $objectdesc);
	}
}

if ($objectdesc) {
	// Example of value for $objectdesc:
	// Bom:bom/class/bom.class.php:0:t.status=1
	// Bom:bom/class/bom.class.php:0:t.status=1:ref
	// Bom:bom/class/bom.class.php:0:(t.status:=:1) OR (t.field2:=:2):ref
	$InfoFieldList = explode(":", $objectdesc, 4);
	$vartmp = (empty($InfoFieldList[3]) ? '' : $InfoFieldList[3]);
	$reg = array();
	if (preg_match('/^.*:(\w*)$/', $vartmp, $reg)) {
		$InfoFieldList[4] = $reg[1];    // take the sort field
	}
	$InfoFieldList[3] = preg_replace('/:\w*$/', '', $vartmp);    // take the filter field

	$classname = $InfoFieldList[0];
	$classpath = $InfoFieldList[1];
	//$addcreatebuttonornot = empty($InfoFieldList[2]) ? 0 : $InfoFieldList[2];
	$filter = empty($InfoFieldList[3]) ? '' : $InfoFieldList[3];
	$sortfield = empty($InfoFieldList[4]) ? '' : $InfoFieldList[4];

	// Load object according to $id and $element
	$objecttmp = fetchObjectByElement(0, strtolower($InfoFieldList[0]));

	// Fallback to another solution to get $objecttmp
	if (empty($objecttmp) && !empty($classpath)) {
		dol_include_once($classpath);

		if ($classname && class_exists($classname)) {
			$objecttmp = new $classname($db);
		}
	}
}

// Make some replacement
$sharedentities = getEntity(strtolower($objecttmp->element));

$filter = str_replace(
	array('__ENTITY__', '__SHARED_ENTITIES__', '__USER_ID__', '$ID$'),
	array($conf->entity, $sharedentities, $user->id, $id),
	$filter
);

/*
$module = $object->module;
$element = $object->element;
$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !isset($user->rights->$module->$element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}
*/

// When used from jQuery, the search term is added as GET param "term".
$searchkey = (($id && GETPOST((string) $id, 'alpha')) ? GETPOST((string) $id, 'alpha') : (($htmlname && GETPOST($htmlname, 'alpha')) ? GETPOST($htmlname, 'alpha') : ''));

// Add a security test to avoid to get content of all tables
if (!empty($objecttmp->module)) {
	restrictedArea($user, $objecttmp->module, $id, $objecttmp->table_element, $objecttmp->element);
} else {
	restrictedArea($user, $objecttmp->element, $id);
}


/*
 * View
 */

$form = new Form($db);

top_httphead($outjson ? 'application/json' : 'text/html');

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

$arrayresult = $form->selectForFormsList($objecttmp, $htmlname, '', 0, $searchkey, '', '', '', 0, 1, 0, '', $filter);

$db->close();

if ($outjson) {
	print json_encode($arrayresult);
}
