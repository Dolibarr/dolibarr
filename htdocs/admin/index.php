<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if ($HTTP_POST_VARS["action"] == 'changetheme')
{
  $sql = "REPLACE INTO llx_const SET name = 'MAIN_THEME', value='".$HTTP_POST_VARS["theme"]."', visible=0";

  if ($db->query($sql))
    {

    }
  Header('Location: index.php');
}

llxHeader();

print_titre("Configuration Dolibarr (version ".DOL_VERSION.")");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td><td>Valeur</td><td>Action</td>';
print "</TR>\n";

print '<tr><td>Version</td><td>' . DOL_VERSION . '</td><td>&nbsp;</td></tr>';

print '<tr><td>theme</td>';

if ($action == 'modtheme')
{
  clearstatcache();
  $dir = "../theme/";
  $handle=opendir($dir);

  print '<form method="post" action="index.php">';
  print '<input type="hidden" name="action" value="changetheme">';
  print '<td><select name="theme">';

  while (($file = readdir($handle))!==false)
    {
      if (is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
	{
	  print '<option value="'.$file.'">'.$file;
	}
    }
  print '</td><td><input type="submit" value="Enregistrer"></td></form>';
}
else
{
print '<td>' . $conf->theme . '</td><td><a href="index.php?action=modtheme">Changer</a></td></tr>';
}

print '<tr><td>Document root</td><td>' . DOL_DOCUMENT_ROOT . '</td><td>&nbsp;</td></tr>';

print '<tr class="liste_titre"><td colspan="3">Base de données</td></tr>';
print '<tr><td>Type</td><td>' . $conf->db->type . '</td><td>&nbsp;</td></tr>';
print '<tr><td>Serveur</td><td>' . $conf->db->host . '</td><td>&nbsp;</td></tr>';
print '<tr><td>Nom</td><td>' . $conf->db->name . '</td><td>&nbsp;</td></tr>';

print '</table>';

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
