<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/contact/pre.inc.php
   \brief      File to manage left menu for contact area
   \version    $Id$
*/
require("../main.inc.php");



function llxHeader($head = '', $title='', $help_url='')
{
	global $langs, $user, $conf;

	$langs->load("companies");
	$langs->load("commercial");

	top_menu($head);

	$menu = new Menu();

	if ($user->rights->societe->contact->lire)
	{
		$menu->add(DOL_URL_ROOT."/contact/index.php", $langs->trans("Contacts"));
	}
	if ($user->rights->societe->contact->creer)
	{
		$menu->add_submenu(DOL_URL_ROOT."/contact/fiche.php?action=create", $langs->trans("NewContact"));
	}
	if ($user->rights->societe->contact->lire)
	{
		$menu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->rights->societe->contact->lire);
		$menu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->rights->societe->contact->lire);
		$menu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->rights->societe->contact->lire);
		$menu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=o", $langs->trans("Others"), 2, $user->rights->societe->contact->lire);

		$menu->add(DOL_URL_ROOT."/contact/index.php?view=recent", $langs->trans("LastContacts"));
		$menu->add(DOL_URL_ROOT."/contact/index.php?view=phone", $langs->trans("Phones"));
		$menu->add(DOL_URL_ROOT."/contact/index.php?view=mail", $langs->trans("EMails"));

		$menu->add(DOL_URL_ROOT."/contact/index.php?userid=$user->id", $langs->trans("MyContacts"));
	}

	left_menu($menu->liste, $help_url);
}

?>
