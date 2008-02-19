<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
		\file 		htdocs/ecm/pre.inc.php
		\ingroup    ecm
		\brief      File to manage left menu for ecm module
		\version    $Id$
*/

require ("../main.inc.php");

$user->getrights('ecm');

function llxHeader($head = "", $title="", $help_url='')
{
	global $conf,$langs;
	$langs->load("ecm");
	$langs->load("bills");
	$langs->load("propal");
	
	top_menu($head, $title);
	
	$menu = new Menu();

	$menu->add(DOL_URL_ROOT."/ecm/index.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("MenuECM"));
	$menu->add_submenu(DOL_URL_ROOT."/ecm/docmine.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsMine"));
	$menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsThirdParties"));
	$menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsInvoices"));
	$menu->add_submenu(DOL_URL_ROOT."/ecm/docother.php?mainmenu=ecm&idmenu=".$_SESSION["idmenu"], $langs->trans("DocsProposals"));
	
	left_menu($menu->liste, $help_url);
}
?>
