<?php
/* Copyright (C) 2005-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Charlie BENKE        <charlie@patas-monkey.com>
 * Copyright (C) 2017-2019  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *	\file       htdocs/salaries/info.php
 *	\ingroup    salaries
 *	\brief      Page with info about salaries contribution
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "users", "salaries", "hrm"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$label = GETPOST('label', 'alphanohtml');
$projectid = (GETPOST('projectid', 'int') ? GETPOST('projectid', 'int') : GETPOST('fk_project', 'int'));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}

$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$object = new Salary($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);

	// Check current user can read this salary
	$canread = 0;
	if (!empty($user->rights->salaries->readall)) {
		$canread = 1;
	}
	if (!empty($user->rights->salaries->read) && $object->fk_user > 0 && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

restrictedArea($user, 'salaries', $object->id, 'salary', '');


/*
 * Actions
 */

// Link to a project
if ($action == 'classin' && $user->rights->banque->modifier) {
	$object->fetch($id);
	$object->setProject($projectid);
}

// set label
if ($action == 'setlabel' && $user->rights->salaries->write) {
	$object->fetch($id);
	$object->label = $label;
	$object->update($user);
}



/*
 * View
 */

if (isModEnabled('project')) $formproject = new FormProjets($db);

$title = $langs->trans('Salary')." - ".$langs->trans('Info');
$help_url = "";
llxHeader("", $title, $help_url);

$object = new Salary($db);
$object->fetch($id);
$object->info($id);

$head = salaries_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("SalaryPayment"), -1, 'salary');

$linkback = '<a href="'.DOL_URL_ROOT.'/salaries/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';

$userstatic = new User($db);
$userstatic->fetch($object->fk_user);


// Label
if ($action != 'editlabel') {
	$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->salaries->write, 'string', '', 0, 1);
	$morehtmlref .= $object->label;
} else {
	$morehtmlref .= $langs->trans('Label').' :&nbsp;';
	$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	$morehtmlref .= '<input type="hidden" name="action" value="setlabel">';
	$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	$morehtmlref .= '<input type="text" name="label" value="'.$object->label.'"/>';
	$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	$morehtmlref .= '</form>';
}

$morehtmlref .= '<br>'.$langs->trans('Employee').' : '.$userstatic->getNomUrl(-1);

// Project
if (isModEnabled('project')) {
	$morehtmlref .= '<br>'.$langs->trans('Project').' ';
	if ($user->rights->salaries->write) {
		if ($action != 'classify') {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
		}
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $formproject->select_projects(-1, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
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

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
