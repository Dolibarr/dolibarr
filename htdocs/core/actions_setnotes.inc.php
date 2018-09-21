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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
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
if ($action == 'setnote_public' && ! empty($permissionnote) && ! GETPOST('cancel','alpha'))
{
	if (empty($action) || ! is_object($object) || empty($id)) dol_print_error('','Include of actions_setnotes.inc.php was done but required variable was not set before');
	if (empty($object->id)) $object->fetch($id);	// Fetch may not be already done
	
	$result_update=$object->update_note(dol_html_entity_decode(GETPOST('note_public', 'none'), ENT_QUOTES),'_public');

	if ($result_update < 0) setEventMessages($object->error, $object->errors, 'errors');
	elseif (in_array($object->table_element, array('supplier_proposal', 'propal', 'commande_fournisseur', 'commande', 'facture_fourn', 'facture')))
	{
		// Define output language
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$model=$object->modelpdf;
			$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
			$hidedesc = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
			$hideref = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

			$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			
			if ($result < 0) dol_print_error($db,$result);
		}
	}
}
// Set public note
else if ($action == 'setnote_private' && ! empty($permissionnote) && ! GETPOST('cancel','alpha'))
{
	if (empty($action) || ! is_object($object) || empty($id)) dol_print_error('','Include of actions_setnotes.inc.php was done but required variable was not set before');
	if (empty($object->id)) $object->fetch($id);	// Fetch may not be already done
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private', 'none'), ENT_QUOTES),'_private');
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}
