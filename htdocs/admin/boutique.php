<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/admin/boutique.php
		\ingroup    boutique
		\brief      Page d'administration/configuration du module Boutique
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


llxHeader();

$dir = "../includes/modules/facture/";

//
// \todo mettre cette section dans la base de données
//
$modules["BOUTIQUE_LIVRE"][0] = "Livres";
$modules["BOUTIQUE_LIVRE"][1] = "BOUTIQUE_LIVRE";
$modules["BOUTIQUE_LIVRE"][2] = BOUTIQUE_LIVRE;
$modules["BOUTIQUE_LIVRE"][3] = "Module de gestion des livres";

$modules["BOUTIQUE_ALBUM"][0] = "Albums";
$modules["BOUTIQUE_ALBUM"][1] = "BOUTIQUE_ALBUM";
$modules["BOUTIQUE_ALBUM"][2] = BOUTIQUE_ALBUM;
$modules["BOUTIQUE_ALBUM"][3] = "Module de gestion des albums";


if ($action == 'set')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = '".$value."', value='1', visible = 0";

  if ($db->query($sql))
    {
      $modules[$value][2] = 1;
    }
}

if ($action == 'reset')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = '".$value."', value='0', visible = 0";

  if ($db->query($sql))
    {
      $modules[$value][2] = 0;
    }
}

$db->close();

print_titre("Boutique");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";

foreach ($modules as $key => $value)
{
  $titre = $modules[$key][0];
  $const_name = $modules[$key][1];
  $const_value = $modules[$key][2];
  $desc = $modules[$key][3];


  print '<tr><td>';
  echo "$titre";
  print "</td><td>\n";
  echo "$desc";
  print '</td><td align="center">';

  if ($const_value == 1)
    {
      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
    }
  else
    {
      print "&nbsp;";
    }
  
  print '</td><td align="center">';
  
  if ($const_value == 1)
    {
      print '<a href="boutique.php?action=reset&value='.$const_name.'">Désactiver</a>';
    }
  else
    {
      print '<a href="boutique.php?action=set&value='.$const_name.'">Activer</a>';
    }
  
  print '</td></tr>';
}

print '</table>';

llxFooter();
?>
