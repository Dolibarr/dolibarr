<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2007 Yannick Warnier      <ywarnier@beeznest.org>
 * Copyright (C) 2014      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2016      Alexandre Spangaro   <aspangaro@zendsi.com>
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
 *	    \file       htdocs/compta/tva/quadri_detail.php
 *      \ingroup    tax
 *		\brief      Trimestrial page - detailed version
 *		TODO 		Deal with recurrent invoices as well
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");
$langs->load("trips");
$langs->load("other");

// Date range
$year=GETPOST("year","int");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,GETPOST("date_startmonth"),GETPOST("date_startday"),GETPOST("date_startyear"));
$date_end=dol_mktime(23,59,59,GETPOST("date_endmonth"),GETPOST("date_endday"),GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q");
	if (empty($q))
	{
		if (GETPOST("month")) { $date_start=dol_get_first_day($year_start,GETPOST("month"),false); $date_end=dol_get_last_day($year_start,GETPOST("month"),false); }
		else
		{
            $month_current = strftime("%m",dol_now());
            if ($month_current >= 10) $q=4;
            elseif ($month_current >= 7) $q=3;
            elseif ($month_current >= 4) $q=2;
            else $q=1;
		}
	}
	if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
	if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
	if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
	if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
}

$min = GETPOST("min");
if (empty($min)) $min = 0;

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit
$modetax = $conf->global->TAX_MODE;
if (isset($_REQUEST["modetax"])) $modetax=$_REQUEST["modetax"];
if (empty($modetax)) $modetax=0;

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

$morequerystring='';
$listofparams=array('date_startmonth','date_startyear','date_startday','date_endmonth','date_endyear','date_endday');
foreach($listofparams as $param)
{
	if (GETPOST($param)!='') $morequerystring.=($morequerystring?'&':'').$param.'='.GETPOST($param);
}

llxHeader('',$langs->trans("VATReport"),'','',0,0,'','',$morequerystring);

$form=new Form($db);

$company_static=new Societe($db);
$invoice_customer=new Facture($db);
$invoice_supplier=new FactureFournisseur($db);
$expensereport=new ExpenseReport($db);
$product_static=new Product($db);
$payment_static=new Paiement($db);
$paymentfourn_static=new PaiementFourn($db);
$paymentexpensereport_static=new PaymentExpenseReport($db);

//print load_fiche_titre($langs->trans("VAT"),"");

//$fsearch.='<br>';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  <input type="hidden" name="modetax" value="'.$modetax.'">';
//$fsearch.='  '.$langs->trans("SalesTurnoverMinimum").': ';
//$fsearch.='  <input type="text" name="min" value="'.$min.'">';


// Affiche en-tete du rapport
if ($modetax==1)	// Calculate on invoice for goods and services
{
    $name=$langs->trans("VATReportByQuartersInDueDebtMode");
    $calcmode=$langs->trans("CalcModeVATDebt");
    $calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    $prevyear=$year_start; $prevquarter=$q;
	if ($prevquarter > 1) $prevquarter--;
	else { $prevquarter=4; $prevyear--; }
	$nextyear=$year_start; $nextquarter=$q;
	if ($nextquarter < 4) $nextquarter++;
	else { $nextquarter=1; $nextyear++; }
	//$periodlink=($prevyear?"<a href='".$_SERVER["PHP_SELF"]."?year=".$prevyear."&q=".$prevquarter."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".$nextyear."&q=".$nextquarter."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATDueServices");
    $description.='<br>';
    $description.=$langs->trans("RulesVATDueProducts");
    //if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
    //if (! empty($conf->global->MAIN_MODULE_COMPTABILITE)) $description.='<br>'.$langs->trans("WarningDepositsNotIncluded");
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.='<br>'.$langs->trans("DepositsAreNotIncluded");
	else  $description.='<br>'.$langs->trans("DepositsAreIncluded");
    $description.=$fsearch;
    $builddate=dol_now();
    //$exportlink=$langs->trans("NotYetAvailable");

	// Customers invoices
	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';

	// Suppliers invoices
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPaid");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';

}
if ($modetax==0) 	// Invoice for goods, payment for services
{
    $name=$langs->trans("VATReportByQuartersInInputOutputMode");
    $calcmode=$langs->trans("CalcModeVATEngagement");
    $calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    $prevyear=$year_start; $prevquarter=$q;
	if ($prevquarter > 1) $prevquarter--;
	else { $prevquarter=4; $prevyear--; }
	$nextyear=$year_start; $nextquarter=$q;
	if ($nextquarter < 4) $nextquarter++;
	else { $nextquarter=1; $nextyear++; }
	//$periodlink=($prevyear?"<a href='".$_SERVER["PHP_SELF"]."?year=".$prevyear."&q=".$prevquarter."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".$nextyear."&q=".$nextquarter."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATInServices");
    $description.=' '.$langs->trans("DepositsAreIncluded");
    $description.='<br>';
    $description.=$langs->trans("RulesVATInProducts");
    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.=' '.$langs->trans("DepositsAreNotIncluded");
	else  $description.=' '.$langs->trans("DepositsAreIncluded");
    //if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
    //if (! empty($conf->global->MAIN_MODULE_COMPTABILITE)) $description.='<br>'.$langs->trans("WarningDepositsNotIncluded");
    $description.=$fsearch;
	$builddate=dol_now();
    //$exportlink=$langs->trans("NotYetAvailable");

	// Customers invoices
	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("ProductOrService");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';

	// Suppliers invoices
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("ProductOrService");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPaid");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';

}
report_header($name,'',$period,$periodlink,$description,$builddate,$exportlink,array(),$calcmode);

$vatcust=$langs->trans("VATReceived");
$vatsup=$langs->trans("VATPaid");
$vatexpensereport=$langs->trans("VATPaid");

// VAT Received and paid
print '<table class="noborder" width="100%">';

$y = $year_current;
$total = 0;
$i=0;

// Load arrays of datas
$x_coll = vat_by_date($db, 0, 0, $date_start, $date_end, $modetax, 'sell');
$x_paye = vat_by_date($db, 0, 0, $date_start, $date_end, $modetax, 'buy');

if (! is_array($x_coll) || ! is_array($x_paye))
{
	$langs->load("errors");
	if ($x_coll == -1)
		print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
	else if ($x_coll == -2)
		print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	else
		print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
}
else
{
	$x_both = array();
	//now, from these two arrays, get another array with one rate per line
	foreach(array_keys($x_coll) as $my_coll_rate)
	{
		$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
		$x_both[$my_coll_rate]['coll']['vat']     = $x_coll[$my_coll_rate]['vat'];
		$x_both[$my_coll_rate]['paye']['totalht'] = 0;
		$x_both[$my_coll_rate]['paye']['vat'] = 0;
		$x_both[$my_coll_rate]['coll']['links'] = '';
		$x_both[$my_coll_rate]['coll']['detail'] = array();
		foreach($x_coll[$my_coll_rate]['facid'] as $id=>$dummy)
		{
			$invoice_customer->id=$x_coll[$my_coll_rate]['facid'][$id];
			$invoice_customer->ref=$x_coll[$my_coll_rate]['facnum'][$id];
			$invoice_customer->type=$x_coll[$my_coll_rate]['type'][$id];
			$x_both[$my_coll_rate]['coll']['detail'][] = array(
				'id'				=>$x_coll[$my_coll_rate]['facid'][$id],
				'descr'				=>$x_coll[$my_coll_rate]['descr'][$id],
				'pid'				=>$x_coll[$my_coll_rate]['pid'][$id],
				'pref'				=>$x_coll[$my_coll_rate]['pref'][$id],
				'ptype'				=>$x_coll[$my_coll_rate]['ptype'][$id],
				'payment_id'		=>$x_coll[$my_coll_rate]['payment_id'][$id],
				'payment_amount'	=>$x_coll[$my_coll_rate]['payment_amount'][$id],
				'ftotal_ttc'		=>$x_coll[$my_coll_rate]['ftotal_ttc'][$id],
				'dtotal_ttc'		=>$x_coll[$my_coll_rate]['dtotal_ttc'][$id],
				'dtype'				=>$x_coll[$my_coll_rate]['dtype'][$id],
				'ddate_start'		=>$x_coll[$my_coll_rate]['ddate_start'][$id],
				'ddate_end'			=>$x_coll[$my_coll_rate]['ddate_end'][$id],
				'totalht'			=>$x_coll[$my_coll_rate]['totalht_list'][$id],
				'vat'				=>$x_coll[$my_coll_rate]['vat_list'][$id],
				'link'				=>$invoice_customer->getNomUrl(1,'',12));
		}
	}
	// tva paid
	foreach(array_keys($x_paye) as $my_paye_rate){
		$x_both[$my_paye_rate]['paye']['totalht'] = $x_paye[$my_paye_rate]['totalht'];
		$x_both[$my_paye_rate]['paye']['vat'] = $x_paye[$my_paye_rate]['vat'];
		if(!isset($x_both[$my_paye_rate]['coll']['totalht'])){
			$x_both[$my_paye_rate]['coll']['totalht'] = 0;
			$x_both[$my_paye_rate]['coll']['vat'] = 0;
		}
		$x_both[$my_paye_rate]['paye']['links'] = '';
		$x_both[$my_paye_rate]['paye']['detail'] = array();

		foreach($x_paye[$my_paye_rate]['facid'] as $id=>$dummy)
		{
			// ExpenseReport
			if ($x_paye[$my_paye_rate]['ptype'][$id] == 'ExpenseReportPayment')
			{
				$expensereport->id=$x_paye[$my_paye_rate]['facid'][$id];
				$expensereport->ref=$x_paye[$my_paye_rate]['facnum'][$id];
				$expensereport->type=$x_paye[$my_paye_rate]['type'][$id];

				$x_both[$my_paye_rate]['paye']['detail'][] = array(
					'id'				=>$x_paye[$my_paye_rate]['facid'][$id],
					'descr'				=>$x_paye[$my_paye_rate]['descr'][$id],
					'pid'				=>$x_paye[$my_paye_rate]['pid'][$id],
					'pref'				=>$x_paye[$my_paye_rate]['pref'][$id],
					'ptype'				=>$x_paye[$my_paye_rate]['ptype'][$id],
					'payment_id'		=>$x_paye[$my_paye_rate]['payment_id'][$id],
					'payment_amount'	=>$x_paye[$my_paye_rate]['payment_amount'][$id],
					'ftotal_ttc'		=>price2num($x_paye[$my_paye_rate]['ftotal_ttc'][$id]),
					'dtotal_ttc'		=>price2num($x_paye[$my_paye_rate]['dtotal_ttc'][$id]),
					'dtype'				=>$x_paye[$my_paye_rate]['dtype'][$id],
					'ddate_start'		=>$x_paye[$my_paye_rate]['ddate_start'][$id],
					'ddate_end'			=>$x_paye[$my_paye_rate]['ddate_end'][$id],
					'totalht'			=>price2num($x_paye[$my_paye_rate]['totalht_list'][$id]),
					'vat'				=>$x_paye[$my_paye_rate]['vat_list'][$id],
					'link'				=>$expensereport->getNomUrl(1)
				);
			}
			else
			{
				$invoice_supplier->id=$x_paye[$my_paye_rate]['facid'][$id];
				$invoice_supplier->ref=$x_paye[$my_paye_rate]['facnum'][$id];
				$invoice_supplier->type=$x_paye[$my_paye_rate]['type'][$id];
				$x_both[$my_paye_rate]['paye']['detail'][] = array(
					'id'				=>$x_paye[$my_paye_rate]['facid'][$id],
					'descr'				=>$x_paye[$my_paye_rate]['descr'][$id],
					'pid'				=>$x_paye[$my_paye_rate]['pid'][$id],
					'pref'				=>$x_paye[$my_paye_rate]['pref'][$id],
					'ptype'				=>$x_paye[$my_paye_rate]['ptype'][$id],
					'payment_id'		=>$x_paye[$my_paye_rate]['payment_id'][$id],
					'payment_amount'	=>$x_paye[$my_paye_rate]['payment_amount'][$id],
					'ftotal_ttc'		=>price2num($x_paye[$my_paye_rate]['ftotal_ttc'][$id]),
					'dtotal_ttc'		=>price2num($x_paye[$my_paye_rate]['dtotal_ttc'][$id]),
					'dtype'				=>$x_paye[$my_paye_rate]['dtype'][$id],
					'ddate_start'		=>$x_paye[$my_paye_rate]['ddate_start'][$id],
					'ddate_end'			=>$x_paye[$my_paye_rate]['ddate_end'][$id],
					'totalht'			=>price2num($x_paye[$my_paye_rate]['totalht_list'][$id]),
					'vat'				=>$x_paye[$my_paye_rate]['vat_list'][$id],
					'link'				=>$invoice_supplier->getNomUrl(1,'',12)
				);
			}
		}
	}
	//now we have an array (x_both) indexed by rates for coll and paye


	//print table headers for this quadri - incomes first

	$x_coll_sum = 0;
	$x_coll_ht = 0;
	$x_paye_sum = 0;
	$x_paye_ht = 0;

	$span=3;
	if ($modetax == 0) $span+=2;

	//print '<tr><td colspan="'.($span+1).'">'..')</td></tr>';

	// Customers invoices
	print '<tr class="liste_titre">';
	print '<td align="left">'.$elementcust.'</td>';
	print '<td align="left">'.$productcust.'</td>';
	if ($modetax == 0)
	{
		print '<td align="right">'.$amountcust.'</td>';
		print '<td align="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
	}
	print '<td align="right">'.$langs->trans("AmountHTVATRealReceived").'</td>';
	print '<td align="right">'.$vatcust.'</td>';
	print '</tr>';

	$action = "tvadetail";
	$parameters["mode"] = $modetax;
	$parameters["start"] = $date_start;
	$parameters["end"] = $date_end;
	$parameters["type"] = 'vat';

	$object = array(&$x_coll, &$x_paye, &$x_both);
	// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
	$hookmanager->initHooks(array('externalbalance'));
	$reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

	foreach(array_keys($x_coll) as $rate)
	{
		$subtot_coll_total_ht = 0;
		$subtot_coll_vat = 0;

		if (is_array($x_both[$rate]['coll']['detail']))
		{
			// VAT Rate
			$var=true;
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="'.$span.'"></td>';
			print '</tr>'."\n";

			foreach($x_both[$rate]['coll']['detail'] as $index => $fields)
			{
				// Define type
				$type=($fields['dtype']?$fields['dtype']:$fields['ptype']);
				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (! empty($fields['ddate_start'])) $type=1;
				if (! empty($fields['ddate_end'])) $type=1;


				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowrap" align="left">'.$fields['link'].'</td>';

				// Description
				print '<td align="left">';
				if ($fields['pid'])
				{
					$product_static->id=$fields['pid'];
					$product_static->ref=$fields['pref'];
					$product_static->type=$fields['ptype'];
					print $product_static->getNomUrl(1);
					if (dol_string_nohtmltag($fields['descr'])) print ' - '.dol_trunc(dol_string_nohtmltag($fields['descr']),16);
				}
				else
				{
					if ($type) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					if (preg_match('/^\((.*)\)$/',$fields['descr'],$reg))
					{
						if ($reg[1]=='DEPOSIT') $fields['descr']=$langs->transnoentitiesnoconv('Deposit');
						elseif ($reg[1]=='CREDIT_NOTE') $fields['descr']=$langs->transnoentitiesnoconv('CreditNote');
						else $fields['descr']=$langs->transnoentitiesnoconv($reg[1]);
					}
					print $text.' '.dol_trunc(dol_string_nohtmltag($fields['descr']),16);

					// Show range
					print_date_range($fields['ddate_start'],$fields['ddate_end']);
				}
				print '</td>';

				// Total HT
				if ($modetax == 0)
				{
					print '<td class="nowrap" align="right">';
					print price($fields['totalht']);
					if (price2num($fields['ftotal_ttc']))
					{
						//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
						$ratiolineinvoice=($fields['dtotal_ttc']/$fields['ftotal_ttc']);
						//print ' ('.round($ratiolineinvoice*100,2).'%)';
					}
					print '</td>';
				}

				// Payment
				$ratiopaymentinvoice=1;
				if ($modetax == 0)
				{
					if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) $ratiopaymentinvoice=($fields['payment_amount']/$fields['ftotal_ttc']);
					print '<td class="nowrap" align="right">';
					//print $fields['totalht']."-".$fields['payment_amount']."-".$fields['ftotal_ttc'];
					if ($fields['payment_amount'] && $fields['ftotal_ttc'])
					{
						$payment_static->id=$fields['payment_id'];
						print $payment_static->getNomUrl(2);
					}
					if ($type == 0)
					{
						print $langs->trans("NotUsedForGoods");
					}
					else {
						print price($fields['payment_amount']);
						if (isset($fields['payment_amount'])) print ' ('.round($ratiopaymentinvoice*100,2).'%)';
					}
					print '</td>';
				}

				// Total collected
				print '<td class="nowrap" align="right">';
				$temp_ht=$fields['totalht'];
				if ($type == 1) $temp_ht=$fields['totalht']*$ratiopaymentinvoice;
				print price(price2num($temp_ht,'MT'),1);
				print '</td>';

				// VAT
				print '<td class="nowrap" align="right">';
				$temp_vat=$fields['vat'];
				if ($type == 1) $temp_vat=$fields['vat']*$ratiopaymentinvoice;
				print price(price2num($temp_vat,'MT'),1);
				//print price($fields['vat']);
				print '</td>';
				print '</tr>';

				$subtot_coll_total_ht += $temp_ht;
				$subtot_coll_vat      += $temp_vat;
				$x_coll_sum           += $temp_vat;
			}
		}
        // Total customers for this vat rate
        print '<tr class="liste_total">';
        print '<td></td>';
        print '<td align="right">'.$langs->trans("Total").':</td>';
        if ($modetax == 0)
        {
            print '<td class="nowrap" align="right">&nbsp;</td>';
            print '<td align="right">&nbsp;</td>';
        }
        print '<td align="right">'.price(price2num($subtot_coll_total_ht,'MT')).'</td>';
        print '<td class="nowrap" align="right">'.price(price2num($subtot_coll_vat,'MT')).'</td>';
        print '</tr>';
	}

    if (count($x_coll) == 0)   // Show a total ine if nothing shown
    {
        print '<tr class="liste_total">';
        print '<td>&nbsp;</td>';
        print '<td align="right">'.$langs->trans("Total").':</td>';
        if ($modetax == 0)
        {
            print '<td class="nowrap" align="right">&nbsp;</td>';
            print '<td align="right">&nbsp;</td>';
        }
        print '<td align="right">'.price(price2num(0,'MT')).'</td>';
        print '<td class="nowrap" align="right">'.price(price2num(0,'MT')).'</td>';
        print '</tr>';
    }

    // Blank line
	print '<tr><td colspan="'.($span+1).'">&nbsp;</td></tr>';

	//print table headers for this quadri - expenses now
	//imprime les en-tete de tables pour ce quadri - maintenant les d�penses
	print '<tr class="liste_titre liste_titre_topborder">';
	print '<td align="left">'.$elementsup.'</td>';
	print '<td align="left">'.$productsup.'</td>';
	if ($modetax == 0)
	{
		print '<td align="right">'.$amountsup.'</td>';
		print '<td align="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
	}
	print '<td align="right">'.$langs->trans("AmountHTVATRealPaid").'</td>';
	print '<td align="right">'.$vatsup.'</td>';
	print '</tr>'."\n";

	foreach(array_keys($x_paye) as $rate)
	{
		$subtot_paye_total_ht = 0;
		$subtot_paye_vat = 0;

		if(is_array($x_both[$rate]['paye']['detail']))
		{
			$var=true;
			print "<tr>";
			print '<td class="tax_rate">'.$langs->trans("Rate").': '.vatrate($rate).'%</td><td colspan="'.$span.'"></td>';
			print '</tr>'."\n";
			foreach($x_both[$rate]['paye']['detail'] as $index=>$fields)
			{
				// Define type
				$type=($fields['dtype']?$fields['dtype']:$fields['ptype']);
				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (! empty($fields['ddate_start'])) $type=1;
				if (! empty($fields['ddate_end'])) $type=1;


				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowrap" align="left">'.$fields['link'].'</td>';

				// Description
				print '<td align="left">';
				if ($fields['pid'])
				{
					$product_static->id=$fields['pid'];
					$product_static->ref=$fields['pref'];
					$product_static->type=$fields['ptype'];
					print $product_static->getNomUrl(1);
					if (dol_string_nohtmltag($fields['descr'])) print ' - '.dol_trunc(dol_string_nohtmltag($fields['descr']),16);
				}
				else
				{
					if ($type) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.dol_trunc(dol_string_nohtmltag($fields['descr']),16);

					// Show range
					print_date_range($fields['ddate_start'],$fields['ddate_end']);
				}
				print '</td>';

				// Total HT
				if ($modetax == 0)
				{
					print '<td class="nowrap" align="right">';
					print price($fields['totalht']);
					if (price2num($fields['ftotal_ttc']))
					{
						//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
						$ratiolineinvoice=($fields['dtotal_ttc']/$fields['ftotal_ttc']);
						//print ' ('.round($ratiolineinvoice*100,2).'%)';
					}
					print '</td>';
				}

				// Payment
				$ratiopaymentinvoice=1;
				if ($modetax == 0)
				{
					if (isset($fields['payment_amount']) && $fields['ftotal_ttc']) $ratiopaymentinvoice=($fields['payment_amount']/$fields['ftotal_ttc']);
					print '<td class="nowrap" align="right">';
					if ($fields['payment_amount'] && $fields['ftotal_ttc'])
					{
						$paymentfourn_static->id=$fields['payment_id'];
						print $paymentfourn_static->getNomUrl(2);
					}
					if ($type == 0)
					{
						print $langs->trans("NotUsedForGoods");
					}
					else
					{
						print price($fields['payment_amount']);
						if (isset($fields['payment_amount'])) print ' ('.round($ratiopaymentinvoice*100,2).'%)';
					}
					print '</td>';
				}

				// VAT paid
				print '<td class="nowrap" align="right">';
				$temp_ht=$fields['totalht'];
				if ($type == 1) $temp_ht=$fields['totalht']*$ratiopaymentinvoice;
				print price(price2num($temp_ht,'MT'),1);
				print '</td>';

				// VAT
				print '<td class="nowrap" align="right">';
				$temp_vat=$fields['vat'];
				if ($type == 1) $temp_vat=$fields['vat']*$ratiopaymentinvoice;
				print price(price2num($temp_vat,'MT'),1);
				//print price($fields['vat']);
				print '</td>';
				print '</tr>';

				$subtot_paye_total_ht += $temp_ht;
				$subtot_paye_vat      += $temp_vat;
				$x_paye_sum           += $temp_vat;
			}
		}
        // Total suppliers for this vat rate
        print '<tr class="liste_total">';
        print '<td>&nbsp;</td>';
        print '<td align="right">'.$langs->trans("Total").':</td>';
        if ($modetax == 0)
        {
            print '<td class="nowrap" align="right">&nbsp;</td>';
            print '<td align="right">&nbsp;</td>';
        }
        print '<td align="right">'.price(price2num($subtot_paye_total_ht,'MT')).'</td>';
        print '<td class="nowrap" align="right">'.price(price2num($subtot_paye_vat,'MT')).'</td>';
        print '</tr>';
	}

	if (count($x_paye) == 0)   // Show a total line if nothing shown
	{
        print '<tr class="liste_total">';
        print '<td>&nbsp;</td>';
        print '<td align="right">'.$langs->trans("Total").':</td>';
        if ($modetax == 0)
        {
            print '<td class="nowrap" align="right">&nbsp;</td>';
            print '<td align="right">&nbsp;</td>';
        }
        print '<td align="right">'.price(price2num(0,'MT')).'</td>';
        print '<td class="nowrap" align="right">'.price(price2num(0,'MT')).'</td>';
        print '</tr>';
	}

    print '</table>';

	// Total to pay
    print '<br><br>';
    print '<table class="noborder" width="100%">';
    $diff = $x_coll_sum - $x_paye_sum;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="'.$span.'">'.$langs->trans("TotalToPay").($q?', '.$langs->trans("Quadri").' '.$q:'').'</td>';
	print '<td class="liste_total nowrap" align="right"><b>'.price(price2num($diff,'MT'))."</b></td>\n";
	print "</tr>\n";

	$i++;
}
echo '</table>';

llxFooter();
$db->close();
