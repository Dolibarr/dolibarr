<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 */

define('NOCSRFCHECK', 1); // This is main home and login page. We must be able to go on it from another web site.

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// If not defined, we select menu "home"
$_GET['mainmenu'] = GETPOST('mainmenu', 'aZ09') ?GETPOST('mainmenu', 'aZ09') : 'home';
$action = GETPOST('action', 'aZ09');

$hookmanager->initHooks(array('index'));


/*
 * Actions
 */

// Check if company name is defined (first install)
if (!isset($conf->global->MAIN_INFO_SOCIETE_NOM) || empty($conf->global->MAIN_INFO_SOCIETE_NOM))
{
    header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
    exit;
}
if (count($conf->modules) <= (empty($conf->global->MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING) ? 1 : $conf->global->MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING))	// If only user module enabled
{
    header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
    exit;
}
if (GETPOST('addbox'))	// Add box (when submit is done from a form when ajax disabled)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOST('areacode', 'aZ09');
	$userid = GETPOST('userid', 'int');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');

	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) setEventMessages($langs->trans("BoxAdded"), null);
}


/*
 * View
 */

if (!is_object($form)) $form = new Form($db);

// Title
$title = $langs->trans("HomeArea").' - Dolibarr '.DOL_VERSION;
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = $langs->trans("HomeArea").' - '.$conf->global->MAIN_APPLICATION_TITLE;

llxHeader('', $title);


$resultboxes = FormOther::getBoxesArea($user, "0"); // Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)


print load_fiche_titre($langs->trans("HomeArea"), $resultboxes['selectboxlist'], 'home');

if (!empty($conf->global->MAIN_MOTD))
{
    $conf->global->MAIN_MOTD = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', '<br>', $conf->global->MAIN_MOTD);
    if (!empty($conf->global->MAIN_MOTD))
    {
        $substitutionarray = getCommonSubstitutionArray($langs);
        complete_substitutions_array($substitutionarray, $langs);
        $texttoshow = make_substitutions($conf->global->MAIN_MOTD, $substitutionarray, $langs);

        print "\n<!-- Start of welcome text -->\n";
        print '<table width="100%" class="notopnoleftnoright"><tr><td>';
        print dol_htmlentitiesbr($texttoshow);
        print '</td></tr></table><br>';
        print "\n<!-- End of welcome text -->\n";
    }
}





/*
 * Dashboard Dolibarr states (statistics)
 * Hidden for external users
 */


$boxstatItems = array();
$boxstatFromHook = '';

// Load translation files required by page
$langs->loadLangs(array('commercial', 'bills', 'orders', 'contracts'));

// Load global statistics of objects
if (empty($user->socid) && empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS))
{
    $object = new stdClass();
    $parameters = array();
    $action = '';
    $reshook = $hookmanager->executeHooks('addStatisticLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
    $boxstatFromHook = $hookmanager->resPrint;

    if (empty($reshook))
    {
	    // Condition to be checked for each display line dashboard
	    $conditions = array(
	    $user->rights->user->user->lire,
	    !empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS),
	    !empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS),
	    !empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS),
	    !empty($conf->societe->enabled) && $user->rights->societe->contact->lire,
	    !empty($conf->adherent->enabled) && $user->rights->adherent->lire,
	    !empty($conf->product->enabled) && $user->rights->produit->lire,
	    !empty($conf->service->enabled) && $user->rights->service->lire,
	    !empty($conf->propal->enabled) && $user->rights->propale->lire,
	    !empty($conf->commande->enabled) && $user->rights->commande->lire,
	    !empty($conf->facture->enabled) && $user->rights->facture->lire,
	    !empty($conf->contrat->enabled) && $user->rights->contrat->lire,
	    !empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire,
		!empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_ORDERS_STATS),
		!empty($conf->supplier_invoice->enabled) && $user->rights->fournisseur->facture->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_INVOICES_STATS),
		!empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_PROPOSAL_STATS),
	    !empty($conf->projet->enabled) && $user->rights->projet->lire,
	    !empty($conf->expensereport->enabled) && $user->rights->expensereport->lire,
        !empty($conf->holiday->enabled) && $user->rights->holiday->read,
		!empty($conf->don->enabled) && $user->rights->don->lire
	    );
	    // Class file containing the method load_state_board for each line
	    $includes = array(
	        DOL_DOCUMENT_ROOT."/user/class/user.class.php",
	        DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
	        DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
    	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php",
    	    DOL_DOCUMENT_ROOT."/contact/class/contact.class.php",
    	    DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php",
    	    DOL_DOCUMENT_ROOT."/product/class/product.class.php",
    	    DOL_DOCUMENT_ROOT."/product/class/product.class.php",
    	    DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php",
    	    DOL_DOCUMENT_ROOT."/commande/class/commande.class.php",
    	    DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php",
    	    DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php",
    	    DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php",
    	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php",
    	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php",
    	    DOL_DOCUMENT_ROOT."/supplier_proposal/class/supplier_proposal.class.php",
            DOL_DOCUMENT_ROOT."/projet/class/project.class.php",
	        DOL_DOCUMENT_ROOT."/expensereport/class/expensereport.class.php",
            DOL_DOCUMENT_ROOT."/holiday/class/holiday.class.php",
			DOL_DOCUMENT_ROOT."/don/class/don.class.php"
	    );
	    // Name class containing the method load_state_board for each line
	    $classes = array('User',
	                   'Client',
	                   'Client',
	                   'Fournisseur',
	                   'Contact',
	                   'Adherent',
	                   'Product',
	                   'Product',
	                   'Propal',
	                   'Commande',
	                   'Facture',
	                   'Contrat',
	                   'Fichinter',
	                   'CommandeFournisseur',
	                   'FactureFournisseur',
            	       'SupplierProposal',
	                   'Project',
	                   'ExpenseReport',
                       'Holiday',
					   'Don'
	    );
	    // Cle array returned by the method load_state_board for each line
	    $keys = array('users',
	                'customers',
	                'prospects',
	                'suppliers',
	                'contacts',
	                'members',
	                'products',
	                'services',
	                'proposals',
	                'orders',
	                'invoices',
	                'Contracts',
	                'fichinters',
	                'supplier_orders',
	                'supplier_invoices',
	                'askprice',
	                'projects',
	                'expensereports',
                    'holidays',
					'donations'
	    );
	    // Dashboard Icon lines
	    $icons = array('user',
	                 'company',
	                 'company',
	                 'company',
	                 'contact',
	                 'user',
	                 'product',
	                 'service',
	                 'propal',
	                 'order',
	                 'bill',
	                 'order',
	                 'order',
	                 'order',
	                 'bill',
	                 'propal',
	                 'projectpub',
					 'trip',
                     'holiday',
					 'generic'
	    );
	    // Translation keyword
	    $titres = array("Users",
	                  "ThirdPartyCustomersStats",
	                  "ThirdPartyProspectsStats",
	                  "Suppliers",
	                  "Contacts",
	                  "Members",
	                  "Products",
	                  "Services",
	                  "CommercialProposalsShort",
	                  "CustomersOrders",
	                  "BillsCustomers",
	                  "Contracts",
	                  "Interventions",
	                  "SuppliersOrders",
                      "SuppliersInvoices",
	                  "SupplierProposalShort",
	                  "Projects",
					  "ExpenseReports",
                      "Holidays",
					  "Donations"
	    );
	    // Dashboard Link lines
	    $links = array(
	        DOL_URL_ROOT.'/user/list.php',
    	    DOL_URL_ROOT.'/societe/list.php?type=c&mainmenu=companies',
    	    DOL_URL_ROOT.'/societe/list.php?type=p&mainmenu=companies',
    	    DOL_URL_ROOT.'/societe/list.php?type=f&mainmenu=companies',
    	    DOL_URL_ROOT.'/contact/list.php?mainmenu=companies',
    	    DOL_URL_ROOT.'/adherents/list.php?statut=1&mainmenu=members',
    	    DOL_URL_ROOT.'/product/list.php?type=0&mainmenu=products',
    	    DOL_URL_ROOT.'/product/list.php?type=1&mainmenu=products',
    	    DOL_URL_ROOT.'/comm/propal/list.php?mainmenu=commercial&leftmenu=propals',
    	    DOL_URL_ROOT.'/commande/list.php?mainmenu=commercial&leftmenu=orders',
    	    DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=billing&leftmenu=customers_bills',
    	    DOL_URL_ROOT.'/contrat/list.php?mainmenu=commercial&leftmenu=contracts',
    	    DOL_URL_ROOT.'/fichinter/list.php?mainmenu=commercial&leftmenu=ficheinter',
    	    DOL_URL_ROOT.'/fourn/commande/list.php?mainmenu=commercial&leftmenu=orders_suppliers',
	        DOL_URL_ROOT.'/fourn/facture/list.php?mainmenu=billing&leftmenu=suppliers_bills',
	        DOL_URL_ROOT.'/supplier_proposal/list.php?mainmenu=commercial&leftmenu=',
	        DOL_URL_ROOT.'/projet/list.php?mainmenu=project',
    		DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm&leftmenu=expensereport',
            DOL_URL_ROOT.'/holiday/list.php?mainmenu=hrm&leftmenu=holiday',
			DOL_URL_ROOT.'/don/list.php?leftmenu=donations'
	    );
	    // Translation lang files
	    $langfile=array(
            "users",
            "companies",
            "prospects",
            "suppliers",
            "companies",
            "members",
            "products",
            "products",
            "propal",
            "orders",
            "bills",
			"contracts",
			"interventions",
            "bills",
            "bills",
            "supplier_proposal",
	        "projects",
			"trips",
            "holiday",
			"donations",
	    );


	    // Loop and displays each line of table
		$boardloaded = array();
	    foreach ($keys as $key=>$val)
	    {
	        if ($conditions[$key])
	        {
                $boxstatItem = '';
	            $classe = $classes[$key];
	            // Search in cache if load_state_board is already realized
	            if (!isset($boardloaded[$classe]) || !is_object($boardloaded[$classe]))
	            {
	            	include_once $includes[$key]; // Loading a class cost around 1Mb

	                $board = new $classe($db);
	                $board->load_state_board();
	                $boardloaded[$classe] = $board;
	            }
	            else
	            {
	                $board = $boardloaded[$classe];
	            }


	            if (!empty($langfile[$key])) $langs->load($langfile[$key]);
	            $text = $langs->trans($titres[$key]);
	            $boxstatItem .= '<a href="'.$links[$key].'" class="boxstatsindicator thumbstat nobold nounderline">';
	            $boxstatItem .= '<div class="boxstats">';
	            $boxstatItem .= '<span class="boxstatstext" title="'.dol_escape_htmltag($text).'">'.$text.'</span><br>';
	            $boxstatItem .= '<span class="boxstatsindicator">'.img_object("", $icons[$key], 'class="inline-block"').' '.($board->nb[$val] ? $board->nb[$val] : 0).'</span>';
	            $boxstatItem .= '</div>';
	            $boxstatItem .= '</a>';

                $boxstatItems[$val] = $boxstatItem;
	        }
	    }
    }
}




// Dolibarr Working Board with weather

if (empty($conf->global->MAIN_DISABLE_GLOBAL_WORKBOARD)) {
    $showweather = (empty($conf->global->MAIN_DISABLE_METEO) || $conf->global->MAIN_DISABLE_METEO == 2) ? 1 : 0;

    //Array that contains all WorkboardResponse classes to process them
    $dashboardlines = array();

    // Do not include sections without management permission
    require_once DOL_DOCUMENT_ROOT . '/core/class/workboardresponse.class.php';

    // Number of actions to do (late)
    if (!empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read) {
        include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $board = new ActionComm($db);
        $dashboardlines[$board->element] = $board->load_board($user);
    }

    // Number of project opened
    if (!empty($conf->projet->enabled) && $user->rights->projet->lire) {
        include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
        $board = new Project($db);
        $dashboardlines[$board->element] = $board->load_board($user);
    }

    // Number of tasks to do (late)
    if (!empty($conf->projet->enabled) && empty($conf->global->PROJECT_HIDE_TASKS) && $user->rights->projet->lire) {
        include_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
        $board = new Task($db);
        $dashboardlines[$board->element] = $board->load_board($user);
    }

    // Number of commercial proposals opened (expired)
    if (!empty($conf->propal->enabled) && $user->rights->propale->lire) {
        include_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $board = new Propal($db);
        $dashboardlines[$board->element . '_opened'] = $board->load_board($user, "opened");
        // Number of commercial proposals CLOSED signed (billed)
        $dashboardlines[$board->element . '_signed'] = $board->load_board($user, "signed");
    }

    // Number of commercial proposals opened (expired)
    if (!empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire) {
        include_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
        $board = new SupplierProposal($db);
        $dashboardlines[$board->element . '_opened'] = $board->load_board($user, "opened");
        // Number of commercial proposals CLOSED signed (billed)
        $dashboardlines[$board->element . '_signed'] = $board->load_board($user, "signed");
    }

    // Number of customer orders a deal
    if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
        include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        $board = new Commande($db);
        $dashboardlines[$board->element] = $board->load_board($user);
    }

    // Number of suppliers orders a deal
    if (!empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire) {
        include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
        $board = new CommandeFournisseur($db);
        $dashboardlines[$board->element . '_opened'] = $board->load_board($user, "opened");
        $dashboardlines[$board->element . '_awaiting'] = $board->load_board($user, 'awaiting');
    }

    // Number of services enabled (delayed)
    if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
        include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $board = new Contrat($db);
        $dashboardlines[$board->element . '_inactive'] = $board->load_board($user, "inactive");
        // Number of active services (expired)
        $dashboardlines[$board->element . '_active'] = $board->load_board($user, "active");
    }
    // Number of invoices customers (has paid)
    if (!empty($conf->facture->enabled) && $user->rights->facture->lire) {
        include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        $board = new Facture($db);
        $dashboardlines[$board->element] = $board->load_board($user);
    }

    // Number of supplier invoices (has paid)
    if (!empty($conf->supplier_invoice->enabled) && !empty($user->rights->fournisseur->facture->lire)) {
        include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
        $board = new FactureFournisseur($db);
        $dashboardlines[$board->element] = $board->load_board($user);
    }

    // Number of transactions to conciliate
    if (!empty($conf->banque->enabled) && $user->rights->banque->lire && !$user->socid) {
        include_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
        $board = new Account($db);
        $nb = $board::countAccountToReconcile();    // Get nb of account to reconciliate
        if ($nb > 0) {
            $dashboardlines[$board->element] = $board->load_board($user);
        }
    }

    // Number of cheque to send
    if (!empty($conf->banque->enabled) && $user->rights->banque->lire && !$user->socid && empty($conf->global->BANK_DISABLE_CHECK_DEPOSIT)) {
        include_once DOL_DOCUMENT_ROOT . '/compta/paiement/cheque/class/remisecheque.class.php';
        $board = new RemiseCheque($db);
        $dashboardlines['RemiseCheque'] = $board->load_board($user);
    }

    // Number of foundation members
    if (!empty($conf->adherent->enabled) && $user->rights->adherent->lire && !$user->socid) {
        include_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
        $board = new Adherent($db);
        $dashboardlines['Adherent'] = $board->load_board($user);
    }

    // Number of expense reports to approve
    if (!empty($conf->expensereport->enabled) && $user->rights->expensereport->approve) {
        include_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
        $board = new ExpenseReport($db);
        $dashboardlines['ExpenseReport'] = $board->load_board($user, 'toapprove');
    }

    // Number of expense reports to pay
    if (!empty($conf->expensereport->enabled) && $user->rights->expensereport->to_paid) {
        include_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
        $board = new ExpenseReport($db);
        $dashboardlines['ExpenseReport'] = $board->load_board($user, 'topay');
    }

    // Number of holidays to approve
    if (!empty($conf->holiday->enabled) && $user->rights->holiday->approve) {
        include_once DOL_DOCUMENT_ROOT . '/holiday/class/holiday.class.php';
        $board = new Holiday($db);
        $dashboardlines['Holiday'] = $board->load_board($user);
    }

    $object = new stdClass();
    $parameters = array();
    $action = '';
    $reshook = $hookmanager->executeHooks('addOpenElementsDashboardLine', $parameters, $object,
        $action); // Note that $action and $object may have been modified by some hooks
    if ($reshook == 0) {
        $dashboardlines = array_merge($dashboardlines, $hookmanager->resArray);
    }

    /* grouping dashboard stats */
    $dashboardgroup = array(
        'action' =>
            array(
                'groupName' => 'Agenda',
                'stats' => array('action'),
            ),
        'project' =>
            array(
                'groupName' => 'Projects',
                'globalStatsKey' => 'projects',
                'stats' => array('project', 'project_task'),
            ),
        'propal' =>
            array(
                'groupName' => 'Proposals',
                'globalStatsKey' => 'proposals',
                'stats' =>
                    array('propal_opened', 'propal_signed'),
            ),
        'commande' =>
            array(
                'groupName' => 'Orders',
                'globalStatsKey' => 'orders',
                'stats' =>
                    array('commande'),
            ),
        'facture' =>
            array(
                'groupName' => 'Invoices',
                'globalStatsKey' => 'invoices',
                'stats' =>
                    array('facture'),
            ),
        'contrat' =>
            array(
                'groupName' => 'Contracts',
                'globalStatsKey' => 'Contracts',
                'stats' =>
                    array('contrat_inactive', 'contrat_active'),
            ),
        'supplier_proposal' =>
            array(
                'groupName' => 'SupplierProposals',
                'globalStatsKey' => 'askprice',
                'stats' =>
                    array('supplier_proposal_opened', 'supplier_proposal_signed'),
            ),
        'order_supplier' =>
            array(
                'groupName' => 'SuppliersOrders',
                'globalStatsKey' => 'supplier_orders',
                'stats' =>
                    array('order_supplier_opened', 'order_supplier_awaiting'),
            ),
        'invoice_supplier' =>
            array(
                'groupName' => 'BillsSuppliers',
                'globalStatsKey' => 'supplier_invoices',
                'stats' =>
                    array('invoice_supplier'),
            ),
        'bank_account' =>
            array(
                'groupName' => 'BankAccount',
                'stats' =>
                    array('bank_account', 'RemiseCheque'),
            ),
        'Adherent' =>
            array(
                'groupName' => 'Members',
                'globalStatsKey' => 'members',
                'stats' =>
                    array('Adherent'),
            ),
        'ExpenseReport' =>
            array(
                'groupName' => 'ExpenseReport',
                'globalStatsKey' => 'expensereports',
                'stats' =>
                    array('ExpenseReport'),
            ),
        'Holiday' =>
            array(
                'groupName' => 'Holidays',
                'globalStatsKey' => 'holidays',
                'stats' =>
                    array('Holiday'),
            ),
    );

    $object = new stdClass();
    $parameters = array(
        'dashboardgroup' => $dashboardgroup
    );
    $reshook = $hookmanager->executeHooks('addOpenElementsDashboardGroup', $parameters, $object,
        $action); // Note that $action and $object may have been modified by some hooks
    if ($reshook == 0) {
        $dashboardgroup = array_merge($dashboardgroup, $hookmanager->resArray);
    }


    // Calculate total nb of late
    $totallate = $totaltodo = 0;

    //Remove any invalid response
    //load_board can return an integer if failed or WorkboardResponse if OK
    $valid_dashboardlines = array();
    foreach ($dashboardlines as $infoKey => $tmp) {
        if ($tmp instanceof WorkboardResponse) {
            $valid_dashboardlines[$infoKey] = $tmp;
        }
    }

    // We calculate $totallate. Must be defined before start of next loop because it is show in first fetch on next loop
    foreach ($valid_dashboardlines as $board) {
        if ($board->nbtodolate > 0) {
            $totaltodo += $board->nbtodo;
            $totallate += $board->nbtodolate;
        }
    }

    $openedDashBoardSize = 'info-box-sm'; // use sm by default
    foreach ($dashboardgroup as $dashbordelement) {
        if (is_array($dashbordelement['stats']) && count($dashbordelement['stats']) > 2) {
            $openedDashBoardSize = ''; // use default info box size : big
            break;
        }
    }

    $totalLateNumber = $totallate;
    $totallatePercentage = ((!empty($totaltodo)) ? round($totallate / $totaltodo * 100, 2) : 0);
    if (!empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE)) {
        $totallate = $totallatePercentage;
    }

    $boxwork = '';
    $boxwork .= '<div class="box">';
    $boxwork .= '<table summary="'.dol_escape_htmltag($langs->trans("WorkingBoard")).'" class="noborder boxtable boxtablenobottom boxworkingboard" width="100%">'."\n";
    $boxwork .= '<tr class="liste_titre">';
    $boxwork .= '<th class="liste_titre"><div class="inline-block valignmiddle">'.$langs->trans("DolibarrWorkBoard").'</div>';
    if ($showweather) {
        if ($totallate > 0) {
            $text = $langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv("NActionsLate",
                    $totallate.(!empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE) ? '%' : '')).')';
        } else {
            $text = $langs->transnoentitiesnoconv("NoItemLate");
        }
        $text .= '. '.$langs->transnoentitiesnoconv("LateDesc");
        //$text.=$form->textwithpicto('',$langs->trans("LateDesc"));
        $options = 'height="24px" style="float: right"';
        $boxwork .= showWeather($totallate, $text, $options, 'inline-block valignmiddle');
    }
    $boxwork .= '</th>';
    $boxwork .= '</tr>'."\n";

    // Show dashboard
    $nbworkboardempty = 0;
    $isIntopOpenedDashBoard = $globalStatInTopOpenedDashBoard = array();
    if (!empty($valid_dashboardlines)) {
        $openedDashBoard = '';

        $boxwork .= '<tr class="nobottom nohover"><td class="tdboxstats nohover flexcontainer centpercent"><div style="display: flex: flex-wrap: wrap">';

        foreach ($dashboardgroup as $groupKey => $groupElement) {
            $boards = array();

            if (empty($conf->global->MAIN_DISABLE_NEW_OPENED_DASH_BOARD)) {
                foreach ($groupElement['stats'] as $infoKey) {
                    if (!empty($valid_dashboardlines[$infoKey])) {
                        $boards[] = $valid_dashboardlines[$infoKey];
                        $isIntopOpenedDashBoard[] = $infoKey;
                    }
                }
            }

            if (!empty($boards)) {
                $groupName = $langs->trans($groupElement['groupName']);
                $groupKeyLowerCase = strtolower($groupKey);
                $nbTotalForGroup = 0;

                // global stats
                $globalStatsKey = false;
                if (!empty($groupElement['globalStatsKey']) && empty($groupElement['globalStats'])) { // can be filled by hook
                    $globalStatsKey = $groupElement['globalStatsKey'];
                    $groupElement['globalStats'] = array();

                    if (is_array($keys) && in_array($globalStatsKey, $keys))
                    {
                        // get key index of stats used in $includes, $classes, $keys, $icons, $titres, $links
                        $keyIndex = array_search($globalStatsKey, $keys);

                        $classe = $classes[$keyIndex];
                        if (isset($boardloaded[$classe]) && is_object($boardloaded[$classe]))
                        {
                            $groupElement['globalStats']['total'] = $boardloaded[$classe]->nb[$globalStatsKey] ? $boardloaded[$classe]->nb[$globalStatsKey] : 0;
                            $nbTotal = doubleval($groupElement['globalStats']['total']);
                            if ($nbTotal >= 10000) { $nbTotal = round($nbTotal / 1000, 2).'k'; }
                            $groupElement['globalStats']['text'] = $langs->trans('Total').' : '.$langs->trans($titres[$keyIndex]).' ('.$groupElement['globalStats']['total'].')';
                            $groupElement['globalStats']['total'] = $nbTotal;
                            $groupElement['globalStats']['link'] = $links[$keyIndex];
                        }
                    }
                }


                $openedDashBoard .= '<div class="box-flex-item">'."\n";
                $openedDashBoard .= '	<div class="info-box '.$openedDashBoardSize.'">'."\n";
                $openedDashBoard .= '		<span class="info-box-icon bg-infoxbox-'.$groupKeyLowerCase.'">'."\n";
                $openedDashBoard .= '		<i class="fa fa-dol-'.$groupKeyLowerCase.'"></i>'."\n";

                // Show the span for the total of record
                if (!empty($groupElement['globalStats'])) {
                    $globalStatInTopOpenedDashBoard[] = $globalStatsKey;
                    $openedDashBoard .= '		<span class="info-box-icon-text" title="'.$groupElement['globalStats']['text'].'">'.$nbTotal.'</span>'."\n";
                }

                $openedDashBoard .= '		</span>'."\n";
                $openedDashBoard .= '		<div class="info-box-content">'."\n";

                $openedDashBoard .= '			<span class="info-box-title" title="'.strip_tags($groupName).'">'.$groupName.'</span>'."\n";

                foreach ($boards as $board) {
                    if (!empty($board->labelShort)) {
                        $infoName = '<span title="'.$board->label.'">'.$board->labelShort.'</span>';
                    } else {
                        $infoName = $board->label;
                    }

                    $textLateTitle = $langs->trans("NActionsLate", $board->nbtodolate);
                    $textLateTitle .= ' ('.$langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($board->warning_delay) >= 0 ? '+' : '').ceil($board->warning_delay).' '.$langs->trans("days").')';

                    $textLate = '';
                    if ($board->nbtodolate > 0) {
                        $textLate .= ' <span title="'.dol_htmlentities($textLateTitle).'" class="classfortooltip badge badge-warning">';
                        $textLate .= '<i class="fa fa-exclamation-triangle"></i> '.$board->nbtodolate;
                        $textLate .= '</span>';
                    }

                    $nbtodClass = '';
                    if ($board->nbtodo > 0) {
                        $nbtodClass = 'badge badge-info';
                    }

                    $openedDashBoard .= '			<a href="'.$board->url.'" class="info-box-text">'.$infoName.' : <span class="'.$nbtodClass.' classfortooltip" title="'.$board->label.'" >'.$board->nbtodo.'</span>'.$textLate.'</a>'."\n";

                    if ($board->total > 0 && !empty($conf->global->MAIN_WORKBOARD_SHOW_TOTAL_WO_TAX)) {
                        $openedDashBoard .= '<a href="'.$board->url.'" class="info-box-text">'.$langs->trans('Total').' : '.price($board->total).'</a>';
                    }
                }

                $openedDashBoard .= '		</div><!-- /.info-box-content -->'."\n";
                $openedDashBoard .= '	</div><!-- /.info-box -->'."\n";
                $openedDashBoard .= '</div><!-- /.box-flex-item -->'."\n";
                $openedDashBoard .= "\n";
            }
        }

        if ($showweather && !empty($isIntopOpenedDashBoard)) {
            $appendClass = $conf->global->MAIN_DISABLE_METEO == 2 ? ' hideonsmartphone' : '';
            $weather = getWeatherStatus($totallate);

            $text = '';
            if ($totallate > 0) {
                $text = $langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv("NActionsLate",
                        $totallate.(!empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE) ? '%' : '')).')';
            } else {
                $text = $langs->transnoentitiesnoconv("NoItemLate");
            }
            $text .= '. '.$langs->transnoentitiesnoconv("LateDesc");

            $weatherDashBoard = '<div class="box-flex-item '.$appendClass.'">'."\n";
            $weatherDashBoard .= '	<div class="info-box '.$openedDashBoardSize.' info-box-weather info-box-weather-level'.$weather->level.'">'."\n";
            $weatherDashBoard .= '		<span class="info-box-icon">';
            $weatherDashBoard .= img_weather('', $weather->level, '', 0, 'valignmiddle width50');
            $weatherDashBoard .= '       </span>'."\n";
            $weatherDashBoard .= '		<div class="info-box-content">'."\n";
            $weatherDashBoard .= '			<span class="info-box-title">'.$langs->trans('GlobalOpenedElemView').'</span>'."\n";

            if ($totallatePercentage > 0 && !empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE)) {
                $weatherDashBoard .= '			<span class="info-box-number">'.$langs->transnoentitiesnoconv("NActionsLate",
                        price($totallatePercentage).'%').'</span>'."\n";
                $weatherDashBoard .= '			<span class="progress-description">'.$langs->trans('NActionsLate',
                        $totalLateNumber).'</span>'."\n";
            } else {
                $weatherDashBoard .= '			<span class="info-box-number">'.$langs->transnoentitiesnoconv("NActionsLate",
                        $totalLateNumber).'</span>'."\n";
                if ($totallatePercentage > 0) {
                    $weatherDashBoard .= '			<span class="progress-description">'.$langs->trans('NActionsLate',
                            price($totallatePercentage).'%').'</span>'."\n";
                }
            }

            $weatherDashBoard .= '		</div><!-- /.info-box-content -->'."\n";
            $weatherDashBoard .= '	</div><!-- /.info-box -->'."\n";
            $weatherDashBoard .= '</div><!-- /.box-flex-item -->'."\n";
            $weatherDashBoard .= "\n";

            $openedDashBoard = $weatherDashBoard.$openedDashBoard;
        }

        if (!empty($isIntopOpenedDashBoard)) {
            for ($i = 1; $i <= 10; $i++) {
                $openedDashBoard .= '<div class="box-flex-item filler"></div>';
            }
        }

        $nbworkboardcount = 0;
        foreach ($valid_dashboardlines as $infoKey => $board) {
            if (in_array($infoKey, $isIntopOpenedDashBoard)) {
                // skip if info is present on top
                continue;
            }

            if (empty($board->nbtodo)) {
                $nbworkboardempty++;
            }
            $nbworkboardcount++;


            $textlate = $langs->trans("NActionsLate", $board->nbtodolate);
            $textlate .= ' ('.$langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($board->warning_delay) >= 0 ? '+' : '').ceil($board->warning_delay).' '.$langs->trans("days").')';


            $boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats130 boxstatsborder">';
            $boxwork .= '<div class="boxstatscontent">';
            $boxwork .= '<span class="boxstatstext" title="'.dol_escape_htmltag($board->label).'">'.$board->img.' '.$board->label.'</span><br>';
            $boxwork .= '<a class="valignmiddle dashboardlineindicator" href="'.$board->url.'"><span class="dashboardlineindicator'.(($board->nbtodo == 0) ? ' dashboardlineok' : '').'">'.$board->nbtodo.'</span></a>';
            if ($board->total > 0 && !empty($conf->global->MAIN_WORKBOARD_SHOW_TOTAL_WO_TAX)) {
                $boxwork .= '&nbsp;/&nbsp;<a class="valignmiddle dashboardlineindicator" href="'.$board->url.'"><span class="dashboardlineindicator'.(($board->nbtodo == 0) ? ' dashboardlineok' : '').'">'.price($board->total).'</span></a>';
            }
            $boxwork .= '</div>';
            if ($board->nbtodolate > 0) {
                $boxwork .= '<div class="dashboardlinelatecoin nowrap">';
                $boxwork .= '<a title="'.dol_escape_htmltag($textlate).'" class="valignmiddle dashboardlineindicatorlate'.($board->nbtodolate > 0 ? ' dashboardlineko' : ' dashboardlineok').'" href="'.((!$board->url_late) ? $board->url : $board->url_late).'">';
                //$boxwork .= img_picto($textlate, "warning_white", 'class="valigntextbottom"').'';
                $boxwork .= img_picto($textlate, "warning_white",
                        'class="inline-block hideonsmartphone valigntextbottom"').'';
                $boxwork .= '<span class="dashboardlineindicatorlate'.($board->nbtodolate > 0 ? ' dashboardlineko' : ' dashboardlineok').'">';
                $boxwork .= $board->nbtodolate;
                $boxwork .= '</span>';
                $boxwork .= '</a>';
                $boxwork .= '</div>';
            }
            $boxwork .= '</div></div>';
            $boxwork .= "\n";
        }

        $boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';
        $boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';
        $boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';
        $boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';

        $boxwork .= '</div>';
        $boxwork .= '</td></tr>';
    } else {
        $boxwork .= '<tr class="nohover">';
        $boxwork .= '<td class="nohover valignmiddle opacitymedium">';
        $boxwork .= $langs->trans("NoOpenedElementToProcess");
        $boxwork .= '</td>';
        $boxwork .= '</tr>';
    }

    $boxwork .= '</td></tr>';

    $boxwork .= '</table>'; // End table array of working board
    $boxwork .= '</div>';

    if (!empty($isIntopOpenedDashBoard)) {
        print '<div class="fichecenter">';
        print '<div class="opened-dash-board-wrap"><div class="box-flex-container">'.$openedDashBoard.'</div></div>';
        print '</div>';
    }
}


print '<div class="clearboth"></div>';

print '<div class="fichecenter fichecenterbis">';


/*
 * Show boxes
 */

$boxlist.='<div class="twocolumns">';

$boxlist.='<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';
if(!empty($nbworkboardcount))
{
	$boxlist.=$boxwork;
}

$boxlist.=$resultboxes['boxlista'];

$boxlist.= '</div>';


if (empty($user->socid) && empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS))
{
    // Remove allready present info in new dash board
    if(!empty($conf->global->MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD) && is_array($boxstatItems) && count($boxstatItems) > 0){
        foreach ($boxstatItems as $boxstatItemKey => $boxstatItemHtml) {
            if (in_array($boxstatItemKey, $globalStatInTopOpenedDashBoard)) {
                unset($boxstatItems[$boxstatItemKey]);
            }
        }
    }

    if (!empty($boxstatFromHook) || !empty($boxstatItems)) {
        $boxstat .= '<div class="box">';
        $boxstat .= '<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="noborder boxtable boxtablenobottom nohover" width="100%">';
        $boxstat .= '<tr class="liste_titre">';
        $boxstat .= '<th class="liste_titre">';
        $boxstat .= '<div class="inline-block valignmiddle">'.$langs->trans("DolibarrStateBoard").'</div>';
        $boxstat .= '</th>';
        $boxstat .= '</tr>';
        $boxstat .= '<tr class="nobottom nohover"><td class="tdboxstats nohover flexcontainer">';

        $boxstat .= $boxstatFromHook;

        if (is_array($boxstatItems) && count($boxstatItems) > 0)
        {
            $boxstat .= implode('', $boxstatItems);
        }

        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
        $boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';

        $boxstat .= '</td></tr>';
        $boxstat .= '</table>';
        $boxstat .= '</div>';
    }
}

$boxlist .= '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

$boxlist .= $boxstat;
$boxlist .= $resultboxes['boxlistb'];

$boxlist .= '</div>';
$boxlist .= "\n";

$boxlist .= '</div>';


print $boxlist;

print '</div>';


/*
 * Show security warnings
 */

// Security warning repertoire install existe (si utilisateur admin)
if ($user->admin && empty($conf->global->MAIN_REMOVE_INSTALL_WARNING))
{
    $message = '';

    // Check if install lock file is present
    $lockfile = DOL_DATA_ROOT.'/install.lock';
    if (!empty($lockfile) && !file_exists($lockfile) && is_dir(DOL_DOCUMENT_ROOT."/install"))
    {
        $langs->load("errors");
        //if (! empty($message)) $message.='<br>';
        $message .= info_admin($langs->trans("WarningLockFileDoesNotExists", DOL_DATA_ROOT).' '.$langs->trans("WarningUntilDirRemoved", DOL_DOCUMENT_ROOT."/install"), 0, 0, '1', 'clearboth');
    }

    // Conf files must be in read only mode
    if (is_writable($conffile))
    {
        $langs->load("errors");
        //$langs->load("other");
        //if (! empty($message)) $message.='<br>';
        $message .= info_admin($langs->transnoentities("WarningConfFileMustBeReadOnly").' '.$langs->trans("WarningUntilDirRemoved", DOL_DOCUMENT_ROOT."/install"), 0, 0, '1', 'clearboth');
    }

    if ($message)
    {
        print $message;
        //$message.='<br>';
        //print info_admin($langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }
}

//print 'mem='.memory_get_usage().' - '.memory_get_peak_usage();

// End of page
llxFooter();
$db->close();


/**
 *  Show weather logo. Logo to show depends on $totallate and values for
 *  $conf->global->MAIN_METEO_LEVELx
 *
 *  @param      int     $totallate      Nb of element late
 *  @param      string  $text           Text to show on logo
 *  @param      string  $options        More parameters on img tag
 *  @param      string  $morecss        More CSS
 *  @return     string                  Return img tag of weather
 */
function showWeather($totallate, $text, $options, $morecss = '')
{
    global $conf;

    $weather = getWeatherStatus($totallate);
    return img_weather($text, $weather->picto, $options, 0, $morecss);
}


/**
 *  get weather level
 *  $conf->global->MAIN_METEO_LEVELx
 *
 *  @param      int     $totallate      Nb of element late
 *  @return     string                  Return img tag of weather
 */
function getWeatherStatus($totallate)
{
	global $conf;

	$weather = new stdClass();
	$weather->picto = '';

	$offset = 0;
	$factor = 10; // By default

	$used_conf = !empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE) ? 'MAIN_METEO_PERCENTAGE_LEVEL' : 'MAIN_METEO_LEVEL';

	$level0 = $offset;
	$weather->level = 0;
	if (!empty($conf->global->{$used_conf.'0'})) {
		$level0 = $conf->global->{$used_conf.'0'};
	}
	$level1 = $offset + 1 * $factor;
	if (!empty($conf->global->{$used_conf.'1'})) {
		$level1 = $conf->global->{$used_conf.'1'};
	}
	$level2 = $offset + 2 * $factor;
	if (!empty($conf->global->{$used_conf.'2'})) {
		$level2 = $conf->global->{$used_conf.'2'};
	}
	$level3 = $offset + 3 * $factor;
	if (!empty($conf->global->{$used_conf.'3'})) {
		$level3 = $conf->global->{$used_conf.'3'};
	}

	if ($totallate <= $level0) {
		$weather->picto = 'weather-clear.png';
		$weather->level = 0;
	}
	elseif ($totallate <= $level1) {
		$weather->picto = 'weather-few-clouds.png';
		$weather->level = 1;
	}
	elseif ($totallate <= $level2) {
		$weather->picto = 'weather-clouds.png';
		$weather->level = 2;
	}
	elseif ($totallate <= $level3) {
		$weather->picto = 'weather-many-clouds.png';
		$weather->level = 3;
	}
	else {
		$weather->picto = 'weather-storm.png';
		$weather->level = 4;
	}

	return $weather;
}
