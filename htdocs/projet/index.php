<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
* Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/projet/index.php
 *       \ingroup    projet
 *       \brief      Main project home page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


$langs->load("projects");
$langs->load("companies");

$mine = GETPOST('mode')=='mine' ? 1 : 0;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');


/*
 * View
*/

$socstatic=new Societe($db);
$projectstatic=new Project($db);

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:(empty($user->rights->projet->all->lire)?0:2)),1);
//var_dump($projectsListId);


llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$text=$langs->trans("Projects");
if ($mine) $text=$langs->trans("MyProjects");

print_fiche_titre($text);

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if (! empty($user->rights->projet->all->lire) && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}



print '<div class="fichecenter"><div class="fichethirdleft">';


print_projecttasks_array($db,$socid,$projectsListId);


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("ThirdParties"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfProjects"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT count(p.rowid) as nb";
$sql.= ", s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql.= " GROUP BY s.nom, s.rowid";

$var=true;
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td class="nowrap">';
		if ($obj->socid)
		{
			$socstatic->id=$obj->socid;
			$socstatic->nom=$obj->nom;
			print $socstatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("OthersNotLinkedToThirdParty");
		}
		print '</td>';
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$obj->socid.'">'.$obj->nb.'</a></td>';
		print "</tr>\n";

		$i++;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print "</table>";


print '</div></div></div>';


// Tasks for all resources of all opened projects and time spent for each task/resource
print '<div class="fichecenter">';

$sql = "SELECT p.title, p.rowid as projectid, t.label, t.rowid as taskid, u.rowid as userid, t.planned_workload, t.dateo, t.datee, SUM(tasktime.task_duration) as timespent";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."projet_task_time as tasktime on tasktime.fk_task = t.rowid";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user as u on tasktime.fk_user = u.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql.= " AND p.fk_statut=1";
$sql.= " GROUP BY p.title, p.rowid, t.label, t.rowid, u.rowid, t.planned_workload, t.dateo, t.datee";
$sql.= " ORDER BY u.rowid, t.dateo, t.datee";

$userstatic=new User($db);

dol_syslog('projet:index.php: affectationpercent sql='.$sql,LOG_DEBUG);
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	if ($num > (empty($conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA)?1000:$conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA))
	{
/*		$langs->load("errors");
  		print '<tr '.$bc[0].'>';
		print '<td colspan="9">';
		print $langs->trans("WarningTooManyDataPleaseUseMoreFilters");
		print '</td></tr>';*/
	}
	else
	{
		print '<br>';

		print_fiche_titre($langs->trans("TimeSpent"),'','').'<br>';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans('TaskRessourceLinks').'</th>';
		print '<th>'.$langs->trans('Projects').'</th>';
		print '<th>'.$langs->trans('Task').'</th>';
		print '<th>'.$langs->trans('DateStart').'</th>';
		print '<th>'.$langs->trans('DateEnd').'</th>';
		print '<th>'.$langs->trans('TimeSpent').'</th>';
		print '</tr>';

		while ($i < $num)
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
			print '<td>'.$username.'</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$obj->projectid.'">'.$obj->title.'</a></td>';
			print '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$obj->taskid.'&withproject=1">'.$obj->label.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($obj->dateo)).'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datee)).'</td>';
			/* I disable this because information is wrong. This percent has no meaning for a particular resource. What do we want ?
			 * Percent of completion ?
			 * If we want to show completion, we must remove "user" into list,
			if (empty($obj->planned_workload)) {
				$percentcompletion = $langs->trans("Unknown");
			} else {
				$percentcompletion = intval($obj->task_duration*100/$obj->planned_workload);
			}*/
			print '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$obj->taskid.'&withproject=1">';
			//print $percentcompletion.' %';
			print convertSecondToTime($obj->timespent, 'all');
			print '</a></td>';
			print "</tr>\n";

			$i++;
		}

		print "</table>";
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print '</div>';



llxFooter();

$db->close();
