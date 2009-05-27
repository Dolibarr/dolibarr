<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
      \file   	    htdocs/compta/commande/pre.inc.php
      \ingroup      compta,commande
      \brief  	    Fichier gestionnaire du menu commande de compta
*/

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');

$langs->load("orders");


function llxHeader($head = "", $title="", $help_url='')
{
    global $user, $conf, $langs;

    top_menu($head, $title);

    $menu = new Menu();

    // Les recettes

    $langs->load("commercial");
    $menu->add(DOL_URL_ROOT."/compta/clients.php", $langs->trans("Customers"));

    if ($conf->propal->enabled)
    {
        $langs->load("propal");
        $menu->add(DOL_URL_ROOT."/compta/propal.php",$langs->trans("Prop"));
    }

    if ($conf->contrat->enabled)
    {
        $langs->load("contracts");
        $menu->add(DOL_URL_ROOT."/contrat/",$langs->trans("Contracts"));
    }

    if ($conf->don->enabled)
    {
        $langs->load("donations");
        $menu->add(DOL_URL_ROOT."/compta/dons/",$langs->trans("Donations"));
    }

    if ($conf->facture->enabled)
    {
        $langs->load("bills");
        $menu->add(DOL_URL_ROOT."/compta/facture.php",$langs->trans("Bills"));
        $menu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php",$langs->trans("Unpayed"));
        $menu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php",$langs->trans("Payments"));
        $menu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php", $langs->trans("Repeatable"));

        $menu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/", $langs->trans("Statistics"));
    }


    if ($conf->commande->enabled && $conf->facture->enabled)
    {
        $langs->load("orders");
        $menu->add(DOL_URL_ROOT."/compta/commande/liste.php?leftmenu=orders&afacturer=1", $langs->trans("MenuOrdersToBill"));
    }


    // Les d�penses
    if ($conf->fournisseur->enabled)
    {
        $langs->load("suppliers");
        $menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
    }

    if ($conf->deplacement->enabled && $user->societe_id == 0)
    {
        $menu->add(DOL_URL_ROOT."/compta/deplacement/", "D�placement");
    }

    if ($conf->tax->enabled && $conf->compta->tva && $user->societe_id == 0)
    {
        $menu->add(DOL_URL_ROOT."/compta/tva/index.php",$langs->trans("VAT"));
    }

    if ($conf->tax->enabled)
    {
        $menu->add(DOL_URL_ROOT."/compta/charges/index.php",$langs->trans("Charges"));
    }


    // Vision des recettes-d�penses
    if ($conf->banque->enabled && $user->rights->banque->lire)
    {
        $langs->load("banks");
        $menu->add(DOL_URL_ROOT."/compta/bank/",$langs->trans("Bank"));
    }

    $menu->add(DOL_URL_ROOT."/compta/stats/",$langs->trans("Reportings"));

    if ($conf->prelevement->enabled)
    {
        $menu->add(DOL_URL_ROOT."/compta/prelevement/",$langs->trans("StandingOrders"));
    }

    if ($conf->compta->enabled)
    {
    	if ($user->rights->compta->ventilation->creer)
		{
	    	$menu->add(DOL_URL_ROOT."/compta/ventilation/",$langs->trans("Ventilation"));
		}

	    if ($user->rights->compta->ventilation->parametrer)
	    {
	        $menu->add(DOL_URL_ROOT."/compta/param/",$langs->trans("Param"));
	    }
	}


    left_menu($menu->liste, $help_url);
}

?>
