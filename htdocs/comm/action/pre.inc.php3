<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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

  $menu->add("/comm/clients.php3", "Clients");

  $menu->add_submenu("/soc.php3?&action=create", "Nouvelle société");
  $menu->add_submenu("/comm/contact.php3", "Contacts");

  $menu->add("/comm/action/", "Actions");

  $menu->add("/comm/propal.php3", "Propales");

  $menu->add_submenu("/comm/propal.php3?viewstatut=0", "Brouillons");
  $menu->add_submenu("/comm/propal.php3?viewstatut=1", "Ouvertes");

  $menu->add("/product/", "Produits");

  $menu->add("/service/", "Services");

  $menu->add("/comm/projet/", "Projets");

  left_menu($menu->liste);

}

?>
