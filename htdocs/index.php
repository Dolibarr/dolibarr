<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/boxes.php");


// If not defined, we select menu "home"
if (! isset($_GET["mainmenu"])) $_GET["mainmenu"]="home";

$infobox=new InfoBox($db);

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
	$conf->global->MAIN_MOTD=eregi_replace('<br[ /]*>$','',$conf->global->MAIN_MOTD);
	if (! empty($conf->global->MAIN_MOTD))
	{
		print "\n<!-- Start of welcome text -->\n";
		print '<table width="100%" class="notopnoleftnoright"><tr><td>';
		print dol_htmlentitiesbr($conf->global->MAIN_MOTD);
		print '</td></tr></table><br>';
		print "\n<!-- End of welcome text -->\n";
	}
}

// Affiche warning répertoire install existe (si utilisateur admin)
if ($user->admin && ! defined("MAIN_REMOVE_INSTALL_WARNING"))
{
	if (is_dir(DOL_DOCUMENT_ROOT."/install") && ! file_exists('../install.lock'))
	{
		$langs->load("other");
		$message=$langs->trans("WarningInstallDirExists",DOL_DOCUMENT_ROOT."/install");
		$message.=$langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install");
		print info_admin($message);
		print "<br>\n";
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
	print "</table><br />\n";
}


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Informations").'</td></tr>';
print '<tr '.$bc[false].'>';
$userstring=$user->fullname;
print '<td nowrap>'.$langs->trans("User").'</td><td>'.$userstring.'</td></tr>';
print '<tr '.$bc[true].'>';
print '<td nowrap>'.$langs->trans("PreviousConnexion").'</td><td>';
if ($user->datepreviouslogin) print dol_print_date($user->datepreviouslogin,"dayhour");
else print $langs->trans("Unknown");
print '</td>';
print "</tr>\n";
print "</table>\n";


/*
 * Tableau de bord d'états Dolibarr (statistiques)
 * Non affiché pour un utilisateur externe
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

	// Condition à vérifier pour affichage de chaque ligne du tableau de bord
	$conditions=array(
	! empty($conf->societe->enabled) && $user->rights->societe->lire,
	! empty($conf->societe->enabled) && $user->rights->societe->lire,
	! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire,
	! empty($conf->adherent->enabled) && $user->rights->adherent->lire,
	! empty($conf->produit->enabled) && $user->rights->produit->lire,
	! empty($conf->service->enabled) && $user->rights->service->lire,
	! empty($conf->propal->enabled) && $user->rights->propale->lire,
	! empty($conf->commande->enabled) && $user->rights->commande->lire,
	! empty($conf->facture->enabled) && $user->rights->facture->lire,
	! empty($conf->telephonie->enabled) && $user->rights->telephonie->lire,
	! empty($conf->societe->enabled) && $user->rights->contrat->activer);
	// Fichier des classes qui contiennent la methode load_state_board pour chaque ligne
	$includes=array(DOL_DOCUMENT_ROOT."/client.class.php",
	DOL_DOCUMENT_ROOT."/prospect.class.php",
	DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php",
	DOL_DOCUMENT_ROOT."/adherents/adherent.class.php",
	DOL_DOCUMENT_ROOT."/product.class.php",
	DOL_DOCUMENT_ROOT."/service.class.php",
	DOL_DOCUMENT_ROOT."/propal.class.php",
	DOL_DOCUMENT_ROOT."/commande/commande.class.php",
	DOL_DOCUMENT_ROOT."/facture.class.php",
	DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php",
	DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
	// Nom des classes qui contiennent la methode load_state_board pour chaque ligne
	$classes=array('Client',
                   'Prospect',
                   'Fournisseur',
                   'Adherent',
                   'Product',
                   'Service',
				   'Propal',
				   'Commande',
				   'Facture',
                   'LigneTel',
                   'Contrat');
	// Clé de tableau retourné par la methode load_state_board pour chaque ligne
	$keys=array('customers',
                'prospects',
                'suppliers',
                'members',
                'products',
                'services',
				'proposals',
				'orders',
				'invoices',
                'sign',
				'Contracts');
	// Icon des lignes du tableau de bord
	$icons=array('company',
                 'company',
                 'company',
                 'user',
                 'product',
                 'service',
				 'propal',
				 'order',
				 'bill',
                 'phoning',
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
                  "Lignes de telephonie suivis",
                  "Contracts");
	// Lien des lignes du tableau de bord
	$links=array(DOL_URL_ROOT.'/comm/clients.php',
	DOL_URL_ROOT.'/comm/prospect/prospects.php',
	DOL_URL_ROOT.'/fourn/index.php',
	DOL_URL_ROOT.'/adherents/liste.php?statut=1&amp;mainmenu=members',
	DOL_URL_ROOT.'/product/liste.php?type=0&amp;mainmenu=products',
	DOL_URL_ROOT.'/product/liste.php?type=1&amp;mainmenu=products',
	DOL_URL_ROOT.'/comm/propal.php?mainmenu=commercial',
	DOL_URL_ROOT.'/commande/liste.php?mainmenu=commercial',
	DOL_URL_ROOT.'/compta/facture.php?mainmenu=accountancy',
	DOL_URL_ROOT.'/telephonie/ligne/index.php',
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
                    "",
					"Contracts");

	//print memory_get_usage()."<br>";

	// Boucle et affiche chaque ligne du tableau
	foreach ($keys as $key=>$val)
	{
		if ($conditions[$key])
		{
			$classe=$classes[$key];
			// Cherche dans cache si le load_state_board deja réalisé
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
// Ne pas inclure de sections sans gestion de permissions
//

// Nbre actions à faire (en retard)
if ($conf->agenda->enabled && $user->rights->agenda->myactions->read)
{
	include_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
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

// Nbre commandes clients à traiter
if ($conf->commande->enabled && $user->rights->commande->lire)
{
	include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
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

// Nbre propales ouvertes (expirées)
if ($conf->propal->enabled && $user->rights->propale->lire)
{
	$langs->load("propal");

	include_once(DOL_DOCUMENT_ROOT."/propal.class.php");
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

// Nbre propales fermées signées (à facturer)
if ($conf->propal->enabled && $user->rights->propale->lire)
{
	$langs->load("propal");

	include_once(DOL_DOCUMENT_ROOT."/propal.class.php");
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

// Nbre services à activer (en retard)
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
	$langs->load("contracts");

	include_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
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

// Nbre services actifs (expired)
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
	$langs->load("contracts");

	include_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
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

// Nbre factures fournisseurs (à payer)
if ($conf->fournisseur->enabled && $conf->facture->enabled && $user->rights->facture->lire)
{
	$langs->load("bills");

	include_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");
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

// Nbre factures clients (à payer)
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	$langs->load("bills");

	include_once(DOL_DOCUMENT_ROOT."/facture.class.php");
	$board=new Facture($db);
	$board->load_board($user);

	$var=!$var;
	print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("CustomerBillsUnpayed").'</td>';
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

// Nbre ecritures à rapprocher
if ($conf->banque->enabled && $user->rights->banque->lire && ! $user->societe_id)
{
	$langs->load("banks");

	include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
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

// Nbre ecritures à rapprocher
if ($conf->banque->enabled && $user->rights->banque->lire && ! $user->societe_id)
{
	$langs->load("banks");

	include_once(DOL_DOCUMENT_ROOT."/compta/paiement/cheque/remisecheque.class.php");
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

// Nbre adhérent valides (attente cotisation)
if ($conf->adherent->enabled && $user->rights->adherent->lire && ! $user->societe_id)
{
	$langs->load("members");

	include_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
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
	print '<br><table width="100%" class="border"><tr><td><font class="warning">'.img_picto($langs->trans("Alert"),'warning').' '.$langs->trans("WarningYouHaveAtLeastOneTaskLate").'</font></td></tr></table>';
}

print '</td></tr></table>';


/*
 * Show boxes
 */
$boxarray=$infobox->listboxes("0",$user);       // 0=value for home page

//$boxid_left = array();
//$boxid_right = array();

if (sizeof($boxarray))
{
	print '<br>';
	print_fiche_titre($langs->trans("OtherInformationsBoxes"),'','');
	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td class="notopnoleftnoright">'."\n";

	print '<table width="100%" style="border-collapse: collapse; border: 0px; margin: 0px; padding: 0px;"><tr>';

	// Affichage colonne gauche
	print '<td width="50%" valign="top">'."\n";

	print "\n<!-- Box left container -->\n";
	print '<div id="left">'."\n";

	$ii=0;
	foreach ($boxarray as $key => $box)
	{
		if (eregi('^A',$box->box_order)) // colonne A
		{
			$ii++;
			//print 'box_id '.$boxarray[$ii]->box_id.' ';
			//print 'box_order '.$boxarray[$ii]->box_order.'<br>';
			//$boxid_left[$key] = $box->box_id;
			// Affichage boite key
			$box->loadBox($conf->box_max_lines);
			$box->showBox();
		}
	}

	// If no box on left, we add an invisible empty box
	if ($ii==0)
	{
		$emptybox=new ModeleBoxes($db);
		$emptybox->box_id='A';
		$emptybox->info_box_head=array();
		$emptybox->info_box_contents=array();
		$emptybox->showBox(array(),array());
	}

	print "</div>\n";
	print "<!-- End box container -->\n";

	print "</td>\n";
	// Affichage colonne droite
	print '<td width="50%" valign="top">';

	print "\n<!-- Box right container -->\n";
	print '<div id="right">'."\n";

	$ii=0;
	foreach ($boxarray as $key => $box)
	{
		if (eregi('^B',$box->box_order)) // colonne B
		{
			$ii++;
			//print 'box_id '.$boxarray[$ii]->box_id.' ';
			//print 'box_order '.$boxarray[$ii]->box_order.'<br>';
			//$boxid_right[$key] = $boxarray[$key]->box_id;
			// Affichage boite key
			$box->loadBox($conf->box_max_lines);
			$box->showBox();
		}
	}

	// If no box on right, we show add an invisible empty box
	if ($ii==0)
	{
		$emptybox=new ModeleBoxes($db);
		$emptybox->box_id='B';
		$emptybox->info_box_head=array();
		$emptybox->info_box_contents=array();
		$emptybox->showBox(array(),array());
	}

	print "</div>\n";
	print "<!-- End box container -->\n";
	print "</td>";
	print "</tr></table>\n";
	print "\n";

	print "</td></tr>";
	print "</table>";

	if ($conf->use_javascript_ajax)
	{
		print "\n";
		print '<script type="text/javascript" language="javascript">
		function updateOrder(){
	    var left_list = cleanSerialize(Sortable.serialize(\'left\'));
	    var right_list = cleanSerialize(Sortable.serialize(\'right\'));
	    var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
	    //alert( \'boxorder=\' + boxorder );
	    var userid = \''.$user->id.'\';
	    var url = "ajaxbox.php";
	    o_options = new Object();
	    o_options = {asynchronous:true,method: \'get\',parameters: \'boxorder=\' + boxorder + \'&userid=\' + userid};
	    var myAjax = new Ajax.Request(url, o_options);
	  }'."\n";
		print '// <![CDATA['."\n";

		print 'Sortable.create(\'left\', {'."\n";
		print 'tag:\'div\', '."\n";
		print 'containment:["left","right"], '."\n";
		print 'constraint:false, '."\n";
		print "handle: 'boxhandle',"."\n";
		print 'onUpdate:updateOrder';
		print "});\n";

		print 'Sortable.create(\'right\', {'."\n";
		print 'tag:\'div\', '."\n";
		print 'containment:["right","left"], '."\n";
		print 'constraint:false, '."\n";
		print "handle: 'boxhandle',"."\n";
		print 'onUpdate:updateOrder';
		print "});\n";

		print '// ]]>'."\n";
		print '</script>'."\n";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>