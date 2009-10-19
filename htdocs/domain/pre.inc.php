<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

require_once("../main.inc.php");


function llxHeader($head = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("/comm/index.php", "Commercial");

  $menu->add_submenu("/comm/clients.php", "Clients");

  $menu->add_submenu("/compta/", "Factures");


  left_menu($menu->liste);

}
?>
