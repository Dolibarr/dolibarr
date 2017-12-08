<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 */

define('NOCSRFCHECK',1);	// This is main home and login page. We must be able to go on it from another web site.

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// If not defined, we select menu "home"
$_GET['mainmenu']=GETPOST('mainmenu', 'aZ09')?GETPOST('mainmenu', 'aZ09'):'home';
$action=GETPOST('action');

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
if (count($conf->modules) <= (empty($conf->global->MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING)?1:$conf->global->MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING))	// If only user module enabled
{
    header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
    exit;
}
if (GETPOST('addbox'))	// Add box (when submit is done from a form when ajax disabled)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone=GETPOST('areacode', 'aZ09');
	$userid=GETPOST('userid', 'int');
	$boxorder=GETPOST('boxorder', 'aZ09');
	$boxorder.=GETPOST('boxcombo', 'aZ09');

	$result=InfoBox::saveboxorder($db,$zone,$boxorder,$userid);
}




/*
 * View
 */

if (! is_object($form)) $form=new Form($db);

// Title
$title=$langs->trans("HomeArea").' - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$langs->trans("HomeArea").' - '.$conf->global->MAIN_APPLICATION_TITLE;

llxHeader('',$title);


$resultboxes=FormOther::getBoxesArea($user,"0");


print load_fiche_titre($langs->trans("HomeArea"),$resultboxes['selectboxlist'],'title_home');

if (! empty($conf->global->MAIN_MOTD))
{
    $conf->global->MAIN_MOTD=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i','<br>',$conf->global->MAIN_MOTD);
    if (! empty($conf->global->MAIN_MOTD))
    {
    	$i=0;
    	while (preg_match('/__\(([a-zA-Z|@]+)\)__/i',$conf->global->MAIN_MOTD,$reg) && $i < 100)
    	{
    		$tmp=explode('|',$reg[1]);
    		if (! empty($tmp[1])) $langs->load($tmp[1]);
    		$conf->global->MAIN_MOTD=preg_replace('/__\('.preg_quote($reg[1]).'\)__/i',$langs->trans($tmp[0]),$conf->global->MAIN_MOTD);
    		$i++;
    	}

        print "\n<!-- Start of welcome text -->\n";
        print '<table width="100%" class="notopnoleftnoright"><tr><td>';
        print dol_htmlentitiesbr($conf->global->MAIN_MOTD);
        print '</td></tr></table><br>';
        print "\n<!-- End of welcome text -->\n";
    }
}


print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Informations area
 */
$boxinfo='';
$boxinfo.= '<div class="box">';
$boxinfo.= '<table summary="'.dol_escape_htmltag($langs->trans("LoginInformation")).'" class="noborder boxtable" width="100%">';
$boxinfo.= '<tr class="liste_titre"><th class="liste_titre" colspan="2">'.$langs->trans("Informations").'</th></tr>';
$boxinfo.= '<tr '.$bc[false].'>';
$boxinfo.= '<td class="nowrap">'.$langs->trans("User").'</td><td>'.$user->getNomUrl(0).'</td></tr>';
$boxinfo.= '<tr '.$bc[true].'>';
$boxinfo.= '<td class="nowrap">'.$langs->trans("PreviousConnexion").'</td><td>';
if ($user->datepreviouslogin) $boxinfo.= dol_print_date($user->datepreviouslogin,"dayhour",'tzuser');
else $boxinfo.= $langs->trans("Unknown");
$boxinfo.= '</td>';
$boxinfo.= "</tr>\n";
$boxinfo.= "</table>\n";
$boxinfo.= '</div>';
//print $boxinfo;


/*
 * Dashboard Dolibarr states (statistics)
 * Hidden for external users
 */
$boxstat='';

$langs->load("commercial");
$langs->load("bills");
$langs->load("orders");
$langs->load("contracts");

if (empty($user->societe_id))
{
    $boxstat.='<div class="box">';
    $boxstat.='<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="noborder boxtable" width="100%">';
    $boxstat.='<tr class="liste_titre">';
    $boxstat.='<th class="liste_titre">'.$langs->trans("DolibarrStateBoard").'</th>';
    $boxstat.='</tr>';
    $boxstat.='<tr class="impair"><td class="tdboxstats nohover">';

    $var=true;

    $object=new stdClass();
    $parameters=array();
    $action='';
    $reshook=$hookmanager->executeHooks('addStatisticLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
    $boxstat.=$hookmanager->resPrint;
    
    if (empty($reshook))
    {
	    // Condition to be checked for each display line dashboard
	    $conditions=array(
	    $user->rights->user->user->lire,
	    ! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS),
	    ! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS),
	    ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS),
	    ! empty($conf->societe->enabled) && $user->rights->societe->contact->lire,
	    ! empty($conf->adherent->enabled) && $user->rights->adherent->lire,
	    ! empty($conf->product->enabled) && $user->rights->produit->lire,
	    ! empty($conf->service->enabled) && $user->rights->service->lire,
	    ! empty($conf->propal->enabled) && $user->rights->propale->lire,
	    ! empty($conf->commande->enabled) && $user->rights->commande->lire,
	    ! empty($conf->facture->enabled) && $user->rights->facture->lire,
	    ! empty($conf->contrat->enabled) && $user->rights->contrat->lire,
	    ! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire,
		! empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_ORDERS_STATS),
		! empty($conf->supplier_invoice->enabled) && $user->rights->fournisseur->facture->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_INVOICES_STATS),
		! empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_PROPOSAL_STATS),
	    ! empty($conf->projet->enabled) && $user->rights->projet->lire,
	    ! empty($conf->expensereport->enabled) && $user->rights->expensereport->lire
	    );
	    // Class file containing the method load_state_board for each line
	    $includes=array(
	        DOL_DOCUMENT_ROOT."/user/class/user.class.php",
	        DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
	        DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
    	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php",
    	    DOL_DOCUMENT_ROOT."/contact/class/contact.class.php",
    	    DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php",
    	    DOL_DOCUMENT_ROOT."/product/class/product.class.php",
    	    DOL_DOCUMENT_ROOT."/product/class/service.class.php",
    	    DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php",
    	    DOL_DOCUMENT_ROOT."/commande/class/commande.class.php",
    	    DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php",
    	    DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php",
    	    DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php",
    	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php",
    	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php",
    	    DOL_DOCUMENT_ROOT."/supplier_proposal/class/supplier_proposal.class.php",
            DOL_DOCUMENT_ROOT."/projet/class/project.class.php", 
	        DOL_DOCUMENT_ROOT."/expensereport/class/expensereport.class.php"
	    );
	    // Name class containing the method load_state_board for each line
	    $classes=array('User',
	                   'Client',
	                   'Client',
	                   'Fournisseur',
	                   'Contact',
	                   'Adherent',
	                   'Product',
	                   'Service',
	                   'Propal',
	                   'Commande',
	                   'Facture',
	                   'Contrat',
	                   'Fichinter',
	                   'CommandeFournisseur',
	                   'FactureFournisseur',
            	       'SupplierProposal',
	                   'Project',
	                   'ExpenseReport'
	    );
	    // Cle array returned by the method load_state_board for each line
	    $keys=array('users',
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
	                'expensereports'
	    );
	    // Dashboard Icon lines
	    $icons=array('user',
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
	                 'project',
					 'trip'
	    );
	    // Translation keyword
	    $titres=array("Users",
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
					  "ExpenseReports"
	    );
	    // Dashboard Link lines
	    $links=array(
	        DOL_URL_ROOT.'/user/index.php',
    	    DOL_URL_ROOT.'/societe/list.php?type=c&mainmenu=companies',
    	    DOL_URL_ROOT.'/societe/list.php?type=p&mainmenu=companies',
    	    DOL_URL_ROOT.'/societe/list.php?type=f&mainmenu=companies',
    	    DOL_URL_ROOT.'/contact/list.php?mainmenu=companies',
    	    DOL_URL_ROOT.'/adherents/list.php?statut=1&mainmenu=members',
    	    DOL_URL_ROOT.'/product/list.php?type=0&mainmenu=products',
    	    DOL_URL_ROOT.'/product/list.php?type=1&mainmenu=products',
    	    DOL_URL_ROOT.'/comm/propal/list.php?mainmenu=commercial&leftmenu=propals',
    	    DOL_URL_ROOT.'/commande/list.php?mainmenu=commercial&leftmenu=orders',
    	    DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=accountancy&leftmenu=customers_bills',
    	    DOL_URL_ROOT.'/contrat/list.php?mainmenu=commercial&leftmenu=contracts',
    	    DOL_URL_ROOT.'/fichinter/list.php?mainmenu=commercial&leftmenu=ficheinter',
    	    DOL_URL_ROOT.'/fourn/commande/list.php?mainmenu=commercial&leftmenu=orders_suppliers',
	        DOL_URL_ROOT.'/fourn/facture/list.php?mainmenu=accountancy&leftmenu=suppliers_bills',
	        DOL_URL_ROOT.'/supplier_proposal/list.php?mainmenu=commercial&leftmenu=',
	        DOL_URL_ROOT.'/projet/list.php?mainmenu=project',
    		DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm&leftmenu=expensereport'
	    );
	    // Translation lang files
	    $langfile=array("users",
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
						"trips"
	    );


	    // Loop and displays each line of table
	    foreach ($keys as $key=>$val)
	    {
	        if ($conditions[$key])
	        {
	            $classe=$classes[$key];
	            // Search in cache if load_state_board is already realized
	            if (! isset($boardloaded[$classe]) || ! is_object($boardloaded[$classe]))
	            {
	            	include_once $includes[$key];	// Loading a class cost around 1Mb

	                $board=new $classe($db);
	                $board->load_state_board($user);
	                $boardloaded[$classe]=$board;
	            }
	            else $board=$boardloaded[$classe];

	            $var=!$var;
	            if (!empty($langfile[$key])) $langs->load($langfile[$key]);
	            $text=$langs->trans($titres[$key]);
	            $boxstat.='<a href="'.$links[$key].'" class="boxstatsindicator thumbstat nobold nounderline">';
	            $boxstat.='<div class="boxstats">';
	            $boxstat.='<span class="boxstatstext">'.img_object("",$icons[$key]).' '.$text.'</span><br>';
	            $boxstat.='<span class="boxstatsindicator">'.$board->nb[$val].'</span>';
	            $boxstat.='</div>';
	            $boxstat.='</a>';
	        }
	    }
    }

    $boxstat.='</td></tr>';
    $boxstat.='</table>';
    $boxstat.='</div>';
}
//print $boxstat;

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Dolibarr Working Board with weather
 */
$showweather=empty($conf->global->MAIN_DISABLE_METEO)?1:0;

//Array that contains all WorkboardResponse classes to process them
$dashboardlines=array();

$boxwork='';
$boxwork.='<div class="box">';
$boxwork.='<table summary="'.dol_escape_htmltag($langs->trans("WorkingBoard")).'" class="noborder boxtable" width="100%">'."\n";
$boxwork.='<tr class="liste_titre">';
$boxwork.='<th class="liste_titre" colspan="2">'.$langs->trans("DolibarrWorkBoard").'</th>';
$boxwork.='<th class="liste_titre" align="right">'.$langs->trans("Number").'</th>';
$boxwork.='<th class="liste_titre" align="right">'.$form->textwithpicto($langs->trans("Late"),$langs->trans("LateDesc")).'</th>';
$boxwork.='<th class="liste_titre">&nbsp;</th>';
//print '<th class="liste_titre" width="20">&nbsp;</th>';
if ($showweather) $boxwork.='<th class="liste_titre hideonsmartphone" width="80">&nbsp;</th>';
$boxwork.='</tr>'."\n";

// Do not include sections without management permission
require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

// Number of actions to do (late)
if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read)
{
    include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    $board=new ActionComm($db);

    $dashboardlines[] = $board->load_board($user);
}

// Number of project opened
if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    $board=new Project($db);

    $dashboardlines[] = $board->load_board($user);
}

// Number of tasks to do (late)
if (! empty($conf->projet->enabled) && empty($conf->global->PROJECT_HIDE_TASKS) && $user->rights->projet->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
    $board=new Task($db);
    $dashboardlines[] = $board->load_board($user);
}

// Number of commercial proposals opened (expired)
if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
    $board=new Propal($db);
    $dashboardlines[] = $board->load_board($user,"opened");
    // Number of commercial proposals CLOSED signed (billed)
    $dashboardlines[] = $board->load_board($user,"signed");
}

// Number of commercial proposals opened (expired)
if (! empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
    $board=new SupplierProposal($db);
    $dashboardlines[] = $board->load_board($user,"opened");
    // Number of commercial proposals CLOSED signed (billed)
    $dashboardlines[] = $board->load_board($user,"signed");
}

// Number of customer orders a deal
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    $board=new Commande($db);
	$dashboardlines[] = $board->load_board($user);
}

// Number of suppliers orders a deal
if (! empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
    $board=new CommandeFournisseur($db);
	$dashboardlines[] = $board->load_board($user);
}

// Number of services enabled (delayed)
if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
    $board=new Contrat($db);
    $dashboardlines[] = $board->load_board($user,"inactives");
	// Number of active services (expired)
    $dashboardlines[] = $board->load_board($user,"expired");
}
// Number of invoices customers (has paid)
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    $board=new Facture($db);
    $dashboardlines[] = $board->load_board($user);
}

// Number of supplier invoices (has paid)
if (! empty($conf->supplier_invoice->enabled) && ! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
    $board=new FactureFournisseur($db);
    $dashboardlines[] = $board->load_board($user);
}

// Number of transactions to conciliate
if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id)
{
    include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
    $board=new Account($db);
    $nb = $board::countAccountToReconcile();    // Get nb of account to reconciliate
    if ($nb > 0)
    {
        $dashboardlines[] = $board->load_board($user);
    }
}

// Number of cheque to send
if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id && empty($conf->global->BANK_DISABLE_CHECK_DEPOSIT))
{
    include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
    $board=new RemiseCheque($db);
    $dashboardlines[] = $board->load_board($user);
}

// Number of foundation members
if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire && ! $user->societe_id)
{
    include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
    $board=new Adherent($db);
    $dashboardlines[] = $board->load_board($user);
}

// Number of expense reports to approve
if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->approve)
{
    include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
    $board=new ExpenseReport($db);
	$dashboardlines[] = $board->load_board($user,'toapprove');
}

// Number of expense reports to pay
if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->to_paid)
{
    include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
    $board=new ExpenseReport($db);
	$dashboardlines[] = $board->load_board($user,'topay');
}

// Calculate total nb of late
$totallate=0;
$var=true;

//Remove any invalid response
//load_board can return an integer if failed or WorkboardResponse if OK
$valid_dashboardlines=array();
foreach($dashboardlines as $tmp)
{
	if ($tmp instanceof WorkboardResponse) $valid_dashboardlines[] = $tmp;
}
$rowspan = count($valid_dashboardlines);

// We calculate $totallate. Must be defined before start of next loop because it is show in first fetch on next loop
foreach($valid_dashboardlines as $board)
{
    if ($board->nbtodolate > 0) {
	    $totallate += $board->nbtodolate;
    }
}

// Show dashboard
foreach($valid_dashboardlines as $board)
{
    $var=!$var;
    $boxwork.= '<tr '.$bc[$var].'><td width="16">'.$board->img.'</td><td>'.$board->label.'</td>';
    $boxwork.= '<td align="right"><a class="dashboardlineindicator" href="'.$board->url.'"><span class="dashboardlineindicator">'.$board->nbtodo.'</span></a></td>';
    $boxwork.= '<td align="right">';
    //if ($board->nbtodolate > 0)
    //{
    $textlate = $langs->trans("NActionsLate",$board->nbtodolate);
    $textlate .= ' ('.$langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($board->warning_delay) >= 0 ? '+' : '').ceil($board->warning_delay).' '.$langs->trans("days").')';
    $boxwork.= '<a title="'.dol_escape_htmltag($textlate).'" class="dashboardlineindicatorlate'.($board->nbtodolate>0?' dashboardlineko':' dashboardlineok').'" href="'.$board->url.'"><span class="dashboardlineindicatorlate'.($board->nbtodolate>0?' dashboardlineko':' dashboardlineok').'">';
    $boxwork.= $board->nbtodolate;
    $boxwork.= '</span></a>';
    //}
    $boxwork.='</td>';
    $boxwork.='<td align="left">';
    if ($board->nbtodolate > 0) $boxwork.=img_picto($textlate,"warning");
    else $boxwork.='&nbsp;';
    $boxwork.='</td>';
    /*print '<td class="nowrap" align="right">';
    print ' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')';
    print '</td>';*/
    
    if ($showweather)
    {
        $boxwork.='<td class="nohover hideonsmartphone noborderbottom" rowspan="'.$rowspan.'" width="80" style="border-left: 1px solid #DDDDDD" align="center">';
        $text='';
        if ($totallate > 0) $text=$langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv("NActionsLate",$totallate).')';
        $options='height="64px"';
        if ($rowspan <= 2) $options='height="24"';  // Weather logo is smaller if dashboard has few elements
        else if ($rowspan <= 3) $options='height="48"';  // Weather logo is smaller if dashboard has few elements
        $boxwork.=showWeather($totallate,$text,$options);
        $boxwork.='</td>';
        $showweather=0;
    }
    $boxwork.='</tr>';
    $boxwork.="\n";
}

$boxwork.='</table>';   // End table array of working board
$boxwork.='</div>';

//print $boxwork;

print '</div></div></div><div class="clearboth"></div>';

print '<div class="fichecenter fichecenterbis">';


/*
 * Show boxes
 */

$boxlist.='<table width="100%" class="notopnoleftnoright">';
$boxlist.='<tr><td class="notopnoleftnoright">'."\n";

$boxlist.='<div class="fichehalfleft">';

$boxlist.=$boxinfo;
$boxlist.=$boxstat;
$boxlist.=$resultboxes['boxlista'];

$boxlist.= '</div><div class="fichehalfright"><div class="ficheaddleft">';

$boxlist.=$boxwork;
$boxlist.=$resultboxes['boxlistb'];

$boxlist.= '</div></div>';
$boxlist.= "\n";

$boxlist.= "</td></tr>";
$boxlist.= "</table>";

print $boxlist;

print '</div>';


/*
 * Show security warnings
 */

// Security warning repertoire install existe (si utilisateur admin)
if ($user->admin && empty($conf->global->MAIN_REMOVE_INSTALL_WARNING))
{
    $message='';

    // Check if install lock file is present
    $lockfile=DOL_DATA_ROOT.'/install.lock';
    if (! empty($lockfile) && ! file_exists($lockfile) && is_dir(DOL_DOCUMENT_ROOT."/install"))
    {
        $langs->load("errors");
        //if (! empty($message)) $message.='<br>';
        $message.=info_admin($langs->trans("WarningLockFileDoesNotExists",DOL_DATA_ROOT).' '.$langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }

    // Conf files must be in read only mode
    if (is_writable($conffile))
    {
        $langs->load("errors");
        //$langs->load("other");
        //if (! empty($message)) $message.='<br>';
        $message.=info_admin($langs->transnoentities("WarningConfFileMustBeReadOnly").' '.$langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }

    if ($message)
    {
        print $message;
        //$message.='<br>';
        //print info_admin($langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }
}

//print 'mem='.memory_get_usage().' - '.memory_get_peak_usage();

llxFooter();

$db->close();


/**
 *  Show weather logo. Logo to show depends on $totallate and values for
 *  $conf->global->MAIN_METEO_LEVELx
 *
 *  @param      int     $totallate      Nb of element late
 *  @param      string  $text           Text to show on logo
 *  @param      string  $options        More parameters on img tag
 *  @return     string                  Return img tag of weather
 */
function showWeather($totallate,$text,$options)
{
    global $conf;

    $out='';
    $offset=0;
    $factor=10; // By default
    
    $level0=$offset;           if (! empty($conf->global->MAIN_METEO_LEVEL0)) $level0=$conf->global->MAIN_METEO_LEVEL0;
    $level1=$offset+1*$factor; if (! empty($conf->global->MAIN_METEO_LEVEL1)) $level1=$conf->global->MAIN_METEO_LEVEL1;
    $level2=$offset+2*$factor; if (! empty($conf->global->MAIN_METEO_LEVEL2)) $level2=$conf->global->MAIN_METEO_LEVEL2;
    $level3=$offset+3*$factor; if (! empty($conf->global->MAIN_METEO_LEVEL3)) $level3=$conf->global->MAIN_METEO_LEVEL3;

    if ($totallate <= $level0) $out.=img_weather($text,'weather-clear.png',$options);
    if ($totallate > $level0 && $totallate <= $level1) $out.=img_weather($text,'weather-few-clouds.png',$options);
    if ($totallate > $level1 && $totallate <= $level2) $out.=img_weather($text,'weather-clouds.png',$options);
    if ($totallate > $level2 && $totallate <= $level3) $out.=img_weather($text,'weather-many-clouds.png',$options);
    if ($totallate > $level3) $out.=img_weather($text,'weather-storm.png',$options);
    return $out;
}
