<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/projet/index.php
 *       \ingroup    projet
 *       \brief      Main project home page
 *       \version    $Id$
 */

require("./pre.inc.php");
$langs->load("projects");

// Security check
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}
if (!$user->rights->projet->lire) accessforbidden();


/*
 * View
 */

$socstatic=new Societe($db);
$projectstatic=new Project($db);

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);

llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$text=$langs->trans("Projects");
if ($mine) $text=$langs->trans("MyProjects");

print_fiche_titre($text);

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td width="30%" valign="top" class="notopnoleft">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Project"),"index.php","","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOpenTasks"),"","","","",'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Status"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut, count(t.rowid) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
$sql.= " GROUP BY p.ref";

$var=true;
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

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
			print '<td nowrap="nowrap">';
			$projectstatic->ref=$objp->ref;
			print $projectstatic->getNomUrl(1);
			print ' - '.$objp->title.'</td>';
			print '<td align="right">'.$objp->nb.'</td>';
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

print '</td><td width="70%" valign="top" class="notopnoleft">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("ThirdParties"),"index.php","s.nom","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfProjects"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT count(p.rowid) as nb";
$sql.= ", s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
if (!$user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid) $sql.= " AND s.rowid = ".$socid;
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
		print "<tr $bc[$var]>";
		print '<td nowrap="nowrap">';
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

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
