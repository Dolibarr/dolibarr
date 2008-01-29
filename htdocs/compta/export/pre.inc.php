<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
  \file       htdocs/compta/export/pre.inc.php
  \ingroup    compta
  \brief      Fichier gestionnaire du menu export compta
*/

require("../../main.inc.php");

function llxHeader($head = "", $title="", $help_url='')
{
  global $user, $conf, $langs;

  top_menu($head, $title);

  $menu = new Menu();

  // Les recettes

  $langs->load("bills");

  $menu->add(DOL_URL_ROOT."/compta/clients.php", $langs->trans("Customers"));

  if (!$user->rights->compta->ventilation->creer)
    {
      $menu->add(DOL_URL_ROOT."/compta/ventilation/",$langs->trans("Ventilation"));
    }
      
  $menu->add(DOL_URL_ROOT."/compta/facture.php",$langs->trans("Bills"));

  $menu->add(DOL_URL_ROOT."/compta/ventilation/",$langs->trans("Ventilation"));

  $menu->add(DOL_URL_ROOT."/compta/export/",$langs->trans("Export"));

  $menu->add_submenu(DOL_URL_ROOT."/compta/export/index.php?action=export",$langs->trans("New"));

  $menu->add_submenu(DOL_URL_ROOT."/compta/export/liste.php",$langs->trans("List"));

  left_menu($menu->liste, $help_url);
}

?>
