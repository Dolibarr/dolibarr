<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 	Juanjo Menent			<jmenent@2byte.es>
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

define('NOCSRFCHECK',1);	// This is login page. We must be able to go on it from another web site.

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// If not defined, we select menu "home"
$_GET['mainmenu']=GETPOST('mainmenu', 'alpha')?GETPOST('mainmenu', 'alpha'):'home';
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



/*
 * View
 */

llxHeader('',$langs->trans("HomeArea"));

print_fiche_titre($langs->trans("HomeArea"));

if (! empty($conf->global->MAIN_MOTD))
{
    $conf->global->MAIN_MOTD=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i','<br>',$conf->global->MAIN_MOTD);
    if (! empty($conf->global->MAIN_MOTD))
    {
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

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th class="liste_titre" colspan="2">'.$langs->trans("Informations").'</th></tr>';
print '<tr '.$bc[false].'>';
print '<td class="nowrap">'.$langs->trans("User").'</td><td>'.$user->getNomUrl(0).'</td></tr>';
print '<tr '.$bc[true].'>';
print '<td class="nowrap">'.$langs->trans("PreviousConnexion").'</td><td>';
if ($user->datepreviouslogin) print dol_print_date($user->datepreviouslogin,"dayhour",'tzuser');
else print $langs->trans("Unknown");
print '</td>';
print "</tr>\n";
print "</table>\n";


/*
 * Dashboard Dolibarr states (statistics)
 * Hidden for external users
 */
$langs->load("commercial");
$langs->load("bills");
$langs->load("orders");
$langs->load("contracts");

if ($user->societe_id == 0)
{
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<th class="liste_titre" colspan="2">'.$langs->trans("DolibarrStateBoard").'</th>';
    print '<th class="liste_titre" align="right">&nbsp;</th>';
    print '</tr>';

    $var=true;

    // Condition to be checked for each display line dashboard
    $conditions=array(
    ! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS),
    ! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS),
    ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS),
    ! empty($conf->adherent->enabled) && $user->rights->adherent->lire,
    ! empty($conf->product->enabled) && $user->rights->produit->lire,
    ! empty($conf->service->enabled) && $user->rights->service->lire,
    ! empty($conf->propal->enabled) && $user->rights->propale->lire,
    ! empty($conf->commande->enabled) && $user->rights->commande->lire,
    ! empty($conf->facture->enabled) && $user->rights->facture->lire,
    ! empty($conf->contrat->enabled) && $user->rights->contrat->activer);
    // Class file containing the method load_state_board for each line
    $includes=array(DOL_DOCUMENT_ROOT."/societe/class/client.class.php",
    DOL_DOCUMENT_ROOT."/comm/prospect/class/prospect.class.php",
    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php",
    DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php",
    DOL_DOCUMENT_ROOT."/product/class/product.class.php",
    DOL_DOCUMENT_ROOT."/product/class/service.class.php",
    DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php",
    DOL_DOCUMENT_ROOT."/commande/class/commande.class.php",
    DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php",
    DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    // Name class containing the method load_state_board for each line
    $classes=array('Client',
                   'Prospect',
                   'Fournisseur',
                   'Adherent',
                   'Product',
                   'Service',
				   'Propal',
				   'Commande',
				   'Facture',
                   'Contrat');
    // Cle array returned by the method load_state_board for each line
    $keys=array('customers',
                'prospects',
                'suppliers',
                'members',
                'products',
                'services',
				'proposals',
				'orders',
				'invoices',
				'Contracts');
    // Dashboard Icon lines
    $icons=array('company',
                 'company',
                 'company',
                 'user',
                 'product',
                 'service',
				 'propal',
				 'order',
				 'bill',
				 'order');
    // Translation keyword
    $titres=array("ThirdPartyCustomersStats",
                  "ThirdPartyProspectsStats",
                  "Suppliers",
                  "Members",
                  "Products",
                  "Services",
                  "CommercialProposals",
                  "CustomersOrders",
                  "BillsCustomers",
                  "Contracts");
    // Dashboard Link lines
    $links=array(DOL_URL_ROOT.'/comm/list.php',
    DOL_URL_ROOT.'/comm/prospect/list.php',
    DOL_URL_ROOT.'/fourn/liste.php',
    DOL_URL_ROOT.'/adherents/liste.php?statut=1&mainmenu=members',
    DOL_URL_ROOT.'/product/liste.php?type=0&mainmenu=products',
    DOL_URL_ROOT.'/product/liste.php?type=1&mainmenu=products',
    DOL_URL_ROOT.'/comm/propal/list.php?mainmenu=commercial',
    DOL_URL_ROOT.'/commande/liste.php?mainmenu=commercial',
    DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=accountancy',
    DOL_URL_ROOT.'/contrat/liste.php');
    // Translation lang files
    $langfile=array("companies",
                    "prospects",
                    "suppliers",
                    "members",
                    "products",
                    "produts",
                    "propal",
                    "orders",
                    "bills",
					"Contracts");


    // Loop and displays each line of table
    foreach ($keys as $key=>$val)
    {
        if ($conditions[$key])
        {
            $classe=$classes[$key];
            // Search in cache if load_state_board is already realized
            if (! isset($boardloaded[$classe]) || ! is_object($boardloaded[$classe]))
            {
                include_once $includes[$key];

                $board=new $classe($db);
                $board->load_state_board($user);
                $boardloaded[$classe]=$board;
            }
            else $board=$boardloaded[$classe];

            $var=!$var;
            if ($langfile[$key]) $langs->load($langfile[$key]);
            $title=$langs->trans($titres[$key]);
            print '<tr '.$bc[$var].'><td width="16">'.img_object($title,$icons[$key]).'</td>';
            print '<td>'.$title.'</td>';
            print '<td align="right"><a href="'.$links[$key].'">'.$board->nb[$val].'</a></td>';
            print '</tr>';
        }
    }

    $object=new stdClass();
    $parameters=array();
    $action='';
    $reshook=$hookmanager->executeHooks('addStatisticLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

    print '</table>';
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Dolibarr Working Board with weather
 */
$showweather=empty($conf->global->MAIN_DISABLE_METEO)?1:0;
$rowspan=0;
$dashboardlines=array();

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th class="liste_titre"colspan="2">'.$langs->trans("DolibarrWorkBoard").'</th>';
print '<th class="liste_titre"align="right">'.$langs->trans("Number").'</th>';
print '<th class="liste_titre"align="right">'.$langs->trans("Late").'</th>';
print '<th class="liste_titre">&nbsp;</th>';
print '<th class="liste_titre"width="20">&nbsp;</th>';
if ($showweather) print '<th class="liste_titre hideonsmartphone" width="80">&nbsp;</th>';
print '</tr>';


//
// Do not include sections without management permission
//

// Number of actions to do (late)
if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read)
{
    include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    $board=new ActionComm($db);
    $board->load_board($user);
    $board->warning_delay=$conf->actions->warning_delay/60/60/24;
    $board->label=$langs->trans("ActionsToDo");
    $board->url=DOL_URL_ROOT.'/comm/action/listactions.php?status=todo&mainmenu=agenda';
    $board->img=img_object($langs->trans("Actions"),"action");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of customer orders a deal
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    $board=new Commande($db);
    $board->load_board($user);
    $board->warning_delay=$conf->commande->client->warning_delay/60/60/24;
    $board->label=$langs->trans("OrdersToProcess");
    $board->url=DOL_URL_ROOT.'/commande/liste.php?viewstatut=-3';
    $board->img=img_object($langs->trans("Orders"),"order");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of suppliers orders a deal
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
    $board=new CommandeFournisseur($db);
    $board->load_board($user);
    $board->warning_delay=$conf->commande->fournisseur->warning_delay/60/60/24;
    $board->label=$langs->trans("SuppliersOrdersToProcess");
    $board->url=DOL_URL_ROOT.'/fourn/commande/index.php';
    $board->img=img_object($langs->trans("Orders"),"order");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of commercial proposals opened (expired)
if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
{
    $langs->load("propal");

    include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
    $board=new Propal($db);
    $board->load_board($user,"opened");
    $board->warning_delay=$conf->propal->cloture->warning_delay/60/60/24;
    $board->label=$langs->trans("PropalsToClose");
    $board->url=DOL_URL_ROOT.'/comm/propal/list.php?viewstatut=1';
    $board->img=img_object($langs->trans("Propals"),"propal");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of commercial proposals CLOSED signed (billed)
if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
{
    $langs->load("propal");

    include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
    $board=new Propal($db);
    $board->load_board($user,"signed");
    $board->warning_delay=$conf->propal->facturation->warning_delay/60/60/24;
    $board->label=$langs->trans("PropalsToBill");
    $board->url=DOL_URL_ROOT.'/comm/propal/list.php?viewstatut=2';
    $board->img=img_object($langs->trans("Propals"),"propal");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of services enabled (delayed)
if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
{
    $langs->load("contracts");

    include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
    $board=new Contrat($db);
    $board->load_board($user,"inactives");
    $board->warning_delay=$conf->contrat->services->inactifs->warning_delay/60/60/24;
    $board->label=$langs->trans("BoardNotActivatedServices");
    $board->url=DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&amp;leftmenu=contracts&amp;mode=0';
    $board->img=img_object($langs->trans("Contract"),"contract");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of active services (expired)
if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
{
    $langs->load("contracts");

    include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
    $board=new Contrat($db);
    $board->load_board($user,"expired");
    $board->warning_delay=$conf->contrat->services->expires->warning_delay/60/60/24;
    $board->label=$langs->trans("BoardRunningServices");
    $board->url=DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&amp;leftmenu=contracts&amp;mode=4&amp;filter=expired';
    $board->img=img_object($langs->trans("Contract"),"contract");
    $rowspan++;
    $dashboardlines[]=$board;
}
// Number of invoices customers (has paid)
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
    $langs->load("bills");

    include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    $board=new Facture($db);
    $board->load_board($user);
    $board->warning_delay=$conf->facture->client->warning_delay/60/60/24;
    $board->label=$langs->trans("CustomerBillsUnpaid");
    $board->url=DOL_URL_ROOT.'/compta/facture/impayees.php';
    $board->img=img_object($langs->trans("Bills"),"bill");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of supplier invoices (has paid)
if (! empty($conf->fournisseur->enabled) && ! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
    $langs->load("bills");

    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
    $board=new FactureFournisseur($db);
    $board->load_board($user);
    $board->warning_delay=$conf->facture->fournisseur->warning_delay/60/60/24;
    $board->label=$langs->trans("SupplierBillsToPay");
    $board->url=DOL_URL_ROOT.'/fourn/facture/index.php?filtre=paye:0';
    $board->img=img_object($langs->trans("Bills"),"bill");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of transactions to conciliate
if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id)
{
    $langs->load("banks");

    include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
    $board=new Account($db);
    $found=$board->load_board($user);
    if ($found > 0)
    {
        $board->warning_delay=$conf->bank->rappro->warning_delay/60/60/24;
        $board->label=$langs->trans("TransactionsToConciliate");
        $board->url=DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank';
        $board->img=img_object($langs->trans("TransactionsToConciliate"),"payment");
        $rowspan++;
        $dashboardlines[]=$board;
    }
}

// Number of cheque to send
if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id)
{
    $langs->load("banks");

    include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
    $board=new RemiseCheque($db);
    $board->load_board($user);
    $board->warning_delay=$conf->bank->cheque->warning_delay/60/60/24;
    $board->label=$langs->trans("BankChecksToReceipt");
    $board->url=DOL_URL_ROOT.'/compta/paiement/cheque/index.php?leftmenu=checks&mainmenu=accountancy';
    $board->img=img_object($langs->trans("BankChecksToReceipt"),"payment");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Number of foundation members
if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire && ! $user->societe_id)
{
    $langs->load("members");

    include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
    $board=new Adherent($db);
    $board->load_board($user);
    $board->warning_delay=$conf->adherent->cotisation->warning_delay/60/60/24;
    $board->label=$langs->trans("MembersWithSubscriptionToReceive");
    $board->url=DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1';
    $board->img=img_object($langs->trans("Members"),"user");
    $rowspan++;
    $dashboardlines[]=$board;
}

// Calculate total nb of late
$totallate=0;
foreach($dashboardlines as $key => $board)
{
    if ($board->nbtodolate > 0) $totallate+=$board->nbtodolate;
}

// Show dashboard
$var=true;
foreach($dashboardlines as $key => $board)
{
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.$board->img.'</td><td>'.$board->label.'</td>';
    print '<td align="right"><a href="'.$board->url.'">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.$board->url.'">';
    print $board->nbtodolate;
    print '</a></td>';
    print '<td align="left">';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning");
    else print '&nbsp;';
    print '</td>';
    print '<td class="nowrap" align="right">';
    print ' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')';
    print '</td>';
    if ($showweather)
    {
        print '<td class="nohover hideonsmartphone" rowspan="'.$rowspan.'" width="80" style="border-left: 1px solid #DDDDDD" align="center">';
        $text='';
        if ($totallate > 0) $text=$langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv("NActionsLate",$totallate).')';
        $options='height="64px"';
        if ($rowspan <= 2) $options='height="24"';  // Weather logo is smaller if dashboard has few elements
        else if ($rowspan <= 3) $options='height="48"';  // Weather logo is smaller if dashboard has few elements
        print showWeather($totallate,$text,$options);
        //print showWeather(0,'');
        //print showWeather(40,$text);
        print '</td>';
        $showweather=0;
    }
    print '</tr>';
    print "\n";
}


print '</table>';   // End table array


print '</div></div></div><div class="fichecenter"><br>';



/*
 * Show boxes
 */

FormOther::printBoxesArea($user,"0");


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
 *  $conf->global->MAIN_METEO_OFFSET
 *  $conf->global->MAIN_METEO_GAP
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
    $cursor=10; // By default
    //if (! empty($conf->global->MAIN_METEO_OFFSET)) $offset=$conf->global->MAIN_METEO_OFFSET;
    //if (! empty($conf->global->MAIN_METEO_GAP)) $cursor=$conf->global->MAIN_METEO_GAP;
    $level0=$offset;           if (! empty($conf->global->MAIN_METEO_LEVEL0)) $level0=$conf->global->MAIN_METEO_LEVEL0;
    $level1=$offset+1*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL1)) $level1=$conf->global->MAIN_METEO_LEVEL1;
    $level2=$offset+2*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL2)) $level2=$conf->global->MAIN_METEO_LEVEL2;
    $level3=$offset+3*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL3)) $level3=$conf->global->MAIN_METEO_LEVEL3;

    if ($totallate <= $level0) $out.=img_picto_common($text,'weather/weather-clear.png',$options);
    if ($totallate > $level0 && $totallate <= $level1) $out.=img_picto_common($text,'weather/weather-few-clouds.png',$options);
    if ($totallate > $level1 && $totallate <= $level2) $out.=img_picto_common($text,'weather/weather-clouds.png',$options);
    if ($totallate > $level2 && $totallate <= $level3) $out.=img_picto_common($text,'weather/weather-many-clouds.png',$options);
    if ($totallate > $level3) $out.=img_picto_common($text,'weather/weather-storm.png',$options);
    return $out;
}
?>
