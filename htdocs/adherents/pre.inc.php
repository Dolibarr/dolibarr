<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
 */

/**     \file       htdocs/adherents/pre.inc.php
        \ingroup    adherent
		\brief      Fichier de gestion du menu gauche du module adherent
		\version    $Id$
*/

require("../main.inc.php");

function llxHeader($head = "")
{
	global $user, $conf, $langs;

	$langs->load("members");

	top_menu($head);

	$menu = new Menu();


	$menu->add(DOL_URL_ROOT."/adherents/index.php",$langs->trans("Members"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/fiche.php?action=create",$langs->trans("NewMember"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/liste.php",$langs->trans("List"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=-1",$langs->trans("MenuMembersToValidate"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=1",$langs->trans("MenuMembersValidated"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=1&amp;filter=outofdate",$langs->trans("MenuMembersNotUpToDate"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=1&amp;filter=uptodate",$langs->trans("MenuMembersUpToDate"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=0",$langs->trans("MenuMembersResiliated"));

	$menu->add(DOL_URL_ROOT."/adherents/public.php?leftmenu=member_public",$langs->trans("MemberPublicLinks"));

	$menu->add(DOL_URL_ROOT."/adherents/index.php",$langs->trans("Exports"));
	$menu->add_submenu(DOL_URL_ROOT."/exports/index.php?leftmenu=export",$langs->trans("Datas"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/htpasswd.php",$langs->trans("Filehtpasswd"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/cartes/carte.php",$langs->trans("MembersCards"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/cartes/etiquette.php",$langs->trans("MembersTickets"));

	$langs->load("compta");
	$menu->add(DOL_URL_ROOT."/adherents/index.php",$langs->trans("Accountancy"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/cotisations.php",$langs->trans("Subscriptions"));
	$langs->load("banks");
	$menu->add_submenu(DOL_URL_ROOT."/compta/bank/",$langs->trans("Banks"));

	$menu->add(DOL_URL_ROOT."/adherents/index.php",$langs->trans("Setup"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/type.php",$langs->trans("MembersTypes"));
	$menu->add_submenu(DOL_URL_ROOT."/adherents/options.php",$langs->trans("MembersAttributes"));

	left_menu($menu->liste);

}

?>
