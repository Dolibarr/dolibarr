<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/projet/index.php
 *       \ingroup    projet
 *       \brief      Main project home page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('projectsindex'));

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies'));

$search_project_user = GETPOST('search_project_user', 'int');
$mine = GETPOST('mode', 'aZ09')=='mine' ? 1 : 0;
if ($search_project_user == $user->id) $mine = 1;

// Security check
$socid=0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

$max=3;


/*
 * View
 */

$companystatic=new Societe($db);
$projectstatic=new Project($db);
$form=new Form($db);
$formfile=new FormFile($db);

$projectset = ($mine?$mine:(empty($user->rights->projet->all->lire)?0:2));
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, $projectset, 1);
//var_dump($projectsListId);

llxHeader("", $langs->trans("Projects"), "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$title=$langs->trans("ProjectsArea");
//if ($mine) $title=$langs->trans("MyProjectsArea");


// Title for combo list see all projects
$titleall=$langs->trans("AllAllowedProjects");
if (! empty($user->rights->projet->all->lire) && ! $socid) $titleall=$langs->trans("AllProjects");
else $titleall=$langs->trans("AllAllowedProjects").'<br><br>';

$morehtml='';
$morehtml.='<form name="projectform" method="POST">';
$morehtml.='<input type="hidden" name="token" value="'.newToken().'">';
$morehtml.='<SELECT name="search_project_user">';
$morehtml.='<option name="all" value="0"'.($mine?'':' selected').'>'.$titleall.'</option>';
$morehtml.='<option name="mine" value="'.$user->id.'"'.(($search_project_user == $user->id)?' selected':'').'>'.$langs->trans("ProjectsImContactFor").'</option>';
$morehtml.='</SELECT>';
$morehtml.='<input type="submit" class="button" name="refresh" value="'.$langs->trans("Refresh").'">';
$morehtml.='</form>';

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, -1, 'project', 0, $morehtml);

// Show description of content
print '<div class="opacitymedium">';
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if (!empty($user->rights->projet->all->lire) && !$socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}
print '</div>';

// Get list of ponderated percent for each status
$listofoppstatus = array(); $listofopplabel = array(); $listofoppcode = array();
$sql = "SELECT cls.rowid, cls.code, cls.percent, cls.label";
$sql .= " FROM ".MAIN_DB_PREFIX."c_lead_status as cls";
$sql .= " WHERE active=1";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		$listofoppstatus[$objp->rowid] = $objp->percent;
		$listofopplabel[$objp->rowid] = $objp->label;
		$listofoppcode[$objp->rowid] = $objp->code;
		$i++;
	}
}
else dol_print_error($db);



print '<div class="fichecenter"><div class="fichethirdleft">';


if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search project
    if (!empty($conf->projet->enabled) && $user->rights->projet->lire)
    {
    	$listofsearchfields['search_project'] = array('text'=>'Project');
    }

    if (count($listofsearchfields))
    {
    	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
    	print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<div class="div-table-responsive-no-min">';
    	print '<table class="noborder nohover centpercent">';
    	$i = 0;
    	foreach ($listofsearchfields as $key => $value)
    	{
    		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    		print '<tr>';
    		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
    		if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    		print '</tr>';
    		$i++;
    	}
    	print '</table>';
        print '</div>';
    	print '</form>';
    	print '<br>';
    }
}


/*
 * Statistics
 */
include DOL_DOCUMENT_ROOT.'/projet/graph_opportunities.inc.php';


// List of draft projects
print_projecttasks_array($db, $form, $socid, $projectsListId, 0, 0, $listofoppstatus, array('projectlabel', 'plannedworkload', 'declaredprogress'));


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

// Latest modified projects
$sql = "SELECT p.rowid, p.ref, p.title, p.fk_statut, p.tms as datem,";
$sql .= " s.rowid as socid, s.nom as name, s.email, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.canvas";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= " WHERE p.entity IN (".getEntity('project').")";
if ($mine || empty($user->rights->projet->all->lire)) $sql .= " AND p.rowid IN (".$projectsListId.")"; // If we have this test true, it also means projectset is not 2
if ($socid)	$sql .= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql .= " ORDER BY p.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql)
{
    print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LatestModifiedProjects", $max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		$var = true;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="20%" class="nowrap">';

			$projectstatic->id = $obj->rowid;
			$projectstatic->ref = $obj->ref;
			$projectstatic->title = $obj->title;
			$projectstatic->dateo = $obj->dateo;
			$projectstatic->datep = $obj->datep;
			$projectstatic->thirdparty_name = $obj->name;

			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->name;
			$companystatic->email = $obj->email;
			$companystatic->client = $obj->client;
			$companystatic->fournisseur = $obj->fournisseur;
			$companystatic->code_client = $obj->code_client;
			$companystatic->code_fournisseur = $obj->code_fournisseur;
			$companystatic->canvas = $obj->canvas;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">';
			print $projectstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($projectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			print '<td class="nowrap">';
			if ($companystatic->id > 0)
			{
				print $companystatic->getNomUrl(1, 'company', 16);
			}
			print '</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem), 'day').'</td>';
			print '<td class="right">'.$projectstatic->LibStatut($obj->fk_statut, 3).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table></div><br>";
}
else dol_print_error($db);


$companystatic = new Societe($db); // We need a clean new object for next loop because current one has some properties set.


// Open project per thirdparty
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print_liste_field_titre("OpenedProjectsByThirdparties", $_SERVER["PHP_SELF"], "s.nom", "", "", '', $sortfield, $sortorder);
print_liste_field_titre("NbOfProjects", "", "", "", "", '', $sortfield, $sortorder, 'right ');
print "</tr>\n";

$sql = "SELECT COUNT(p.rowid) as nb, SUM(p.opp_amount)";
$sql .= ", s.nom as name, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= " WHERE p.entity IN (".getEntity('project').")";
$sql .= " AND p.fk_statut = 1";
if ($mine || empty($user->rights->projet->all->lire)) $sql .= " AND p.rowid IN (".$projectsListId.")"; // If we have this test true, it also means projectset is not 2
if ($socid)	$sql .= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql .= " GROUP BY s.nom, s.rowid";
$sql .= $db->order($sortfield, $sortorder);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';
		print '<td class="nowrap">';
		if ($obj->socid)
		{
			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->name;
			print $companystatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("OthersNotLinkedToThirdParty");
		}
		print '</td>';
		print '<td class="right">';
		if ($obj->socid) print '<a href="'.DOL_URL_ROOT.'/projet/list.php?socid='.$obj->socid.'&search_status=1">'.$obj->nb.'</a>';
		else print '<a href="'.DOL_URL_ROOT.'/projet/list.php?search_societe='.urlencode('^$').'&search_status=1">'.$obj->nb.'</a>';
		print '</td>';
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
print '</div>';

if (!empty($conf->global->PROJECT_SHOW_PROJECT_LIST_ON_PROJECT_AREA))
{
    // This list can be very long, so we don't show it by default on task area. We prefer to use the list page.
    // Add constant PROJECT_SHOW_PROJECT_LIST_ON_PROJECT_AREA to show this list

    print '<br>';

    print_projecttasks_array($db, $form, $socid, $projectsListId, 0, 1, $listofoppstatus, array());
}

print '</div></div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProjects', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
