<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
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
 *
 * $Id$
 * $Source$
 *
 */

require ("./main.inc.php");

function llxHeader($head = "") {
  global $user, $conf;
  $user->getrights('societe');

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  if ($conf->societe->enabled) 
    {
      $menu->add(DOL_URL_ROOT."/societe.php", "Sociétés","company");

      if ($user->rights->societe->creer)
	{
	  $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create", "Nouvelle société");
	}
      $menu->add_submenu(DOL_URL_ROOT."/contact/index.php", "Contacts");
    }

  if ($conf->commercial->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/comm/index.php", "Commercial");

      $menu->add_submenu(DOL_URL_ROOT."/comm/clients.php", "Clients");
      $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php", "Prospects");

      if ($user->rights->propale->lire)
	$menu->add_submenu(DOL_URL_ROOT."/comm/propal.php", "Prop. commerciales");
    }

  if ($conf->compta->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/compta/index.php", "Comptabilité");

      if ($user->rights->facture->lire)
	$menu->add_submenu(DOL_URL_ROOT."/compta/facture.php", "Factures");
    }

  if ($conf->fichinter->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/fichinter/index.php", "Fiches d'intervention");
    }

  if ($conf->produit->enabled || $conf->service->enabled)
    {
      $chaine="";
      if ($conf->produit->enabled) { $chaine.="Produits"; }
      if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
      if ($conf->service->enabled) { $chaine.="Services"; }
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
      $menu->add(DOL_URL_ROOT."/adherents/index.php", "Adherents");
    }

  if ($conf->commande->enabled)
    {
      $menu->add(DOL_URL_ROOT."/commande/index.php", "Commandes");
	  if ($conf->expedition->enabled) {
      	$menu->add_submenu(DOL_URL_ROOT."/expedition/index.php", "Expéditions");
      }
    }

  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/index.php", "Dons");
    }

  if ($conf->fournisseur->enabled)
    {
      $menu->add(DOL_URL_ROOT."/fourn/index.php", "Fournisseurs");
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
	
  $menu->add(DOL_URL_ROOT."/user/index.php", "Utilisateurs");

  if ($user->admin)
    {      
      $menu->add(DOL_URL_ROOT."/admin/index.php", "Configuration");
    }

  /*
   *
   */

  left_menu($menu->liste);

}
?>
