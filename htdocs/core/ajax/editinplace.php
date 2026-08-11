<?php
/* Copyright (C) 2011-2025  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *       \file      htdocs/core/ajax/editinplace.php
 *       \brief     File to load or update a field value.
 *       			Was used in past when option "Edit In Place" is set (MAIN_USE_EDIT_IN_PLACE).
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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

// For developers you can define  DEBUGEDITINPLACE in your conf.php file
if (!defined('DEBUGEDITINPLACE')) {
	define('DEBUGEDITINPLACE', 0);
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/jsonResponse.class.php';


$hookmanager->initHooks(array('editinplace'));

$jsonResponse = new JsonResponse();
$jsonResponse->result = 0;

$action = GETPOST('action', 'alpha');
$elementType = GETPOST('elementType', 'alpha');
$fk_element = GETPOST('fk_element', 'alpha');
$field = GETPOST('field', 'alpha');

// Load object according to $id and $element
$element_ref = '';
if (is_numeric($fk_element)) {
	$id = (int) $fk_element;
} else {
	$element_ref = $fk_element;
	$id = 0;
}
$object = fetchObjectByElement($id, $elementType, $element_ref);

$module = $object->module;
$element = $object->element;
$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}


/*
 * View
 */

// Security check
$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	$jsonResponse->setError('Not allowed by restrictArea', JsonResponse::HTTP_FORBIDDEN);
	$jsonResponse->output();
	exit; // useless but for security in case of someone remove exit en output()
}

if (!getDolGlobalString('MAIN_USE_EDIT_IN_PLACE')) {
	$jsonResponse->setError('Can be used only when option MAIN_USE_EDIT_IN_PLACE is set', JsonResponse::HTTP_SERVICE_UNAVAILABLE);
	$jsonResponse->output();
	exit; // useless but for security in case of someone remove exit en output()
}

// @phpstan-ignore-next-line
if ((int) DEBUGEDITINPLACE === 1) {
	$jsonResponse->debug = [
		'element' => $object->element,
		'Object id' => $object->id,
		'module' => $object->module,
		'usesublevelpermission' => $usesublevelpermission,
		'table_element' => $object->table_element,
		'call url' => $_SERVER["PHP_SELF"],
		'query string' => $_SERVER["QUERY_STRING"]
	];
}


// Load original field value
if (!empty($field) && !empty($elementType) && !empty($fk_element)) {
	$jsonResponse->data = new stdClass();
	$jsonResponse->data->settedValue = '';

	$jsonResponse->data->htmlform = '';
	$jsonResponse->data->fieldValue = '';
	$jsonResponse->data->fieldName = '';

	$elementProperties = getElementProperties($elementType);
	$element = $elementProperties['element'];
	$subElement = $elementProperties['subelement'];
	$keysuffix = '';
	$keyprefix = '';

	//  $elementProperties = array(
	//      'module' => $module,
	//      'element' => $element,
	//      'table_element' => $table_element,
	//      'subelement' => $subelement,
	//      'classpath' => $classpath,
	//      'classfile' => $classfile,
	//      'classname' => $classname,
	//      'dir_output' => $dir_output,
	//      'dir_temp' => $dir_temp,
	//      'parent_element' => $parent_element,
	//  );


	$parameters = array(
		'elementProperties' => $elementProperties,
		'field' => $field,
		'id' => $id,
		'jsonResponse' =>& $jsonResponse
	);
	$res = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
	if ($res < 0) {
		$jsonResponse->result = 0;
		$jsonResponse->msg = !empty($hookmanager->error) ? $hookmanager->error : $langs->transnoentities('AnErrorOccurredDuringHookExecution');
	} elseif (empty($res)) {
		if (!$object->isFieldEditAllowed($user, $field)) {
			$jsonResponse->setError($langs->transnoentities('ErrorFieldIsNotEditable'), JsonResponse::HTTP_FORBIDDEN);
			$jsonResponse->output();
		}

		if ($action === 'get-field-form') {
			// TODO : WORK IN PROGRESS
			// TODO : Create a new method in common object like $object->showInputFieldForEditInPlace() ?
			$jsonResponse->data->htmlform = $object->showInputField($object->fields[$field], $field, $object->{$field}, '', $keysuffix, $keyprefix);
			$jsonResponse->data->fieldValue = $object->{$field};
			$jsonResponse->data->fieldName = $keyprefix.$field.$keysuffix;

			$jsonResponse->setSuccess();
			$jsonResponse->output();
		} elseif ($action === 'set-field-value') {
			$value = GETPOST('value'); // TODO WORK IN PROGRESS : determine value see actions_addupdatedelete.inc.php or core/class/fieldsmanager.class.php

			if ($object->setFieldValue($user, $field, $value)) {
				$updateRes = $object->updateCommon($user);
				if ($updateRes > 0) {
					$jsonResponse->data->settedValue = $value;
					$jsonResponse->setSuccess();
				} else {
					$jsonResponse->setError($langs->transnoentities('SaveError'), JsonResponse::HTTP_INTERNAL_ERROR);
				}
			} else {
				$jsonResponse->setError($object->getFieldError($field), JsonResponse::HTTP_NOT_IMPLEMENTED);
			}

			$jsonResponse->output();
		} else {
			$jsonResponse->setError($langs->transnoentities('ActionNotFound'));
			$jsonResponse->output();
		}
	}
} else {
	$jsonResponse->msg = 'Error : Invalid parameters'; // Developer error message
}

$jsonResponse->output();
