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
// $noback may be defined
// $triggermodname may be defined

$hidedetails = isset($hidedetails) ? $hidedetails : '';
$hidedesc = isset($hidedesc) ? $hidedesc : '';
$hideref = isset($hideref) ? $hideref : '';


if (!empty($permissionedit) && empty($permissiontoadd)) {
	$permissiontoadd = $permissionedit; // For backward compatibility
}

if ($cancel) {
	/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
}


// Action to add record
if ($action == 'add' && !empty($permissiontoadd)) {
	foreach ($object->fields as $key => $val) {
		if ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour') == '' && GETPOST($key.'min') == '') {
				continue; // The field was not submited to be saved
			}
		} else {
			if (!GETPOSTISSET($key)) {
				continue; // The field was not submited to be saved
			}
		}
		// Ignore special fields
		if (in_array($key, array('rowid', 'entity', 'import_key'))) {
			continue;
		}
		if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
			if (!in_array(abs($val['visible']), array(1, 3))) {
				continue; // Only 1 and 3 that are case to create
			}
		}

		// Set value to insert
		if (in_array($object->fields[$key]['type'], array('text', 'html'))) {
			$value = GETPOST($key, 'restricthtml');
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int')); // for date without hour, we use gmt
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($object->fields[$key]['type'] == 'duration') {
			$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key) == '1' || GETPOST($key) == 'on') ? 1 : 0);
		} elseif ($object->fields[$key]['type'] == 'reference') {
			$tmparraykey = array_keys($object->param_list);
			$value = $tmparraykey[GETPOST($key)].','.GETPOST($key.'2');
		} else {
			if ($key == 'lang') {
				$value = GETPOST($key, 'aZ09') ?GETPOST($key, 'aZ09') : "";
			} else {
				$value = GETPOST($key, 'alphanohtml');
			}
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		//var_dump($key.' '.$value.' '.$object->fields[$key]['type']);
		$object->$key = $value;
		if (!empty($val['notnull']) && $val['notnull'] > 0 && $object->$key == '' && isset($val['default']) && $val['default'] == '(PROV)') {
			$object->$key = '(PROV)';
		}
		if (!empty($val['notnull']) && $val['notnull'] > 0 && $object->$key == '' && !isset($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}

		// Validation of fields values
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 || !empty($conf->global->MAIN_ACTIVATE_VALIDATION_RESULT)) {
			if (!$error && !empty($val['validate']) && is_callable(array($object, 'validateField'))) {
				if (!$object->validateField($object->fields, $key, $value)) {
					$error++;
				}
			}
		}
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '', 1);
		if ($ret < 0) {
			$error++;
		}
	}

	if (!$error) {
		$result = $object->create($user);
		if ($result > 0) {
			// Creation OK
			if (isModEnabled('categorie') && method_exists($object, 'setCategories')) {
				$categories = GETPOST('categories', 'array:int');
				$object->setCategories($categories);
			}
			$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation

			if (empty($noback)) {
				header("Location: " . $urltogo);
				exit;
			}
		} else {
			$error++;
			// Creation KO
			if (!empty($object->errors)) {
				setEventMessages(null, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, null, 'errors');
			}
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}

// Action to update record
if ($action == 'update' && !empty($permissiontoadd)) {
	foreach ($object->fields as $key => $val) {
		// Check if field was submited to be edited
		if ($object->fields[$key]['type'] == 'duration') {
			if (!GETPOSTISSET($key.'hour') || !GETPOSTISSET($key.'min')) {
				continue; // The field was not submited to be saved
			}
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			if (!GETPOSTISSET($key)) {
				$object->$key = 0; // use 0 instead null if the field is defined as not null
				continue;
			}
		} else {
			if (!GETPOSTISSET($key)) {
				continue; // The field was not submited to be saved
			}
		}
		// Ignore special fields
		if (in_array($key, array('rowid', 'entity', 'import_key'))) {
			continue;
		}
		if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
			if (!in_array(abs($val['visible']), array(1, 3, 4))) {
				continue; // Only 1 and 3 and 4, that are cases to update
			}
		}

		// Set value to update
		if (preg_match('/^(text|html)/', $object->fields[$key]['type'])) {
			$tmparray = explode(':', $object->fields[$key]['type']);
			if (!empty($tmparray[1])) {
				$value = GETPOST($key, $tmparray[1]);
			} else {
				$value = GETPOST($key, 'restricthtml');
			}
		} elseif ($object->fields[$key]['type'] == 'date') {
			$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int')); // for date without hour, we use gmt
		} elseif ($object->fields[$key]['type'] == 'datetime') {
			$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), GETPOST($key.'sec', 'int'), GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'), 'tzuserrel');
		} elseif ($object->fields[$key]['type'] == 'duration') {
			if (GETPOST($key.'hour', 'int') != '' || GETPOST($key.'min', 'int') != '') {
				$value = 60 * 60 * GETPOST($key.'hour', 'int') + 60 * GETPOST($key.'min', 'int');
			} else {
				$value = '';
			}
		} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
			$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
		} elseif ($object->fields[$key]['type'] == 'boolean') {
			$value = ((GETPOST($key, 'aZ09') == 'on' || GETPOST($key, 'aZ09') == '1') ? 1 : 0);
		} elseif ($object->fields[$key]['type'] == 'reference') {
			$value = array_keys($object->param_list)[GETPOST($key)].','.GETPOST($key.'2');
		} else {
			if ($key == 'lang') {
				$value = GETPOST($key, 'aZ09');
			} else {
				$value = GETPOST($key, 'alphanohtml');
			}
		}
		if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
			$value = ''; // This is an implicit foreign key field
		}
		if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
			$value = ''; // This is an explicit foreign key field
		}

		$object->$key = $value;
		if ($val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
		}

		// Validation of fields values
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 || !empty($conf->global->MAIN_ACTIVATE_VALIDATION_RESULT)) {
			if (!$error && !empty($val['validate']) && is_callable(array($object, 'validateField'))) {
				if (!$object->validateField($object->fields, $key, $value)) {
					$error++;
				}
			}
		}

		if (isModEnabled('categorie')) {
			$categories = GETPOST('categories', 'array');
			if (method_exists($object, 'setCategories')) {
				$object->setCategories($categories);
			}
		}
	}

	// Fill array 'array_options' with data from add form
	if (!$error) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}
	}

	if (!$error) {
		$result = $object->update($user);
		if ($result > 0) {
			$action = 'view';
			$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
			if ($urltogo && !$noback) {
				header("Location: " . $urltogo);
				exit;
			}
		} else {
			$error++;
			// Creation KO
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit';
		}
	} else {
		$action = 'edit';
	}
}

// Action to update one extrafield
if ($action == "update_extras" && !empty($permissiontoadd)) {
	$object->fetch(GETPOST('id', 'int'));

	$attributekey = GETPOST('attribute', 'alpha');
	$attributekeylong = 'options_'.$attributekey;

	if (GETPOSTISSET($attributekeylong.'day') && GETPOSTISSET($attributekeylong.'month') && GETPOSTISSET($attributekeylong.'year')) {
		// This is properties of a date
		$object->array_options['options_'.$attributekey] = dol_mktime(GETPOST($attributekeylong.'hour', 'int'), GETPOST($attributekeylong.'min', 'int'), GETPOST($attributekeylong.'sec', 'int'), GETPOST($attributekeylong.'month', 'int'), GETPOST($attributekeylong.'day', 'int'), GETPOST($attributekeylong.'year', 'int'));
		//var_dump(dol_print_date($object->array_options['options_'.$attributekey]));exit;
	} else {
		$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong, 'alpha');
	}

	$result = $object->insertExtraFields(empty($triggermodname) ? '' : $triggermodname, $user);
	if ($result > 0) {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = 'view';
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'edit_extras';
	}
}

// Action to delete
if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
	if (!($object->id > 0)) {
		dol_print_error('', 'Error, object must be fetched before being deleted');
		exit;
	}

	$result = $object->delete($user);

	if ($result > 0) {
		// Delete OK
		setEventMessages("RecordDeleted", null, 'mesgs');

		if (empty($noback)) {
			header("Location: " . $backurlforlist);
			exit;
		}
	} else {
		$error++;
		if (!empty($object->errors)) {
			setEventMessages(null, $object->errors, 'errors');
		} else {
			setEventMessages($object->error, null, 'errors');
		}
	}

	$action = '';
}

// Remove a line
if ($action == 'confirm_deleteline' && $confirm == 'yes' && !empty($permissiontoadd)) {
	if (method_exists($object, 'deleteline')) {
		$result = $object->deleteline($user, $lineid); // For backward compatibility
	} else {
		$result = $object->deleteLine($user, $lineid);
	}
	if ($result > 0) {
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && is_object($object->thirdparty)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			if (method_exists($object, 'generateDocument')) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');

		if (empty($noback)) {
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
			exit;
		}
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action validate object
if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->validate($user);
	if ($result >= 0) {
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			if (method_exists($object, 'generateDocument')) {
				$outputlangs = $langs;
				$newlang = '';
				if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret = $object->fetch($id); // Reload to get new records

				$model = $object->model_pdf;

				$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($retgen < 0) {
					setEventMessages($object->error, $object->errors, 'warnings');
				}
			}
		}
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action close object
if ($action == 'confirm_close' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->cancel($user);
	if ($result >= 0) {
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			if (method_exists($object, 'generateDocument')) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action setdraft object
if ($action == 'confirm_setdraft' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->setDraft($user);
	if ($result >= 0) {
		// Nothing else done
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action reopen object
if ($action == 'confirm_reopen' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->reopen($user);
	if ($result >= 0) {
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			if (method_exists($object, 'generateDocument')) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && !empty($permissiontoadd)) {
	if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers')) {
		setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
	} else {
		$objectutil = dol_clone($object, 1); // To avoid to denaturate loaded object when setting some properties for clone or if createFromClone modifies the object. We use native clone to keep this->db valid.
		//$objectutil->date = dol_mktime(12, 0, 0, GETPOST('newdatemonth', 'int'), GETPOST('newdateday', 'int'), GETPOST('newdateyear', 'int'));
		// ...
		$result = $objectutil->createFromClone($user, (($object->id > 0) ? $object->id : $id));
		if (is_object($result) || $result > 0) {
			$newid = 0;
			if (is_object($result)) {
				$newid = $result->id;
			} else {
				$newid = $result;
			}

			if (empty($noback)) {
				header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $newid); // Open record of new object
				exit;
			}
		} else {
			$error++;
			setEventMessages($objectutil->error, $objectutil->errors, 'errors');
			$action = '';
		}
	}
}
