<?php
/* Copyright (C) 2015-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_dellink.inc.php
 *  \brief			Code for actions on linking and deleting link between elements
 */


// $action must be defined
// $object must be defined
// $permissiondellink must be defined

$dellinkid = GETPOSTINT('dellinkid');
$addlink = GETPOST('addlink', 'alpha');
$addlinkid = GETPOSTINT('idtolinkto');
$addlinkref = GETPOST('reftolinkto', 'alpha');
$cancellink = GETPOST('cancel', 'alpha');

// Link object to another object
if ($action == 'addlink' && !empty($permissiondellink) && !$cancellink && $id > 0 && $addlinkid > 0) {
	$object->fetch($id);
	$object->fetch_thirdparty();
	$result = $object->add_object_linked($addlink, $addlinkid);
}

// Link by reference
if ($action == 'addlinkbyref' && !empty($permissiondellink) && !$cancellink && $id > 0 && !empty($addlinkref) && getDolGlobalString('MAIN_LINK_BY_REF_IN_LINKTO')) {
	$element_prop = getElementProperties($addlink);
	if (is_array($element_prop)) {
		dol_include_once('/' . $element_prop['classpath'] . '/' . $element_prop['classfile'] . '.class.php');

		$objecttmp = new $element_prop['classname']($db);
		$ret = $objecttmp->fetch(0, $addlinkref);
		if ($ret > 0) {
			$object->fetch($id);
			$object->fetch_thirdparty();
			$result = $object->add_object_linked($addlink, $objecttmp->id);
			if (isset($_POST['reftolinkto'])) {
				unset($_POST['reftolinkto']);
			}
		} elseif ($ret < 0) {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
		} else {
			$langs->load('errors');
			setEventMessage($langs->trans('ErrorRecordNotFound'), 'errors');
		}
	}
}

// Delete link in table llx_element_element
if ($action == 'dellink' && !empty($permissiondellink) && !$cancellink && $dellinkid > 0) {
	$result = $object->deleteObjectLinked(0, '', 0, '', $dellinkid);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}
