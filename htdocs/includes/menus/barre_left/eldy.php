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

/**	    \file       htdocs/includes/menus/barre_left/eldy.php
		\brief      Gestionnaire du menu du gauche Eldy
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
        \remarks    définir la liste des entrées menu à faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est défini dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les définitions de menu des fichiers pre.inc.php
*/


/**     \class      MenuLeft
	    \brief      Classe permettant la gestion du menu du gauche Eldy
*/

class MenuLeft {

    var $require_top=array("eldy");     // Si doit etre en phase avec un gestionnaire de menu du haut particulier

    
    /**
     *    \brief      Constructeur
     *    \param      db            Handler d'accès base de donnée
     *    \param      menu_array    Tableau des entrée de menu défini dans les fichier pre.inc.php
     */
    function MenuLeft($db,&$menu_array)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
    }
  
    
    /**
     *    \brief      Affiche le menu
     */
    function showmenu()
    {
        global $user, $conf, $langs;
        
        session_start();
        
        $user->getrights("");
        
        $newmenu = new Menu();
        $overwritemenufor=array('home','commercial','accountancy','products','suppliers','tools');
        
        // On récupère mainmenu et leftmenu qui définissent le menu à afficher
        if (isset($_GET["mainmenu"])) {
            // On sauve en session le menu principal choisi
            $mainmenu=$_GET["mainmenu"];
            $_SESSION["mainmenu"]=$mainmenu;
            $_SESSION["leftmenuopened"]="";
        } else {
            // On va le chercher en session si non défini par le lien    
            $mainmenu=$_SESSION["mainmenu"];
        }
        if (isset($_GET["leftmenu"])) {
            // On sauve en session le menu principal choisi
            $leftmenu=$_GET["leftmenu"];
            $_SESSION["leftmenu"]=$leftmenu;
            if ($_SESSION["leftmenuopened"]==$leftmenu) {
                //$leftmenu="";
                $_SESSION["leftmenuopened"]="";
            }
            else {
                $_SESSION["leftmenuopened"]=$leftmenu;
            }
        } else {
            // On va le chercher en session si non défini par le lien    
            $leftmenu=$_SESSION["leftmenu"];
        }
        
        
        /**
         * On definit newmenu en fonction de mainmenu et leftmenu
         * ------------------------------------------------------
         */
        if ($mainmenu) {
        
          
            /*
             * Menu HOME
             */
            if ($mainmenu == 'home') {
                $langs->load("users");
                $newmenu->add(DOL_URL_ROOT."/user/home.php?leftmenu=users", $langs->trans("MenuUsersAndGroups"));
        
                if($user->admin)
                {
                  $langs->load("admin");
                  if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/index.php", $langs->trans("Users"));
                  if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/fiche.php?action=create", $langs->trans("NewUser"),2);
                  if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/group/index.php", $langs->trans("Groups"));
                  if ($leftmenu=="users") $newmenu->add_submenu(DOL_URL_ROOT."/user/group/fiche.php?action=create", $langs->trans("NewGroup"),2);
                  
                  $newmenu->add(DOL_URL_ROOT."/admin/index.php?leftmenu=setup", $langs->trans("Setup"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/index.php", $langs->trans("GlobalSetup"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/perms.php", $langs->trans("DefaultRights"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));
                  
                  $newmenu->add(DOL_URL_ROOT."/admin/system/index.php?leftmenu=system", $langs->trans("System"));
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/about.php", "Dolibarr");
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/constall.php", $langs->trans("AllParameters"),2);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/os.php", $langs->trans("OS"));
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/web.php", $langs->trans("WebServer"));
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/phpinfo.php", $langs->trans("Php"));
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/phpinfo.php?what=conf", $langs->trans("PhpConf"),2);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/phpinfo.php?what=env", $langs->trans("PhpEnv"),2);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/phpinfo.php?what=modules", $langs->trans("PhpModules"),2);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/database.php", $langs->trans("Database"));
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/database-tables.php", $langs->trans("Tables"),2);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/database-tables-contraintes.php", $langs->trans("Constraints"),2);
                }
            }
        
            /*
             * Menu SOCIETES
             */
            if ($mainmenu == 'companies') {

            }
          
            /*
             * Menu COMMERCIAL
             */
            if ($mainmenu == 'commercial') {
                $langs->load("companies");
        
                // Prospects
                $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?leftmenu=prospects", $langs->trans("Prospects"), 0, $user->rights->societe->lire);
                
                $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=prospects&action=create&amp;type=p", $langs->trans("MenuNewProspect"), 1, $user->rights->societe->creer);
                $newmenu->add_submenu(DOL_URL_ROOT."/comm/contact.php?leftmenu=prospects&type=p", $langs->trans("Contacts"), 1, $user->rights->societe->lire);

                // Clients
                $newmenu->add(DOL_URL_ROOT."/comm/clients.php?leftmenu=customers", $langs->trans("Customers"), 0, $user->rights->societe->lire);
                $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=prospects&action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 1, $user->rights->societe->creer);
                $newmenu->add_submenu(DOL_URL_ROOT."/comm/contact.php?leftmenu=prospects&type=c", $langs->trans("Contacts"), 1, $user->rights->societe->lire);
                
                // Actions
                $newmenu->add(DOL_URL_ROOT."/comm/action/index.php?leftmenu=actions", $langs->trans("Actions"), 0);
                if ($leftmenu=="actions") $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?time=today", $langs->trans("Today"), 1);
                if ($leftmenu=="actions") $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/rapport/index.php", $langs->trans("Reporting"), 1);
        
                // Propal
                if ($conf->propal->enabled)
                {
                  $langs->load("propal");
                  $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals", $langs->trans("Prop"), 0 ,$user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=0", $langs->trans("PropalsDraft"), 1, $user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=1", $langs->trans("PropalsOpened"), 1, $user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal/stats/", $langs->trans("Statistics"), 1, $user->rights->propale->lire);
                }
                
                if ($conf->contrat->enabled)
                {
                  $langs->load("contracts");
                  $newmenu->add(DOL_URL_ROOT."/contrat/index.php?leftmenu=contracts", $langs->trans("Contracts"), 0 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/liste.php", $langs->trans("List"), 1 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/enservice.php", "En service", 1 ,$user->rights->contrat->lire);
                }
                
                if ($conf->commande->enabled ) 
                {
                  $langs->load("orders");
                  $newmenu->add(DOL_URL_ROOT."/commande/index.php?leftmenu=orders", $langs->trans("Orders"), 0 ,$user->rights->commande->lire);
                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php", $langs->trans("List"), 1 ,$user->rights->commande->lire);
                  if ($conf->expedition->enabled) {
                      if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/expedition/", $langs->trans("Sendings"));
                  }
                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/stats/", $langs->trans("Statistics"), 1 ,$user->rights->commande->lire);
                }
                
                if ($conf->fichinter->enabled ) 
                {
                  $langs->load("interventions");
                  $newmenu->add(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("Interventions"));
                }
                
            }
        
        
            /*
             * Menu COMPTA
             */
            if ($mainmenu == 'accountancy') {
                $langs->load("companies");
        
                // Fournisseurs
                if ($conf->societe->enabled && $conf->fournisseur->enabled) 
                {
                    $langs->load("suppliers");
                    $newmenu->add(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"));
                
                    // Sécurité accés client
                    if ($user->societe_id == 0) 
                    {
                      $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=suppliers&action=create&type=f",$langs->trans("NewSupplier"),1);
                    }
                    if ($conf->societe->enabled)
                    {
                      $newmenu->add_submenu(DOL_URL_ROOT."/fourn/contact.php?leftmenu=suppliers",$langs->trans("Contacts"));
                    }

                }
                
                if ($conf->facture->enabled)
                {
                    $langs->load("bills");
                    $newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php?leftmenu=suppliers_bills", $langs->trans("BillsSuppliers"),1,$user->rights->facture->lire);
                    if ($user->societe_id == 0) 
                    {
                      if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"),2,$user->rights->facture->creer);
                    }
                    if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"),2,$user->rights->facture->lire);
                }
        
        
                // Clients
                if ($conf->societe->enabled) { 
                    $newmenu->add(DOL_URL_ROOT."/compta/clients.php?leftmenu=customers", $langs->trans("Customers"),0,$user->rights->societe->lire);
                    if ($user->societe_id == 0) 
                    {
                        $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=customers&action=create&amp;type=c", $langs->trans("MenuNewCustomer"),1,$user->rights->societe->creer);
                    }
                    $newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&type=c", $langs->trans("Contacts"),1,$user->rights->societe->lire);
                }
                                
                if ($conf->facture->enabled)
                {
                    $langs->load("customers_bills");
                    $newmenu->add(DOL_URL_ROOT."/compta/facture.php?leftmenu=customers_bills",$langs->trans("BillsCustomers"),1,$user->rights->facture->lire);
                    if ($leftmenu=="customers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php",$langs->trans("Unpayed"),2,$user->rights->facture->lire);
                    if ($user->societe_id == 0) 
                    {
                        if ($leftmenu=="customers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/compta/clients.php?action=facturer",$langs->trans("NewBill"),2,$user->rights->facture->creer);
                    }
                    if (! defined(FACTURE_DISABLE_RECUR) || ! FACTURE_DISABLE_RECUR)
                    {
                        if ($leftmenu=="customers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php","Récurrentes",2,$user->rights->facture->lire);
                    }
                    if ($leftmenu=="customers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php",$langs->trans("Payments"),2,$user->rights->facture->lire);
                    if ($leftmenu=="customers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/", $langs->trans("Statistics"),2,$user->rights->facture->lire);
                }
                
                // Dons
                if ($conf->don->enabled)
                {
                  $langs->load("donations");
                  $newmenu->add(DOL_URL_ROOT."/compta/dons/index.php?leftmenu=donations&mainmenu=",$langs->trans("Donations"));
                }
                
                // Déplacements
                if ($conf->deplacement->enabled)
                {
                    $langs->load("trips");
                  $newmenu->add(DOL_URL_ROOT."/compta/deplacement/index.php?leftmenu=deplacement&mainmenu=accountancy", $langs->trans("Trips"));
                }
                
                // Charges
                if ($conf->compta->enabled)
                {
                  $newmenu->add(DOL_URL_ROOT."/compta/charges/index.php?leftmenu=charges&mainmenu=accountancy",$langs->trans("Charges"));
                  if ($leftmenu=="charges") $newmenu->add_submenu(DOL_URL_ROOT."/compta/sociales/index.php",$langs->trans("SocialContributions"));
                }
                
                if ($conf->compta->enabled && $conf->compta->tva && $user->societe_id == 0)
                {
                  $newmenu->add(DOL_URL_ROOT."/compta/tva/index.php?leftmenu=vat&mainmenu=accountancy",$langs->trans("VAT"));
                  if ($leftmenu=="vat") $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/reglement.php",$langs->trans("Payments"),1);
                  if ($leftmenu=="vat") $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/fiche.php?action=create",$langs->trans("NewPayment"),1);
                }
                
                if ($conf->compta->enabled) {
                    //$newmenu->add(DOL_URL_ROOT."/compta/ventilation/index.php?leftmenu=ventil",$langs->trans("Ventilations"));
                    //if ($leftmenu=="ventil") $newmenu->add_submenu(DOL_URL_ROOT."/compta/ventilation/liste.php",$langs->trans("A ventiler"),1);
                    //if ($leftmenu=="ventil") $newmenu->add_submenu(DOL_URL_ROOT."/compta/ventilation/lignes.php",$langs->trans("Ventilées"),1);
                    //if ($user->rights->compta->ventilation->param) {
                    //    if ($leftmenu=="ventil") $newmenu->add_submenu(DOL_URL_ROOT."/compta/param/",$langs->trans("Param"),1);
                    //}
                }
                        
                // Bank-Caisse
                if ($conf->banque->enabled && $user->rights->banque->lire)
                { 
                  $langs->load("banks");
                  $newmenu->add(DOL_URL_ROOT."/compta/bank/index.php?leftmenu=bank&mainmenu=bank",$langs->trans("Bank"));


                }
                
                // Prélèvements
                if ($conf->prelevement->enabled)
                {
                    $newmenu->add(DOL_URL_ROOT."/compta/prelevement/index.php?leftmenu=withdraw",$langs->trans("StandingOrders"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/bons.php",$langs->trans("Receipts"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/rejets.php",$langs->trans("Rejects"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/stats.php",$langs->trans("Statistics"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php",$langs->trans("StandingOrderToProcess"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandestraitees.php",$langs->trans("StandingOrderProcessed"));
                }
               
                // Bilan, résultats
                $newmenu->add(DOL_URL_ROOT."/compta/stats/index.php?leftmenu=ca&mainmenu=accountancy","Résultats / CA");
        
            	if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca","Résultat / Exercice",1);
                if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/clientfourn.php?leftmenu=ca","Détail client/fourn.",2);
                if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/compteres.php?leftmenu=ca","Compte de résultat",2);
                if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/bilan.php?leftmenu=ca","Bilan",2);
            	
            	if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/index.php?leftmenu=ca","Chiffre d'affaire",1);
            	
            	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/cumul.php?leftmenu=ca","Cumulé",2);
            	if ($conf->propal->enabled) {
            		if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/prev.php?leftmenu=ca","Prévisionnel",2);
            		if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/comp.php?leftmenu=ca","Transformé",2);
            	}
            	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/exercices.php?leftmenu=ca",$langs->trans("Evolution"),2);
            	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/casoc.php?leftmenu=ca",$langs->trans("ByCompanies"),2);
            	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/cabyuser.php?leftmenu=ca",$langs->trans("ByUsers"),2);
            }
        
        
            /*
             * Menu PRODUITS-SERVICES
             */
            if ($mainmenu == 'products') {
        
              if ($conf->produit->enabled)
                {
                  $newmenu->add(DOL_URL_ROOT."/product/index.php?type=0", $langs->trans("Products"), 0, $user->rights->produit->lire);
                  if ($user->societe_id == 0)
            	{
            	  $newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=0", $langs->trans("NewProduct"), 1, $user->rights->produit->creer);
            	}
                  $newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=0", $langs->trans("List"), 1, $user->rights->produit->lire);
                }
              
              if ($conf->service->enabled)
                {
                  $newmenu->add(DOL_URL_ROOT."/product/index.php?type=1", $langs->trans("Services"), 0, $user->rights->produit->lire);
                  if ($user->societe_id == 0)
            	{
            	  $newmenu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=1", $langs->trans("NewService"), 1, $user->rights->produit->creer);
            	}
                  $newmenu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=1", $langs->trans("List"), 1, $user->rights->produit->lire);
                }
            
              $newmenu->add(DOL_URL_ROOT."/product/stats/", $langs->trans("Statistics"), 0, $user->rights->produit->lire);
              if ($conf->propal->enabled)
                {
                  $newmenu->add_submenu(DOL_URL_ROOT."/product/popuprop.php", $langs->trans("Popularity"), 1, $user->rights->propale->lire);
                }
              
              if ($conf->stock->enabled)
                {
                    // \todo mettre droits pour module stock
                  $newmenu->add(DOL_URL_ROOT."/product/stock/", $langs->trans("Stock"), 0, $user->rights->stock->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/fiche.php?action=create", $langs->trans("NewWarehouse"), 1, $user->rights->stock->creer);
                  $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/liste.php", $langs->trans("List"), 1, $user->rights->stock->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/product/stock/mouvement.php", $langs->trans("Movements"), 1, $user->rights->stock->mouvement->lire);
                }
            }
        
        
            /*
             * Menu FOURNISSEURS
             */
            if ($mainmenu == 'suppliers') {

              $langs->load("suppliers");
        
              if ($conf->societe->enabled && $conf->fournisseur->enabled) 
                {
                    $newmenu->add(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"), 0, $user->rights->societe->lire);
            
                    // Sécurité accés client
                    if ($user->societe_id == 0) 
                    {
                      $newmenu->add_submenu(DOL_URL_ROOT."/soc.php??leftmenu=suppliers&action=create&type=f",$langs->trans("NewSupplier"), 1, $user->rights->societe->creer);
                    }
                    $newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&type=f",$langs->trans("Contacts"), 1, $user->rights->societe->lire);
              }
              
              if ($conf->facture->enabled)
                {
                  $langs->load("bills");
                  $newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php", $langs->trans("Bills"), 0, $user->rights->facture->lire);
                  
                  if ($user->societe_id == 0) 
            	{
            	  $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"), 1, $user->rights->facture->creer);
            	}
                  
                  $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"), 1, $user->rights->facture->lire);
                }
              
              if ($conf->commande->enabled)
              {
                  $langs->load("orders");
                  $newmenu->add(DOL_URL_ROOT."/fourn/commande/index.php?leftmenu=suppliers",$langs->trans("Orders"), 0, $user->rights->fournisseur->commande->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/fourn/commande/liste.php?leftmenu=suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
              }
            
            }
        
        
            /*
             * Menu OUTILS
             */
            if ($mainmenu == 'tools') {

                $newmenu->add(DOL_URL_ROOT."/comm/mailing/index.php?leftmenu=mailing", $langs->trans("Mailings"));

                if($user->admin)
                {
                  $langs->load("admin");
                  $langs->load("mails");
                  $newmenu->add_submenu(DOL_URL_ROOT."/comm/mailing/fiche.php?leftmenu=mailing&action=create", $langs->trans("NewMailing"));
        
                }

                if ($conf->projet->enabled ) 
                {
                  $langs->load("projects");
                  $newmenu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects", $langs->trans("Projects"), 0, $user->rights->projet->lire);
                  // \todo $newmenu->add_submenu(DOL_URL_ROOT."/comm/mailing/fiche.php?leftmenu=mailing&action=create", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
                }

            }
        
        
            // Pour les menu du haut qui ne serait pas gérés
            if ($mainmenu && ! in_array($mainmenu,$overwritemenufor)) { $mainmenu=""; }
        
        }


        
        /**
         *  Si on est sur un cas géré de surcharge du menu, on ecrase celui par defaut
         */
        if ($mainmenu) {
            $this->menu_array=$newmenu->liste;
        }


        // Affichage du menu
        $alt=0;
        for ($i = 0 ; $i < sizeof($this->menu_array) ; $i++) 
        {
            $alt++;
            if ($this->menu_array[$i]['level']==0) {
                if (($alt%2==0))
                {
                    print '<div class="blockvmenuimpair">'."\n";
                }
                else
                {
                    print '<div class="blockvmenupair">'."\n";
                }
            }

            if ($this->menu_array[$i]['level']==0) {
                if ($this->menu_array[$i]['enabled'])
                    print '<a class="vmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '<font class="vmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            if ($this->menu_array[$i]['level']==1) {
                if ($this->menu_array[$i]['enabled'])
                    print '<a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '<font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            if ($this->menu_array[$i]['level']==2) {
                if ($this->menu_array[$i]['enabled'])
                    print '&nbsp; &nbsp; <a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '&nbsp; &nbsp; <font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            
            if ($i == (sizeof($this->menu_array)-1) || $this->menu_array[$i+1]['level']==0)  {
                print '</div>';
            }
        }


    }
    
}

?>
