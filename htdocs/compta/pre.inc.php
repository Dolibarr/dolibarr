<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
require("../main.inc.php");

function llxHeader($head = "", $title="", $help_url='')
{
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head, $title);

  $menu = new Menu();

  // Les recettes

  $menu->add(DOL_URL_ROOT."/compta/clients.php", "Clients");

  if ($user->comm > 0 && $conf->commercial->enabled && $conf->propal->enabled) 
    {
      $menu->add(DOL_URL_ROOT."/compta/propal.php","Prop. commerciales");
    }

  if ($conf->contrat->enabled)
    {
      $menu->add(DOL_URL_ROOT."/contrat/","Contrats");
    }

  if ($conf->don->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/dons/","Dons");
    }

  if ($conf->facture->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/facture.php","Factures");
      $menu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php","Paiements");
      if (! defined(FACTURE_DISABLE_RECUR) || ! FACTURE_DISABLE_RECUR) {
        $menu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php","Récurrentes");
      }
      $menu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/","Statistiques");
    }
   
    
  // Les dépenses

  $menu->add(DOL_URL_ROOT."/fourn/index.php", "Fournisseurs");

  if ($user->societe_id == 0)
    {
      $menu->add(DOL_URL_ROOT."/compta/deplacement/", "Déplacement");
    }

  if ($conf->compta->enabled && $conf->compta->tva && $user->societe_id == 0)
    {
      $menu->add(DOL_URL_ROOT."/compta/tva/index.php","TVA");
    }
  if ($conf->compta->enabled)
    {
    $menu->add(DOL_URL_ROOT."/compta/charges/index.php","Charges");
    }


  // Vision des recettes-dépenses
  if ($conf->banque->enabled && $user->rights->banque->lire)
    { 
      $menu->add(DOL_URL_ROOT."/compta/bank/","Banque");
    }
  
  if ($conf->caisse->enabled)
    {
      $menu->add(DOL_URL_ROOT."/compta/caisse/index.php","Caisses");
    }

  $menu->add(DOL_URL_ROOT."/compta/stats/","CA / Résultats");

  if (! $user->compta) 
    {
      $menu->clear();
      $menu->add(DOL_URL_ROOT."/","Accueil");      
    }

  left_menu($menu->liste, $help_url);
}

?>
