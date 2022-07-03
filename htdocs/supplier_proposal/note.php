<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *	\file       htdocs/comm/propal/note.php
 *	\ingroup    propal
 *	\brief      Fiche d'information sur une proposition commerciale
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';
if (!empty($conf->project->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
// Load translation files required by the page
$langs->loadLangs(array('supplier_proposal', 'compta', 'bills'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('supplier_proposalnote'));

$result = restrictedArea($user, 'supplier_proposal', $id, 'supplier_proposal');

$object = new SupplierProposal($db);



/*
 * Actions
 */

$permissionnote = $user->rights->supplier_proposal->creer; // Used by the include of actions_setnotes.inc.php

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
$title = $langs->trans('CommRequest')." - ".$langs->trans('Notes');
$help_url = 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur';
llxHeader('', $title, $help_url);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	if ($mesg) {
		print $mesg;
	}

	$now = dol_now();

	if ($object->fetch($id, $ref)) {
		$object->fetch_thirdparty();

		$societe = new Societe($db);
		if ($societe->fetch($object->socid)) {
			$head = supplier_proposal_prepare_head($object);
			print dol_get_fiche_head($head, 'note', $langs->trans('CommRequest'), -1, 'supplier_proposal');


			// Supplier proposal card
			$linkback = '<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


			$morehtmlref = '<div class="refidno">';
			// Ref supplier
			//$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->fournisseur->commande->creer, 'string', '', 0, 1);
			//$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->fournisseur->commande->creer, 'string', '', null, null, '', 1);
			// Thirdparty
			$morehtmlref .= $langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
			// Project
			if (!empty($conf->project->enabled)) {
				$langs->load("projects");
				$morehtmlref .= '<br>'.$langs->trans('Project').' ';
				if ($user->rights->supplier_proposal->creer) {
					if ($action != 'classify') {
						//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
						$morehtmlref .= ' : ';
					}
					if ($action == 'classify') {
						//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
						$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
						$morehtmlref .= '<input type="hidden" name="action" value="classin">';
						$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
						$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
						$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
						$morehtmlref .= '</form>';
					} else {
						$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
					}
				} else {
					if (!empty($object->fk_project)) {
						$proj = new Project($db);
						$proj->fetch($object->fk_project);
						$morehtmlref .= ' : '.$proj->getNomUrl(1);
						if ($proj->title) {
							$morehtmlref .= ' - '.$proj->title;
						}
					} else {
						$morehtmlref .= '';
					}
				}
			}
			$morehtmlref .= '</div>';


			dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';

			$cssclass = "titlefield";
			include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

			print '</div>';

			print dol_get_fiche_end();
		}
	}
}

// End of page
llxFooter();
$db->close();
