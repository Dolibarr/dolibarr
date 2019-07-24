<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Ferran Marcet			<fmarcet@2byte.es>
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
 *	\file       htdocs/compta/facture/prelevement.php
 *	\ingroup    facture
 *	\brief      Gestion des prelevement d'une facture
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

if (!$user->rights->facture->lire) accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'withdrawals', 'companies'));

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');

$fieldid = (! empty($ref)?'facnumber':'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid);

$object = new Facture($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
	if ($ret > 0)
	{
		$object->fetch_thirdparty();
	}
}

$hookmanager->initHooks(array('directdebitcard','globalcard'));



/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($action == "new")
    {
        if ($object->id > 0)
        {
            $db->begin();

            $result = $object->demande_prelevement($user, GETPOST('withdraw_request_amount'));
            if ($result > 0)
            {
                $db->commit();

                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            }
            else
            {
                $db->rollback();
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }
        $action='';
    }

    if ($action == "delete")
    {
        if ($object->id > 0)
        {
            $result = $object->demande_prelevement_delete($user, GETPOST('did'));
            if ($result == 0)
            {
                header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
                exit;
            }
        }
    }
}


/*
 * View
 */

$now=dol_now();

$title = $langs->trans('InvoiceCustomer') . " - " . $langs->trans('StandingOrders');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);

$form = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($object->id > 0)
{
	$selleruserevenustamp = $mysoc->useRevenueStamp();

	$totalpaye  = $object->getSommePaiement();
	$totalcreditnotes = $object->getSumCreditNotesUsed();
	$totaldeposits = $object->getSumDepositsUsed();
	//print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

	// We can also use bcadd to avoid pb with floating points
	// For example print 239.2 - 229.3 - 9.9; does not return 0.
	//$resteapayer=bcadd($object->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
	//$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
	$resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

	if ($object->paye) $resteapayer=0;
	$resteapayeraffiche=$resteapayer;

	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	} else {
		$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
		$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
	}

	$absolute_discount=$object->thirdparty->getAvailableDiscounts('',$filterabsolutediscount);
	$absolute_creditnote=$object->thirdparty->getAvailableDiscounts('',$filtercreditnote);
	$absolute_discount=price2num($absolute_discount,'MT');
	$absolute_creditnote=price2num($absolute_creditnote,'MT');

	$author = new User($db);
	if ($object->user_author)
	{
		$author->fetch($object->user_author);
	}

	$head = facture_prepare_head($object);

	dol_fiche_head($head, 'standingorders', $langs->trans('InvoiceCustomer'), -1, 'bill');

	// Invoice content

	$linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->facture->creer)
	    {
	        if ($action != 'classify')
	            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	            $morehtmlref.=' : ';
	        	if ($action == 'classify') {
	                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                $morehtmlref.='<input type="hidden" name="action" value="classin">';
	                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	                $morehtmlref.='</form>';
	            } else {
	                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	            }
	    } else {
	        if (! empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
	            $morehtmlref.=$proj->ref;
	            $morehtmlref.='</a>';
	        } else {
	            $morehtmlref.='';
	        }
	    }
	}
	$morehtmlref.='</div>';

	$object->totalpaye = $totalpaye;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Type
	print '<tr><td class="titlefield">'.$langs->trans('Type').'</td><td colspan="3">';
	print $object->getLibType();
	if ($object->type == Facture::TYPE_REPLACEMENT)
	{
		$facreplaced=new Facture($db);
		$facreplaced->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
	}
	if ($object->type == Facture::TYPE_CREDIT_NOTE)
	{
		$facusing=new Facture($db);
		$facusing->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
	}

	$facidavoir=$object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0)
	{
		print ' ('.$langs->transnoentities("InvoiceHasAvoir");
		$i=0;
		foreach($facidavoir as $id)
		{
			if ($i==0) print ' ';
			else print ',';
			$facavoir=new Facture($db);
			$facavoir->fetch($id);
			print $facavoir->getNomUrl(1);
		}
		print ')';
	}
	/*
	if ($facidnext > 0)
	{
		$facthatreplace=new Facture($db);
		$facthatreplace->fetch($facidnext);
		print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
	}
	*/
	print '</td></tr>';

	// Discounts
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="3">';

	$thirdparty = $object->thirdparty;
	$discount_type = 0;
	$backtopage = urlencode($_SERVER["PHP_SELF"] . '?facid=' . $object->id);
	$cannotApplyDiscount = 1;
	include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

	print '</td></tr>';

	// Date invoice
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateInvoice');
	print '</td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editinvoicedate' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';

	if ($object->type != Facture::TYPE_CREDIT_NOTE)
	{
		if ($action == 'editinvoicedate')
		{
			$form->form_date($_SERVER['PHP_SELF'].'?id='.$object->id,$object->date,'invoicedate');
		}
		else
		{
			print dol_print_date($object->date,'daytext');
		}
	}
	else
	{
		print dol_print_date($object->date,'daytext');
	}
	print '</td>';
	print '</tr>';

	// Payment condition
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editconditions' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->type != Facture::TYPE_CREDIT_NOTE)
	{
		if ($action == 'editconditions')
		{
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
		}
		else
		{
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none');
		}
	}
	else
	{
		print '&nbsp;';
	}
	print '</td></tr>';

	// Date payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateMaxPayment');
	print '</td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editpaymentterm' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->type != Facture::TYPE_CREDIT_NOTE)
	{
		if ($action == 'editpaymentterm')
		{
			$form->form_date($_SERVER['PHP_SELF'].'?id='.$object->id,$object->date_lim_reglement,'paymentterm');
		}
		else
		{
			print dol_print_date($object->date_lim_reglement,'daytext');
			if ($object->hasDelay()) {
				print img_warning($langs->trans('Late'));
			}
		}
	}
	else
	{
		print '&nbsp;';
	}
	print '</td></tr>';

	// Payment mode
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && ! empty($object->brouillon) && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
	}
	print '</td></tr>';

	// Bank Account
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('BankAccount');
	print '<td>';
	if (($action != 'editbankaccount') && $user->rights->commande->creer && ! empty($object->brouillon))
	    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editbankaccount')
	{
	    $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
	}
	else
	{
	    $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
	}
	print "</td>";
	print '</tr>';

	print '<tr><td>'.$langs->trans("RIB").'</td><td colspan="3">';
	print $object->thirdparty->display_rib();
	print '</td></tr>';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
	{
	    // Multicurrency Amount HT
	    print '<tr><td class="titlefieldmiddle">' . fieldLabel('MulticurrencyAmountHT','multicurrency_total_ht') . '</td>';
	    print '<td class="nowrap">' . price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
	    print '</tr>';

	    // Multicurrency Amount VAT
	    print '<tr><td>' . fieldLabel('MulticurrencyAmountVAT','multicurrency_total_tva') . '</td>';
	    print '<td class="nowrap">' . price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
	    print '</tr>';

	    // Multicurrency Amount TTC
	    print '<tr><td>' . fieldLabel('MulticurrencyAmountTTC','multicurrency_total_ttc') . '</td>';
	    print '<td class="nowrap">' . price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
	    print '</tr>';
	}

	// Amount
	print '<tr><td class="titlefield">' . $langs->trans('AmountHT') . '</td>';
	print '<td class="nowrap">' . price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

	// Vat
	print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td colspan="3" class="nowrap">' . price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	print '</tr>';

	// Amount Local Taxes
	if (($mysoc->localtax1_assuj == "1" && $mysoc->useLocalTax(1)) || $object->total_localtax1 != 0) 	// Localtax1
	{
	    print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
	    print '<td class="nowrap">' . price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}
	if (($mysoc->localtax2_assuj == "1" && $mysoc->useLocalTax(2)) || $object->total_localtax2 != 0) 	// Localtax2
	{
	    print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
	    print '<td class=nowrap">' . price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}

	// Revenue stamp
	if ($selleruserevenustamp) 	// Test company use revenue stamp
	{
	    print '<tr><td>';
	    print '<table class="nobordernopadding" width="100%"><tr><td>';
	    print $langs->trans('RevenueStamp');
	    print '</td>';
	    if ($action != 'editrevenuestamp' && ! empty($object->brouillon) && $user->rights->facture->creer)
	    {
	        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editrevenuestamp&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetRevenuStamp'), 1) . '</a></td>';
	    }
        print '</tr></table>';
        print '</td><td>';
       	print price($object->revenuestamp, 1, '', 1, - 1, - 1, $conf->currency);
        print '</td></tr>';
	}

	// Total with tax
	print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td class="nowrap">' . price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

    $resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

    // TODO Replace this by an include with same code to show already done payment visible in invoice card
    print '<tr><td>'.$langs->trans('RemainderToPay').'</td><td class="nowrap">'.price($resteapayer, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';

	print '</table>';

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	dol_fiche_end();


	$numopen = 0; $pending = 0; $numclosed = 0;


	// How many Direct debit opened requests ?

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
	$sql .= " , pfd.date_traite as date_traite";
	$sql .= " , pfd.amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
	$sql .= " WHERE fk_facture = ".$object->id;
	$sql .= " AND pfd.traite = 0";
	$sql .= " ORDER BY pfd.date_demande DESC";

	$result_sql = $db->query($sql);
	if ($result_sql)
	{
		$num = $db->num_rows($result_sql);
		$numopen = $num;
	}
	else
	{
		dol_print_error($db);
	}

	// For wich amount ?

	$sql = "SELECT SUM(pfd.amount) as amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
	$sql .= " WHERE fk_facture = ".$object->id;
	$sql .= " AND pfd.traite = 0";

	$result_sql = $db->query($sql);
	if ($result_sql)
	{
		$obj = $db->fetch_object($result_sql);
		if ($obj) $pending = $obj->amount;
	}
	else
	{
		dol_print_error($db);
	}


	/*
	 * Buttons
	 */

	print "\n<div class=\"tabsAction\">\n";

	// Add a transfer request
	if ($object->statut > Facture::STATUS_DRAFT && $object->paye == 0 && $num == 0)
	{
	    if ($resteapayer > 0)
	    {
    		if ($user->rights->prelevement->bons->creer)
    		{
    			$remaintopaylesspendingdebit = $resteapayer - $pending;

    			print '<form method="POST" action="">';
    			print '<input type="hidden" name="id" value="' . $object->id . '" />';
    			print '<input type="hidden" name="action" value="new" />';
    			print '<label for="withdraw_request_amount">' . $langs->trans('WithdrawRequestAmount') . ' </label>';
    			print '<input type="text" id="withdraw_request_amount" name="withdraw_request_amount" value="' . $remaintopaylesspendingdebit . '" size="10" />';
    			print '<input type="submit" class="butAction" value="'.$langs->trans("MakeWithdrawRequest").'" />';
    			print '</form>';
    		}
    		else
    		{
    			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
    		}
	    }
	    else
        {
            print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("AmountMustBePositive")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
        }
	}
	else
	{
		if ($num == 0)
		{
			if ($object->statut > Facture::STATUS_DRAFT) print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("AlreadyPaid")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
			else print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("Draft")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
		}
		else print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("RequestAlreadyDone")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
	}

	print "</div><br>\n";


	print '<div class="opacitymedium">'.$langs->trans("DoStandingOrdersBeforePayments").'</div><br>';


	/*
	 * Withdrawals
	 */

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td align="left">'.$langs->trans("DateRequest").'</td>';
	print '<td align="center">'.$langs->trans("User").'</td>';
	print '<td align="center">'.$langs->trans("Amount").'</td>';
	print '<td align="center">'.$langs->trans("WithdrawalReceipt").'</td>';
	print '<td>&nbsp;</td>';
	print '<td align="center">'.$langs->trans("DateProcess").'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
	$sql.= " , pfd.date_traite as date_traite, pfd.amount,";
	$sql.= " u.rowid as user_id, u.lastname, u.firstname, u.login";
	$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
	$sql.= " WHERE fk_facture = ".$object->id;
	$sql.= " AND pfd.traite = 0";
	$sql.= " ORDER BY pfd.date_demande DESC";

	$result_sql = $db->query($sql);

	$num = 0;
	if ($result_sql)
	{
		$i = 0;

		$num = $db->num_rows($result);
		while ($i < $num)
		{
			$obj = $db->fetch_object($result_sql);

			print '<tr class="oddeven">';
			print '<td align="left">'.dol_print_date($db->jdate($obj->date_demande),'day')."</td>\n";
			print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
			print '<td align="center">'.price($obj->amount).'</td>';
			print '<td align="center">-</td>';
			print '<td>&nbsp;</td>';

			print '<td align="center">'.$langs->trans("OrderWaiting").'</td>';

			print '<td align="right">';
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=delete&amp;did='.$obj->rowid.'">';
			print img_delete();
			print '</a></td>';

			print "</tr>\n";
			$i++;
		}

		$db->free($result_sql);
	}
	else
	{
		dol_print_error($db);
	}


	// Past requests

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande, pfd.date_traite, pfd.fk_prelevement_bons, pfd.amount,";
	$sql.= " pb.ref,";
	$sql.= " u.rowid as user_id, u.lastname, u.firstname, u.login";
	$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
	$sql.= " WHERE fk_facture = ".$object->id;
	$sql.= " AND pfd.traite = 1";
	$sql.= " ORDER BY pfd.date_demande DESC";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$numclosed = $num;
		$i = 0;

		while ($i < $num)
		{
			$obj = $db->fetch_object($result);

			print '<tr class="oddeven">';

			print '<td align="left">'.dol_print_date($db->jdate($obj->date_demande),'day')."</td>\n";

			print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

			print '<td align="center">'.price($obj->amount).'</td>';

			print '<td align="center">';
			if ($obj->fk_prelevement_bons > 0)
			{
				$withdrawreceipt=new BonPrelevement($db);
				$withdrawreceipt->id=$obj->fk_prelevement_bons;
				$withdrawreceipt->ref=$obj->ref;
				print $withdrawreceipt->getNomUrl(1);
			}
			print "</td>\n";

			print '<td>&nbsp;</td>';

			print '<td align="center">'.dol_print_date($db->jdate($obj->date_traite),'day')."</td>\n";

			print '<td>&nbsp;</td>';

			print "</tr>\n";
			$i++;
		}

		if (! $numopen && ! $numclosed)
			print '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.$langs->trans("None").'</td></tr>';

		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}

	print "</table>";
	print '</div>';
}

// End of page
llxFooter();
$db->close();
