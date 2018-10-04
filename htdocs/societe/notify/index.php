<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/societe/notify/index.php
 * \ingroup    notification
 * \brief      List of done notifications
 */

require '../../main.inc.php';
$langs->loadLangs(array("companies", "banks"));

// S�curit� acc�s client
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

if ($page == -1 || $page == null) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
 * View
 */

llxHeader();

$sql = "SELECT s.nom as name, s.rowid as socid, c.lastname, c.firstname, a.label, n.rowid";
$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c,";
$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as a,";
$sql.= " ".MAIN_DB_PREFIX."notify_def as n,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE n.fk_contact = c.rowid";
$sql.= " AND a.rowid = n.fk_action";
$sql.= " AND n.fk_soc = s.rowid";
$sql.= " AND s.entity IN (".getEntity('societe').")";
if ($socid > 0)	$sql.= " AND s.rowid = " . $user->societe_id;

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	$paramlist='';
	print_barre_liste($langs->trans("ListOfNotificationsDone"), $page, $_SERVER["PHP_SELF"], $paramlist, $sortfield,$sortorder,'',$num);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Company",$_SERVER["PHP_SELF"],"s.nom","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre("Contact",$_SERVER["PHP_SELF"],"c.lastname","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre("Action",$_SERVER["PHP_SELF"],"a.titre","","",'valign="center"',$sortfield,$sortorder);
	print "</tr>\n";

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);

		print '<tr class="oddeven">';
		print "<td><a href=\"card.php?socid=".$obj->socid."\">".$obj->name."</a></td>\n";
		print "<td>".dolGetFirstLastname($obj->firstname, $obj->lastname)."</td>\n";
		print "<td>".$obj->titre."</td>\n";
		print "</tr>\n";
		$i++;
	}
	print "</table>";
	$db->free();
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
