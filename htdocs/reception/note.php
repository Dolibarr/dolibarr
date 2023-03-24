<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/reception/note.php
 *      \ingroup    receptionsending
 *      \brief      Note card reception
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
if (!empty($conf->project->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

$langs->loadLangs(array("receptions", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal'));

$id = (GETPOST('id', 'int') ?GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Reception($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	if (!empty($object->origin)) {
		$origin = $object->origin;

		$object->fetch_origin();
		$typeobject = $object->origin;
	}

	// Linked documents
	if ($origin == 'order_supplier' && $object->$typeobject->id && (isModEnabled("fournisseur") && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || isModEnabled("supplier_order"))) {
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($object->$typeobject->id);
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('receptionnote'));

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}

if (isModEnabled("reception")) {
	$permissiontoread = $user->rights->reception->lire;
	$permissiontoadd = $user->rights->reception->creer;
	$permissiondellink = $user->rights->reception->creer; // Used by the include of actions_dellink.inc.php
	$permissiontovalidate = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->creer)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->reception_advance->validate)));
	$permissiontodelete = $user->rights->reception->supprimer;
} else {
	$permissiontoread = $user->rights->fournisseur->commande->receptionner;
	$permissiontoadd = $user->rights->fournisseur->commande->receptionner;
	$permissiondellink = $user->rights->fournisseur->commande->receptionner; // Used by the include of actions_dellink.inc.php
	$permissiontovalidate = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->commande->receptionner)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->commande_advance->check)));
	$permissiontodelete = $user->rights->fournisseur->commande->receptionner;
}
$permissionnote = $user->rights->reception->creer; // Used by the include of actions_setnotes.inc.php

if ($origin == 'reception') {
	$result = restrictedArea($user, $origin, $object->id);
} else {
	if ($origin == 'reception') {
		$result = restrictedArea($user, $origin, $object->id);
	} else {
		if ($origin == 'supplierorder' || $origin == 'order_supplier') {
			$result = restrictedArea($user, 'fournisseur', $origin_id, 'commande_fournisseur', 'commande');
		} elseif (empty($user->rights->{$origin}->lire) && empty($user->rights->{$origin}->read)) {
			accessforbidden();
		}
	}
}



/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once
}


/*
 * View
 */

llxHeader('', $langs->trans('Reception'));

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$head = reception_prepare_head($object);
	print dol_get_fiche_head($head, 'note', $langs->trans("Reception"), -1, 'dollyrevert');


	// Reception card
	$linkback = '<a href="'.DOL_URL_ROOT.'/reception/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer reception
	$morehtmlref .= $form->editfieldkey("RefSupplier", '', $object->ref_supplier, $object, $user->rights->reception->creer, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", '', $object->ref_supplier, $object, $user->rights->reception->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (!empty($conf->project->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {    // Do not change on reception
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify' && $permissiontoadd) {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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


llxFooter();

$db->close();
