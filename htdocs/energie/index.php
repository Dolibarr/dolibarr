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
 *
 */
 
/**
   \file       htdocs/commande/index.php
   \ingroup    compta
   \brief      Page acceuil zone comptabilité
   \version    $Revision$
*/

require("./pre.inc.php");

llxHeader($langs,"",$langs->trans("Energy"),$langs->trans("Energy"));

print_titre($langs->trans("Energy"));

print '<table class="noborder" width="100%">';

print '<tr><td valign="top" width="30%">';

/*
 * Groupe
 */
$sql = "SELECT c.rowid, c.libelle";
$sql .= " FROM ".MAIN_DB_PREFIX."energie_groupe as c";
$sql .= " ORDER BY c.libelle DESC";
$resql = $db->query($sql);
if ( $resql) 
{
  $num = $db->num_rows($resql);
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("Groupes").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($resql);
	  print "<tr $bc[$var]><td><a href=\"groupe.php?id=$obj->rowid\">".$obj->libelle."</a></td>";
	  print '</tr>';
	  $i++;
	}
      print "</table><br>";
    }
}

/*
 * Compteurs
 */
$sql = "SELECT c.rowid, c.libelle";
$sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur as c";
$sql .= " ORDER BY c.libelle DESC";
$resql = $db->query($sql);
if ( $resql) 
{
  $num = $db->num_rows($resql);
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("Compteurs").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($resql);
	  print "<tr $bc[$var]><td><a href=\"compteur.php?id=$obj->rowid\">".$obj->libelle."</a></td>";
	  print '</tr>';
	  $i++;
	}
      print "</table><br>";
    }
}

print '</td><td valign="top" width="70%">';

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file=month.png" alt="" title=""><br /><br />'."\n";

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
