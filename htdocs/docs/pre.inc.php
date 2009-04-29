<?php
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *	\file       htdocs/docs/pre.inc.php
 *	\brief      Fichier gestionnaire du menu de gauche de l'accueil
 *	\version    $Source$
 */
require ("../main.inc.php");


require_once("../main.inc.php");


function llxHeader($head = "", $title="", $help_url='')
{
	global $user, $conf, $langs;

	top_menu($head);

	$menu = new Menu();

	if ($conf->societe->enabled && $user->rights->societe->lire)
	{
		$langs->load("companies");
		$menu->add(DOL_URL_ROOT."/societe.php", $langs->trans("Companies"));

		if(is_dir("societe/groupe"))
		{
	  $menu->add_submenu(DOL_URL_ROOT."/societe/groupe/index.php", $langs->trans("MenuSocGroup"));
		}
		$menu->add_submenu(DOL_URL_ROOT."/contact/index.php",$langs->trans("Contacts"));
	}

	if ($conf->commercial->enabled && $user->rights->commercial->main->lire)
	{
		$langs->load("commercial");
		$menu->add(DOL_URL_ROOT."/comm/index.php",$langs->trans("Commercial"));

		$menu->add_submenu(DOL_URL_ROOT."/comm/clients.php",$langs->trans("Customers"));
		$menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php",$langs->trans("Prospects"));

		if ($user->rights->propale->lire)
		{
	  $langs->load("propal");
	  $menu->add_submenu(DOL_URL_ROOT."/comm/propal.php", $langs->trans("Prop"));
		}
	}

	if ($conf->compta->enabled || $conf->comptaexpert->enabled)
	{
		$langs->load("compta");
		$menu->add(DOL_URL_ROOT."/compta/index.php", $langs->trans("MenuFinancial"));

		if ($user->rights->facture->lire) {
			$langs->load("bills");
			$menu->add_submenu(DOL_URL_ROOT."/compta/facture.php", $langs->trans("Bills"));
		}
	}

	if ($conf->ficheinter->enabled && $user->rights->ficheinter->lire)
	{
		$langs->trans("interventions");
		$menu->add(DOL_URL_ROOT."/fichinter/index.php", $langs->trans("Interventions"));
	}

	if (($conf->produit->enabled || $conf->service->enabled) && $user->rights->produit->lire)
	{
		$langs->load("products");
		$chaine="";
		if ($conf->produit->enabled) { $chaine.= $langs->trans("Products"); }
		if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
		if ($conf->service->enabled) { $chaine.= $langs->trans("Services"); }
		$menu->add(DOL_URL_ROOT."/product/index.php", "$chaine");

	}


	if ($conf->commande->enabled && $user->rights->commande->lire)
	{
		$langs->load("orders");
		$menu->add(DOL_URL_ROOT."/commande/index.php", $langs->trans("Orders"));
	}

	if ($conf->document->enabled)
	{
		$menu->add(DOL_URL_ROOT."/docs/index.php", $langs->trans("DocumentsBuilder"));
		$menu->add_submenu(DOL_URL_ROOT."/docs/generate.php", $langs->trans("Build"));
	}


	if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire)
	{
		$langs->load("suppliers");
		$menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
	}



	left_menu($menu->liste);
}
?>
