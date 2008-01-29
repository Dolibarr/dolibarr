<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/compta/prelevement/pre.inc.php
        \ingroup    prelevement
        \brief      Fichier gestionnaire du menu prelevement
*/

require("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT."/bon-prelevement.class.php";

$langs->load("banks");
$langs->load("withdrawals");
$langs->load("bills");


function llxHeader($head = "", $title="", $help_url='')
{
  global $user, $conf, $langs;

  top_menu($head, $title);

  $menu = new Menu();

  if ($conf->prelevement->enabled)
    {

      if ($user->rights->prelevement->bons->lire)
	{

	  $menu->add(DOL_URL_ROOT."/compta/prelevement/",$langs->trans("StandingOrders"));

	  if ($user->rights->prelevement->bons->creer)
	    $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/create.php",$langs->trans("NewStandingOrder"));
	  
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/bons.php",$langs->trans("Receipts"));
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/liste.php",$langs->trans("List"));
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/liste_factures.php",$langs->trans("Bills"));
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/rejets.php",$langs->trans("Rejects"));
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/stats.php",$langs->trans("Statistics"));
	  
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/config.php",$langs->trans("Setup"));
	  
	  $menu->add(DOL_URL_ROOT."/compta/prelevement/demandes.php",$langs->trans("Demandes"));
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php",$langs->trans("StandingOrderToProcess"));
	  $menu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php?statut=1",$langs->trans("StandingOrderProcessed"));
	  
	}      
    }

  $langs->load("bills");
  $menu->add(DOL_URL_ROOT."/compta/facture.php",$langs->trans("Bills"));
  $menu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php",$langs->trans("BillsUnpayed"));

  left_menu($menu->liste, $help_url);
}

?>
