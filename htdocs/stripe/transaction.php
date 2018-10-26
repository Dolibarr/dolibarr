<?php
/* Copyright (C) 2018 	PtibogXIV        <support@ptibogxiv.net>
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

// Put here all includes required by your class file

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'salaries', 'bills', 'hrm', 'stripe'));

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'salaries', '', '', '');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$rowid = GETPOST("rowid",'alpha');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
 * View
 */

$form = new Form($db);
$societestatic = new Societe($db);
$memberstatic = new Adherent($db);
$acc = new Account($db);
$stripe = new Stripe($db);

llxHeader('', $langs->trans("StripeTransactionList"));

if (! empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox','alpha')))
{
	$service = 'StripeTest';
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}
else
{
	$service = 'StripeLive';
}

$stripeaccount = $stripe->getStripeAccount($service);
/*if (empty($stripeaccount))
{
	print $langs->trans('ErrorStripeAccountNotDefined');
}*/

if (! $rowid) {

	print '<FORM method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '')
		print '<INPUT type="hidden" name="optioncss" value="' . $optioncss . '">';
	print '<INPUT type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<INPUT type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<INPUT type="hidden" name="action" value="list">';
	print '<INPUT type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<INPUT type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<INPUT type="hidden" name="page" value="' . $page . '">';

	$title=$langs->trans("StripeTransactionList");
	$title.=($stripeaccount?' (Stripe connection with Stripe OAuth Connect account '.$stripeaccount.')':' (Stripe connection with keys from Stripe module setup)');

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy.png', 0, '', '', $limit);

	print '<DIV class="div-table-responsive">';
	print '<TABLE class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

	print '<TR class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	//print_liste_field_titre("StripeCustomerId",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
	//print_liste_field_titre("CustomerId", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Origin", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "", "", "", 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("Paid", $_SERVER["PHP_SELF"], "", "", "", 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Fee", $_SERVER["PHP_SELF"], "", "", "", 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", "", 'align="right"');
	print "</TR>\n";

	print "</TR>\n";

	if ($stripeaccount)
	{
		$txn = \Stripe\BalanceTransaction::all(array("limit" => $limit), array("stripe_account" => $stripeaccount));
	}
	else
	{
		$txn = \Stripe\BalanceTransaction::all(array("limit" => $limit));
	}

	foreach ($txn->data as $txn) 
	{
		//$charge = $txn;
		//var_dump($txn);
		
		// The metadata FULLTAG is defined by the online payment page
		/*$FULLTAG=$charge->metadata->FULLTAG;
		
		// Save into $tmparray all metadata
		$tmparray = dolExplodeIntoArray($FULLTAG,'.','=');
		// Load origin object according to metadata
		if (! empty($tmparray['CUS']))
		{
			$societestatic->fetch($tmparray['CUS']);
		}
		else
		{
			$societestatic->id = 0;
		}
		if (! empty($tmparray['MEM']))
		{
			$memberstatic->fetch($tmparray['MEM']);
		}
		else
		{
			$memberstatic->id = 0;
		}*/
		
		$societestatic->fetch($charge->metadata->idcustomer);
		$societestatic->id = $charge->metadata->idcustomer;
		$societestatic->lastname = $obj->lastname;
		$societestatic->firstname = $obj->firstname;
		$societestatic->admin = $obj->admin;
		$societestatic->login = $obj->login;
		$societestatic->email = $obj->email;
		$societestatic->societe_id = $obj->fk_soc;

		print '<TR class="oddeven">';
		
		// Ref
		print "<TD><A href='" . DOL_URL_ROOT . "/stripe/transaction.php?rowid=" . $txn->source . "'>" . $txn->source . "</A></TD>\n";
		// Stripe customer
		//print "<TD>".$charge->customer."</TD>\n";
		// Link
		/*print "<TD>";
		if ($societestatic->id > 0)
		{
			print $societestatic->getNomUrl(1);
		}
		if ($memberstatic->id > 0)
		{
			print $memberstatic->getNomUrl(1);
		}
		print "</TD>\n";*/
		// Origine
		print "<TD>";
		print $FULLTAG;
		if ($charge->metadata->source=="order"){
			$object = new Commande($db);
			$object->fetch($charge->metadata->idsource);
			print "<A href='".DOL_URL_ROOT."/commande/card.php?id=".$charge->metadata->idsource."'>".img_picto('', 'object_order')." ".$object->ref."</A>";
		} elseif ($charge->metadata->source=="invoice"){
			$object = new Facture($db);
			$object->fetch($charge->metadata->idsource);
			print "<A href='".DOL_URL_ROOT."/compta/facture/card.php?facid=".$charge->metadata->idsource."'>".img_picto('', 'object_invoice')." ".$object->ref."</A>";
		}
		print "</TD>\n";
		// Date payment
		print '<TD align="center">' . dol_print_date($txn->created, '%d/%m/%Y %H:%M') . "</TD>\n";
		// Type
		print '<TD>' . $txn->type . '</TD>';
		// Amount
		print "<TD align=\"right\">" . price(($txn->amount) / 100) . "</TD>";
		print "<TD align=\"right\">" . price(($txn->fee) / 100) . "</TD>";
		// Status
		print "<TD align='right'>";
if ($txn->status=='available')
 {print img_picto($langs->trans("".$txn->status.""),'statut4');} 
elseif ($txn->status=='pending')
 {print img_picto($langs->trans("".$txn->status.""),'statut7');}
elseif ($txn->status=='failed')
 {print img_picto($langs->trans("".$txn->status.""),'statut8');}        
		print '</TD>';
		print "</TR>\n";
	}
	print "</TABLE>";
	print '</DIV>';
	print '</FORM>';
} else {}

llxFooter();
$db->close();
