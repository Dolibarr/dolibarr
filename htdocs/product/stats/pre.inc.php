<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require(DOL_DOCUMENT_ROOT."/bargraph.class.php");

function llxHeader($head = "", $urlp = "")
{
  global $user, $conf;

  top_menu($head);

  $menu = new Menu();

  if ($conf->produit->enabled)
    {
	  $menu->add(DOL_URL_ROOT."/product/index.php?type=0", "Produits");
  	  $menu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=0","Liste");
  	  $menu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=0","Nouveau produit");
	}
	
  if ($conf->service->enabled)
    {
      $menu->add(DOL_URL_ROOT."/product/index.php?type=1", "Services");
      $menu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=1","Liste");
      $menu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=1","Nouveau service");
    }

  if ($conf->boutique->enabled)
    {

      $menu->add(DOL_URL_ROOT."/product/osc-liste.php", "Osc");
      $menu->add_submenu(DOL_URL_ROOT."/product/osc-liste.php?reqstock=epuise", "Produits Epuisés");


      $menu->add(DOL_URL_ROOT."/product/osc-reviews.php", "Critiques");

      $menu->add_submenu(DOL_URL_ROOT."/product/osc-productsbyreviews.php", "Meilleurs produits");

      $menu->add(DOL_URL_ROOT."/product/album/", "Albums");
      $menu->add(DOL_URL_ROOT."/product/groupart/", "Groupes/Artistes");
      
      $menu->add(DOL_URL_ROOT."/product/categorie/", "Catégories");
    }      
    
  $menu->add(DOL_URL_ROOT."/fourn/index.php", "Fournisseurs");

  if ($conf->commande->enabled)
    {
      $menu->add(DOL_URL_ROOT."/commande/", "Commandes");
	}
	
  $menu->add(DOL_URL_ROOT."/product/stats/", "Statistiques");
  if ($conf->propal->enabled) {
    $menu->add_submenu(DOL_URL_ROOT."/product/popuprop.php", "Popularité");
  }
  
  if ($conf->stock->enabled)
    {
      $menu->add(DOL_URL_ROOT."/product/stock/", "Stock");
   	}

  left_menu($menu->liste);
  /*
   *
   *
   */

}
?>
