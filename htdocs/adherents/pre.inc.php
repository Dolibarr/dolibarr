<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*! \file htdocs/adherents/pre.inc.php
    \ingroup    adherent
		\brief      Fichier de gestion du menu gauche du module adherent
		\version    $Revision$
*/

require("../main.inc.php");

function llxHeader($head = "") {
  global $user, $conf, $langs;


  top_menu($head);

  $menu = new Menu();


  $menu->add("index.php",$langs->trans("Members"));
  $menu->add_submenu("fiche.php?action=create",$langs->trans("NewMember"));
  $menu->add_submenu("liste.php?statut=-1","Adhésions à valider");
  $menu->add_submenu("liste.php?statut=1","Adhérents à ce jour");
  $menu->add_submenu("liste.php?statut=0","Adhésions résiliées");

  $menu->add(DOL_URL_ROOT."/public/adherents/","Espace adherents public");

  $menu->add("index.php","Export");
  $menu->add_submenu("htpasswd.php","Format htpasswd");
  $menu->add_submenu("cartes/carte.php","Cartes d'adhérents");
  $menu->add_submenu("cartes/etiquette.php","Etiquettes d'adhérents");

  $langs->load("compta");
  $menu->add("index.php",$langs->trans("Accountancy"));
  $menu->add_submenu("cotisations.php","Cotisations");
  $menu->add_submenu(DOL_URL_ROOT."/compta/bank/",$langs->trans("Bank"));

  $menu->add("index.php",$langs->trans("Setup"));
  $menu->add_submenu("type.php","Type d'adhérent");
  $menu->add_submenu("options.php","Champs optionnels");

  if ($user->admin) {
    $menu->add_submenu(DOL_URL_ROOT."/admin/adherent.php",$langs->trans("Module"));
  }
  
  left_menu($menu->liste);

}

?>
