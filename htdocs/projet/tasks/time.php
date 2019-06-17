<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2018	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

// Load translation files required by the page
$langs->load('projects');

$id=GETPOST('id','int');
$projectid=GETPOST('projectid','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$cancel=GETPOST('cancel','alpha');
$withproject=GETPOST('withproject','int');
$project_ref=GETPOST('project_ref','alpha');

$search_day=GETPOST('search_day','int');
$search_month=GETPOST('search_month','int');
$search_year=GETPOST('search_year','int');
$search_datehour='';
$search_datewithhour='';
$search_note=GETPOST('search_note','alpha');
$search_duration=GETPOST('search_duration','int');
$search_value=GETPOST('search_value','int');
$search_task_ref=GETPOST('search_task_ref','alpha');
$search_task_label=GETPOST('search_task_label','alpha');
$search_user=GETPOST('search_user','int');

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) accessforbidden();

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='t.task_date,t.task_datehour,t.rowid';
if (! $sortorder) $sortorder='DESC,DESC,DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
//$object = new TaskTime($db);
$hookmanager->initHooks(array('projecttasktime','globalcard'));

$object = new Task($db);
$projectstatic = new Project($db);
$extrafields_project = new ExtraFields($db);
$extrafields_task = new ExtraFields($db);

$extralabels_projet=$extrafields_project->fetch_name_optionals_label($projectstatic->table_element);
$extralabels_task=$extrafields_task->fetch_name_optionals_label($object->table_element);


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action=''; }

$parameters=array('socid'=>$socid, 'projectid'=>$projectid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_day='';
	$search_month='';
	$search_year='';
	$search_date='';
    $search_datehour='';
    $search_datewithhour='';
    $search_note='';
    $search_duration='';
    $search_value='';
    $search_date_creation='';
    $search_date_update='';
    $search_task_ref='';
    $search_task_label='';
    $search_user=0;
    $toselect='';
    $search_array_options=array();
    $action='';
}

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
		if ($id || $ref)
		{
			$object->fetch($id, $ref);
		}
		else
		{
			$object->fetch(GETPOST('taskid','int'));
		}
		$object->fetch_projet();

		if (empty($object->project->statut))
		{
			setEventMessages($langs->trans("ProjectMustBeValidatedFirst"), null, 'errors');
			$error++;
		}
		else
		{
			$object->timespent_note = $_POST["timespent_note"];
			if (GETPOST('progress', 'int') > 0) $object->progress = GETPOST('progress', 'int');		// If progress is -1 (not defined), we do not change value
			$object->timespent_duration = $_POST["timespent_durationhour"]*60*60;	// We store duration in seconds
			$object->timespent_duration+= ($_POST["timespent_durationmin"]?$_POST["timespent_durationmin"]:0)*60;   // We store duration in seconds
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
		if (empty($id)) $action='createtime';
		else $action='createtime';
	}
}

if ($action == 'updateline' && ! $_POST["cancel"] && $user->rights->projet->lire)
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
		// TODO Check that ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids))

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

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->projet->lire)
{
	$object->fetchTimeSpent(GETPOST('lineid','int'));
	// TODO Check that ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids))
	$result = $object->delTimeSpent($user);

	if ($result < 0)
	{
		$langs->load("errors");
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
		$action='';
	}
	else
	{
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
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
if (GETPOST('projectid','none'))
{
	$projectidforalltimes=GETPOST('projectid','int');
}


/*
 * View
 */

llxHeader("",$langs->trans("Task"));

$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);
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
        $res=$projectstatic->fetch_optionals();
    }
    elseif ($object->fetch($id, $ref) >= 0)
	{
		if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();
		$result=$projectstatic->fetch($object->fk_project);
		if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) $projectstatic->fetchComments();
		if (! empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();
		$res=$projectstatic->fetch_optionals();

		$object->project = clone $projectstatic;
    }

    $userRead = $projectstatic->restrictedProjectArea($user, 'read');
    $linktocreatetime = '';

	if ($projectstatic->id > 0)
	{
		if ($withproject)
		{
			// Tabs for project
			if (empty($id)) $tab='timespent';
			else $tab='tasks';
			$head=project_prepare_head($projectstatic);
			dol_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public?'projectpub':'project'));

			$param=($mode=='mine'?'&mode=mine':'');

			// Project card

            $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

            $morehtmlref='<div class="refidno">';
            // Title
            $morehtmlref.=$projectstatic->title;
            // Thirdparty
            if ($projectstatic->thirdparty->id > 0)
            {
                $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $projectstatic->thirdparty->getNomUrl(1, 'project');
            }
            $morehtmlref.='</div>';

            // Define a complementary filter for search of next/prev ref.
            if (! $user->rights->projet->all->lire)
            {
                $objectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,0);
                $projectstatic->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
            }

            dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="fichehalfleft">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border" width="100%">';

            // Visibility
            print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
            if ($projectstatic->public) print $langs->trans('SharedProject');
            else print $langs->trans('PrivateProject');
            print '</td></tr>';

            // Date start - end
            print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
            $start = dol_print_date($projectstatic->date_start,'day');
            print ($start?$start:'?');
            $end = dol_print_date($projectstatic->date_end,'day');
            print ' - ';
            print ($end?$end:'?');
            if ($projectstatic->hasDelay()) print img_warning("Late");
            print '</td></tr>';

            // Budget
            print '<tr><td>'.$langs->trans("Budget").'</td><td>';
            if (strcmp($projectstatic->budget_amount, '')) print price($projectstatic->budget_amount,'',$langs,1,0,0,$conf->currency);
            print '</td></tr>';

            // Other attributes
            $cols = 2;
            //include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '</div>';
            print '<div class="fichehalfright">';
            print '<div class="ficheaddleft">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border" width="100%">';

            // Description
            print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
            print nl2br($projectstatic->description);
            print '</td></tr>';

            // Bill time
            if (empty($conf->global->PROJECT_HIDE_TASKS) && ! empty($conf->global->PROJECT_BILL_TIME_SPENT))
            {
	            print '<tr><td>'.$langs->trans("BillTime").'</td><td>';
	            print yn($projectstatic->bill_time);
	            print '</td></tr>';
            }

            // Categories
            if ($conf->categorie->enabled) {
                print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
                print $form->showCategories($projectstatic->id,'project',1);
                print "</td></tr>";
            }

            print '</table>';

            print '</div>';
            print '</div>';
            print '</div>';

            print '<div class="clearboth"></div>';

			dol_fiche_end();

			print '<br>';

			// Link to create time
		    if ($user->rights->projet->all->lire || $user->rights->projet->lire)	// To enter time, read permission is enough
			{
			    if ($projectstatic->public || $userRead > 0)
			    {
			    	if (! empty($projectidforalltimes))		// We are on tab 'Time Spent' of project
			    	{
			    		$backtourl = $_SERVER['PHP_SELF'].'?projectid='.$projectstatic->id.($withproject?'&withproject=1':'');
			    		$linktocreatetime = '<a class="butActionNew" href="'.$_SERVER['PHP_SELF'].'?withproject=1&projectid='.$projectstatic->id.'&action=createtime'.$param.'&backtopage='.urlencode($backtourl).'">'.$langs->trans('AddTimeSpent').'<span class="fa fa-plus-circle valignmiddle"></span></a>';
			    	}
			    	else									// We are on tab 'Time Spent' of task
			    	{
			    		$backtourl = $_SERVER['PHP_SELF'].'?id='.$object->id.($withproject?'&withproject=1':'');
			    		$linktocreatetime = '<a class="butActionNew" href="'.$_SERVER['PHP_SELF'].'?withproject=1'.($object->id > 0 ? '&id='.$object->id : '&projectid='.$projectstatic->id).'&action=createtime'.$param.'&backtopage='.urlencode($backtourl).'">'.$langs->trans('AddTimeSpent').'<span class="fa fa-plus-circle valignmiddle"></span></a>';
			    	}
			    }
			    else
			    {
			    	$linktocreatetime = '<a class="butActionNewRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTime').'<span class="fa fa-plus-circle valignmiddle"></span></a>';
			    }
			}
			else
			{
				$linktocreatetime = '<a class="butActionNewRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('AddTime').'<span class="fa fa-plus-circle valignmiddle"></span></a>';
			}
		}
	}

	if (empty($projectidforalltimes))
	{
		$head=task_prepare_head($object);
		dol_fiche_head($head, 'task_time', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

		if ($action == 'deleteline')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id>0?"id=".$object->id:'projectid='.$projectstatic->id).'&lineid='.GETPOST("lineid",'int').($withproject?'&withproject=1':''),$langs->trans("DeleteATimeSpent"),$langs->trans("ConfirmDeleteATimeSpent"),"confirm_delete",'','',1);
		}

		$param=($withproject?'&withproject=1':'');
		$linkback=$withproject?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

		if (! GETPOST('withproject') || empty($projectstatic->id))
		{
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
			$object->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;

		$morehtmlref='';

		// Project
		if (empty($withproject))
		{
		    $morehtmlref.='<div class="refidno">';
		    $morehtmlref.=$langs->trans("Project").': ';
		    $morehtmlref.=$projectstatic->getNomUrl(1);
		    $morehtmlref.='<br>';

		    // Third party
	    	$morehtmlref.=$langs->trans("ThirdParty").': ';
	    	if (is_object($projectstatic->thirdparty)) {
	    		$morehtmlref.=$projectstatic->thirdparty->getNomUrl(1);
	    	}
		    $morehtmlref.='</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

        print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		// Date start - Date end
		print '<tr><td class="titlefield">'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
		$start = dol_print_date($object->date_start,'dayhour');
		print ($start?$start:'?');
		$end = dol_print_date($object->date_end,'dayhour');
		print ' - ';
		print ($end?$end:'?');
		if ($object->hasDelay()) print img_warning("Late");
		print '</td></tr>';

		// Planned workload
		print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
		if ($object->planned_workload)
		{
			print convertSecondToTime($object->planned_workload,'allhourmin');
		}
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright"><div class="ficheaddleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		// Progress declared
		print '<tr><td class="titlefield">'.$langs->trans("ProgressDeclared").'</td><td>';
		print $object->progress != '' ? $object->progress.' %' : '';
		print '</td></tr>';

		// Progress calculated
		print '<tr><td>'.$langs->trans("ProgressCalculated").'</td><td>';
		if ($object->planned_workload)
		{
			$tmparray=$object->getSummaryOfTimeSpent();
			if ($tmparray['total_duration'] > 0) print round($tmparray['total_duration']/$object->planned_workload*100, 2).' %';
			else print '0 %';
		}
		else print '<span class="opacitymedium">'.$langs->trans("WorkloadNotDefined").'</span>';
		print '</td></tr>';

		print '</table>';

		print '</div>';
		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';

		dol_fiche_end();

		print '<!-- List of time spent for task -->'."\n";

		$title=$langs->trans("ListTaskTimeForTask");
		//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'title_generic.png', 0, '', '', 0, 1);
		print load_fiche_titre($title, $linktocreatetime, 'title_generic.png');

		/*
		 * Form to add time spent on task
		 */

		if ($action == 'createtime' && $object->id > 0 && $user->rights->projet->lire)
		{
			print '<!-- form to add time spent on task -->'."\n";
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addtimespent">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder nohover" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("By").'</td>';
			print '<td>'.$langs->trans("Note").'</td>';
			print '<td>'.$langs->trans("NewTimeSpent").'</td>';
			print '<td>'.$langs->trans("ProgressDeclared").'</td>';
			print '<td></td>';
			print "</tr>\n";

			print '<tr class="oddeven">';

			// Date
			print '<td class="maxwidthonsmartphone">';
			//$newdate=dol_mktime(12,0,0,$_POST["timemonth"],$_POST["timeday"],$_POST["timeyear"]);
			$newdate='';
			print $form->selectDate($newdate, 'time', ($conf->browser->layout == 'phone'?2:1), 1, 2, "timespent_date", 1, 0);
			print '</td>';

			// Contributor
			print '<td class="maxwidthonsmartphone">';
			print img_object('','user','class="hideonsmartphone"');
			$contactsoftask=$object->getListContactId('internal');
			if (count($contactsoftask)>0)
			{
				if(in_array($user->id, $contactsoftask)) $userid = $user->id;
				else $userid=$contactsoftask[0];
				print $form->select_dolusers((GETPOST('userid')?GETPOST('userid'):$userid), 'userid', 0, '', 0, '', $contactsoftask, 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToTheTask"), 'maxwidth200');
			}
			else
			{
				print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
			}
			print '</td>';

			// Note
			print '<td>';
			print '<textarea name="timespent_note" class="maxwidth100onsmartphone" rows="'.ROWS_2.'">'.($_POST['timespent_note']?$_POST['timespent_note']:'').'</textarea>';
			print '</td>';

			// Duration - Time spent
			print '<td>';
			print $form->select_duration('timespent_duration', ($_POST['timespent_duration']?$_POST['timespent_duration']:''), 0, 'text');
			print '</td>';

			// Progress declared
			print '<td class="nowrap">';
			print $formother->select_percent(GETPOST('progress')?GETPOST('progress'):$object->progress, 'progress', 0, 5, 0, 100, 1);
			print '</td>';

			print '<td align="center">';
			print '<input type="submit" name="save" class="button" value="'.$langs->trans("Add").'">';
			print ' &nbsp; ';
			print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';

			print '</table>';
			print '</div>';

			print '</form>';

			print '<br>';
		}
	}

	if ($projectstatic->id > 0)
	{
		if ($action == 'deleteline' && ! empty($projectidforalltimes))
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id>0?"id=".$object->id:'projectid='.$projectstatic->id).'&lineid='.GETPOST('lineid','int').($withproject?'&withproject=1':''),$langs->trans("DeleteATimeSpent"),$langs->trans("ConfirmDeleteATimeSpent"),"confirm_delete",'','',1);
		}

	    // Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
	    $hookmanager->initHooks(array('tasktimelist'));
	    $extrafields = new ExtraFields($db);

	    // Definition of fields for list
	    $arrayfields=array();
	    $arrayfields['t.task_date']=array('label'=>$langs->trans("Date"), 'checked'=>1);
		if ((empty($id) && empty($ref)) || ! empty($projectidforalltimes))   // Not a dedicated task
	    {
    	    $arrayfields['t.task_ref']=array('label'=>$langs->trans("RefTask"), 'checked'=>1);
    	    $arrayfields['t.task_label']=array('label'=>$langs->trans("LabelTask"), 'checked'=>1);
	    }
	    $arrayfields['author']=array('label'=>$langs->trans("By"), 'checked'=>1);
	    $arrayfields['t.note']=array('label'=>$langs->trans("Note"), 'checked'=>1);
	    $arrayfields['t.task_duration']=array('label'=>$langs->trans("Duration"), 'checked'=>1);
	    $arrayfields['value'] =array('label'=>$langs->trans("Value"), 'checked'=>1, 'enabled'=>(empty($conf->salaries->enabled)?0:1));
	    $arrayfields['valuebilled'] =array('label'=>$langs->trans("AmountInvoiced"), 'checked'=>1, 'enabled'=>((! empty($conf->global->PROJECT_HIDE_TASKS) || empty($conf->global->PROJECT_BILL_TIME_SPENT))?0:1));
	    // Extra fields
	    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	    {
	        foreach($extrafields->attribute_label as $key => $val)
	        {
				if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	        }
	    }

		/*
		 *  List of time spent
		 */
		$tasks = array();

		$sql = "SELECT t.rowid, t.fk_task, t.task_date, t.task_datehour, t.task_date_withhour, t.task_duration, t.fk_user, t.note, t.thm,";
		$sql .= " pt.ref, pt.label,";
		$sql .= " u.lastname, u.firstname, u.login, u.photo, u.statut as user_status,";
		$sql .= " il.fk_facture as invoice_id, il.total_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as il ON il.rowid = t.invoice_line_id";
		$sql .= ", ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
		if (empty($projectidforalltimes)) $sql .= " AND t.fk_task =".$object->id;
		else $sql.= " AND pt.fk_projet IN (".$projectidforalltimes.")";
		if ($search_ref) $sql .= natural_search('c.ref', $search_ref);
		if ($search_note) $sql .= natural_search('t.note', $search_note);
		if ($search_task_ref) $sql .= natural_search('pt.ref', $search_task_ref);
		if ($search_task_label) $sql .= natural_search('pt.label', $search_task_label);
		if ($search_user > 0) $sql .= natural_search('t.fk_user', $search_user);
		if ($search_month > 0)
		{
			if ($search_year > 0 && empty($search_day))
			$sql.= " AND t.task_datehour BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
			else if ($search_year > 0 && ! empty($search_day))
			$sql.= " AND t.task_datehour BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
			else
			$sql.= " AND date_format(t.task_datehour, '%m') = '".$db->escape($search_month)."'";
		}
		else if ($search_year > 0)
		{
			$sql.= " AND t.task_datehour BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
		}

		$sql .= $db->order($sortfield, $sortorder);

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$totalnboflines=$num;

			if (! empty($projectidforalltimes))
			{
				print '<!-- List of time spent for project -->'."\n";

				$title=$langs->trans("ListTaskTimeUserProject");
			    //$linktotasks='<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("GoToListOfTasks").'</a>';
			    //print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'title_generic.png', 0, '', '', 0, 1);
			    print load_fiche_titre($title, $linktocreatetime, 'title_generic.png');
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


		/*
		 * Form to add time spent
		 */
		if ($action == 'createtime' && empty($id) && $user->rights->projet->lire)
		{
			print '<!-- form to add time spent -->'."\n";
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addtimespent">';
			print '<input type="hidden" name="projectid" value="'.$projectstatic->id.'">';
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';

			print '<table class="noborder nohover" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Task").'</td>';
			print '<td>'.$langs->trans("By").'</td>';
			print '<td>'.$langs->trans("Note").'</td>';
			print '<td>'.$langs->trans("NewTimeSpent").'</td>';
			print '<td>'.$langs->trans("ProgressDeclared").'</td>';
			print '<td></td>';
			print "</tr>\n";

			print '<tr class="oddeven">';

			// Date
			print '<td class="maxwidthonsmartphone">';
			//$newdate=dol_mktime(12,0,0,$_POST["timemonth"],$_POST["timeday"],$_POST["timeyear"]);
			$newdate='';
			print $form->selectDate($newdate, 'time', ($conf->browser->layout == 'phone'?2:1), 1, 2, "timespent_date", 1, 0);
			print '</td>';

			// Task
			print '<td class="maxwidthonsmartphone">';
			$formproject->selectTasks(-1, GETPOST('taskid','int'), 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth300', $projectstatic->id, '');
			print '</td>';

			// Contributor
			print '<td class="maxwidthonsmartphone">';
			print img_object('','user','class="hideonsmartphone"');
			$contactsofproject=$projectstatic->getListContactId('internal');
			if (count($contactsofproject)>0)
			{
				if (in_array($user->id, $userid=$contactsofproject)) $userid = $user->id;
				else $userid=$contactsofproject[0];
				if ($projectstatic->public) $contactsofproject = array();
				print $form->select_dolusers((GETPOST('userid')?GETPOST('userid'):$userid), 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToProject"), 'maxwidth200');
			}
			else
			{
				print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
			}
			print '</td>';

			// Note
			print '<td>';
			print '<textarea name="timespent_note" class="maxwidth100onsmartphone" rows="'.ROWS_2.'">'.($_POST['timespent_note']?$_POST['timespent_note']:'').'</textarea>';
			print '</td>';

			// Duration - Time spent
			print '<td>';
			$durationtouse = ($_POST['timespent_duration']?$_POST['timespent_duration']:'');
			if (GETPOSTISSET('timespent_durationhour') || GETPOSTISSET('timespent_durationmin'))
			{
				$durationtouse = (GETPOST('timespent_durationhour') * 3600 + GETPOST('timespent_durationmin') * 60);
			}
			print $form->select_duration('timespent_duration', $durationtouse, 0, 'text');
			print '</td>';

			// Progress declared
			print '<td class="nowrap">';
			print $formother->select_percent(GETPOST('progress')?GETPOST('progress'):$object->progress, 'progress', 0, 5, 0, 100, 1);
			print '</td>';

			print '<td align="center">';
			print '<input type="submit" name="save" class="button" value="'.$langs->trans("Add").'">';
			print ' &nbsp; ';
			print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';

			print '</table></form>';

			print '<br>';
		}


		$arrayofselected=is_array($toselect)?$toselect:array();

		$param='';
		if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
		if ($search_month > 0) $param.= '&search_month='.urlencode($search_month);
		if ($search_year > 0) $param.= '&search_year='.urlencode($search_year);
		if ($search_user > 0) $param.= '&search_user='.urlencode($search_user);
		if ($search_task_ref != '') $param.= '&search_task_ref='.urlencode($search_task_ref);
		if ($search_task_label != '') $param.= '&search_task_label='.urlencode($search_task_label);
		if ($search_note != '') $param.= '&search_note='.urlencode($search_note);
		if ($search_duration != '') $param.= '&amp;search_field2='.urlencode($search_duration);
		if ($optioncss != '') $param.='&optioncss='.urlencode($optioncss);
		/*
		// Add $param from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
		*/
		if ($id) $param.='&id='.urlencode($id);
		if ($projectid) $param.='&projectid='.urlencode($projectid);
		if ($withproject) $param.='&withproject='.urlencode($withproject);


		$arrayofmassactions =  array(
		    //'presend'=>$langs->trans("SendByMail"),
		    //'builddoc'=>$langs->trans("PDFMerge"),
		);
		//if ($user->rights->projet->creer) $arrayofmassactions['predelete']=$langs->trans("Delete");
		if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
		$massactionbutton=$form->selectMassAction('', $arrayofmassactions);


		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		if ($action == 'editline') print '<input type="hidden" name="action" value="updateline">';
		else print '<input type="hidden" name="action" value="list">';
	    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	    print '<input type="hidden" name="page" value="'.$page.'">';

		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="projectid" value="'.$projectidforalltimes.'">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';

		$moreforfilter = '';

		$parameters=array();
		$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
		else $moreforfilter = $hookmanager->resPrint;

		if (! empty($moreforfilter))
		{
		    print '<div class="liste_titre liste_titre_bydiv centpercent">';
		    print $moreforfilter;
		    print '</div>';
		}

		$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
		$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

        print '<div class="div-table-responsive">';
		print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

		// Fields title search
		print '<tr class="liste_titre_filter">';
		// Date
		if (! empty($arrayfields['t.task_date']['checked']))
		{
			print '<td class="liste_titre">';
			if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
			print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
			$formother->select_year($search_year,'search_year',1, 20, 5);
			print '</td>';
		}
		if ((empty($id) && empty($ref)) || ! empty($projectidforalltimes))   // Not a dedicated task
        {
            if (! empty($arrayfields['t.task_ref']['checked'])) print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'"></td>';
            if (! empty($arrayfields['t.task_label']['checked'])) print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
        }
        // Author
        if (! empty($arrayfields['author']['checked'])) print '<td class="liste_titre">'.$form->select_dolusers(($search_user > 0 ? $search_user : -1), 'search_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200').'</td>';
		// Note
        if (! empty($arrayfields['t.note']['checked'])) print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_note" value="'.dol_escape_htmltag($search_note).'"></td>';
		// Duration
        if (! empty($arrayfields['t.task_duration']['checked'])) print '<td class="liste_titre right"></td>';
		// Value in main currency
        if (! empty($arrayfields['value']['checked'])) print '<td class="liste_titre"></td>';
        // Value billed
        if (! empty($arrayfields['valuebilled']['checked'])) print '<td class="liste_titre"></td>';
        /*
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
		*/
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields);
		$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Action column
		print '<td class="liste_titre center">';
		$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
		print $searchpicto;
		print '</td>';
		print '</tr>'."\n";

		print '<tr class="liste_titre">';
		if (! empty($arrayfields['t.task_date']['checked']))      print_liste_field_titre($arrayfields['t.task_date']['label'],$_SERVER['PHP_SELF'],'t.task_date,t.task_datehour,t.rowid','',$param,'',$sortfield,$sortorder);
		if ((empty($id) && empty($ref)) || ! empty($projectidforalltimes))   // Not a dedicated task
        {
            if (! empty($arrayfields['t.task_ref']['checked']))   print_liste_field_titre($arrayfields['t.task_ref']['label'],$_SERVER['PHP_SELF'],'pt.ref','',$param,'',$sortfield,$sortorder);
            if (! empty($arrayfields['t.task_label']['checked'])) print_liste_field_titre($arrayfields['t.task_label']['label'],$_SERVER['PHP_SELF'],'pt.label','',$param,'',$sortfield,$sortorder);
        }
        if (! empty($arrayfields['author']['checked']))           print_liste_field_titre($arrayfields['author']['label'],$_SERVER['PHP_SELF'],'','',$param,'',$sortfield,$sortorder);
		if (! empty($arrayfields['t.note']['checked']))           print_liste_field_titre($arrayfields['t.note']['label'],$_SERVER['PHP_SELF'],'t.note','',$param,'',$sortfield,$sortorder);
		if (! empty($arrayfields['t.task_duration']['checked']))  print_liste_field_titre($arrayfields['t.task_duration']['label'],$_SERVER['PHP_SELF'],'t.task_duration','',$param,'align="right"',$sortfield,$sortorder);
		if (! empty($arrayfields['value']['checked']))            print_liste_field_titre($arrayfields['value']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
		if (! empty($arrayfields['valuebilled']['checked']))      print_liste_field_titre($arrayfields['valuebilled']['label'],$_SERVER['PHP_SELF'],'il.total_ht','',$param,'align="right"',$sortfield,$sortorder);
		/*
    	// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
		*/
	    // Hook fields
		$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
        $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
    	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center" width="80"',$sortfield,$sortorder,'maxwidthsearch ');
		print "</tr>\n";

		$tasktmp = new Task($db);

		$i = 0;

		$childids = $user->getAllChildIds();

		$total = 0;
		$totalvalue = 0;
		$totalarray=array();
		foreach ($tasks as $task_time)
		{
			print '<tr class="oddeven">';

			$date1=$db->jdate($task_time->task_date);
			$date2=$db->jdate($task_time->task_datehour);

			// Date
			if (! empty($arrayfields['t.task_date']['checked']))
			{
    			print '<td class="nowrap">';
    			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
    			{
    				if (empty($task_time->task_date_withhour))
    				{
    					print $form->selectDate(($date2?$date2:$date1), 'timeline', 3, 3, 2, "timespent_date", 1, 0);
    				}
    				else print $form->selectDate(($date2?$date2:$date1), 'timeline', 1, 1, 2, "timespent_date", 1, 0);
    			}
    			else
    			{
    				print dol_print_date(($date2?$date2:$date1),($task_time->task_date_withhour?'dayhour':'day'));
    			}
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
			}

			// Task ref
            if (! empty($arrayfields['t.task_ref']['checked']))
            {
        		if ((empty($id) && empty($ref)) || ! empty($projectidforalltimes))   // Not a dedicated task
    			{
        			print '<td class="nowrap">';
        			$tasktmp->id = $task_time->fk_task;
        			$tasktmp->ref = $task_time->ref;
        			$tasktmp->label = $task_time->label;
        			print $tasktmp->getNomUrl(1, 'withproject', 'time');
        			print '</td>';
        			if (! $i) $totalarray['nbfield']++;
    			}
            }

			// Task label
            if (! empty($arrayfields['t.task_label']['checked']))
            {
        		if ((empty($id) && empty($ref)) || ! empty($projectidforalltimes))   // Not a dedicated task
    			{
        			print '<td class="nowrap">';
        			print $task_time->label;
        			print '</td>';
        			if (! $i) $totalarray['nbfield']++;
    			}
            }

            // User
            if (! empty($arrayfields['author']['checked']))
            {
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
    				$userstatic->photo      = $task_time->photo;
    				$userstatic->statut     = $task_time->user_status;
    				print $userstatic->getNomUrl(-1);
    			}
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
            }

			// Note
            if (! empty($arrayfields['t.note']['checked']))
            {
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
    			if (! $i) $totalarray['nbfield']++;
            }

			// Time spent
            if (! empty($arrayfields['t.task_duration']['checked']))
            {
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
    			if (! $i) $totalarray['nbfield']++;
    			if (! $i) $totalarray['totaldurationfield']=$totalarray['nbfield'];
    			$totalarray['totalduration'] += $task_time->task_duration;
            }

			// Value spent
            if (! empty($arrayfields['value']['checked']))
            {
				print '<td align="right">';
				$value = price2num($task_time->thm * $task_time->task_duration / 3600);
				print price($value, 1, $langs, 1, -1, -1, $conf->currency);
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
    			if (! $i) $totalarray['totalvaluefield']=$totalarray['nbfield'];
    			$totalarray['totalvalue'] += $value;
            }

            // Value billed
            if (! empty($arrayfields['valuebilled']['checked']))
            {
            	print '<td align="right">';
            	$valuebilled = price2num($task_time->total_ht);
            	if (isset($task_time->total_ht)) print price($valuebilled, 1, $langs, 1, -1, -1, $conf->currency);
            	print '</td>';
            	if (! $i) $totalarray['nbfield']++;
            	if (! $i) $totalarray['totalvaluebilledfield']=$totalarray['nbfield'];
            	$totalarray['totalvaluebilled'] += $valuebilled;
            }

            /*
            // Extra fields
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
            */

			// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$task_time);
			$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

            // Action column
			print '<td class="center nowraponall">';
			if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print '<input type="hidden" name="lineid" value="'.$_GET['lineid'].'">';
				print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
				print '<br>';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
			}
			else if ($user->rights->projet->lire || $user->rights->projet->all->creer)    // Read project and enter time consumed on assigned tasks
			{
				if ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids) || $user->rights->projet->all->creer)
				{
					//$param = ($projectidforalltimes?'projectid='.$projectidforalltimes.'&amp;':'').'.($withproject?'&amp;withproject=1':'');
					print '&nbsp;';
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&amp;action=editline&amp;lineid='.$task_time->rowid.$param.'">';
					print img_edit();
					print '</a>';

					print '&nbsp;';
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&amp;action=deleteline&amp;lineid='.$task_time->rowid.$param.'">';
					print img_delete();
					print '</a>';
			    }
			}
        	print '</td>';
        	if (! $i) $totalarray['nbfield']++;

			print "</tr>\n";

			$i++;
		}

		// Show total line
		if (isset($totalarray['totaldurationfield']) || isset($totalarray['totalvaluefield']))
		{
		    print '<tr class="liste_total">';
		    $i=0;
		    while ($i < $totalarray['nbfield'])
		    {
		        $i++;
		        if ($i == 1)
		        {
		            if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
		            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
		        }
		        elseif ($totalarray['totaldurationfield'] == $i) print '<td align="right">'.convertSecondToTime($totalarray['totalduration'],'allhourmin').'</td>';
		        elseif ($totalarray['totalvaluefield'] == $i) print '<td align="right">'.price($totalarray['totalvalue']).'</td>';
		        elseif ($totalarray['totalvaluebilledfield'] == $i) print '<td align="right">'.price($totalarray['totalvaluebilled']).'</td>';
		        else print '<td></td>';
		    }
		    print '</tr>';
		}

		print '</tr>';

		print "</table>";
		print '</div>';
		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
