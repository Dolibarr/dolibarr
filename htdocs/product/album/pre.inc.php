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
require("./album.class.php");
require("../groupart/groupart.class.php");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("/product/album/", "Albums");

  $menu->add_submenu("../osc-liste.php", "Osc");
  $menu->add_submenu("../osc-liste.php?reqstock=epuise", "Produits Epuisés");

  $menu->add_submenu("/product/album/fiche.php?&action=create","Nouvel album");

  $menu->add("/product/groupart/", "Artistes/Groupes");

  $menu->add_submenu("/product/groupart/fiche.php?&action=create","Nouvel Artiste/Groupe");

  $menu->add("/product/concert/", "Concerts");

  $menu->add_submenu("/product/concert/fiche.php?&action=create","Nouveau concert");

  $menu->add("../osc-reviews.php", "Critiques");

  $menu->add_submenu("../osc-productsbyreviews.php", "Meilleurs produits");


  left_menu($menu->liste);
  /*
   *
   *
   */

}
?>
