<?php
/* Copyright (C) 2005 Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005 Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/categories/index.php
        \ingroup    categorie
        \brief      Page accueil espace categories
*/

require "./pre.inc.php";

if (!$user->rights->categorie->lire) accessforbidden();


/**
 * Affichage page accueil
 */

llxHeader("","",$langs->trans("Categories"));
$html = new Form($db);
print_fiche_titre($langs->trans("CategoriesArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

$c = new Categorie ($db);

/*
 * Zone recherche produit/service
 */
print '<form method="post" action="index.php">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Search").'</td>';
print '</tr>';
print '<tr '.$bc[0].'><td>';
print $langs->trans("Name").':</td><td><input class="flat" type="text" size="20" name="catname" value="' . $_POST['catname'] . '"/></td><td><input type="submit" class="button" value="'.$langs->trans ("Search").'"></td></tr>';
/*
// faire une rech dans une sous catégorie uniquement
print '<tr '.$bc[0].'><td>';
print $langs->trans("SubCatOf").':</td><td>';

print $html->select_all_categories('','subcatof');
print '</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans ("Search").'"></td></tr>';
*/

print '</table></form>';

print '</td><td valign="top" width="70%">';


/*
 * Catégories trouvées
 */

if($_POST['catname']) {
  $cats = $c->rechercher_par_nom ($_POST['catname']);
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FoundCats").'</td></tr>';
  
  foreach ($cats as $cat)
    {
      $i = !$i;
      print "\t<tr ".$bc[$i].">\n";
      print "\t\t<td><a href='viewcat.php?id=".$cat->id."'>".$cat->label."</a></td>\n";
      print "\t\t<td>".$cat->description."</td>\n";
      print "\t</tr>\n";
    }
  print "</table>";
  
}

/*
 * Catégories principales
 */
$cats = $c->get_main_categories ();

if ($cats != -1)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("MainCats").'</td></tr>';
  
  foreach ($cats as $cat)
    {
      $i = !$i;
      print "\t<tr ".$bc[$i].">\n";
      print "\t\t<td><a href='viewcat.php?id=".$cat->id."'>".$cat->label."</a></td>\n";
      print "\t\t<td>".$cat->description."</td>\n";
      
      if ($cat->visible == 1)
			{
				print "\t\t<td>".$langs->trans("ContentsVisibleByAllShort")."</td>\n";
			}
			else
			{
				print "\t\t<td>".$langs->trans("ContentsNoVisibleByAllShort")."</td>\n";
			}
      
      print "\t</tr>\n";
    }
  print "</table>";
}
else
{
  dolibarr_print_error();
}


print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
