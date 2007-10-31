<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require(DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
$user->getrights('telephonie');

function llxHeader($head = "", $title="") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  if ($user->rights->telephonie->adsl->lire && TELEPHONIE_MODULE_ADSL == 1)
    $menu->add(DOL_URL_ROOT."/telephonie/adsl/", "Liens xDSL");

  $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Telephonie");

  if (TELEPHONIE_MODULE_SIMULATION == 1)
    $menu->add(DOL_URL_ROOT."/telephonie/simulation/fiche.php", "Simulation");

  $menu->add(DOL_URL_ROOT."/telephonie/tarifs/", "Tarifs");

  $menu->add(DOL_URL_ROOT."/telephonie/ligne/index.php", "Lignes");

  $menu->add(DOL_URL_ROOT."/telephonie/ligne/commande/", "Commande");

  $menu->add(DOL_URL_ROOT."/telephonie/facture/liste.php", "Factures");

  $menu->add(DOL_URL_ROOT."/telephonie/fournisseur/", "Fournisseurs");

  $menu->add(DOL_URL_ROOT."/telephonie/config/", "Configuration");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/config/compta.php", "Compta");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/config/mail.php", "Mails");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/config/concurrents/liste.php", "Concurrents");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/config/perms.php", "Permissions");

  if ($user->rights->telephonie->adsl->lire && TELEPHONIE_MODULE_ADSL == 1)
    $menu->add_submenu(DOL_URL_ROOT."/telephonie/config/xdsl.php", "Liens xDSL");

  left_menu($menu->liste);
}

?>
