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
require("../../main.inc.php3");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("/boutique/livre/", "Livres");

  $menu->add_submenu("/boutique/livre/fiche.php?&action=create","Nouvel ouvrage");

  $menu->add_submenu("/boutique/livre/vignettes.php","Vignettes manquantes");

  $menu->add("/boutique/auteur/", "Auteurs");

  $menu->add_submenu("/boutique/auteur/fiche.php?&action=create","Nouvel auteur");

  $menu->add("/boutique/editeur/", "Editeurs");

  $menu->add_submenu("/boutique/editeur/fiche.php?&action=create","Nouvel éditeur");

  $menu->add("/product/categorie/", "Catégories");

  $menu->add("/product/promotion/", "Promotions");

  if (defined("MAIN_MODULE_POSTNUKE") && MAIN_MODULE_POSTNUKE)
    {
      $menu->add("/postnuke/", "Editorial");
    }

  left_menu($menu->liste);
}
?>
