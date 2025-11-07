<?php
/* Copyright (C) 2007-2024  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/ajax/ajaxfield.php
 *      \ingroup    core
 *      \brief      This script returns content of extrafield. See extrafield to update value.
 */

if (!defined('NOTOKENRENEWAL')) {
	// Disables token renewal
	define('NOTOKENRENEWAL', 1);
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fieldsmanager.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// object id
$objectid = GETPOST('objectid', 'aZ09');
// 'module' or 'myobject@mymodule', 'mymodule_myobject'
$objecttype = GETPOST('objecttype', 'aZ09arobase');
$objectkey = GETPOST('objectkey', 'restricthtml');
$search = GETPOST('search', 'restricthtml');
$page = GETPOSTINT('page');
$mode = GETPOST('mode', 'aZ09');
$value = GETPOST('value', 'alphanohtml');
$dependencyvalue = GETPOST('dependencyvalue', 'alphanohtml');
$limit = 10;
$element_ref = '';
if (is_numeric($objectid)) {
	$objectid = (int) $objectid;
} else {
	$element_ref = $objectid;
	$objectid = 0;
}
// Load object according to $element
$object = fetchObjectByElement($objectid, $objecttype, $element_ref);
if (empty($object->element)) {
	httponly_accessforbidden('Failed to get object with fetchObjectByElement(id=' . $objectid . ', objecttype=' . $objecttype . ')');
}

$module = $object->module;
$element = $object->element;

$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}

// print $object->id.' - '.$object->module.' - '.$object->element.' - '.$object->table_element.' - '.$usesublevelpermission."\n";

// Security check
restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission);


/*
 * View
 */

top_httphead();

$data = [
	'results' => [],
	'pagination' => [
		'more' => true,
	]
];
$nbResult = 0;
if ($object instanceof CommonObject) {
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($object->table_element);

	$fieldManager = new FieldsManager($db);
	$fieldInfos = $fieldManager->getFieldsInfos($objectkey, $object, $extrafields, $mode);
	$field = $fieldManager->getFieldClass($fieldInfos->type);
	if (isset($field)) {
		if (method_exists($field, 'getOptions')) {
			$fieldInfos->optionsSqlPage = $page;
			$fieldInfos->optionsSqlLimit = $limit;
			if ($dependencyvalue !== '') {
				$fieldInfos->optionsSqlDependencyValue = $dependencyvalue;
			}

			/**
			 * @var CommonSellistField $field
			 */
			'@phan-var-force CommonSellistField $field';
			$options = $field->getOptions($fieldInfos, $objectkey, $page == 1, true);
			if (is_array($options)) {
				$nbResult = count($options);
				foreach ($options as $key => $option) {
					$data['results'][] = [
						'id' => $key,
						'text' => $option['label'],
					];
				}
			} else {
				dol_syslog('htdocs/core/ajax/ajaxfield.php ' . $field->errorsToString(), LOG_ERR);
			}
		} else {
			dol_syslog('htdocs/core/ajax/ajaxfield.php method getOptions() don\'t exist in class ' . get_class($field), LOG_ERR);
		}
	} else {
		dol_syslog('htdocs/core/ajax/ajaxfield.php ' . $fieldManager->errorsToString(), LOG_ERR);
	}
}

if ($page > 1 && $nbResult < 10) {
	$data['pagination'] = [
		'more' => false,
	];
}
print json_encode($data);

$db->close();
