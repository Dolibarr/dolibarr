<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file htdocs/admin/pre.inc.php
		\brief      Fichier gestionnaire du menu de gauche de l'espace configuration
		\version    $Revision$
*/

require("../main.inc.php");

$langs->load("admin");


function llxHeader($head = "", $title="", $help_url='')
{
  global $user, $langs;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();
  $langs->load("admin");
  
  $menu->add(DOL_URL_ROOT."/admin/index.php", $langs->trans("GlobalSetup"));

  $menu->add(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));

  $menu->add(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));

  $menu->add(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));

  $menu->add(DOL_URL_ROOT."/admin/perms.php", $langs->trans("Rights"));

  $menu->add(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));

  $menu->add(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));

  $menu->add("system/", $langs->trans("System"));

  left_menu($menu->liste, $help_url);
}

?>
