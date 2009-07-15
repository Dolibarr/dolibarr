<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \brief      Page d'accueil du module projet
 *       \version    $Id$
 */

require("./pre.inc.php");
$langs->load("projects");

if (!$user->rights->projet->lire) accessforbidden();

// Security check
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}


/*
 * View
 */

llxHeader("",$langs->trans("Projects"),"EN:Projects|FR:Projet|ES:Proyectos");

$text=$langs->trans("Projects");
if ($_REQUEST["mode"]=='mine') $text=$langs->trans("MyProjects");
print_fiche_titre($text);

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td width="30%" valign="top" class="notopnoleft">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Project"),"index.php","","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOpenTasks"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT p.title, p.rowid, count(t.rowid)";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($_REQUEST["mode"]=='mine') $sql.=' AND p.fk_user_resp='.$user->id;
if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
$sql.= " GROUP BY p.rowid";

$var=true;
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$row = $db->fetch_row( $resql);
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$row[1].'">'.img_object($langs->trans("ShowProject"),"project")." ".$row[0].'</a></td>';
		print '<td align="right">'.$row[2].'</td>';
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

print '</td><td width="70%" valign="top" class="notopnoleft">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfProjects"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT count(p.rowid) as nb, s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
$sql.= " WHERE p.entity = ".$conf->entity;
if ($_REQUEST["mode"]=='mine') $sql.=' AND p.fk_user_resp='.$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " GROUP BY s.nom";
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

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
			print '<a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company")." ".$obj->nom.'</a>';
		}
		else
		{
			print $langs->trans("Public");
		}
		print '</td>';
		print '<td align="right">'.$obj->nb.'</td>';
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
