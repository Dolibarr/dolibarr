<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!
	    \file   	htdocs/fourn/facture/pre.inc.php
  	    \ingroup    fournisseur,facture
  	    \brief  	Fichier gestionnaire du menu factures fournisseurs
*/

require("../../main.inc.php");


function llxHeader($head = "", $urlp = "") {
  global $user, $conf;

  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/fourn/index.php", "Fournisseurs");

  if ($user->societe_id == 0) 
    {
      $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&type=f","Nouveau");
    }


  $menu->add(DOL_URL_ROOT."/fourn/facture/index.php", "Factures");

  if ($user->societe_id == 0) 
    {
      $menu->add_submenu("fiche.php?action=create","Nouvelle");
    }

  $menu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", "Paiements");

  left_menu($menu->liste);
}


?>
