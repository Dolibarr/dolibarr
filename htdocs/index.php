<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 *	\version    $Id$
 */

define('NOCSRFCHECK',1);	// This is login page. We must be able to go on it from another web site.

require("./main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/boxes.php");


// If not defined, we select menu "home"
if (! isset($_GET["mainmenu"])) $_GET["mainmenu"]="home";


/*
 * Actions
 */

// No actions



/*
 * View
 */

llxHeader();

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

print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" class="notopnoleft">';


/*
 * Informations area
 */

if (file_exists(DOL_DOCUMENT_ROOT.'/logo.png'))
{
	print '<table class="noborder" width="100%">';
	print '<tr><td colspan="3" style="text-align:center;">';
	print '<img src="/logo.png"></td></tr>';
	print "</table><br>\n";
}


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Informations").'</td></tr>';
print '<tr '.$bc[false].'>';
$userstring=$user->getFullName($langs);
print '<td nowrap>'.$langs->trans("User").'</td><td>'.$userstring.'</td></tr>';
print '<tr '.$bc[true].'>';
print '<td nowrap>'.$langs->trans("PreviousConnexion").'</td><td>';
if ($user->datepreviouslogin) print dol_print_date($user->datepreviouslogin,"dayhour");
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
if ($user->societe_id == 0)
{
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("DolibarrStateBoard").'</td>';
	print '<td align="right">&nbsp;</td>';
	print '</tr>';

	$var=true;

	// Condition to be checked for each display line dashboard
	$conditions=array(
	! empty($conf->societe->enabled) && $user->rights->societe->lire,
	! empty($conf->societe->enabled) && $user->rights->societe->lire,
	! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire,
	! empty($conf->adherent->enabled) && $user->rights->adherent->lire,
	! empty($conf->product->enabled) && $user->rights->produit->lire,
	! empty($conf->service->enabled) && $user->rights->service->lire,
	! empty($conf->propal->enabled) && $user->rights->propale->lire,
	! empty($conf->commande->enabled) && $user->rights->commande->lire,
	! empty($conf->facture->enabled) && $user->rights->facture->lire,
	! empty($conf->telephonie->enabled) && $user->rights->telephonie->lire,
	! empty($conf->societe->enabled) && $user->rights->contrat->activer);
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
	$titres=array("Customers",
                  "Prospects",
                  "Suppliers",
                  "Members",
                  "Products",
                  "Services",
                  "CommercialProposals",
                  "CustomersOrders",
                  "BillsCustomers",
                  "Contracts");
	// Dashboard Link lines
	$links=array(DOL_URL_ROOT.'/comm/clients.php',
	DOL_URL_ROOT.'/comm/prospect/prospects.php',
	DOL_URL_ROOT.'/fourn/index.php',
	DOL_URL_ROOT.'/adherents/liste.php?statut=1&amp;mainmenu=members',
	DOL_URL_ROOT.'/product/liste.php?type=0&amp;mainmenu=products',
	DOL_URL_ROOT.'/product/liste.php?type=1&amp;mainmenu=products',
	DOL_URL_ROOT.'/comm/propal.php?mainmenu=commercial',
	DOL_URL_ROOT.'/commande/liste.php?mainmenu=commercial',
	DOL_URL_ROOT.'/compta/facture.php?mainmenu=accountancy',
	DOL_URL_ROOT.'/contrat/liste.php');
	// Translation lang files
	$langfile=array("bills",
                    "prospects",
                    "suppliers",
                    "members",
                    "products",
                    "produts",
                    "propal",
                    "orders",
                    "bills",
					"Contracts");

	//print memory_get_usage()."<br>";

	// Loop and displays each line of table
	foreach ($keys as $key=>$val)
	{
		if ($conditions[$key])
		{
			$classe=$classes[$key];
			// Search in cache if load_state_board is already realized
			if (! isset($boardloaded[$classe]) || ! is_object($boardloaded[$classe]))
			{
				include_once($includes[$key]);

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

			//print $includes[$key].' '.memory_get_usage()."<br>";
		}
	}

	print '</table>';
}

print '</td><td width="65%" valign="top" class="notopnoleftnoright">';


/*
 * Dolibarr Working Board
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("DolibarrWorkBoard").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("Late").'</td>';
print '<td>&nbsp;</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

$nboflate=0;
$var=true;

//
// Do not include sections without management permission
//

// Number actions to do (late)
if ($conf->agenda->enabled && $user->rights->agenda->myactions->read)
{
	include_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
	$board=new ActionComm($db);
	$board->load_board($user);
	$board->warning_delay=$conf->actions->warning_delay/60/60/24;
	$board->label=$langs->trans("ActionsToDo");

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Actions"),"task").'</td><td>'.$board->label.'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?status=todo">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?status=todo">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number customer orders a deal
if ($conf->commande->enabled && $user->rights->commande->lire)
{
	include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
	$board=new Commande($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Orders"),"order").'</td><td>'.$langs->trans("OrdersToProcess").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=-2">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=-2">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->commande->traitement->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number propale open (expired)
if ($conf->propal->enabled && $user->rights->propale->lire)
{
	$langs->load("propal");

	include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
	$board=new Propal($db);
	$board->load_board($user,"opened");

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Propals"),"propal").'</td><td>'.$langs->trans("PropalsToClose").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=1">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=1">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->propal->cloture->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
}

// Number propale CLOSED signed (billed)
if ($conf->propal->enabled && $user->rights->propale->lire)
{
	$langs->load("propal");

	include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
	$board=new Propal($db);
	$board->load_board($user,"signed");

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Propals"),"propal").'</td><td>'.$langs->trans("PropalsToBill").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=2">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=2">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->propal->facturation->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number services is enabled (delayed)
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
	$langs->load("contracts");

	include_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
	$board=new Contrat($db);
	$board->load_board($user,"inactives");

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Contract"),"contract").'</td><td>'.$langs->trans("BoardNotActivatedServices").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->contrat->services->inactifs->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number of active services (expired)
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
	$langs->load("contracts");

	include_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
	$board=new Contrat($db);
	$board->load_board($user,"expired");

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Contract"),"contract").'</td><td>'.$langs->trans("BoardRunningServices").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4&filter=expired">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4&filter=expired">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->contrat->services->expires->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number of supplier invoices (has paid)
if ($conf->fournisseur->enabled && $conf->facture->enabled && $user->rights->facture->lire)
{
	$langs->load("bills");

	include_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
	$board=new FactureFournisseur($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("SupplierBillsToPay").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/facture/index.php?filtre=paye:0">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/fourn/facture/index.php?filtre=paye:0">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->facture->fournisseur->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number invoices customers (has paid)
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	$langs->load("bills");

	include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
	$board=new Facture($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("CustomerBillsUnpaid").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->facture->client->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number Scripture closer
if ($conf->banque->enabled && $user->rights->banque->lire && ! $user->societe_id)
{
	$langs->load("banks");

	include_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
	$board=new Account($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("TransactionsToConciliate"),"payment").'</td><td>'.$langs->trans("TransactionsToConciliate").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->bank->rappro->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Number Scripture closer
if ($conf->banque->enabled && $user->rights->banque->lire && ! $user->societe_id)
{
	$langs->load("banks");

	include_once(DOL_DOCUMENT_ROOT."/compta/paiement/cheque/class/remisecheque.class.php");
	$board=new RemiseCheque($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("BankChecksToReceipt"),"payment").'</td><td>'.$langs->trans("BankChecksToReceipt").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/index.php?leftmenu=checks&mainmenu=accountancy">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/index.php?leftmenu=checks&mainmenu=accountancy">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->bank->cheque->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

// Participant Number valid (awaiting assessment)
if ($conf->adherent->enabled && $user->rights->adherent->lire && ! $user->societe_id)
{
	$langs->load("members");

	include_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
	$board=new Adherent($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Members"),"user").'</td><td>'.$langs->trans("Members").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1">'.$board->nbtodo.'</a></td>';
	print '<td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1">';
	print $board->nbtodolate;
	print '</a></td><td nowrap align="right">';
	print ' (>'.ceil($conf->adherent->cotisation->warning_delay/60/60/24).' '.$langs->trans("days").')';
	print '</td>';
	print '<td>';
	if ($board->nbtodolate > 0) { print img_picto($langs->trans("NActionsLate",$board->nbtodolate),"warning"); $nboflate+=$board->nbtodolate; }
	else print '&nbsp;';
	print '</td>';
	print '</tr>';
	print "\n";
}

print '</table>';

if ($nboflate > 0)
{
	print '<br>';
	//print '<table width="100%" class="border"><tr><td>';
	print '<div class="warning">'.img_picto($langs->trans("Alert"),'warning').' '.$langs->trans("WarningYouHaveAtLeastOneTaskLate").'</div>';
	//print '</td></tr></table>';
}

print '</td></tr></table>';
print '<br>';


/*
 * Show boxes
 */

printBoxesArea($user,"0");



/*
 * Show security warnings
 */

// Security warning repertoire install existe (si utilisateur admin)
if ($user->admin && empty($conf->global->MAIN_REMOVE_INSTALL_WARNING))
{
	$message='';

	// Install lock missing
	if (! file_exists('../install.lock') && is_dir(DOL_DOCUMENT_ROOT."/install"))
	{
		$langs->load("other");
		//if (! empty($message)) $message.='<br>';
		$message.=info_admin($langs->trans("WarningInstallDirExists",DOL_DOCUMENT_ROOT."/install").' '.$langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
	}

	// Conf files must be in read only mode
	if (is_writable(DOL_DOCUMENT_ROOT.'/conf/conf.php'))
	{
		$langs->load("errors");
		$langs->load("other");
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


$db->close();

llxFooter('$Date$ - $Revision$');
?>