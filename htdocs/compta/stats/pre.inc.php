<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
require("../../main.inc.php");

function llxHeader($head = "") {
	global $conf;

	/*
	*
	*
	*/
	top_menu($head);
	
	$menu = new Menu();
	
	$menu->add(DOL_URL_ROOT."/compta/resultat/","Résultat / Exercice");
    $menu->add_submenu(DOL_URL_ROOT."/compta/resultat/clientfourn.php","Détail client/fourn.");
    $menu->add_submenu(DOL_URL_ROOT."/compta/resultat/compteres.php","Compte de résultat");
    $menu->add_submenu(DOL_URL_ROOT."/compta/resultat/bilan.php","Bilan");
	
	$menu->add(DOL_URL_ROOT."/compta/stats/index.php","Chiffre d'affaire");
	
	$menu->add_submenu(DOL_URL_ROOT."/compta/stats/cumul.php","Cumulé");
	if ($conf->propal->enabled) {
		$menu->add_submenu(DOL_URL_ROOT."/compta/stats/prev.php","Prévisionnel");
		$menu->add_submenu(DOL_URL_ROOT."/compta/stats/comp.php","Transformé");
	}
	$menu->add_submenu(DOL_URL_ROOT."/compta/stats/exercices.php","Evolution");
	$menu->add_submenu(DOL_URL_ROOT."/compta/stats/casoc.php","Par société");
	$menu->add_submenu(DOL_URL_ROOT."/compta/stats/cabyuser.php","Par utilisateur");
	
	left_menu($menu->liste);
}

?>
