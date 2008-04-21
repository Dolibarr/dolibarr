<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/admin/tools/listevents.php
        \ingroup    core
        \brief      List of security events
        \version    $Id$
*/
 
require_once("./pre.inc.php");

if (! $user->admin)
  accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$langs->load("companies");

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="dateevent";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
*	View
*/

llxHeader();

$userstatic=new User($db);

$sql = "SELECT e.rowid, e.type, e.ip, ".$db->pdate("e.dateevent")." as dateevent,";
$sql.= " e.fk_user, e.description,";
$sql.= " u.login";
$sql.= " FROM ".MAIN_DB_PREFIX."events as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = e.fk_user";
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print_barre_liste($langs->trans("ListOfSecurityEvents"), $page, "listevents.php","",$sortfield,$sortorder,'',$num);

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"e.dateevent","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Code"),$_SERVER["PHP_SELF"],"e.type","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("IP"),$_SERVER["PHP_SELF"],"e.ip","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("User"),$_SERVER["PHP_SELF"],"u.login","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"e.description","","",'align="left"',$sortfield,$sortorder);
	print '<td>&nbsp;</td>';
	print "</tr>\n";

/*
	// Lignes des champs de filtre
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<tr class="liste_titre">';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_compta" value="'.$_GET["search_user"].'">';
	print '</td>';

	print '<td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'">';
	print '</td>';
	
	print "</tr>\n";
	print '</form>';
*/
	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object();
		
		$var=!$var;

		print "<tr $bc[$var]>";
	
		// Date
		print '<td align="left" nowrap="nowrap">'.dolibarr_print_date($obj->dateevent,'%Y-%m-%d %H:%M:%S').'</td>';

		// Code
		print '<td>'.$obj->type.'</td>';

		// IP
		print '<td>'.$obj->ip.'</td>';

		// Login
		print '<td>';
		if ($obj->fk_user)
		{
			$userstatic->id=$obj->fk_user;
			$userstatic->login=$obj->login;
			print $userstatic->getLoginUrl(1);
		}
		else print '&nbsp;';
		print '</td>';

		// Description
		print '<td>'.$obj->description.'</td>';
		
		print '<td>&nbsp;</td>';

		print "</tr>\n";
		$i++;
	}
	
	if ($num == 0)
	print '<tr><td colspan="6">'.$langs->trans("NoEventOrNoAuditSetup").'</td></tr>';
	print "</table>";
	$db->free();
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ r&eacute;vision $Revision$');
?>
