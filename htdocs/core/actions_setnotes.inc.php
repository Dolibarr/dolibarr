<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_setnotes.inc.php
 *  \brief			Code for actions on setting notes of object page
 */


// $action must be defined
// $permissionnote must be defined to permission to edit object
// $object must be defined (object is loaded in this file with fetch)
// $id must be defined (object is loaded in this file with fetch)

// Set public note
if ($action == 'setnote_public' && !empty($permissionnote) && !GETPOST('cancel', 'alpha')) {
	if (empty($action) || !is_object($object) || empty($id)) {
		dol_print_error(null, 'Include of actions_setnotes.inc.php was done but required variable was not set before');
	}
	if (empty($object->id)) {
		$object->fetch($id); // Fetch may not be already done
	}

	$result_update = $object->update_note(dol_html_entity_decode(GETPOST('note_public', 'restricthtml'), ENT_QUOTES | ENT_HTML5, 'UTF-8', 1), '_public');

	if ($result_update < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} elseif (in_array($object->table_element, array('supplier_proposal', 'propal', 'commande_fournisseur', 'commande', 'facture_fourn', 'facture'))) {
		// Define output language
		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			$outputlangs = $langs;
			$newlang = '';
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
				if (!is_object($object->thirdparty)) {
					$object->fetch_thirdparty();
				}
				$newlang = $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$model = $object->model_pdf;
			$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
			$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
			$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

			//see #21072: Update a public note with a "document model not found" is not really a problem : the PDF is not created/updated
			//but the note is saved, so just add a notification will be enough
			$resultGenDoc = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($resultGenDoc < 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}

			if ($result < 0) {
				dol_print_error($db, $result);
			}
		}
	}
} elseif ($action == 'setnote_private' && !empty($permissionnote) && !GETPOST('cancel', 'alpha')) {	// Set public note
	if (empty($user->socid)) {
		// Private notes (always hidden to external users)
		if (empty($action) || !is_object($object) || empty($id)) {
			dol_print_error(null, 'Include of actions_setnotes.inc.php was done but required variable was not set before');
		}
		if (empty($object->id)) {
			$object->fetch($id); // Fetch may not be already done
		}
		$result = $object->update_note(dol_html_entity_decode(GETPOST('note_private', 'restricthtml'), ENT_QUOTES | ENT_HTML5, 'UTF-8', 1), '_private');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}
