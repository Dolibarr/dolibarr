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
		\brief      Gestionnaire du menu du gauche Eldy
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
        \remarks    définir la liste des entrées menu à faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est défini dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les définitions de menu des fichiers pre.inc.php
*/


/**
        \class      MenuLeft
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
        
        if (! session_id()) session_start();    // En mode authentification PEAR, la session a déjà été ouverte
        
        $user->getrights("");
        
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
            $leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
        }
        
        
        $newmenu = new Menu();
        $overwritemenufor=array('home','members','products','suppliers','commercial','accountancy','tools');
        
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
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/triggers.php", $langs->trans("Triggers"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/perms.php", $langs->trans("DefaultRights"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
                  if ($leftmenu=="setup") $newmenu->add_submenu(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));
                  
                  $newmenu->add(DOL_URL_ROOT."/admin/system/index.php?leftmenu=system", $langs->trans("SystemInfo"));
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/dolibarr.php", $langs->trans("Dolibarr"),1);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/admin/system/constall.php", $langs->trans("AllParameters"),2);
                  if ($leftmenu=="system") $newmenu->add_submenu(DOL_URL_ROOT."/about.php", $langs->trans("About"),2);
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
                $newmenu->add(DOL_URL_ROOT."/comm/prospect/index.php?leftmenu=prospects", $langs->trans("Prospects"), 0, $user->rights->societe->lire);
                
                $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=prospects&action=create&amp;type=p", $langs->trans("MenuNewProspect"), 1, $user->rights->societe->creer);
                $newmenu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?leftmenu=prospects", $langs->trans("List"), 1, $user->rights->societe->lire);
                $newmenu->add_submenu(DOL_URL_ROOT."/comm/contact.php?leftmenu=prospects&type=p", $langs->trans("Contacts"), 1, $user->rights->societe->lire);

                // Clients
                $newmenu->add(DOL_URL_ROOT."/comm/index.php?leftmenu=customers", $langs->trans("Customers"), 0, $user->rights->societe->creer);

                $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=customers&action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 1, $user->rights->societe->creer);
                $newmenu->add_submenu(DOL_URL_ROOT."/comm/clients.php?leftmenu=customers", $langs->trans("List"), 1, $user->rights->societe->lire);
                $newmenu->add_submenu(DOL_URL_ROOT."/comm/contact.php?leftmenu=customers&type=c", $langs->trans("Contacts"), 1, $user->rights->societe->lire);
                
                // Actions
                $newmenu->add(DOL_URL_ROOT."/comm/action/index.php?leftmenu=actions", $langs->trans("Actions"), 0);
                if ($leftmenu=="actions") $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?status=todo", $langs->trans("MenuToDoActions"), 1);
                if ($leftmenu=="actions") $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/index.php?time=today", $langs->trans("Today"), 1);
                if ($leftmenu=="actions") $newmenu->add_submenu(DOL_URL_ROOT."/comm/action/rapport/index.php", $langs->trans("Reportings"), 1);
        
                // Propal
                if ($conf->propal->enabled)
                {
                  $langs->load("propal");
                  $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals", $langs->trans("Prop"), 0 ,$user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=propals", $langs->trans("NewPropal"), 1, $user->rights->propale->creer);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=0", $langs->trans("PropalsDraft"), 1, $user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=1", $langs->trans("PropalsOpened"), 1, $user->rights->propale->lire);
                  //if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=2", $langs->trans("PropalsNotBilled"), 1, $user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal.php?viewstatut=2,3,4", $langs->trans("PropalStatusClosedShort"), 1, $user->rights->propale->lire);
                  if ($leftmenu=="propals") $newmenu->add_submenu(DOL_URL_ROOT."/comm/propal/stats/", $langs->trans("Statistics"), 1, $user->rights->propale->lire);
                }
                
                // Commandes
                if ($conf->commande->enabled ) 
                {
                  $langs->load("orders");
                  $newmenu->add(DOL_URL_ROOT."/commande/index.php?leftmenu=orders", $langs->trans("Orders"), 0 ,$user->rights->commande->lire);
                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=orders", $langs->trans("NewOrder"), 1, $user->rights->commande->creer);
                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders", $langs->trans("List"), 1 ,$user->rights->commande->lire);
                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/stats/index.php?leftmenu=orders", $langs->trans("Statistics"), 1 ,$user->rights->commande->lire);
                }

                // Expeditions
                if ($conf->expedition->enabled) {
                  $newmenu->add(DOL_URL_ROOT."/expedition/index.php?leftmenu=sendings", $langs->trans("Sendings"), 0, $user->rights->expedition->lire);
                  if ($leftmenu=="sendings") $newmenu->add_submenu(DOL_URL_ROOT."/expedition/liste.php?leftmenu=sendings", $langs->trans("List"), 1 ,$user->rights->expedition->lire);
                  if ($leftmenu=="sendings") $newmenu->add_submenu(DOL_URL_ROOT."/expedition/stats/index.php?leftmenu=sendings", $langs->trans("Statistics"), 1 ,$user->rights->expedition->lire);
                }
                
                // Contrat
                if ($conf->contrat->enabled)
                {
                  $langs->load("contracts");
                  $newmenu->add(DOL_URL_ROOT."/contrat/index.php?leftmenu=contracts", $langs->trans("Contracts"), 0 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=contracts", $langs->trans("NewContract"), 1, $user->rights->contrat->creer);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/liste.php?leftmenu=contracts", $langs->trans("List"), 1 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts", $langs->trans("MenuServices"), 1 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&mode=0", $langs->trans("MenuInactiveServices"), 2 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&mode=4", $langs->trans("MenuRunningServices"), 2 ,$user->rights->contrat->lire);
                  //if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&mode=4", $langs->trans("MenuExpiredServices"), 2 ,$user->rights->contrat->lire);
                  if ($leftmenu=="contracts") $newmenu->add_submenu(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&mode=5", $langs->trans("MenuClosedServices"), 2 ,$user->rights->contrat->lire);
                }
                
                // Interventions
                if ($conf->fichinter->enabled ) 
                {
                  $langs->load("interventions");
                  $newmenu->add(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("Interventions"), 0, $user->rights->ficheinter->lire);
                  if ($leftmenu=="ficheinter") $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=ficheinter", $langs->trans("NewIntervention"), 1, $user->rights->ficheinter->creer);
                  if ($leftmenu=="ficheinter") $newmenu->add_submenu(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("List"), 1 ,$user->rights->ficheinter->lire);
                }
                
            }
        
        
            /*
             * Menu COMPTA
             */
            if ($mainmenu == 'accountancy')
            {
                $langs->load("companies");
        
                // Fournisseurs
                if ($conf->societe->enabled && $conf->fournisseur->enabled) 
                {
                    $langs->load("suppliers");
                    $newmenu->add_submenu(DOL_URL_ROOT."/compta/index.php?leftmenu=suppliers", $langs->trans("Suppliers"),0,$user->rights->societe->lire);
                
                    // Sécurité accés client
                    if ($user->societe_id == 0) 
                    {
                      $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=suppliers&action=create&type=f",$langs->trans("NewSupplier"),1,$user->rights->societe->creer);
                    }
                    $newmenu->add_submenu(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("List"),1,$user->rights->societe->lire);
                    if ($conf->societe->enabled)
                    {
                      $newmenu->add_submenu(DOL_URL_ROOT."/fourn/contact.php?leftmenu=suppliers",$langs->trans("Contacts"),1,$user->rights->societe->lire);
                    }
                    if ($conf->facture->enabled)
                    {
                        $langs->load("bills");
                        $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/index.php?leftmenu=suppliers_bills", $langs->trans("BillsSuppliers"),1,$user->rights->facture->lire);
                        if ($user->societe_id == 0) 
                        {
                          if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"),2,$user->rights->facture->creer);
                        }
                        if ($leftmenu=="suppliers_bills") $newmenu->add_submenu(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"),2,$user->rights->facture->lire);
                    }
                }        
        
                // Clients
                if ($conf->societe->enabled) { 
                    $newmenu->add(DOL_URL_ROOT."/compta/index.php?leftmenu=customers", $langs->trans("Customers"),0,$user->rights->societe->lire);
                    if ($user->societe_id == 0) 
                    {
                        $newmenu->add_submenu(DOL_URL_ROOT."/soc.php?leftmenu=customers&action=create&amp;type=c", $langs->trans("MenuNewCustomer"),1,$user->rights->societe->creer);
                    }
                    $newmenu->add(DOL_URL_ROOT."/compta/clients.php?leftmenu=customers", $langs->trans("List"),1,$user->rights->societe->lire);
                    $newmenu->add_submenu(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&type=c", $langs->trans("Contacts"),1,$user->rights->societe->lire);
                }
                                
                if ($conf->facture->enabled)
                {
                    $langs->load("bills");
                    $newmenu->add(DOL_URL_ROOT."/compta/facture.php?leftmenu=customers_bills",$langs->trans("BillsCustomers"),1,$user->rights->facture->lire);
                    if ($user->societe_id == 0) 
                    {
                        if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/clients.php?action=facturer",$langs->trans("NewBill"),2,$user->rights->facture->creer);
                    }
                    if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php",$langs->trans("Unpayed"),2,$user->rights->facture->lire);
                    if (! defined("FACTURE_DISABLE_RECUR") || ! FACTURE_DISABLE_RECUR)
                    {
                        if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/fiche-rec.php","Récurrentes",2,$user->rights->facture->lire);
                    }
                    if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/liste.php?leftmenu=customers_bills_payments",$langs->trans("Payments"),2,$user->rights->facture->lire);
                    
                    if (eregi("customers_bills_payments",$leftmenu))  $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/avalider.php",$langs->trans("MenuToValid"),3,$user->rights->facture->lire);
                    if (eregi("customers_bills_payments",$leftmenu))  $newmenu->add_submenu(DOL_URL_ROOT."/compta/paiement/rapport.php",$langs->trans("Reportings"),3,$user->rights->facture->lire);


                    if (eregi("customers_bills",$leftmenu)) $newmenu->add_submenu(DOL_URL_ROOT."/compta/facture/stats/", $langs->trans("Statistics"),2,$user->rights->facture->lire);
                }
                
                // Propal
                if ($conf->propal->enabled)
                {
                    $langs->load("propal");
                    $newmenu->add(DOL_URL_ROOT."/compta/propal.php",$langs->trans("Prop"),0,$user->rights->propale->lire);
                }

                // Commandes
                if ($conf->commande->enabled ) 
                {
                  $langs->load("orders");
                  $newmenu->add(DOL_URL_ROOT."/compta/commande/liste.php?leftmenu=orders&status=3", $langs->trans("MenuOrdersToBill"), 0, $user->rights->commande->lire);
//                  if ($leftmenu=="orders") $newmenu->add_submenu(DOL_URL_ROOT."/commande/", $langs->trans("StatusOrderToBill"), 1 ,$user->rights->commande->lire);
                }

                // Dons
                if ($conf->don->enabled)
                {
                  $langs->load("donations");
                  $newmenu->add(DOL_URL_ROOT."/compta/dons/index.php?leftmenu=donations&mainmenu=accountancy",$langs->trans("Donations"), 0, $user->rights->don->lire);
                  if ($leftmenu=="donations") $newmenu->add_submenu(DOL_URL_ROOT."/compta/dons/fiche.php?action=create",$langs->trans("NewDonation"), 1, $user->rights->don->creer);
                  if ($leftmenu=="donations") $newmenu->add_submenu(DOL_URL_ROOT."/compta/dons/liste.php",$langs->trans("List"), 1, $user->rights->don->lire);
                  if ($leftmenu=="donations") $newmenu->add_submenu(DOL_URL_ROOT."/compta/dons/stats.php",$langs->trans("Statistics"), 1, $user->rights->don->lire);
                }
                
                // Déplacements
                if ($conf->deplacement->enabled)
                {
                  $langs->load("trips");
                  $newmenu->add(DOL_URL_ROOT."/compta/deplacement/index.php?leftmenu=deplacement&mainmenu=accountancy", $langs->trans("Trips"), 0, $user->rights->deplacement->lire);
                }
                
                // Charges
                if ($conf->compta->enabled)
                {
                  $newmenu->add(DOL_URL_ROOT."/compta/charges/index.php?leftmenu=charges&mainmenu=accountancy",$langs->trans("Charges"), 0, $user->rights->compta->charges->lire);
                  if ($leftmenu=="charges") $newmenu->add_submenu(DOL_URL_ROOT."/compta/sociales/index.php",$langs->trans("SocialContributions"), 1, $user->rights->compta->charges->creer);
                }
                
                if ($conf->compta->enabled && $conf->compta->tva && $user->societe_id == 0)
                {
                  $newmenu->add(DOL_URL_ROOT."/compta/tva/index.php?leftmenu=vat&mainmenu=accountancy",$langs->trans("VAT"),0,$user->rights->compta->charges->lire);
                  if ($leftmenu=="vat") $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/fiche.php?action=create",$langs->trans("NewPayment"),1,$user->rights->compta->charges->creer);
                  if ($leftmenu=="vat") $newmenu->add_submenu(DOL_URL_ROOT."/compta/tva/reglement.php",$langs->trans("Payments"),1,$user->rights->compta->charges->lire);
                }
                
                if ($conf->compta->enabled) {
                    //$newmenu->add(DOL_URL_ROOT."/compta/ventilation/index.php?leftmenu=ventil",$langs->trans("Ventilations"));
                    //if ($leftmenu=="ventil") $newmenu->add_submenu(DOL_URL_ROOT."/compta/ventilation/liste.php",$langs->trans("A ventiler"),1);
                    //if ($leftmenu=="ventil") $newmenu->add_submenu(DOL_URL_ROOT."/compta/ventilation/lignes.php",$langs->trans("Ventilées"),1);
                    //if ($user->rights->compta->ventilation->param) {
                    //    if ($leftmenu=="ventil") $newmenu->add_submenu(DOL_URL_ROOT."/compta/param/",$langs->trans("Param"),1);
                    //}
                }
                        
                // Prélèvements
                if ($conf->prelevement->enabled)
                {
                    $langs->load("withdrawals");
                    $langs->load("banks");
                    $newmenu->add(DOL_URL_ROOT."/compta/prelevement/index.php?leftmenu=withdraw",$langs->trans("StandingOrders"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/bons.php",$langs->trans("Receipts"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/rejets.php",$langs->trans("Rejects"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php?status=0",$langs->trans("StandingOrderToProcess"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/demandes.php?status=1",$langs->trans("StandingOrderProcessed"));
                    if ($leftmenu=="withdraw") $newmenu->add_submenu(DOL_URL_ROOT."/compta/prelevement/stats.php",$langs->trans("Statistics"));
                }

                // Bank-Caisse
                if ($conf->banque->enabled)
                { 
                  $langs->load("banks");
                  $newmenu->add(DOL_URL_ROOT."/compta/bank/index.php?leftmenu=bank&mainmenu=bank",$langs->trans("Bank"),0,$user->rights->banque->lire);
                }
                
                // Rapports
                if ($conf->compta->enabled) {
                    // Bilan, résultats
                    $newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca&mainmenu=accountancy",$langs->trans("Reportings"),0,$user->rights->compta->resultat->lire);
            
                	if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca","Résultat / Exercice",1,$user->rights->compta->resultat->lire);
                    if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/clientfourn.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire);
                    /* On verra ca avec module compabilité
                    if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/compteres.php?leftmenu=ca","Compte de résultat",2,$user->rights->compta->resultat->lire);
                    if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/resultat/bilan.php?leftmenu=ca","Bilan",2,$user->rights->compta->resultat->lire);
                	*/
                	if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/index.php?leftmenu=ca","Chiffre d'affaire",1,$user->rights->compta->resultat->lire);
                	
                	/*
                	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/cumul.php?leftmenu=ca","Cumulé",2,$user->rights->compta->resultat->lire);
                	if ($conf->propal->enabled) {
                		if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/prev.php?leftmenu=ca","Prévisionnel",2,$user->rights->compta->resultat->lire);
                		if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/comp.php?leftmenu=ca","Transformé",2,$user->rights->compta->resultat->lire);
                	}
                	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/exercices.php?leftmenu=ca",$langs->trans("Evolution"),2,$user->rights->compta->resultat->lire);
                	*/
                	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/casoc.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire);
                	if ($leftmenu=="ca") $newmenu->add_submenu(DOL_URL_ROOT."/compta/stats/cabyuser.php?leftmenu=ca",$langs->trans("ByUsers"),2,$user->rights->compta->resultat->lire);
                }

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
                  $newmenu->add_submenu(DOL_URL_ROOT."/societe.php?leftmenu=supplier", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
                  $newmenu->add_submenu(DOL_URL_ROOT."/fourn/commande/liste.php?leftmenu=suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
              }
            
            }
        
        
            /*
             * Menu OUTILS
             */
            if ($mainmenu == 'tools') {

                if ($conf->mailing->enabled) 
                {
                  $langs->load("admin");
                  $langs->load("mails");

                  $newmenu->add(DOL_URL_ROOT."/comm/mailing/index.php?leftmenu=mailing", $langs->trans("EMailings"),0,$user->rights->mailing->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/comm/mailing/fiche.php?leftmenu=mailing&action=create", $langs->trans("NewMailing"),1,$user->rights->mailing->creer);
                }

                if ($conf->projet->enabled) 
                {
                  $langs->load("projects");
                  $newmenu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects", $langs->trans("Projects"), 0, $user->rights->projet->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/comm/clients.php?leftmenu=projects", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
                }

                $newmenu->add_submenu(DOL_URL_ROOT."/comm/bookmark.php?leftmenu=bookmarks", $langs->trans("Bookmarks"), 0, 1);
            }
        
            /*
             * Menu MEMBERS
             */
            if ($mainmenu == 'members') {

                if ($conf->adherent->enabled)
                {
                  $user->getrights("adherent");
                  
                  $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=members&mainmenu=members",$langs->trans("Members"),0,$user->rights->adherent->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/fiche.php?action=create",$langs->trans("NewMember"),1,$user->rights->adherent->creer);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php",$langs->trans("List"),1,$user->rights->adherent->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=-1",$langs->trans("MenuMembersToValidate"),1,$user->rights->adherent->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=1",$langs->trans("MenuMembersValidated"),1,$user->rights->adherent->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=1&filter=uptodate",$langs->trans("MenuMembersUpToDate"),1,$user->rights->adherent->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/liste.php?statut=0",$langs->trans("MenuMembersResiliated"),1,$user->rights->adherent->lire);
                
                  $langs->load("compta");
                  $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=accountancy&mainmenu=members",$langs->trans("Subscriptions"),0,$user->rights->adherent->cotisation->lire);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/cotisations.php?leftmenu=accountancy",$langs->trans("List"),1,$user->rights->adherent->cotisation->lire);
                  $langs->load("banks");
                  $newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/index.php?leftmenu=accountancy",$langs->trans("Banks"),0,$user->rights->adherent->lire);
                
                  $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=export&mainmenu=members",$langs->trans("Export"),0,$user->rights->adherent->lire);
                  if ($leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/adherents/htpasswd.php?leftmenu=export","Format htpasswd",1,$user->rights->adherent->lire);
                  if ($leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/adherents/cartes/carte.php?leftmenu=export","Cartes d'adhérents",1,$user->rights->adherent->lire);
                  if ($leftmenu=="export") $newmenu->add_submenu(DOL_URL_ROOT."/adherents/cartes/etiquette.php?leftmenu=export","Etiquettes d'adhérents",1,$user->rights->adherent->lire);
                
                  $newmenu->add(DOL_URL_ROOT."/public/adherents/index.php","Espace adherents public");
                
                  $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=setup&mainmenu=members",$langs->trans("Setup"),0,$user->rights->adherent->configurer);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/type.php?leftmenu=setup&",$langs->trans("MembersTypes"),1,$user->rights->adherent->configurer);
                  $newmenu->add_submenu(DOL_URL_ROOT."/adherents/options.php?leftmenu=setup&",$langs->trans("MembersAttributes"),1,$user->rights->adherent->configurer);
                }

            }

            /*
             * Menu AUTRES (Pour les menus du haut qui ne serait pas gérés)
             */
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
            if ($this->menu_array[$i]['level']==3) {
                if ($this->menu_array[$i]['enabled'])
                    print '&nbsp; &nbsp; &nbsp; &nbsp; <a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '&nbsp; &nbsp; &nbsp; &nbsp; <font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            
            if ($i == (sizeof($this->menu_array)-1) || $this->menu_array[$i+1]['level']==0)  {
                print '</div>';
            }
        }


    }
    
}

?>
