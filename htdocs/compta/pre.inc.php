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
require("../main.inc.php");

function llxHeader($head = "", $title="") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/compta/clients.php", "Clients");

  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/","Dons");
    }

  $menu->add(DOL_URL_ROOT."/compta/facture.php","Factures");
  $menu->add_submenu("paiement.php","Paiements");

  if ($user->comm > 0 && $conf->commercial && MAIN_MODULE_PROPALE) 
    {
      $menu->add(DOL_URL_ROOT."/compta/propal.php","Propales");
    }

  $menu->add(DOL_URL_ROOT."/contrat/","Contrats");


  $menu->add("stats/","Chiffre d'affaire");

  if ($conf->compta->tva && $user->societe_id == 0)
    {
      $menu->add("tva/index.php","TVA");
    }

  $menu->add(DOL_URL_ROOT."/compta/caisse/index.php","Caisse");

  $menu->add(DOL_URL_ROOT."/fourn/index.php", "Fournisseurs");

  /*
   *  $menu->add("ligne.php","Compta");
   *  $menu->add_submenu("ligne.php","Lignes");
   *  $menu->add_submenu("config.php","Configuration");
   */

  if ($user->compta > 0) 
    {

    } 
  else 
    {
      $menu->clear();
      $menu->add(DOL_URL_ROOT."/","Accueil");      
    }

  $menu->add(DOL_URL_ROOT."/compta/deplacement/", "Déplacement");

  left_menu($menu->liste);

}

?>
