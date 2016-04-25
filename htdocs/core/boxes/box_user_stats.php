<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016 Frederic France      <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/boxes/box_user_stats.php
 *	\ingroup    user
 *	\brief      Module to show box of user stats
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show user stats
 */
class box_user_stats extends ModeleBoxes
{
    var $boxcode="userstats";
    var $boximg="object_user";
    var $boxlabel="BoxTitleDolibarrStateBoard";
    var $depends = array("user");

    var $db;
    var $param;
    var $enabled = 1;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param='')
    {
        global $conf, $user;

        $this->db = $db;

        // disable module for such cases

    }

    /**
     *  Load data into info_box_contents array to show array later.
     *
     *  @param  int     $max        Maximum number of records to load
     *  @return void
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db, $conf, $hookmanager;
        $langs->load("boxes");

        $this->max = $max;


        $this->info_box_head = array('text' => $langs->trans("BoxTitleDolibarrStateBoard"));

        $boxstat='';

        $langs->load("commercial");
        $langs->load("bills");
        $langs->load("orders");
        $langs->load("contracts");

        if (empty($user->societe_id))
        {

            $var=true;

            $object=new stdClass();
            $parameters=array();
            $action='';
            $reshook = $hookmanager->executeHooks('addStatisticLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

            if (empty($reshook))
            {
                // Condition to be checked for each display line dashboard
                $conditions=array(
                    $user->rights->user->user->lire,
                    ! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS),
                    ! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS),
                    ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS),
                    ! empty($conf->adherent->enabled) && $user->rights->adherent->lire,
                    ! empty($conf->product->enabled) && $user->rights->produit->lire,
                    ! empty($conf->service->enabled) && $user->rights->service->lire,
                    ! empty($conf->propal->enabled) && $user->rights->propale->lire,
                    ! empty($conf->commande->enabled) && $user->rights->commande->lire,
                    ! empty($conf->facture->enabled) && $user->rights->facture->lire,
                    ! empty($conf->contrat->enabled) && $user->rights->contrat->activer,
                    ! empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_ORDERS_STATS),
                    ! empty($conf->supplier_invoice->enabled) && $user->rights->fournisseur->facture->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_INVOICES_STATS),
                    ! empty($conf->expensereport->enabled) && $user->rights->expensereport->lire,
                    ! empty($conf->projet->enabled) && $user->rights->projet->lire
                );
                // Class file containing the method load_state_board for each line
                $includes=array(
                    DOL_DOCUMENT_ROOT."/user/class/user.class.php",
                    DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
                    DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
                    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php",
                    DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php",
                    DOL_DOCUMENT_ROOT."/product/class/product.class.php",
                    DOL_DOCUMENT_ROOT."/product/class/service.class.php",
                    DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php",
                    DOL_DOCUMENT_ROOT."/commande/class/commande.class.php",
                    DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php",
                    DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php",
                    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php",
                    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php",
                    DOL_DOCUMENT_ROOT."/expensereport/class/expensereport.class.php",
                    DOL_DOCUMENT_ROOT."/projet/class/project.class.php" 
                );
                // Name class containing the method load_state_board for each line
                $classes=array(
                    'User',
                    'Client',
                    'Client',
                    'Fournisseur',
                    'Adherent',
                    'Product',
                    'Service',
                    'Propal',
                    'Commande',
                    'Facture',
                    'Contrat',
                    'CommandeFournisseur',
                    'FactureFournisseur',
                    'ExpenseReport',
                    'Project'
                );
                // Cle array returned by the method load_state_board for each line
                $keys=array(
                    'users',
                    'customers',
                    'prospects',
                    'suppliers',
                    'members',
                    'products',
                    'services',
                    'proposals',
                    'orders',
                    'invoices',
                    'Contracts',
                    'supplier_orders',
                    'supplier_invoices',
                    'expensereports',
                    'projects'
                );
                // Dashboard Icon lines
                $icons=array(
                    'user',
                    'company',
                    'company',
                    'company',
                    'user',
                    'product',
                    'service',
                    'propal',
                    'order',
                    'bill',
                    'order',
                    'order',
                    'bill',
                    'trip',
                    'project'
                );
                // Translation keyword
                $titres=array(
                    "Users",
                    "ThirdPartyCustomersStats",
                    "ThirdPartyProspectsStats",
                    "Suppliers",
                    "Members",
                    "Products",
                    "Services",
                    "CommercialProposalsShort",
                    "CustomersOrders",
                    "BillsCustomers",
                    "Contracts",
                    "SuppliersOrders",
                    "SuppliersInvoices",
                    "ExpenseReports",
                    "Projects"
                );
                // Dashboard Link lines
                $links=array(
                    DOL_URL_ROOT.'/user/index.php',
                    DOL_URL_ROOT.'/societe/list.php?type=c',
                    DOL_URL_ROOT.'/societe/list.php?type=p',
                    DOL_URL_ROOT.'/societe/list.php?type=f',
                    DOL_URL_ROOT.'/adherents/list.php?statut=1&mainmenu=members',
                    DOL_URL_ROOT.'/product/list.php?type=0&mainmenu=products',
                    DOL_URL_ROOT.'/product/list.php?type=1&mainmenu=products',
                    DOL_URL_ROOT.'/comm/propal/list.php?mainmenu=commercial',
                    DOL_URL_ROOT.'/commande/list.php?mainmenu=commercial',
                    DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=accountancy',
                    DOL_URL_ROOT.'/contrat/list.php',
                    DOL_URL_ROOT.'/fourn/commande/list.php',
                    DOL_URL_ROOT.'/fourn/facture/list.php',
                    DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm',
                    DOL_URL_ROOT.'/projet/list.php?mainmenu=project'
                );
                // Translation lang files
                $langfile=array(
                    "users",
                    "companies",
                    "prospects",
                    "suppliers",
                    "members",
                    "products",
                    "produts",
                    "propal",
                    "orders",
                    "bills",
                    "contracts",
                    "trips",
                    "projects"
                );


                // Loop and displays each line of table
                foreach ($keys as $key=>$val)
                {
                    if ($conditions[$key])
                    {
                        $classe = $classes[$key];
                        // Search in cache if load_state_board is already realized
                        if (! isset($boardloaded[$classe]) || ! is_object($boardloaded[$classe]))
                        {
                            include_once $includes[$key];   // Loading a class cost around 1Mb

                            $board = new $classe($db);
                            $board->load_state_board($user);
                            $boardloaded[$classe]=$board;
                        }
                        else $board=$boardloaded[$classe];

                        $var = !$var;
                        if (!empty($langfile[$key])) $langs->load($langfile[$key]);
                        $text = $langs->trans($titres[$key]);
                        $boxstat.='<a href="'.$links[$key].'" class="boxstatsindicator thumbstat nobold nounderline">';
                        $boxstat.='<div class="boxstats">';
                        $boxstat.='<span class="boxstatstext">'.img_object("",$icons[$key]).' '.$text.'</span><br>';
                        $boxstat.='<span class="boxstatsindicator">'.$board->nb[$val].'</span>';
                        $boxstat.='</div>';
                        $boxstat.='</a>';
                    }
                }
            }

        }
        //print $boxstat;

        $this->info_box_contents[0][0] = array('td' => 'align="center" class="nohover"','textnoformat'=>$boxstat);

    }

    /**
     *  Method to show box
     *
     *  @param  array   $head       Array with properties of box title
     *  @param  array   $contents   Array with properties of box lines
     *  @return void
     */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

