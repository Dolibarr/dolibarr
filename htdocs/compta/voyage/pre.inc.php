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

function llxHeader($head = "") {
  global $user, $conf;


  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php","Voyages");

  $menu->add_submenu("index.php","Voyages");
  $menu->add_submenu("bilan.php3","Bilan");
  $menu->add_submenu("reduc.php3","Reduc");
  $menu->add_submenu("voyage.php3","Voyage");

  $menu->add("/compta/facture.php3","Factures");

  $menu->add("/compta/bank/index.php","Bank");

  left_menu($menu->liste);

}

?>
