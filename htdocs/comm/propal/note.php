<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
if (!empty($conf->project->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('propal', 'compta', 'bills', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$now = dol_now();

$object = new Propal($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
	$object->id = $user->socid;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('propalnote'));

restrictedArea($user, 'propal', $object->id, 'propal');


/*
 * Actions
 */

$permissionnote = $user->rights->propale->creer; // Used by the include of actions_setnotes.inc.php

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

$form = new Form($db);

$title = $langs->trans('Proposal')." - ".$langs->trans('Notes');
$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
llxHeader('', $title, $help_url);

if ($object->id > 0) {
	if ($object->fetch_thirdparty() > 0) {
		$head = propal_prepare_head($object);
		print dol_get_fiche_head($head, 'note', $langs->trans('Proposal'), -1, 'propal');

		$cssclass = 'titlefield';
		//if ($action == 'editnote_public') $cssclass='titlefieldcreate';
		//if ($action == 'editnote_private') $cssclass='titlefieldcreate';


		// Proposal card

		$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
		// Project
		if (!empty($conf->project->enabled)) {
			$langs->load("projects");
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if ($user->rights->propal->creer) {
				if ($action != 'classify') {
					//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
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

// End of page
llxFooter();
$db->close();
