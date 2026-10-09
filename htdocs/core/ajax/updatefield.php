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
 *       \file      htdocs/core/ajax/updatefield.php
 *       \brief     Update one field of a record. Only fields of a whitelist can be updated.
 *                  Used for example to save the "group by" field of a record dragged into another column of a
 *                  kanban group by view (mode=kanbangroupby), or the level of a skill of a draft evaluation.
 *                  The value is saved with setValueFrom() and a trigger key, so triggers are called.
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
$value = GETPOST('value', 'alphanohtml');

// Whitelist of fields that can be updated, per element
// TODO Use a property into ->fields to defined whitelist properties allowed for updatefield.php or more globally for inline standalone update?
$whitelistfields = array(
	'project' => array('fk_opp_status'),
	'evaluationdet' => array('rankorder'),
);

// Load object according to $fk_element and $element
$object = fetchObjectByElement($fk_element, $element);
if (!is_object($object) || $object->id <= 0) {
	httponly_accessforbidden('Not allowed, bad combination of parameters for fetchObjectByElement');
}

if (empty($whitelistfields[$object->element]) || !in_array($field, $whitelistfields[$object->element])) {
	httponly_accessforbidden("Can't edit a field ".$field." not in whitelist for element ".$object->element);
}

// Add blacklist of some forbidden field name.
$blacklistedfields = array('pass', 'pass_crypted', 'pass_temp', 'api_key', 'openid', 'admin', 'status', 'statut');
$canreadsalary = ((isModEnabled('salaries') && $user->hasRight('salaries', 'read')) || !isModEnabled('salaries'));
if (!$canreadsalary) {
	$blacklistedfields[] = 'salary';
	$blacklistedfields[] = 'salaryextra';
	$blacklistedfields[] = 'thm';
	$blacklistedfields[] = 'tjm';
}
if (in_array($field, $blacklistedfields)) {
	httponly_accessforbidden("Can't edit a field blacklisted with name ".$field);
}

// Security check with mode 'write', so restrictedArea() tests the write permission and not only the read permission.
if ($object->element == 'evaluationdet') {
	// A line of evaluation: permission is checked on the parent evaluation, that must be a draft
	'@phan-var-force EvaluationLine $object';
	require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluation.class.php';
	$parentobject = new Evaluation($db);
	if ($parentobject->fetch($object->fk_evaluation) <= 0) {
		httponly_accessforbidden('Parent evaluation not found');
	}
	if ($parentobject->status != Evaluation::STATUS_DRAFT) {
		httponly_accessforbidden('Evaluation is not a draft');
	}
	$result = restrictedArea($user, $parentobject->element, $parentobject, $parentobject->table_element, '', 'fk_soc', 'rowid', 1, 1, 'write');	// Call with nodie return
} else {
	$result = restrictedArea($user, empty($object->module) ? $element : $object->module, $object, $object->table_element, '', 'fk_soc', 'rowid', 0, 1, 'write');	// Call with nodie return
}
if (!$result) {
	httponly_accessforbidden('Not allowed by restrictArea');
}


/*
 * Actions
 */

top_httphead('application/json');

$langs->load('errors');

$return = array();

// The column of the records without any value uses the id 'undefined' (see mode=kanbangroupby
// into the list pages), it is stored as 0 like the "Undefined" entry of the column dictionary.
$isint = preg_match('/^integer/', $object->fields[$field]['type']);
if ($value === 'undefined' || $value === '') {
	$newvalue = $isint ? 0 : '';
} elseif ($isint) {
	if (!preg_match('/^-?\d+$/', $value)) {
		$return['error'] = $langs->trans('ErrorBadValueForParameter', $value, 'value');
	}
	$newvalue = (int) $value;
} else {
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
		$return['error'] = $langs->trans('ErrorBadValueForParameter', $value, 'value');
	}
	$newvalue = $value;
}

// Check the range of value for some fields
if ($object->element == 'evaluationdet' && $field == 'rankorder') {
	// A rank can only be "not applicable" (-1) or a level between 0 and the maximum number of levels
	require_once DOL_DOCUMENT_ROOT.'/hrm/class/skill.class.php';
	if ($newvalue < -1 || $newvalue > getDolGlobalInt('HRM_MAXRANK', Skill::DEFAULT_MAX_RANK_PER_SKILL)) {
		$return['error'] = $langs->trans('ErrorBadValueForParameter', $value, 'value');
	}
}

if (empty($return['error'])) {
	// Save with a trigger key, so a change is seen by triggers, hooks and the agenda
	$res = $object->setValueFrom($field, $newvalue, '', null, $isint ? 'int' : 'text', '', $user, strtoupper($object->element).'_MODIFY');
	if ($res > 0) {
		$return['value'] = $newvalue;
	} else {
		$return['error'] = empty($object->error) ? $langs->trans('ErrorFailedToUpdateRecord') : $object->error;
	}
}

echo json_encode($return);
