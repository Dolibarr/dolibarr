<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
require("../main.inc.php3");

function llxHeader($head = "") {
  global $user, $conf;


  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/compta/clients.php3", "Clients");

  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/","Dons");
    }

  $menu->add(DOL_URL_ROOT."/compta/facture.php3","Factures");
  $menu->add_submenu("paiement.php3","Paiements");

  if ($user->comm > 0 && $conf->commercial && MAIN_MODULE_PROPALE) 
    {
      $menu->add(DOL_URL_ROOT."/compta/propal.php3","Propales");
    }

  $menu->add(DOL_URL_ROOT."/contrat/","Contrats");

  /*
   * Sécurité accés client
   */
  if ($user->societe_id == 0) 
    {

      $menu->add("charges/index.php","Charges");
      $menu->add_submenu("sociales/","Prest. Sociales");
    }
  $menu->add("stats/","Chiffre d'affaire");

  if ($conf->compta->tva && $user->societe_id == 0)
    {
      $menu->add("tva/index.php","TVA");
    }

  $menu->add(DOL_URL_ROOT."/compta/caisse/index.php","Caisse");

  if ($user->societe_id == 0) 
    {
      $menu->add("resultat/","Résultats");

      $menu->add("bank/index.php","Banque");
    }


  $menu->add(DOL_URL_ROOT."/fourn/index.php", "Fournisseurs");

  /*
   *  $menu->add("ligne.php3","Compta");
   *  $menu->add_submenu("ligne.php3","Lignes");
   *  $menu->add_submenu("config.php3","Configuration");
   */

  if ($user->compta > 0) 
    {

    } 
  else 
    {
      $menu->clear();
      $menu->add(DOL_URL_ROOT."/","Accueil");      
    }

  left_menu($menu->liste);

}

?>
