<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
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
 *	\file       htdocs/projet/activity/perweek.php
 *	\ingroup    projet
 *	\brief      List activities of tasks (per week entry)
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load('projects');

$action=GETPOST('action');
$mode=GETPOST("mode");
$id=GETPOST('id','int');
$taskid=GETPOST('taskid');


$mine=0;
if ($mode == 'mine') $mine=1;

$projectid='';
$projectid=isset($_GET["id"])?$_GET["id"]:$_POST["projectid"];

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);

$now=dol_now();
$nowtmp=dol_getdate($now);
$nowday=$nowtmp['mday'];
$nowmonth=$nowtmp['mon'];
$nowyear=$nowtmp['year'];
$year=GETPOST('reyear')?GETPOST('reyear'):(GETPOST("year","int")?GETPOST("year","int"):date("Y"));
$month=GETPOST('remonth')?GETPOST('remonth'):(GETPOST("month","int")?GETPOST("month","int"):date("m"));
$day=GETPOST('reday')?GETPOST('reday'):(GETPOST("day","int")?GETPOST("day","int"):date("d"));
$day = (int) $day;
$week=GETPOST("week","int")?GETPOST("week","int"):date("W");

$startdayarray=dol_get_first_day_week($day, $month, $year);

$prev = $startdayarray;
$prev_year  = $prev['prev_year'];
$prev_month = $prev['prev_month'];
$prev_day   = $prev['prev_day'];
$first_day  = $prev['first_day'];
$first_month= $prev['first_month'];
$first_year = $prev['first_year'];
$week = $prev['week'];

$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
$next_year  = $next['year'];
$next_month = $next['month'];
$next_day   = $next['day'];

// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow=dol_mktime(0,0,0,$first_month,$first_day,$first_year);
$lastdaytoshow=dol_time_plus_duree($firstdaytoshow, 7, 'd');

$usertoprocess=$user;

$object=new Task($db);


/*
 * Actions
 */

if (GETPOST('submitdateselect'))
{
	$daytoparse = dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

	$action = '';
}

if ($action == 'assign')
{
    if ($taskid > 0)
    {
		$result = $object->fetch($taskid, $ref);
		if ($result < 0) $error++;
    }
    else
    {
    	setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), '', 'errors');
    	$error++;
    }
    if (! GETPOST('type'))
    {
    	setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), '', 'errors');
    	$error++;
    }
    if (! $error)
    {
    	$idfortaskuser=$user->id;
		$result = $object->add_contact($idfortaskuser, GETPOST("type"), 'internal');
    }

	if ($result < 0)
	{
		$error++;
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'warnings');
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (! $error)
	{
		setEventMessages("TaskAssignedToEnterTime", null);
	}

	$action='';
}

if ($action == 'addtime' && $user->rights->projet->creer)
{
    $timetoadd=$_POST['task'];
	if (empty($timetoadd))
	{
	    setEventMessage($langs->trans("ErrorTimeSpentIsEmpty"), 'errors');
    }
	else
	{
		foreach($timetoadd as $taskid => $value)
	    {
			foreach($value as $key => $val)
			{
				$amountoadd=$timetoadd[$taskid][$key];
		    	if (! empty($amountoadd))
		        {
		        	$tmpduration=explode(':',$amountoadd);
		        	$newduration=0;
					if (! empty($tmpduration[0])) $newduration+=($tmpduration[0] * 3600);
					if (! empty($tmpduration[1])) $newduration+=($tmpduration[1] * 60);
					if (! empty($tmpduration[2])) $newduration+=($tmpduration[2]);

		        	if ($newduration > 0)
		        	{
		       	        $object->fetch($taskid);
					    $object->progress = GETPOST($taskid . 'progress', 'int');
				        $object->timespent_duration = $newduration;
				        $object->timespent_fk_user = $usertoprocess->id;
			        	$object->timespent_date = dol_time_plus_duree($firstdaytoshow, $key, 'd');

						$result=$object->addTimeSpent($user);
						if ($result < 0)
						{
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
							break;
						}
		        	}
		        }
			}
	    }

	   	if (! $error)
	   	{
	    	setEventMessage($langs->trans("RecordSaved"));

	   	    // Redirect to avoid submit twice on back
	       	header('Location: '.$_SERVER["PHP_SELF"].($projectid?'?id='.$projectid:'?').($mode?'&mode='.$mode:''));
	       	exit;
	   	}
	}
}



/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$formcompany=new FormCompany($db);
$formproject=new FormProjets($db);
$projectstatic=new Project($db);
$project = new Project($db);
$taskstatic = new Task($db);

$title=$langs->trans("TimeSpent");
if ($mine) $title=$langs->trans("MyTimeSpent");

//$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($usertoprocess,0,1);  // Return all project i have permission on. I want my tasks and some of my task may be on a public projet that is not my project

if ($id)
{
    $project->fetch($id);
    $project->fetch_thirdparty();
}

$onlyopened=1;	// or -1
$tasksarray=$taskstatic->getTasksArray(0,0,($project->id?$project->id:$projectsListId),$socid,0,'',$onlyopened);    // We want to see all task of opened project i am allowed to see, not only mine. Later only mine will be editable later.
$projectsrole=$taskstatic->getUserRolesForProjectsOrTasks($usertoprocess,0,($project->id?$project->id:$projectsListId),0);
$tasksrole=$taskstatic->getUserRolesForProjectsOrTasks(0,$usertoprocess,($project->id?$project->id:$projectsListId),0);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


llxHeader("",$title,"",'','','',array('/core/js/timesheet.js'));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'title_project');

$param=($mode?'&amp;mode='.$mode:'');

// Show navigation bar
$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0,0,0,$first_month,$first_day,$first_year),"%Y").", ".$langs->trans("Week")." ".$week." </span>\n";
$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\">".img_next($langs->trans("Next"))."</a>\n";
$nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
$nav.='<br>'.$form->select_date(-1,'',0,0,2,"addtime",1,0,1).' ';
$nav.=' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';

$picto='calendarweek';

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="day" value="'.$day.'">';
print '<input type="hidden" name="month" value="'.$month.'">';
print '<input type="hidden" name="year" value="'.$year.'">';

$head=project_timesheet_prepare_head($mode);
dol_fiche_head($head, 'inputperweek', '', 0, 'task');

// Show description of content
if ($mine) print $langs->trans("MyTasksDesc").($onlyopened?' '.$langs->trans("OnlyOpenedProject"):'').'<br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").($onlyopened?' '.$langs->trans("OnlyOpenedProject"):'').'<br>';
	else print $langs->trans("ProjectsPublicTaskDesc").($onlyopened?' '.$langs->trans("AlsoOnlyOpenedProject"):'').'<br>';
}
if ($mine)
{
	print $langs->trans("OnlyYourTaskAreVisible").'<br>';
}
else
{
	print $langs->trans("AllTaskVisibleButEditIfYouAreAssigned").'<br>';
}
print '<br>';
print "\n";

// Filter on user
/*	dol_fiche_head('');
	print '<table class="border" width="100%"><tr><td width="25%">'.$langs->trans("User").'</td>';
	print '<td>';
	if ($mine) print $user->getLoginUrl(1);
	print '</td>';
	print '</tr></table>';
	dol_fiche_end();
*/

// Filter on user
/*	dol_fiche_head('');
	print '<table class="border" width="100%"><tr><td width="25%">'.$langs->trans("User").'</td>';
	print '<td>';
	if ($mine) print $user->getLoginUrl(1);
	print '</td>';
	print '</tr></table>';
	dol_fiche_end();
*/


print '<div align="right">'.$nav.'</div>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="right">'.$langs->trans("PlannedWorkload").'</td>';
print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
if ($usertoprocess->id == $user->id) print '<td align="right">'.$langs->trans("TimeSpentByYou").'</td>';
else print '<td align="right">'.$langs->trans("TimeSpentByUser").'</td>';

$startday=dol_mktime(12, 0, 0, $startdayarray['first_month'], $startdayarray['first_day'], $startdayarray['first_year']);

for($i=0;$i<7;$i++)
{
	print '<td width="7%" align="center">'.dol_print_date($startday + ($i * 3600 * 24), '%a').'<br>'.dol_print_date($startday + ($i * 3600 * 24), 'day').'</td>';
}
print '<td class="liste_total"></td>';

print "</tr>\n";

// By default, we can edit only tasks we are assigned to
$restrictviewformytask=(empty($conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED)?1:0);

if (count($tasksarray) > 0)
{
	$j=0;
	projectLinesPerWeek($j, $firstdaytoshow, $usertoprocess, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask);

	print '<tr class="liste_total">
                <td class="liste_total" colspan="7" align="right">'.$langs->trans("Total").'</td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[0]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[1]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[2]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[3]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[4]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[5]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[6]">&nbsp;</div></td>
                <td class="liste_total"></td>
    </tr>';
}
else
{
	print '<tr><td colspan="11">'.$langs->trans("NoTasks").'</td></tr>';
}
print "</table>";

print '<input type="hidden" name="timestamp" value="1425423513"/>'."\n";
print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
print '</div>';

print '</form>'."\n\n";

$modeinput='hours';

print '<script type="text/javascript">';
print "jQuery(document).ready(function () {\n";
print '		jQuery(".timesheetalreadyrecorded").tipTip({ maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50, content: \''.dol_escape_js($langs->trans("TimeAlreadyRecorded", $user->getFullName($langs))).'\'});';
$i=0;
while ($i < 7)
{
	print '    updateTotal('.$i.',\''.$modeinput.'\');';
	$i++;
}
print "});";
print '</script>';


// Add a new project/task
print '<br>';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="assign">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="year" value="'.$year.'">';
print '<input type="hidden" name="month" value="'.$month.'">';
print '<input type="hidden" name="day" value="'.$day.'">';
print $langs->trans("AssignTaskToMe").'<br>';
$formproject->selectTasks($socid?$socid:-1, $taskid, 'taskid', 32, 0, 1, 1);
print $formcompany->selectTypeContact($object, '', 'type','internal','rowid', 1);
print '<input type="submit" class="button" name="submit" value="'.$langs->trans("AssignTask").'">';
print '</form>';



llxFooter();

$db->close();
