<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require(DOL_DOCUMENT_ROOT."/telephonie/adsl/ligneadsl.class.php");
$user->getrights('telephonie');

function llxHeader($head = "", $title="") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/telephonie/adsl/index.php", "Liens xDSL");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/adsl/liste.php", "Liste");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/adsl/fiche.php?action=create", "Nouvelle liaison");

  $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Telephonie");

  if ($user->rights->telephonie->configurer)
    $menu->add(DOL_URL_ROOT."/telephonie/config/xdsl_product.php", "Configuration");

  left_menu($menu->liste);
}

?>
