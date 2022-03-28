<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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

$action = GETPOST('action', 'aZ09');
$search_project_user = GETPOST('search_project_user', 'int');
$mine = GETPOST('mode', 'aZ09') == 'mine' ? 1 : 0;
if ($mine == 0 && $search_project_user === '') {
	$search_project_user = (empty($user->conf->MAIN_SEARCH_PROJECT_USER_PROJECTSINDEX) ? '' : $user->conf->MAIN_SEARCH_PROJECT_USER_PROJECTSINDEX);
}
if ($search_project_user == $user->id) {
	$mine = 1;
}

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) {
	accessforbidden();
}

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	if ($action == 'refresh_search_project_user') {
		$search_project_user = GETPOST('search_project_user', 'int');
		$tabparam = array("MAIN_SEARCH_PROJECT_USER_PROJECTSINDEX" => $search_project_user);

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$result = dol_set_user_param($db, $conf, $user, $tabparam);
	}
}


/*
 * View
 */

$companystatic = new Societe($db);
$projectstatic = new Project($db);
$form = new Form($db);
$formfile = new FormFile($db);

$projectset = ($mine ? $mine : (empty($user->rights->projet->all->lire) ? 0 : 2));
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, $projectset, 1);
//var_dump($projectsListId);


$title = $langs->trans('ProjectsArea');

$help_url = 'EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos|DE:Modul_Projekte';

llxHeader('', $title, $help_url);


//if ($mine) $title=$langs->trans("MyProjectsArea");


// Title for combo list see all projects
$titleall = $langs->trans("AllAllowedProjects");
if (!empty($user->rights->projet->all->lire) && !$socid) {
	$titleall = $langs->trans("AllProjects");
} else {
	$titleall = $langs->trans("AllAllowedProjects").'<br><br>';
}

$morehtml = '';
$morehtml .= '<form name="projectform" method="POST">';
$morehtml .= '<input type="hidden" name="token" value="'.newToken().'">';
$morehtml .= '<input type="hidden" name="action" value="refresh_search_project_user">';
$morehtml .= '<SELECT name="search_project_user">';
$morehtml .= '<option name="all" value="0"'.($mine ? '' : ' selected').'>'.$titleall.'</option>';
$morehtml .= '<option name="mine" value="'.$user->id.'"'.(($search_project_user == $user->id) ? ' selected' : '').'>'.$langs->trans("ProjectsImContactFor").'</option>';
$morehtml .= '</SELECT>';
$morehtml .= '<input type="submit" class="button smallpaddingimp" name="refresh" value="'.$langs->trans("Refresh").'">';
$morehtml .= '</form>';

if ($mine) {
	$tooltiphelp = $langs->trans("MyProjectsDesc");
} else {
	if (!empty($user->rights->projet->all->lire) && !$socid) {
		$tooltiphelp = $langs->trans("ProjectsDesc");
	} else {
		$tooltiphelp = $langs->trans("ProjectsPublicDesc");
	}
}

print_barre_liste($form->textwithpicto($title, $tooltiphelp), 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, -1, 'project', 0, $morehtml);


// Get list of ponderated percent and colors for each status
include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
$listofoppstatus = array(); $listofopplabel = array(); $listofoppcode = array(); $colorseries = array();
$sql = "SELECT cls.rowid, cls.code, cls.percent, cls.label";
$sql .= " FROM ".MAIN_DB_PREFIX."c_lead_status as cls";
$sql .= " WHERE active=1";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num) {
		$objp = $db->fetch_object($resql);
		$listofoppstatus[$objp->rowid] = $objp->percent;
		$listofopplabel[$objp->rowid] = $objp->label;		// default label if translation from "OppStatus".code not found.
		$listofoppcode[$objp->rowid] = $objp->code;
		switch ($objp->code) {
			case 'PROSP':
				$colorseries[$objp->rowid] = "-".$badgeStatus0;
				break;
			case 'QUAL':
				$colorseries[$objp->rowid] = "-".$badgeStatus1;
				break;
			case 'PROPO':
				$colorseries[$objp->rowid] = $badgeStatus1;
				break;
			case 'NEGO':
				$colorseries[$objp->rowid] = $badgeStatus4;
				break;
			case 'LOST':
				$colorseries[$objp->rowid] = $badgeStatus9;
				break;
			case 'WON':
				$colorseries[$objp->rowid] = $badgeStatus6;
				break;
			default:
				$colorseries[$objp->rowid] = $badgeStatus2;
				break;
		}
		$i++;
	}
} else {
	dol_print_error($db);
}
//var_dump($listofoppcode);


print '<div class="fichecenter"><div class="fichethirdleft">';

/*
 * Statistics
 */
include DOL_DOCUMENT_ROOT.'/projet/graph_opportunities.inc.php';


// List of draft projects
print_projecttasks_array($db, $form, $socid, $projectsListId, 0, 0, $listofoppstatus, array('projectlabel', 'plannedworkload', 'declaredprogress', 'prospectionstatus', 'projectstatus'));


print '</div><div class="fichetwothirdright">';

// Latest modified projects
$sql = "SELECT p.rowid, p.ref, p.title, p.dateo, p.datee, p.fk_statut as status, p.tms as datem";
$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
$sql .= ", s.code_client, s.code_compta, s.client";
$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
$sql .= ", s.logo, s.email, s.entity";
$sql .= ", s.canvas, s.status as thirdpartystatus";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= " WHERE p.entity IN (".getEntity('project').")";
if ($mine || empty($user->rights->projet->all->lire)) {
	$sql .= " AND p.rowid IN (".$db->sanitize($projectsListId).")"; // If we have this test true, it also means projectset is not 2
}
if ($socid) {
	$sql .= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
}
$sql .= " ORDER BY p.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("LatestModifiedProjects", $max).'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap">';

			$projectstatic->id = $obj->rowid;
			$projectstatic->ref = $obj->ref;
			$projectstatic->title = $obj->title;
			$projectstatic->thirdparty_name = $obj->name;
			$projectstatic->status = $obj->status;

			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->name;
			//$companystatic->name_alias = $obj->name_alias;
			//$companystatic->code_client = $obj->code_client;
			$companystatic->code_compta = $obj->code_compta;
			$companystatic->client = $obj->client;
			//$companystatic->code_fournisseur = $obj->code_fournisseur;
			$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
			$companystatic->fournisseur = $obj->fournisseur;
			$companystatic->logo = $obj->logo;
			$companystatic->email = $obj->email;
			$companystatic->entity = $obj->entity;
			$companystatic->canvas = $obj->canvas;
			$companystatic->status = $obj->thirdpartystatus;

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

			// Label
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->title).'">';
			print $projectstatic->title;
			print '</td>';

			// Thirdparty
			print '<td class="nowrap">';
			if ($companystatic->id > 0) {
				print $companystatic->getNomUrl(1, 'company', 16);
			}
			print '</td>';

			// Date
			print '<td>'.dol_print_date($db->jdate($obj->datem), 'day').'</td>';

			// Status
			print '<td class="right">'.$projectstatic->LibStatut($obj->status, 3).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table></div>";
} else {
	dol_print_error($db);
}


$companystatic = new Societe($db); // We need a clean new object for next loop because current one has some properties set.


$sql = "SELECT COUNT(p.rowid) as nb, SUM(p.opp_amount)";
$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
$sql .= ", s.code_client, s.code_compta, s.client";
$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
$sql .= ", s.logo, s.email, s.entity";
$sql .= ", s.canvas, s.status";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= " WHERE p.entity IN (".getEntity('project').")";
$sql .= " AND p.fk_statut = 1";
if ($mine || empty($user->rights->projet->all->lire)) {
	$sql .= " AND p.rowid IN (".$db->sanitize($projectsListId).")"; // If we have this test true, it also means projectset is not 2
}
if ($socid > 0) {
	$sql .= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
}
$sql .= " GROUP BY s.rowid, s.nom, s.name_alias, s.code_client, s.code_compta, s.client, s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur, s.logo, s.email, s.entity, s.canvas, s.status";
$sql .= $db->order($sortfield, $sortorder);
//$sql .= $db->plimit($max + 1, 0);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	$othernb = 0;

	if ($num) {
		print '<br>';

		// Open project per thirdparty
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("OpenedProjectsByThirdparties", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder);
		print_liste_field_titre("NbOfProjects", $_SERVER["PHP_SELF"], "nb", "", "", '', $sortfield, $sortorder, 'right ');
		print "</tr>\n";
	}

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		if ($i >= $max) {
			$othernb += $obj->nb;
			$i++;
			continue;
		}

		print '<tr class="oddeven">';
		print '<td class="nowraponall tdoverflowmax100">';
		if ($obj->socid > 0) {
			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->name;
			$companystatic->name_alias = $obj->name_alias;
			$companystatic->code_client = $obj->code_client;
			$companystatic->code_compta = $obj->code_compta;
			$companystatic->client = $obj->client;
			$companystatic->code_fournisseur = $obj->code_fournisseur;
			$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
			$companystatic->fournisseur = $obj->fournisseur;
			$companystatic->logo = $obj->logo;
			$companystatic->email = $obj->email;
			$companystatic->entity = $obj->entity;
			$companystatic->canvas = $obj->canvas;
			$companystatic->status = $obj->status;

			print $companystatic->getNomUrl(1);
		} else {
			print $langs->trans("OthersNotLinkedToThirdParty");
		}
		print '</td>';
		print '<td class="right">';
		if ($obj->socid) {
			print '<a href="'.DOL_URL_ROOT.'/projet/list.php?socid='.$obj->socid.'&search_status=1">'.$obj->nb.'</a>';
		} else {
			print '<a href="'.DOL_URL_ROOT.'/projet/list.php?search_societe='.urlencode('^$').'&search_status=1">'.$obj->nb.'</a>';
		}
		print '</td>';
		print "</tr>\n";

		$i++;
	}
	if ($othernb) {
		print '<tr class="oddeven">';
		print '<td class="nowrap">';
		print '<span class="opacitymedium">...</span>';
		print '</td>';
		print '<td class="nowrap right">';
		print $othernb;
		print '</td>';
		print "</tr>\n";
	}

	if ($num) {
		print "</table>";
		print '</div>';
	}

	$db->free($resql);
} else {
	dol_print_error($db);
}

if (empty($conf->global->PROJECT_HIDE_PROJECT_LIST_ON_PROJECT_AREA)) {
	// This list can be very long, so we allow to hide it to prefer to use the list page.
	// Add constant PROJECT_HIDE_PROJECT_LIST_ON_PROJECT_AREA to hide this list

	print '<br>';

	print_projecttasks_array($db, $form, $socid, $projectsListId, 0, 1, $listofoppstatus, array());
}

print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProjects', $parameters, $projectstatic); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
