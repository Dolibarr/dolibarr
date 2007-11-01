<?php
/* Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>
 * Copyright (C) 2006 Regis Houssin     <regis@dolibarr.fr>
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
		\file       htdocs/categories/docreate.php
		\ingroup    category
		\brief      Page de creation categorie
		\version    $Revision$
*/

require "./pre.inc.php";

$user->getrights('categorie');

$langs->load("categories");


if (!isset($_REQUEST["nom"]) || !isset($_REQUEST["description"]))
	accessforbidden();


/**
 * Affichage page accueil
 */

llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("CatCreated"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

$categorie = new Categorie($db);

$categorie->label       = $_REQUEST["nom"];
$categorie->description = $_REQUEST["description"];

$cats_meres = isset($_REQUEST['cats_meres']) ? $_REQUEST['cats_meres'] : array();

$res = $categorie->create();

  if ($res < 0)
	{
	  print "<p>Impossible d'ajouter la catégorie ".$categorie->label.".</p>";
	}
  else
	{
	print "<p>La catégorie ".$categorie->label." a été ajoutée avec succès.</p>";

	  foreach ($cats_meres as $id)
    {
      $mere = new Categorie($db, $id);
	    $res = $mere->add_fille($categorie);
		 
		  if ($res < 0)
      {
         print "<p>Impossible d'associer la catégorie à \"".$mere->label."\" ($res).</p>";
      }
    }
	}


print '</td></tr></table>';

$db->close();
?>
