<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/** 
    \file       htdocs/contrat/pre.inc.php
    \ingroup    contrat
    \brief      Fichier de gestion du menu gauche de l'espace contrat
    \version    $Revision$
*/

require("../main.inc.php");

function llxHeader($head = "", $urlp = "")
{
  global $user, $conf, $langs;
  $langs->load("contracts");
  
  top_menu($head);
  
  $menu = new Menu();
  
  $menu->add(DOL_URL_ROOT."/contrat/index.php", $langs->trans("Contracts"));
  $menu->add_submenu(DOL_URL_ROOT."/societe.php", $langs->trans("NewContract"));
  $menu->add_submenu(DOL_URL_ROOT."/contrat/liste.php", $langs->trans("List"));
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php", $langs->trans("MenuServices"));
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=0", $langs->trans("MenuInactiveServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=4", $langs->trans("MenuRunningServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=4&filter=expired", $langs->trans("MenuExpiredServices"), 2 , true);
  $menu->add_submenu(DOL_URL_ROOT."/contrat/services.php?mode=5", $langs->trans("MenuClosedServices"), 2 , true);
  
  left_menu($menu->liste);
}
?>
