<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

if ($HTTP_POST_VARS["action"] == 'update')
{
  dolibarr_set_const($db, "MAIN_THEME",$HTTP_POST_VARS["main_theme"]);
  dolibarr_set_const($db, "SIZE_LISTE_LIMIT",$HTTP_POST_VARS["size_liste_limit"]);

  Header("Location: $PHP_SELF");
}


llxHeader();

print_titre("Configuration IHM (Dolibarr version ".DOL_VERSION.")");

print "<br>\n";

if ($_GET["action"] == 'edit')
{
  print '<form method="post" action="'.$PHP_SELF.'">';
  print '<input type="hidden" name="action" value="update">';

  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>Nom</td><td>Valeur</td></tr>';

  print '<tr class="impair"><td>Thème</td>';
  print '<td><select name="main_theme">';
  clearstatcache();
  $dir = "../theme/";
  $handle=opendir($dir);
  while (($file = readdir($handle))!==false)
    {
      if (is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
	{
	  if ($file == MAIN_THEME)
	    {
	      print '<option value="'.$file.'" selected>'.$file;
	    }
	  else
	    {
	      print '<option value="'.$file.'">'.$file;
	    }
	}
      
    }
  print '</td></tr>';

  print '<tr class="pair"><td width="50%">Longueur maximum des listes</td><td>';
  print '<input name="size_liste_limit" size="20" value="' . SIZE_LISTE_LIMIT . '"></td></tr>';

  print '<tr class="impair"><td colspan="2">';
  print '<input type="submit" value="Enregistrer"></td></tr>';
  print '</table></form>';
}
else
{

  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>Nom</td><td>Valeur</td></tr>';
  print '<tr class="impair"><td width="50%">Thème</td><td>' . MAIN_THEME . '</td></tr>';
  print '<tr class="pair"><td>Longueur maximum des listes</td><td>' . SIZE_LISTE_LIMIT . '</td></tr>';
  print '</table><br>';

  print '<div class="tabsAction">';

  print '<a class="tabAction" href="'.$PHP_SELF.'?action=edit">Editer</a>';

  print '</div>';


}


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
