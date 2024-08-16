<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/projet/tasks/comment.php
 *	\ingroup    project
 *	\brief      Page of a project task comment
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies'));

$id = GETPOSTINT('id');
$idcomment = GETPOSTINT('idcomment');
$ref = GETPOST("ref", 'alpha', 1); // task ref
$objectref = GETPOST("taskref", 'alpha'); // task ref
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$withproject = GETPOSTINT('withproject');
$project_ref = GETPOST('project_ref', 'alpha');
$planned_workload = ((GETPOSTINT('planned_workloadhour') != '' || GETPOSTINT('planned_workloadmin') != '') ? (GETPOSTINT('planned_workloadhour') > 0 ? GETPOSTINT('planned_workloadhour') * 3600 : 0) + (GETPOSTINT('planned_workloadmin') > 0 ? GETPOSTINT('planned_workloadmin') * 60 : 0) : '');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projecttaskcommentcard', 'globalcard'));

$object = new Task($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// include comment actions
include DOL_DOCUMENT_ROOT.'/core/actions_comments.inc.php';

// Retrieve First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject)) {
	if ($projectstatic->fetch('', $project_ref) > 0) {
		$objectsarray = $object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($objectsarray) > 0) {
			$id = $objectsarray[0]->id;
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode) ? '' : '&mode='.$mode));
		}
	}
}


if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}

// Security check
$socid = 0;

restrictedArea($user, 'projet', $object->fk_project, 'projet&project');



/*
 * View
 */

llxHeader('', $langs->trans("CommentPage"), '', '', 0, 0, '', '', '', 'mod-project project-tasks page-task_comment');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		$result = $object->fetch_optionals();

		$result = $object->fetchComments();
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$result = $projectstatic->fetch($object->fk_project);
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
			$projectstatic->fetchComments();
		}

		$object->project = clone $projectstatic;

		$userWrite = $projectstatic->restrictedProjectArea($user, 'write');

		if (!empty($withproject)) {
			// Tabs for project
			$tab = 'tasks';
			$head = project_prepare_head($projectstatic);
			print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

			$param = ($mode == 'mine' ? '&mode=mine' : '');

			// Project card

			$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Title
			$morehtmlref .= $projectstatic->title;
			// Thirdparty
			if ($projectstatic->thirdparty->id > 0) {
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

			print '<table class="border centpercent">';

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

			// Opportunities
			if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') && !empty($object->usage_opportunity)) {
				// Opportunity status
				print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
				$code = dol_getIdFromCode($db, $projectstatic->opp_status, 'c_lead_status', 'rowid', 'code');
				if ($code) {
					print $langs->trans("OppStatus".$code);
				}
				print '</td></tr>';

				// Opportunity percent
				print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
				if (strcmp($projectstatic->opp_percent, '')) {
					print price($projectstatic->opp_percent, 0, $langs, 1, 0).' %';
				}
				print '</td></tr>';

				// Opportunity Amount
				print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
				if (strcmp($projectstatic->opp_amount, '')) {
					print price($projectstatic->opp_amount, 0, $langs, 1, 0, -1, $conf->currency);
					if (strcmp($projectstatic->opp_percent, '')) {
						print ' &nbsp; &nbsp; &nbsp; <span title="'.dol_escape_htmltag($langs->trans('OpportunityWeightedAmount')).'"><span class="opacitymedium">'.$langs->trans("OpportunityWeightedAmountShort").'</span>: <span class="amount">'.price($projectstatic->opp_amount * $projectstatic->opp_percent / 100, 0, $langs, 1, 0, -1, $conf->currency).'</span></span>';
					}
				}
				print '</td></tr>';
			}

			// Budget
			print '<tr><td>'.$langs->trans("Budget").'</td><td>';
			if (strcmp($projectstatic->budget_amount, '')) {
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

			print '<table class="border centpercent">';

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

		/*
		 * Fiche tache en mode visu
		 */
		$param = ($withproject ? '&withproject=1' : '');
		$linkback = $withproject ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'&restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>' : '';

		print dol_get_fiche_head($head, 'task_comment', $langs->trans("Task"), -1, 'projecttask');

		if ($action == 'delete') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".GETPOSTINT("id").'&withproject='.$withproject, $langs->trans("DeleteATask"), $langs->trans("ConfirmDeleteATask"), "confirm_delete");
		}

		if (!GETPOST('withproject') || empty($projectstatic->id)) {
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			$object->next_prev_filter = "fk_projet IN (".$db->sanitize($projectsListId).")";
		} else {
			$object->next_prev_filter = "fk_projet = ".((int) $projectstatic->id);
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
			if (!empty($projectstatic->thirdparty)) {
				$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
			}
			$morehtmlref .= '</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

		// Nb comments
		print '<td class="titlefield">'.$langs->trans("NbComments").'</td><td>';
		print $object->getNbComments();
		print '</td></tr>';

		// Other attributes
		$cols = 3;
		$parameters = array('socid'=>$socid);
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';

		print dol_get_fiche_end();


		// Include comment tpl view
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_comment.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
