<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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

/*!	\file htdocs/admin/system/pre.inc.php
		\brief      Gestionnaire menu page infos système
		\version    $Revision$
*/

require("../../main.inc.php");

function llxHeader($head = "", $urlp = "") {
    global $langs;

    $langs->load("admin");
    
    /*
    *
    *
    */
    top_menu($head);
    
    $menu = new Menu();
    
    $menu->add("index.php", $langs->trans("Summary"));
    
    $menu->add(DOL_URL_ROOT."/about.php", "Dolibarr");
    $menu->add_submenu("constall.php", $langs->trans("AllParameters"));
    
    $menu->add("os.php", $langs->trans("OS"));
    
    $menu->add("web.php", $langs->trans("WebServer"));
    
    $menu->add("phpinfo.php", $langs->trans("Php"));
    
    $menu->add_submenu("phpinfo.php?what=conf", $langs->trans("PhpConf"));
    
    $menu->add_submenu("phpinfo.php?what=env", $langs->trans("PhpEnv"));
    
    $menu->add_submenu("phpinfo.php?what=modules", $langs->trans("PhpModules"));
    
    $menu->add("pear.php", $langs->trans("Pear"));
    $menu->add_submenu("pear_packages.php", $langs->trans("PearPackages"));
    
    $menu->add("database.php", $langs->trans("Database"));
    $menu->add_submenu("database-tables.php", $langs->trans("Tables"));
    $menu->add_submenu("database-tables-contraintes.php", $langs->trans("Constraints"));
    
    left_menu($menu->liste);
}

?>
