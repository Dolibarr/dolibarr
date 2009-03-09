<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file   	htdocs/fourn/pre.inc.php
 \ingroup    fournisseur,facture
 \brief  	Fichier gestionnaire du menu fournisseurs
 */

require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php";



function llxHeader($head = '', $title='', $help_url='')
{
	global $user, $conf, $langs;

	$langs->load("suppliers");
	$langs->load("propal");

	top_menu($head, $title);

	$menu = new Menu();

	if ($conf->fournisseur->enabled && $user->rights->societe->lire)
	{
		$menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));

		// Sécurité accés client
		if ($user->societe_id == 0 && $user->rights->societe->creer)
		{
			$menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=f",$langs->trans("NewSupplier"));
		}

		$menu->add_submenu(DOL_URL_ROOT."/fourn/liste.php",$langs->trans("List"));

		if ($conf->societe->enabled )
		{
	  $menu->add_submenu(DOL_URL_ROOT."/fourn/contact.php",$langs->trans("Contacts"));
		}

		$menu->add_submenu(DOL_URL_ROOT."/fourn/stats.php",$langs->trans("Statistics"));
	}

	$langs->load("bills");
	if ($user->societe_id == 0 && $user->rights->fournisseur->facture->lire)
	{
		$menu->add(DOL_URL_ROOT."/fourn/facture/index.php", $langs->trans("Bills"));
	}
	if ($user->societe_id == 0 && $user->rights->fournisseur->facture->creer)
	{
		$menu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"));
	}
	if ($user->rights->fournisseur->facture->lire)
	{
		$menu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"));
	}
	$langs->load("orders");
	if ($user->rights->fournisseur->commande->lire)
	{
		$menu->add(DOL_URL_ROOT."/fourn/commande/",$langs->trans("Orders"));
	}
	if ($conf->produit->enabled || $conf->service->enabled)
	{
		if ($user->rights->produit->lire)
		{
			$menu->add(DOL_URL_ROOT."/product/", $langs->trans("Products"));
		}
	}

	if ($conf->categorie->enabled)
	{
		$langs->load("categories");
		// Catégories fournisseurs
		$menu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=1", $langs->trans("SuppliersCategoriesShort"), 0);
	}

	left_menu($menu->liste, $help_url);
}
?>
