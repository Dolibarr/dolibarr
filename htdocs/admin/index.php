<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'MAIN_THEME', value='".$HTTP_POST_VARS["theme"]."', visible=0";

  if ($db->query($sql))
    {

    }
  Header('Location: index.php');
}

if ($HTTP_POST_VARS["action"] == 'update')
{
  dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM",$HTTP_POST_VARS["nom"]);
  dolibarr_set_const($db, "MAIN_INFO_TVAINTRA",$HTTP_POST_VARS["tva"]);

  Header('Location: index.php');
}


llxHeader();

print_titre("Configuration Dolibarr (version ".DOL_VERSION.")");

print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td><td>Valeur</td><td>Action</td>';
print "</TR>\n";

print '<tr class="pair"><td>Version</td><td>' . DOL_VERSION . '</td><td>&nbsp;</td></tr>';
print '<tr class="impair"><td>theme</td>';

if ($_GET["action"] == 'modtheme')
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
	  if ($file == MAIN_THEME)
	    {
	      print '<option value="'.$file.'" SELECTED>'.$file;
	    }
	  else
	    {
	      print '<option value="'.$file.'">'.$file;
	    }
	}
      
    }
  print '</td><td><input type="submit" value="Enregistrer"></td></form>';
}
else
{
  print '<td>' . $conf->theme . '</td><td><a href="index.php?action=modtheme">Changer</a></td></tr>';
}
print '</table><br>';

if ($_GET["action"] == 'edit')
{
  print '<form method="post" action="index.php">';
  print '<input type="hidden" name="action" value="update">';

  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td colspan="3">Informations sur la société</td></tr>';

  print '<tr class="impair"><td>Nom de la société</td><td>';
  print '<input name="nom" value="'. MAIN_INFO_SOCIETE_NOM . '"></td></tr>';

  print '<tr class="pair"><td width="50%">Numéro de tva intracommunautaire</td><td>';
  print '<input name="tva" size="20" value="' . MAIN_INFO_TVAINTRA . '"></td></tr>';

  print '<tr class="impair"><td colspan="2">';
  print '<input type="submit" value="Enregistrer"></td></tr>';
  print '</table></form>';
}
else
{

  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td colspan="3">Informations sur la société</td></tr>';
  print '<tr class="impair"><td width="50%">Nom de la société</td><td>' . MAIN_INFO_SOCIETE_NOM . '</td></tr>';
  print '<tr class="pair"><td>Numéro de tva intracommunautaire</td><td>' . MAIN_INFO_TVAINTRA . '</td></tr>';
  print '</table><br>';

  print '<div class="tabsAction">';

  print '<a class="tabAction" href="index.php?action=edit">Editer</a>';

  print '</div>';


}


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
