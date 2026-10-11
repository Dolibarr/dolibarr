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
 *       \brief     Read or update fields of an object from the user interface (ajax edition of cards).
 *                  GET with parameter fields (list of fields separated by a comma) returns the values of the fields
 *                  allowed by CommonObject::isFieldReadableFromUi(), to refresh the values displayed by a page.
 *                  POST with parameter field and the inputs of this field (named ajaxfield_<field>..., read by
 *                  CommonObject::getFieldValueFromAjaxPost()) updates one field allowed by CommonObject::isFieldEditableFromUi()
 *                  (attribute "uieditable"). The value is saved with CommonObject::updateFieldFromUi(), so triggers are called.
 *                  Answer is a JsonResponse, data contains the values and their output html (CommonObject::ajaxOutputField()).
 *                  After an update, it also contains the other fields changed by the update (computed fields, triggers...).
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
$fieldlist = GETPOST('fields', 'aZ09comma');
$tms = GETPOSTINT('tms');

$isupdate = ($_SERVER['REQUEST_METHOD'] === 'POST');

$langs->load('errors');

top_httphead('application/json');

$jsonResponse = new JsonResponse();

// Update only with POST, so the CSRF token is always checked. An invalid token empties $_POST, so $field is empty.
if (!$isupdate && $_SERVER['REQUEST_METHOD'] !== 'GET') {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_METHOD_NOT_ALLOWED);
	$jsonResponse->output();
}
if (empty($element) || empty($id) || ($isupdate ? empty($field) : empty($fieldlist))) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}

// Load object
$object = fetchObjectByElement($id, $element);
if (!is_object($object) || $object->id <= 0) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorRecordNotFound'), JsonResponse::HTTP_NOT_FOUND);
	$jsonResponse->output();
}

// Security check, restrictedArea() tests the read or write permission and the access of external users
$result = restrictedArea($user, empty($object->module) ? $object->element : $object->module, $object, $object->table_element, '', 'fk_soc', 'rowid', 0, 1, $isupdate ? 'write' : 'read');
if (!$result) {
	$jsonResponse->setError($langs->transnoentitiesnoconv('NotEnoughPermissions'), JsonResponse::HTTP_FORBIDDEN);
	$jsonResponse->output();
}

/**
 * Return the value of a field of an object and its output html
 *
 * @param	CommonObject	$object		Object
 * @param	string			$fieldname	Field name
 * @return	array{value:mixed,html:string}
 */
function objectFieldGetOutput($object, $fieldname)
{
	return array(
		'value' => property_exists($object, $fieldname) ? $object->$fieldname : null,
		'html' => $object->ajaxOutputField($fieldname),
	);
}

// Read the values of fields
if (!$isupdate) {
	$fields = array();
	foreach (explode(',', $fieldlist) as $fieldname) {
		// Fields not readable are ignored, the page may display fields not defined in $fields
		if ($fieldname !== '' && $object->isFieldReadableFromUi($user, $fieldname)) {
			$fields[$fieldname] = objectFieldGetOutput($object, $fieldname);
		}
	}

	$jsonResponse->data = array(
		'element' => $element,
		'id' => $id,
		'fields' => $fields,
		'tms' => empty($object->date_modification) ? null : (int) $object->date_modification,
	);

	$jsonResponse->setSuccess();
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

// Get the posted value, the object knows the inputs of its fields
$value = $object->getFieldValueFromAjaxPost($field);
if ($value === null) {
	// Input not posted or type of field not supported
	$jsonResponse->setError($langs->transnoentitiesnoconv('ErrorBadParameters'), JsonResponse::HTTP_BAD_REQUEST);
	$jsonResponse->output();
}


/*
 * Actions
 */

// Values before the update, to return the other fields changed by the update
$valuesbefore = array();
foreach (array_keys($object->fields) as $fieldname) {
	if ($object->isFieldReadableFromUi($user, $fieldname)) {
		$valuesbefore[$fieldname] = property_exists($object, $fieldname) ? $object->$fieldname : null;
	}
}

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

// The updated field and the other fields changed by the update
$fields = array($field => objectFieldGetOutput($object, $field));
foreach ($valuesbefore as $fieldname => $valuebefore) {
	$valueafter = property_exists($object, $fieldname) ? $object->$fieldname : null;
	if ($fieldname != $field && $valueafter !== $valuebefore) {
		$fields[$fieldname] = objectFieldGetOutput($object, $fieldname);
	}
}

$jsonResponse->data = array(
	'element' => $element,
	'id' => $id,
	'field' => $field,
	'value' => $fields[$field]['value'],
	'html' => $fields[$field]['html'],
	'fields' => $fields,
	'tms' => empty($object->date_modification) ? null : (int) $object->date_modification,
);

$jsonResponse->setSuccess();
$jsonResponse->output();
