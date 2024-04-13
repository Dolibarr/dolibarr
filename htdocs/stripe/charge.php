<?php
/* Copyright (C) 2018-2022  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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

// Put here all includes required by your class file

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta', 'salaries', 'bills', 'hrm', 'stripe'));

// Security check
$socid = GETPOSTINT("socid");
if ($user->socid) {
	$socid = $user->socid;
}
//$result = restrictedArea($user, 'salaries', '', '', '');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$rowid = GETPOST("rowid", 'alpha');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$result = restrictedArea($user, 'banque');
$optioncss = GETPOST('optioncss', 'alpha');

/*
 * View
 */

$form = new Form($db);
$societestatic = new Societe($db);
$memberstatic = new Adherent($db);
$acc = new Account($db);
$stripe = new Stripe($db);

llxHeader('', $langs->trans("StripeChargeList"));

if (isModEnabled('stripe') && (!getDolGlobalString('STRIPE_LIVE') || GETPOST('forcesandbox', 'alpha'))) {
	$service = 'StripeTest';
	$servicestatus = '0';
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
} else {
	$service = 'StripeLive';
	$servicestatus = '1';
}

$stripeacc = $stripe->getStripeAccount($service);
/*if (empty($stripeaccount))
{
	print $langs->trans('ErrorStripeAccountNotDefined');
}*/

if (!$rowid) {
	$option = array('limit' => $limit + 1);
	$num = 0;

	$param = '';
	$totalnboflines = '';
	$moreforfilter = '';
	$list = null;
	if (GETPOSTISSET('starting_after_'.$page)) {
		$option['starting_after'] = GETPOST('starting_after_'.$page, 'alphanohtml');
	}
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}

	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	$title = $langs->trans("StripeChargeList");
	$title .= ($stripeacc ? ' (Stripe connection with Stripe OAuth Connect account '.$stripeacc.')' : ' (Stripe connection with keys from Stripe module setup)');

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy.png', 0, '', 'hidepaginationprevious', $limit);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("StripePaymentId", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("StripeCustomerId", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Customer", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Origin", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre("Paid", $_SERVER["PHP_SELF"], "", "", "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", "", '', '', '', 'right ');
	print "</tr>\n";

	try {
		if ($stripeacc) {
			$list = \Stripe\Charge::all($option, array("stripe_account" => $stripeacc));
		} else {
			$list = \Stripe\Charge::all($option);
		}

		$num = count($list->data);


		//if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		$param .= '&starting_after_'.($page + 1).'='.$list->data[($limit - 1)]->id;
		//$param.='&ending_before_'.($page+1).'='.$list->data[($limit-1)]->id;
	} catch (Exception $e) {
		print '<tr><td colspan="8">'.$e->getMessage().'</td></td>';
	}

	//print $list;
	$i = 0;
	if (!empty($list)) {
		foreach ($list->data as $charge) {
			if ($i >= $limit) {
				break;
			}

			if ($charge->refunded == '1') {
				$status = img_picto($langs->trans("refunded"), 'statut6');
			} elseif ($charge->paid == '1') {
				$status = img_picto($langs->trans((string) $charge->status), 'statut4');
			} else {
				$label = $langs->trans("Message").": ".$charge->failure_message."<br>";
				$label .= $langs->trans("Network").": ".$charge->outcome->network_status."<br>";
				$label .= $langs->trans("Status").": ".$langs->trans((string) $charge->outcome->seller_message);
				$status = $form->textwithpicto(img_picto($langs->trans((string) $charge->status), 'statut8'), $label, -1);
			}

			if (isset($charge->payment_method_details->type) && $charge->payment_method_details->type == 'card') {
				$type = $langs->trans("card");
			} elseif (isset($charge->source->type) && $charge->source->type == 'card') {
				$type = $langs->trans("card");
			} elseif (isset($charge->payment_method_details->type) && $charge->payment_method_details->type == 'three_d_secure') {
				$type = $langs->trans("card3DS");
			} elseif (isset($charge->payment_method_details->type) && $charge->payment_method_details->type == 'sepa_debit') {
				$type = $langs->trans("sepadebit");
			} elseif (isset($charge->payment_method_details->type) && $charge->payment_method_details->type == 'ideal') {
				$type = $langs->trans("iDEAL");
			}

			// Why this ?
			/*if (!empty($charge->payment_intent)) {
			 if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
			 $charge = \Stripe\PaymentIntent::retrieve($charge->payment_intent);
			 } else {
			 $charge = \Stripe\PaymentIntent::retrieve($charge->payment_intent, array("stripe_account" => $stripeacc));
			 }
			 }*/

			// The metadata FULLTAG is defined by the online payment page
			$FULLTAG = $charge->metadata->FULLTAG;

			// Save into $tmparray all metadata
			$tmparray = dolExplodeIntoArray($FULLTAG, '.', '=');
			// Load origin object according to metadata
			if (!empty($tmparray['CUS']) && $tmparray['CUS'] > 0) {
				$societestatic->fetch($tmparray['CUS']);
			} elseif (!empty($charge->metadata->dol_thirdparty_id) && $charge->metadata->dol_thirdparty_id > 0) {
				$societestatic->fetch($charge->metadata->dol_thirdparty_id);
			} else {
				$societestatic->id = 0;
			}
			if (!empty($tmparray['MEM']) && $tmparray['MEM'] > 0) {
				$memberstatic->fetch($tmparray['MEM']);
			} else {
				$memberstatic->id = 0;
			}

			print '<tr class="oddeven">';

			if (!empty($stripeacc)) {
				$connect = $stripeacc.'/';
			} else {
				$connect = '';
			}

			// Ref
			$url = 'https://dashboard.stripe.com/'.$connect.'test/payments/'.$charge->id;
			if ($servicestatus) {
				$url = 'https://dashboard.stripe.com/'.$connect.'payments/'.$charge->id;
			}
			print "<td>";
			print "<a href='".$url."' target='_stripe'>".img_picto($langs->trans('ShowInStripe'), 'globe')." ".$charge->id."</a>";
			if ($charge->payment_intent) {
				print '<br><span class="opacitymedium">'.$charge->payment_intent.'</span>';
			}
			print "</td>\n";

			// Stripe customer
			print "<td>";
			if (isModEnabled('stripe') && !empty($stripeacc)) {
				$connect = $stripeacc.'/';
			}
			$url = 'https://dashboard.stripe.com/'.$connect.'test/customers/'.$charge->customer;
			if ($servicestatus) {
				$url = 'https://dashboard.stripe.com/'.$connect.'customers/'.$charge->customer;
			}
			if (!empty($charge->customer)) {
				print '<a href="'.$url.'" target="_stripe">'.img_picto($langs->trans('ShowInStripe'), 'globe').' '.$charge->customer.'</a>';
			}
			print "</td>\n";

			// Link
			print "<td>";
			if ($societestatic->id > 0) {
				print $societestatic->getNomUrl(1);
			} elseif ($memberstatic->id > 0) {
				print $memberstatic->getNomUrl(1);
			}
			print "</td>\n";

			// Origin
			print "<td>";
			if ($charge->metadata->dol_type == "order" || $charge->metadata->dol_type == "commande") {
				$object = new Commande($db);
				$object->fetch($charge->metadata->dol_id);
				if ($object->id > 0) {
					print "<a href='".DOL_URL_ROOT."/commande/card.php?id=".$object->id."'>".img_picto('', 'order')." ".$object->ref."</a>";
				} else {
					print $FULLTAG;
				}
			} elseif ($charge->metadata->dol_type == "invoice" || $charge->metadata->dol_type == "facture") {
				$object = new Facture($db);
				$object->fetch($charge->metadata->dol_id);
				if ($object->id > 0) {
					print "<a href='".DOL_URL_ROOT."/compta/facture/card.php?facid=".$charge->metadata->dol_id."'>".img_picto('', 'bill')." ".$object->ref."</a>";
				} else {
					print $FULLTAG;
				}
			} else {
				print $FULLTAG;
			}
			print "</td>\n";

			// Date payment
			print '<td class="center">'.dol_print_date($charge->created, 'dayhour')."</td>\n";
			// Type
			print '<td>';
			print $type;
			print '</td>';
			// Amount
			print '<td class="right"><span class="amount">'.price(($charge->amount - $charge->amount_refunded) / 100, 0, '', 1, - 1, - 1, strtoupper($charge->currency))."</span></td>";
			// Status
			print '<td class="right">';
			print $status;
			print "</td>\n";

			print "</tr>\n";

			$i++;
		}
	}

	print '</table>';
	print '</div>';
	print '</form>';
}

// End of page
llxFooter();
$db->close();
