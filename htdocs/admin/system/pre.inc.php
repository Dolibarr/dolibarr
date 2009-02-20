<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**
 *		\file 		htdocs/admin/system/pre.inc.php
 *		\brief      Fichier gestionnaire menu page infos système
 *		\version    $Id$
 */

require("../../main.inc.php");

function llxHeader($head = "", $urlp = "")
{
    global $langs;

    $langs->load("admin");


	top_menu($head);

    $menu = new Menu();

    $menu->add(DOL_URL_ROOT."/admin/system/index.php", $langs->trans("Summary"));

    // Dolibarr
    $menu->add(DOL_URL_ROOT."/admin/system/dolibarr.php", "Dolibarr");
    $menu->add_submenu(DOL_URL_ROOT."/admin/system/constall.php", $langs->trans("AllParameters"));
	$menu->add_submenu(DOL_URL_ROOT."/admin/system/modules.php", $langs->trans("Modules"));
    $menu->add_submenu(DOL_URL_ROOT."/admin/triggers.php", $langs->trans("Triggers"));
    $menu->add_submenu(DOL_URL_ROOT."/about.php", $langs->trans("About"));

    // OS
    $menu->add(DOL_URL_ROOT."/admin/system/os.php", $langs->trans("OS"));

    // Web server
    $menu->add(DOL_URL_ROOT."/admin/system/web.php", $langs->trans("WebServer"));

    // PHP
    $menu->add(DOL_URL_ROOT."/admin/system/phpinfo.php", $langs->trans("Php"));

    // XDebug
    //$menu->add(DOL_URL_ROOT."/admin/system/xdebug.php", $langs->trans("XDebug"));

    // Database
    $menu->add(DOL_URL_ROOT."/admin/system/database.php", $langs->trans("Database"));
    $menu->add_submenu(DOL_URL_ROOT."/admin/system/database-tables.php", $langs->trans("Tables"));
    $menu->add_submenu(DOL_URL_ROOT."/admin/system/database-tables-contraintes.php", $langs->trans("Constraints"));

    left_menu($menu->liste);
}

?>
