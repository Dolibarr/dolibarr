<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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
 
/*!	\file htdocs/admin/ldap.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'CLICKTODIAL_URL'";

  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible)";
  $sql .= " VALUES ('CLICKTODIAL_URL','".$_POST["url"]."',0)";
	
  if ($db->query($sql))
    {
      Header("Location: clicktodial.php");
    }
  else
    {
      print $db->error();
    }
}

llxHeader();

/*
 *
 *
 */

print_titre("Configuration du click to dial");

print '<form method="post" action="clicktodial.php">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table class="border" cellpadding="4" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Nouvelle Valeur</td><td>Valeur actuelle</td>';
print "</tr>\n";
print '<tr><td>';
print 'URL</td><td>';
print '<input size="25" type="text" name="url" value="'.CLICKTODIAL_URL.'">';
print '</td><td>';
print CLICKTODIAL_URL;
print '</td></tr>';

print '<tr><td colspan="3" align="center"><input type="submit" value="changer"></td></tr>';
print '</table></form>';

/*
 *
 *
 */

$db->close();

llxFooter();
?>
