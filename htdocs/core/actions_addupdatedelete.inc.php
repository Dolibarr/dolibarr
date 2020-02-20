<?php
/* Copyright (C) 2017-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_addupdatedelete.inc.php
 *  \brief			Code for common actions cancel / add / update / update_extras / delete / deleteline / validate / cancel / reopen / clone
 */


// $action or $cancel must be defined
// $object must be defined
// $permissiontoadd must be defined
// $permissiontodelete must be defined
// $backurlforlist must be defined
// $backtopage may be defined
// $triggermodname may be defined

if (!empty($permissionedit) && empty($permissiontoadd)) $permissiontoadd = $permissionedit; // For backward compatibility

if ($cancel)
{
	/*var_dump($cancel);
	var_dump($backtopage);exit;*/
	if (!empty($backtopageforcancel))
	{
		header("Location: ".$backtopageforcancel);
		exit;
	}
	elseif (!empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
}


// Action to add record
if ($action == 'add' && !empty($permissiontoadd))
{
	foreach ($object->fields as $key => $val)
	{
		if ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour') == '' && GETPOST($key.'min') == '') continue; // The field was not submited to be edited
		}
		else {
			if (!GETPOSTISSET($key)) continue; // The field was not submited to be edited
		}
		// Ignore special fields
		if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'))) continue;

		// Set value to insert
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key, 'none');
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
		} elseif ($object->fields[$key]['type'] == 'duration') {
			$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'none')); // To fix decimal separator according to lang setup
		} else {
			$value = GETPOST($key, 'alphanohtml');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value = ''; // This is an implicit foreign key field
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') $value = ''; // This is an explicit foreign key field

		//var_dump($key.' '.$value.' '.$object->fields[$key]['type']);
		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && !is_null($val['default']) && $val['default'] == '(PROV)')
		{
		    $object->$key = '(PROV)';
		}
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if (!$error)
	{
		$result = $object->create($user);
		if ($result > 0)
		{
		    // Creation OK
			$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$urltogo);
			exit;
		}
		else
		{
			// Creation KO
			if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else  setEventMessages($object->error, null, 'errors');
			$action = 'create';
		}
	}
	else
	{
		$action = 'create';
	}
}

// Action to update record
if ($action == 'update' && !empty($permissiontoadd))
{
	foreach ($object->fields as $key => $val)
	{
		// Check if field was submited to be edited
		if ($object->fields[$key]['type'] == 'duration') {
			if (!GETPOSTISSET($key.'hour') || !GETPOSTISSET($key.'min')) continue; // The field was not submited to be edited
		}
		else {
			if (!GETPOSTISSET($key)) continue; // The field was not submited to be edited
		}
		// Ignore special fields
		if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'))) continue;

		// Set value to update
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key, 'none');
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour'), GETPOST($key.'min'), 0, GETPOST($key.'month'), GETPOST($key.'day'), GETPOST($key.'year'));
		} elseif ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour', 'int') != '' || GETPOST($key.'min', 'int') != '') {
				$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
			} else {
				$value = '';
			}
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
            $value = price2num(GETPOST($key, 'none'));	// To fix decimal separator according to lang setup
		} else {
			$value = GETPOST($key, 'alpha');
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') $value = ''; // This is an implicit foreign key field
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') $value = ''; // This is an explicit foreign key field

		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default']))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}
	}

	if (!$error)
	{
		$result = $object->update($user);
		if ($result > 0)
		{
			$action = 'view';
		}
		else
		{
			// Creation KO
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit';
		}
	}
	else
	{
		$action = 'edit';
	}
}

// Action to update one extrafield
if ($action == "update_extras" && !empty($permissiontoadd))
{
	$object->fetch(GETPOST('id', 'int'));

	$attributekey = GETPOST('attribute', 'alpha');
	$attributekeylong = 'options_'.$attributekey;
	$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong, ' alpha');

	$result = $object->insertExtraFields(empty($triggermodname) ? '' : $triggermodname, $user);
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
if ($action == 'confirm_delete' && !empty($permissiontodelete))
{
    if (!($object->id > 0))
    {
        dol_print_error('', 'Error, object must be fetched before being deleted');
        exit;
    }

	$result = $object->delete($user);
	if ($result > 0)
	{
		// Delete OK
		setEventMessages("RecordDeleted", null, 'mesgs');
		header("Location: ".$backurlforlist);
		exit;
	}
	else
	{
		if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
		else setEventMessages($object->error, null, 'errors');
	}
}

// Remove a line
if ($action == 'confirm_deleteline' && $confirm == 'yes' && !empty($permissiontoadd))
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
		if (!empty($newlang)) {
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

// Action validate object
if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontoadd)
{
	$result = $object->validate($user);
	if ($result >= 0)
	{
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$model = $object->modelpdf;
			$ret = $object->fetch($id); // Reload to get new records

			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action close object
if ($action == 'confirm_close' && $confirm == 'yes' && $permissiontoadd)
{
	$result = $object->cancel($user);
	if ($result >= 0)
	{
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$model = $object->modelpdf;
			$ret = $object->fetch($id); // Reload to get new records

			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action setdraft object
if ($action == 'confirm_setdraft' && $confirm == 'yes' && $permissiontoadd)
{
	$result = $object->setDraft($user);
	if ($result >= 0)
	{
		// Nothing else done
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action reopen object
if ($action == 'confirm_reopen' && $confirm == 'yes' && $permissiontoadd)
{
	$result = $object->reopen($user);
	if ($result >= 0)
	{
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$model = $object->modelpdf;
			$ret = $object->fetch($id); // Reload to get new records

			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && !empty($permissiontoadd))
{
	if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers'))
	{
		setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
	}
	else
	{
	    $objectutil = dol_clone($object, 1); // To avoid to denaturate loaded object when setting some properties for clone or if createFromClone modifies the object. We use native clone to keep this->db valid.
		//$objectutil->date = dol_mktime(12, 0, 0, GETPOST('newdatemonth', 'int'), GETPOST('newdateday', 'int'), GETPOST('newdateyear', 'int'));
        // ...
	    $result = $objectutil->createFromClone($user, (($object->id > 0) ? $object->id : $id));
	    if (is_object($result) || $result > 0)
		{
			$newid = 0;
			if (is_object($result)) $newid = $result->id;
			else $newid = $result;
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$newid); // Open record of new object
			exit;
		}
		else
		{
		    setEventMessages($objectutil->error, $objectutil->errors, 'errors');
			$action = '';
		}
	}
}
