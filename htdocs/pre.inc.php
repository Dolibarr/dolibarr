<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

require ("./main.inc.php");

function llxHeader($head = "") {
  global $user, $conf, $langs;
  $user->getrights('societe');
  $user->getrights('propale');
  $user->getrights('facture');

  top_menu($head);

  $menu = new Menu();

  if ($conf->societe->enabled) 
    {
      $langs->load("companies");
      $menu->add(DOL_URL_ROOT."/societe.php", $langs->trans("Companies"));

      if ($user->rights->societe->creer)
	{
	  $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create", $langs->trans("MenuNewCompany"));
	}
      $menu->add_submenu(DOL_URL_ROOT."/societe/groupe/index.php", $langs->trans("MenuSocGroup"));
      $menu->add_submenu(DOL_URL_ROOT."/contact/index.php",$langs->trans("Contacts"));
    }

  if ($conf->commercial->enabled ) 
    {
      $langs->load("commercial");
      $menu->add(DOL_URL_ROOT."/comm/index.php",$langs->trans("Commercial"));

      $menu->add_submenu(DOL_URL_ROOT."/comm/clients.php",$langs->trans("Customers"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php",$langs->trans("Prospects"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/mailing.php","Mailing");
			
      if ($user->rights->propale->lire)
	$menu->add_submenu(DOL_URL_ROOT."/comm/propal.php", "Prop. commerciales");
    }

  if ($conf->compta->enabled ) 
    {
      $langs->load("compta");
      $menu->add(DOL_URL_ROOT."/compta/index.php", $langs->trans("Accountancy"));

      if ($user->rights->facture->lire) {
        $langs->load("bills");
    	$menu->add_submenu(DOL_URL_ROOT."/compta/facture.php", $langs->trans("Bills"));
      }
    }

  if ($conf->fichinter->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/fichinter/index.php", "Fiches d'intervention");
    }

  if ($conf->produit->enabled || $conf->service->enabled)
    {
      $langs->load("products");
      $chaine="";
      if ($conf->produit->enabled) { $chaine.= $langs->trans("Products"); }
      if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
      if ($conf->service->enabled) { $chaine.= $langs->trans("Services"); }
      $menu->add(DOL_URL_ROOT."/product/index.php", "$chaine");

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
    }

  if ($conf->adherent->enabled ) 
    {
      $langs->load("members");
      $menu->add(DOL_URL_ROOT."/adherents/index.php", $langs->trans("Members"));
    }

  if ($conf->commande->enabled)
    {
      $langs->load("orders");
      $menu->add(DOL_URL_ROOT."/commande/index.php", $langs->trans("Orders"));
      if ($conf->expedition->enabled) {
      	$menu->add_submenu(DOL_URL_ROOT."/expedition/index.php", $langs->trans("Sendings"));
      }
    }

  if ($conf->telephonie->enabled) // EXPERIMENTAL -> RODO
    {
      $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Téléphonie");
    }

  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/index.php", "Dons");
    }

  if ($conf->fournisseur->enabled)
    {
      $langs->load("suppliers");
      $menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
    }

  if ($conf->voyage && $user->societe_id == 0) 
    {

      $menu->add(DOL_URL_ROOT."/compta/voyage/index.php","Voyages");

      $menu->add_submenu(DOL_URL_ROOT."/compta/voyage/index.php","Voyages");
      $menu->add_submenu(DOL_URL_ROOT."/compta/voyage/reduc.php","Reduc");
    }

  if ($conf->domaine->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/domain/index.php", "Domaines");
    }

  if ($conf->postnuke->enabled)
    {
      $menu->add(DOL_URL_ROOT."/postnuke/articles/index.php", "Editorial");
    }

  if ($conf->rapport->enabled)
    {
	  $menu->add(DOL_URL_ROOT."/rapport/", "Rapports");
	}
	
  $menu->add(DOL_URL_ROOT."/user/index.php", $langs->trans("Users"));

  if ($user->admin)
    {      
      $menu->add(DOL_URL_ROOT."/admin/index.php", $langs->trans("Setup"));
    }

  /*
   *
   */

  left_menu($menu->liste);

}
?>
