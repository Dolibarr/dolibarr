<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 *
 * $Id$
 * $Source$
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

  if ($conf->societe ) 
    {
      $menu->add("/societe.php", "Sociétés","company");

      if ($user->admin)
	{
	  $menu->add_submenu("../soc.php3?&action=create", "Nouvelle société");
	}
    }

  if ($conf->commercial ) 
    {
      $menu->add("/comm/index.php3", "Commercial");

      $menu->add_submenu("/comm/clients.php3", "Clients");

      $menu->add_submenu("/comm/propal.php3", "Propales");
    }

  if ($user->compta > 0) 
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
    }

  if ($conf->service->enabled ) 
    {
      $menu->add("/service/", "Services");
    }

  if ($conf->adherent->enabled ) 
    {
      $menu->add("/adherents/", "Adherents");
    }

  $menu->add("/compta/dons/", "Dons");

  $menu->add("/fourn/index.php3", "Fournisseurs");

  $menu->add("/user/", "Utilisateurs");

  $menu->add("/info.php3", "Configuration");

  if ($conf->voyage) 
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
