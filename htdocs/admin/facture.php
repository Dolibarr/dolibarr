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
require("./pre.inc.php");

$db = new Db();

// positionne la variable pour le test d'affichage de l'icone

$facture_addon_var = FACTURE_ADDON;

if ($action == 'set')
{
  $sql = "REPLACE INTO llx_const SET name = 'FACTURE_ADDON', value='".$value."'";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $facture_addon_var = $value;
    }
}

$db->close();


require("../includes/modules/facture/modules_facture.php");
llxHeader();

$dir = "../includes/modules/facture/";

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

print_titre("Module de facture");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (is_dir($dir.$file) && substr($file, 0, 1) <> '.')
    {
      print '<tr><td>';
      echo "$file";
      print "</td><td>\n";

      $func = $file."_get_num_explain";

      print $func();

      print '</td><td align="center">';

      if ($facture_addon_var == "$file")
	{
	  print '<img src="/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	}
      else
	{
	  print "&nbsp;";
	}

      print "</td><td>\n";

      print '<a href="facture.php?action=set&value='.$file.'">activer</a>';

      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';

llxFooter();
?>
