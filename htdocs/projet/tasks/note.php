<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@inodbox.com>
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
 *	\file       htdocs/projet/tasks/note.php
 *	\ingroup    project
 *	\brief      Page to show information on a task
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

// Load translation files required by the page
$langs->load('projects');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$mine = GETPOST('mode') == 'mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$withproject = GETPOSTINT('withproject');
$project_ref = GETPOST('project_ref', 'alpha');

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}

$hookmanager->initHooks(array('projettasknote'));


$object = new Task($db);
$projectstatic = new Project($db);

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_TASK') && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$projectstatic->fetch($object->fk_project);
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
			$projectstatic->fetchComments();
		}
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}

		$object->project = clone $projectstatic;
	} else {
		dol_print_error($db);
	}
}


// Retrieve First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject)) {
	if ($projectstatic->fetch(0, $project_ref) > 0) {
		$tasksarray = $object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0) {
			$id = $tasksarray[0]->id;
			$object->fetch($id);
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode) ? '' : '&mode='.$mode));
		}
	}
}

if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}

//$result = restrictedArea($user, 'projet', $id, '', 'task'); // TODO ameliorer la verification
restrictedArea($user, 'projet', $object->fk_project, 'projet&project');

$permissionnote = ($user->hasRight('projet', 'creer') || $user->hasRight('projet', 'all', 'creer'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'
}


/*
 * View
 */
$form = new Form($db);
$userstatic = new User($db);

$now = dol_now();

$title = $object->ref . ' - ' . $langs->trans("Notes");
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': '.$projectstatic->ref : '')  ;
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-project project-tasks page-task_note');

if ($object->id > 0) {
	$userWrite = $projectstatic->restrictedProjectArea($user, 'write');

	if (!empty($withproject)) {
		// Tabs for project
		$tab = 'tasks';
		$head = project_prepare_head($projectstatic);
		print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

		$param = (isset($mode) && $mode == 'mine' ? '&mode=mine' : '');
		// Project card

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $projectstatic->title;
		// Thirdparty
		if (isset($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (!$user->hasRight('projet', 'all', 'lire')) {
			$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
			$projectstatic->next_prev_filter = "rowid IN (".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0').")";
		}

		dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Usage
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
			print '<tr><td class="tdtop">';
			print $langs->trans("Usage");
			print '</td>';
			print '<td>';
			if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
				print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
				print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
				print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
				print '<br>';
			}
			if (isModEnabled('eventorganization')) {
				print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("EventOrganizationDescriptionLong");
				print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
			}
			print '</td></tr>';
		}

		// Visibility
		print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
		if ($projectstatic->public) {
			print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
			print $langs->trans('SharedProject');
		} else {
			print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
			print $langs->trans('PrivateProject');
		}
		print '</td></tr>';

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (isset($projectstatic->budget_amount) && strcmp($projectstatic->budget_amount, '')) {
			print price($projectstatic->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
		}
		print '</td></tr>';

		// Date start - end project
		print '<tr><td>'.$langs->trans("Dates").'</td><td>';
		$start = dol_print_date($projectstatic->date_start, 'day');
		print($start ? $start : '?');
		$end = dol_print_date($projectstatic->date_end, 'day');
		print ' - ';
		print($end ? $end : '?');
		if ($projectstatic->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Other attributes
		$cols = 2;
		//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($projectstatic->description);
		print '</td></tr>';

		// Categories
		if (isModEnabled('category')) {
			print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($projectstatic->id, 'project', 1);
			print "</td></tr>";
		}

		print '</table>';

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		print '<br>';
	}

	$head = task_prepare_head($object);
	print dol_get_fiche_head($head, 'task_notes', $langs->trans('Task'), -1, 'projecttask', 0, '', 'reposition');


	$param = (GETPOST('withproject') ? '&withproject=1' : '');
	$linkback = GETPOST('withproject') ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

	if (!GETPOST('withproject') || empty($projectstatic->id)) {
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
		$object->next_prev_filter = " fk_projet IN (".$db->sanitize($projectsListId).")";
	} else {
		$object->next_prev_filter = " fk_projet = ".((int) $projectstatic->id);
	}

	$morehtmlref = '';

	// Project
	if (empty($withproject)) {
		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans("Project").': ';
		$morehtmlref .= $projectstatic->getNomUrl(1);
		$morehtmlref .= '<br>';

		// Third party
		$morehtmlref .= $langs->trans("ThirdParty").': ';
		$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
		$morehtmlref .= '</div>';
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	$cssclass = 'titlefield';
	$moreparam = $param;
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
