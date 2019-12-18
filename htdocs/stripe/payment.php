<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Teddy Andreotti         <125155@supinfo.com>
 * Copyright (C) 2015       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018-2019  Thibault FOUCART         <support@ptibogxiv.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/stripe/payment.php
 *	\ingroup    stripe
 *	\brief      Payment page for customers invoices. @todo Seems deprecated and bugged and not used (no link to this page) !
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'banks', 'multicurrency', 'stripe'));

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm');

$facid		= GETPOST('facid', 'int');
$socname	= GETPOST('socname');
$source = GETPOST('source_id');
$accountid	= GETPOST('accountid');
$paymentnum = GETPOST('num_paiement');

$sortfield	= GETPOST('sortfield', 'alpha');
$sortorder	= GETPOST('sortorder', 'alpha');
$page		= GETPOST('page', 'int');

$amounts=array();
$amountsresttopay=array();
$addwarning=0;

$multicurrency_amounts=array();
$multicurrency_amountsresttopay=array();

// Security check
$socid=0;
if ($user->socid > 0)
{
    $socid = $user->socid;
}

$object=new Facture($db);
$stripe=new Stripe($db);

// Load object
if ($facid > 0)
{
	$ret=$object->fetch($facid);
}

if (empty($conf->stripe->enabled))
{
    accessforbidden();
}

if (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha'))
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

// Initialize technical object to manage hooks of paiements. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paiementcard', 'globalcard'));

/*
 * Actions
 */

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm == 'yes'))
	{
	    $error = 0;

	    $datepaye = dol_now();
	    $paiement_id = 0;
	    $totalpayment = 0;
		$multicurrency_totalpayment = 0;
	    $atleastonepaymentnotnull = 0;

	    // Generate payment array and check if there is payment higher than invoice and payment date before invoice date
	    $tmpinvoice = new Facture($db);
	    foreach ($_POST as $key => $value)
	    {
	        if (substr($key, 0, 7) == 'amount_')
	        {
	            $cursorfacid = substr($key, 7);
	            $amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $totalpayment = $totalpayment + $amounts[$cursorfacid];
	            if (!empty($amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result = $tmpinvoice->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $amountsresttopay[$cursorfacid] = price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
	            if ($amounts[$cursorfacid])
	            {
		            // Check amount
		            if ($amounts[$cursorfacid] && (abs($amounts[$cursorfacid]) > abs($amountsresttopay[$cursorfacid])))
		            {
		                $addwarning = 1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
		            }
		            // Check date
		            if ($datepaye && ($datepaye < $tmpinvoice->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => $_POST[$key]);
	        }
			elseif (substr($key, 0, 21) == 'multicurrency_amount_')
			{
				$cursorfacid = substr($key, 21);
	            $multicurrency_amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $multicurrency_totalpayment += $multicurrency_amounts[$cursorfacid];
	            if (!empty($multicurrency_amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result = $tmpinvoice->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $multicurrency_amountsresttopay[$cursorfacid] = price2num($tmpinvoice->multicurrency_total_ttc - $tmpinvoice->getSommePaiement(1));
	            if ($multicurrency_amounts[$cursorfacid])
	            {
		            // Check amount
		            if ($multicurrency_amounts[$cursorfacid] && (abs($multicurrency_amounts[$cursorfacid]) > abs($multicurrency_amountsresttopay[$cursorfacid])))
		            {
		                $addwarning = 1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
		            }
		            // Check date
		            if ($datepaye && ($datepaye < $tmpinvoice->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => GETPOST($key, 'int'));
			}
	    }

        // Check parameters
        /*if (! GETPOST('paiementcode'))
        {
            setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('PaymentMode')), null, 'errors');
            $error++;
        }*/

	    if (!empty($conf->banque->enabled))
	    {
	        // If bank module is on, account is required to enter a payment
	        if (GETPOST('accountid') <= 0)
	        {
	            setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
	            $error++;
	        }
	    }

	    if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull))
	    {
	        setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->trans('PaymentAmount')), null, 'errors');
	        $error++;
	    }

        /*if (empty($datepaye))
        {
            setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('Date')), null, 'errors');
            $error++;
        }*/

		// Check if payments in both currency
		if ($totalpayment > 0 && $multicurrency_totalpayment > 0)
		{
			setEventMessages($langs->transnoentities('ErrorPaymentInBothCurrency'), null, 'errors');
	        $error++;
		}
	}

	/*
	 * Action add_paiement
	 */
	if ($action == 'add_paiement') {
	    if ($error) {
            $action = 'create';
            if (!$source) {
			    setEventMessages($langs->transnoentities('NoSource'), null, 'errors');
            }
            $error++;
        }
	    // Le reste propre a cette action s'affiche en bas de page.
	}

	/*
	 * Action confirm_paiement
	 */
	if ($action == 'confirm_paiement' && $confirm == 'yes')
	{
		$error = 0;

		$datepaye = dol_now();

		$db->begin();

		// Clean parameters amount if payment is for a credit note
		if (GETPOST('type') == 2)
		{
			foreach ($amounts as $key => $value)	// How payment is dispatch
			{
				$newvalue = price2num($value, 'MT');
				$amounts[$key] = -$newvalue;
			}

			foreach ($multicurrency_amounts as $key => $value)	// How payment is dispatch
			{
				$newvalue = price2num($value, 'MT');
				$multicurrency_amounts[$key] = -$newvalue;
			}
		}

		if (!empty($conf->banque->enabled))
		{
			// Si module bank actif, un compte est obligatoire lors de la saisie d'un paiement
			if (GETPOST('accountid') <= 0)
			{
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
				$error++;
			}
		}

		$facture = new Facture($db);
		$facture->fetch($facid);
		$facture->fetch_thirdparty();

		$error = 0;

		if (is_object($stripe) && $stripeacc)
		{
			$customerstripe = $stripe->customerStripe($facture->thirdparty, $stripeacc, $servicestatus);

			if ($customerstripe->id) {
				$listofsources = $customerstripe->sources->data;
			}
		}

		$stripeamount = 0;
		foreach ($amounts as $key => $value)	// How payment is dispatch
		{
			$stripeamount += price2num($value, 'MT');
		}

		if (preg_match('/acct_/i', $source))
		{
			$paiementcode = "VIR";
		}
		elseif (preg_match('/card_/i', $source))
		{
			$paiementcode = "CB";
		}
		elseif (preg_match('/src_/i', $source))
		{
			$customer2 = $customerstripe = $stripe->customerStripe($facture->thirdparty, $stripeacc, $servicestatus);
			$src = $customer2->sources->retrieve("$source");
			if ($src->type == 'card')
			{
				$paiementcode = "CB";
			}
		}



		$societe = new Societe($db);
		$societe->fetch($facture->socid);
		dol_syslog("Create charge", LOG_DEBUG, 0, '_stripe');

		$stripecu = $stripe->getStripeCustomerAccount($societe->id, $servicestatus); // Get thirdparty cu_...

		$charge = $stripe->createPaymentStripe($stripeamount, $facture->multicurrency_code, "invoice", $facid, $source, $stripecu, $stripeacc, $servicestatus);

		if (!$error)
		{
			// Creation of payment line
			$paiement = new Paiement($db);
			$paiement->datepaye     = $datepaye;
			$paiement->amounts      = $amounts; // Array with all payments dispatching
			$paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
			$paiement->paiementid   = dol_getIdFromCode($db, $paiementcode, 'c_paiement');
			$paiement->num_paiement = $charge->message;
			$paiement->note         = GETPOST('comment');
			$paiement->ext_payment_id = $charge->id;
			$paiement->ext_payment_site = $service;
		}

		if (!$error)
		{
			$paiement_id = $paiement->create($user, 0);
			if ($paiement_id < 0)
			{
				setEventMessages($paiement->error, $paiement->errors, 'errors');
				$error++;
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE) && count($facture->lines))
			{
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $facture->thirdparty->default_lang;
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $facture->modelpdf;
				$ret = $facture->fetch($facid); // Reload to get new records

				$facture->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		if (!$error)
		{
			$label = '(CustomerInvoicePayment)';
			if (GETPOST('type') == 2) $label = '(CustomerInvoicePaymentBack)';
			$result = $paiement->addPaymentToBank($user, 'payment', $label, GETPOST('accountid'), '', '');
			if ($result < 0)
			{
				setEventMessages($paiement->error, $paiement->errors, 'errors');
				$error++;
			}
			elseif (GETPOST('closepaidinvoices') == 'on') {
				$facture->set_paid($user);
			}
		}

		if (!$error)
		{
			$db->commit();

			// If payment dispatching on more than one invoice, we keep on summary page, otherwise go on invoice card
			$invoiceid = 0;
			foreach ($paiement->amounts as $key => $amount)
			{
				$facid = $key;
				if (is_numeric($amount) && $amount <> 0)
				{
					if ($invoiceid != 0) $invoiceid = -1; // There is more than one invoice payed by this payment
					else $invoiceid = $facid;
				}
			}
			if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/compta/facture/card.php?facid='.$invoiceid;
			else $loc = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$paiement_id;
			header('Location: '.$loc);
			exit;
		}
		else
		{
			$loc = DOL_URL_ROOT.'/stripe/payment.php?facid='.$facid.'&action=create&error='.$charge->message;
			$db->rollback();

			header('Location: '.$loc);
			exit;
		}
	}
}


/*
 * View
 */

$form = new Form($db);

llxHeader();

if (!empty($conf->global->STRIPE_LIVE) && !GETPOST('forcesandbox', 'alpha')) {
	$service = 'StripeLive';
	$servicestatus = 0;
} else {
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}

if (GETPOST('error')) {
	setEventMessages(GETPOST('error'), null, 'errors');
}

if ($action == 'create' || $action == 'confirm_paiement' || $action == 'add_paiement')
{
	$facture = new Facture($db);
	$result = $facture->fetch($facid);

	if ($result >= 0)
	{
		$facture->fetch_thirdparty();

		$title = '';
		if ($facture->type != 2) $title .= $langs->trans("EnterPaymentReceivedFromCustomer");
		if ($facture->type == 2) $title .= $langs->trans("EnterPaymentDueToCustomer");
		print load_fiche_titre($title);

		// Initialize data for confirmation (this is used because data can be change during confirmation)
		if ($action == 'add_paiement')
		{
			$i = 0;

			$formquestion[$i++] = array('type' => 'hidden', 'name' => 'facid', 'value' => $facture->id);
			$formquestion[$i++] = array('type' => 'hidden', 'name' => 'socid', 'value' => $facture->socid);
			$formquestion[$i++] = array('type' => 'hidden', 'name' => 'type', 'value' => $facture->type);
		}


		// Add realtime total information
		if ($conf->use_javascript_ajax)
		{
			print "\n".'<script type="text/javascript" language="javascript">';
			print '$(document).ready(function () {
            			setPaiementCode();

            			$("#selectpaiementcode").change(function() {
            				setPaiementCode();
            			});

            			function setPaiementCode()
            			{
            				var code = $("#selectpaiementcode option:selected").val();

                            if (code == \'CHQ\' || code == \'VIR\')
            				{
            					if (code == \'CHQ\')
			                    {
			                        $(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
			                    }
            					if ($(\'#fieldchqemetteur\').val() == \'\')
            					{
            						var emetteur = ('.$facture->type.' == 2) ? \''.dol_escape_js(dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM)).'\' : jQuery(\'#thirdpartylabel\').val();
            						$(\'#fieldchqemetteur\').val(emetteur);
            					}
            				}
            				else
            				{
            					$(\'.fieldrequireddyn\').removeClass(\'fieldrequired\');
            					$(\'#fieldchqemetteur\').val(\'\');
            				}
            			}

						function _elemToJson(selector)
						{
							var subJson = {};
							$.map(selector.serializeArray(), function(n,i)
							{
								subJson[n["name"]] = n["value"];
							});

							return subJson;
						}
						function callForResult(imgId)
						{
							var json = {};
							var form = $("#payment_form");

							json["invoice_type"] = $("#invoice_type").val();
            				json["amountPayment"] = $("#amountpayment").attr("value");
							json["amounts"] = _elemToJson(form.find("input.amount"));
							json["remains"] = _elemToJson(form.find("input.remain"));

							if (imgId != null) {
								json["imgClicked"] = imgId;
							}

							$.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
							{
								json = $.parseJSON(data);

								form.data(json);

								for (var key in json)
								{
									if (key == "result")	{
										if (json["makeRed"]) {
											$("#"+key).addClass("error");
										} else {
											$("#"+key).removeClass("error");
										}
										json[key]=json["label"]+" "+json[key];
										$("#"+key).text(json[key]);
									} else {console.log(key);
										form.find("input[name*=\""+key+"\"]").each(function() {
											$(this).attr("value", json[key]);
										});
									}
								}
							});
						}
						$("#payment_form").find("input.amount").change(function() {
							callForResult();
						});
						$("#payment_form").find("input.amount").keyup(function() {
							callForResult();
						});
			';

			print '	});'."\n";
			if (!empty($conf->use_javascript_ajax)) {
				//Add js for AutoFill
				print ' $(document).ready(function () {';
				print ' 	$(".AutoFillAmout").on(\'click touchstart\', function(){
								$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
							});';
				print '	});'."\n";
			}
			print '	</script>'."\n";
		}

		print '<form id="payment_form" name="add_paiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add_paiement">';
		print '<input type="hidden" name="facid" value="'.$facture->id.'">';
		print '<input type="hidden" name="socid" value="'.$facture->socid.'">';
		print '<input type="hidden" name="type" id="invoice_type" value="'.$facture->type.'">';
		print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($facture->thirdparty->name).'">';

		dol_fiche_head();

		print '<table class="border centpercent">';

		// Invoice
		/*if ($facture->id > 0)
		{
			print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans('Invoice').'</span></td><td>'.$facture->getNomUrl(4)."</td></tr>\n";
		}*/

        // Third party
        print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans('Company').'</span></td><td>'.$facture->thirdparty->getNomUrl(4)."</td></tr>\n";

        // Bank account
        if (!empty($conf->banque->enabled))
        {
            //$form->select_comptes($accountid,'accountid',0,'',2);
            print '<input name="accountid" type="hidden" value="'.$conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS.'">';
        }
        else
        {
            print '<input name="accountid" type="hidden" value="'.$conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS.'">';
        }

        // Cheque number
        //print '<tr><td>'.$langs->trans('Numero');
        //print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
        //print '</td>';
        //print '<td><input name="num_paiement" type="text" value="'.$paymentnum.'"></td></tr>';

        // Check transmitter
        //print '<tr><td class="'.(GETPOST('paiementcode')=='CHQ'?'fieldrequired ':'').'fieldrequireddyn">'.$langs->trans('CheckTransmitter');
        //print ' <em>('.$langs->trans("ChequeMaker").')</em>';
        //print '</td>';
        //print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.GETPOST('chqemetteur').'"></td></tr>';

        // Bank name
        //print '<tr><td>'.$langs->trans('Bank');
        //print ' <em>('.$langs->trans("ChequeBank").')</em>';
        //print '</td>';
        //print '<td><input name="chqbank" size="30" type="text" value="'.GETPOST('chqbank').'"></td></tr>';

		// Comments
		print '<tr><td>'.$langs->trans('Comments').'</td>';
		print '<td class="tdtop">';
		print '<textarea name="comment" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.GETPOST('comment', 'none').'</textarea></td></tr>';

        print '</table>';

        dol_fiche_end();


        $customerstripe = $stripe->customerStripe($facture->thirdparty, $stripeacc, $servicestatus);

        print '<br>';
        print_barre_liste($langs->trans('StripeSourceList').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

        print '<table class="liste centpercent">'."\n";
        // Titles with sort buttons
        print '<tr class="liste_titre">';
        print '<td class="left"></td>';
        print '<td class="left">'.$langs->trans('Type').'</td>';
        print '<td class="left">'.$langs->trans('Informations').'</td>';
        print '<td class="left"></td>';
        print "<td></td></tr>\n";
        foreach ($customerstripe->sources->data as $src) {
            print '<tr>';

            print '<td class="center" width="20" ';
            if (($action == 'add_paiement' && $src->id != $source) or ($src->object == 'source' && $src->card->three_d_secure == 'required')) {
                print'class="opacitymedium"';
            }
            print '><input type="radio" id="source_id" class="flat" name="source_id"  value="'.$src->id.'"';
            if (($action == 'add_paiement' && $src->id != $source) or ($src->object == 'source' && $src->card->three_d_secure == 'required')) {
                print ' disabled';
            } elseif (($customerstripe->default_source == $src->id && $action != 'add_paiement') or ($source == $src->id && $action == 'add_paiement')) {
                print ' checked';
            }
            print '></td>';

            print '<td ';
            if (($action == 'add_paiement' && $src->id != $source) or ($src->object == 'source' && $src->card->three_d_secure == 'required')) {
                print'class="opacitymedium"';
            }

            print' >';
            if ($src->object == 'card') {
                print img_credit_card($src->brand);
            } elseif ($src->object == 'source' && $src->type == 'card') {
                print img_credit_card($src->card->brand);
            } elseif ($src->object == 'source' && $src->type == 'sepa_debit') {
                print '<span class="fa fa-university fa-2x fa-fw"></span>';
            }
            print '</td>';
            print '<td ';
            if (($action == 'add_paiement' && $src->id != $source) or ($src->object == 'source' && $src->card->three_d_secure == 'required')) {
                print'class="opacitymedium"';
            }
            print' >';
            if ($src->object == 'card') {
                print '....'.$src->last4.' - '.$src->exp_month.'/'.$src->exp_year.'';
                print '</td><td>';
                if ($src->country) {
                    $img = picto_from_langcode($src->country);
                    print $img ? $img.' ' : '';
                    print getCountry($src->country, 1);
                } else {
                    print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
                }
            } elseif ($src->object == 'source' && $src->type == 'card') {
                print $src->owner->name.'<br>....'.$src->card->last4.' - '.$src->card->exp_month.'/'.$src->card->exp_year.'';
                print '</td><td>';
                if ($src->card->country) {
                    $img = picto_from_langcode($src->card->country);
                    print $img ? $img.' ' : '';
                    print getCountry($src->card->country, 1);
                } else {
                    print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
                }
            } elseif ($src->object == 'source' && $src->type == 'sepa_debit') {
                print 'info sepa';
                print '</td><td>';
                if ($src->sepa_debit->country) {
                    $img = picto_from_langcode($src->sepa_debit->country);
                    print $img ? $img.' ' : '';
                    print getCountry($src->sepa_debit->country, 1);
                } else {
                    print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
                }
            }
            print '</td>';
            // Default
            print '<td class="center" width="50" ';
            if (($action == 'add_paiement' && $src->id != $source) or ($src->object == 'source' && $src->card->three_d_secure == 'required')) {
                print'class="opacitymedium"';
            }
            print'>';
            if (($customerstripe->default_source == $src->id)) {
                print "<SPAN class=' fa fa-star  fa-2x'></SPAN>";
            }
            print '</td>';
            print '</tr>';
        }
        // TODO more dolibarize with new stripe function and stripeconnect
        //if ($stripe->getStripeCustomerAccount($facture->socid)) {
        //    $account=\Stripe\Account::retrieve("".$stripe->getStripeCustomerAccount($facture->socid)."");
        //}

        if (($account->type == 'custom' or $account->type == 'express') && $entity == 1) {
            print '<tr class="oddeven">';

            print '<td class="center" width="20" ';
            if ($action == 'add_paiement' && $stripe->getStripeCustomerAccount($facture->socid) != $source) {
                print'class="opacitymedium"';
            }
            print'><input type="radio" id="source_id" class="flat" name="source_id"  value="'.$conf->global->STRIPE_EXTERNAL_ACCOUNT.'"';
            if ((empty($input) && $action != 'add_paiement') or ($source == $conf->global->STRIPE_EXTERNAL_ACCOUNT && $action == 'add_paiement')) {
                print ' checked';
            } elseif ($action == 'add_paiement' && $conf->global->STRIPE_EXTERNAL_ACCOUNT != $source) {
                print ' disabled';
            }
            print '></td><td ';
            if ($action == 'add_paiement' && $stripe->getStripeCustomerAccount($facture->socid) != $source) {
                print'class="opacitymedium"';
            }
            print '><span class="fa fa-cc-stripe fa-3x fa-fw"></span></td>';

            print '<td ';
            if ($action == 'add_paiement' && $stripe->getStripeCustomerAccount($facture->socid) != $source) {
                print'class="opacitymedium"';
            }
            print'>'.$langs->trans('sold');
            print'</td><td ';
            if ($action == 'add_paiement' && $src->id != $source) {
                print'class="opacitymedium"';
            }
            print'>';

            print '</td>';
            // Default
            print '<td class="center" width="50" ';
            if ($action == 'add_paiement' && $src->id != $source) {
                print'class="opacitymedium"';
            }
            print'>';
            //if (($customer->default_source!=$src->id)) {
            //    print img_picto($langs->trans("Disabled"),'off');
            //} else {
            //    print img_picto($langs->trans("Default"),'on');
            //}
            print '</td>';
            print '</tr>';
        }
        if (empty($input) && !$stripe->getStripeCustomerAccount($facture->socid)) {
            print '<tr><td class="opacitymedium" colspan="5">'.$langs->trans("None").'</td></tr>';
        }

        print "</table>";


        /*
         * List of unpaid invoices
         */

        $sql = 'SELECT f.rowid as facid, f.ref, f.total_ttc, f.multicurrency_code, f.multicurrency_total_ttc, f.type, ';
        $sql .= ' f.datef as df, f.fk_soc as socid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';

		if (!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS)) {
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON (f.fk_soc = s.rowid)';
		}

		$sql .= ' WHERE f.entity IN ('.getEntity('invoice').")";
        $sql .= ' AND (f.fk_soc = '.$facture->socid;

		if (!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS) && !empty($facture->thirdparty->parent)) {
			$sql .= ' OR f.fk_soc IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE parent = '.$facture->thirdparty->parent.')';
		}

        $sql .= ') AND f.paye = 0';
        $sql .= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled
        if ($facture->type != 2)
        {
            $sql .= ' AND type IN (0,1,3,5)'; // Standard invoice, replacement, deposit, situation
        }
        else
        {
            $sql .= ' AND type = 2'; // If paying back a credit note, we show all credit notes
        }

        // Sort invoices by date and serial number: the older one comes first
        $sql .= ' ORDER BY f.datef ASC, f.ref ASC';

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num > 0)
            {
            	$sign = 1;
            	if ($facture->type == 2) $sign = -1;

				$arraytitle = $langs->trans('Invoice');
				if ($facture->type == 2) $arraytitle = $langs->trans("CreditNotes");
				$alreadypayedlabel = $langs->trans('Received');
				$multicurrencyalreadypayedlabel = $langs->trans('MulticurrencyReceived');
				if ($facture->type == 2) { $alreadypayedlabel = $langs->trans("PaidBack"); $multicurrencyalreadypayedlabel = $langs->trans("MulticurrencyPaidBack"); }
				$remaindertopay = $langs->trans('RemainderToTake');
				$multicurrencyremaindertopay = $langs->trans('MulticurrencyRemainderToTake');
				if ($facture->type == 2) { $remaindertopay = $langs->trans("RemainderToPayBack"); $multicurrencyremaindertopay = $langs->trans("MulticurrencyRemainderToPayBack"); }

                $i = 0;

                print '<br>';

                print_barre_liste($langs->trans('StripeInvoiceList').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

                print '<table class="noborder centpercent">';
                print '<tr class="liste_titre">';
                print '<td>'.$arraytitle.'</td>';
                print '<td class="center">'.$langs->trans('Date').'</td>';
                if (!empty($conf->multicurrency->enabled)) {
                    print '<td>'.$langs->trans('Currency').'</td>';
                    print '<td class="right">'.$langs->trans('MulticurrencyAmountTTC').'</td>';
                    print '<td class="right">'.$multicurrencyalreadypayedlabel.'</td>';
                    print '<td class="right">'.$multicurrencyremaindertopay.'</td>';
                }
                print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
                print '<td class="right">'.$alreadypayedlabel.'</td>';
                print '<td class="right">'.$remaindertopay.'</td>';
                print '<td class="right">'.$langs->trans('PaymentAmount').'</td>';
                if (!empty($conf->multicurrency->enabled)) {
                    print '<td class="right">'.$langs->trans('MulticurrencyPaymentAmount').'</td>';
                }

                $tmpinvoice = new Facture($db);
                $parameters = array();
                $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $tmpinvoice, $action); // Note that $action and $object may have been modified by hook

                print '<td align="right">&nbsp;</td>';
                print "</tr>\n";

                $total = 0;
                $totalrecu = 0;
                $totalrecucreditnote = 0;
                $totalrecudeposits = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);

					$soc = new Societe($db);
					$soc->fetch($objp->socid);

                    $invoice = new Facture($db);
                    $invoice->fetch($objp->facid);
                    $paiement = $invoice->getSommePaiement();
                    $creditnotes = $invoice->getSumCreditNotesUsed();
                    $deposits = $invoice->getSumDepositsUsed();
                    $alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
                    $remaintopay = price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');

					// Multicurrency Price
					if (!empty($conf->multicurrency->enabled))
					{
						$multicurrency_payment = $invoice->getSommePaiement(1);
						$multicurrency_creditnotes = $invoice->getSumCreditNotesUsed(1);
						$multicurrency_deposits = $invoice->getSumDepositsUsed(1);
						$multicurrency_alreadypayed = price2num($multicurrency_payment + $multicurrency_creditnotes + $multicurrency_deposits, 'MT');
	                    $multicurrency_remaintopay = price2num($invoice->multicurrency_total_ttc - $multicurrency_payment - $multicurrency_creditnotes - $multicurrency_deposits, 'MT');
					}

                    print '<tr class="oddeven">';

                    print '<td>';
                    print $invoice->getNomUrl(1, '');
                    if ($objp->socid != $facture->thirdparty->id) print ' - '.$soc->getNomUrl(1).' ';
                    print "</td>\n";

                    // Date
                    print '<td class="center">'.dol_print_date($db->jdate($objp->df), 'day')."</td>\n";

                    // Currency
                    if (!empty($conf->multicurrency->enabled)) print '<td class="center">'.$objp->multicurrency_code."</td>\n";

					// Multicurrency Price
					if (!empty($conf->multicurrency->enabled))
					{
					    print '<td class="right">';
					    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) print price($sign * $objp->multicurrency_total_ttc);
					    print '</td>';

                    	// Multicurrency Price
						print '<td class="right">';
						if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
						{
						    print price($sign * $multicurrency_payment);
    		                if ($multicurrency_creditnotes) print '+'.price($multicurrency_creditnotes);
    		                if ($multicurrency_deposits) print '+'.price($multicurrency_deposits);
						}
		                print '</td>';

    					// Multicurrency Price
    				    print '<td class="right">';
    				    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) print price($sign * $multicurrency_remaintopay);
    				    print '</td>';
					}

					// Price
                    print '<td class="right">'.price($sign * $objp->total_ttc).'</td>';

                    // Received or paid back
                    print '<td class="right">'.price($sign * $paiement);
                    if ($creditnotes) print '+'.price($creditnotes);
                    if ($deposits) print '+'.price($deposits);
                    print '</td>';

                    // Remain to take or to pay back
                    print '<td class="right">'.price($sign * $remaintopay).'</td>';
                    //$test= price(price2num($objp->total_ttc - $paiement - $creditnotes - $deposits));

                    // Amount
                    print '<td class="right">';

                    // Add remind amount
                    $namef = 'amount_'.$objp->facid;
                    $nameRemain = 'remain_'.$objp->facid;

                    if ($action != 'add_paiement')
                    {
                        if (!empty($conf->use_javascript_ajax))
							print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $remaintopay)."'");
                        print '<input type=hidden class="remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
                        print '<input type="text" size="8" class="amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                    }
                    else
                    {
                        print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.dol_escape_htmltag(GETPOST($namef)).'" disabled>';
                        print '<input type="hidden" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                    }
                    print "</td>";

					// Multicurrency Price
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">';

						// Add remind multicurrency amount
	                    $namef = 'multicurrency_amount_'.$objp->facid;
	                    $nameRemain = 'multicurrency_remain_'.$objp->facid;

	                    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
	                    {
    	                    if ($action != 'add_paiement')
    	                    {
    	                        if (!empty($conf->use_javascript_ajax))
    								print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $multicurrency_remaintopay)."'");
    	                        print '<input type=hidden class="multicurrency_remain" name="'.$nameRemain.'" value="'.$multicurrency_remaintopay.'">';
    	                        print '<input type="text" size="8" class="multicurrency_amount" name="'.$namef.'" value="'.$_POST[$namef].'">';
    	                    }
    	                    else
    	                    {
    	                        print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.$_POST[$namef].'" disabled>';
    	                        print '<input type="hidden" name="'.$namef.'" value="'.$_POST[$namef].'">';
    	                    }
	                    }
	                    print "</td>";
					}

					$parameters = array();
					$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objp, $action); // Note that $action and $object may have been modified by hook

                    // Warning
                    print '<td class="center" width="16">';
                    //print "xx".$amounts[$invoice->id]."-".$amountsresttopay[$invoice->id]."<br>";
                    if ($amounts[$invoice->id] && (abs($amounts[$invoice->id]) > abs($amountsresttopay[$invoice->id]))
                    	|| $multicurrency_amounts[$invoice->id] && (abs($multicurrency_amounts[$invoice->id]) > abs($multicurrency_amountsresttopay[$invoice->id])))
                    {
                        print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));
                    }
                    print '</td>';

                    print "</tr>\n";

                    $total += $objp->total;
                    $total_ttc += $objp->total_ttc;
                    $totalrecu += $paiement;
                    $totalrecucreditnote += $creditnotes;
                    $totalrecudeposits += $deposits;
                    $i++;
                }
                if ($i > 1)
                {
            	    $amount = round(price($sign * price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits, 'MT')) * 100);

                    // Print total
                    print '<tr class="liste_total">';
                    print '<td colspan="2" class="left">'.$langs->trans('TotalTTC').'</td>';
                    if (!empty($conf->multicurrency->enabled)) {
                        print '<td></td>';
          					    print '<td></td>';
					              print '<td></td>';
                    }
					print '<td class="right"><b>'.price($sign * $total_ttc).'</b></td>';
                    print '<td class="right"><b>'.price($sign * $totalrecu);
                    if ($totalrecucreditnote) print '+'.price($totalrecucreditnote);
                    if ($totalrecudeposits) print '+'.price($totalrecudeposits);
                    print '</b></td>';
                    print '<td class="right"><b>'.price($sign * price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits, 'MT')).'</b></td>';
                    print '<td class="right" id="result" style="font-weight: bold;"></td>';
                    if (!empty($conf->multicurrency->enabled)) {
                        print '<td class="right" id="multicurrency_result" style="font-weight: bold;"></td>';
                    }
                    print '<td></td>';
                    print "</tr>\n";
                }
                print "</table>";
                //print "</td></tr>\n";
            }
            $db->free($resql);
        }
        else
        {
            dol_print_error($db);
        }


        // Bouton Enregistrer
        if ($action != 'add_paiement')
        {
        	$checkboxlabel = $langs->trans("ClosePaidInvoicesAutomatically");
        	if ($facture->type == 2) $checkboxlabel = $langs->trans("ClosePaidCreditNotesAutomatically");
        	$buttontitle = $langs->trans('ToMakePayment');
        	if ($facture->type == 2) $buttontitle = $langs->trans('ToMakePaymentBack');

        	print '<br><div class="center">';
        	print '<input type="checkbox" checked name="closepaidinvoices"> '.$checkboxlabel;
            /*if (! empty($conf->prelevement->enabled))
            {
                $langs->load("withdrawals");
                if (! empty($conf->global->WITHDRAW_DISABLE_AUTOCREATE_ONPAYMENTS)) print '<br>'.$langs->trans("IfInvoiceNeedOnWithdrawPaymentWontBeClosed");
            }*/
            print '<br><input type="submit" class="button" value="'.dol_escape_htmltag($buttontitle).'"><br><br>';
            print '</div>';
        }

        // Form to confirm payment
        if ($action == 'add_paiement')
        {
            $preselectedchoice = $addwarning ? 'no' : 'yes';

            print '<br>';
            if (!empty($totalpayment)) {
                $text = $langs->trans('ConfirmCustomerPayment', $totalpayment, $langs->trans("Currency".$conf->currency));
            }
            if (!empty($multicurrency_totalpayment)) {
                $text .= '<br>'.$langs->trans('ConfirmCustomerPayment', $multicurrency_totalpayment, $langs->trans("paymentInInvoiceCurrency"));
            }
            if (GETPOST('closepaidinvoices'))
            {
                $text .= '<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
                print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
            }
            print $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type, $langs->trans('ReceivedCustomersPayments'), $text, 'confirm_paiement', $formquestion, $preselectedchoice);
        }

        print "</form>\n";
    }
}


/**
 *  Show list of payments
 */

if (!GETPOST('action'))
{
    if ($page == -1 || empty($page)) $page = 0;
    $limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
    $offset = $limit * $page;

    if (!$sortorder) $sortorder = 'DESC';
    if (!$sortfield) $sortfield = 'p.datep';

    $sql = 'SELECT p.datep as dp, p.amount, f.amount as fa_amount, f.ref';
    $sql .= ', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'c_paiement as c';
    $sql .= ' WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id';
    $sql .= ' AND f.entity IN ('.getEntity('invoice').")";
    if ($socid)
    {
        $sql .= ' AND f.fk_soc = '.$socid;
    }

    $sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
    $sql .= $db->plimit($limit + 1, $offset);
    $resql = $db->query($sql);

    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        print_barre_liste($langs->trans('Payments'), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num);
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print_liste_field_titre('Invoice', $_SERVER["PHP_SELF"], 'ref', '', '', '', $sortfield, $sortorder);
        print_liste_field_titre('Date', $_SERVER["PHP_SELF"], 'dp', '', '', '', $sortfield, $sortorder);
        print_liste_field_titre('Type', $_SERVER["PHP_SELF"], 'libelle', '', '', '', $sortfield, $sortorder);
        print_liste_field_titre('Amount', $_SERVER["PHP_SELF"], 'fa_amount', '', '', '', $sortfield, $sortorder, 'right ');

		$tmpobject = new Paiement($db);
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $tmpobject, $action); // Note that $action and $object may have been modified by hook

		print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
		print "</tr>\n";

        while ($i < min($num, $limit))
        {
            $objp = $db->fetch_object($resql);

            print '<tr class="oddeven">';
            print '<td><a href="'.DOL_URL_ROOT.'compta/facture/card.php?facid='.$objp->facid.'">'.$objp->ref."</a></td>\n";
            print '<td>'.dol_print_date($db->jdate($objp->dp))."</td>\n";
            print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
            print '<td class="right">'.price($objp->amount).'</td>';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objp, $action); // Note that $action and $object may have been modified by hook

			print '<td>&nbsp;</td>';
			print '</tr>';
            $i++;
        }
        print '</table>';
    }
}

// End of page
llxFooter();
$db->close();
