<?php
/* Copyright (C) 2014-2017 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France       <frederic.france@free.fr>
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
 *  \file           htdocs/core/actions_fetchobject.inc.php
 *  \brief          Code for actions on fetching object page
 */


// $action must be defined
// $object must be defined (object is loaded in this file with fetch)
// $cancel must be defined
// $id or $ref must be defined (object is loaded in this file with fetch)

if (($id > 0 || (!empty($ref) && !in_array($action, array('create', 'createtask', 'add')))) && (empty($cancel) || $id > 0)) {
	if (($id > 0 && is_numeric($id)) || !empty($ref)) {	// To discard case when id is list of ids like '1,2,3...'
		$ret = $object->fetch($id, (empty($ref)? '' : $ref));
		if ($ret > 0) {
			$object->fetch_thirdparty();
			$id = $object->id;
		} else {
			if (empty($object->error) && !count($object->errors)) {
				if ($ret < 0) {	// if $ret == 0, it means not found.
					setEventMessages('Fetch on object (type '.get_class($object).') return an error without filling $object->error nor $object->errors', null, 'errors');
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			$action = '';
		}
	}
}
