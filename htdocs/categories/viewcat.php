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

if ($_REQUEST['id'] == "")
{
  dolibarr_print_error ();
  exit ();
}

$c = new Categorie ($db, $_REQUEST['id']);

print_titre ($langs->trans("Categorie")." ".$c->label);
?>
<table border="0" width="100%">
<tr><td valign="top" width="30%">
<?php

$ways = $c->print_all_ways ();
print "<div id='ways'>";
foreach ($ways as $way)
{
  print $way."<br />\n";
}
print "</div>";

$cats = $c->get_filles ();

if ($cats < 0)
{
  dolibarr_print_error();
}
else if (sizeof ($cats) > 0)
{
  print "<table class='noborder' width='100%'>\n";
	print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("SubCats")."</td></tr>\n";
	
	foreach ($cats as $cat)
	  {
	    $i++;
	    print "\t<tr ".$bc[$i%2].">\n";
	    print "\t\t<td><a href='viewcat.php?id=".$cat->id."'>".$cat->label."</a></td>\n";
	    print "\t\t<td>".$cat->description."</td>\n";
	    print "\t</tr>\n";
	  }
	print "</table>\n<br/>\n";
}
else
{
  print "<p>".$langs->trans("NoSubCat")."</p>";
}

$i = 0;

$prods = $c->get_products ();

if ($prods < 0)
{
  dolibarr_print_error();
}
else if (sizeof ($prods) > 0)
{
  print "<table class='noborder' width='100%'>\n";
  print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("Products")."</td></tr>\n";
  
  foreach ($prods as $prod)
    {
      $i++;
      print "\t<tr ".$bc[$i%2].">\n";
      print "\t\t<td><a href='".DOL_URL_ROOT."/product/fiche.php?id=".$prod->id."'>".$prod->libelle."</a></td>\n";
      print "\t\t<td>".$prod->description."</td>\n";
      print "\t</tr>\n";
    }
  print "</table>\n";
}
else
{
  print "<p>".$langs->trans ("NoProd")."</p>";
}

print "<div class='tabsAction'>\n";
print "<a class='tabAction' href='edit.php?id=".$c->id."'>&Eacute;diter</a>";
print "<a class='tabAction' href='delete.php?id=".$c->id."'>Supprimer</a>";
print "</div>";
print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
