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
 *	\file 		htdocs/projet/pre.inc.php
 *	\ingroup    projet
 *	\brief      Fichier de gestion du menu gauche du module projet
 *	\version    $Id$
 */

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("commercial");

/**
 * Enter description here...
 *
 * @param unknown_type $head
 * @param unknown_type $title
 * @param unknown_type $help_url
 */
function llxHeader($head = "", $title="", $help_url='')
{
	global $langs, $user;

	top_menu($head, $title);

	$menu = new Menu();

	$menu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects", $langs->trans("Projects"), 0, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/fiche.php?leftmenu=projects&action=create", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
	$menu->add_submenu(DOL_URL_ROOT."/projet/liste.php?leftmenu=projects", $langs->trans("List"), 1, $user->rights->projet->lire);

	$menu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects&mode=mine", $langs->trans("MyProjects"), 0, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/fiche.php?leftmenu=projects&action=create&mode=mine", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
	$menu->add_submenu(DOL_URL_ROOT."/projet/liste.php?leftmenu=projects&mode=mine", $langs->trans("List"), 1, $user->rights->projet->lire);

	$menu->add(DOL_URL_ROOT."/projet/activity/index.php", $langs->trans("Activities"), 0, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/tasks/fiche.php?action=create", $langs->trans("NewTask"), 1, $user->rights->projet->creer);
	$menu->add_submenu(DOL_URL_ROOT."/projet/tasks/index.php", $langs->trans("List"), 1, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/activity/list.php", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer);

	$menu->add(DOL_URL_ROOT."/projet/activity/index.php?mode=mine", $langs->trans("MyActivities"), 0, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/tasks/fiche.php?action=create&mode=mine", $langs->trans("NewTask"), 1, $user->rights->projet->creer);
	$menu->add_submenu(DOL_URL_ROOT."/projet/tasks/index.php?mode=mine", $langs->trans("List"), 1, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/activity/list.php?mode=mine", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer);


	left_menu($menu->liste, $help_url);
}
?>
