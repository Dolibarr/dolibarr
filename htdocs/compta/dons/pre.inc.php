<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/compta/dons/pre.inc.php
        \ingroup    don
		\brief      Fichier gestionnaire du menu de gauche de l'espace dons
		\version    $Revision$
*/

require("../../main.inc.php");

$langs->load("donations");
$langs->load("propal");

$libelle[0] = $langs->trans("PromisesNotValid");
$libelle[1] = $langs->trans("PromisesValid");
$libelle[2] = $langs->trans("DonationsPayed");
$libelle[3] = $langs->trans("DonationsReceived");


function llxHeader($head = "")
{
	global $user, $conf, $langs;
	
	$langs->load("donations");
	
	top_menu($head);
	
	$menu = new Menu();
	
	$menu->add(DOL_URL_ROOT."/compta/dons/",$langs->trans("Donations"));
	$menu->add_submenu(DOL_URL_ROOT."/compta/dons/fiche.php?action=create",$langs->trans("NewDonation"));
	$menu->add_submenu(DOL_URL_ROOT."/compta/dons/liste.php",$langs->trans("List"));
	$menu->add_submenu(DOL_URL_ROOT."/compta/dons/stats.php",$langs->trans("Statistics"));
	
	if ($conf->banque->enabled)
	{
		$langs->load("banks");
		$menu->add(DOL_URL_ROOT."/compta/bank/index.php",$langs->trans("Bank"));
	}
	
	left_menu($menu->liste);
}

?>
