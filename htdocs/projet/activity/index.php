<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/projet/activity/index.php
 *	\ingroup    projet
 *	\brief      Page activite perso du module projet
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$search_project_user = GETPOST('search_project_user','int');
$mine = GETPOST('mode','aZ09')=='mine' ? 1 : 0;
if ($search_project_user == $user->id) $mine = 1;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
//$result = restrictedArea($user, 'projet', $projectid);
if (!$user->rights->projet->lire) accessforbidden();


$langs->load("projects");


/*
 * View
 */

$now = dol_now();
$tmp=dol_getdate($now);
$day=$tmp['mday'];
$month=$tmp['mon'];
$year=$tmp['year'];

$projectstatic=new Project($db);
$taskstatic=new Task($db);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);  // Return all projects I have permission on because I want my tasks and some of my task may be on a public projet that is not my project
$taskstatic=new Task($db);
$tasktmp=new Task($db);

$title=$langs->trans("Activities");
//if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title);


// Title for combo list see all projects
$titleall=$langs->trans("AllAllowedProjects");
if (! empty($user->rights->projet->all->lire) && ! $socid) $titleall=$langs->trans("AllProjects");
else $titleall=$langs->trans("AllAllowedProjects").'<br><br>';


$morehtml='';
$morehtml.='<form name="projectform">';
$morehtml.='<SELECT name="mode">';
$morehtml.='<option name="all" value="all"'.($mine?'':' selected').'>'.$titleall.'</option>';
$morehtml.='<option name="mine" value="'.$user->id.'"'.(($search_project_user == $user->id)?' selected':'').'>'.$langs->trans("ProjectsImContactFor").'</option>';
$morehtml.='</SELECT>';
$morehtml.='<input type="submit" class="button" name="refresh" value="'.$langs->trans("Refresh").'">';

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, -1, 'title_project.png', 0, $morehtml);
//print load_fiche_titre($title, '', 'title_project');

if ($mine) print $langs->trans("MyTasksDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("TasksDesc").'<br><br>';
	else print $langs->trans("TasksPublicDesc").'<br><br>';
}


print '<div class="fichecenter"><div class="fichethirdleft">';


if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search project
    if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
    {
    	$listofsearchfields['search_task']=array('text'=>'Task');
    }

    if (count($listofsearchfields))
    {
    	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	print '<table class="noborder nohover centpercent">';
    	$i=0;
    	foreach($listofsearchfields as $key => $value)
    	{
    		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    		print '<tr '.$bc[false].'>';
    		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
    		if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    		print '</tr>';
    		$i++;
    	}
    	print '</table>';
    	print '</form>';
    	print '<br>';
    }
}


/* Affichage de la liste des projets d'aujourd'hui */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('ActivityOnProjectToday').'</td>';
print '<td width="50%" align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, p.public, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND task_date BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title, p.public";

$resql = $db->query($sql);
if ( $resql )
{
	$var=true;
	$total=0;

	while ($row = $db->fetch_object($resql))
	{

		print '<tr class="oddeven">';
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		$projectstatic->title=$row->title;
		$projectstatic->public=$row->public;
		print $projectstatic->getNomUrl(1, '', 1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb, 'allhourmin').'</td>';
		print "</tr>\n";
		$total += $row->nb;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total, 'allhourmin').'</td>';
print "</tr>\n";
print "</table>";


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/* Affichage de la liste des projets d'hier */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('ActivityOnProjectYesterday').'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, p.public, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND task_date BETWEEN '".$db->idate(dol_time_plus_duree(dol_mktime(0, 0, 0, $month, $day, $year), -1, 'd'))."' AND '".$db->idate(dol_time_plus_duree(dol_mktime(23, 59, 59, $month, $day, $year), -1, 'd'))."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title, p.public";

$resql = $db->query($sql);
if ( $resql )
{
	$var=true;
	$total=0;

	while ($row = $db->fetch_object($resql))
	{

		print '<tr class="oddeven">';
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		$projectstatic->title=$row->title;
		$projectstatic->public=$row->public;
		print $projectstatic->getNomUrl(1, '', 1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb, 'allhourmin').'</td>';
		print "</tr>\n";
		$total += $row->nb;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total, 'allhourmin').'</td>';
print "</tr>\n";
print "</table>";



/*
if ($db->type != 'pgsql')
{
    print '<br>';

    // Affichage de la liste des projets de la semaine
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("ActivityOnProjectThisWeek").'</td>';
    print '<td align="right">'.$langs->trans("Time").'</td>';
    print "</tr>\n";

    $sql = "SELECT p.rowid, p.ref, p.title, p.public, SUM(tt.task_duration) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
    $sql.= " , ".MAIN_DB_PREFIX."projet_task as t";
    $sql.= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
    $sql.= " WHERE t.fk_projet = p.rowid";
    $sql.= " AND p.entity = ".$conf->entity;
    $sql.= " AND tt.fk_task = t.rowid";
    $sql.= " AND tt.fk_user = ".$user->id;
    $sql.= " AND task_date >= '".$db->idate(dol_get_first_day($year, $month)).'" AND ...";
    $sql.= " AND p.rowid in (".$projectsListId.")";
    $sql.= " GROUP BY p.rowid, p.ref, p.title";

    $resql = $db->query($sql);
    if ( $resql )
    {
    	$total = 0;
    	$var=true;

    	while ($row = $db->fetch_object($resql))
    	{

    		print '<tr class="oddeven">';
    		print '<td>';
    		$projectstatic->id=$row->rowid;
    		$projectstatic->ref=$row->ref;
    		$projectstatic->title=$row->title;
    		$projectstatic->public=$row->public;
    		print $projectstatic->getNomUrl(1, '', 1);
    		print '</td>';
    		print '<td align="right">'.convertSecondToTime($row->nb, 'allhourmin').'</td>';
    		print "</tr>\n";
    		$total += $row->nb;
    	}

    	$db->free($resql);
    }
    else
    {
    	dol_print_error($db);
    }
    print '<tr class="liste_total">';
    print '<td>'.$langs->trans('Total').'</td>';
    print '<td align="right">'.convertSecondToTime($total, 'allhourmin').'</td>';
    print "</tr>\n";
    print "</table><br>";

}
*/

/* Affichage de la liste des projets du mois */
if (! empty($conf->global->PROJECT_TASK_TIME_MONTH))
{
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("ActivityOnProjectThisMonth").': '.dol_print_date($now,"%B %Y").'</td>';
    print '<td align="right">'.$langs->trans("Time").'</td>';
    print "</tr>\n";

    $sql = "SELECT p.rowid, p.ref, p.title, p.public, SUM(tt.task_duration) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
    $sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
    $sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
    $sql.= " WHERE t.fk_projet = p.rowid";
    $sql.= " AND p.entity = ".$conf->entity;
    $sql.= " AND tt.fk_task = t.rowid";
    $sql.= " AND tt.fk_user = ".$user->id;
	$sql.= " AND task_date BETWEEN '".$db->idate(dol_get_first_day($year, $month))."' AND '".$db->idate(dol_get_last_day($year, $month))."'";
    $sql.= " AND p.rowid in (".$projectsListId.")";
    $sql.= " GROUP BY p.rowid, p.ref, p.title, p.public";

    $resql = $db->query($sql);
    if ( $resql )
    {
    	$var=false;

    	while ($row = $db->fetch_object($resql))
    	{
    		print '<tr class="oddeven">';
    		print '<td>';
    		$projectstatic->id=$row->rowid;
    		$projectstatic->ref=$row->ref;
    		$projectstatic->title=$row->title;
    		print $projectstatic->getNomUrl(1, '', 1);
    		print '</td>';
    		print '<td align="right">'.convertSecondToTime($row->nb, 'allhourmin').'</td>';
    		print "</tr>\n";

    	}
    	$db->free($resql);
    }
    else
    {
    	dol_print_error($db);
    }
    print '<tr class="liste_total">';
    print '<td>'.$langs->trans('Total').'</td>';
    print '<td align="right">'.convertSecondToTime($total, 'allhourmin').'</td>';
    print "</tr>\n";
    print "</table>";
}

/* Affichage de la liste des projets de l'annee */
if (! empty($conf->global->PROJECT_TASK_TIME_YEAR))
{
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ActivityOnProjectThisYear").': '.strftime("%Y", $now).'</td>';
	print '<td align="right">'.$langs->trans("Time").'</td>';
	print "</tr>\n";

	$sql = "SELECT p.rowid, p.ref, p.title, p.public, SUM(tt.task_duration) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
	$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
	$sql.= " WHERE t.fk_projet = p.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	$sql.= " AND tt.fk_task = t.rowid";
	$sql.= " AND tt.fk_user = ".$user->id;
	$sql.= " AND YEAR(task_date) = '".strftime("%Y",$now)."'";
	$sql.= " AND p.rowid in (".$projectsListId.")";
	$sql.= " GROUP BY p.rowid, p.ref, p.title, p.public";

	$var=false;
	$resql = $db->query($sql);
	if ( $resql )
	{
		while ($row = $db->fetch_object($resql))
		{
			print '<tr class="oddeven">';
			print '<td>';
			$projectstatic->id=$row->rowid;
			$projectstatic->ref=$row->ref;
			$projectstatic->title=$row->title;
			$projectstatic->public=$row->public;
			print $projectstatic->getNomUrl(1, '', 1);
			print '</td>';
			print '<td align="right">'.convertSecondToTime($row->nb, 'allhourmin').'</td>';
			print "</tr>\n";

		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	print '<tr class="liste_total">';
	print '<td>'.$langs->trans('Total').'</td>';
	print '<td align="right">'.convertSecondToTime($total, 'allhourmin').'</td>';
	print "</tr>\n";
	print "</table>";
}

if (empty($conf->global->PROJECT_HIDE_TASKS) && ! empty($conf->global->PROJECT_SHOW_TASK_LIST_ON_PROJECT_AREA))
{
    // Get id of types of contacts for projects (This list never contains a lot of elements)
    $listofprojectcontacttype=array();
    $sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
    $sql.= " WHERE ctc.element = '" . $projectstatic->element . "'";
    $sql.= " AND ctc.source = 'internal'";
    $resql = $db->query($sql);
    if ($resql)
    {
        while($obj = $db->fetch_object($resql))
        {
            $listofprojectcontacttype[$obj->rowid]=$obj->code;
        }
    }
    else dol_print_error($db);
    if (count($listofprojectcontacttype) == 0) $listofprojectcontacttype[0]='0';    // To avoid sql syntax error if not found
    // Get id of types of contacts for tasks (This list never contains a lot of elements)
    $listoftaskcontacttype=array();
    $sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
    $sql.= " WHERE ctc.element = '" . $taskstatic->element . "'";
    $sql.= " AND ctc.source = 'internal'";
    $resql = $db->query($sql);
    if ($resql)
    {
        while($obj = $db->fetch_object($resql))
        {
            $listoftaskcontacttype[$obj->rowid]=$obj->code;
        }
    }
    else dol_print_error($db);
    if (count($listoftaskcontacttype) == 0) $listoftaskcontacttype[0]='0';         // To avoid sql syntax error if not found


	// Tasks for all resources of all opened projects and time spent for each task/resource
	// This list can be very long, so we don't show it by default on task area. We prefer to use the list page.
    // Add constant PROJECT_SHOW_TASK_LIST_ON_PROJECT_AREA to show this list

	$max = (empty($conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA)?1000:$conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA);

	$sql = "SELECT p.ref, p.title, p.rowid as projectid, p.fk_statut as status, p.fk_opp_status as opp_status, p.public, p.dateo as projdateo, p.datee as projdatee,";
	$sql.= " t.label, t.rowid as taskid, t.planned_workload, t.duration_effective, t.progress, t.dateo, t.datee, SUM(tasktime.task_duration) as timespent";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time as tasktime on tasktime.fk_task = t.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on tasktime.fk_user = u.rowid";
	if ($mine)
	{
	    $sql.= ", " . MAIN_DB_PREFIX . "element_contact as ect";
	}
	$sql.= " WHERE p.entity IN (".getEntity('project').")";
	if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";  // project i have permission on
	if ($mine)     // this may duplicate record if we are contact twice
	{
        $sql.= " AND ect.fk_c_type_contact IN (".join(',',array_keys($listoftaskcontacttype)).") AND ect.element_id = t.rowid AND ect.fk_socpeople = ".$user->id;
	}
	if ($socid)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
	$sql.= " AND p.fk_statut=1";
	$sql.= " GROUP BY p.ref, p.title, p.rowid, p.fk_statut, p.fk_opp_status, p.public, t.label, t.rowid, t.planned_workload, t.duration_effective, t.progress, t.dateo, t.datee";
	$sql.= " ORDER BY t.dateo desc, t.rowid desc, t.datee";
	$sql.= $db->plimit($max+1);	// We want more to know if we have more than limit

	$var=true;

	dol_syslog('projet:index.php: affectationpercent', LOG_DEBUG);
	$resql = $db->query($sql);
	if ( $resql )
	{
		$num = $db->num_rows($resql);
		$i = 0;

		//print load_fiche_titre($langs->trans("TasksOnOpenedProject"),'','').'<br>';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		//print '<th>'.$langs->trans('TaskRessourceLinks').'</th>';
		print '<th>'.$langs->trans('OpenedProjects').'</th>';
		if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) print '<th>'.$langs->trans('OpportunityStatus').'</th>';
		print '<th>'.$langs->trans('Task').'</th>';
		print '<th align="center">'.$langs->trans('DateStart').'</th>';
		print '<th align="center">'.$langs->trans('DateEnd').'</th>';
		print '<th align="right">'.$langs->trans('PlannedWorkload').'</th>';
		print '<th align="right">'.$langs->trans('TimeSpent').'</th>';
		print '<th align="right">'.$langs->trans("ProgressCalculated").'</td>';
		print '<th align="right">'.$langs->trans("ProgressDeclared").'</td>';
		print '</tr>';

		while ($i < $num && $i < $max)
		{
			$obj = $db->fetch_object($resql);


			$username='';
			if ($obj->userid && $userstatic->id != $obj->userid)	// We have a user and it is not last loaded user
			{
				$result=$userstatic->fetch($obj->userid);
				if (! $result) $userstatic->id=0;
			}
			if ($userstatic->id) $username = $userstatic->getNomUrl(0,0);

			print '<tr class="oddeven">';
			//print '<td>'.$username.'</td>';
			print '<td>';
			$projectstatic->id=$obj->projectid;
			$projectstatic->ref=$obj->ref;
			$projectstatic->title=$obj->title;
			$projectstatic->statut = $obj->status;
			$projectstatic->public = $obj->public;
			$projectstatic->dateo = $db->jdate($obj->projdateo);
			$projectstatic->datee = $db->jdate($obj->projdatee);

			print $projectstatic->getNomUrl(1,'',0,'','<br>');
			print '</td>';
			if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
			{
				print '<td>';
				$code = dol_getIdFromCode($db, $obj->opp_status, 'c_lead_status', 'rowid', 'code');
        		if ($code) print $langs->trans("OppStatus".$code);
				print '</td>';
			}
			print '<td>';
			if (! empty($obj->taskid))
			{
				$tasktmp->id = $obj->taskid;
				$tasktmp->ref = $obj->ref;
				$tasktmp->label = $obj->label;
				print $tasktmp->getNomUrl(1,'withproject','task',1,'<br>');
			}
			else print $langs->trans("NoTasks");
			print '</td>';
			$taskstatic->projectstatus = $obj->projectstatus;
			$taskstatic->progress = $obj->progress;
			$taskstatic->fk_statut = $obj->status;
			$taskstatic->dateo = $db->jdate($obj->dateo);
			$taskstatic->datee = $db->jdate($obj->datee);
			print '<td align="center">'.dol_print_date($db->jdate($obj->dateo),'day').'</td>';
			print '<td align="center">'.dol_print_date($db->jdate($obj->datee),'day');
			print dol_print_date($obj->date_end,'dayhour');
			if ($taskstatic->hasDelay()) print img_warning($langs->trans("Late"));
			print '</td>';
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$obj->taskid.'&withproject=1">';
			print convertSecondToTime($obj->planned_workload, 'allhourmin');
			print '</a></td>';
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$obj->taskid.'&withproject=1">';
			print convertSecondToTime($obj->timespent, 'allhourmin');
			print '</a></td>';
			print '<td align="right">';
			if (! empty($obj->taskid))
			{
				if (empty($obj->planned_workload) > 0) {
					$percentcompletion = $langs->trans("WorkloadNotDefined");
				} else {
					$percentcompletion = intval($obj->duration_effective*100/$obj->planned_workload).'%';
				}
			}
			print $percentcompletion;
			print '</td>';
			print '<td align="right">';
			print ($obj->taskid>0)?$obj->progress.'%':'';
			print '</td>';
			print "</tr>\n";

			$i++;
		}

		if ($num > $max)
		{
			$colspan=6;
			if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) $colspan++;
			print '<tr><td colspan="'.$colspan.'">'.$langs->trans("WarningTooManyDataPleaseUseMoreFilters").'</td></tr>';
		}

		print "</table>";


		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

}


print '</div></div></div>';


llxFooter();

$db->close();
