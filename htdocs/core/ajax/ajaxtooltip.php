<?php
/* Copyright (C) 2007-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/ajax/ajaxtooltip.php
 *      \ingroup    tooltip
 *      \brief      This script returns content of tooltip
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
if (!defined('NOHEADERNOFOOTER')) {
	define('NOHEADERNOFOOTER', '1');
}

include '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$id = GETPOST('id', 'aZ09');
$objecttype = GETPOST('objecttype', 'aZ09arobase');	// 'module' or 'myobject@mymodule', 'mymodule_myobject'

$params = array('fromajaxtooltip' => 1);
if (GETPOSTISSET('infologin')) {
	$params['infologin'] = GETPOSTINT('infologin');
}
if (GETPOSTISSET('option')) {
	$params['option'] = GETPOST('option', 'restricthtml');
}
$element_ref = '';
if (is_numeric($id)) {
	$id = (int) $id;
} else {
	$element_ref = $id;
	$id = 0;
}
// Load object according to $element
$object = fetchObjectByElement($id, $objecttype, $element_ref);
if (empty($object->element)) {
	httponly_accessforbidden('Failed to get object with fetchObjectByElement(id='.$id.', objecttype='.$objecttype.')');
}

$module = $object->module;
$element = $object->element;

$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}

//print $object->id.' - '.$object->module.' - '.$object->element.' - '.$object->table_element.' - '.$usesublevelpermission."\n";

// Security check
restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission);


/*
 * View
 */

top_httphead();

$html = '';

if (is_object($object)) {
	'@phan-var-force CommonObject $object';
	if ($object->id > 0 || !empty($object->ref)) {
		/** @var CommonObject $object */
		$html = $object->getTooltipContent($params);
	} elseif ($id > 0) {
		$html = $langs->trans('Deleted');
	}
	unset($object);
}

print $html;

$db->close();
