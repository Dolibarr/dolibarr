<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file 		htdocs/admin/tools/pre.inc.php
		\brief      Fichier gestionnaire menu page outils
		\version    $Id$
*/

require("../../main.inc.php");

function llxHeader($head = '', $title='', $help_url='')
{
    global $langs;

    $langs->load("admin");


	top_menu($head);

    $menu = new Menu();

    $menu->add(DOL_URL_ROOT."/admin/tools/index.php", $langs->trans("SystemTools"));
    $menu->add(DOL_URL_ROOT."/admin/tools/dolibarr_export.php", $langs->trans("Backup"),1);
    $menu->add(DOL_URL_ROOT."/admin/tools/dolibarr_import.php", $langs->trans("Restore"),1);
	$menu->add(DOL_URL_ROOT."/admin/tools/update.php", $langs->trans("Upgrade"),1);
	if (function_exists('eaccelerator_info')) $menu->add(DOL_URL_ROOT."/admin/tools/eaccelerator.php", $langs->trans("EAccelerator"),1);
	$menu->add(DOL_URL_ROOT."/admin/tools/listevents.php", $langs->trans("Audit"),1);
	$menu->add(DOL_URL_ROOT."/admin/tools/listsessions.php", $langs->trans("Sessions"),1);
	$menu->add(DOL_URL_ROOT."/admin/tools/purge.php", $langs->trans("Purge"),1);
	$menu->add(DOL_URL_ROOT."/support/index.php", $langs->trans("HelpCenter"),1,1,'targethelp');

    left_menu($menu->liste, $help_url);
}

?>
