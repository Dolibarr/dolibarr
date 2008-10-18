<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser  <simon@kornog-computing.com>
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
   \file       htdocs/admin/pre.inc.php
   \brief      Fichier gestionnaire du menu de gauche de l'espace configuration
   \version    $Id$
*/

$res=@include("../main.inc.php");
if (! $res) include("../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only


$langs->load("admin");


function llxHeader($head = '', $title='', $help_url='')
{
	global $conf, $user, $langs;

	top_menu($head, $title, $target);

	$menuarray=array();
	
	//if ($conf->left_menu == 'rodolphe.php')
	//{
	$langs->load("admin");
	$langs->load("users");
	$menu = new Menu();
	$menu->add(DOL_URL_ROOT."/admin/company.php", $langs->trans("MenuCompanySetup"));
	$menu->add(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
	$menu->add(DOL_URL_ROOT."/admin/menus.php", $langs->trans("Menus"));
	$menu->add(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));
	$menu->add(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
	$menu->add(DOL_URL_ROOT."/admin/delais.php",$langs->trans("Alerts"));
	$menu->add(DOL_URL_ROOT."/admin/perms.php", $langs->trans("Security"));
	$menu->add(DOL_URL_ROOT."/admin/mails.php", $langs->trans("EMails"));
	$menu->add(DOL_URL_ROOT."/admin/limits.php", $langs->trans("Limits"));
	$menu->add(DOL_URL_ROOT."/user/home.php", $langs->trans("MenuUsersAndGroups"));
	$menu->add(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
	$menu->add(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));
	$menu->add(DOL_URL_ROOT."/admin/system/", $langs->trans("System"));
	$menu->add(DOL_URL_ROOT."/admin/tools/", $langs->trans("Tools"));
	$varmenuarray=$menu->liste;
	//}
	
	left_menu($varmenuarray, $help_url);
}

?>
