<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/includes/menus/barre_left/eldy.php
		\brief      Gestionnaire par défaut du menu du gauche
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
        \remarks    définir la liste des entrées menu à faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est définir dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les définitions de menu des fichiers pre.inc.php
*/

session_start();

$user->getrights("");
$newmenu = new Menu();
$overwritemenufor=array('home','commercial','accountancy','products','supplier','tools');


/**
 * On récupère mainmenu qui définit le menu à afficher
 */
if (isset($_GET["mainmenu"])) {
    // On sauve en session le menu principal choisi
    $mainmenu=$_GET["mainmenu"];
    $_SESSION["mainmenu"]=$mainmenu;
} else {
    // On va le chercher en session si non défini par le lien    
    $mainmenu=$_SESSION["mainmenu"];
}


/**
 * On definit newmenu en fonction de mainmenu
 */
if ($mainmenu) {

  
    /*
     * Menu HOME
     */
    if ($mainmenu == 'home') {
        $newmenu->add(DOL_URL_ROOT."/user/index.php", $langs->trans("Users"));

        if($user->admin)
        {
          $langs->load("users");
          $langs->load("admin");
          $newmenu->add_submenu(DOL_URL_ROOT."/user/fiche.php?action=create", $langs->trans("NewUser"));
          $newmenu->add(DOL_URL_ROOT."/admin/index.php?", $langs->trans("Setup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/index.php", $langs->trans("GlobalSetup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/perms.php", $langs->trans("DefaultRights"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));
          $newmenu->add(DOL_URL_ROOT."/admin/system/?mainmenu=", $langs->trans("System"));
        }
    }

    /*
     * Menu COMMERCIAL
     */
    if ($mainmenu == 'commercial') {
        $langs->load("companies");

        // Clients
        $newmenu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));
        if ($user->rights->societe->creer)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=c", $langs->trans("MenuNewCustomer"));
        }
        $newmenu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=c", $langs->trans("Contacts"));
        
        // Prospects
        $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php", $langs->trans("Prospects"));
        
        if ($user->rights->societe->creer)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=p", $langs->trans("MenuNewProspect"));
        }
        
        $newmenu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=p", $langs->trans("Contacts"));
        
        
        
        $newmenu->add(DOL_URL_ROOT."/comm/action/index.php", $langs->trans("Actions"));
        $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?time=today", $langs->trans("Today"));
        $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/rapport/index.php", $langs->trans("Reporting"));

        // Propal
        if ($conf->propal->enabled && $user->rights->propale->lire)
        {
          $langs->load("propal");
          $newmenu->add(DOL_URL_ROOT."/comm/propal.php", $langs->trans("Prop"));
          $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=0", $langs->trans("Drafts"));
          $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=1", $langs->trans("Opened"));
          $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal/stats/", $langs->trans("Statistics"));
        }
        
        if ($conf->contrat->enabled)
        {
          $langs->load("contracts");
          $newmenu->add(DOL_URL_ROOT."/contrat/index.php", $langs->trans("Contracts"));
          $newmenu->add_submenu(DOL_URL_ROOT."/contrat/liste.php", "Liste");
          $newmenu->add_submenu(DOL_URL_ROOT."/contrat/enservice.php", "En service");
        }
        
        if ($conf->commande->enabled ) 
        {
          $langs->load("orders");
          $newmenu->add(DOL_URL_ROOT."/commande/index.php", $langs->trans("Orders"));
          $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php", $langs->trans("List"));
            if ($conf->expedition->enabled) {
              $newmenu->add(DOL_URL_ROOT."/expedition/", "Expeditions");
            }
          $newmenu->add_submenu(DOL_URL_ROOT."/commande/stats/", $langs->trans("Statistics"));
        }
        
        if ($conf->fichinter->enabled ) 
        {
          $newmenu->add(DOL_URL_ROOT."/fichinter/index.php", "Fiches d'intervention");
        }
        
        if ($conf->projet->enabled ) 
        {
          $langs->load("projects");
          $newmenu->add(DOL_URL_ROOT."/projet/index.php", $langs->trans("Projects"));
        }
        

    }


    /*
     * Menu COMPTA
     */
    if ($mainmenu == 'accountancy') {
        $langs->load("companies");

        // Fournisseurs
        if ($conf->fournisseur->enabled) 
        {
            $langs->load("suppliers");
            $newmenu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
        
            // Sécurité accés client
            if ($user->societe_id == 0) 
            {
              $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&type=f",$langs->trans("NewSupplier"));
            }
        }
        
        if ($conf->societe->enabled)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/fourn/contact.php",$langs->trans("Contacts"));
        }
        
        if ($conf->facture->enabled)
        {
          $langs->load("bills");
          $newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php", $langs->trans("BillsSuppliers"));
          
          if ($user->societe_id == 0) 
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"));
        }
          
          $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"));
        }


        // Clients
        $newmenu->add(DOL_URL_ROOT."/compta/clients.php", $langs->trans("Customers"));
        if ($user->rights->societe->creer)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=c", $langs->trans("MenuNewCustomer"));
        }
        $newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?type=c", $langs->trans("Contacts"));
        
        if ($conf->facture->enabled)
        {
          $langs->load("bills");
          $newmenu->add(DOL_URL_ROOT."/compta/facture.php",$langs->trans("BillsCustomers"));
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/clients.php?action=facturer",$langs->trans("NewBill"));
          if (! defined(FACTURE_DISABLE_RECUR) || ! FACTURE_DISABLE_RECUR)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php","Récurrentes");
        }
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php",$langs->trans("Payments"));
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/", $langs->trans("Statistics"));
        }
        
        if ($conf->don->enabled)
        {
          $langs->load("donations");
          $newmenu->add(DOL_URL_ROOT."/compta/dons/",$langs->trans("Donations"));
        }
        
        if ($conf->deplacement->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/compta/deplacement/", "Déplacement");
        }
        
        if ($conf->compta->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/compta/charges/index.php","Charges");
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/sociales/index.php","Prest. Sociales");
        }
        
        if ($conf->compta->enabled && $conf->compta->tva && $user->societe_id == 0)
        {
          $newmenu->add(DOL_URL_ROOT."/compta/tva/index.php",$langs->trans("VAT"));
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/reglement.php","Réglements");
          $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/fiche.php?action=create","Nouveau réglement");
        }
        
        $newmenu->add(DOL_URL_ROOT."/compta/ventilation/",$langs->trans("Ventilations"));
        if ($user->rights->compta->ventilation->param) {
            $newmenu->add_submenu(DOL_URL_ROOT."/compta/param/",$langs->trans("Param"));
        }

        // Bank-Caisse
        if ($conf->banque->enabled && $user->rights->banque->lire)
        { 
          $langs->load("banks");
          $newmenu->add(DOL_URL_ROOT."/compta/bank/index.php?mainmenu=banque",$langs->trans("Bank"));
        }
        
        if ($conf->caisse->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/compta/caisse/index.php?mainmenu=cash",$langs->trans("Caisse"));
        }
        
        // Bilan, résultats
        $newmenu->add(DOL_URL_ROOT."/compta/stats/index.php?mainmenu=ca","CA / Résultats");

        
        if ($conf->prelevement->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/compta/prelevement/","Bon prélèv.");
        }

    }


    /*
     * Menu PRODUITS-SERVICES
     */
    if ($mainmenu == 'products') {

      if ($conf->produit->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/product/index.php?type=0", $langs->trans("Products"));
          $newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=0", $langs->trans("List"));
    
          if ($user->societe_id == 0 && $user->rights->produit->creer)
    	{
    	  $newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=0", $langs->trans("NewProduct"));
    	}
        }
      
      if ($conf->service->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/product/index.php?type=1", $langs->trans("Services"));
          $newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=1", $langs->trans("List"));
          if ($user->societe_id == 0  && $user->rights->produit->creer)
    	{
    	  $newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=1", $langs->trans("NewService"));
    	}
        }
    
      $newmenu->add(DOL_URL_ROOT."/product/stats/", $langs->trans("Statistics"));
      if ($conf->propal->enabled)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/product/popuprop.php", $langs->trans("Popularity"));
        }
      
      if ($conf->stock->enabled)
        {
          $newmenu->add(DOL_URL_ROOT."/product/stock/", $langs->trans("Stock"));
          $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/fiche.php?action=create", "Nouvel entrepôt");
          $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/mouvement.php", "Mouvements");
        }
    }


    /*
     * Menu FOURNISSEURS
     */
    if ($mainmenu == 'supplier') {

      if ($conf->fournisseur->enabled) 
        {
            $newmenu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
    
            // Sécurité accés client
            if ($user->societe_id == 0) 
            {
              $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&type=f",$langs->trans("NewSupplier"));
            }
        }
    
      if ($conf->societe->enabled)
        {
          $newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?type=f",$langs->trans("Contacts"));
        }
      
      if ($conf->facture->enabled)
        {
          $langs->load("bills");
          $newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php", $langs->trans("Bills"));
          
          if ($user->societe_id == 0) 
    	{
    	  $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"));
    	}
          
          $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"));
        }
      
      if ($conf->commande->enabled)
      {
          $langs->load("orders");
          $newmenu->add(DOL_URL_ROOT."/fourn/commande/",$langs->trans("Orders"));
          $newmenu->add_submenu(DOL_URL_ROOT."/fourn/commande/liste.php", $langs->trans("List"));
      }
    
    }


    /*
     * Menu OUTILS
     */
    if ($mainmenu == 'tools') {
        if($user->admin)
        {
          $langs->load("admin");
          $newmenu->add(DOL_URL_ROOT."/comm/mailing.php?mainmenu=outils", $langs->trans("Mailings"));

        }
    }


    // Pour les menu du haut qui ne serait pas gérés
    if ($mainmenu && ! in_array($mainmenu,$overwritemenufor)) { $mainmenu=""; }

}

/**
 *  Si on est sur un cas géré de surcharge du menu, on ecrase celui par defaut
 */
if ($mainmenu) {
    $menu=$newmenu->liste;
}

?>
