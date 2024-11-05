<?php
/* Copyright (C) 2022       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/editextrafield.php
 *       \brief      File to return Ajax response for stars extrafield request
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
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
include '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$objectType = GETPOST('objectType', 'aZ09');
$objectId = GETPOST('objectId', 'aZ09');
$field = GETPOST('field', 'aZ09');
$value = GETPOST('value', 'aZ09');

$module = getElementProperties($objectType)['module'];
$element_ref = '';
if (is_numeric($objectId)) {
	$objectId = (int) $objectId;
} else {
	$element_ref = $objectId;
	$objectId = 0;
}
$object = fetchObjectByElement($objectId, $objectType, $element_ref);

// Security check
if (!$user->hasRight($module, $object->element, 'write') && !$user->hasRight($module, 'write')) {
	accessforbidden();
}

/*
 * View
 */

dol_syslog("Call ajax core/ajax/editextrafield.php");

top_httphead();

// Update the object field with the new value
if ($object->id > 0 && $field && isset($value)) {
	$object->array_options['options_'.$field] = $value;
	$result = $object->update($user);
	if ($result < 0) {
		print json_encode(['status' => 'error', 'message' => 'Error updating '. $field]);
	} else {
		print json_encode(['status' => 'success', 'message' => $field . ' updated successfully']);
	}
}

$db->close();
