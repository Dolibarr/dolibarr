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
require("../main.inc.php");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/comm/clients.php", "Clients");

  $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php", "Contacts");

  $menu->add(DOL_URL_ROOT."/comm/action/", "Actions");

  if ($user->rights->propale->lire)
    {
      $menu->add(DOL_URL_ROOT."/comm/propal.php", "Propales");
      $menu->add_submenu("propal.php?viewstatut=0", "Brouillons");
      $menu->add_submenu("propal.php?viewstatut=1", "Ouvertes");
    }

  if ($conf->fichinter->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/fichinter/", "Fiches d'intervention");
    }

  if ($conf->produit->enabled )
    {
      $menu->add(DOL_URL_ROOT."/product/", "Produits");
    }

  $menu->add(DOL_URL_ROOT."/contrat/", "Contrats");

  if ($conf->service->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/service/", "Services");
    }

  $menu->add(DOL_URL_ROOT."/projet/", "Projets");

  left_menu($menu->liste);

}


?>
