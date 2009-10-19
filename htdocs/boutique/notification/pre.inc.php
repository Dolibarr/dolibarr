<?php
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
 */
 
/**
        \file       htdocs/boutique/notification/pre.inc.php
		\brief      Fichier gestionnaire du menu de gauche
		\version    $Id$
*/

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/boutique/notification/notification.class.php');


function llxHeader($head = "", $urlp = "")
{
	global $user,$conf, $langs;
	$langs->load("shop");
	$langs->load("products");
	
	top_menu($head);
	
	$menu = new Menu();
	
	$menu->add(DOL_URL_ROOT."/boutique/index.php", $langs->trans("OSCommerceShop"));
	$menu->add_submenu(DOL_URL_ROOT."/boutique/produits/osc-liste.php", $langs->trans("Produits"));
	$menu->add_submenu(DOL_URL_ROOT."/boutique/critiques/index.php", $langs->trans("Critiques"));
	$menu->add_submenu(DOL_URL_ROOT."/boutique/critiques/bestproduct.php", "Meilleurs produits",2);
	$menu->add_submenu(DOL_URL_ROOT."/boutique/promotion/index.php", $langs->trans("Promotion"));
	$menu->add_submenu(DOL_URL_ROOT."/boutique/notification/", $langs->trans("Notifications"));
	$menu->add_submenu(DOL_URL_ROOT."/boutique/client/", $langs->trans("Customers"));
	$menu->add_submenu(DOL_URL_ROOT."/boutique/commande/", $langs->trans("Commandes"));
	
	left_menu($menu->liste);
}
?>
