<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013	Charles-Fr BENKE		<charles.fr@benke.fr>
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
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    project
 *	\brief      Page of a project task
 */

require( "../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("projects");
$langs->load("companies");

$taskid = GETPOST("id",'int');
$taskref = GETPOST("ref",'int');


$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$withproject=GETPOST('withproject','int');
$project_ref=GETPOST('project_ref','alpha');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! $user->rights->projet->lire) accessforbidden();

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('projecttaskcard'));

$object = new Task($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('projet_task');
/*
 * Actions
 */

if ($action == 'update' && ! $_POST["cancel"] && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["label"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
	}
	if (! $error)
	{
		$object->fetch($id);

		$tmparray=explode('_',$_POST['task_parent']);
		$task_parent=$tmparray[1];
		if (empty($task_parent)) $task_parent = 0;	// If task_parent is ''

		$object->label = $_POST["label"];
		$object->description = $_POST['description'];
		$object->fk_task_parent = $task_parent;
		$object->date_start = dol_mktime(0,0,0,$_POST['dateomonth'],$_POST['dateoday'],$_POST['dateoyear']);
		$object->date_end = dol_mktime(0,0,0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear']);
		$object->progress = $_POST['progress'];

		$object->subprice = $_POST['subprice'];
		$object->duration_planned = $_POST["duration_plannedhour"]*60*60;	// We store duration in seconds
		$object->duration_planned+= $_POST["duration_plannedmin"]*60;		// We store duration in seconds
		if ($_POST['subprice'])
			$object->total_ht = $_POST['subprice']*($object->duration_planned/3600);
		else
			$object->total_ht = $_POST['total_ht'];

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		
		$result=$object->update($user);
	}
	else
	{
		$action='edit';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->projet->supprimer)
{
	if ($object->fetch($id) >= 0 )
	{
		$result=$projectstatic->fetch($object->fk_projet);
		if (! empty($projectstatic->socid))
		{
			$projectstatic->societe->fetch($projectstatic->socid);
		}

		if ($object->delete($user) > 0)
		{
			header("Location: index.php");
			exit;
		}
		else
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$action='';
		}
	}
}
if ($action == 'confirm_TaskToInter' && $_REQUEST['confirm'] == 'yes' && $user->rights->projet->creer)
{
	Task_Transfer_FichInter($db, $conf, $langs, $user, $_GET["id"]);
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (! empty($project_ref) && ! empty($withproject))
{
	if ($projectstatic->fetch('',$project_ref) > 0)
	{
		$tasksarray=$object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id=$tasksarray[0]->id;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode)?'':'&mode='.$mode));
		}
	}
}

/*
 * View
 */

$langs->load('projects');

llxHeader('', $langs->trans("Task"));


$form = new Form($db);
$formother = new FormOther($db);
//$project = new Project($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id) > 0)
	{
		$res=$object->fetch_optionals($object->id,$extralabels);

		$result=$projectstatic->fetch($object->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

		$userWrite  = $projectstatic->restrictedProjectArea($user,'write');

		if (! empty($withproject))
		{
			// Tabs for project
			$tab='tasks';
			$head=project_prepare_head($projectstatic);
			dol_fiche_head($head, $tab, $langs->trans("Project"),0,($projectstatic->public?'projectpub':'project'));

			$param=($mode=='mine'?'&mode=mine':'');

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">';
			print $langs->trans("Ref");
			print '</td><td>';
			// Define a complementary filter for search of next/prev ref.
			if (! $user->rights->projet->all->lire)
			{
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,0);
				$projectstatic->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
			}
			print $form->showrefnav($projectstatic,'project_ref','',1,'ref','ref','',$param.'&withproject=1');
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstatic->title.'</td></tr>';

			print '<tr><td>'.$langs->trans("Company").'</td><td>';
			if (! empty($projectstatic->societe->id)) print $projectstatic->societe->getNomUrl(1);
			else print '&nbsp;';
			print '</td>';
			print '</tr>';

			// Visibility
			print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
			if ($projectstatic->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td><td>'.$projectstatic->getLibStatut(4).'</td></tr>';

			print '</table>';

			dol_fiche_end();

			print '<br>';
		}


		dol_htmloutput_mesg($mesg);

		$head=task_prepare_head($object);
		dol_fiche_head($head, 'task_task', $langs->trans("Task"),0,'projecttask');

		if ($action == 'edit' && $user->rights->projet->creer)
		{
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">'.$langs->trans("Ref").'</td>';
			print '<td>'.$object->ref.'</td></tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td>';
			print '<td><input size="30" name="label" value="'.$object->label.'"></td></tr>';

			// Project
			if (empty($withproject))
			{
				print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
				print $projectstatic->getNomUrl(1);
				print '</td></tr>';

				// Third party
				print '<td>'.$langs->trans("Company").'</td><td colspan="3">';
				if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
				else print '&nbsp;';
				print '</td></tr>';
			}

			// Task parent
			print '<tr><td>'.$langs->trans("ChildOfTask").'</td><td>';
			print $formother->selectProjectTasks($object->fk_task_parent,$projectstatic->id, 'task_parent', $user->admin?0:1, 0);
			print '</td></tr>';

			// Date start
			print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
			print $form->select_date($object->date_start,'dateo');
			print '</td></tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
			print $form->select_date($object->date_end?$object->date_end:-1,'datee');
			print '</td></tr>';

			// Progress
			print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
			print $formother->select_percent($object->progress,'progress');
			print '</td></tr>';

			//unit price 
			print '<tr><td >'.$langs->trans("Subprice").'</td><td colspan="3">';
			print '<input size="5" name="subprice" value="'.price2num($object->subprice, 'MU').'">';
			print '</td></tr>';

			//planned time
			print '<tr><td >'.$langs->trans("DurationPlanned").'</td><td colspan="3">';
			print $form->select_duration('duration_planned',$object->duration_planned);
			print '</td></tr>';

			//Amount Task
			print '<tr><td >'.$langs->trans("Total_ht").'</td><td colspan="3">';
			// si le prix unitaire est saisie on bloque le montant total
			if($object->subprice == null || $object->subprice == 0)
				print '<input size="5" name="total_ht" value="'.price2num($object->total_ht, 'MU').'">';
			else
				print price2num($object->total_ht, 'MU');
			print '</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
			print $object->getLibStatut(4);
			print '</td></tr>';

			// Description
			print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
			print '<td>';
			print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$object->description.'</textarea>';
			print '</td></tr>';

			// Other options
			$parameters=array();
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
			if (empty($reshook) && ! empty($extrafields->attribute_label))
			{
				print $object->showOptionals($extrafields,'edit');
			}
			
			print '</table>';

			print '<center><br>';
			print '<input type="submit" class="button" name="update" value="'.$langs->trans("Modify").'"> &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '<center>';

			print '</form>';
		}
		else
		{
			/*
			 * Fiche tache en mode visu
			 */
			$param=($withproject?'&withproject=1':'');
			$linkback=$withproject?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

			if ($action == 'delete')
			{
				$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"].'&withproject='.$withproject,$langs->trans("DeleteATask"),$langs->trans("ConfirmDeleteATask"),"confirm_delete");
				if ($ret == 'html') print '<br>';
			}

			if ($action == 'TaskToInter')
			{
				$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],"Transfert de la tache en intervention","Confirmation du transfert de la tache en intervention","confirm_TaskToInter",'',0,1);
				if ($ret == 'html') print '<br>';
			}

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">';
			print $langs->trans("Ref");
			print '</td><td colspan="3">';
			if (! GETPOST('withproject') || empty($projectstatic->id))
			{
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
				$object->next_prev_filter=" fk_projet in (".$projectsListId.")";
			}
			else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;
			print $form->showrefnav($object,'id',$linkback,1,'rowid','ref','',$param);
			print '</td>';
			print '</tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->label.'</td></tr>';

			// Project
			if (empty($withproject))
			{
				print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
				print $projectstatic->getNomUrl(1);
				print '</td></tr>';

				// Third party
				print '<td>'.$langs->trans("Company").'</td><td colspan="3">';
				if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
				else print '&nbsp;';
				print '</td></tr>';
			}

			// Date start
			print '<tr><td>'.$langs->trans("DateStart").'</td><td colspan="3">';
			print dol_print_date($object->date_start,'day');
			print '</td></tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateEnd").'</td><td colspan="3">';
			print dol_print_date($object->date_end,'day');
			print '</td></tr>';

			// Progress
			print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
			print $object->progress.' %';
			print '</td></tr>';

			//Amount Task
			print '<tr><td >'.$langs->trans("Subprice").'</td><td colspan="3">';
			print price2num($object->subprice,'MU').'</td></tr>';

			//planned time
			print '<tr><td >'.$langs->trans("DurationPlanned").' / '.$langs->trans("TimesSpent").' / '.$langs->trans("DurationRemain").'</td><td>';  
			print ConvertSecondToTime($object->duration_planned,'all').'</td>';
			$TaskTimeSpent=$object->getTaskTimeSpent($taskid);
			print '<td >'.ConvertSecondToTime($TaskTimeSpent).'</td>';
			if ($object->progress>0)
				$EstimatedTimeRemind=($TaskTimeSpent/($object->progress/100))-$TaskTimeSpent;
			else
				$EstimatedTimeRemind=$object->duration_planned;
			print '<td>'.ConvertSecondToTime($EstimatedTimeRemind).'</td>';
			print '</tr>';

			//Amount Task
			print '<tr><td >'.$langs->trans("Total_ht").'</td><td colspan="3">';
			print price2num($object->total_ht,'MU').'</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
			print $object->getLibStatut(4);
			print '</td></tr>';

			// Description
			print '<td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
			print nl2br($object->description);
			print '</td></tr>';

			// Other options
			$parameters=array();
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
			if (empty($reshook) && ! empty($extrafields->attribute_label))
			{
				print $object->showOptionals($extrafields);
			}

			print '</table>';

		}

		dol_fiche_end();

		if ($_GET["action"] != 'edit' && $projectstatic->statut!=2)
		{
			/*
			 * Actions
			 */
			print '<div class="tabsAction">';

			// Modify
			if ($user->rights->projet->creer && $object->fk_statut!=4)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit&amp;withproject='.$withproject.'">'.$langs->trans('Modify').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Modify').'</a>';
			}

			// Delete
			if ($user->rights->projet->supprimer && ! $object->hasChildren())
			{
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=delete&amp;withproject='.$withproject.'">'.$langs->trans('Delete').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
			}
			
			// trasnfert autorisé ssi date de fin tache saisie, tache terminé à 100% et pas déjà transférée
			if ($user->rights->projet->creer && $object->date_end && $object->progress==100 && $object->fk_statut!=4)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=TaskToInter">'.'Transfert intervention'.'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.'Transfert intervention'.'</a>';
			}
			print '</div>';
		}
	}
}

llxFooter();
$db->close();
?>