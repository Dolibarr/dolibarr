<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2005       Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2011-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *       \file       htdocs/salaries/document.php
 *       \ingroup    salaries
 *       \brief      Page of linked files onto salaries
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "users", "salaries", "hrm"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

$label = GETPOST('label', 'alphanohtml');
$projectid = (GETPOSTINT('projectid') ? GETPOSTINT('projectid') : GETPOSTINT('fk_project'));

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('salarydoc', 'globalcard'));

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

$upload_dir = $conf->salaries->dir_output.'/'.dol_sanitizeFileName((string) $object->id);
$modulepart = 'salaries';

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
restrictedArea($user, 'salaries', $object->id, 'salary', '');

$permissiontoread = $user->hasRight('salaries', 'read');
$permissiontoadd = $user->hasRight('salaries', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('salaries', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_UNPAID);

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

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

$title = $langs->trans('Salary')." - ".$langs->trans('Documents');
$help_url = "";
llxHeader("", $title, $help_url);

if ($object->id) {
	$object->fetch_thirdparty();

	$head = salaries_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans("SalaryPayment"), -1, 'salary');

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

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

	print '<table class="border tableforfield centpercent">';

	print "<tr>";
	print '<td class="titlefield">' . $langs->trans("DateStartPeriod") . '</td><td>';
	print dol_print_date($object->datesp, 'day');
	print '</td></tr>';

	print "<tr>";
	print '<td>' . $langs->trans("DateEndPeriod") . '</td><td>';
	print dol_print_date($object->dateep, 'day');
	print '</td></tr>';

	print '<tr><td>' . $langs->trans("Amount") . '</td><td><span class="amount">' . price($object->amount, 0, $langs, 1, -1, -1, $conf->currency) . '</span></td></tr>';

	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';

	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.dol_print_size($totalsize, 1, 1).'</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'salaries';
	// $permissiontoadd = $permissiontoadd;
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
