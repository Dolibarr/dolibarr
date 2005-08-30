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
require("../../main.inc.php");

$user->getrights('telephonie');

function llxHeader($head = "", $title="") {
  global $user;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Telephonie");

  if (TELEPHONIE_MODULE_SIMULATION == 1)
    {

      $menu->add(DOL_URL_ROOT."/telephonie/simulation/fiche.php", "Simulation");
      $menu->add_submenu(DOL_URL_ROOT."/telephonie/simulation/fiche.php?action=create", "Nouvelle");
    }


  $menu->add(DOL_URL_ROOT."/telephonie/client/index.php", "Clients");

  $menu->add(DOL_URL_ROOT."/telephonie/contrat/", "Contrats");

  $menu->add(DOL_URL_ROOT."/telephonie/ligne/index.php", "Lignes");

  if ($user->rights->telephonie->ligne_commander)    
    $menu->add(DOL_URL_ROOT."/telephonie/ligne/commande/", "Commandes");

  if ($user->rights->telephonie->stats->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/stats/", "Statistiques");

  $menu->add(DOL_URL_ROOT."/telephonie/facture/", "Factures");

  $menu->add(DOL_URL_ROOT."/telephonie/tarifs/", "Tarifs");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/tarifs/grilles.php", "Grilles");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/tarifs/index.php?type=vente", "Vente");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/tarifs/index.php?type=achat", "Achat");

  $menu->add_submenu(DOL_URL_ROOT."/telephonie/tarifs/prefix.php", "Prefix");

  if ($user->rights->telephonie->tarif->permission)
    $menu->add_submenu(DOL_URL_ROOT."/telephonie/tarifs/permissions.php", "Permissions");

  if ($user->rights->telephonie->fournisseur->lire)
    $menu->add(DOL_URL_ROOT."/telephonie/fournisseurs.php", "Fournisseurs");


  left_menu($menu->liste);
}

?>
