<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/projet/liste.php
 *	\ingroup    projet
 *	\brief      Page to list projects
 *	\version    $Id$
 */

require("./pre.inc.php");

if (!$user->rights->projet->lire) accessforbidden();

$socid = ( is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );

$title = $langs->trans("Projects");

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;

if ($socid > 0)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="liste.php">'.$soc->nom.'</a>)';
}


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



/*
 * View
 */

llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$projectstatic = new Project($db);
$socstatic = new Societe($db);

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);

$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_statut, p.public, p.fk_user_creat";
$sql.= ", p.datec as date_create, p.dateo as date_start, p.datee as date_end";
$sql.= ", s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= " AND s.rowid = ".$socid;

if ($_GET["search_ref"])
{
	$sql.= " AND p.ref LIKE '%".addslashes($_GET["search_ref"])."%'";
}
if ($_GET["search_label"])
{
	$sql.= " AND p.title LIKE '%".addslashes($_GET["search_label"])."%'";
}
if ($_GET["search_societe"])
{
	$sql.= " AND s.nom LIKE '%".addslashes($_GET["search_societe"])."%'";
}
$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$var=true;
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$text=$langs->trans("Projects");
	if ($mine) $text=$langs->trans('MyProjects');
	print_barre_liste($text, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"p.title","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Visibility"),$_SERVER["PHP_SELF"],"p.public","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],'p.fk_statut',"","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<form method="get" action="liste.php">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"valign="right">';
	print '<input type="text" class="flat" name="search_ref" value="'.$_GET["search_ref"].'" size="6">';
	print '</td>';
	print '<td class="liste_titre"valign="right">';
	print '<input type="text" class="flat" name="search_label" value="'.$_GET["search_label"].'">';
	print '</td>';
	print '<td class="liste_titre"valign="right">';
	print '<input type="text" class="flat" name="search_societe" value="'.$_GET["search_societe"].'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'"></td>';
	print "</tr>\n";

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);

		$projectstatic->id = $objp->projectid;
		$projectstatic->user_author_id = $objp->fk_user_creat;
		$projectstatic->public = $objp->public;

		$userAccess = $projectstatic->restrictedProjectArea($user,1);

		if ($userAccess >= 0)
		{
			$var=!$var;
			print "<tr $bc[$var]>";

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

$db->close();


llxFooter('$Date$ - $Revision$');
?>
