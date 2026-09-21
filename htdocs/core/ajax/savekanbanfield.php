<?php
/* Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
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
 *       \file      htdocs/core/ajax/savekanbanfield.php
 *       \brief     Save the "group by" field of a record dragged into another column of a
 *                  kanban group by view (mode=kanbangroupby).
 *                  Unlike core/ajax/saveinplace.php, this endpoint is not tied to the
 *                  deprecated "Edit in place" option and accepts only the fields a class
 *                  declares into its $kanbangroupbyfields property. The value is saved with
 *                  setValueFrom() and a trigger key, so triggers are called.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal, the page is called several times in a row
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$element = GETPOST('element', 'aZ09');
$fk_element = GETPOSTINT('fk_element');
$field = preg_replace('/^editval_/', '', GETPOST('field', 'aZ09'));
$value = GETPOST('value', 'aZ09');

// Load object according to $fk_element and $element
$object = fetchObjectByElement($fk_element, $element);
if (!is_object($object) || $object->id <= 0) {
	httponly_accessforbidden('Not allowed, bad combination of parameters for fetchObjectByElement');
}

// Only a field the class declares as a kanban "group by" field can be saved here. This is what
// keeps this endpoint narrow: no arbitrary table and no arbitrary field can be written.
if (empty($object->kanbangroupbyfields) || !is_array($object->kanbangroupbyfields)
	|| !in_array($field, $object->kanbangroupbyfields) || !isset($object->fields[$field])) {
	httponly_accessforbidden('Not allowed, field is not declared as a kanban group by field');
}

// Security check. Set the action to 'update' so restrictedArea() tests the write permission
// and not only the read permission.
$_POST['action'] = 'update';
$result = restrictedArea($user, empty($object->module) ? $element : $object->module, $object, $object->table_element, '', 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	httponly_accessforbidden('Not allowed by restrictArea');
}


/*
 * Actions
 */

top_httphead();

// The column of the records without any value uses the id 'undefined' (see mode=kanbangroupby
// into the list pages), it is stored as 0 like the "Undefined" entry of the column dictionary.
$isint = preg_match('/^integer/', $object->fields[$field]['type']);
if ($value === 'undefined' || $value === '') {
	$newvalue = $isint ? 0 : '';
} else {
	$newvalue = $isint ? (int) $value : $value;
}

$return = array();

// Save with a trigger key, so a stage change is seen by triggers, hooks and the agenda
// (saveinplace.php does not pass any trigger key).
$res = $object->setValueFrom($field, $newvalue, '', null, $isint ? 'int' : 'text', '', $user, strtoupper($object->element).'_MODIFY');
if ($res > 0) {
	$return['value'] = $newvalue;
} else {
	$return['error'] = empty($object->error) ? $langs->trans('ErrorFailedToUpdateRecord') : $object->error;
}

echo json_encode($return);
