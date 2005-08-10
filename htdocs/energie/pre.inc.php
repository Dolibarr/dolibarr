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
 *
 * $Id$
 * $Source$
 *
 */

/**
   \file       htdocs/energie/pre.inc.php
   \ingroup    energie
   \brief      Gestionnaire du menu energie
   \version    $Revision$
*/

require("../main.inc.php");
require("./EnergieCompteur.class.php");
require("./EnergieGroupe.class.php");

$langs->load("energy");

function llxHeader($langs, $head = "", $title="", $help_url='')
{

  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/energie/", $langs->trans("Energy"));

  $menu->add_submenu(DOL_URL_ROOT."/energie/compteur.php?action=create", $langs->trans("NewCounter"));
  $menu->add_submenu(DOL_URL_ROOT."/energie/groupe.php?action=create", $langs->trans("NewGroup"));

  $menu->add_submenu(DOL_URL_ROOT."/energie/graph.php", $langs->trans("Statistics"));

  
  left_menu($menu->liste, $help_url);
}
?>
