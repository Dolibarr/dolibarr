<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../main.inc.php");
require(DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
$user->getrights('telephonie');

function llxHeader($head = "", $title="") {
  global $user;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  if ($user->rights->telephonie->adsl->lire && TELEPHONIE_MODULE_ADSL == 1)
    $menu->add(DOL_URL_ROOT."/telephonie/adsl/", "ADSL");

  $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Telephonie");

  if (TELEPHONIE_MODULE_SIMULATION == 1)
    {
      $menu->add(DOL_URL_ROOT."/telephonie/simulation/fiche.php", "Simulation");
      $menu->add_submenu(DOL_URL_ROOT."/telephonie/simulation/fiche.php?action=create", "Nouvelle");
    }



  $menu->add(DOL_URL_ROOT."/telephonie/client/index.php", "Clients");
  if ($user->rights->telephonie->ligne->creer)
    $menu->add_submenu(DOL_URL_ROOT."/telephonie/client/new.php", "Nouveau client");

  $menu->add(DOL_URL_ROOT."/telephonie/contrat/", "Contrats");

  $menu->add(DOL_URL_ROOT."/telephonie/ligne/index.php", "Lignes");

  if ($user->rights->telephonie->ligne_commander)    
    $menu->add(DOL_URL_ROOT."/telephonie/ligne/commande/", "Commandes");

  if ($user->rights->telephonie->facture->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/facture/", "Factures");

  if ($user->rights->telephonie->stats->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/stats/", "Statistiques");

  $menu->add(DOL_URL_ROOT."/telephonie/tarifs/", "Tarifs");

  $menu->add(DOL_URL_ROOT."/telephonie/distributeurs/", "Distributeurs");

  if ($user->rights->telephonie->fournisseur->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/fournisseur/index.php", "Fournisseurs");

  if ($user->rights->telephonie->service->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/service/", "Services");

  if ($user->rights->telephonie->ca->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/ca/", "Chiffre d'affaire");

  if ($user->rights->telephonie->configurer)
    $menu->add(DOL_URL_ROOT."/telephonie/config/", "Configuration");

  left_menu($menu->liste);
}

?>
