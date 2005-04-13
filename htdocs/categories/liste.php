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


llxHeader ("","",$langs->trans("Categories"));

print_titre ($langs->trans ("CatList"));
?>
<table border="0" width="100%">
<tr><td valign="top" width="30%">
<?php
$c = new Categorie ($db);
$cats = $c->get_all_categories ();


if ($cats != -1)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("AllCats").'</td></tr>';
  
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
