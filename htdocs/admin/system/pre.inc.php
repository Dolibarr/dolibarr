<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("../../main.inc.php");

function llxHeader($head = "", $urlp = "") {
  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php", "Résumé");

  $menu->add(DOL_URL_ROOT."/about.php", "Dolibarr");
  $menu->add_submenu("constall.php", "Tous&nbsp;les&nbsp;paramètres");

  $menu->add("os.php", "OS");

  $menu->add("web.php", "Serveur Web");

  $menu->add("phpinfo.php", "Php");

  $menu->add_submenu("phpinfo.php?what=conf", "Conf");

  $menu->add_submenu("phpinfo.php?what=env", "Env");

  $menu->add_submenu("phpinfo.php?what=modules", "Modules");

  $menu->add("pear.php", "Pear");
  $menu->add_submenu("pear_packages.php", "Paquets");

  $menu->add("mysql.php", "Base de données");
  $menu->add_submenu("mysql-tables.php", "Tables");
  $menu->add_submenu("mysql-tables-contraintes.php", "Tables Contraintes");

  left_menu($menu->liste);
}

?>
