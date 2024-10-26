<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015 Claudio Aschieri				<c.aschieri@19.coop>
 * Copyright (C) 2024		MDW								<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/core/lib/reception.lib.php
 *  \brief      Function for reception module
 *  \ingroup    reception
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Reception	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function reception_prepare_head(Reception $object)
{
	global $db, $langs, $conf, $user;

	$langs->loadLangs(array("sendings", "deliveries"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/reception/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("ReceptionCard");
	$head[$h][2] = 'reception';
	$h++;

	if ($object->statut ==  Reception::STATUS_DRAFT || ($object->statut == Reception::STATUS_VALIDATED && !getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION'))) {
		$head[$h][0] = DOL_URL_ROOT."/reception/dispatch.php?id=".$object->id;
		$head[$h][1] = $langs->trans("ReceptionDistribution");
		$head[$h][2] = 'dispatch';
		$h++;
	}

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$objectsrc = $object;
		if ($object->origin == 'supplier_order' && $object->origin_id > 0) {
			$objectsrc = new CommandeFournisseur($db);
			$objectsrc->fetch($object->origin_id);
		}
		$nbContact = count($objectsrc->liste_contact(-1, 'internal')) + count($objectsrc->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT."/reception/contact.php?id=".$object->id;
		$head[$h][1] = $langs->trans("ContactsAddresses");
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reception', 'add', 'core');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->reception->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/reception/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$nbNote = 0;
	if (!empty($object->note_private)) {
		$nbNote++;
	}
	if (!empty($object->note_public)) {
		$nbNote++;
	}
	$head[$h][0] = DOL_URL_ROOT."/reception/note.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Notes");
	if ($nbNote > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	}
	$head[$h][2] = 'note';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reception', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reception', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function reception_admin_prepare_head()
{
	global $langs, $conf, $user, $db;
	$langs->load("receptions");

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('reception');
	$extrafields->fetch_name_optionals_label('receptiondet_batch');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/reception_setup.php";
	$head[$h][1] = $langs->trans("Reception");
	$head[$h][2] = 'reception';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'reception_admin');

	if (getDolGlobalString('MAIN_SUBMODULE_RECEPTION')) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/reception_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFields");
		$nbExtrafields = $extrafields->attributes['reception']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributes_reception';
		$h++;
	}

	if (getDolGlobalString('MAIN_SUBMODULE_RECEPTION')) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/receptiondet_batch_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFieldsLines");
		$nbExtrafields = $extrafields->attributes['receptiondet_batch']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributeslines_reception';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'reception_admin', 'remove');

	return $head;
}
