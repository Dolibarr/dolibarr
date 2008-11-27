<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/comm/propal/pre.inc.php
        \ingroup    propale
		\brief      Fichier de gestion du menu gauche du module propale
		\version    $Revision$
*/

require("../../main.inc.php");

function llxHeader($head = "", $urlp = "") {
  global $user, $conf, $langs;

  $langs->load("companies");

  top_menu($head);

  $menu = new Menu();

  // Clients
  $menu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));
  if ($user->rights->societe->creer)
    {
      $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=c", $langs->trans("MenuNewCustomer"));
    }
  $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=c", $langs->trans("Contacts"));

  // Prospects
  $menu->add(DOL_URL_ROOT."/comm/prospect/prospects.php", $langs->trans("Prospects"));
  if ($user->rights->societe->creer)
    {
      $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=p", $langs->trans("MenuNewProspect"));
    }
  $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=p", $langs->trans("Contacts"));


  $menu->add(DOL_URL_ROOT."/comm/action/index.php", $langs->trans("Actions"));


  if ($conf->propal->enabled && $user->rights->propale->lire)
    {
      $langs->load("propal");
      $menu->add(DOL_URL_ROOT."/comm/propal.php", $langs->trans("Prop"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=0", $langs->trans("PropalsDraft"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=1", $langs->trans("PropalsOpened"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=2,3,4", $langs->trans("PropalStatusClosedShort"));
      $menu->add_submenu(DOL_URL_ROOT."/comm/propal/stats/", $langs->trans("Statistics"));
    }

  if ($conf->contrat->enabled)
    {
      $langs->load("contracts");
      $menu->add(DOL_URL_ROOT."/contrat/index.php", $langs->trans("Contracts"));
    }

  if ($conf->commande->enabled ) 
    {
      $langs->load("orders");
      $menu->add(DOL_URL_ROOT."/commande/index.php", $langs->trans("Orders"));
    }

  if ($conf->ficheinter->enabled) 
    {
      $langs->load("interventions");
      $menu->add(DOL_URL_ROOT."/fichinter/index.php", $langs->trans("Interventions"));
    }

  if ($conf->produit->enabled || $conf->service->enabled)
    {
      $langs->load("products");
      $chaine="";
	  if ($conf->produit->enabled) { $chaine.=$langs->trans("Products"); }
	  if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
	  if ($conf->service->enabled) { $chaine.=$langs->trans("Services"); }
      $menu->add(DOL_URL_ROOT."/product/index.php", "$chaine");
    }

  if ($conf->projet->enabled ) 
    {
      $langs->load("projects");
	  $menu->add(DOL_URL_ROOT."/projet/index.php", $langs->trans("Projects"));
	}
	
  left_menu($menu->liste);

}


?>
