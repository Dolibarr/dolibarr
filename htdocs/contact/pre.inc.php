<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../main.inc.php");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/contact/index.php", "Contacts");
  $menu->add_submenu(DOL_URL_ROOT."/contact/fiche.php?action=create", "Nouveau Contact");

  $menu->add(DOL_URL_ROOT."/contact/index.php?userid=$user->id", "Mes contacts");

  $menu->add(DOL_URL_ROOT."/contact/index.php?view=recent", "Contacts récents");

  $menu->add(DOL_URL_ROOT."/contact/index.php?view=phone", "Téléphones");

  $menu->add(DOL_URL_ROOT."/contact/index.php?view=mail", "Emails");

  left_menu($menu->liste);
}
?>
