<?php
/* Copyright (C) 2015-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/objectonoff.php
 *       \brief      File to set status for an object
 *       			 This Ajax service is oftenly called when option MAIN_DIRECT_STATUS_UPDATE is set.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$value = GETPOST('value', 'int');
$field = GETPOST('field', 'alpha');
$element = GETPOST('element', 'alpha');
$format = 'int';

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
$object->fields[$field] = array('type' => $format, 'enabled' => 1);

$module = $object->module;
$element = $object->element;

//var_dump($object->module); var_dump($object->element); var_dump($object->table_element);

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

//$user->hasRight('societe', 'lire') = 0;$user->rights->fournisseur->lire = 0;
//restrictedArea($user, 'societe', $id);

// We check permission.
// Check is done on $user->rights->element->create or $user->rights->element->subelement->create (because $action = 'set')
if (preg_match('/status$/', $field)) {
	$module = $object->module;
	$element = $object->element;
	$usesublevelpermission = ($module != $element ? $element : '');
	if ($usesublevelpermission && !isset($user->rights->$module->$element)) {	// There is no permission on object defined, we will check permission on module directly
		$usesublevelpermission = '';
	}
	restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission);
} elseif ($element == 'product' && in_array($field, array('tosell', 'tobuy', 'tobatch'))) {	// Special case for products
	restrictedArea($user, 'produit|service', $object, 'product&product', '', '', 'rowid');
} else {
	httponly_accessforbidden("Bad value for combination of parameters element/field.");	// This includes the exit.
}


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering new values
if (($action == 'set') && !empty($id)) {
	$triggerkey = strtoupper(($module != $element ? $module.'_' : '').$element).'_UPDATE';
	// Special case
	if ($triggerkey == 'SOCIETE_UPDATE') {
		$triggerkey = 'COMPANY_MODIFY';
	}
	if ($triggerkey == 'PRODUCT_UPDATE') {
		$triggerkey = 'PRODUCT_MODIFY';
	}

	$result = $object->setValueFrom($field, $value, $object->table_element, $id, $format, '', $user, $triggerkey);

	if ($result < 0) {
		print $object->error;
		http_response_code(500);
		exit;
	}
}
