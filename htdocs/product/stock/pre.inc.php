<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
 *  \file       htdocs/product/stock/pre.inc.php
 *  \ingroup    stock
 *  \brief      Fichier gestionnaire du menu gauche de stocks
 *  \version    $Id$
 */

require("../../main.inc.php");
require("./entrepot.class.php");

function llxHeader($head="", $title="", $help_url="")
{
  global $langs,$conf,$user;
  
  top_menu($head, $title);
  
  $langs->load("stocks");
  
  $menu = new Menu();
  
  $menu->add(DOL_URL_ROOT."/product/stock/", $langs->trans("Stock"));
  
  $menu->add_submenu(DOL_URL_ROOT."/product/stock/fiche.php?action=create", $langs->trans("MenuNewWarehouse"));
  $menu->add_submenu(DOL_URL_ROOT."/product/stock/liste.php", $langs->trans("List"));
  $menu->add_submenu(DOL_URL_ROOT."/product/stock/valo.php", $langs->trans("EnhancedValue"));
  $menu->add_submenu(DOL_URL_ROOT."/product/reassort.php?type=0", $langs->trans("Restock"));
  
  $menu->add(DOL_URL_ROOT."/product/stock/mouvement.php", $langs->trans("Movements"));
  left_menu($menu->liste, $help_url);
}
?>
