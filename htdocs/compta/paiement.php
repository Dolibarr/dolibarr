<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 *	\file       htdocs/compta/paiement.php
 *	\ingroup    compta
 *	\brief      Page to create a payment
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

$action		= GETPOST('action');
$confirm	= GETPOST('confirm');

$facid		= GETPOST('facid','int');
$socname	= GETPOST('socname');
$accountid	= GETPOST('accountid');
$paymentnum	= GETPOST('num_paiement');

$sortfield	= GETPOST('sortfield','alpha');
$sortorder	= GETPOST('sortorder','alpha');
$page		= GETPOST('page','int');

$amounts=array();
$amountsresttopay=array();
$addwarning=0;

// Security check
$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

$object=new Facture($db);

// Load object
if ($facid > 0)
{
	$ret=$object->fetch($facid);
}

// Initialize technical object to manage hooks of paiements. Note that conf->hooks_modules contains array array
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('paiementcard'));

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks 

/*
 * Action add_paiement et confirm_paiement
 */
if ($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm=='yes'))
{
    $error = 0;

    $datepaye = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
    $paiement_id = 0;
    $totalpaiement = 0;
    $atleastonepaymentnotnull = 0;

    // Verifie si des paiements sont superieurs au montant facture
    foreach ($_POST as $key => $value)
    {
        if (substr($key,0,7) == 'amount_')
        {
            $cursorfacid = substr($key,7);
            $amounts[$cursorfacid] = price2num(trim($_POST[$key]));
            $totalpaiement = $totalpaiement + $amounts[$cursorfacid];
            if (! empty($amounts[$cursorfacid])) $atleastonepaymentnotnull++;
            $tmpfacture=new Facture($db);
            $tmpfacture->fetch($cursorfacid);
            $amountsresttopay[$cursorfacid]=price2num($tmpfacture->total_ttc-$tmpfacture->getSommePaiement());
            if ($amounts[$cursorfacid] && (abs($amounts[$cursorfacid]) > abs($amountsresttopay[$cursorfacid])))
            {
                $addwarning=1;
                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
            }

            $formquestion[$i++]=array('type' => 'hidden','name' => $key,  'value' => $_POST[$key]);
        }
    }

    // Check parameters
    if (! GETPOST('paiementcode'))
    {
        $fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('PaymentMode')).'</div>';
        $error++;
    }

    if (! empty($conf->banque->enabled))
    {
        // If bank module is on, account is required to enter a payment
        if (GETPOST('accountid') <= 0)
        {
            $fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')).'</div>';
            $error++;
        }
    }

    if (empty($totalpaiement) && empty($atleastonepaymentnotnull))
    {
        $fiche_erreur_message = '<div class="error">'.$langs->transnoentities('ErrorFieldRequired',$langs->trans('PaymentAmount')).'</div>';
        $error++;
    }

    if (empty($datepaye))
    {
        $fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Date')).'</div>';
        $error++;
    }
}

/*
 * Action add_paiement
 */
if ($action == 'add_paiement')
{
    if ($error)
    {
        $action = 'create';
    }
    // Le reste propre a cette action s'affiche en bas de page.
}

/*
 * Action confirm_paiement
 */
if ($action == 'confirm_paiement' && $confirm == 'yes')
{
    $error=0;

    $datepaye = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

    $db->begin();

    // Clean parameters amount if payment is for a credit note
    if (GETPOST('type') == 2)
    {
	    foreach ($amounts as $key => $value)	// How payment is dispatch
	    {
	    	$newvalue = price2num($value,'MT');
	    	$amounts[$key] = -$newvalue;
	    }
    }

    if (! empty($conf->banque->enabled))
    {
    	// Si module bank actif, un compte est obligatoire lors de la saisie d'un paiement
    	if (GETPOST('accountid') <= 0)
    	{
    		$fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')).'</div>';
    		$error++;
    	}
    }

    // Creation of payment line
    $paiement = new Paiement($db);
    $paiement->datepaye     = $datepaye;
    $paiement->amounts      = $amounts;   // Array with all payments dispatching
    $paiement->paiementid   = dol_getIdFromCode($db,$_POST['paiementcode'],'c_paiement');
    $paiement->num_paiement = $_POST['num_paiement'];
    $paiement->note         = $_POST['comment'];

    if (! $error)
    {
    	$paiement_id = $paiement->create($user, (GETPOST('closepaidinvoices')=='on'?1:0));
    	if ($paiement_id < 0)
        {
            $errmsg=$paiement->error;
            $error++;
        }
    }

    if (! $error)
    {
    	$label='(CustomerInvoicePayment)';
    	if (GETPOST('type') == 2) $label='(CustomerInvoicePaymentBack)';
        $result=$paiement->addPaymentToBank($user,'payment',$label,GETPOST('accountid'),GETPOST('chqemetteur'),GETPOST('chqbank'));
        if ($result < 0)
        {
            $errmsg=$paiement->error;
            $error++;
        }
    }

    if (! $error)
    {
        $db->commit();

        // If payment dispatching on more than one invoice, we keep on summary page, otherwise go on invoice card
        $invoiceid=0;
        foreach ($paiement->amounts as $key => $amount)
        {
            $facid = $key;
            if (is_numeric($amount) && $amount <> 0)
            {
                if ($invoiceid != 0) $invoiceid=-1; // There is more than one invoice payed by this payment
                else $invoiceid=$facid;
            }
        }
        if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/compta/facture.php?facid='.$invoiceid;
        else $loc = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$paiement_id;
        header('Location: '.$loc);
        exit;
    }
    else
    {
        $db->rollback();
    }
}


/*
 * View
 */

llxHeader();

$form=new Form($db);


if ($action == 'create' || $action == 'confirm_paiement' || $action == 'add_paiement')
{
	$facture = new Facture($db);
	$result=$facture->fetch($facid);

	if ($result >= 0)
	{
		$facture->fetch_thirdparty();

		$title='';
		if ($facture->type != 2) $title.=$langs->trans("EnterPaymentReceivedFromCustomer");
		if ($facture->type == 2) $title.=$langs->trans("EnterPaymentDueToCustomer");
		print_fiche_titre($title);

		dol_htmloutput_errors($errmsg);

		// Initialize data for confirmation (this is used because data can be change during confirmation)
		if ($action == 'add_paiement')
		{
			$i=0;

			$formquestion[$i++]=array('type' => 'hidden','name' => 'facid', 'value' => $facture->id);
			$formquestion[$i++]=array('type' => 'hidden','name' => 'socid', 'value' => $facture->socid);
			$formquestion[$i++]=array('type' => 'hidden','name' => 'type',  'value' => $facture->type);
		}

		// Invoice with Paypal transaction
		// TODO add hook possibility (regis)
		if (! empty($conf->paypalplus->enabled) && $conf->global->PAYPAL_ENABLE_TRANSACTION_MANAGEMENT && ! empty($facture->ref_int))
		{
			if (! empty($conf->global->PAYPAL_BANK_ACCOUNT)) $accountid=$conf->global->PAYPAL_BANK_ACCOUNT;
			$paymentnum=$facture->ref_int;
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
            					$(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
            					if ($(\'#fieldchqemetteur\').val() == \'\')
            					{
            						var emetteur = ('.$facture->type.' == 2) ? \''.dol_escape_htmltag(MAIN_INFO_SOCIETE_NOM).'\' : jQuery(\'#thirdpartylabel\').val();
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
							json["amounts"] = _elemToJson(form.find("input[name*=\"amount_\"]"));
							json["remains"] = _elemToJson(form.find("input[name*=\"remain_\"]"));

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
									} else {
										form.find("input[name*=\""+key+"\"]").each(function() {
											$(this).attr("value", json[key]);
										});
									}
								}
							});
						}
						$("#payment_form").find("input[name*=\"amount_\"]").change(function() {
							callForResult();
						});
						$("#payment_form").find("input[name*=\"amount_\"]").keyup(function() {
							callForResult();
						});
			';

			// Add user helper to input amount on invoices
			if (! empty($conf->global->MAIN_JS_ON_PAYMENT) && $facture->type != 2)
			{
				print '	$("#payment_form").find("img").click(function() {
							callForResult(jQuery(this).attr("id"));
						});

						$("#amountpayment").change(function() {
							callForResult();
						});';
			}

			print '	});'."\n";
			print '	</script>'."\n";
		}

		print '<form id="payment_form" name="add_paiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add_paiement">';
		print '<input type="hidden" name="facid" value="'.$facture->id.'">';
		print '<input type="hidden" name="socid" value="'.$facture->socid.'">';
		print '<input type="hidden" name="type" id="invoice_type" value="'.$facture->type.'">';
		print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($facture->client->name).'">';

		print '<table class="border" width="100%">';

        // Third party
        print '<tr><td><span class="fieldrequired">'.$langs->trans('Company').'</span></td><td colspan="2">'.$facture->client->getNomUrl(4)."</td></tr>\n";

        // Date payment
        print '<tr><td><span class="fieldrequired">'.$langs->trans('Date').'</span></td><td>';
        $datepayment = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        $datepayment= ($datepayment == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0) : $datepayment);
        $form->select_date($datepayment,'','','',0,"add_paiement",1,1);
        print '</td>';
        print '<td>'.$langs->trans('Comments').'</td></tr>';

        $rowspan=5;
        if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT)) $rowspan++;

        // Payment mode
        print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
        $form->select_types_paiements((GETPOST('paiementcode')?GETPOST('paiementcode'):$facture->mode_reglement_code),'paiementcode','',2);
        print "</td>\n";
        print '<td rowspan="'.$rowspan.'" valign="top">';
        print '<textarea name="comment" wrap="soft" cols="60" rows="'.ROWS_4.'">'.(empty($_POST['comment'])?'':$_POST['comment']).'</textarea></td>';
        print '</tr>';

        // Bank account
        print '<tr>';
        if (! empty($conf->banque->enabled))
        {
            if ($facture->type != 2) print '<td><span class="fieldrequired">'.$langs->trans('AccountToCredit').'</span></td>';
            if ($facture->type == 2) print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
            print '<td>';
            $form->select_comptes($accountid,'accountid',0,'',2);
            print '</td>';
        }
        else
        {
            print '<td colspan="2">&nbsp;</td>';
        }
        print "</tr>\n";

        // Payment amount
        if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT))
        {
            print '<tr><td><span class="fieldrequired">'.$langs->trans('AmountPayment').'</span></td>';
            print '<td>';
            if ($action == 'add_paiement')
            {
                print '<input id="amountpayment" name="amountpaymenthidden" size="8" type="text" value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'" disabled="disabled">';
                print '<input name="amountpayment" type="hidden" value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'">';
            }
            else
            {
                print '<input id="amountpayment" name="amountpayment" size="8" type="text" value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'">';
            }
            print '</td>';
            print '</tr>';
        }

        // Cheque number
        print '<tr><td>'.$langs->trans('Numero');
        print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
        print '</td>';
        print '<td><input name="num_paiement" type="text" value="'.$paymentnum.'"></td></tr>';

        // Check transmitter
        print '<tr><td class="'.(GETPOST('paiementcode')=='CHQ'?'fieldrequired ':'').'fieldrequireddyn">'.$langs->trans('CheckTransmitter');
        print ' <em>('.$langs->trans("ChequeMaker").')</em>';
        print '</td>';
        print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.GETPOST('chqemetteur').'"></td></tr>';

        // Bank name
        print '<tr><td>'.$langs->trans('Bank');
        print ' <em>('.$langs->trans("ChequeBank").')</em>';
        print '</td>';
        print '<td><input name="chqbank" size="30" type="text" value="'.GETPOST('chqbank').'"></td></tr>';

        print '</table>';

        /*
         * List of unpaid invoices
         */
        $sql = 'SELECT f.rowid as facid, f.facnumber, f.total_ttc, f.type, ';
        $sql.= ' f.datef as df';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
        $sql.= ' WHERE f.entity = '.$conf->entity;
        $sql.= ' AND f.fk_soc = '.$facture->socid;
        $sql.= ' AND f.paye = 0';
        $sql.= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled
        if ($facture->type != 2)
        {
            $sql .= ' AND type IN (0,1,3)';	// Standard invoice, replacement, deposit
        }
        else
        {
            $sql .= ' AND type = 2';		// If paying back a credit note, we show all credit notes
        }

        // Sort invoices by date and serial number: the older one comes first
        $sql.=' ORDER BY f.datef ASC, f.facnumber ASC';

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num > 0)
            {
            	$sign=1;
            	if ($facture->type == 2) $sign=-1;

				$arraytitle=$langs->trans('Invoice');
				if ($facture->type == 2) $arraytitle=$langs->trans("CreditNotes");
				$alreadypayedlabel=$langs->trans('Received');
				if ($facture->type == 2) $alreadypayedlabel=$langs->trans("PaidBack");
				$remaindertopay=$langs->trans('RemainderToTake');
				if ($facture->type == 2) $remaindertopay=$langs->trans("RemainderToPayBack");

                $i = 0;
                //print '<tr><td colspan="3">';
                print '<br>';
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td>'.$arraytitle.'</td>';
                print '<td align="center">'.$langs->trans('Date').'</td>';
                print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
                print '<td align="right">'.$alreadypayedlabel.'</td>';
                print '<td align="right">'.$remaindertopay.'</td>';
                print '<td align="right">'.$langs->trans('PaymentAmount').'</td>';
                print '<td align="right">&nbsp;</td>';
                print "</tr>\n";

                $var=True;
                $total=0;
                $totalrecu=0;
                $totalrecucreditnote=0;
                $totalrecudeposits=0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);
                    $var=!$var;

                    $invoice=new Facture($db);
                    $invoice->fetch($objp->facid);
                    $paiement = $invoice->getSommePaiement();
                    $creditnotes=$invoice->getSumCreditNotesUsed();
                    $deposits=$invoice->getSumDepositsUsed();
                    $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
                    $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');

                    print '<tr '.$bc[$var].'>';

                    print '<td>';
                    print $invoice->getNomUrl(1,'');
                    print "</td>\n";

                    // Date
                    print '<td align="center">'.dol_print_date($db->jdate($objp->df),'day')."</td>\n";

                    // Price
                    print '<td align="right">'.price($sign * $objp->total_ttc).'</td>';

                    // Received or paid back
                    print '<td align="right">'.price($sign * $paiement);
                    if ($creditnotes) print '+'.price($creditnotes);
                    if ($deposits) print '+'.price($deposits);
                    print '</td>';

                    // Remain to take or to pay back
                    print '<td align="right">'.price($sign * $remaintopay).'</td>';
                    //$test= price(price2num($objp->total_ttc - $paiement - $creditnotes - $deposits));

                    // Amount
                    print '<td align="right">';

                    // Add remind amount
                    $namef = 'amount_'.$objp->facid;
                    $nameRemain = 'remain_'.$objp->facid;

                    if ($action != 'add_paiement')
                    {
                        if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT))
                        {
                            print img_picto($langs->trans('AddRemind'),'rightarrow.png','id="'.$objp->facid.'"');
                        }
                        print '<input type=hidden name="'.$nameRemain.'" value="'.$remaintopay.'">';
                        print '<input type="text" size="8" name="'.$namef.'" value="'.$_POST[$namef].'">';
                    }
                    else
                    {
                        print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.$_POST[$namef].'" disabled="disabled">';
                        print '<input type="hidden" name="'.$namef.'" value="'.$_POST[$namef].'">';
                    }
                    print "</td>";

                    // Warning
                    print '<td align="center" width="16">';
                    //print "xx".$amounts[$invoice->id]."-".$amountsresttopay[$invoice->id]."<br>";
                    if ($amounts[$invoice->id] && (abs($amounts[$invoice->id]) > abs($amountsresttopay[$invoice->id])))
                    {
                        print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));
                    }
                    print '</td>';

					$parameters=array();
					$reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$objp,$action); // Note that $action and $object may have been modified by hook

                    print "</tr>\n";

                    $total+=$objp->total;
                    $total_ttc+=$objp->total_ttc;
                    $totalrecu+=$paiement;
                    $totalrecucreditnote+=$creditnotes;
                    $totalrecudeposits+=$deposits;
                    $i++;
                }
                if ($i > 1)
                {
                    // Print total
                    print '<tr class="liste_total">';
                    print '<td colspan="2" align="left">'.$langs->trans('TotalTTC').'</td>';
                    print '<td align="right"><b>'.price($sign * $total_ttc).'</b></td>';
                    print '<td align="right"><b>'.price($sign * $totalrecu);
                    if ($totalrecucreditnote) print '+'.price($totalrecucreditnote);
                    if ($totalrecudeposits) print '+'.price($totalrecudeposits);
                    print '</b></td>';
                    print '<td align="right"><b>'.price($sign * price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits,'MT')).'</b></td>';
                    print '<td align="right" id="result" style="font-weight: bold;"></td>';
                    print '<td align="center">&nbsp;</td>';
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
        	$checkboxlabel=$langs->trans("ClosePaidInvoicesAutomatically");
        	if ($facture->type == 2) $checkboxlabel=$langs->trans("ClosePaidCreditNotesAutomatically");
        	$buttontitle=$langs->trans('ToMakePayment');
        	if ($facture->type == 2) $buttontitle=$langs->trans('ToMakePaymentBack');

        	print '<center><br>';
        	print '<input type="checkbox" checked="checked" name="closepaidinvoices"> '.$checkboxlabel;
            /*if (! empty($conf->prelevement->enabled))
            {
                $langs->load("withdrawals");
                if (! empty($conf->global->WITHDRAW_DISABLE_AUTOCREATE_ONPAYMENTS)) print '<br>'.$langs->trans("IfInvoiceNeedOnWithdrawPaymentWontBeClosed");
            }*/
            print '<br><input type="submit" class="button" value="'.dol_escape_htmltag($buttontitle).'"><br><br>';
            print '</center>';
        }



        // Message d'erreur
        if ($fiche_erreur_message)
        {
            print $fiche_erreur_message;
        }

        // Form to confirm payment
        if ($action == 'add_paiement')
        {
            $preselectedchoice=$addwarning?'no':'yes';

            print '<br>';
            $text=$langs->trans('ConfirmCustomerPayment',$totalpaiement,$langs->trans("Currency".$conf->currency));
            if (GETPOST('closepaidinvoices'))
            {
                $text.='<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
                print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
            }
            $form->form_confirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type,$langs->trans('ReceivedCustomersPayments'),$text,'confirm_paiement',$formquestion,$preselectedchoice);
        }

        print "</form>\n";
    }
}


/**
 *  Show list of payments
 */
if (! GETPOST('action'))
{
    if ($page == -1) $page = 0 ;
    $limit = $conf->liste_limit;
    $offset = $limit * $page ;

    if (! $sortorder) $sortorder='DESC';
    if (! $sortfield) $sortfield='p.datep';

    $sql = 'SELECT p.datep as dp, p.amount, f.amount as fa_amount, f.facnumber';
    $sql.=', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'c_paiement as c';
    $sql.= ' WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id';
    $sql.= ' AND f.entity = '.$conf->entity;
    if ($socid)
    {
        $sql.= ' AND f.fk_soc = '.$socid;
    }

    $sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
    $sql.= $db->plimit($limit+1, $offset);
    $resql = $db->query($sql);

    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=True;

        print_barre_liste($langs->trans('Payments'), $page, 'paiement.php','',$sortfield,$sortorder,'',$num);
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('Invoice'),'paiement.php','facnumber','','','',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),'paiement.php','dp','','','',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Type'),'paiement.php','libelle','','','',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Amount'),'paiement.php','fa_amount','','','align="right"',$sortfield,$sortorder);
        print '<td>&nbsp;</td>';
        print "</tr>\n";

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'>';
            print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.$objp->facnumber."</a></td>\n";
            print '<td>'.dol_print_date($db->jdate($objp->dp))."</td>\n";
            print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
            print '<td align="right">'.price($objp->amount).'</td><td>&nbsp;</td>';
			
			$parameters=array();
			$reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$objp,$action); // Note that $action and $object may have been modified by hook
			
            print '</tr>';
            $i++;
        }
        print '</table>';
    }
}

$db->close();

llxFooter();
?>
