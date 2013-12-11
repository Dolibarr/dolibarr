<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/tasks.php
 *	\ingroup    projet
 *	\brief      List all tasks of a project
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("users");
$langs->load("projects");

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$backtopage=GETPOST('backtopage','alpha');

$mode = GETPOST('mode', 'alpha');
$mine = ($mode == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);
$taskstatic = new Task($db);
$extrafields_project = new ExtraFields($db);
$extrafields_task = new ExtraFields($db);
if ($ref)
{
	$object->fetch(0,$ref);
	$id=$object->id;
}

// fetch optionals attributes and labels
if (!empty($id)) {
	$extralabels_projet=$extrafields_project->fetch_name_optionals_label($object->table_element);
	$extralabels_task=$extrafields_task->fetch_name_optionals_label($taskstatic->table_element);

}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$result = restrictedArea($user, 'projet', $id);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('projecttaskcard'));

$progress=GETPOST('progress', 'int');
$label=GETPOST('label', 'alpha');
$description=GETPOST('description');
$planned_workload=GETPOST('planned_workloadhour')*3600+GETPOST('planned_workloadmin')*60;

$userAccess=0;



/*
 * Actions
*/

if ($action == 'createtask' && $user->rights->projet->creer)
{
	$error=0;

	$date_start = dol_mktime(0,0,0,$_POST['dateomonth'],$_POST['dateoday'],$_POST['dateoyear']);
	$date_end = dol_mktime(0,0,0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear']);

	if (empty($_POST["cancel"]))
	{
		if (empty($label))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$action='create';
			$error++;
		}
		else if (empty($_POST['task_parent']))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("ChildOfTask"));
			$action='create';
			$error++;
		}

		if (! $error)
		{
			$tmparray=explode('_',$_POST['task_parent']);
			$projectid=$tmparray[0];
			if (empty($projectid)) $projectid = $id; // If projectid is ''
			$task_parent=$tmparray[1];
			if (empty($task_parent)) $task_parent = 0;	// If task_parent is ''

			$task = new Task($db);

			$task->fk_project = $projectid;
			$task->ref = GETPOST('ref','alpha');
			$task->label = $label;
			$task->description = $description;
			$task->planned_workload = $planned_workload;
			$task->fk_task_parent = $task_parent;
			$task->date_c = dol_now();
			$task->date_start = $date_start;
			$task->date_end = $date_end;
			$task->progress = $progress;

			// Fill array 'array_options' with data from add form
			$ret = $extrafields_task->setOptionalsFromPost($extralabels_task,$task);

			$taskid = $task->create($user);

			if ($taskid > 0)
			{
				$result = $task->add_contact($_POST["userid"], 'TASKEXECUTIVE', 'internal');
			}
		}

		if (! $error)
		{
			if (! empty($backtopage))
			{
				header("Location: ".$backtopage);
				exit;
			}
			else if (empty($projectid))
			{
				header("Location: ".DOL_URL_ROOT.'/projet/tasks/index.php'.(empty($mode)?'':'?mode='.$mode));
				exit;
			}
		}
	}
	else
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		else if (empty($id))
		{
			// We go back on task list
			header("Location: ".DOL_URL_ROOT.'/projet/tasks/index.php'.(empty($mode)?'':'?mode='.$mode));
			exit;
		}
	}
}

/*
 * View
*/

$form=new Form($db);
$formother=new FormOther($db);
$taskstatic = new Task($db);
$userstatic=new User($db);

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Tasks"),$help_url);

if ($id > 0 || ! empty($ref))
{
	$object->fetch($id, $ref);
	if ($object->societe->id > 0)  $result=$object->societe->fetch($object->societe->id);
	$res=$object->fetch_optionals($object->id,$extralabels_projet);


	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite  = $object->restrictedProjectArea($user,'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


	$tab=GETPOST('tab')?GETPOST('tab'):'tasks';

	$head=project_prepare_head($object);
	dol_fiche_head($head, $tab, $langs->trans("Project"),0,($object->public?'projectpub':'project'));

	$param=($mode=='mine'?'&mode=mine':'');

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/liste.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="30%">';
	print $langs->trans("Ref");
	print '</td><td>';
	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire)
	{
		$projectsListId = $object->getProjectsAuthorizedForUser($user,$mine,0);
		$object->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
	}
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', $param);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

	print '<tr><td>'.$langs->trans("Company").'</td><td>';
	if (! empty($object->societe->id)) print $object->societe->getNomUrl(1);
	else print '&nbsp;';
	print '</td>';
	print '</tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print dol_print_date($object->date_start,'day');
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print dol_print_date($object->date_end,'day');
	print '</td></tr>';

	// Other options
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields_project->attribute_label))
	{
		print $object->showOptionals($extrafields_project);
	}

	print '</table>';

	dol_fiche_end();
}


if ($action == 'create' && $user->rights->projet->creer && (empty($object->societe->id) || $userWrite > 0))
{
	if ($id > 0 || ! empty($ref)) print '<br>';

	print_fiche_titre($langs->trans("NewTask"));

	dol_htmloutput_errors($mesg);

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createtask">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	if (! empty($object->id)) print '<input type="hidden" name="id" value="'.$object->id.'">';
	if (! empty($mode)) print '<input type="hidden" name="mode" value="'.$mode.'">';

	print '<table class="border" width="100%">';

	$defaultref='';
	$obj = empty($conf->global->PROJECT_TASK_ADDON)?'mod_task_simple':$conf->global->PROJECT_TASK_ADDON;
	if (! empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php"))
	{
		require_once DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$defaultref = $modTask->getNextValue($soc,$object);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

	// Ref
	print '<input type="hidden" name="ref" value="'.($_POST["ref"]?$_POST["ref"]:$defaultref).'">';
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>'.($_POST["ref"]?$_POST["ref"]:$defaultref).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input type="text" size="25" name="label" class="flat" value="'.$label.'">';
	print '</td></tr>';

	// List of projects
	print '<tr><td class="fieldrequired">'.$langs->trans("ChildOfTask").'</td><td>';
	print $formother->selectProjectTasks('',$projectid?$projectid:$object->id, 'task_parent', 0, 0, 1, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	print $form->select_dolusers($user->id,'userid',1);
	print '</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print $form->select_date(($date_start?$date_start:''),'dateo',0,0,0,'',1,1);
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print $form->select_date(($date_end?$date_end:-1),'datee',0,0,0,'',1,1);
	print '</td></tr>';

	// planned workload
	print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
	print $form->select_duration('planned_workload',$object->planned_workload,0,'text');
	print '</td></tr>';

	// Progress
	print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
	print $formother->select_percent($progress,'progress');
	print '</td></tr>';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
	print '<td>';
	print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$description.'</textarea>';
	print '</td></tr>';

	// Other options
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields_task->attribute_label))
	{
		print $object->showOptionals($extrafields_task,'edit');
	}

	print '</table>';

	print '<div align="center"><br>';
	print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

}
else
{
	/*
	 * Fiche projet en mode visu
	*/

	/*
	 * Actions
	*/
	print '<div class="tabsAction">';

	if ($user->rights->projet->all->creer || $user->rights->projet->creer)
	{
		if ($object->public || $userWrite > 0)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id).'">'.$langs->trans('AddTask').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NoPermission").'">'.$langs->trans('AddTask').'</a>';
	}

	print '</div>';

	print '<br>';


	// Link to switch in "my task" / "all task"
	print '<table width="100%"><tr><td align="right">';
	if ($mode == 'mine')
	{
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("DoNotShowMyTasksOnly").'</a>';
		//print ' - ';
		//print $langs->trans("ShowMyTaskOnly");
	}
	else
	{
		//print $langs->trans("DoNotShowMyTaskOnly");
		//print ' - ';
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mode=mine">'.$langs->trans("ShowMyTasksOnly").'</a>';
	}
	print '</td></tr></table>';

	// Get list of tasks in tasksarray and taskarrayfiltered
	// We need all tasks (even not limited to a user because a task to user
	// can have a parent that is not affected to him).
	$tasksarray=$taskstatic->getTasksArray(0, 0, $object->id, $socid, 0);
	// We load also tasks limited to a particular user
	$tasksrole=($mode=='mine' ? $taskstatic->getUserRolesForProjectsOrTasks(0,$user,$object->id,0) : '');
	//var_dump($tasksarray);
	//var_dump($tasksrole);

	if (! empty($conf->use_javascript_ajax))
	{
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '<table id="tablelines" class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	// print '<td>'.$langs->trans("Project").'</td>';
	print '<td width="100">'.$langs->trans("RefTask").'</td>';
	print '<td>'.$langs->trans("LabelTask").'</td>';
	print '<td align="center">'.$langs->trans("DateStart").'</td>';
	print '<td align="center">'.$langs->trans("DateEnd").'</td>';
	print '<td align="center">'.$langs->trans("PlannedWorkload").'</td>';
	print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
	print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
	print '<td align="right">'.$langs->trans("ProgressCalculated").'</td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";
	if (count($tasksarray) > 0)
	{
		// Show all lines in taskarray (recursive function to go down on tree)
		$j=0;
		$nboftaskshown=projectLinesa($j, 0, $tasksarray, $level, true, 0, $tasksrole, '', 1);
	}
	else
	{
		print '<tr><td colspan="'.(! empty($object->id) ? "5" : "4").'">'.$langs->trans("NoTasks").'</td></tr>';
	}
	print "</table>";


	// Test if database is clean. If not we clean it.
	//print 'mode='.$_REQUEST["mode"].' $nboftaskshown='.$nboftaskshown.' count($tasksarray)='.count($tasksarray).' count($tasksrole)='.count($tasksrole).'<br>';
	if ($mode=='mine')
	{
		if ($nboftaskshown < count($tasksrole)) $object->clean_orphelins();
	}
	else
	{
		if ($nboftaskshown < count($tasksarray)) $object->clean_orphelins();
	}
}

llxFooter();

$db->close();
?>
