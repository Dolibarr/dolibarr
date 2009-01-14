<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  \file 		htdocs/projet/activity/pre.inc.php
  \ingroup    	projet
  \brief      	Fichier de gestion du menu gauche du module projet
  \version    	$Id$
*/

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/task.class.php");

$langs->load("projects");
$langs->load("companies");

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

	$menu->add(DOL_URL_ROOT."/projet/tasks/index.php", $langs->trans("Tasks"), 0, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/tasks/fiche.php?action=create", $langs->trans("NewTask"), 1, $user->rights->projet->creer);
	$menu->add_submenu(DOL_URL_ROOT."/projet/tasks/index.php?mode=mine", $langs->trans("MyTasks"), 1, $user->rights->projet->lire);

	$menu->add(DOL_URL_ROOT."/projet/activity/index.php", $langs->trans("TimeSpent"), 0, $user->rights->projet->lire);
	$menu->add_submenu(DOL_URL_ROOT."/projet/activity/list.php", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer);
	$menu->add_submenu(DOL_URL_ROOT."/projet/activity/index.php?mode=mine", $langs->trans("MyTimeSpent"), 1, $user->rights->projet->lire);

  left_menu($menu->liste, $help_url);
}

?>
