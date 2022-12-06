<?php
/* Copyright (C) 2014-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_setmoduleoptions.inc.php
 *  \brief			Code for actions on setting notes of object page
 */

// $error must have been initialized to 0
// $action must be defined
// $arrayofparameters must be set for action 'update'
// $nomessageinupdate can be set to 1
// $nomessageinsetmoduleoptions can be set to 1
// $formSetup may be defined


if ($action == 'update' && !empty($formSetup) && is_object($formSetup) && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
	return;
}


if ($action == 'update' && is_array($arrayofparameters) && !empty($user->admin)) {
	$db->begin();

	foreach ($arrayofparameters as $key => $val) {
		// Modify constant only if key was posted (avoid resetting key to the null value)
		if (GETPOSTISSET($key)) {
			if (preg_match('/category:/', $val['type'])) {
				if (GETPOST($key, 'int') == '-1') {
					$val_const = '';
				} else {
					$val_const = GETPOST($key, 'int');
				}
			} else {
				$val_const = GETPOST($key, 'alpha');
			}

			$result = dolibarr_set_const($db, $key, $val_const, 'chaine', 0, '', $conf->entity);
			if ($result < 0) {
				$error++;
				break;
			}
		}
	}

	if (!$error) {
		$db->commit();
		if (empty($nomessageinupdate)) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		}
	} else {
		$db->rollback();
		if (empty($nomessageinupdate)) {
			setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
		}
	}
}

if ($action == 'deletefile' && $modulepart == 'doctemplates' && !empty($user->admin)) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$keyforuploaddir = GETPOST('keyforuploaddir', 'aZ09');

	$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($keyforuploaddir))));
	foreach ($listofdir as $key => $tmpdir) {
		$tmpdir = preg_replace('/DOL_DATA_ROOT\/*/', '', $tmpdir);	// Clean string if we found a hardcoded DOL_DATA_ROOT
		if (!$tmpdir) {
			unset($listofdir[$key]);
			continue;
		}
		$tmpdir = DOL_DATA_ROOT.'/'.$tmpdir;	// Complete with DOL_DATA_ROOT. Only files into DOL_DATA_ROOT can be reach/set
		if (!is_dir($tmpdir)) {
			if (empty($nomessageinsetmoduleoptions)) {
				setEventMessages($langs->trans("ErrorDirNotFound", $tmpdir), null, 'warnings');
			}
		} else {
			$upload_dir = $tmpdir;
			break;	// So we take the first directory found into setup $conf->global->$keyforuploaddir
		}
	}

	$filetodelete = $tmpdir.'/'.GETPOST('file');
	$result = dol_delete_file($filetodelete);
	if ($result > 0) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
	}
}

// Define constants for submodules that contains parameters (forms with param1, param2, ... and value1, value2, ...)
if ($action == 'setModuleOptions' && !empty($user->admin)) {
	$db->begin();

	// Process common param fields
	if (is_array($_POST)) {
		foreach ($_POST as $key => $val) {
			$reg = array();
			if (preg_match('/^param(\d*)$/', $key, $reg)) {    // Works for POST['param'], POST['param1'], POST['param2'], ...
				$param = GETPOST("param".$reg[1], 'alpha');
				$value = GETPOST("value".$reg[1], 'alpha');
				if ($param) {
					$res = dolibarr_set_const($db, $param, $value, 'chaine', 0, '', $conf->entity);
					if (!($res > 0)) {
						$error++;
					}
				}
			}
		}
	}

	// Process upload fields
	if (GETPOST('upload', 'alpha') && GETPOST('keyforuploaddir', 'aZ09')) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$keyforuploaddir = GETPOST('keyforuploaddir', 'aZ09');
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($keyforuploaddir))));
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT\/*/', '', $tmpdir);	// Clean string if we found a hardcoded DOL_DATA_ROOT
			if (!$tmpdir) {
				unset($listofdir[$key]);
				continue;
			}
			$tmpdir = DOL_DATA_ROOT.'/'.$tmpdir;	// Complete with DOL_DATA_ROOT. Only files into DOL_DATA_ROOT can be reach/set
			if (!is_dir($tmpdir)) {
				if (empty($nomessageinsetmoduleoptions)) {
					setEventMessages($langs->trans("ErrorDirNotFound", $tmpdir), null, 'warnings');
				}
			} else {
				$upload_dir = $tmpdir;
				break;	// So we take the first directory found into setup $conf->global->$keyforuploaddir
			}
		}
		if ($upload_dir) {
			$result = dol_add_file_process($upload_dir, 1, 1, 'uploadfile', '');
			if ($result <= 0) {
				$error++;
			}
		}
	}

	if (!$error) {
		$db->commit();
		if (empty($nomessageinsetmoduleoptions)) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		}
	} else {
		$db->rollback();
		if (empty($nomessageinsetmoduleoptions)) {
			setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
		}
	}
}
