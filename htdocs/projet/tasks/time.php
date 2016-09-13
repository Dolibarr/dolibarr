<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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
 *	\file       htdocs/projet/tasks/time.php
 *	\ingroup    project
 *	\brief      Page to add new time spent on a task
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('projects');

$id=GETPOST('id','int');
$projectid=GETPOST('projectid','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$withproject=GETPOST('withproject','int');
$project_ref=GETPOST('project_ref','alpha');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('projecttaskcard','globalcard'));

$object = new Task($db);
$projectstatic = new Project($db);
$extrafields_project = new ExtraFields($db);
$extrafields_task = new ExtraFields($db);

if ($projectid > 0 || ! empty($ref))
{
    // fetch optionals attributes and labels
    $extralabels_projet=$extrafields_project->fetch_name_optionals_label($projectstatic->table_element);
}
$extralabels_task=$extrafields_task->fetch_name_optionals_label($object->table_element);


/*
 * Actions
 */

if ($action == 'addtimespent' && $user->rights->projet->lire)
{
	$error=0;

	$timespent_durationhour = GETPOST('timespent_durationhour','int');
	$timespent_durationmin = GETPOST('timespent_durationmin','int');
	if (empty($timespent_durationhour) && empty($timespent_durationmin))
	{
		setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}
	if (empty($_POST["userid"]))
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorUserNotAssignedToTask'), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$object->fetch($id, $ref);
		$object->fetch_projet();

		if (empty($object->projet->statut))
		{
			setEventMessages($langs->trans("ProjectMustBeValidatedFirst"), null, 'errors');
			$error++;
		}
		else
		{
			$object->timespent_note = $_POST["timespent_note"];
			$object->progress = GETPOST('progress', 'int');
			$object->timespent_duration = $_POST["timespent_durationhour"]*60*60;	// We store duration in seconds
			$object->timespent_duration+= $_POST["timespent_durationmin"]*60;		// We store duration in seconds
	        if (GETPOST("timehour") != '' && GETPOST("timehour") >= 0)	// If hour was entered
	        {
				$object->timespent_date = dol_mktime(GETPOST("timehour"),GETPOST("timemin"),0,GETPOST("timemonth"),GETPOST("timeday"),GETPOST("timeyear"));
				$object->timespent_withhour = 1;
	        }
	        else
			{
				$object->timespent_date = dol_mktime(12,0,0,GETPOST("timemonth"),GETPOST("timeday"),GETPOST("timeyear"));
			}
			$object->timespent_fk_user = $_POST["userid"];
			$result=$object->addTimeSpent($user);
			if ($result >= 0)
			{
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			}
			else
			{
				setEventMessages($langs->trans($object->error), null, 'errors');
				$error++;
			}
		}
	}
	else
	{
		$action='';
	}
}

if ($action == 'updateline' && ! $_POST["cancel"] && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["new_durationhour"]) && empty($_POST["new_durationmin"]))
	{
		setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$object->fetch($id, $ref);

		$object->timespent_id = $_POST["lineid"];
		$object->timespent_note = $_POST["timespent_note_line"];
		$object->timespent_old_duration = $_POST["old_duration"];
		$object->timespent_duration = $_POST["new_durationhour"]*60*60;	// We store duration in seconds
		$object->timespent_duration+= $_POST["new_durationmin"]*60;		// We store duration in seconds
        if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0)	// If hour was entered
        {
			$object->timespent_date = dol_mktime(GETPOST("timelinehour"),GETPOST("timelinemin"),0,GETPOST("timelinemonth"),GETPOST("timelineday"),GETPOST("timelineyear"));
			$object->timespent_withhour = 1;
        }
        else
		{
			$object->timespent_date = dol_mktime(12,0,0,GETPOST("timelinemonth"),GETPOST("timelineday"),GETPOST("timelineyear"));
		}
		$object->timespent_fk_user = $_POST["userid_line"];

		$result=$object->updateTimeSpent($user);
		if ($result >= 0)
		{
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		}
		else
		{
			setEventMessages($langs->trans($object->error), null, 'errors');
			$error++;
		}
	}
	else
	{
		$action='';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->projet->creer)
{
	$object->fetchTimeSpent($_GET['lineid']);
	$result = $object->delTimeSpent($user);

	if ($result < 0)
	{
		$langs->load("errors");
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
		$action='';
	}
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (! empty($project_ref) && ! empty($withproject))
{
	if ($projectstatic->fetch(0,$project_ref) > 0)
	{
		$tasksarray=$object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id=$tasksarray[0]->id;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject?'&withproject=1':'').(empty($mode)?'':'&mode='.$mode));
			exit;
		}
	}
}

// To show all time lines for project
$projectidforalltimes=0;
if (GETPOST('projectid'))
{
    $projectidforalltimes=GETPOST('projectid','int');
    
}


    
/*
 * View
 */

llxHeader("",$langs->trans("Task"));

$form = new Form($db);
$formother = new FormOther($db);
$userstatic = new User($db);

if (($id > 0 || ! empty($ref)) || $projectidforalltimes > 0)
{
	/*
	 * Fiche projet en mode visu
 	 */
    if ($projectidforalltimes)
    {
        $result=$projectstatic->fetch($projectidforalltimes);
        if (! empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();
        $res=$projectstatic->fetch_optionals($object->id,$extralabels_projet);
    }
    elseif ($object->fetch($id, $ref) >= 0)
	{
		$result=$projectstatic->fetch($object->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();
		$res=$projectstatic->fetch_optionals($object->id,$extralabels_projet);
		
		$object->project = clone $projectstatic;
    }
	
    $userWrite = $projectstatic->restrictedProjectArea($user,'write');

	if ($projectstatic->id > 0)
	{
		if ($withproject)
		{
			// Tabs for project
			$tab='tasks';
			$head=project_prepare_head($projectstatic);
			dol_fiche_head($head, $tab, $langs->trans("Project"),0,($projectstatic->public?'projectpub':'project'));

			$param=($mode=='mine'?'&mode=mine':'');

			print '<table class="border" width="100%">';

	        $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';
			
			// Ref
			print '<tr><td class="titlefield">';
			print $langs->trans("Ref");
			print '</td><td>';
			// Define a complementary filter for search of next/prev ref.
			if (! $user->rights->projet->all->lire)
			{
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,0);
				$projectstatic->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
			}
			print $form->showrefnav($projectstatic,'project_ref',$linkback,1,'ref','ref','',$param.'&withproject=1');
			print '</td></tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstatic->title.'</td></tr>';

			// Thirdparty
			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
			if (! empty($projectstatic->thirdparty->id)) print $projectstatic->thirdparty->getNomUrl(1);
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

			// Date start
			print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
			print dol_print_date($projectstatic->date_start,'day');
			print '</td></tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
			print dol_print_date($projectstatic->date_end,'day');
			print '</td></tr>';

        	if (! $id && ! $ref)   // Not a dedicated task
        	{
    			// Budget
            	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
            	if (strcmp($projectstatic->budget_amount, '')) print price($projectstatic->budget_amount,'',$langs,0,0,0,$conf->currency);
            	print '</td></tr>';
            	
            	// Other options
            	$parameters=array();
            	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$projectstatic,$action); // Note that $action and $object may have been modified by hook
            	if (empty($reshook) && ! empty($extrafields_project->attribute_label))
            	{
            		print $projectstatic->showOptionals($extrafields_project);
            	}
        	}    
        	
			print '</table>';

			dol_fiche_end();
			
			
			/*
			 * Actions
			 */
			if (empty($id) && empty($ref))
			{
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
    			    print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('AddTask').'</a>';
    			}
    			
    			print '</div>';
			}
		}
	}
	
	if (empty($projectidforalltimes))
	{
		$head=task_prepare_head($object);
		dol_fiche_head($head, 'task_time', $langs->trans("Task"),0,'projecttask');

		if ($action == 'deleteline')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id.'&lineid='.$_GET["lineid"].($withproject?'&withproject=1':''),$langs->trans("DeleteATimeSpent"),$langs->trans("ConfirmDeleteATimeSpent"),"confirm_delete",'','',1);
		}

		print '<table class="border" width="100%">';

		$param=($withproject?'&withproject=1':'');
		$linkback=$withproject?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

		// Ref
		print '<tr><td class="titlefield">';
		print $langs->trans("Ref");
		print '</td><td colspan="3">';
		if (! GETPOST('withproject') || empty($projectstatic->id))
		{
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
			$object->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;
		print $form->showrefnav($object,'ref',$linkback,1,'ref','ref','',$param);
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->label.'</td></tr>';

		// Date start
		print '<tr><td>'.$langs->trans("DateStart").'</td><td colspan="3">';
		print dol_print_date($object->date_start,'dayhour');
		print '</td></tr>';

		// Date end
		print '<tr><td>'.$langs->trans("DateEnd").'</td><td colspan="3">';
		print dol_print_date($object->date_end,'dayhour');
		print '</td></tr>';

		// Planned workload
		print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td colspan="3">';
		print convertSecondToTime($object->planned_workload,'allhourmin');
		print '</td></tr>';

		// Progress declared
		print '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td colspan="3">';
		print $object->progress.' %';
		print '</td></tr>';

		// Progress calculated
		print '<tr><td>'.$langs->trans("ProgressCalculated").'</td><td colspan="3">';
		if ($object->planned_workload)
		{
			$tmparray=$object->getSummaryOfTimeSpent();
			if ($tmparray['total_duration'] > 0) print round($tmparray['total_duration']/$object->planned_workload*100, 2).' %';
			else print '0 %';
		}
		else print '';
		print '</td></tr>';

		// Project
		if (empty($withproject))
		{
			print '<tr><td>'.$langs->trans("Project").'</td><td>';
			print $projectstatic->getNomUrl(1);
			print '</td></tr>';

			// Third party
			print '<td>'.$langs->trans("ThirdParty").'</td><td>';
			if ($projectstatic->thirdparty->id) print $projectstatic->thirdparty->getNomUrl(1);
			else print '&nbsp;';
			print '</td></tr>';
		}

		print '</table>';

		dol_fiche_end();


		/*
		 * Form to add time spent
		 */
		if ($user->rights->projet->lire)
		{
			print '<br>';

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addtimespent">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';

			print '<table class="noborder nohover" width="100%">';

			print '<tr class="liste_titre">';
			print '<td width="100">'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("By").'</td>';
			print '<td>'.$langs->trans("Note").'</td>';
			print '<td>'.$langs->trans("ProgressDeclared").'</td>';
			print '<td align="right" colspan="2">'.$langs->trans("NewTimeSpent").'</td>';
			print "</tr>\n";

			print '<tr '.$bc[false].'>';

			// Date
			print '<td class="nowrap">';
			//$newdate=dol_mktime(12,0,0,$_POST["timemonth"],$_POST["timeday"],$_POST["timeyear"]);
			$newdate='';
			print $form->select_date($newdate,'time',1,1,2,"timespent_date",1,0,1);
			print '</td>';

			// Contributor
			print '<td class="nowrap">';
			print img_object('','user','class="hideonsmartphone"');
			$contactsoftask=$object->getListContactId('internal');
			if (count($contactsoftask)>0)
			{
				$userid=$contactsoftask[0];
				print $form->select_dolusers((GETPOST('userid')?GETPOST('userid'):$userid), 'userid', 0, '', 0, '', $contactsoftask, 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToTheTask"));
			}
			else
			{
				print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
			}
			print '</td>';

			// Note
			print '<td class="nowrap">';
			print '<textarea name="timespent_note" width="95%" rows="'.ROWS_2.'">'.($_POST['timespent_note']?$_POST['timespent_note']:'').'</textarea>';
			print '</td>';

			// Progress declared
			print '<td class="nowrap">';
			print $formother->select_percent(GETPOST('progress')?GETPOST('progress'):$object->progress,'progress');
			print '</td>';

			// Duration - Time spent
			print '<td class="nowrap" align="right">';
			print $form->select_duration('timespent_duration', ($_POST['timespent_duration']?$_POST['timespent_duration']:''), 0, 'text');
			print '</td>';

			print '<td align="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
			print '</td></tr>';

			print '</table></form>';
			
			print '<br>';
		}
	}
	
	if ($projectstatic->id > 0)
	{	
		if ($action == 'deleteline')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id.'&lineid='.$_GET["lineid"].($withproject?'&withproject=1':''),$langs->trans("DeleteATimeSpent"),$langs->trans("ConfirmDeleteATimeSpent"),"confirm_delete",'','',1);
		}

	    /*
		 *  List of time spent
		 */
		$tasks = array();
		
		$sql = "SELECT t.rowid, t.fk_task, t.task_date, t.task_datehour, t.task_date_withhour, t.task_duration, t.fk_user, t.note, t.thm,";
		$sql .= " pt.ref, pt.label,";
		$sql .= " u.lastname, u.firstname";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
		if (empty($projectidforalltimes)) $sql .= " AND t.fk_task =".$object->id;
		else $sql.= " AND pt.fk_projet IN (".$projectidforalltimes.")";
		$sql .= " ORDER BY t.task_date DESC, t.task_datehour DESC, t.rowid DESC";

		$var=true;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$totalnboflines=$num;

			if (! empty($projectidforalltimes))
			{
			    $title=$langs->trans("ListTaskTimeUserProject");
			    $linktotasks='<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("GoToListOfTasks").'</a>';
			    //print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'title_generic.png', 0, '', '', 0, 1);
			    print load_fiche_titre($title,$linktotasks,'title_generic.png');
			}

			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_object($resql);
				$tasks[$i] = $row;
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="updateline">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="projectid" value="'.$projectidforalltimes.'">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="100">'.$langs->trans("Date").'</td>';
		if (! $id && ! $ref)   // Not a dedicated task
        {
		  print '<td>'.$langs->trans("Task").'</td>';
        }
		print '<td>'.$langs->trans("By").'</td>';
		print '<td align="left">'.$langs->trans("Note").'</td>';
		print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
		if (! empty($conf->salaries->enabled))
		{
			print '<td align="right">'.$langs->trans("Value").'</td>';
		}
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		$tasktmp = new Task($db);
		
		$total = 0;
		$totalvalue = 0;
		foreach ($tasks as $task_time)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			$date1=$db->jdate($task_time->task_date);
			$date2=$db->jdate($task_time->task_datehour);

			// Date
			print '<td class="nowrap">';
			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print $form->select_date(($date2?$date2:$date1),'timeline',1,1,2,"timespent_date",1,0,1);
			}
			else
			{
				print dol_print_date(($date2?$date2:$date1),($task_time->task_date_withhour?'dayhour':'day'));
			}
			print '</td>';

			// Task
			if (! $id && ! $ref)   // Not a dedicated task
			{
    			print '<td class="nowrap">';
    			$tasktmp->id = $task_time->fk_task;
    			$tasktmp->ref = $task_time->ref;
    			$tasktmp->label = $task_time->label;
    			print $tasktmp->getNomUrl(1, 'withproject', 'time');	
    			print '</td>';
			}
			
			// User
			print '<td>';
			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{   
			    if (empty($object->id)) $object->fetch($id);
				$contactsoftask=$object->getListContactId('internal');
				if (!in_array($task_time->fk_user,$contactsoftask)) {
					$contactsoftask[]=$task_time->fk_user;
				}
				if (count($contactsoftask)>0) {
					print img_object('','user','class="hideonsmartphone"');
					print $form->select_dolusers($task_time->fk_user,'userid_line',0,'',0,'',$contactsoftask);
				}else {
					print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
				}
			}
			else
			{
				$userstatic->id         = $task_time->fk_user;
				$userstatic->lastname	= $task_time->lastname;
				$userstatic->firstname 	= $task_time->firstname;
				print $userstatic->getNomUrl(1);
			}
			print '</td>';

			// Note
			print '<td align="left">';
			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print '<textarea name="timespent_note_line" width="95%" rows="'.ROWS_2.'">'.$task_time->note.'</textarea>';
			}
			else
			{
				print dol_nl2br($task_time->note);
			}
			print '</td>';

			// Time spent
			print '<td align="right">';
			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print '<input type="hidden" name="old_duration" value="'.$task_time->task_duration.'">';
				print $form->select_duration('new_duration',$task_time->task_duration,0,'text');
			}
			else
			{
				print convertSecondToTime($task_time->task_duration,'allhourmin');
			}
			print '</td>';

			// Value spent
			if ($conf->salaries->enabled)
			{
				print '<td align="right">';
				print price(price2num($task_time->thm * $task_time->task_duration / 3600), 1, $langs, 1, -1, -1, $conf->currency);
				print '</td>';
			}

			// Edit and delete icon
			print '<td align="center" valign="middle" width="80">';
			if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print '<input type="hidden" name="lineid" value="'.$_GET['lineid'].'">';
				print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
				print '<br>';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
			}
			else if ($user->rights->projet->creer)
			{
				print '&nbsp;';
				print '<a href="'.$_SERVER["PHP_SELF"].'?'.($projectidforalltimes?'projectid='.$projectidforalltimes.'&amp;':'').'id='.$task_time->fk_task.'&amp;action=editline&amp;lineid='.$task_time->rowid.($withproject?'&amp;withproject=1':'').'">';
				print img_edit();
				print '</a>';

				print '&nbsp;';
				print '<a href="'.$_SERVER["PHP_SELF"].'?'.($projectidforalltimes?'projectid='.$projectidforalltimes.'&amp;':'').'id='.$task_time->fk_task.'&amp;action=deleteline&amp;lineid='.$task_time->rowid.($withproject?'&amp;withproject=1':'').'">';
				print img_delete();
				print '</a>';
			}
			print '</td>';

			print "</tr>\n";
			$total += $task_time->task_duration;
			$totalvalue += price2num($task_time->thm * $task_time->task_duration / 3600);
		}
		print '<tr class="liste_total"><td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';
		print '<td align="right" class="nowrap liste_total">'.convertSecondToTime($total,'allhourmin').'</td>';
		if ($conf->salaries->enabled)
		{
			print '<td align="right">'.price($totalvalue, 1, $langs, 1, -1, -1, $conf->currency).'</td>';
		}
		print '<td>&nbsp;</td>';
		print '</tr>';

		print "</table>";
		print "</form>";
	}
}


llxFooter();
$db->close();
