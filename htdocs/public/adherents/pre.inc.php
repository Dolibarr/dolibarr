<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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

function llxHeader($head = "")
{
  global $user, $conf, $langs;

  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/public/adherents/","Non adherent");
  $menu->add_submenu("new.php","Inscription");
  $menu->add(DOL_URL_ROOT."/public/adherents/","Adherents");
  $menu->add_submenu("priv_edit.php","Edition de sa fiche");
  $menu->add_submenu("priv_liste.php","Liste des adherents");

  left_menu($menu->liste);
}


function llxHeaderVierge($head = "")
{
	global $user, $conf, $langs;

	top_htmlhead('');
    print '<body>';
	
}

?>
