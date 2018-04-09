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

$langs->load("compta");
$langs->load("salaries");
$langs->load("bills");
$langs->load("hrm");
$langs->load("stripe");

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'salaries', '', '', '');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
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
$stripe=new Stripe($db);

llxHeader('', $langs->trans("StripeChargeList"));

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

if (!$rowid)
{
	print '<FORM method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<INPUT type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<INPUT type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<INPUT type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<INPUT type="hidden" name="action" value="list">';
    print '<INPUT type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<INPUT type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<INPUT type="hidden" name="page" value="'.$page.'">';

    $title=$langs->trans("StripeChargeList");
    $title.=($stripeaccount?' (Stripe connection with Stripe OAuth Connect account '.$stripeaccount.')':' (Stripe connection with keys from Stripe module setup)');

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, $totalnboflines, 'title_accountancy.png', 0, '', '', $limit);

    print '<DIV class="div-table-responsive">';
    print '<TABLE class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    print '<TR class="liste_titre">';
    print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
    print_liste_field_titre("StripeCustomerId",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
    print_liste_field_titre("Customer",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
    print_liste_field_titre("Origin",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
    print_liste_field_titre("DatePayment",$_SERVER["PHP_SELF"],"","","",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre("Type",$_SERVER["PHP_SELF"],"","","",'align="left"',$sortfield,$sortorder);
    print_liste_field_titre("Paid",$_SERVER["PHP_SELF"],"","","",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"","","",'align="right"');
    print "</TR>\n";

	print "</TR>\n";

	if ($stripeaccount)
	{
		$list=\Stripe\Charge::all(array("limit" => $limit), array("stripe_account" => $stripeaccount));
	}
	else
	{
		$list=\Stripe\Charge::all(array("limit" => $limit));
	}

	//print $list;
	foreach ($list->data as $charge)
	{
		// The metadata FULLTAG is defined by the online payment page
		$FULLTAG=$charge->metadata->FULLTAG;

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
		}

	    print '<TR class="oddeven">';
	    // Ref
		print "<TD><A href='".DOL_URL_ROOT."/stripe/charge.php?rowid=".$charge->id."'>".$charge->id."</A></TD>\n";
		// Stripe customer
		print "<TD>".$charge->customer."</TD>\n";
		// Link
		print "<TD>";
		if ($societestatic->id > 0)
		{
			print $societestatic->getNomUrl(1);
		}
		if ($memberstatic->id > 0)
		{
			print $memberstatic->getNomUrl(1);
		}
		print "</TD>\n";
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
	    print '<TD align="center">'.dol_print_date($charge->created,'%d/%m/%Y %H:%M')."</TD>\n";
	    // Type
	    print '<TD>';
		if ($charge->source->object=='card')
		{
		    print $langs->trans("card");
		}
		elseif ($charge->source->type=='card'){
		    print $langs->trans("card");
		} elseif ($charge->source->type=='three_d_secure'){
		    print $langs->trans("card3DS");
		}
	    print '</TD>';
	    // Amount
	    print "<TD align=\"right\">".price(($charge->amount-$charge->amount_refunded)/100)."</TD>";
	    // Status
	    print '<TD align="right">';
	    if ($charge->refunded=='1'){
	    	print $langs->trans("refunded");
	    } elseif ($charge->paid=='1'){
	    	print $langs->trans("".$charge->status."");
	    } else {
	    	$label="Message: ".$charge->failure_message."<br>";
	    	$label.="Réseau: ".$charge->outcome->network_status."<br>";
	    	$label.="Statut: ".$langs->trans("".$charge->outcome->seller_message."");
	    	print $form->textwithpicto($langs->trans("".$charge->status.""),$label,1);
	    }
	    print "</TD>\n";

	    print "</TR>\n";
	}
} else {

}

llxFooter();
$db->close();
