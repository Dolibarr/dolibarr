<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
		\file 		htdocs/ecm/pre.inc.php
		\ingroup    ecm
		\brief      File to manage left menu for ecm module
		\version    $Id$
*/

require ("../main.inc.php");

$user->getrights('ecm');

function llxHeader($head = "", $title="", $help_url='', $morehtml='')
{
	global $conf,$langs,$user;
	$langs->load("ecm");
	$langs->load("bills");
	$langs->load("propal");
	
	top_menu($head, $title);
	
	$menu = new Menu();

	$menu->add(DOL_URL_ROOT."/ecm/index.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("MenuECM"),0,$user->rights->ecm->download);
	$menu->add_submenu(DOL_URL_ROOT."/ecm/index.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("List"),1,$user->rights->ecm->download);
	//$menu->add_submenu(DOL_URL_ROOT."/ecm/index?mainmenu=ecm&action=create&idmenu=".$_SESSION["idmenu"], $langs->trans("ECMNewDocument"),1,$user->rights->ecm->upload);

	$menu->add_submenu(DOL_URL_ROOT."/ecm/docdir.php?mainmenu=ecm&action=create&idmenu=".$_SESSION["idmenu"], $langs->trans("ECMNewSection"),1,$user->rights->ecm->setup);

/*	if ($conf->societe->enabled) $menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsThirdParties"),2);
	if ($conf->contrat->enabled) $menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsContracts"),2);
	if ($conf->propal->enabled) $menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsProposals"),2);
	if ($conf->commande->enabled) $menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsOrders"),2);
	if ($conf->facture->enabled) $menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsInvoices"),2);
*/	
	left_menu($menu->liste, $help_url, $morehtml);
}
?>
