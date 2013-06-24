<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
//$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:($user->rights->projet->all->lire?2:0)),1);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);  // Return all projects I have permission on because I want my tasks and some of my task may be on a public projet that is not my project

$title=$langs->trans("Activities");
if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title);

print_fiche_titre($title);

if ($mine) print $langs->trans("MyTasksDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("TasksDesc").'<br><br>';
	else print $langs->trans("TasksPublicDesc").'<br><br>';
}


print '<div class="fichecenter"><div class="fichethirdleft">';


print_projecttasks_array($db,$socid,$projectsListId,$mine);


/* Affichage de la liste des projets d'aujourd'hui */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('Today').'</td>';
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
		print "<tr $bc[$var]>";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
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
print '<td>'.$langs->trans('Yesterday').'</td>';
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
		print "<tr $bc[$var]>";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
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


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// TODO Do not use week function to be compatible with all database
if ($db->type != 'pgsql')
{

/* Affichage de la liste des projets de la semaine */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisWeek").'</td>';
print '<td align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, sum(tt.task_duration) as nb";
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
		print $projectstatic->getNomUrl(1);
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

$sql = "SELECT p.rowid, p.ref, p.title, sum(tt.task_duration) as nb";
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
		print "<tr $bc[$var]>";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
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
print "</table>";

/* Affichage de la liste des projets de l'annee */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ActivityOnProjectThisYear").': '.strftime("%Y", $now).'</td>';
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
$sql.= " AND YEAR(task_date) = '".strftime("%Y",$now)."'";
$sql.= " AND p.rowid in (".$projectsListId.")";
$sql.= " GROUP BY p.rowid, p.ref, p.title";

$var=false;
$resql = $db->query($sql);
if ( $resql )
{
	while ($row = $db->fetch_object($resql))
	{
		print "<tr $bc[$var]>";
		print '<td>';
		$projectstatic->id=$row->rowid;
		$projectstatic->ref=$row->ref;
		print $projectstatic->getNomUrl(1);
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
print "</table>";


print '</div></div></div>';


llxFooter();

$db->close();
?>
