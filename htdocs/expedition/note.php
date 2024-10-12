<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013 	   Florian Henry        <florian.henry@open-concept.pro>
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
 */

/**
 *      \file       htdocs/expedition/note.php
 *      \ingroup    expedition
 *      \brief      Note card expedition
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('sendings', 'companies', 'bills', 'deliveries', 'orders', 'stocks', 'other', 'propal'));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Expedition($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	if (!empty($object->origin)) {
		$typeobject = $object->origin;
		$origin = $object->origin;
		$object->fetch_origin();
	}

	// Linked documents
	if ($typeobject == 'commande' && $object->origin_object->id && isModEnabled('order')) {
		$objectsrc = new Commande($db);
		$objectsrc->fetch($object->origin_object->id);
	}
	if ($typeobject == 'propal' && $object->origin_object->id && isModEnabled("propal")) {
		$objectsrc = new Propal($db);
		$objectsrc->fetch($object->origin_object->id);
	}

	$upload_dir = $conf->expedition->dir_output."/sending/".dol_sanitizeFileName($object->ref);
}

$permissionnote = $user->hasRight('expedition', 'creer'); // Used by the include of actions_setnotes.inc.php

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

$hookmanager->initHooks(array('expeditionnote'));
$result = restrictedArea($user, 'expedition', $object->id, '');


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-expedition page-card_note');

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$head = shipping_prepare_head($object);
	print dol_get_fiche_head($head, 'note', $langs->trans("Shipment"), -1, $object->picto);


	// Shipment card
	$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer shipment
	$morehtmlref .= $form->editfieldkey("RefCustomer", '', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", '', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {	// Do not change on shipment
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $objectsrc->socid, $objectsrc->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($objectsrc) && !empty($objectsrc->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($objectsrc->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="underbanner clearboth"></div>';

	$cssclass = 'titlefield';
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
