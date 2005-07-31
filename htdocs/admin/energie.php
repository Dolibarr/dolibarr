<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 
/**	    \file       htdocs/admin/energie.php
	    \ingroup    energie
	    \brief      Page d'administration/configuration du module de gestion de l'energie
	    \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'JPGRAPH_DIR'";

  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible)";
  $sql .= " VALUES ('JPGRAPH_DIR','".$_POST["url"]."',0)";
	
  if ($db->query($sql))
    {
      Header("Location: energie.php");
    }
  else
    {
      dolibarr_print_error($db);
    }
}

llxHeader();

/*
 *
 *
 */

print_titre($langs->trans("Energy"));

print '<br>';
print '<form method="post" action="energie.php">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table class="border">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("NewValue").'</td><td>'.$langs->trans("CurrentValue").'</td>';
print "</tr>\n";
print '<tr><td>';
print $langs->trans("Emplacement de la librairie JpGraph").'</td><td>';
print '<input size="45" type="text" name="url" value="'.JPGRAPH_DIR.'">';
print '</td><td>';
print JPGRAPH_DIR;
print '</td></tr>';

print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

$db->close();

llxFooter();
?>
