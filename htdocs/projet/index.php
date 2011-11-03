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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/projet/index.php
 *       \ingroup    projet
 *       \brief      Main project home page
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");


$langs->load("projects");
$langs->load("companies");

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

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

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:($user->rights->projet->all->lire?2:0)),1);
//var_dump($projectsListId);


llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$text=$langs->trans("Projects");
if ($mine) $text=$langs->trans("MyProjects");

print_fiche_titre($text);

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td width="30%" valign="top" class="notopnoleft">';

print_projecttasks_array($db,$socid,$projectsListId);

print '</td><td width="70%" valign="top" class="notopnoleftnoright">';

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
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
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

llxFooter();
?>
