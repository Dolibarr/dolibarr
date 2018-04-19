<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_addupdatedelete.inc.php
 *  \brief			Code for common actions cancel / add / update / delete
 */


// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined

if ($cancel)
{
	if (! empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
	$action='';
}

// Action to add record
if ($action == 'add' && ! empty($permissiontoadd))
{
	foreach ($object->fields as $key => $val)
	{
		if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'))) continue;	// Ignore special fields

		// Set value to insert
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key,'none');
		} elseif ($object->fields[$key]['type']=='date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='datetime') {
			$value = dol_mktime(GETPOST($key.'hour'), GETPOST($key.'min'), 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='price') {
			$value = price2num(GETPOST($key));
		} else {
			$value = GETPOST($key,'alpha');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value='';		// This is an implicit foreign key field
		if (! empty($object->fields[$key]['foreignkey']) && $value == '-1') $value='';					// This is an explicit foreign key field

		$object->$key=$value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if (! $error)
	{
		$result=$object->createCommon($user);
		if ($result > 0)
		{
			// Creation OK
			$urltogo=$backtopage?$backtopage:$backurlforlist;
			header("Location: ".$urltogo);
			exit;
		}
		else
		{
			// Creation KO
			if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else  setEventMessages($object->error, null, 'errors');
			$action='create';
		}
	}
	else
	{
		$action='create';
	}
}

// Action to update record
if ($action == 'update' && ! empty($permissiontoadd))
{
	foreach ($object->fields as $key => $val)
	{
		if (! GETPOSTISSET($key)) continue;		// The field was not submited to be edited
		if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'))) continue;	// Ignore special fields

		// Set value to update
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key,'none');
		} elseif ($object->fields[$key]['type']=='date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='datetime') {
			$value = dol_mktime(GETPOST($key.'hour'), GETPOST($key.'min'), 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='price') {
			$value = price2num(GETPOST($key));
		} else {
			$value = GETPOST($key,'alpha');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value='';		// This is an implicit foreign key field
		if (! empty($object->fields[$key]['foreignkey']) && $value == '-1') $value='';					// This is an explicit foreign key field

		$object->$key=$value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if (! $error)
	{
		$result=$object->updateCommon($user);
		if ($result > 0)
		{
			$action='view';
		}
		else
		{
			// Creation KO
			if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
			$action='edit';
		}
	}
	else
	{
		$action='edit';
	}
}

// Action to delete
if ($action == 'confirm_delete' && ! empty($permissiontodelete))
{
	$result=$object->deleteCommon($user);
	if ($result > 0)
	{
		// Delete OK
		setEventMessages("RecordDeleted", null, 'mesgs');
		header("Location: ".$backurlforlist);
		exit;
	}
	else
	{
		if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
		else setEventMessages($object->error, null, 'errors');
	}
}
