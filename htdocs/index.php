<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin	<regis.houssin@capnetworks.com>
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

if (GETPOST('addbox'))	// Add box (when submit is done from a form when ajax disabled)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone=GETPOST('areacode');
	$userid=GETPOST('userid');
	$boxorder=GETPOST('boxorder');
	$boxorder.=GETPOST('boxcombo');

	$result=InfoBox::saveboxorder($db,$zone,$boxorder,$userid);
}




/*
 * View
 */

// Title
$title=$langs->trans("HomeArea").' - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$langs->trans("HomeArea").' - '.$conf->global->MAIN_APPLICATION_TITLE;

llxHeader('',$title);

print_fiche_titre($langs->trans("HomeArea"));

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

//print memory_get_usage();
if (empty($user->societe_id))
{
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<th class="liste_titre" colspan="2">'.$langs->trans("DolibarrStateBoard").'</th>';
    print '<th class="liste_titre" align="right">&nbsp;</th>';
    print '</tr>';
    print '<tr class="impair"><td colspan="3" class="impair tdboxstats nohover">';

    $var=true;

    $object=new stdClass();
    $parameters=array();
    $action='';
    $reshook=$hookmanager->executeHooks('addStatisticLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

    if (empty($reshook))
    {
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
	    ! empty($conf->contrat->enabled) && $user->rights->contrat->activer,
		! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire,
		! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire);
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
	    DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php",
	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php",
	    DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
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
	                   'Contrat',
	                   'CommandeFournisseur',
	                   'FactureFournisseur');
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
	                'Contracts',
	                'supplier_orders',
	                'supplier_invoices');
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
	                 'order',
	                 'order',
	                 'bill');
	    // Translation keyword
	    $titres=array("ThirdPartyCustomersStats",
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
	                  "SuppliersInvoices");
	    // Dashboard Link lines
	    $links=array(DOL_URL_ROOT.'/comm/list.php',
	    DOL_URL_ROOT.'/comm/prospect/list.php',
	    DOL_URL_ROOT.'/fourn/list.php',
	    DOL_URL_ROOT.'/adherents/list.php?statut=1&mainmenu=members',
	    DOL_URL_ROOT.'/product/list.php?type=0&mainmenu=products',
	    DOL_URL_ROOT.'/product/list.php?type=1&mainmenu=products',
	    DOL_URL_ROOT.'/comm/propal/list.php?mainmenu=commercial',
	    DOL_URL_ROOT.'/commande/list.php?mainmenu=commercial',
	    DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=accountancy',
	    DOL_URL_ROOT.'/contrat/list.php',
	    DOL_URL_ROOT.'/fourn/commande/list.php',
	    DOL_URL_ROOT.'/fourn/facture/list.php');
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
						"contracts");


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
	            if ($langfile[$key]) $langs->load($langfile[$key]);
	            $text=$langs->trans($titres[$key]);
	            print '<div class="boxstats">';
	            print '<a href="'.$links[$key].'" class="nobold nounderline">';
	            print img_object("",$icons[$key]).' '.$text.'<br>';
	            print '</a>';
	            print '<a href="'.$links[$key].'">';
	            print $board->nb[$val];
	            print '</a>';
	            print '</div>';
	        }
	    }
    }

    print '</td></tr>';
    print '</table>';
}

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Dolibarr Working Board with weather
 */
$showweather=empty($conf->global->MAIN_DISABLE_METEO)?1:0;

//Array that contains all WorkboardResponse classes to process them
$dashboardlines=array();

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">';
print '<th class="liste_titre" colspan="2">'.$langs->trans("DolibarrWorkBoard").'</th>';
print '<th class="liste_titre" align="right">'.$langs->trans("Number").'</th>';
print '<th class="liste_titre" align="right">'.$langs->trans("Late").'</th>';
print '<th class="liste_titre">&nbsp;</th>';
print '<th class="liste_titre" width="20">&nbsp;</th>';
if ($showweather) print '<th class="liste_titre hideonsmartphone" width="80">&nbsp;</th>';
print '</tr>'."\n";


//
// Do not include sections without management permission
//

require DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

// Number of actions to do (late)
if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read)
{
    include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    $board=new ActionComm($db);

    $dashboardlines[] = $board->load_board($user);
}

// Number of customer orders a deal
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    $board=new Commande($db);

	$dashboardlines[] = $board->load_board($user);
}

// Number of suppliers orders a deal
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire)
{
    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
    $board=new CommandeFournisseur($db);

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
if (! empty($conf->fournisseur->enabled) && ! empty($conf->facture->enabled) && $user->rights->facture->lire)
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
	$dashboardlines[] = $board->load_board($user);
}

// Number of cheque to send
if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id)
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

foreach($valid_dashboardlines as $board)
{
    if ($board->nbtodolate > 0) {
	    $totallate += $board->nbtodolate;
    }

	// Show dashboard

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
