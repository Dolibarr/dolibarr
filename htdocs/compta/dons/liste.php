<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/compta/dons/liste.php
 *	\ingroup    don
 *	\brief      Page de liste des dons
 *	\version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/dons/class/don.class.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load("companies");
$langs->load("donations");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$statut=isset($_GET["statut"])?$_GET["statut"]:"-1";

if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="d.datedon"; }


/*
 * View
 */

if ($conf->projet->enabled) $projectstatic=new Project($db);

llxHeader('',$langs->trans("Donations"),'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Subvenciones');

$donationstatic=new Don($db);

// Genere requete de liste des dons
$sql = "SELECT d.rowid, d.datedon, d.prenom, d.nom, d.societe,";
$sql.= " d.amount, d.fk_statut as statut, ";
$sql.= " p.rowid as pid, p.ref, p.title, p.public";
$sql.= " FROM ".MAIN_DB_PREFIX."don as d LEFT JOIN ".MAIN_DB_PREFIX."projet AS p";
$sql.= " ON p.rowid = d.fk_don_projet WHERE 1 = 1";
if ($statut >= 0)
{
	$sql .= " AND d.fk_statut = ".$statut;
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	if ($statut >= 0)
	{
		print_barre_liste($libelle[$statut], $page, "liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
	}
	else
	{
		print_barre_liste($langs->trans("Donation"), $page, "liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
	}
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","d.rowid","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Firstname"),"liste.php","d.prenom","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Name"),"liste.php","d.nom","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"liste.php","d.societe","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),"liste.php","d.datedon","&page=$page&statut=$statut","",'align="center"',$sortfield,$sortorder);
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print_liste_field_titre($langs->trans("Project"),"liste.php","projet","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Amount"),"liste.php","d.amount","&page=$page&statut=$statut","",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php","d.statut","&page=$page&statut=$statut","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$var=True;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		$donationstatic->id=$objp->rowid;
		$donationstatic->ref=$objp->rowid;
		print "<td>".$donationstatic->getNomUrl(1)."</td>\n";
		print "<td>".$objp->prenom."</td>\n";
		print "<td>".$objp->nom."</td>\n";
		print "<td>".$objp->societe."</td>\n";
		print '<td align="center">'.dol_print_date($db->jdate($objp->datedon)).'</td>';
		if ($conf->projet->enabled)
		{
			print "<td>";
			if ($objp->pid)
			{
				$projectstatic->id=$objp->pid;
				$projectstatic->ref=$objp->ref;
				$projectstatic->id=$objp->pid;
				$projectstatic->public=$objp->public;
				$projectstatic->title=$objp->title;
				print $projectstatic->getNomUrl(1);
			}
			else print '&nbsp;';
			print "</td>\n";
		}
		print '<td align="right">'.price($objp->amount).'</td>';
		print '<td align="right">'.$donationstatic->LibStatut($objp->statut,5).'</td>';

		print "</tr>";
		$i++;
	}
	print "</table>";
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
