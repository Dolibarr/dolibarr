<?php
/* Copyright (C) 2014-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2017		Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/loan/info.php
 *	\ingroup    loan
 *	\brief      Page with info about loan
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "loan"));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'loan', $id, '', '');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Loan").' - '.$langs->trans("Info");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-loan page-card_info');

$object = new Loan($db);
$object->fetch($id);
$object->info($id);

$head = loan_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("Loan"), -1, 'money-bill-alt');

$morehtmlref = '<div class="refidno">';
// Ref loan
$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, 0, 'string', '', null, null, '', 1);
// Project
if (isModEnabled('project')) {
	$langs->load("projects");
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

$morehtmlstatus = $morehtmlright;
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

// End of page
llxFooter();
$db->close();
