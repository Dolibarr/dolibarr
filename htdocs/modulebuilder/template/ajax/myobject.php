<?php
/* Copyright (C) 2022       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) ---Replace with your own copyright and developer email---
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
 *       \file       htdocs/mymodule/ajax/myobject.php
 *       \brief      File to return Ajax response on myobject list request
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
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
dol_include_once('/mymodule/class/myobject.class.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$mode = GETPOST('mode', 'aZ09');
$objectId = GETPOST('objectId', 'aZ09');
$field = GETPOST('field', 'aZ09');
$value = GETPOST('value', 'aZ09');

// @phan-suppress-next-line PhanUndeclaredClass
$object = new MyObject($db);

// Security check
if (!$user->hasRight('mymodule', 'myobject', 'write')) {
	accessforbidden();
}

/*
 * View
 */

dol_syslog("Call ajax mymodule/ajax/myobject.php");

top_httphead();

// Update the object field with the new value
if ($objectId && $field && isset($value)) {
	$object->fetch($objectId);
	if ($object->id > 0) {
		$object->$field = $value;
	}
	$result = $object->update($user);

	if ($result < 0) {
		print json_encode(['status' => 'error', 'message' => 'Error updating '. $field]);
	} else {
		print json_encode(['status' => 'success', 'message' => $field . ' updated successfully']);
	}
}

$db->close();
