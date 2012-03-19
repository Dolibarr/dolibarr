<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/liste.php
 *	\ingroup    projet
 *	\brief      Page to list projects
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$title = $langs->trans("Projects");

// Security check
$socid = (is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );
if ($user->societe_id > 0) $socid=$user->societe_id;
if ($socid > 0)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="liste.php">'.$soc->nom.'</a>)';
}
if (!$user->rights->projet->lire) accessforbidden();


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])? $_GET["page"]:$_POST["page"];
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

$search_ref=GETPOST("search_ref");
$search_label=GETPOST("search_label");
$search_societe=GETPOST("search_societe");


/*
 * View
 */

$projectstatic = new Project($db);
$socstatic = new Societe($db);

llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");


$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:($user->rights->projet->all->lire?2:0)),1,$socid);

$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_statut, p.public, p.fk_user_creat";
$sql.= ", p.datec as date_create, p.dateo as date_start, p.datee as date_end";
$sql.= ", s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($_GET["search_ref"])
{
	$sql.= " AND p.ref LIKE '%".$db->escape($search_ref)."%'";
}
if ($_GET["search_label"])
{
	$sql.= " AND p.title LIKE '%".$db->escape($search_label)."%'";
}
if ($_GET["search_societe"])
{
	$sql.= " AND s.nom LIKE '%".$db->escape($search_societe)."%'";
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$var=true;
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$text=$langs->trans("Projects");
	if ($mine) $text=$langs->trans('MyProjects');
	print_barre_liste($text, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

	// Show description of content
	if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
	else
	{
		if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
		else print $langs->trans("ProjectsPublicDesc").'<br><br>';
	}

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"p.title","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Visibility"),$_SERVER["PHP_SELF"],"p.public","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],'p.fk_statut',"","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" value="'.$search_label.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe" value="'.$search_societe.'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);

		$projectstatic->id = $objp->projectid;
		$projectstatic->user_author_id = $objp->fk_user_creat;
		$projectstatic->public = $objp->public;

		$userAccess = $projectstatic->restrictedProjectArea($user);

		if ($userAccess >= 0)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Project url
			print "<td>";
			$projectstatic->ref = $objp->ref;
			print $projectstatic->getNomUrl(1);
			print "</td>";

			// Title
			print '<td>';
			print dol_trunc($objp->title,24);
			print '</td>';

			// Company
			print '<td>';
			if ($objp->socid)
			{
				$socstatic->id=$objp->socid;
				$socstatic->nom=$objp->nom;
				print $socstatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			// Visibility
			print '<td align="left">';
			if ($objp->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td>';

			// Status
			$projectstatic->statut = $objp->fk_statut;
			print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';

			print "</tr>\n";

		}

		$i++;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print "</table>";


llxFooter();

$db->close();
?>
