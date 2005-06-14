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
require("../../main.inc.php");

$user->getrights('telephonie');
require DOL_DOCUMENT_ROOT.'/telephonie/distributeurtel.class.php';

function llxHeader($head = "", $title="") {
  global $user, $conf, $db;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  if (TELEPHONIE_MODULE_ADSL == 1)
    $menu->add(DOL_URL_ROOT."/telephonie/adsl/", "ADSL");

  $menu->add(DOL_URL_ROOT."/telephonie/index.php", "Telephonie");

  if (TELEPHONIE_MODULE_SIMULATION == 1)
    {
      $menu->add(DOL_URL_ROOT."/telephonie/simulation/fiche.php", "Simulation");
      $menu->add_submenu(DOL_URL_ROOT."/telephonie/simulation/fiche.php?action=create", "Nouvelle");
    }



  $menu->add(DOL_URL_ROOT."/telephonie/client/index.php", "Clients");

  $menu->add(DOL_URL_ROOT."/telephonie/contrat/", "Contrats");

  $menu->add(DOL_URL_ROOT."/telephonie/ligne/index.php", "Lignes");

  $menu->add(DOL_URL_ROOT."/telephonie/ligne/commande/", "Commandes");

  $menu->add(DOL_URL_ROOT."/telephonie/facture/", "Factures");

  $menu->add(DOL_URL_ROOT."/telephonie/stats/", "Statistiques");
  $menu->add_submenu(DOL_URL_ROOT."/telephonie/stats/distributeurs/", "Distributeurs");
  $menu->add(DOL_URL_ROOT."/telephonie/tarifs/", "Tarifs");

  $menu->add(DOL_URL_ROOT."/telephonie/distributeurs/", "Distributeurs");



  $sql = "SELECT d.nom, d.rowid";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
  $sql .= " ORDER BY d.nom ASC";
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows();
      $i = 0;
      $total = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  
	  $menu->add_submenu(DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$row[1], $row[0]);

	  $i++;
	}
    }


  $menu->add(DOL_URL_ROOT."/telephonie/fournisseur/index.php", "Fournisseurs");

  $menu->add(DOL_URL_ROOT."/telephonie/service/", "Services");

  $menu->add(DOL_URL_ROOT."/telephonie/ca/", "Chiffre d'affaire");

  left_menu($menu->liste);
}

?>
