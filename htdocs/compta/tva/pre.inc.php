<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

require("../../main.inc.php");

function llxHeader($head = "") {
  global $user, $conf, $langs;


  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php",$langs->trans("VAT"));

  $menu->add_submenu("reglement.php",$langs->trans("Payments"));
  $menu->add_submenu("fiche.php?action=create",$langs->trans("NewPayment"));
  $menu->add_submenu("clients.php",$langs->trans("Clients"));
  $menu->add_submenu("quadri.php",$langs->trans("Quadri"));

  left_menu($menu->liste);
}

?>
