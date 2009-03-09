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

/**
	    \file       htdocs/comm/action/pre.inc.php
		\ingroup	agenda
		\brief      Left menu handler for acions area
		\version    $Id$
*/

require("../../main.inc.php");


function llxHeader($head = '', $title='', $help_url='')
{
    global $conf,$user,$langs;

    top_menu($head);

    $menu = new Menu();

	// Actions
	if ($conf->agenda->enabled)
	{
		$langs->load("agenda");

		// Actions
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("Actions"), 0, $user->rights->agenda->myactions->read);
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/fiche.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create", $langs->trans("NewAction"), 1, $user->rights->agenda->myactions->read);
		// Calendar
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("Calendar"), 1, $user->rights->agenda->myactions->read);
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine", $langs->trans("MenuToDoMyActions"),2, $user->rights->agenda->myactions->read);
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine", $langs->trans("MenuDoneMyActions"),2, $user->rights->agenda->myactions->read);
		if ($user->rights->agenda->allactions->read)
		{
			$menu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo", $langs->trans("MenuToDoActions"),2, $user->rights->agenda->allactions->read);
			$menu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done", $langs->trans("MenuDoneActions"),2, $user->rights->agenda->allactions->read);
		}
		// List
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("List"), 1, $user->rights->agenda->myactions->read);
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine", $langs->trans("MenuToDoMyActions"),2, $user->rights->agenda->myactions->read);
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine", $langs->trans("MenuDoneMyActions"),2, $user->rights->agenda->myactions->read);
		if ($user->rights->agenda->allactions->read)
		{
			$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo", $langs->trans("MenuToDoActions"),2, $user->rights->agenda->allactions->read);
			$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done", $langs->trans("MenuDoneActions"),2, $user->rights->agenda->allactions->read);
		}
		// Reports
		$menu->add_submenu(DOL_URL_ROOT."/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda", $langs->trans("Reportings"), 1, $user->rights->agenda->myactions->read);
	}

    left_menu($menu->liste, $help_url);
}

?>
