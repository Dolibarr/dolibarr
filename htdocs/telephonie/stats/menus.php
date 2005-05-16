<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


$menu->add(DOL_URL_ROOT."/telephonie/index.php", "Telephonie");

$menu->add(DOL_URL_ROOT."/telephonie/client/index.php", "Clients");

$menu->add(DOL_URL_ROOT."/telephonie/contrat/index.php", "Contrats");

$menu->add(DOL_URL_ROOT."/telephonie/ligne/index.php", "Lignes");

$menu->add(DOL_URL_ROOT."/telephonie/ligne/commande/", "Commandes");

$menu->add(DOL_URL_ROOT."/telephonie/stats/", "Statistiques");

$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/clients/", "Clients");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/contrats/", "Contrats");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/lignes/", "Lignes");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/commerciaux/", "Commerciaux");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/distributeurs/", "Distributeurs");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/communications/", "Communications");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/destinations/", "Destinations");
$menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/factures/", "Factures");

$menu->add(DOL_URL_ROOT."/telephonie/facture/", "Factures");

$menu->add(DOL_URL_ROOT."/telephonie/tarifs/", "Tarifs");

$menu->add(DOL_URL_ROOT."/telephonie/fournisseur/", "Fournisseurs");

$menu->add(DOL_URL_ROOT."/telephonie/ca/", "Chiffre d'affaire");



?>
