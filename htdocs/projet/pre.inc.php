<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	\file 		htdocs/projet/pre.inc.php
	\ingroup    projet
	\brief      Fichier de gestion du menu gauche du module projet
	\version    $Id$
*/

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("commercial");


function llxHeader($head = "", $title="", $help_url='')
{
  global $langs;
  
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/projet/fiche.php?leftmenu=projects&action=create", $langs->trans("Customers"));

  $menu->add(DOL_URL_ROOT."/projet/", $langs->trans("Projects"));
  $menu->add_submenu(DOL_URL_ROOT."/projet/liste.php", $langs->trans("List"));

  $menu->add(DOL_URL_ROOT."/projet/tasks/", $langs->trans("Tasks"));
  $menu->add_submenu(DOL_URL_ROOT."/projet/tasks/mytasks.php", $langs->trans("Mytasks"));

  $menu->add(DOL_URL_ROOT."/projet/activity/", $langs->trans("Activity"));
  $menu->add_submenu(DOL_URL_ROOT."/projet/activity/myactivity.php", $langs->trans("MyActivity"));

  left_menu($menu->liste, $help_url);
}
?>
