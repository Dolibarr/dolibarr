<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
 * Copyright (C) 2016-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
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
 *   \file       htdocs/loan/note.php
 *   \ingroup    loan
 *   \brief      Tab for notes on loan
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$action = GETPOST('action', 'aZ09');

// Load translation files required by the page
$langs->loadLangs(array("loan"));

// Security check
$id = GETPOST('id', 'int');

$hookmanager->initHooks(array('loannote'));
$result = restrictedArea($user, 'loan', $id, '&loan');

$object = new Loan($db);
if ($id > 0) {
	$object->fetch($id);
}

$permissionnote = $user->rights->loan->write; // Used by the include of actions_setnotes.inc.php


/*
 *  Actions
 */


$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once
}


/*
 *  View
 */

$form = new Form($db);

$title = $langs->trans("Loan").' - '.$langs->trans("Notes");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("", $title, $help_url);

if ($id > 0) {
	/*
	 * Affichage onglets
	 */
	$totalpaid = $object->getSumPayment();

	$head = loan_prepare_head($object);

	print dol_get_fiche_head($head, 'note', $langs->trans("Loan"), -1, 'money-bill-alt');

	$morehtmlref = '<div class="refidno">';
	// Ref loan
	$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, 0, 'string', '', null, null, '', 1);
	// Project
	if (isModEnabled('project')) {
		$langs->loadLangs(array("projects"));
		$morehtmlref .= '<br>'.$langs->trans('Project').' : ';
		if ($user->hasRight('loan', 'write')) {
			//if ($action != 'classify')
			//	$morehtmlref .= '<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			if ($action == 'classify') {
				// $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
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

	$linkback = '<a href="'.DOL_URL_ROOT.'/loan/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$cssclass = 'titlefield';
	$permission = $user->rights->loan->write; // Used by the include of notes.tpl.php
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
