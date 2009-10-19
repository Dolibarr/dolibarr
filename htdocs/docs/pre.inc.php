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
 */

/**
 *	\file       htdocs/docs/pre.inc.php
 *	\brief      Fichier gestionnaire du menu de gauche de l'accueil
 *	\version    $Id$
 */

require_once("../main.inc.php");


function llxHeader($head = "", $title="", $help_url='')
{
	global $user, $conf, $langs;

	top_menu($head);

	$menu = new Menu();

	if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
	{
		$langs->load("companies");
		$menu->add(DOL_URL_ROOT."/societe.php", $langs->trans("ThirdParties"));

		if ($user->rights->societe->creer)
		{
	  		$menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create", $langs->trans("MenuNewThirdParty"));
		}

		$menu->add_submenu(DOL_URL_ROOT."/contact/index.php",$langs->trans("Contacts"));
	}

	if (! empty($conf->categorie->enabled))
	{
		$langs->load("categories");
		$menu->add(DOL_URL_ROOT."/categories/index.php?type=0", $langs->trans("Categories"));
	}

	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire)
	{
		$langs->trans("interventions");
		$menu->add(DOL_URL_ROOT."/fichinter/index.php", $langs->trans("Interventions"));
	}

	if (($conf->produit->enabled && $user->rights->produit->lire) || ($conf->service->enabled && $user->rights->service->lire))
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
