<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_builddoc.inc.php
 *  \brief			Code for actions on building or deleting documents
 */


// $action must be defined
// $id must be defined
// $object must be defined and must have a method generateDocument().
// $permissiontoadd must be defined
// $upload_dir must be defined (example $conf->project->dir_output . "/";)
// $hidedetails, $hidedesc, $hideref and $moreparams may have been set or not.

if (!empty($permissioncreate) && empty($permissiontoadd)) {
	$permissiontoadd = $permissioncreate; // For backward compatibility
}

// Build doc
if ($action == 'builddoc' && ($permissiontoadd || !empty($usercangeneretedoc))) {
	if (is_numeric(GETPOST('model', 'alpha'))) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Model")), null, 'errors');
	} else {
		// Reload to get all modified line records and be ready for hooks
		$ret = $object->fetch($id);
		$ret = $object->fetch_thirdparty();
		/*if (empty($object->id) || ! $object->id > 0)
		{
			dol_print_error(null, 'Object must have been loaded by a fetch');
			exit;
		}*/

		// Save last template used to generate document
		if (GETPOST('model', 'alpha')) {
			$object->setDocModel($user, GETPOST('model', 'alpha'));
		}

		// Special case to force bank account
		//if (property_exists($object, 'fk_bank'))
		//{
		if (GETPOSTINT('fk_bank')) {
			// this field may come from an external module
			$object->fk_bank = GETPOSTINT('fk_bank');
		} elseif (!empty($object->fk_account)) {
			$object->fk_bank = $object->fk_account;
		}
		//}

		$outputlangs = $langs;
		$newlang = '';

		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($object->thirdparty->default_lang)) {
			$newlang = $object->thirdparty->default_lang; // for proposal, order, invoice, ...
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($object->default_lang)) {
			$newlang = $object->default_lang; // for thirdparty
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// To be sure vars is defined
		if (empty($hidedetails)) {
			$hidedetails = 0;
		}
		if (empty($hidedesc)) {
			$hidedesc = 0;
		}
		if (empty($hideref)) {
			$hideref = 0;
		}
		if (empty($moreparams)) {
			$moreparams = null;
		}

		$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect)) {	// This is set when include is done by bulk action "Bill Orders"
				setEventMessages($langs->trans("FileGenerated"), null);

				/*$urltoredirect = $_SERVER['REQUEST_URI'];
				$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
				$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

				header('Location: '.$urltoredirect.'#builddoc');
				exit;*/
			}
		}
	}
}

// Delete file in doc form
if ($action == 'remove_file' && $permissiontoadd) {
	if (!empty($upload_dir)) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if (empty($object->id) || !$object->id > 0) {
			// Reload to get all modified line records and be ready for hooks
			$ret = $object->fetch($id);
			$ret = $object->fetch_thirdparty();
		}

		$langs->load("other");
		$filetodelete = GETPOST('file', 'alpha');
		$file = $upload_dir.'/'.$filetodelete;
		$dirthumb = dirname($file).'/thumbs/'; // Chemin du dossier contenant la vignette (if file is an image)
		$ret = dol_delete_file($file, 0, 0, 0, $object);
		if ($ret) {
			// If it exists, remove thumb.
			$regs = array();
			if (preg_match('/(\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff)$/i', $file, $regs)) {
				$photo_vignette = basename(preg_replace('/'.$regs[0].'/i', '', $file).'_small'.$regs[0]);
				if (file_exists(dol_osencode($dirthumb.$photo_vignette))) {
					dol_delete_file($dirthumb.$photo_vignette);
				}

				$photo_vignette = basename(preg_replace('/'.$regs[0].'/i', '', $file).'_mini'.$regs[0]);
				if (file_exists(dol_osencode($dirthumb.$photo_vignette))) {
					dol_delete_file($dirthumb.$photo_vignette);
				}
			}

			setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", $filetodelete), null, 'errors');
		}

		// Make a redirect to avoid to keep the remove_file into the url that create side effects
		$urltoredirect = $_SERVER['REQUEST_URI'];
		$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
		$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

		header('Location: '.$urltoredirect);
		exit;
	} else {
		setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
	}
}
