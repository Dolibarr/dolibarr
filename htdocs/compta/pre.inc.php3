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
require("../main.inc.php3");

function llxHeader($head = "") {
  global $user, $conf;


  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("/compta/index.php3","Factures");
  $menu->add_submenu("paiement.php3","Paiements");
  $menu->add_submenu("fac.php3","admin fac");

  $menu->add("ca.php3","Chiffres d'affaires");

  $menu->add_submenu("prev.php3","Prévisionnel");
  $menu->add_submenu("comp.php3","Comparatif");

  $menu->add_submenu("casoc.php3","Par société");
  $menu->add_submenu("pointmort.php3","Point mort");
  $menu->add_submenu("tva.php3","TVA");

  $menu->add("/comm/propal.php3","Propal");

  $menu->add("bank/index.php3","Bank");


  left_menu($menu->liste);

}

?>
