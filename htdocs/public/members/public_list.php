<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/public/members/priv_liste.php
        \brief      File sample to list members
        \version    $Id$
*/

require("../../master.inc.php");

$langs->setDefaultLang('auto');

$langs->load("main");
$langs->load("members");
$langs->load("companies");


function llxHeaderVierge($title, $head = "")
{
	global $user, $conf, $langs;

	header("Content-type: text/html; charset=".$conf->character_set_client);
	print "<html>\n";
    print "<head>\n";
    print "<title>".$title."</title>\n";
    if ($head) print $head."\n";
    print "</head>\n";
	print "<body>\n";
}

function llxFooter()
{
	print "</body>\n";
	print "</html>\n";
}



$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];
$filter=$_GET["filter"];
$statut=isset($_GET["statut"])?$_GET["statut"]:'';

if (! $sortorder) {  $sortorder="ASC"; }
if (! $sortfield) {  $sortfield="nom"; }
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * View
 */

llxHeaderVierge($langs->trans("ListOfValidatedPublicMembers"));


$sql = "select rowid, prenom, nom, societe, cp, ville, email, naiss, photo";
$sql.= " from ".MAIN_DB_PREFIX."adherent where statut=1 and public=1";
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
//$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, cp, ville, d.email, t.libelle as type, d.morphy, d.statut, t.cotisation";
//$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
//$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = $statut";
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	$param="&statut=$statut&sortorder=$sortorder&sortfield=$sortfield";
	print_barre_liste($langs->trans("ListOfValidatedPublicMembers"), $page, "priv_liste.php", $param, $sortfield, $sortorder, '', $num, 0, '');
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.prenom\">".$langs->trans("Surname")."</a> <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.nom\">".$langs->trans("Name")."</a> / <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.societe\">".$langs->trans("Company")."</a></td>\n";
	print_liste_field_titre($langs->trans("Birthdate"),"priv_liste.php","naiss","",$param,$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("EMail"),"priv_liste.php","email","",$param,$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),"priv_liste.php","cp","",$param,$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),"priv_liste.php","ville","",$param,$sortfield,$sortorder);
	print "<td>".$langs->trans("Photo")."</td>\n";
	print "</tr>\n";

	$var=True;
	while ($i < $num && $i < $conf->liste_limit)
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td><a href=\"priv_fiche.php?id=$objp->rowid\">".$objp->prenom." ".$objp->nom.($objp->societe?" / ".$objp->societe:"")."</a></TD>\n";
		print "<td>$objp->naiss</td>\n";
		print "<td>$objp->email</td>\n";
		print "<td>$objp->cp</td>\n";
		print "<td>$objp->ville</td>\n";
		if (isset($objp->photo) && $objp->photo!= '')
		{
			print "<td><A HREF=\"$objp->photo\"><IMG SRC=\"$objp->photo\" HEIGHT=64 WIDTH=64></A></TD>\n";
		}
		else
		{
			print "<td>&nbsp;</td>\n";
		}
		print "</tr>";
		$i++;
	}
	print "</table>";
}
else
{
	dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
