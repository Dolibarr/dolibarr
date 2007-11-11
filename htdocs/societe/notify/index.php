<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/societe/notify.index.php
		\ingroup    notification
		\brief      Liste des notifications réalisées
		\version    $Revision$
*/
 
require("./pre.inc.php");
$langs->load("companies");
$langs->load("banks");

// Sécurité accés client
if ($user->societe_id > 0) 
{
	$action = '';
	$socid = $user->societe_id;
}

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="s.nom";
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
 * Mode Liste
 *
 */

llxHeader();

$sql = "SELECT s.nom, s.rowid as socid, c.name, c.firstname, a.titre,n.rowid FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE n.fk_contact = c.rowid AND a.rowid = n.fk_action";
$sql .= " AND n.fk_soc = s.rowid";
if ($socid > 0)
{
	$sql .= " AND s.rowid = " . $user->societe_id;
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	
	$paramlist='';
	print_barre_liste($langs->trans("ListOfNotificationsDone"), $page, "index.php", $paramlist, $sortfield,$sortorder,'',$num);
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Contact"),"index.php","c.name","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),"index.php","a.titre","","",'valign="center"',$sortfield,$sortorder);
	print "</tr>\n";
	$var=True;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
	
		$var=!$var;
	
		print "<tr $bc[$var]>";
		print "<td><a href=\"fiche.php?socid=".$obj->socid."\">$obj->nom</A></td>\n";
		print "<td>".$obj->firstname." ".$obj->name."</td>\n";
		print "<td>".$obj->titre."</td>\n";
		print "</tr>\n";
		$i++;
	}
	print "</table>";
	$db->free();
}
else
{
	dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
