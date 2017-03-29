<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Juanjo Menent			<jmenent@2byte.es>
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
 *	\file       htdocs/fourn/facture/paiement.php
 *	\ingroup    fournisseur,facture
 *	\brief      Payment page for suppliers invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');
$langs->load('compta');

$action     = GETPOST('action','alpha');
$confirm	= GETPOST('confirm');

$facid=GETPOST('facid','int');
$socid=GETPOST('socid','int');
$accountid	= GETPOST('accountid');

$search_ref=GETPOST("search_ref","int");
$search_account=GETPOST("search_account","int");
$search_paymenttype=GETPOST("search_paymenttype");
$search_amount=GETPOST("search_amount",'alpha');    // alpha because we must be able to search on "< x"
$search_company=GETPOST("search_company",'alpha');
$search_payment_num=GETPOST('search_payment_num','alpha');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";
$optioncss = GETPOST('optioncss','alpha');

$amounts = array();array();
$amountsresttopay=array();
$addwarning=0;

$multicurrency_amounts=array();
$multicurrency_amountsresttopay=array();

// Security check
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paymentsupplier'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('paymentsupplier');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$arrayfields=array();



/*
 * Actions
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
{
    $search_ref="";
    $search_account="";
    $search_amount="";
    $search_paymenttype="";
    $search_payment_num="";
    $search_company="";
    $day='';
    $year='';
    $month='';
    $search_array_options=array();
}

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm=='yes'))
	{
	    $error = 0;

	    $datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	    $paiement_id = 0;
	    $totalpayment = 0;
	    $atleastonepaymentnotnull = 0;

	    // Generate payment array and check if there is payment higher than invoice and payment date before invoice date
	    $tmpinvoice=new FactureFournisseur($db);
	    foreach ($_POST as $key => $value)
	    {
	        if (substr($key,0,7) == 'amount_')
	        {
	            $cursorfacid = substr($key,7);
	            $amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $totalpayment = $totalpayment + $amounts[$cursorfacid];
	            if (! empty($amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result=$tmpinvoice->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $amountsresttopay[$cursorfacid]=price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
	            if ($amounts[$cursorfacid])
	            {
		            // Check amount
		            if ($amounts[$cursorfacid] && (abs($amounts[$cursorfacid]) > abs($amountsresttopay[$cursorfacid])))
		            {
		                $addwarning=1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPaySupplier")).' '.$langs->trans("HelpPaymentHigherThanReminderToPaySupplier");
		            }
		            // Check date
		            if ($datepaye && ($datepaye < $tmpinvoice->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye,'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++]=array('type' => 'hidden','name' => $key,  'value' => $_POST[$key]);
	        }
			elseif (substr($key,0,21) == 'multicurrency_amount_')
			{
				$cursorfacid = substr($key,21);
	            $multicurrency_amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $multicurrency_totalpayment += $multicurrency_amounts[$cursorfacid];
	            if (! empty($multicurrency_amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result=$tmpinvoice->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $multicurrency_amountsresttopay[$cursorfacid]=price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement(1));
	            if ($multicurrency_amounts[$cursorfacid])
	            {
		            // Check amount
		            if ($multicurrency_amounts[$cursorfacid] && (abs($multicurrency_amounts[$cursorfacid]) > abs($multicurrency_amountsresttopay[$cursorfacid])))
		            {
		                $addwarning=1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPaySupplier")).' '.$langs->trans("HelpPaymentHigherThanReminderToPaySupplier");
		            }
		            // Check date
		            if ($datepaye && ($datepaye < $tmpinvoice->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye,'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++]=array('type' => 'hidden','name' => $key,  'value' => GETPOST($key, 'int'));
			}
	    }

	    // Check parameters
	    if ($_POST['paiementid'] <= 0)
	    {
	    	setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('PaymentMode')), null, 'errors');
	        $error++;
	    }

	    if (! empty($conf->banque->enabled))
	    {
	        // If bank module is on, account is required to enter a payment
	        if (GETPOST('accountid') <= 0)
	        {
	        	setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')), null, 'errors');
	            $error++;
	        }
	    }

	    if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull))
	    {
	    	setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->trans('PaymentAmount')), null, 'errors');
	        $error++;
	    }

	    if (empty($datepaye))
	    {
	    	setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('Date')), null, 'errors');
	        $error++;
	    }
		
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

	    $datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

	    if (! $error)
	    {
	        $db->begin();

	        // Creation de la ligne paiement
	        $paiement = new PaiementFourn($db);
	        $paiement->datepaye     = $datepaye;
	        $paiement->amounts      = $amounts;   // Array of amounts
	        $paiement->multicurrency_amounts = $multicurrency_amounts;
	        $paiement->paiementid   = $_POST['paiementid'];
	        $paiement->num_paiement = $_POST['num_paiement'];
	        $paiement->note         = $_POST['comment'];
	        if (! $error)
	        {
	            $paiement_id = $paiement->create($user,(GETPOST('closepaidinvoices')=='on'?1:0));
	            if ($paiement_id < 0)
	            {
	            	setEventMessages($paiement->error, $paiement->errors, 'errors');
	                $error++;
	            }
	        }

	        if (! $error)
	        {
	            $result=$paiement->addPaymentToBank($user,'payment_supplier','(SupplierInvoicePayment)',$accountid,'','');
	            if ($result < 0)
	            {
	            	setEventMessages($paiement->error, $paiement->errors, 'errors');
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
	            if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$invoiceid;
	            else $loc = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$paiement_id;
	            header('Location: '.$loc);
	            exit;
	        }
	        else
	        {
	            $db->rollback();
	        }
	    }
	}
}


/*
 * View
 */

$supplierstatic=new Societe($db);
$invoicesupplierstatic = new FactureFournisseur($db);

llxHeader('',$langs->trans('ListPayment'));

$form=new Form($db);

if ($action == 'create' || $action == 'confirm_paiement' || $action == 'add_paiement')
{
    $object = new FactureFournisseur($db);
    $object->fetch($facid);

    $datefacture=dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
    $dateinvoice=($datefacture==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$datefacture);

    $sql = 'SELECT s.nom as name, s.rowid as socid,';
    $sql.= ' f.rowid, f.ref, f.ref_supplier, f.amount, f.total_ttc as total, f.fk_mode_reglement, f.fk_account';
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'facture_fourn as f';
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' WHERE f.fk_soc = s.rowid';
    $sql.= ' AND f.rowid = '.$facid;
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        if ($num)
        {
            $obj = $db->fetch_object($resql);
            $total = $obj->total;
            
            print load_fiche_titre($langs->trans('DoPayment'));

            print '<form id="payment_form" name="addpaiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="add_paiement">';
            print '<input type="hidden" name="facid" value="'.$facid.'">';
            print '<input type="hidden" name="ref_supplier" value="'.$obj->ref_supplier.'">';
            print '<input type="hidden" name="socid" value="'.$obj->socid.'">';
            print '<input type="hidden" name="societe" value="'.$obj->name.'">';

            dol_fiche_head(null);
            
            print '<table class="border" width="100%">';

            print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans('Company').'</td><td>';
            $supplierstatic->id=$obj->socid;
            $supplierstatic->name=$obj->name;
            print $supplierstatic->getNomUrl(1,'supplier');
            print '</td></tr>';
            print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
            $form->select_date($dateinvoice,'','','','',"addpaiement",1,1,0,0,'','',$object->date);
            print '</td></tr>';
            print '<tr><td class="fieldrequired">'.$langs->trans('PaymentMode').'</td><td>';
            $form->select_types_paiements(empty($_POST['paiementid'])?$obj->fk_mode_reglement:$_POST['paiementid'],'paiementid');
            print '</td>';
            if (! empty($conf->banque->enabled))
            {
                print '<tr><td class="fieldrequired">'.$langs->trans('Account').'</td><td>';
                $form->select_comptes(empty($accountid)?$obj->fk_account:$accountid,'accountid',0,'',2);
                print '</td></tr>';
            }
            else
            {
                print '<tr><td>&nbsp;</td></tr>';
            }
            print '<tr><td>'.$langs->trans('Numero').'</td><td><input name="num_paiement" type="text" value="'.(empty($_POST['num_paiement'])?'':$_POST['num_paiement']).'"></td></tr>';
            print '<tr><td>'.$langs->trans('Comments').'</td>';
            print '<td class="tdtop">';
            print '<textarea name="comment" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.(empty($_POST['comment'])?'':$_POST['comment']).'</textarea></td></tr>';
            print '</table>';
            dol_fiche_end();

            
			$parameters=array('facid'=>$facid, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
			$reshook=$hookmanager->executeHooks('paymentsupplierinvoices',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
			$error=$hookmanager->error; $errors=$hookmanager->errors;
			if (empty($reshook))
			{
				/*
	             * Autres factures impayees
	             */
	            $sql = 'SELECT f.rowid as facid, f.ref, f.ref_supplier, f.total_ht, f.total_ttc, f.multicurrency_total_ttc, f.datef as df,';
	            $sql.= ' SUM(pf.amount) as am, SUM(pf.multicurrency_amount) as multicurrency_am';
	            $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
	            $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
	            $sql.= " WHERE f.entity = ".$conf->entity;
	            $sql.= ' AND f.fk_soc = '.$object->socid;
	            $sql.= ' AND f.paye = 0';
	            $sql.= ' AND f.fk_statut = 1';  // Statut=0 => non validee, Statut=2 => annulee
	            $sql.= ' GROUP BY f.rowid, f.ref, f.ref_supplier, f.total_ht, f.total_ttc, f.multicurrency_total_ttc, f.datef';
	            $resql = $db->query($sql);
	            if ($resql)
	            {
	                $num = $db->num_rows($resql);
	                if ($num > 0)
	                {
	                    $i = 0;
	                    print '<br>';

	                    if (!empty($conf->use_javascript_ajax)){
							//Add js for AutoFill
							print "\n".'<script type="text/javascript" language="javascript">';
							print ' $(document).ready(function () {';
							print ' 	$(".AutoFillAmout").on(\'click touchstart\', function(){
											$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value"));
										});';
							print '	});'."\n";
							print '	</script>'."\n";
						}

                        print '<div class="div-table-responsive-no-min">';
                        print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

						print '<tr class="liste_titre">';
	                    print '<td>'.$langs->trans('Invoice').'</td>';
	                    print '<td>'.$langs->trans('RefSupplier').'</td>';
	                    print '<td align="center">'.$langs->trans('Date').'</td>';
	                    if (!empty($conf->multicurrency->enabled)) print '<td>'.$langs->trans('Currency').'</td>';
						if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$langs->trans('MulticurrencyAmountTTC').'</td>';
						if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$langs->trans('MulticurrencyAlreadyPaid').'</td>';
						if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$langs->trans('MulticurrencyRemainderToPay').'</td>';
	                    print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
	                    print '<td align="right">'.$langs->trans('AlreadyPaid').'</td>';
	                    print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
						print '<td align="center">'.$langs->trans('PaymentAmount').'</td>';
						if (!empty($conf->multicurrency->enabled)) print '<td align="center">'.$langs->trans('MulticurrencyPaymentAmount').'</td>';
	                    print '</tr>';

	                    $var=True;
	                    $total=0;
	                    $total_ttc=0;
	                    $totalrecu=0;
	                    while ($i < $num)
	                    {
	                        $objp = $db->fetch_object($resql);
	                        $var=!$var;
	                        print '<tr '.$bc[$var].'>';
	                        
	                        // Ref
	                        print '<td>';
	                        $invoicesupplierstatic->ref=$objp->ref;
	                        $invoicesupplierstatic->id=$objp->facid;
	                        print $invoicesupplierstatic->getNomUrl(1);
	                        print '</td>';
	                        
	                        // Ref supplier
	                        print '<td>'.$objp->ref_supplier.'</td>';
	                        
	                        // Date
	                        if ($objp->df > 0 )
	                        {
	                            print '<td align="center">';
	                            print dol_print_date($db->jdate($objp->df), 'day').'</td>';
	                        }
	                        else
	                        {
	                            print '<td align="center"><b>!!!</b></td>';
	                        }
	                        
	                        // Multicurrency
	                        if (!empty($conf->multicurrency->enabled)) 
	                        {
    	                        // Currency
	                            print '<td align="center">'.$objp->multicurrency_code."</td>\n";
	                        
	                            print '<td align="right">';
	                            if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
	                            {
	                                print price($objp->multicurrency_total_ttc);
	                            }
	                            print '</td>';

							    print '<td align="right">';
							    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) 
						        {         
						            print price($objp->multicurrency_am);
							    }
							    print '</td>';
							    
							    print '<td align="right">';
							    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) 
						        {
						            print price($objp->multicurrency_total_ttc - $objp->multicurrency_am);
							    }
							    print '</td>';
	                        }

	                        print '<td align="right">'.price($objp->total_ttc).'</td>';
							
	                        print '<td align="right">'.price($objp->am).'</td>';
							
	                        print '<td align="right">'.price($objp->total_ttc - $objp->am).'</td>';
							
	                        print '<td align="center">';
	                        $namef = 'amount_'.$objp->facid;
	                        if (!empty($conf->use_javascript_ajax))
								print img_picto("Auto fill",'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($objp->total_ttc - $objp->am)."'");
	                        print '<input type="text" size="8" name="'.$namef.'" value="'.GETPOST($namef).'">';
							print "</td>";
							
							// Multicurrency
							if (!empty($conf->multicurrency->enabled)) 
							{
								print '<td align="center">';
								if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
								{
    			                    $namef = 'multicurrency_amount_'.$objp->facid;
    			                    if (!empty($conf->use_javascript_ajax))
    									print img_picto("Auto fill",'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($objp->multicurrency_total_ttc - $objp->multicurrency_am)."'");
    		                        print '<input type="text" size="8" class="multicurrency_amount" name="'.$namef.'" value="'.GETPOST($namef).'">';
								}
			                    print "</td>";
							}
							
							print "</tr>\n";
	                        $total+=$objp->total_ht;
	                        $total_ttc+=$objp->total_ttc;
	                        $totalrecu+=$objp->am;
	                        $i++;
	                    }
	                    if ($i > 1)
	                    {
	                        // Print total
	                        print '<tr class="liste_total">';
	                        print '<td colspan="3" align="left">'.$langs->trans('TotalTTC').':</td>';
							if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
							if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
							if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
							if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
	                        print '<td align="right"><b>'.price($total_ttc).'</b></td>';
							print '<td align="right"><b>'.price($totalrecu).'</b></td>';
	                        print '<td align="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
	                        print '<td align="center">&nbsp;</td>';
	                        if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
	                        print "</tr>\n";
	                    }
	                    print "</table>\n";
	                    
	                    print "</div>";
	                }
	                $db->free($resql);
	            }
	            else
	           {
	                dol_print_error($db);
	            }
			}

	        // Bouton Enregistrer
	        if ($action != 'add_paiement')
	        {
				print '<br><div class="center"><input type="checkbox" checked name="closepaidinvoices"> '.$langs->trans("ClosePaidInvoicesAutomatically");
				print '<br><input type="submit" class="button" value="'.$langs->trans('ToMakePayment').'"></div>';
	        }

            // Form to confirm payment
	        if ($action == 'add_paiement')
	        {
	            $preselectedchoice=$addwarning?'no':'yes';

	            print '<br>';
	            if (!empty($totalpayment)) $text=$langs->trans('ConfirmSupplierPayment',price($totalpayment),$langs->trans("Currency".$conf->currency));
				if (!empty($multicurrency_totalpayment)) 
				{
					$text.='<br>'.$langs->trans('ConfirmSupplierPayment',price($multicurrency_totalpayment),$langs->trans("paymentInInvoiceCurrency"));
				}
	            if (GETPOST('closepaidinvoices'))
	            {
	                $text.='<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
	                print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
	            }
	            print $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type,$langs->trans('PayedSuppliersPayments'),$text,'confirm_paiement',$formquestion,$preselectedchoice);
	        }

            print '</form>';
        }
    }
    else dol_print_error($db);        
}

/*
 * Show list
 */
if (empty($action))
{
    $limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
    $sortfield = GETPOST("sortfield",'alpha');
    $sortorder = GETPOST("sortorder",'alpha');
    $page=GETPOST("page",'int');
    if ($page == -1) { $page = 0 ; }
    $offset = $limit * $page ;
    $pageprev = $page - 1;
    $pagenext = $page + 1;
    if (! $sortorder) $sortorder='DESC';
    if (! $sortfield) $sortfield='p.datep';

    $sql = 'SELECT p.rowid as pid, p.datep as dp, p.amount as pamount, p.num_paiement,';
    $sql.= ' s.rowid as socid, s.nom as name,';
    $sql.= ' c.code as paiement_type, c.libelle as paiement_libelle,';
    $sql.= ' ba.rowid as bid, ba.label,';
    if (!$user->rights->societe->client->voir) $sql .= ' sc.fk_soc, sc.fk_user,';
    $sql.= ' SUM(f.amount)';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn AS p';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS pf ON p.rowid=pf.fk_paiementfourn';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS f ON f.rowid=pf.fk_facturefourn';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON p.fk_paiement = c.id';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
    if (!$user->rights->societe->client->voir) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE f.entity = ".$conf->entity;
    if (!$user->rights->societe->client->voir) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid > 0) $sql .= ' AND f.fk_soc = '.$socid;
    // Search criteria
    if ($search_ref)       		    $sql .= natural_search('p.rowid', $search_ref);
    if ($search_account > 0)      	$sql .=" AND b.fk_account=".$search_account;
    if ($search_paymenttype != "")  $sql .=" AND c.code='".$db->escape($search_paymenttype)."'";
    if ($search_payment_num != '')  $sql .= natural_search('p.num_paiement', $search_payment_num);
    if ($search_amount)      		$sql .= natural_search('p.amount', $search_amount, 1);
    if ($search_company)     		$sql .= natural_search('s.nom', $search_company);
    $sql.= " GROUP BY p.rowid, p.datep, p.amount, p.num_paiement, s.rowid, s.nom, c.code, c.libelle, ba.rowid, ba.label";
    if (!$user->rights->societe->client->voir) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= $db->order($sortfield,$sortorder);
    
    $nbtotalofrecords = '';
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
    {
        $result = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
    }
    
    $sql.= $db->plimit($limit+1, $offset);

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=True;

        $param='';
        if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
        if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
        if ($search_ref)            $param.=($search_ref?"&search_ref=".urlencode($search_ref):"");
        if ($search_company)        $param.=($search_company?"&search_company=".urlencode($search_company):"");
        if ($search_amount != '')   $param.=($search_amount?"&search_amount=".urlencode($search_amount):"");
        if ($search_payment_num)    $param.=($search_payment_num?"&search_payment_num=".urlencode($search_payment_num):"");
    	if ($optioncss != '')       $param.='&optioncss='.$optioncss;
    	// Add $param from extra fields
    	foreach ($search_array_options as $key => $val)
    	{
    	    $crit=$val;
    	    $tmpkey=preg_replace('/search_options_/','',$key);
    	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    	}
    	
    	$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));
        
        print_barre_liste($langs->trans('SupplierPayments'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit);

        print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

        $moreforfilter='';
        
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    	else $moreforfilter = $hookmanager->resPrint;
    
        if ($moreforfilter)
        {
       		print '<div class="liste_titre liste_titre_bydiv centpercent">';
            print $moreforfilter;
            print '</div>';
        }
    
        $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
        $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
        
        print '<div class="div-table-responsive">';
        print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('RefPayment'),$_SERVER["PHP_SELF"],'p.rowid','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'dp','',$param,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('ThirdParty'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Type'),$_SERVER["PHP_SELF"],'c.libelle','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Numero"),$_SERVER["PHP_SELF"],"p.num_paiement","",$param,"",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Account'),$_SERVER["PHP_SELF"],'ba.label','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Amount'),$_SERVER["PHP_SELF"],'p.amount','',$param,'align="right"',$sortfield,$sortorder);
        //print_liste_field_titre($langs->trans('Invoice'),$_SERVER["PHP_SELF"],'ref_supplier','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre('');
		print "</tr>\n";

        // Lines for filters fields
        print '<tr class="liste_titre">';
        print '<td  class="liste_titre" align="left">';
        print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
        print '</td>';
        print '<td class="liste_titre">&nbsp;</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" size="6" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
        print '</td>';
        print '<td class="liste_titre">';
        $form->select_types_paiements($search_paymenttype,'search_paymenttype','',2,1,1);
        print '</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" size="4" name="search_payment_num" value="'.dol_escape_htmltag($search_payment_num).'">';
        print '</td>';
        print '<td class="liste_titre">';
        $form->select_comptes($search_account,'search_account',0,'',1);
        print '</td>';
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="4" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
        print '</td>';
        print '<td class="liste_titre" align="right">';
        $searchpitco=$form->showFilterAndCheckAddButtons(0);
        print $searchpitco;
        print '</td>';
        print "</tr>\n";

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'>';

            // Ref payment
            print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$objp->pid.'">'.img_object($langs->trans('ShowPayment'),'payment').' '.$objp->pid.'</a></td>';

            // Date
            print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";

            // Thirdparty
            print '<td>';
            if ($objp->socid) print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.dol_trunc($objp->name,32).'</a>';
            else print '&nbsp;';
            print '</td>';

            // Type
            $payment_type = $langs->trans("PaymentType".$objp->paiement_type)!=("PaymentType".$objp->paiement_type)?$langs->trans("PaymentType".$objp->paiement_type):$objp->paiement_libelle;
            print '<td>'.$payment_type.' '.dol_trunc($objp->num_paiement,32)."</td>\n";

            // Payment number
            print '<td>'.$objp->num_paiement.'</td>';
            
            print '<td>';
            if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.dol_trunc($objp->label,24).'</a>';
            else print '&nbsp;';
            print '</td>';

            print '<td align="right">'.price($objp->pamount).'</td>';

            // Ref invoice
            /*$invoicesupplierstatic->ref=$objp->ref_supplier;
            $invoicesupplierstatic->id=$objp->facid;
            print '<td class="nowrap">';
            print $invoicesupplierstatic->getNomUrl(1);
            print '</td>';*/

			print '<td>&nbsp;</td>';
            print '</tr>';
            $i++;
        }
        print "</table>";
        print "</div>";
        print "</form>\n";
    }
    else
    {
        dol_print_error($db);
    }
}

llxFooter();
$db->close();
