<?php
/* Copyright (C) 2026		Dolibarr contributors
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
 *	\file       htdocs/projet/tasks/subtasks.php
 *	\ingroup    project
 *	\brief      List of subtasks of a task
 */

require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies'));

$action = GETPOST('action', 'aZ09');

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$withproject = GETPOSTINT('withproject');
$project_ref = GETPOST('project_ref', 'alpha');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projecttasksubtasks', 'globalcard'));

$object = new Task($db);
$projectstatic = new Project($db);
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || $ref) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$projectstatic->fetch($object->fk_project);
	}
}

// Security check
$socid = 0;

restrictedArea($user, 'projet', $object->fk_project, 'projet&project');


/*
 * Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Retrieve First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject)) {
	if ($projectstatic->fetch(0, $project_ref) > 0) {
		$tasksarray = $object->getTasksArray(null, null, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0) {
			$id = $tasksarray[0]->id;
			$object->fetch($id);
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'&withproject=1');
			exit;
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$taskstatic = new Task($db);
$userstatic = new User($db);
$socstatic = new Societe($db);

$title = (string) $object->ref . ' - ' . $langs->trans("Subtasks");
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': '.$projectstatic->ref : '');
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-project project-tasks page-task_subtasks');

if ($object->id > 0) {
	$res = $object->fetch_optionals();
	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}

	$object->project = clone $projectstatic;

	if (!empty($withproject)) {
		// Tabs for project
		$tab = 'tasks';
		$head = project_prepare_head($projectstatic);
		print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $projectstatic->title;
		if (!empty($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		if (!$user->hasRight('projet', 'all', 'lire')) {
			$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
			$projectstatic->next_prev_filter = "rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
		}

		dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '');

		print dol_get_fiche_end();
		print '<br>';
	}

	$head = task_prepare_head($object);
	print dol_get_fiche_head($head, 'task_subtasks', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

	$param = ($withproject ? '&withproject=1' : '');
	$linkback = $withproject ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

	if (!$withproject || empty($projectstatic->id)) {
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
		$object->next_prev_filter = "fk_projet:IN:".$db->sanitize($projectsListId);
	} else {
		$object->next_prev_filter = "fk_projet:=:".((int) $projectstatic->id);
	}

	$morehtmlref = '';
	if (empty($withproject)) {
		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans("Project").': ';
		$morehtmlref .= $projectstatic->getNomUrl(1);
		$morehtmlref .= '<br>';

		$morehtmlref .= $langs->trans("ThirdParty").': ';
		if (!empty($projectstatic->thirdparty) && is_object($projectstatic->thirdparty)) {
			$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
		}
		$morehtmlref .= '</div>';
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

	print dol_get_fiche_end();


	// Link to create a new subtask, with the current task pre-selected as the parent
	$linktocreatetaskUserRight = $user->hasRight('projet', 'creer') ? 1 : 0;
	$createTaskUrl = DOL_URL_ROOT.'/projet/tasks.php?action=create&id='.((int) $projectstatic->id).'&task_parent='.((int) $object->id).'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id.($withproject ? '&withproject=1' : ''));
	$linktocreatetask = dolGetButtonTitle($langs->trans('AddTask'), '', 'fa fa-plus-circle', $createTaskUrl, '', $linktocreatetaskUserRight);

	print load_fiche_titre($langs->trans("Subtasks"), $linktocreatetask, 'projecttask');

	// Columns to display for subtasks
	$arrayfields = array(
		't.ref' => array('label' => "RefTask", 'checked' => '1'),
		't.label' => array('label' => "LabelTask", 'checked' => '1'),
		't.dateo' => array('label' => "DateStart", 'checked' => '1'),
		't.datee' => array('label' => "Deadline", 'checked' => '1'),
		't.planned_workload' => array('label' => "PlannedWorkload", 'checked' => '1'),
		't.duration_effective' => array('label' => "TimeSpent", 'checked' => '1'),
		't.progress' => array('label' => "ProgressDeclared", 'checked' => '1'),
		't.fk_statut' => array('label' => "Status", 'checked' => '1'),
	);

	// Load all tasks of the project so projectLinesa can recurse into the subtree
	$tasksarray = $taskstatic->getTasksArray(null, null, $projectstatic->id, $socid, 0);
	$tasksrole = '';

	print '<div class="div-table-responsive">';
	print '<table id="tablelines" class="tagtable nobottom liste">';

	print '<tr class="liste_titre nodrag nodrop">';
	print_liste_field_titre($arrayfields['t.ref']['label'], '', '', '', '', '', '', '', '');
	print_liste_field_titre($arrayfields['t.label']['label'], '', '', '', '', '', '', '', '');
	print_liste_field_titre($arrayfields['t.dateo']['label'], '', '', '', '', '', '', '', 'center ');
	print_liste_field_titre($arrayfields['t.datee']['label'], '', '', '', '', '', '', '', 'center ');
	print_liste_field_titre($arrayfields['t.planned_workload']['label'], '', '', '', '', '', '', '', 'right ');
	print_liste_field_titre($arrayfields['t.duration_effective']['label'], '', '', '', '', '', '', '', 'right ');
	print_liste_field_titre($arrayfields['t.progress']['label'], '', '', '', '', '', '', '', 'right ');
	print_liste_field_titre($arrayfields['t.fk_statut']['label'], '', '', '', '', '', '', '', 'center ');
	print '<td></td>';
	print "</tr>\n";

	$nbofsubtaskshown = 0;
	if (count($tasksarray) > 0) {
		$j = 0;
		$level = 0;
		$nbofsubtaskshown = projectLinesa($j, $object->id, $tasksarray, $level, '', 0, $tasksrole, (string) $projectstatic->id, 0, $projectstatic->id, '', 0, $arrayfields);
	}
	if ($nbofsubtaskshown == 0) {
		print '<tr class="oddeven"><td colspan="9"><span class="opacitymedium">'.$langs->trans("NoSubtask").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';
}

// End of page
llxFooter();
$db->close();
