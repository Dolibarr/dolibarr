<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Cyrille de Lambert   <info@auguria.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
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
 *       \file      htdocs/core/ajax/ajaxstatusprospect.php
 *       \brief     File of service to update a status of a record:
 *                  - the prospect status of a third party (action=updatestatusprospect),
 *                  - the field used to group the cards of a kanban group by view, when a card is
 *                    dragged into another column (action=updatekanbanfield).
 *       			TODO Rename into updatestatus.php
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
} // Disables token renewal
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$idstatus = GETPOSTINT('id');
$idprospect = GETPOSTINT('prospectid');
$action = GETPOST('action', 'aZ09');

/*
 * Action updatekanbanfield: save the field a card was dragged by into a kanban group by view
 * (mode=kanbangroupby). Unlike core/ajax/saveinplace.php, this is not tied to the deprecated
 * "Edit in place" option and accepts only the fields a class declares into its
 * $kanbangroupbyfields property, so saveinplace.php can remain closed.
 */
if ($action === 'updatekanbanfield') {
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
	// keeps this service narrow: no arbitrary field and no arbitrary table can be written.
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

	top_httphead('application/json');

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
	exit;
}


$prospectstatic = new Client($db);


// Security check
if ($user->socid > 0) {
	if ($idprospect != $user->socid) {
		accessforbidden('Not allowed on this thirdparty');
	}
}

// var_dump(	$user, 'societe', $idprospect, '&societe');
$result = restrictedArea($user, 'societe', $idprospect, '&societe');

$permisstiontoupdate = $user->hasRight('societe', 'creer');


/*
 * View
 */

top_httphead('application/json');


if ($action === "updatestatusprospect" && $permisstiontoupdate) {
	$prospectstatic->client = 2;
	$prospectstatic->loadCacheOfProspStatus();

	$response = "";

	// Load thirdparty
	$prospect = new Societe($db);
	$result = $prospect->fetch($idprospect);

	if ($result >= 0) {
		// Apply new status
		$prospect->stcomm_id = $idstatus;

		// Update using business logic (fires COMPANY_MODIFY)
		$updateResult = $prospect->update($prospect->id, $user);

		if ($updateResult >= 0) {
			$response = img_action('', $prospectstatic->cacheprospectstatus[$idstatus]['code'], $prospectstatic->cacheprospectstatus[$idstatus]['picto'], 'class="inline-block valignmiddle paddingright pictoprospectstatus"');
		} else {
			dol_syslog('Failed to update prospect via update() method', LOG_ERR);
			dol_print_error($db);
		}
	} else {
		dol_print_error($db);
	}
	echo json_encode(array('img' => $response));
}
