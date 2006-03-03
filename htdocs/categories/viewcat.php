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

if ($_REQUEST['id'] == "")
{
  dolibarr_print_error ();
  exit ();
}

$c = new Categorie ($db, $_REQUEST['id']);


/*
 * Affichage fiche categorie
 */
 
llxHeader ("","",$langs->trans("Categories"));

print_fiche_titre($langs->trans("Categorie")." ".$c->label);

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td valign="top" width="30%" class="notopnoleft">';

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
else
{
	print "<br>";
	print "<table class='noborder' width='100%'>\n";
	print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("SubCats")."</td></tr>\n";
	if (sizeof ($cats) > 0)
	{	
		$var=true;
		foreach ($cats as $cat)
		{
			$i++;
			$var=!$var;
			print "\t<tr ".$bc[$var].">\n";
			print "\t\t<td><a href='viewcat.php?id=".$cat->id."'>".$cat->label."</a></td>\n";
			print "\t\t<td>".$cat->description."</td>\n";
			print "\t</tr>\n";
		}
	}
	else
	{
		print "<tr><td>".$langs->trans("NoSubCat")."</td></tr>";
	}
	print "</table>\n";
}

$i = 0;

$prods = $c->get_products ();

if ($prods < 0)
{
  dolibarr_print_error();
}
else
{
	print "<br>";
	print "<table class='noborder' width='100%'>\n";
	print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("ProductsAndServices")."</td></tr>\n";
	
	if (sizeof ($prods) > 0)
	{
		$var=true;
		foreach ($prods as $prod)
		{
			$i++;
			$var=!$var;
			print "\t<tr ".$bc[$var].">\n";
			print "\t\t<td><a href='".DOL_URL_ROOT."/product/fiche.php?id=".$prod->id."'>".$prod->libelle."</a></td>\n";
			print "\t\t<td>".$prod->description."</td>\n";
			print "\t</tr>\n";
		}
	}
	else
	{
		print "<tr><td>".$langs->trans ("NoProd")."</td></tr>";
	}
	print "</table>\n";
}

/*
 * Boutons actions
 */
print "<div class='tabsAction'>\n";
print "<a class='tabAction' href='edit.php?id=".$c->id."'>".$langs->trans("Edit")."</a>";
print "<a class='tabAction' href='delete.php?id=".$c->id."'>".$langs->trans("Delete")."</a>";
print "</div>";


print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
