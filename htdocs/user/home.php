<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/user/home.php
		\brief      Page acceuil de la zone utilisateurs et groupes
		\version    $Revision$
*/
 
require("./pre.inc.php");
	  
$langs->load("users");

$user->getrights('users');



llxHeader();


print_titre($langs->trans("MenuUsersAndGroups"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';


/*
 * Recherche Group
 */
    $var=false;
	print '<table class="noborder" width="100%">';
	print '<form method="post" action="'.DOL_URL_ROOT.'/user/group/index.php">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("SearchAGroup").'</td></tr>';
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("Ref").' : <input class="flat" type="text" name="search_group">&nbsp;<input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "</form></table><br>\n";

/*
 * Recherche User
 */
    $var=false;
	print '<table class="noborder" width="100%">';
	print '<form method="post" action="'.DOL_URL_ROOT.'/user/index.php">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("SearchAUser").'</td></tr>';
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("Ref").' : <input class="flat" type="text" name="search_user">&nbsp;<input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "</form></table><br>\n";


print '</td><td valign="top" width="70%">';


/*
 * Derniers groupes créés
 *
 */
$max=0;

$sql = "SELECT g.rowid, g.nom, g.note, ".$db->pdate("g.datec")." as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
$sql .= " ORDER BY g.datec DESC";
if ($max) $sql .= " LIMIT $max";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastGroupsCreated").'</td></tr>';
  $var = true;
  $i = 0;

  while ($i < $num && (! $max || $i < $max)) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"".DOL_URL_ROOT."/user/group/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowGroup"),"group")." ".$obj->nom."</a></td>";
	  print "<td width=\"120\" align=\"center\">".dolibarr_print_date($obj->datec,"%d %b %Y")."</td>";
      print '</tr>';
	  $i++;
	}
  print "</table><br>";

  $db->free();
} 
else
{
  dolibarr_print_error($db);
}


/*
 * Derniers utilisateurs créés
 *
 */
$max=5;

$sql = "SELECT u.rowid, u.login, u.name, u.firstname, ".$db->pdate("u.datec")." as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " ORDER BY u.datec DESC limit $max";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastUsersCreated",min($num,$max)).'</td></tr>';
  $var = true;
  $i = 0;

  while ($i < $num && $i < $max) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"".DOL_URL_ROOT."/user/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowUser"),"user")." ".$obj->firstname." ".$obj->name."</a></td>";
	  print "<td width=\"120\" align=\"center\">".strftime("%d %b %Y",$obj->datec)."</td>";
      print '</tr>';
	  $i++;
	}
  print "</table><br>";

  $db->free();
} 
else
{
  dolibarr_print_error($db);
}



print '</td></tr>';
print '</table>';

$db->close();
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
