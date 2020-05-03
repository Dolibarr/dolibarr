<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    project
 *	\brief      Page of a project task
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';

// Load translation files required by the page
$langs->loadlangs(array('projects', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST("ref", 'alpha', 1); // task ref
$taskref = GETPOST("taskref", 'alpha'); // task ref
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$withproject = GETPOST('withproject', 'int');
$project_ref = GETPOST('project_ref', 'alpha');
$planned_workload = ((GETPOST('planned_workloadhour', 'int') != '' || GETPOST('planned_workloadmin', 'int') != '') ? (GETPOST('planned_workloadhour', 'int') > 0 ?GETPOST('planned_workloadhour', 'int') * 3600 : 0) + (GETPOST('planned_workloadmin', 'int') > 0 ?GETPOST('planned_workloadmin', 'int') * 60 : 0) : '');

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('projecttaskcard', 'globalcard'));

$object = new Task($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 * Actions
 */

if ($action == 'update' && !$_POST["cancel"] && $user->rights->projet->creer)
{
	$error = 0;

	if (empty($taskref))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
	}
	if (empty($_POST["label"]))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!$error)
	{
		$object->fetch($id, $ref);
        $object->oldcopy = clone $object;

		$tmparray = explode('_', $_POST['task_parent']);
		$task_parent = $tmparray[1];
		if (empty($task_parent)) $task_parent = 0; // If task_parent is ''

		$object->ref = $taskref ? $taskref : GETPOST("ref", 'alpha', 2);
		$object->label = $_POST["label"];
		$object->description = $_POST['description'];
		$object->fk_task_parent = $task_parent;
		$object->planned_workload = $planned_workload;
		$object->date_start = dol_mktime($_POST['dateohour'], $_POST['dateomin'], 0, $_POST['dateomonth'], $_POST['dateoday'], $_POST['dateoyear']);
		$object->date_end = dol_mktime($_POST['dateehour'], $_POST['dateemin'], 0, $_POST['dateemonth'], $_POST['dateeday'], $_POST['dateeyear']);
		$object->progress = $_POST['progress'];

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) $error++;

		if (!$error)
		{
			$result = $object->update($user);
			if ($result < 0)
			{
			    setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
	else
	{
		$action = 'edit';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->projet->supprimer)
{
	if ($object->fetch($id, $ref) >= 0)
	{
		$result = $projectstatic->fetch($object->fk_project);
		$projectstatic->fetch_thirdparty();

		if ($object->delete($user) > 0)
		{
			header('Location: '.DOL_URL_ROOT.'/projet/tasks.php?restore_lastsearch_values=1&id='.$projectstatic->id.($withproject ? '&withproject=1' : ''));
			exit;
		}
		else
		{
		    setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject))
{
	if ($projectstatic->fetch('', $project_ref) > 0)
	{
		$tasksarray = $object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id = $tasksarray[0]->id;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode) ? '' : '&mode='.$mode));
		}
	}
}

// Build doc
if ($action == 'builddoc' && $user->rights->projet->creer)
{
	$object->fetch($id, $ref);

	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model', 'alpha'));

	$outputlangs = $langs;
	if (GETPOST('lang_id', 'aZ09'))
	{
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
	}
	$result = $object->generateDocument($object->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
	}
}

// Delete file in doc form
if ($action == 'remove_file' && $user->rights->projet->creer)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	if ($object->fetch($id, $ref) >= 0)
	{
		$langs->load("other");
		$upload_dir = $conf->projet->dir_output;
		$file = $upload_dir.'/'.GETPOST('file');

		$ret = dol_delete_file($file);
		if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}
}

/*
 * View
 */


llxHeader('', $langs->trans("Task"));

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

if ($id > 0 || !empty($ref))
{
	if ($object->fetch($id, $ref) > 0)
	{
		$res = $object->fetch_optionals();
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();

		$result = $projectstatic->fetch($object->fk_project);
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) $projectstatic->fetchComments();
		if (!empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();

		$object->project = clone $projectstatic;

		$userWrite = $projectstatic->restrictedProjectArea($user, 'write');

		if (!empty($withproject))
		{
			// Tabs for project
			$tab = 'tasks';
			$head = project_prepare_head($projectstatic);
			dol_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'), 0, '', '');

			$param = ($mode == 'mine' ? '&mode=mine' : '');

			// Project card

            $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

            $morehtmlref = '<div class="refidno">';
            // Title
            $morehtmlref .= $projectstatic->title;
            // Thirdparty
            if ($projectstatic->thirdparty->id > 0)
            {
                $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$projectstatic->thirdparty->getNomUrl(1, 'project');
            }
            $morehtmlref .= '</div>';

            // Define a complementary filter for search of next/prev ref.
            if (!$user->rights->projet->all->lire)
            {
                $objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
                $projectstatic->next_prev_filter = " rowid in (".(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
            }

            dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="fichehalfleft">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border tableforfield centpercent">';

            // Usage
            print '<tr><td class="tdtop">';
            print $langs->trans("Usage");
            print '</td>';
            print '<td>';
            if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES))
            {
            	print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
            	$htmltext = $langs->trans("ProjectFollowOpportunity");
            	print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
            	print '<br>';
            }
            if (empty($conf->global->PROJECT_HIDE_TASKS))
            {
            	print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
            	$htmltext = $langs->trans("ProjectFollowTasks");
            	print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
            	print '<br>';
            }
            if (!empty($conf->global->PROJECT_BILL_TIME_SPENT))
            {
            	print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
            	$htmltext = $langs->trans("ProjectBillTimeDescription");
            	print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
            	print '<br>';
            }
            print '</td></tr>';

            // Visibility
            print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
            if ($projectstatic->public) print $langs->trans('SharedProject');
            else print $langs->trans('PrivateProject');
            print '</td></tr>';

            // Date start - end
            print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
            $start = dol_print_date($projectstatic->date_start, 'day');
            print ($start ? $start : '?');
            $end = dol_print_date($projectstatic->date_end, 'day');
            print ' - ';
            print ($end ? $end : '?');
            if ($projectstatic->hasDelay()) print img_warning("Late");
            print '</td></tr>';

            // Budget
            print '<tr><td>'.$langs->trans("Budget").'</td><td>';
            if (strcmp($projectstatic->budget_amount, '')) print price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
            print '</td></tr>';

            // Other attributes
            $cols = 2;
            //include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '</div>';

            print '<div class="fichehalfright">';
            print '<div class="ficheaddleft">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border centpercent">';

            // Description
            print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
            print nl2br($projectstatic->description);
            print '</td></tr>';

            // Categories
            if ($conf->categorie->enabled) {
                print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
                print $form->showCategories($projectstatic->id, 'project', 1);
                print "</td></tr>";
            }

            print '</table>';

            print '</div>';
            print '</div>';
            print '</div>';

            print '<div class="clearboth"></div>';

			dol_fiche_end();

			print '<br>';
		}

		/*
		 * Actions
		*/
		/*print '<div class="tabsAction">';

		if ($user->rights->projet->all->creer || $user->rights->projet->creer)
		{
		if ($projectstatic->public || $userWrite > 0)
		{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create'.$param.'">'.$langs->trans('AddTask').'</a>';
		}
		else
		{
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
		}
		}
		else
		{
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('AddTask').'</a>';
		}

		print '</div>';
		*/

		// To verify role of users
		//$userAccess = $projectstatic->restrictedProjectArea($user); // We allow task affected to user even if a not allowed project
		//$arrayofuseridoftask=$object->getListContactId('internal');

		$head = task_prepare_head($object);

		if ($action == 'edit' && $user->rights->projet->creer)
		{
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			dol_fiche_head($head, 'task_task', $langs->trans("Task"), 0, 'projecttask', 0, '', '');

			print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Ref").'</td>';
			print '<td><input class="minwidth100" name="taskref" value="'.$object->ref.'"></td></tr>';

			// Label
			print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
			print '<td><input class="minwidth500" name="label" value="'.$object->label.'"></td></tr>';

			// Project
			if (empty($withproject))
			{
				print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
				print $projectstatic->getNomUrl(1);
				print '</td></tr>';

				// Third party
				print '<td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
				if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
				else print '&nbsp;';
				print '</td></tr>';
			}

			// Task parent
			print '<tr><td>'.$langs->trans("ChildOfProjectTask").'</td><td>';
			print $formother->selectProjectTasks($object->fk_task_parent, $projectstatic->id, 'task_parent', ($user->admin ? 0 : 1), 0, 0, 0, $object->id);
			print '</td></tr>';

			// Date start
			print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
			print $form->selectDate($object->date_start, 'dateo', 1, 1, 0, '', 1, 0);
			print '</td></tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
			print $form->selectDate($object->date_end ? $object->date_end : -1, 'datee', 1, 1, 0, '', 1, 0);
			print '</td></tr>';

			// Planned workload
			print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
			print $form->select_duration('planned_workload', $object->planned_workload, 0, 'text');
			print '</td></tr>';

			// Progress declared
			print '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td>';
			print $formother->select_percent($object->progress, 'progress', 0, 5, 0, 100, 1);
			print '</td></tr>';

			// Description
			print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
			print '<td>';
			print '<textarea name="description" class="quatrevingtpercent" rows="'.ROWS_4.'">'.$object->description.'</textarea>';
			print '</td></tr>';

			// Other options
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
			if (empty($reshook))
			{
				print $object->showOptionals($extrafields, 'edit');
			}

			print '</table>';

			dol_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button" name="update" value="'.$langs->trans("Modify").'"> &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		}
		else
		{
			/*
			 * Fiche tache en mode visu
			 */
			$param = ($withproject ? '&withproject=1' : '');
			$linkback = $withproject ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'&restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>' : '';

			dol_fiche_head($head, 'task_task', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

			if ($action == 'delete')
			{
				print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"].'&withproject='.$withproject, $langs->trans("DeleteATask"), $langs->trans("ConfirmDeleteATask"), "confirm_delete");
			}

			if (!GETPOST('withproject') || empty($projectstatic->id))
			{
			    $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			    $object->next_prev_filter = " fk_projet in (".$projectsListId.")";
			}
			else $object->next_prev_filter = " fk_projet = ".$projectstatic->id;

			$morehtmlref = '';

			// Project
			if (empty($withproject))
			{
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
			print '<div class="fichehalfleft">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent tableforfield">';

			// Task parent
			print '<tr><td>'.$langs->trans("ChildOfTask").'</td><td>';
			if ($object->fk_task_parent > 0)
			{
				$tasktmp = new Task($db);
				$tasktmp->fetch($object->fk_task_parent);
				print $tasktmp->getNomUrl(1);
			}
			print '</td></tr>';

			// Date start - Date end
			print '<tr><td class="titlefield">'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td colspan="3">';
			$start = dol_print_date($object->date_start, 'dayhour');
    		print ($start ? $start : '?');
			$end = dol_print_date($object->date_end, 'dayhour');
    		print ' - ';
    		print ($end ? $end : '?');
    		if ($object->hasDelay()) print img_warning("Late");
			print '</td></tr>';

			// Planned workload
			print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td colspan="3">';
			if ($object->planned_workload != '')
			{
				print convertSecondToTime($object->planned_workload, 'allhourmin');
			}
			print '</td></tr>';

			// Description
			print '<td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';
			print nl2br($object->description);
			print '</td></tr>';

			print '</table>';
			print '</div>';

			print '<div class="fichehalfright"><div class="ficheaddleft">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent tableforfield">';

			// Progress declared
			print '<tr><td class="titlefield">'.$langs->trans("ProgressDeclared").'</td><td colspan="3">';
			if ($object->progress != '')
			{
				print $object->progress.' %';
			}
			print '</td></tr>';

			// Progress calculated
			print '<tr><td>'.$langs->trans("ProgressCalculated").'</td><td colspan="3">';
			if ($object->planned_workload != '')
			{
				$tmparray = $object->getSummaryOfTimeSpent();
				if ($tmparray['total_duration'] > 0 && !empty($object->planned_workload)) print round($tmparray['total_duration'] / $object->planned_workload * 100, 2).' %';
				else print '0 %';
			}
			else print '<span class="opacitymedium">'.$langs->trans("WorkloadNotDefined").'</span>';
			print '</td></tr>';

			// Other attributes
			$cols = 3;
			$parameters = array('socid'=>$socid);
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div>';
			print '</div>';

			print '</div>';
			print '<div class="clearboth"></div>';

			dol_fiche_end();
		}


		if ($action != 'edit')
		{
			/*
			 * Actions
 			 */

		    print '<div class="tabsAction">';

		    $parameters = array();
		    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		    // modified by hook
		    if (empty($reshook))
		    {
				// Modify
				if ($user->rights->projet->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit&amp;withproject='.$withproject.'">'.$langs->trans('Modify').'</a>';
				}
				else
				{
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Modify').'</a>';
				}

				// Delete
				if ($user->rights->projet->supprimer)
				{
				    if (!$object->hasChildren() && !$object->hasTimeSpent())
				    {
						print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=delete&amp;withproject='.$withproject.'">'.$langs->trans('Delete').'</a>';
				    }
				    else
				    {
				        print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("TaskHasChild").'">'.$langs->trans('Delete').'</a>';
				    }
				}
				else
				{
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
				}

				print '</div>';
		    }

    		print '<div class="fichecenter"><div class="fichehalfleft">';
    		print '<a name="builddoc"></a>'; // ancre

			/*
			 * Documents generes
			 */
			$filename = dol_sanitizeFileName($projectstatic->ref)."/".dol_sanitizeFileName($object->ref);
			$filedir = $conf->projet->dir_output."/".dol_sanitizeFileName($projectstatic->ref)."/".dol_sanitizeFileName($object->ref);
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = ($user->rights->projet->lire);
			$delallowed = ($user->rights->projet->creer);

			print $formfile->showdocuments('project_task', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf);

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'task', $socid, 1, '', 10, 'withproject='.$withproject);

			print '</div></div></div>';
		}
	}
}

// End of page
llxFooter();
$db->close();
