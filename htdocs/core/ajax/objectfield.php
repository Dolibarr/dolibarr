<?php
/* Copyright (C) 2026		John BOTELLA
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
 *       \file      htdocs/core/ajax/objectfield.php
 *       \brief     Update one field of an object from the user interface (ajax edition of cards).
 *                  Only fields allowed by CommonObject::isFieldEditableFromUi() can be updated (attribute "uieditable").
 *                  The value is saved with CommonObject::updateFieldFromUi(), so triggers are called.
 *                  Answer is a JsonResponse, data contains the saved value and its output html.
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
if (!defined('NOREDIRECTBYMAINTOLOGIN')) {
	define('NOREDIRECTBYMAINTOLOGIN', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/jsonResponse.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$element = GETPOST('element', 'aZ09');
$id = GETPOSTINT('id');
$field = GETPOST('field', 'aZ09');
$tms = GETPOSTINT('tms');

$langs->load('errors');

$jsonResponse = new JsonResponse();

// Only POST is allowed, so the CSRF token is always checked. An invalid token empties $_POST, so $field is empty.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_METHOD_NOT_ALLOWED);
	$jsonResponse->output();
}
if (empty($element) || empty($id) || empty($field)) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}

// Load object
$object = fetchObjectByElement($id, $element);
if (!is_object($object) || $object->id <= 0) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorRecordNotFound'), JsonResponse::HTTP_NOT_FOUND);
	$jsonResponse->output();
}

// Security check with mode 'write', so restrictedArea() tests the write permission and the access of external users
$result = restrictedArea($user, empty($object->module) ? $object->element : $object->module, $object, $object->table_element, '', 'fk_soc', 'rowid', 0, 1, 'write');
if (!$result) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('NotEnoughPermissions'), JsonResponse::HTTP_FORBIDDEN);
	$jsonResponse->output();
}

// The user interface can only change fields with an explicit opt-in, never isFieldEditAllowed() alone
if (!$object->isFieldEditableFromUi($user, $field)) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorFieldIsNotEditable'), JsonResponse::HTTP_FORBIDDEN);
	$jsonResponse->output();
}

// Optional optimistic lock: refuse if the object was modified since the date of modification known by the page
if ($tms > 0 && !empty($object->date_modification) && $object->date_modification > $tms) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorRecordModifiedSinceDisplay'), JsonResponse::HTTP_CONFLICT);
	$jsonResponse->output();
}

// Get the value with a filter matching the type of field
$type = $object->fields[$field]['type'];
if ($type == 'html') {
	$value = dol_html_entity_decode(GETPOST('value', 'restricthtml'), ENT_QUOTES | ENT_HTML5, 'UTF-8', 1);
} elseif (preg_match('/^(varchar|text)\b/', $type)) {
	$value = GETPOST('value', 'alphanohtml');
} elseif (preg_match('/^(int|integer)\b/', $type)) {
	$value = GETPOSTINT('value');
} else {
	// Other types (dates, lists, links...) are not supported yet
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorFieldIsNotEditable'), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}


/*
 * Actions
 */

$result = $object->updateFieldFromUi($user, $field, $value);
if ($result < 0) {
	$jsonResponse->setError(dol_html_entity_decode($object->error, ENT_QUOTES | ENT_HTML5), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}

// Reload object to return the value really saved and the new date of modification
$reloadedobject = fetchObjectByElement($id, $element);
if (is_object($reloadedobject) && $reloadedobject->id > 0) {
	$object = $reloadedobject;
}

// Fields like notes are hidden from generic forms and lists (visible = 0) but shown by their own block of the card,
// so the output is forced visible: the field is already allowed by isFieldEditableFromUi().
$fielddef = $object->fields[$field];
$fielddef['visible'] = 1;

$jsonResponse->data = array(
	'element' => $element,
	'id' => $id,
	'field' => $field,
	'value' => $object->$field,
	'html' => $object->showOutputField($fielddef, $field, $object->$field),
	'tms' => empty($object->date_modification) ? null : (int) $object->date_modification,
);

$jsonResponse->setSuccess();
$jsonResponse->output();
