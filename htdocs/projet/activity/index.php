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

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

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

$projectstatic=new Project($db);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);  // Return all projects I have permission on because I want my tasks and some of my task may be on a public projet that is not my project
$tasktmp=new Task($db);

$title=$langs->trans("Activities");
if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title);

print load_fiche_titre($title, '', 'title_project');

if ($mine) print $langs->trans("MyTasksDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("TasksDesc").'<br><br>';
	else print $langs->trans("TasksPublicDesc").'<br><br>';
}


print '<div class="fichecenter"><div class="fichethirdleft">';


// Search task
if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/projet/tasks/index.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="mode" value="'.$mine.'">';
	print '<input type="hidden" name="search_status" value="-1">';	// All status
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchATask").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td class="nowrap"><label for="sf_ref">'.$langs->trans("Ref").'</label>:</td><td><input type="text" class="flat" name="search_task_ref" id="sf_ref" size="18"></td>';
	print '<td rowspan="3"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	//print '<tr '.$bc[$var].'><td class="nowrap"><label for="syear">'.$langs->trans("Year").'</label>:</td><td><input type="text" class="flat" name="search_year" id="search_year" size="18"></td>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="sall">'.$langs->trans("Other").'</label>:</td><td><input type="text" class="flat" name="search_task_label" id="search_task_label" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>\n";
}


/* Affichage de la liste des projets d'aujourd'hui */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('ActivityOnProjectToday').'</td>';
print '<td width="50%" align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND date_format(task_date,'%y-%m-%d') = '".strftime("%y-%m-%d",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$var=true;
	$total=0;

	while ($row = $db->fetch_object($resql))
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		$projectstatic->title=$row->title;
		print $projectstatic->getNomUrl(1, '', 1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
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
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table>";

// TODO Do not use date_add function to be compatible with all database
if ($db->type != 'pgsql')
{

	/* Affichage de la liste des projets d'hier */
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('ActivityOnProjectYesterday').'</td>';
	print '<td align="right">'.$langs->trans("Time").'</td>';
	print "</tr>\n";

	$sql = "SELECT p.rowid, p.ref, p.title, sum(tt.task_duration) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
	$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
	$sql.= " WHERE t.fk_projet = p.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	$sql.= " AND tt.fk_task = t.rowid";
	$sql.= " AND tt.fk_user = ".$user->id;
	$sql.= " AND date_format(date_add(task_date, INTERVAL 1 DAY),'%y-%m-%d') = '".strftime("%y-%m-%d",$now)."'";
	$sql.= " AND p.rowid in (".$projectsListId.")";
	$sql.= " GROUP BY p.rowid, p.ref, p.title";

	$resql = $db->query($sql);
	if ( $resql )
	{
		$var=true;
		$total=0;

		while ($row = $db->fetch_object($resql))
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td>';
			$projectstatic->id=$row->rowid;
			$projectstatic->ref=$row->ref;
			$projectstatic->title=$row->title;
			print $projectstatic->getNomUrl(1, '', 1);
			print '</td>';
			print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
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
	print '<td align="right">'.convertSecondToTime($total).'</td>';
	print "</tr>\n";
	print "</table>";
}


// TODO Do not use week function to be compatible with all database
if ($db->type != 'pgsql')
{
print '<br>';

/* Affichage de la liste des projets de la semaine */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisWeek").'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " , ".MAIN_DB_PREFIX."projet_task as t";
$sql.= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND week(task_date) = '".strftime("%W",time())."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$total = 0;
	$var=true;

	while ($row = $db->fetch_object($resql))
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		$projectstatic->title=$row->title;
		print $projectstatic->getNomUrl(1, '', 1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
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
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table><br>";

}

/* Affichage de la liste des projets du mois */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisMonth").': '.dol_print_date($now,"%B %Y").'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND date_format(task_date,'%y-%m') = '".strftime("%y-%m",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$resql = $db->query($sql);
if ( $resql )
{
	$var=false;

	while ($row = $db->fetch_object($resql))
	{
		print "<tr ".$bc[$var].">";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		$projectstatic->title=$row->title;
		print $projectstatic->getNomUrl(1, '', 1);
		print '</td>';
		print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
		print "</tr>\n";
		$var=!$var;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print '<tr class="liste_total">';
print '<td>'.$langs->trans('Total').'</td>';
print '<td align="right">'.convertSecondToTime($total).'</td>';
print "</tr>\n";
print "</table>";

/* Affichage de la liste des projets de l'annee */
if (! empty($conf->global->PROJECT_TASK_TIME_YEAR))
{
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ActivityOnProjectThisYear").': '.strftime("%Y", $now).'</td>';
	print '<td align="right">'.$langs->trans("Time").'</td>';
	print "</tr>\n";

	$sql = "SELECT p.rowid, p.ref, p.title, SUM(tt.task_duration) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
	$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
	$sql.= " WHERE t.fk_projet = p.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	$sql.= " AND tt.fk_task = t.rowid";
	$sql.= " AND tt.fk_user = ".$user->id;
	$sql.= " AND YEAR(task_date) = '".strftime("%Y",$now)."'";
	$sql.= " AND p.rowid in (".$projectsListId.")";
	$sql.= " GROUP BY p.rowid, p.ref, p.title";

	$var=false;
	$resql = $db->query($sql);
	if ( $resql )
	{
		while ($row = $db->fetch_object($resql))
		{
			print "<tr ".$bc[$var].">";
			print '<td>';
			$projectstatic->id=$row->rowid;
			$projectstatic->ref=$row->ref;
			$projectstatic->title=$row->title;
			print $projectstatic->getNomUrl(1, '', 1);
			print '</td>';
			print '<td align="right">'.convertSecondToTime($row->nb).'</td>';
			print "</tr>\n";
			$var=!$var;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	print '<tr class="liste_total">';
	print '<td>'.$langs->trans('Total').'</td>';
	print '<td align="right">'.convertSecondToTime($total).'</td>';
	print "</tr>\n";
	print "</table>";
}



print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


if (empty($conf->global->PROJECT_HIDE_TASKS))
{
	// Tasks for all resources of all opened projects and time spent for each task/resource

	$max = (empty($conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA)?1000:$conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA);

	$sql = "SELECT p.ref, p.title, p.rowid as projectid, p.fk_statut as status, p.fk_opp_status as opp_status, t.label, t.rowid as taskid, t.planned_workload, t.duration_effective, t.progress, t.dateo, t.datee, SUM(tasktime.task_duration) as timespent";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time as tasktime on tasktime.fk_task = t.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on tasktime.fk_user = u.rowid";
	$sql.= " WHERE p.entity = ".$conf->entity;
	if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";
	if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
	$sql.= " AND p.fk_statut=1";
	$sql.= " GROUP BY p.ref, p.title, p.rowid, t.label, t.rowid, t.planned_workload, t.duration_effective, t.progress, t.dateo, t.datee";
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
		print '<th align="right">'.$langs->trans("ProgressDeclared").'</td>';
		print '<th align="right">'.$langs->trans('TimeSpent').'</th>';
		print '<th align="right">'.$langs->trans("ProgressCalculated").'</td>';
		print '</tr>';

		while ($i < $num && $i < $max)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			$username='';
			if ($obj->userid && $userstatic->id != $obj->userid)	// We have a user and it is not last loaded user
			{
				$result=$userstatic->fetch($obj->userid);
				if (! $result) $userstatic->id=0;
			}
			if ($userstatic->id) $username = $userstatic->getNomUrl(0,0);

			print "<tr ".$bc[$var].">";
			//print '<td>'.$username.'</td>';
			print '<td>';
			$projectstatic->id=$obj->projectid;
			$projectstatic->ref=$obj->ref;
			$projectstatic->title=$obj->title;
			print $projectstatic->getNomUrl(1,'',16,'','<br>');
			//print '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$obj->projectid.'">'.$obj->title.'</a>';
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
			print '<td align="center">'.dol_print_date($db->jdate($obj->dateo),'day').'</td>';
			print '<td align="center">'.dol_print_date($db->jdate($obj->datee),'day').'</td>';
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$obj->taskid.'&withproject=1">';
			print convertSecondToTime($obj->planned_workload, 'all');
			print '</a></td>';
			print '<td align="right">';
			print ($obj->taskid>0)?$obj->progress.'%':'';
			print '</td>';
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$obj->taskid.'&withproject=1">';
			print convertSecondToTime($obj->timespent, 'all');
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
