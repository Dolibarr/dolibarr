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
require("../../main.inc.php");

function llxHeader($head = "") {
  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  /*
   * Sécurité accés client
   */

  $menu->add("index.php","Chiffre d'affaire");

  $menu->add_submenu("cumul.php","Cumuls");
  $menu->add_submenu("prev.php","Prévisionnel");
  $menu->add_submenu("comp.php","Comparatif");
  $menu->add_submenu("exercices.php","Exercices");
  $menu->add_submenu("casoc.php","Par société");
  $menu->add_submenu("cabyuser.php","Par utilisateur");

  left_menu($menu->liste);
}

?>
