<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
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

if (!$user->rights->facture->lire) accessforbidden();

$langs->load("bills");
$langs->load("banks");
$langs->load("withdrawals");

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


/*
 * Actions
 */

if ($action == "new")
{
    if ($object->id > 0)
    {
        $result = $object->demande_prelevement($user);
        if ($result > 0)
        {
            setEventMessage($langs->trans("RecordSaved"));
        }
        else
        {
        	setEventMessage($object->error, 'errors');
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


/*
 * View
 */

$now=dol_now();

llxHeader('', $langs->trans("Bill"));

$form = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($object->id > 0)
{
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

	$absolute_discount=$object->thirdparty->getAvailableDiscounts('','fk_facture_source IS NULL');
	$absolute_creditnote=$object->thirdparty->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
	$absolute_discount=price2num($absolute_discount,'MT');
	$absolute_creditnote=price2num($absolute_creditnote,'MT');

	$author = new User($db);
	if ($object->user_author)
	{
		$author->fetch($object->user_author);
	}

	$head = facture_prepare_head($object);

	dol_fiche_head($head, 'standingorders', $langs->trans('InvoiceCustomer'),0,'bill');

	/*
	 *   Facture
	 */
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="5">';
	$morehtmlref='';
	$discount=new DiscountAbsolute($db);
	$result=$discount->fetch(0,$object->id);
	if ($result > 0)
	{
		$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
	}
	if ($result < 0)
	{
		dol_print_error('',$discount->error);
	}
	print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref);
	print "</td></tr>";

	// Ref customer
	print '<tr><td width="20%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RefCustomer');
	print '</td>';
	print '</tr></table>';
	print '</td>';
	print '<td colspan="5">';
	print $object->ref_client;
	print '</td></tr>';

	// Third party
	print '<tr><td>'.$langs->trans('Company').'</td>';
	print '<td colspan="5">'.$object->thirdparty->getNomUrl(1,'compta');
	print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->socid.'">'.$langs->trans('OtherBills').'</a>)</td>';
	print '</tr>';

	// Type
	print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
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
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($object->thirdparty->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$object->thirdparty->remise_percent);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	print '. ';
	if ($absolute_discount > 0)
	{
		if ($object->statut > Facture::STATUS_DRAFT || $object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT)
		{
			if ($object->statut == Facture::STATUS_DRAFT)
			{
				print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency)).'. ';
			}
			else
			{
				if ($object->statut < Facture::STATUS_VALIDATED || $object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT)
				{
					$text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
					print '<br>'.$text.'.<br>';
				}
				else
				{
					$text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
					$text2=$langs->trans("AbsoluteDiscountUse");
					print $form->textwithpicto($text,$text2);
				}
			}
		}
		else
		{
			// Remise dispo de type non avoir
			$filter='fk_facture_source IS NULL';
			print '<br>';
			$form->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$object->id,0,'remise_id',$object->thirdparty->id,$absolute_discount,$filter,$resteapayer);
		}
	}
	if ($absolute_creditnote > 0)
	{
		// If validated, we show link "add credit note to payment"
		if ($object->statut != Facture::STATUS_VALIDATED || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_CREDIT_NOTE)
		{
			if ($object->statut == Facture::STATUS_DRAFT && $object->type != Facture::TYPE_DEPOSIT)
			{
				$text=$langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency));
				print $form->textwithpicto($text,$langs->trans("CreditNoteDepositUse"));
			}
			else print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency)).'.';
		}
		else
		{
			// Remise dispo de type avoir
			$filter='fk_facture_source IS NOT NULL';
			if (! $absolute_discount) print '<br>';
			$form->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$object->id,0,'remise_id_for_payment',$object->thirdparty->id,$absolute_creditnote,$filter,$resteapayer);
		}
	}
	if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
	print '</td></tr>';

	// Date invoice
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
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

	// Conditions de reglement
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
			if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $object->paye && $object->statut == Facture::STATUS_VALIDATED && ! isset($object->am)) print img_warning($langs->trans('Late'));
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
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'none');
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
	
	// Montants
	print '<tr><td>'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($object->total_ht).'</td>';
	print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';
	print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
	print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj=="1") //Localtax1
	{
		print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
		print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax1).'</td>';
		print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
	}
	if ($mysoc->localtax2_assuj=="1") //Localtax2
	{
		print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
		print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax2).'</td>';
		print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
	}


	print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
	print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

	// We can also use bcadd to avoid pb with floating points
    // For example print 239.2 - 229.3 - 9.9; does not return 0.
    //$resteapayer=bcadd($object->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
    //$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
    $resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

    print '<tr><td>'.$langs->trans('RemainderToPay').'</td><td align="right" colspan="2" nowrap>'.price($resteapayer).'</td>';
    print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans('Status').'</td>';
	print '<td align="left" colspan="3">'.($object->getLibStatut(4,$totalpaye)).'</td></tr>';

	print '<tr><td>'.$langs->trans("RIB").'</td><td colspan="5">';
	print $object->thirdparty->display_rib();
	print '</td></tr>';

	print '</table>';

	dol_fiche_end();



	/*
	 * Withdrawal request
	 */

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
	$sql .= " , pfd.date_traite as date_traite";
	$sql .= " , pfd.amount";
	$sql .= " , u.rowid as user_id, u.lastname, u.firstname, u.login";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
	$sql .= " , ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE fk_facture = ".$object->id;
	$sql .= " AND pfd.fk_user_demande = u.rowid";
	$sql .= " AND pfd.traite = 0";
	$sql .= " ORDER BY pfd.date_demande DESC";

	$result_sql = $db->query($sql);
	if ($result_sql)
	{
		$num = $db->num_rows($result_sql);
	}


	/*
	 * Buttons
	 */
	print "\n<div class=\"tabsAction\">\n";

	// Add a withdraw request
	if ($object->statut > Facture::STATUS_DRAFT && $object->paye == 0 && $num == 0)
	{
		if ($user->rights->prelevement->bons->creer)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=new">'.$langs->trans("MakeWithdrawRequest").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
		}
	}
	else
	{
		if ($num == 0)
		{
			if ($object->statut > Facture::STATUS_DRAFT) print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("AlreadyPayed")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
			else print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("Draft")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
		}
		else print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("RequestAlreadyDone")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
	}

	print "</div><br>\n";


	print $langs->trans("DoStandingOrdersBeforePayments").'<br><br>';


	/*
	 * Withdrawals
	*/
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td align="left">'.$langs->trans("DateRequest").'</td>';
	print '<td align="center">'.$langs->trans("DateProcess").'</td>';
	print '<td align="center">'.$langs->trans("Amount").'</td>';
	print '<td align="center">'.$langs->trans("WithdrawalReceipt").'</td>';
	print '<td align="center">'.$langs->trans("User").'</td><td>&nbsp;</td><td>&nbsp;</td>';
	print '</tr>';
	$var=true;

	if ($result_sql)
	{
		$i = 0;

		while ($i < $num)
		{
			$obj = $db->fetch_object($result_sql);
			$var=!$var;

			print "<tr ".$bc[$var].">";
			print '<td align="left">'.dol_print_date($db->jdate($obj->date_demande),'day')."</td>\n";
			print '<td align="center">'.$langs->trans("OrderWaiting").'</td>';
			print '<td align="center">'.price($obj->amount).'</td>';
			print '<td align="center">-</td>';
			print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
			print '<td>&nbsp;</td>';
			print '<td>';
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

	$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande,";
	$sql.= " pfd.date_traite, pfd.fk_prelevement_bons, pfd.amount,";
	$sql.= " pb.ref,";
	$sql.= " u.rowid as user_id, u.lastname, u.firstname, u.login";
	$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd,";
	$sql.= " ".MAIN_DB_PREFIX."prelevement_bons as pb,";
	$sql.= " ".MAIN_DB_PREFIX."user as u";
	$sql.= " WHERE fk_facture = ".$object->id;
	$sql.= " AND pfd.fk_user_demande = u.rowid";
	$sql.= " AND pb.rowid = pfd.fk_prelevement_bons";
	$sql.= " AND pfd.traite = 1";
	$sql.= " ORDER BY pfd.date_demande DESC";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;

		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$var=!$var;

			print "<tr ".$bc[$var].">";

			print '<td align="left">'.dol_print_date($db->jdate($obj->date_demande),'day')."</td>\n";

			print '<td align="center">'.dol_print_date($db->jdate($obj->date_traite),'day')."</td>\n";

			print '<td align="center">'.price($obj->amount).'</td>';

			print '<td align="center">';
			$withdrawreceipt=new BonPrelevement($db);
			$withdrawreceipt->id=$obj->fk_prelevement_bons;
			$withdrawreceipt->ref=$obj->ref;
			print $withdrawreceipt->getNomUrl(1);
			print "</td>\n";

			print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';

			print "</tr>\n";
			$i++;
		}

		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}

	print "</table>";
}


llxFooter();
$db->close();
