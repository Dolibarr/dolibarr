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

$types[0] = "produit";
$types[1] = "service";

function llxHeader($head = "", $urlp = "")
{
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/product/index.php?type=0", "Produits");
  $menu->add_submenu("liste.php?type=0","Liste");
  $menu->add_submenu("fiche.php3?&action=create&type=0","Nouveau produit");

  $menu->add(DOL_URL_ROOT."/product/index.php?type=1", "Services");
  $menu->add_submenu("liste.php?type=1","Liste");
  $menu->add_submenu("fiche.php3?&action=create&type=1","Nouveau service");

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
    
  // La notion de service est abandonnée pour le moment 
  //  $menu->add(DOL_URL_ROOT."/service/index.php", "Services");

  $menu->add("stats/", "Statistiques");
  $menu->add_submenu("popuprop.php", "Popularité");

  left_menu($menu->liste);
  /*
   *
   *
   */

}
?>
