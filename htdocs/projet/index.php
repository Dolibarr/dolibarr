<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


$langs->load("projects");
$langs->load("companies");

$mine = GETPOST('mode')=='mine' ? 1 : 0;

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');


/*
 * View
 */

$socstatic=new Societe($db);
$projectstatic=new Project($db);
$userstatic=new User($db);
$form=new Form($db);

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:(empty($user->rights->projet->all->lire)?0:2)),1);
//var_dump($projectsListId);


llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$text=$langs->trans("ProjectsArea");
if ($mine) $text=$langs->trans("MyProjectsArea");

print load_fiche_titre($text,'','title_project.png');

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if (! empty($user->rights->projet->all->lire) && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}


// Get list of ponderated percent for each status
$listofoppstatus=array(); $listofopplabel=array(); $listofoppcode=array();
$sql = "SELECT cls.rowid, cls.code, cls.percent, cls.label";
$sql.= " FROM ".MAIN_DB_PREFIX."c_lead_status as cls";
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		$listofoppstatus[$objp->rowid]=$objp->percent;
		$listofopplabel[$objp->rowid]=$objp->label;
		$listofoppcode[$objp->rowid]=$objp->code;
		$i++;
	}
}
else dol_print_error($db);



print '<div class="fichecenter"><div class="fichethirdleft">';

// Search project
if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
{
	$listofsearchfields['search_project']=array('text'=>'Project');
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


/*
 * Statistics
 */
include DOL_DOCUMENT_ROOT.'/projet/graph_opportunities.inc.php';


// List of draft projects
print_projecttasks_array($db, $form, $socid, $projectsListId, 0, 0, $listofoppstatus, array('projectlabel', 'plannedworkload', 'declaredprogress'));


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("OpenedProjectsByThirdparties"),$_SERVER["PHP_SELF"],"s.nom","","",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfProjects"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT COUNT(p.rowid) as nb, SUM(p.opp_amount)";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
$sql.= " AND p.fk_statut = 1";
if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql.= " GROUP BY s.nom, s.rowid";
$sql.= $db->order($sortfield, $sortorder);

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
			$socstatic->name=$obj->name;
			print $socstatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("OthersNotLinkedToThirdParty");
		}
		print '</td>';
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/list.php?socid='.$obj->socid.'&search_status=1">'.$obj->nb.'</a></td>';
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

if (! empty($conf->global->PROJECT_SHOW_PROJECT_LIST_ON_PROJECT_AREA))
{
    // This list can be very long, so we don't show it by default on task area. We prefer to use the list page.
    // Add constant PROJECT_SHOW_PROJECT_LIST_ON_PROJECT_AREA to show this list
    
    print '<br>';
    
    print_projecttasks_array($db, $form, $socid, $projectsListId, 0, 1, $listofoppstatus, array());
}

print '</div></div></div>';


llxFooter();

$db->close();
