<?php
/* Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>
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
 */
require "./pre.inc.php";

if (!$user->rights->categorie->lire) accessforbidden();

/**
 * Affichage page accueil
 */

llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("CategoriesArea"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

$c = new Categorie ($db);

/*
 * Zone recherche produit/service
 */
print '<form method="post" action="search.php">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Search").'</td>';
print '</tr>';
print '<tr '.$bc[0].'><td>';
print $langs->trans("Name").'&nbsp;:</td><td><input class="flat" type="text" size="20" name="catname" /></td><td><input type="submit" value="'.$langs->trans ("Search").'"></td></tr>';
print '<tr '.$bc[0].'><td>';
print $langs->trans("SubCatOf").'&nbsp;:</td><td><select class="flat" name="subcatof" />';
print '<option value="-1">'.$langs->trans("Choose").'</option>';

$cats = $c->get_all_meres ();

foreach ($cats as $cat)
{
  print "<option value='".$cat->id."'>".htmlentities ($cat->label, ENT_QUOTES)."</option>\n";
}

print '</select></td><td><input type="submit" value="'.$langs->trans ("Search").'"></td></tr>';
print '</table></form>';

print '</td><td valign="top" width="70%">';

/*
 * Catégories principales
 */
$cats = $c->get_main_categories ();

if ($cats != -1)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("MainCats").'</td></tr>';
  
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
else
{
  dolibarr_print_error();
}

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
