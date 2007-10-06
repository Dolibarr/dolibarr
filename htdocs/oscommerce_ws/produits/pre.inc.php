<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006 Jean Heimburger      <jean@tiaris.info>
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
        \file       htdocs/oscommerce_ws/pre.inc.php
		\brief      Fichier gestionnaire du menu de gauche
		\version    $Revision$
*/

require("../../main.inc.php");
require("./osc_product.class.php");

function llxHeader($head = "", $urlp = "")
{
	global $user, $conf, $langs;
	$langs->load("shop");
	
	top_menu($head);
	
	$menu = new Menu();
	
	$menu->add(DOL_URL_ROOT."/oscommerce_ws/index.php", $langs->trans("OSCommerceShop"));
	$menu->add_submenu(DOL_URL_ROOT."/oscommerce_ws/produits/", $langs->trans("Products"));
	$menu->add_submenu(DOL_URL_ROOT."/oscommerce_ws/produits/OSCvente.php", $langs->trans("AddProd"));
	$menu->add_submenu(DOL_URL_ROOT."/oscommerce_ws/produits/categories.php", $langs->trans("Categories"));
	$menu->add_submenu(DOL_URL_ROOT."/oscommerce_ws/clients/", $langs->trans("Clients"));	
	$menu->add_submenu(DOL_URL_ROOT."/oscommerce_ws/commandes/", $langs->trans("Commandes"));

	left_menu($menu->liste);
}

?>
