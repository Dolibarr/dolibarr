<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/product/pre.inc.php
 *	\ingroup    product,service
 *	\brief      Fichier gestionnaire du menu gauche des produits et services
 *	\version    $Id$
 */
require("../../main.inc.php");

$langs->load("products");


function llxHeader($head = "", $urlp = "", $title="")
{
	global $user, $conf, $langs;
	$langs->load("products");

	top_menu($head, $title);

	$menu = new Menu();

	// Products
	if ($conf->produit->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/index.php?type=0", $langs->trans("Products"));
		$menu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=0", $langs->trans("List"));

		if ($user->societe_id == 0 && $user->rights->produit->creer)
		{
	 		$menu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=0", $langs->trans("NewProduct"));
		}
	}

	// Services
	if ($conf->service->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/index.php?type=1", $langs->trans("Services"));
		$menu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=1", $langs->trans("List"));
		if ($user->societe_id == 0  && $user->rights->produit->creer)
		{
	  		$menu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=1", $langs->trans("NewService"));
		}
	}

	if ($conf->boutique->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/osc-liste.php", "Osc");
		$menu->add_submenu(DOL_URL_ROOT."/product/osc-liste.php?reqstock=epuise", "Produits Epuis�s");

		$menu->add(DOL_URL_ROOT."/product/osc-reviews.php", $langs->trans("Criticals"));

		$menu->add_submenu(DOL_URL_ROOT."/product/osc-productsbyreviews.php", "Meilleurs produits");

		$menu->add(DOL_URL_ROOT."/product/album/", "Albums");
		$menu->add(DOL_URL_ROOT."/product/groupart/", "Groupes/Artistes");

		$menu->add(DOL_URL_ROOT."/product/categorie/", $langs->trans("Categories"));
	}

	if ($conf->fournisseur->enabled) {
		$langs->load("suppliers");
		$menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
	}

	$menu->add(DOL_URL_ROOT."/product/stats/", $langs->trans("Statistics"));
	if ($conf->propal->enabled)
	{
		$menu->add_submenu(DOL_URL_ROOT."/product/popuprop.php", $langs->trans("Popularity"));
	}

	if ($conf->stock->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/stock/", $langs->trans("Stock"));
	}

	if ($conf->categorie->enabled)
	{
		$menu->add(DOL_URL_ROOT."/categories/", $langs->trans("Categories"));
	}

	left_menu($menu->liste);
	/*
	 *
	 *
	 */

}
?>
