<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file htdocs/admin/perms.php
		\brief      Page d'administration/configuration des permissions par defaut
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("users");

if (!$user->admin)
  accessforbidden();


if ($_GET["action"] == 'add')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=1 WHERE id =".$_GET["pid"];
  $db->query($sql);
}

if ($_GET["action"] == 'remove')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=0 WHERE id =".$_GET["pid"];
  $db->query($sql);
}

llxHeader();

print_titre($langs->trans("DefaultRights"));

print "<br>".$langs->trans("DefaultRightsDesc")."<br><br>\n";


print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%">';

# Affiche lignes des constantes

$sql = "SELECT r.id, r.libelle, r.module, r.bydefault FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql .= " WHERE type <> 'a'";
$sql .= " ORDER BY r.id ASC";

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  $var=True;
  $old = "";
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;

      if ($old <> $obj->module)
	{
	  print '<tr class="liste_titre">';
	  print '<td>'.$langs->trans("Permission").'</td>';
	  print '<td>'.$langs->trans("Module").'</td>';
	  print '<td align="center">'.$langs->trans("Default").'</td>';
	  print '<td align="center">&nbsp;</td>';
	  print "</tr>\n";
	  $old = $obj->module;
	}

      print '<tr '. $bc[$var].'>';
      print '<td>'.$obj->libelle . '</td><td>'.$obj->module . '</td><td align="center">';
      if ($obj->bydefault == 1)
	{

	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0">';
	  print '</td><td>';
	  print '<a href="perms.php?pid='.$obj->id.'&amp;action=remove">'.img_edit_remove().'</a>';
	}
      else
	{
	  print '&nbsp;';
	  print '</td><td>';
	  print '<a href="perms.php?pid='.$obj->id.'&amp;action=add">'.img_edit_add().'</a>';
	}

      print '</td></tr>';
      $i++;
    }
}

print '</table>';

$db->close();

llxFooter();
?>
