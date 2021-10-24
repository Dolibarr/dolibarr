<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/projet/activity/index.php
 *	\ingroup    projet
 *	\brief      Page activite perso du module projet
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

// Security check
if (!$user->rights->projet->lire) accessforbidden();
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}

$langs->load("projects");


/*
 * View
 */

$now = gmmktime();

$projectstatic=new Project($db);

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);

$title=$langs->trans("Activities");
if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title);

print_fiche_titre($title);

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td width="30%" valign="top" class="notopnoleft">';


print_projecttasks_array($db,$mine,$socid,$projectsListId);


/* Affichage de la liste des projets d'aujourd'hui */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="50%">'.$langs->trans('Today').'</td>';
print '<td width="50%" align="right">'.$langs->trans("Time").'</td>';
print "</tr>\n";

$sql = "SELECT p.rowid, p.ref, p.title, sum(tt.task_duration) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
$sql.= ", ".MAIN_DB_PREFIX."projet_task_time as tt";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND tt.fk_task = t.rowid";
$sql.= " AND tt.fk_user = ".$user->id;
$sql.= " AND date_format(task_date,'%d%m%y') = ".strftime("%d%m%y",time());
$sql.= " GROUP BY p.rowid";

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
		print '<td align="right">'.$row->nb.'</td>';
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
print '<td align="right">'.$total.'</td>';
print "</tr>\n";
print "</table>";

/* Affichage de la liste des projets d'hier */
print '<br /><table class="noborder" width="100%">';
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
$sql.= " AND date_format(date_add(task_date, INTERVAL 1 DAY),'%d%m%y') = ".strftime("%d%m%y",time());
$sql.= " GROUP BY p.rowid";

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
		print '<td align="right">'.$row->nb.'</td>';
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
print '<td align="right">'.$total.'</td>';
print "</tr>\n";
print "</table>";

print '</td><td width="70%" valign="top" class="notopnoleft">';

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
$sql.= " AND week(task_date) = ".strftime("%W",time());
$sql.= " GROUP BY p.rowid";

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
		print '<td align="right">'.$row->nb.'</td>';
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
print '<td align="right">'.$total.'</td>';
print "</tr>\n";
print "</table><br />";

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
$sql.= " AND month(task_date) = ".strftime("%m",$now);
$sql.= " GROUP BY p.rowid";

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
		print '<td align="right">'.$row->nb.'</td>';
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
print '<br /><table class="noborder" width="100%">';
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
$sql.= " AND YEAR(task_date) = ".strftime("%Y",$now);
$sql.= " GROUP BY p.rowid";

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
		print '<td align="right">'.$row->nb.'</td>';
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

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
