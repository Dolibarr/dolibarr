<?php
/* Copyright (C) 2018       Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
	$servicestatus = '0';
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}
else
{
	$service = 'StripeLive';
	$servicestatus = '1';
}

$stripeacc = $stripe->getStripeAccount($service);
/*if (empty($stripeaccount))
{
	print $langs->trans('ErrorStripeAccountNotDefined');
}*/

if (! $rowid) {

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '')
		print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<input type="hidden" name="page" value="' . $page . '">';

	$title=$langs->trans("StripeTransactionList");
	$title.=($stripeaccount?' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')':' (Stripe connection with keys from Stripe module setup)');

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy.png', 0, '', '', $limit);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	//print_liste_field_titre("StripeCustomerId",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
	//print_liste_field_titre("CustomerId", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	//print_liste_field_titre("Origin", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "", "", "", 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("Paid", $_SERVER["PHP_SELF"], "", "", "", 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Fee", $_SERVER["PHP_SELF"], "", "", "", 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", "", 'align="right"');
	print "</tr>\n";

	print "</tr>\n";

	if ($stripeacc)
	{
		$txn = \Stripe\BalanceTransaction::all(array("limit" => $limit), array("stripe_account" => $stripeacc));
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

		print '<tr class="oddeven">';

		// Ref
        if (!empty($stripeacc)) $connect=$stripeacc.'/';
    
		// Ref
        if (preg_match('/po_/i', $txn->source)){
            $origin="payouts";
        } elseif (preg_match('/fee_/i', $txn->source)) {
            $origin="connect/application_fees";
        } else {
            $origin="payments";
        }

		$url='https://dashboard.stripe.com/'.$connect.'test/'.$origin.'/'.$txn->source;
		if ($servicestatus) {
			$url='https://dashboard.stripe.com/'.$connect.$origin.'/'.$txn->source;
		}
        if ($txn->type == 'stripe_fee' || $txn->type == 'reserve_transaction') {
            print "<td>".$txn->type."</td>";
        } else {
            print "<td><a href='".$url."' target='_stripe'>".img_picto($langs->trans('ShowInStripe'), 'object_globe')." " . $txn->source . "</a></td>\n";
        }

		// Stripe customer
		//print "<td>".$charge->customer."</td>\n";
		// Link
		/*print "<td>";
		if ($societestatic->id > 0)
		{
			print $societestatic->getNomUrl(1);
		}
		if ($memberstatic->id > 0)
		{
			print $memberstatic->getNomUrl(1);
		}
		print "</td>\n";*/
		// Origine
		//print "<td>";
		////if ($charge->metadata->dol_type=="order"){
		//	$object = new Commande($db);
		//	$object->fetch($charge->metadata->dol_id);
		//	print "<a href='".DOL_URL_ROOT."/commande/card.php?id=".$charge->metadata->dol_id."'>".img_picto('', 'object_order')." ".$object->ref."</a>";
		//} elseif ($charge->metadata->dol_type=="invoice"){
		//	$object = new Facture($db);
		//	$object->fetch($charge->metadata->dol_id);
		//	print "<a href='".DOL_URL_ROOT."/compta/facture/card.php?facid=".$charge->metadata->dol_id."'>".img_picto('', 'object_invoice')." ".$object->ref."</a>";
		//}
		//print "</td>\n";
		// Date payment
		print '<td align="center">' . dol_print_date($txn->created, '%d/%m/%Y %H:%M') . "</td>\n";
		// Type
		print '<td>' . $txn->type . '</td>';
		// Amount
		print "<td align=\"right\">" . price(($txn->amount) / 100, 0, '', 1, - 1, - 1, strtoupper($txn->currency)) . "</td>";
		print "<td align=\"right\">" . price(($txn->fee) / 100, 0, '', 1, - 1, - 1, strtoupper($txn->currency)) . "</td>";
		// Status
		print "<td align='right'>";
		if ($txn->status=='available')
 		{print img_picto($langs->trans("".$txn->status.""),'statut4');}
		elseif ($txn->status=='pending')
		{print img_picto($langs->trans("".$txn->status.""),'statut7');}
		elseif ($txn->status=='failed')
		{print img_picto($langs->trans("".$txn->status.""),'statut8');}
		print '</td>';
		print "</tr>\n";
	}
	print "</table>";
	print '</div>';
	print '</form>';
}

// End of page
llxFooter();
$db->close();
