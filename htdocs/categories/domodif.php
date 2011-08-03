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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
		\file       htdocs/categories/domodif.php
		\ingroup    category
		\brief      Page de modification categorie
		\version    $Revision: 1.6 $
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

if (!isset ($_REQUEST["id"]) || !isset ($_REQUEST["nom"]) || !isset ($_REQUEST["description"]))
	accessforbidden();


/**
 * Affichage page accueil
 */

llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("CatCreated"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

$cat = new Categorie ($db, $_REQUEST['id']);

$cat->label       = $_REQUEST["nom"];
$cat->description = $_REQUEST["description"];

$new_cats_meres = isset ($_REQUEST['cats_meres']) ? $_REQUEST['cats_meres'] : array ();
// tableau d'id de categories

$old_objs_meres = $cat->get_meres ();
// tableau d'objets categorie

$old_cats_meres = array ();
foreach ($old_objs_meres as $old_obj_mere)
{	// transformation en tableau d'id
  $old_cats_meres[] = $old_obj_mere->id;
}

$asupprimer = array ();	// tableaux des categories meres
$aajouter   = array ();	// a ajouter ou a supprimer

foreach ($old_cats_meres as $old_cat)
{
  if (!in_array ($old_cat, $new_cats_meres))
    {
      $asupprimer[] = new Categorie ($db, $old_cat);
    }
}

foreach ($new_cats_meres as $new_cat)
{
  if (!in_array ($new_cat, $old_cats_meres))
    {
      $aajouter[] = new Categorie ($db, $new_cat);
    }
}

$res = $cat->update ();
if ($res < 0)
{
  print "<p>Impossible de modifier la categorie ".$cat->label.".</p>";
}
else
{
  print "<p>La categorie ".$cat->label." a ete modifiee avec succes.</p>";

  foreach ($asupprimer as $old_mere)
    {
      $res = $old_mere->del_fille ($cat);
      if ($res < 0)
	{
	  print "<p>Impossible d'enlever la categorie de \"".$old_mere->label."\" ($res).</p>\n";
	}
      else
	{
	  print "<p>La categorie ne fait plus partie de ".$old_mere->label.".</p>\n";
	}
    }

  foreach ($aajouter as $new_mere)
    {
      $res = $new_mere->add_fille ($cat);
      if ($res < 0)
	{
	  print "<p>Impossible d'ajouter la categorie a \"".$new_mere->label."\" ($res).</p>";
	}
      else
	{
	  print "<p>La categorie fait maintenant partie de ".$new_mere->label.".</p>\n";
	}
    }
}

print '</td></tr></table>';

$db->close();
?>
