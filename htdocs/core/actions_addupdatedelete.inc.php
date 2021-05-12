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
 *  \brief			Code for common actions cancel / add / update / delete / clone
 */


// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $triggermodname may be defined

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
<<<<<<< HEAD
			$value = GETPOST($key,'none');
=======
			$value = GETPOST($key, 'none');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		} elseif ($object->fields[$key]['type']=='date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='datetime') {
			$value = dol_mktime(GETPOST($key.'hour'), GETPOST($key.'min'), 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='price') {
			$value = price2num(GETPOST($key));
		} else {
<<<<<<< HEAD
			$value = GETPOST($key,'alpha');
=======
			$value = GETPOST($key, 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value='';		// This is an implicit foreign key field
		if (! empty($object->fields[$key]['foreignkey']) && $value == '-1') $value='';					// This is an explicit foreign key field

		$object->$key=$value;
<<<<<<< HEAD
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($val['label'])), null, 'errors');
=======
		if ($val['notnull'] > 0 && $object->$key == '' && ! is_null($val['default']) && $val['default'] == '(PROV)')
		{
		    $object->$key = '(PROV)';
		}
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
	}

	if (! $error)
	{
<<<<<<< HEAD
		$result=$object->createCommon($user);
		if ($result > 0)
		{
			// Creation OK
=======
		$result=$object->create($user);
		if ($result > 0)
		{
		    // Creation OK
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$urltogo=$backtopage?str_replace('__ID__', $result, $backtopage):$backurlforlist;
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
<<<<<<< HEAD
			$value = GETPOST($key,'none');
=======
			$value = GETPOST($key, 'none');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		} elseif ($object->fields[$key]['type']=='date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='datetime') {
			$value = dol_mktime(GETPOST($key.'hour'), GETPOST($key.'min'), 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type']=='price') {
			$value = price2num(GETPOST($key));
		} else {
<<<<<<< HEAD
			$value = GETPOST($key,'alpha');
=======
			$value = GETPOST($key, 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value='';		// This is an implicit foreign key field
		if (! empty($object->fields[$key]['foreignkey']) && $value == '-1') $value='';					// This is an explicit foreign key field

		$object->$key=$value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
<<<<<<< HEAD
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($val['label'])), null, 'errors');
=======
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
	}

	if (! $error)
	{
<<<<<<< HEAD
		$result=$object->updateCommon($user);
=======
		$result=$object->update($user);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if ($result > 0)
		{
			$action='view';
		}
		else
		{
			// Creation KO
			setEventMessages($object->error, $object->errors, 'errors');
			$action='edit';
		}
	}
	else
	{
		$action='edit';
	}
}

// Action to update one extrafield
if ($action == "update_extras" && ! empty($permissiontoadd))
{
<<<<<<< HEAD
	$object->fetch(GETPOST('id','int'));
	$attributekey = GETPOST('attribute','alpha');
	$attributekeylong = 'options_'.$attributekey;
	$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong,' alpha');
=======
	$object->fetch(GETPOST('id', 'int'));

	$attributekey = GETPOST('attribute', 'alpha');
	$attributekeylong = 'options_'.$attributekey;
	$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong, ' alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$result = $object->insertExtraFields(empty($triggermodname)?'':$triggermodname, $user);
	if ($result > 0)
	{
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = 'view';
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'edit_extras';
	}
}

// Action to delete
if ($action == 'confirm_delete' && ! empty($permissiontodelete))
{
<<<<<<< HEAD
	$result=$object->deleteCommon($user);
=======
    if (! ($object->id > 0))
    {
        dol_print_error('', 'Error, object must be fetched before being deleted');
        exit;
    }

	$result=$object->delete($user);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
// Remove a line
if ($action == 'confirm_deleteline' && $confirm == 'yes' && ! empty($permissiontoadd))
{
	$result = $object->deleteline($user, $lineid);
	if ($result > 0)
	{
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
		{
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && is_object($object->thirdparty))
		{
			$newlang = $object->thirdparty->default_lang;
		}
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && ! empty($permissiontoadd))
{
	if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
	{
		setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
	}
	else
	{
<<<<<<< HEAD
		if ($object->id > 0)
		{
			// Because createFromClone modifies the object, we must clone it so that we can restore it later
			$orig = clone $object;

			$result=$object->createFromClone($user, $object->id);
			if ($result > 0)
			{
				$newid = 0;
				if (is_object($result)) $newid = $result->id;
				else $newid = $result;
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$newid);	// Open record of new object
				exit;
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$object = $orig;
				$action='';
			}
=======
	    $objectutil = dol_clone($object, 1);   // To avoid to denaturate loaded object when setting some properties for clone or if createFromClone modifies the object. We use native clone to keep this->db valid.
		//$objectutil->date = dol_mktime(12, 0, 0, GETPOST('newdatemonth', 'int'), GETPOST('newdateday', 'int'), GETPOST('newdateyear', 'int'));
        // ...
	    $result=$objectutil->createFromClone($user, (($object->id > 0) ? $object->id : $id));
	    if (is_object($result) || $result > 0)
		{
			$newid = 0;
			if (is_object($result)) $newid = $result->id;
			else $newid = $result;
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$newid);	// Open record of new object
			exit;
		}
		else
		{
		    setEventMessages($objectutil->error, $objectutil->errors, 'errors');
			$action='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
	}
}
