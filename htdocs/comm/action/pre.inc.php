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


function llxHeader($head = "", $urlp = "")
{
    global $conf,$user,$langs;
    
    top_menu($head);
    
    $menu = new Menu();
    
    
    $langs->load("commercial");


	$langs->load("agenda");
	
	// Calendar
	$menu->add(DOL_URL_ROOT."/comm/action/index.php?leftmenu=agenda", $langs->trans("Calendar"), 0, $user->rights->agenda->myactions->read);

	// Actions
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/indexactions.php?leftmenu=agenda", $langs->trans("Actions"), 0, $user->rights->agenda->myactions->read);
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/fiche.php?leftmenu=agenda&amp;action=create", $langs->trans("NewAction"), 1, $user->rights->agenda->myactions->read);
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?leftmenu=agenda", $langs->trans("List"), 1, $user->rights->agenda->myactions->read);
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?leftmenu=agenda&amp;status=todo", $langs->trans("MenuToDoActions"),2, $user->rights->agenda->myactions->read);
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?leftmenu=agenda&amp;status=done", $langs->trans("MenuDoneActions"),2, $user->rights->agenda->myactions->read);
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/listactions.php?leftmenu=agenda&amp;time=today", $langs->trans("Today"), 2, $user->rights->agenda->myactions->read);
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/rapport/index.php?leftmenu=agenda", $langs->trans("Reportings"), 1, $user->rights->agenda->myactions->read);

	// Events
	$menu->add_submenu(DOL_URL_ROOT."/comm/action/listevents.php?leftmenu=agenda", $langs->trans("Events"), 0, $user->rights->agenda->events->read);

    
    if ($conf->societe->enabled) {
        $langs->load("companies");
        $menu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));
        $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=c", $langs->trans("Contacts"));
    }
    
    if ($conf->commercial->enabled) {
        $langs->load("commercial");
        $menu->add(DOL_URL_ROOT."/comm/prospect/prospects.php", $langs->trans("Prospects"));
        $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=p", $langs->trans("Contacts"));
    }
    
    if ($conf->propal->enabled) {
        $langs->load("propal");
        $menu->add(DOL_URL_ROOT."/comm/propal.php", $langs->trans("Prop"));
    }
    
    left_menu($menu->liste);
}

?>
