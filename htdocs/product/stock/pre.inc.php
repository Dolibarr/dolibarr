<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/*!
	    \file       htdocs/product/stock/pre.inc.php
        \ingroup    stock
		\brief      Fichier gestionnaire du menu gauche de stocks
		\version    $Revision$
*/

require("../../main.inc.php");
require("./entrepot.class.php");

function llxHeader($head = "", $urlp = "", $title="")
{
  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/product/stock/", "Stock");

  $menu->add_submenu(DOL_URL_ROOT."/product/stock/fiche.php?action=create", "Nouvel entrepôt");

  $menu->add(DOL_URL_ROOT."/product/stock/mouvement.php", "Mouvements");

  left_menu($menu->liste);

}
?>
