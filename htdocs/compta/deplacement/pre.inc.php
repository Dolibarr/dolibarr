<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./deplacement.class.php");

$deplacement_type[0] = "voiture";
//$deplacement_type[1] = "train";
//$deplacement_type[2] = "avion";

function llxHeader($head = "") {
  global $user, $conf;


  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php","Déplacements");
  $menu->add_submenu("fiche.php?action=create","Nouveau déplacement");

  left_menu($menu->liste);
}

?>
