<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


function llxHeader($head = "", $title="", $soc="") {
  global $user, $conf, $langs;
  $langs->load("bills");
  
  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/comm/fiche.php?socid=".$soc->id, $soc->nom);

  $menu->add(DOL_URL_ROOT."/dossier/client/fiche.php?id=".$soc->id, $langs->trans("Bills"));

  if ($soc) {
      foreach($soc->factures as $key=>$value)
        {
          $menu->add_submenu(DOL_URL_ROOT."/dossier/client/fiche.php?id=".$soc->id."&amp;facid=".$value[0], $value[1]);
        }
  }

  left_menu($menu->liste);
}

?>
