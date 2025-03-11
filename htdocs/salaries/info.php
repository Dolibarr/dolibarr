<?php
/* Copyright (C) 2005-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Charlie BENKE        <charlie@patas-monkey.com>
 * Copyright (C) 2017-2023  Alexandre Spangaro   <aspangaro@easya.solutions>
 * Copyright (C) 2021       Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "users", "salaries", "hrm"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$label = GETPOST('label', 'alphanohtml');
$projectid = (GETPOSTINT('projectid') ? GETPOSTINT('projectid') : GETPOSTINT('fk_project'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}

$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('salaryinfo', 'globalcard'));

$object = new Salary($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);

	// Check current user can read this salary
	$canread = 0;
	if ($user->hasRight('salaries', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('salaries', 'read') && $object->fk_user > 0 && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

restrictedArea($user, 'salaries', $object->id, 'salary', '');

$permissiontoread = $user->hasRight('salaries', 'read');
$permissiontoadd = $user->hasRight('salaries', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('salaries', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_UNPAID);


/*
 * Actions
 */

// Link to a project
if ($action == 'classin' && $permissiontoadd) {
	$object->fetch($id);
	$object->setProject($projectid);
}

// set label
if ($action == 'setlabel' && $permissiontoadd) {
	$object->fetch($id);
	$object->label = $label;
	$object->update($user);
}



/*
 * View
 */

$form = new Form($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $langs->trans('Salary')." - ".$langs->trans('Info');
$help_url = "";
llxHeader("", $title, $help_url);

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
	$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $permissiontoadd, 'string', '', 0, 1);
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

$usercancreate = $permissiontoadd;

// Project
if (isModEnabled('project')) {
	$langs->load("projects");
	$morehtmlref .= '<br>';
	if ($usercancreate) {
		$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
		if ($action != 'classify') {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
		}
		$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, -1, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= $proj->getNomUrl(1);
			if ($proj->title) {
				$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
			}
		}
	}
}

$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table class="centpercent"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
