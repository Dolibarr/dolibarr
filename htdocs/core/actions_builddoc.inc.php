<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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

/**
 * @var Conf $conf
 * @var Translate $langs
 * @var User $user
 * @var CommonObject|Societe $object
 *
 * @var string $action
 * @var int $id
 * @var ?int $permissioncreate
 * @var ?int $usercangeneratedoc
 * @var int $permissiontoadd
 * @var string $upload_dir
 * @var int $donotredirect
 *
 * @var ?int $hidedetails
 * @var ?int $hidedesc
 * @var ?int $hideref
 * @var ?array<string,mixed> $moreparams
 */
'
@phan-var-force int $id
@phan-var-force int $permissiontoadd
@phan-var-force ?int $hidedetails
@phan-var-force ?int $hidedesc
@phan-var-force ?int $hideref
@phan-var-force ?array<string,mixed> $moreparams
@phan-var-force CommonObject|Societe $object
@phan-var-force int $id
';

if (!empty($permissioncreate) && empty($permissiontoadd)) {
	$permissiontoadd = $permissioncreate; // For backward compatibility
}

// Build doc
if ($action == 'builddoc' && ($permissiontoadd || !empty($usercangeneratedoc))) {
	if (is_numeric(GETPOST('model', 'alpha'))) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Model")), null, 'errors');
	} else {
		// Reload to get all modified line records and be ready for hooks
		$ret = $object->fetch($id);
		$ret = $object->fetch_thirdparty();

		// Save last template used to generate document
		if (GETPOST('model', 'alpha')) {
			$object->setDocModel($user, GETPOST('model', 'alpha'));
		}

		// Special case to force bank account
		if (GETPOSTINT('fk_bank')) {
			// this field may come from an external module
			$object->fk_bank = GETPOSTINT('fk_bank');	// For compatibility
			$object->fk_account = GETPOSTINT('fk_bank');
		} elseif (!empty($object->fk_account)) {
			$object->fk_bank = $object->fk_account;		// For compatibility
		}

		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') /* && empty($newlang) */ && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($object->thirdparty->default_lang)) {
			$newlang = $object->thirdparty->default_lang; // for proposal, order, invoice, ...
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && property_exists($object, 'default_lang') && empty($newlang) && isset($object->default_lang)) {
			$newlang = $object->default_lang; // for thirdparty
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// To be sure vars is defined
		$hidedetails = isset($hidedetails) ? $hidedetails : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0);  // @phan-suppress-current-line PhanPluginUndeclaredVariableIsset
		$hidedesc = isset($hidedesc) ? $hidedesc : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0);  // @phan-suppress-current-line PhanPluginUndeclaredVariableIsset
		$hideref = isset($hideref) ? $hideref : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0);  // @phan-suppress-current-line PhanPluginUndeclaredVariableIsset
		$moreparams = isset($moreparams) ? $moreparams : null;
		// Choice made on the generation form to append or not the terms of sale file
		if (GETPOSTISSET('addtermsofsale_shown')) {
			if (!is_array($moreparams)) {
				$moreparams = array();
			}
			$moreparams['addtermsofsale'] = (GETPOSTINT('addtermsofsale') ? 1 : 0);
		}

		$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			setEventMessages($object->warning, $object->warnings, 'warnings');
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

		// Security: $upload_dir is often the root directory of the module, so we check the file is inside the directory of the current object.
		// Define the possible subdirectories (relative to $upload_dir) of the object, depending on how the documents of the object are stored.
		$objectref = dol_sanitizeFileName((string) $object->ref);
		$objectid = (string) ((int) $object->id);
		$arrayofobjectsubdir = array();
		if ($object->element == 'societe') {
			// Thirdparty: the ref is the company name (not unique), so the directory is the id
			$arrayofobjectsubdir[] = $objectid;
		} elseif ($object->element == 'invoice_supplier') {
			// Supplier invoice: the directory is a tree built from the id followed by the ref (example: 6/1/SI2210-0013)
			$arrayofobjectsubdir[] = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$objectref;
		} else {
			// General case: <ref> (invoice, order, expense report...) or <element>/<ref> (objects generated by the ModuleBuilder)
			if ($objectref !== '') {
				$arrayofobjectsubdir[] = $objectref;
				$arrayofobjectsubdir[] = $object->element.'/'.$objectref;
			}
			if (in_array($object->element, array('action', 'don'))) {
				// Event and donation: the directory is the id
				$arrayofobjectsubdir[] = $objectid;
			}
		}
		$subdiroffile = dirname($filetodelete);
		$isfileofobject = false;
		if (!empty($object->id)) {
			foreach ($arrayofobjectsubdir as $objectsubdir) {
				if ($subdiroffile === $objectsubdir) {
					$isfileofobject = true;
				} elseif ($subdiroffile === '.' && preg_match('/\/'.preg_quote($objectsubdir, '/').'$/', rtrim($upload_dir, '/'))) {
					// No subdirectory in file name, so $upload_dir must already be the directory of the object
					$isfileofobject = true;
				}
			}
		}
		if (!$isfileofobject) {
			dol_syslog("actions_builddoc.inc.php: Try to remove file ".$filetodelete." that is not inside the directory of object id ".$object->id, LOG_WARNING);
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
		}

		$file = $upload_dir.'/'.$filetodelete;
		$dirthumb = dirname($file).'/thumbs/'; // Path to the folder containing the thumbnail (if file is an image)
		$ret = ($isfileofobject ? dol_delete_file($file, 1, 0, 0, $object) : false);
		if ($ret) {
			// If it exists, remove thumb.
			$regs = array();
			if (preg_match('/(\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff|\.webp|\.xpm|\.xbm|\.avif)$/i', $file, $regs)) {
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
			$langs->load("errors");
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
