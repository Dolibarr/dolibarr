<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../../main.inc.php");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/comm/prospect/", "Prospection");
  $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php", "Liste");

  $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=0","Derniers");

  $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=p", "Contacts");

  $menu->add(DOL_URL_ROOT."/comm/clients.php", "Clients");
  $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=c", "Contacts");


  $menu->add(DOL_URL_ROOT."/comm/action/index.php", "Actions");

  if ($conf->propal->enabled && $user->rights->propale->lire)
    {
      $menu->add(DOL_URL_ROOT."/comm/propal.php", "Prop. commerciales");
    }

  $menu->add(DOL_URL_ROOT."/contrat/index.php", "Contrats");

  if ($conf->commande->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/commande/index.php", "Commandes");
    }

  if ($conf->fichinter->enabled ) 
    {
      $menu->add(DOL_URL_ROOT."/fichinter/index.php", "Fiches d'intervention");
    }

  if ($conf->projet->enabled ) 
    {
	  $menu->add(DOL_URL_ROOT."/projet/index.php", "Projets");
	}
	
  left_menu($menu->liste);

}


?>
