<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../main.inc.php3");

function llxHeader($head = "", $urlp = "")
{
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/product/index.php3", "Produits");

  $menu->add_submenu("fiche.php3?&action=create","Nouveau produit");

  $menu->add_submenu("popuprop.php", "Popularité");

  if (defined("MAIN_MODULE_BOUTIQUE") && MAIN_MODULE_BOUTIQUE)
    {

      $menu->add_submenu("osc-liste.php", "Osc");
      $menu->add_submenu("osc-liste.php?reqstock=epuise", "Produits Epuisés");


      $menu->add("osc-reviews.php", "Critiques");

      $menu->add_submenu("osc-productsbyreviews.php", "Meilleurs produits");

      $menu->add(DOL_URL_ROOT."/product/album/", "Albums");
      $menu->add(DOL_URL_ROOT."/product/groupart/", "Groupes/Artistes");
      
      $menu->add(DOL_URL_ROOT."/product/categorie/", "Catégories");
    }      
     
  $menu->add(DOL_URL_ROOT."/service/index.php3", "Services");

  left_menu($menu->liste);
  /*
   *
   *
   */

}
?>
