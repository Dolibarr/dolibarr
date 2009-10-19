<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/commande/pre.inc.php
        \ingroup    commandes
        \brief      Gestionnaire du menu commandes
        \version    $Id$
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");

$langs->load("orders");


function llxHeader($head = '', $title='', $help_url='')
{
	global $user, $conf, $langs;

	top_menu($head, $title);

	$menu = new Menu();

	$menu->add(DOL_URL_ROOT."/commande/", $langs->trans("Orders"));
	$menu->add_submenu(DOL_URL_ROOT."/commande/liste.php", $langs->trans("List"));
	$menu->add_submenu(DOL_URL_ROOT."/commande/stats/", $langs->trans("Statistics"));

	if ($conf->expedition->enabled && $user->rights->expedition->lire)
	{
		$menu->add(DOL_URL_ROOT."/expedition/", $langs->trans("Sendings"));
	}

	left_menu($menu->liste, $help_url);
}
?>
