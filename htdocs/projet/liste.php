<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005 Marc Bariley / Ocebo      <marc@ocebo.com>
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
 *
 * $Id$
 * $Source$
 *
 */

/*! 
  \file       htdocs/projet/index.php
  \ingroup    projet
  \brief      Page d'accueil du module projet
  \version    $Revision$
*/

require("./pre.inc.php");

$socid = ( is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );

$title = $langs->trans("Projects");

// Sécurité accés client
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

if ($sortfield == "")
{
	$sortfield="p.ref";
}
if ($sortorder == "")
{
	$sortorder="ASC";
}

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 *
 * Affichage de la liste des projets
 * 
 */
$sql = "SELECT p.rowid as projectid, p.ref, p.title, ".$db->pdate("p.dateo")." as do";
$sql .= " , s.nom, s.idp, s.client";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.fk_soc = s.idp";

if ($socid)
{ 
  $sql .= " AND s.idp = $socid"; 
}

if ($_GET["search_ref"])
{
  $sql .= " AND p.ref LIKE '%".$_GET["search_ref"]."%'";
}
if ($_GET["search_label"])
{
  $sql .= " AND p.title LIKE '%".$_GET["search_label"]."%'";
}
if ($_GET["search_societe"])
{
  $sql .= " AND s.nom LIKE '%".$_GET["search_societe"]."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$var=true;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows();
	$i = 0;

	//llxHeader("",$title,"Projet");
	llxHeader();

	print_barre_liste($langs->trans("Projects"), $page, "liste.php", "", $sortfield, $sortorder, "", $num);

	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","p.ref","","","",$sortfield);
	print_liste_field_titre($langs->trans("Label"),"liste.php","p.title","","","",$sortfield);
	print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom","","","",$sortfield);
	print '<td>&nbsp;</td>';
	print "</tr>\n";

	print '<form method="get" action="liste.php">';
	print '<tr class="liste_titre">';
	print '<td valign="right">';
	print '<input type="text" class="flat" name="search_ref" value="'.$_GET["search_ref"].'">';
	print '</td>';
	print '<td valign="right">';
	print '<input type="text" class="flat" name="search_label" value="'.stripslashes($_GET["search_label"]).'">';
	print '</td>';
	print '<td valign="right">';
	print '<input type="text" class="flat" name="search_societe" value="'.$_GET["search_societe"].'">';
	print '</td>';
	print '<td align="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Search").'">';
	print "</td>";
	print "</tr>\n";

	while ($i < $num)
	{
		$objp = $db->fetch_object( $i);    
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td><a href=\"fiche.php?id=$objp->projectid\">$objp->title</a></td>\n";
		print "<td><a href=\"fiche.php?id=$objp->projectid\">$objp->ref</a></td>\n";
		print '<td>';
		print img_object($langs->trans("ShowCompanie"),"company");

		print '&nbsp;<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		print '<td>&nbsp;</td>';
		print "</tr>\n";
    
		$i++;
	}
  
	$db->free();
}
else
{
	dolibarr_print_error($db);
}

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
