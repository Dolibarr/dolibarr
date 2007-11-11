<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/categories/liste.php
        \ingroup    category
        \brief      Page liste des categories
*/

require "./pre.inc.php";

if (!$user->rights->categorie->lire) accessforbidden();


llxHeader ("","",$langs->trans("Categories"));

print_fiche_titre ($langs->trans ("CatList"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

$c = new Categorie ($db);
$cats = $c->get_all_categories ();


if ($cats != -1)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Ref").'</td>';
  print '<td>'.$langs->trans("Description").'</td>';
  print '<td align="right">'.$langs->trans("Type").'</td>';
  print '</tr>';
  
  $var=true;
  foreach ($cats as $cat)
    {
      $var = ! $var;
      print "\t<tr ".$bc[$var].">\n";
      print "\t\t<td><a href='viewcat.php?id=".$cat->id."'>".$cat->label."</a></td>\n";
      print "\t\t<td>".dolibarr_trunc($cat->description,36)."</td>\n";
      print '<td align="right">';
	  if ($cat->type == 0) print $langs->trans("Product");
	  elseif ($cat->type == 1) print $langs->trans("Supplier");
	  elseif ($cat->type == 2) print $langs->trans("Customer");
	  else print $cat->type;
	  print "</td>\n";
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

llxFooter('$Date$ - $Revision$');
?>
