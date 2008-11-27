<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
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
   \file       htdocs/pre.inc.php
   \brief      File to manage left menu for home page
   \version    $Id$
*/
require ("./main.inc.php");


function llxHeader($head = "")
{
  global $user, $conf, $langs;

  top_menu($head);
  
  $menu = new Menu();
  
  if ($conf->societe->enabled && $user->rights->societe->lire)
    {
      $langs->load("companies");
      $menu->add(DOL_URL_ROOT."/societe.php", $langs->trans("Companies"));
	
      if ($user->rights->societe->creer)
        {
	  $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create", $langs->trans("MenuNewCompany"));
        }
      
      if(is_dir("societe/groupe"))
        {
	  $menu->add_submenu(DOL_URL_ROOT."/societe/groupe/index.php", $langs->trans("MenuSocGroup"));
        }
      $menu->add_submenu(DOL_URL_ROOT."/contact/index.php",$langs->trans("Contacts"));
    }

	if ($conf->categorie->enabled)
	{
		$langs->load("categories");
		$menu->add(DOL_URL_ROOT."/categories/index.php?type=0", $langs->trans("Categories"));
	}
	
  if ($conf->commercial->enabled && isset($user->rights->commercial->lire) && $user->rights->commercial->lire)
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
      
/*
        if ($conf->boutique->enabled)
        {
            if ($conf->boutique->livre->enabled)
            {
                $menu->add_submenu(DOL_URL_ROOT."/boutique/livre/index.php", "Livres");
            }

            if ($conf->boutique->album->enabled)
            {
                $menu->add_submenu(DOL_URL_ROOT."/product/album/index.php", "Albums");
            }
        }
*/        
    }


  if ($conf->commande->enabled && $user->rights->commande->lire)
    {
      $langs->load("orders");
      $menu->add(DOL_URL_ROOT."/commande/index.php", $langs->trans("Orders"));
    }
  
  if ($conf->document->enabled)
    {
      $menu->add(DOL_URL_ROOT."/docs/index.php", $langs->trans("Documents"));
    }
  
  if ($conf->expedition->enabled && $user->rights->expedition->lire)
    {
      $langs->load("sendings");
      $menu->add(DOL_URL_ROOT."/expedition/index.php", $langs->trans("Sendings"));
    }
  
  if ($conf->mailing->enabled && $user->rights->mailing->lire)
    {
      $langs->load("mails");
      $menu->add(DOL_URL_ROOT."/comm/mailing/index.php",$langs->trans("EMailings"));
    }
  
  if ($conf->telephonie->enabled)
    {
      $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Téléphonie");
    }
  
  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/index.php", $langs->trans("Donations"));
    }
  
  if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire)
    {
      $langs->load("suppliers");
      $menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
    }
  
  if ($conf->voyage->enabled && $user->societe_id == 0)
    {
      $menu->add(DOL_URL_ROOT."/compta/voyage/index.php","Voyages");
      $menu->add_submenu(DOL_URL_ROOT."/compta/voyage/index.php","Voyages");
      $menu->add_submenu(DOL_URL_ROOT."/compta/voyage/reduc.php","Reduc");
    }
  
  if ($conf->domaine->enabled)
    {
      $menu->add(DOL_URL_ROOT."/domain/index.php", "Domaines");
    }
  
  if ($conf->postnuke->enabled)
    {
      $menu->add(DOL_URL_ROOT."/postnuke/articles/index.php", "Editorial");
    }
  
  if ($conf->bookmark->enabled && $user->rights->bookmark->lire)
    {
      $menu->add(DOL_URL_ROOT."/bookmarks/liste.php", $langs->trans("Bookmarks"));
    }
  
  if ($conf->export->enabled)
    {
      $langs->load("exports");
      $menu->add(DOL_URL_ROOT."/exports/index.php", $langs->trans("Exports"));
    }
  
  if ($user->rights->user->user->lire || $user->admin)
    {
      $langs->load("users");
      $menu->add(DOL_URL_ROOT."/user/home.php", $langs->trans("MenuUsersAndGroups"));
    }
  
  if ($user->admin)
    {
      $menu->add(DOL_URL_ROOT."/admin/index.php", $langs->trans("Setup"));
    }
    
  left_menu($menu->liste);  
}
?>
