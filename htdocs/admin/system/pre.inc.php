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
 */
require("../../main.inc.php3");

function llxHeader($head = "", $urlp = "") {
  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php", "System");
  $menu->add_submenu("../modules.php", "Modules");

  $menu->add("mysql.php", "Mysql");

  $menu->add("pear.php", "Pear");
  $menu->add_submenu("pear_packages.php", "Paquets");

  $menu->add("const.php", "Constantes");

  $menu->add_submenu("constall.php", "Tout voir");

  $menu->add("phpinfo.php", "phpinfo");

  $menu->add_submenu("phpinfo.php?what=conf", "Conf");

  $menu->add_submenu("phpinfo.php?what=env", "Env");

  $menu->add_submenu("phpinfo.php?what=modules", "Modules");

  $menu->add(DOL_URL_ROOT."/admin/", "Configuration");

  left_menu($menu->liste);
}

?>
