<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */
require("../../main.inc.php");
require("../groupart/groupart.class.php");
require("../categorie/categorie.class.php");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  if ($conf->boutique->livre->enabled)
    {
      $menu->add(DOL_URL_ROOT."/boutique/livre/", "Livres");

      $menu->add_submenu(DOL_URL_ROOT."/boutique/livre/fiche.php?&action=create","Nouvel ouvrage");
      
      $menu->add(DOL_URL_ROOT."/boutique/auteur/", "Auteurs");

      $menu->add_submenu(DOL_URL_ROOT."/boutique/auteur/fiche.php?&action=create","Nouvel auteur");

      $menu->add(DOL_URL_ROOT."/boutique/editeur/", "Editeurs");

      $menu->add_submenu(DOL_URL_ROOT."/boutique/editeur/fiche.php?&action=create","Nouvel éditeur");

    }

  $menu->add(DOL_URL_ROOT."/product/categorie/", "Catégories");

  if ($conf->boutique->album->enabled)
    {
      $menu->add(DOL_URL_ROOT."/product/album/", "Albums");

      $menu->add_submenu("../osc-liste.php", "Osc");
      $menu->add_submenu("../osc-liste.php?reqstock=epuise", "Produits Epuisés");
      
      $menu->add_submenu(DOL_URL_ROOT."/product/album/fiche.php?&action=create","Nouvel album");
  
      $menu->add(DOL_URL_ROOT."/product/groupart/", "Artistes/Groupes");
      
      $menu->add_submenu(DOL_URL_ROOT."/product/groupart/fiche.php?&action=create","Nouvel Artiste/Groupe");
  
      $menu->add(DOL_URL_ROOT."/product/concert/", "Concerts");
      
      $menu->add_submenu("/product/concert/fiche.php?&action=create","Nouveau concert");
      
      $menu->add("../osc-reviews.php", "Critiques");
      
      $menu->add_submenu("../osc-productsbyreviews.php", "Meilleurs produits");
    }

  left_menu($menu->liste);
  /*
   *
   *
   */

}
?>
