<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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

require ("./main.inc.php3");

function llxHeader($head = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  if ($conf->societe && $conf->commercial->enabled) 
    {
      $menu->add("/societe.php", "Sociétés","company");

      if ($user->admin)
	{
	  $menu->add_submenu("../soc.php3?&action=create", "Nouvelle société");
	}
    }

  if ($conf->commercial->enabled ) 
    {
      $menu->add("/comm/index.php3", "Commercial");

      $menu->add_submenu("/comm/clients.php3", "Clients");

      $menu->add_submenu("/comm/propal.php3", "Propales");
    }

  if ($conf->compta->enabled ) 
    {
      $menu->add("/compta/", "Comptabilité");

      $menu->add_submenu("/compta/facture.php3", "Factures");
    }

  if ($conf->fichinter->enabled ) 
    {
      $menu->add("/fichinter/", "Fiches d'intervention");
    }

  if ($conf->produit->enabled )
    {
      $menu->add("/product/", "Produits");

      if ($conf->boutique->enabled)
	{
	  if ($conf->boutique->livre->enabled)
	    {
	      $menu->add_submenu("/boutique/livre/", "Livres");
	    }
	  
	  if ($conf->boutique->album->enabled)
	    {
	      $menu->add_submenu("/product/album/", "Albums");
	    }
	}
    }

  if ($conf->service->enabled ) 
    {
      $menu->add("/service/", "Services");
    }

  if ($conf->adherent->enabled ) 
    {
      $menu->add("/adherents/", "Adherents");
    }

  if ($conf->commande->enabled)
    {
      $menu->add("/commande/", "Commandes");
    }

  if ($conf->don->enabled)
    {
      $menu->add("/compta/dons/", "Dons");
    }

  if ($conf->fournisseur->enabled)
    {
      $menu->add("/fourn/index.php3", "Fournisseurs");
    }

  $menu->add("/user/", "Utilisateurs");

  if ($user->admin)
    {      
      $menu->add("/admin/", "Configuration");
    }

  if ($conf->voyage && $user->societe_id == 0) 
    {

      $menu->add("/compta/voyage/index.php3","Voyages");

      $menu->add_submenu("/compta/voyage/index.php3","Voyages");
      $menu->add_submenu("/compta/voyage/reduc.php3","Reduc");
    }

  if ($conf->domaine->enabled ) 
    {
      $menu->add("/domain/", "Domaines");
    }

  /*
   *
   */

  left_menu($menu->liste);

}
?>
