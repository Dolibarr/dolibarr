<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Yannick Warnier      <ywarnier@beeznest.org>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
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
 *		\file       htdocs/compta/tva/clients.php
 *		\ingroup    tax
 *		\brief      Page of sales taxes
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';

// Load translation files required by the page
$langs->loadLangs(array("other", "compta", "banks", "bills", "companies", "product", "trips", "admin"));


$now = dol_now();
$current_date = dol_getdate($now);
if (empty($conf->global->SOCIETE_FISCAL_MONTH_START)) $conf->global->SOCIETE_FISCAL_MONTH_START = 1;

// Date range
$year = GETPOST("year", "int");
if (empty($year))
{
	$year_current = $current_date['year'];
    $year_start = $year_current;
} else {
    $year_current = $year;
    $year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"));
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"));
// Set default period if not defined
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q = GETPOST("q", "int");
    if (empty($q))
    {
        if (GETPOST("month", 'int')) { $date_start = dol_get_first_day($year_start, GETPOST("month", 'int'), false); $date_end = dol_get_last_day($year_start, GETPOST("month", 'int'), false); }
        else
        {
            if (empty($conf->global->MAIN_INFO_VAT_RETURN) || $conf->global->MAIN_INFO_VAT_RETURN == 2)	{ // quaterly vat, we take last past complete quarter
            	$date_start = dol_time_plus_duree(dol_get_first_day($year_start, $current_date['mon'], false), -3 - (($current_date['mon'] - $conf->global->SOCIETE_FISCAL_MONTH_START) % 3), 'm');
            	$date_end = dol_time_plus_duree($date_start, 3, 'm') - 1;
            }
            elseif ($conf->global->MAIN_INFO_VAT_RETURN == 3) { // yearly vat
            	if ($current_date['mon'] < $conf->global->SOCIETE_FISCAL_MONTH_START) {
            		if (($conf->global->SOCIETE_FISCAL_MONTH_START - $current_date['mon']) > 6) {	// If period started from less than 6 years, we show past year
            			$year_start--;
            		}
            	} else {
            		if (($current_date['mon'] - $conf->global->SOCIETE_FISCAL_MONTH_START) < 6) {	// If perdio started from less than 6 years, we show past year
            			$year_start--;
            		}
            	}
            	$date_start = dol_get_first_day($year_start, $conf->global->SOCIETE_FISCAL_MONTH_START, false);
            	$date_end = dol_time_plus_duree($date_start, 1, 'y') - 1;
            }
            elseif ($conf->global->MAIN_INFO_VAT_RETURN == 1) {	// monthly vat, we take last past complete month
            	$date_start = dol_time_plus_duree(dol_get_first_day($year_start, $current_date['mon'], false), -1, 'm');
            	$date_end = dol_time_plus_duree($date_start, 1, 'm') - 1;
            }
        }
    }
    else
    {
        if ($q == 1) { $date_start = dol_get_first_day($year_start, 1, false); $date_end = dol_get_last_day($year_start, 3, false); }
        if ($q == 2) { $date_start = dol_get_first_day($year_start, 4, false); $date_end = dol_get_last_day($year_start, 6, false); }
        if ($q == 3) { $date_start = dol_get_first_day($year_start, 7, false); $date_end = dol_get_last_day($year_start, 9, false); }
        if ($q == 4) { $date_start = dol_get_first_day($year_start, 10, false); $date_end = dol_get_last_day($year_start, 12, false); }
    }
}

$min = price2num(GETPOST("min", "alpha"));
if (empty($min)) $min = 0;

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = $conf->global->TAX_MODE;
if (GETPOSTISSET("modetax")) $modetax = GETPOST("modetax", 'int');
if (empty($modetax)) $modetax = 0;

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

$form = new Form($db);
$company_static = new Societe($db);
$invoice_customer = new Facture($db);
$invoice_supplier = new FactureFournisseur($db);
$expensereport = new ExpenseReport($db);
$product_static = new Product($db);
$payment_static = new Paiement($db);
$paymentfourn_static = new PaiementFourn($db);
$paymentexpensereport_static = new PaymentExpenseReport($db);

$morequerystring = '';
$listofparams = array('date_startmonth', 'date_startyear', 'date_startday', 'date_endmonth', 'date_endyear', 'date_endday');
foreach ($listofparams as $param)
{
    if (GETPOST($param) != '') $morequerystring .= ($morequerystring ? '&' : '').$param.'='.GETPOST($param);
}

$special_report = false;
if (isset($_REQUEST['extra_report']) && $_REQUEST['extra_report'] == 1) {
    $special_report = true;
}

llxHeader('', $langs->trans("VATReport"), '', '', 0, 0, '', '', $morequerystring);

$fsearch = '<!-- hidden fields for form -->';
$fsearch .= '<input type="hidden" name="token" value="'.newToken().'">';
$fsearch .= '<input type="hidden" name="modetax" value="'.$modetax.'">';
$fsearch .= $langs->trans("SalesTurnoverMinimum").': ';
$fsearch .= '<input type="text" name="min" id="min" value="'.$min.'" size="6">';

// Show report header
$name = $langs->trans("VATReportByThirdParties");
$calcmode = '';
if ($modetax == 0) $calcmode = $langs->trans('OptionVATDefault');
if ($modetax == 1) $calcmode = $langs->trans('OptionVATDebitOption');
if ($modetax == 2) $calcmode = $langs->trans('OptionPaymentForProductAndServices');
$calcmode .= '<br>('.$langs->trans("TaxModuleSetupToModifyRules", DOL_URL_ROOT.'/admin/taxes.php').')';
// Set period
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
$prevyear = $year_start;
$prevquarter = $q;
if ($prevquarter > 1) {
    $prevquarter--;
} else {
    $prevquarter = 4;
    $prevyear--;
}
$nextyear = $year_start;
$nextquarter = $q;
if ($nextquarter < 4) {
    $nextquarter++;
} else {
    $nextquarter = 1;
    $nextyear++;
}
$builddate = dol_now();

if ($conf->global->TAX_MODE_SELL_PRODUCT == 'invoice') $description .= $langs->trans("RulesVATDueProducts");
if ($conf->global->TAX_MODE_SELL_PRODUCT == 'payment') $description .= $langs->trans("RulesVATInProducts");
if ($conf->global->TAX_MODE_SELL_SERVICE == 'invoice') $description .= '<br>'.$langs->trans("RulesVATDueServices");
if ($conf->global->TAX_MODE_SELL_SERVICE == 'payment') $description .= '<br>'.$langs->trans("RulesVATInServices");
if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
    $description .= '<br>'.$langs->trans("DepositsAreNotIncluded");
}
if (!empty($conf->global->MAIN_MODULE_ACCOUNTING)) $description .= '<br>'.$langs->trans("ThisIsAnEstimatedValue");

//$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
$description .= ($description ? '<br>' : '').$fsearch;
if (!empty($conf->global->TAX_REPORT_EXTRA_REPORT))
{
    $description .= '<br>'
        . '<input type="radio" name="extra_report" value="0" '.($special_report ? '' : 'checked="checked"').'> '
            . $langs->trans('SimpleReport')
            . '</input>'
                . '<br>'
                    . '<input type="radio" name="extra_report" value="1" '.($special_report ? 'checked="checked"' : '').'> '
                        . $langs->trans('AddExtraReport')
                        . '</input>'
                            . '<br>';
}

$elementcust = $langs->trans("CustomersInvoices");
$productcust = $langs->trans("Description");
$namerate = $langs->trans("VATRate");
$amountcust = $langs->trans("AmountHT");
if ($mysoc->tva_assuj) {
    $vatcust .= ' ('.$langs->trans("StatusToPay").')';
}
$elementsup = $langs->trans("SuppliersInvoices");
$productsup = $langs->trans("Description");
$amountsup = $langs->trans("AmountHT");
if ($mysoc->tva_assuj) {
    $vatsup .= ' ('.$langs->trans("ToGetBack").')';
}
report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

$vatcust = $langs->trans("VATReceived");
$vatsup = $langs->trans("VATPaid");


// VAT Received

print "<table class=\"noborder\" width=\"100%\">";

$y = $year_current;
$total = 0;
$i = 0;
$columns = 5;

// Load arrays of datas
$x_coll = tax_by_thirdparty('vat', $db, 0, $date_start, $date_end, $modetax, 'sell');
$x_paye = tax_by_thirdparty('vat', $db, 0, $date_start, $date_end, $modetax, 'buy');

if (!is_array($x_coll) || !is_array($x_paye))
{
    $langs->load("errors");
    if ($x_coll == -1) {
        print '<tr><td colspan="'.$columns.'">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
    } elseif ($x_coll == -2) {
        print '<tr><td colspan="'.$columns.'">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
    } else {
        print '<tr><td colspan="'.$columns.'">'.$langs->trans("Error").'</td></tr>';
    }
} else {
    $x_both = array();
    //now, from these two arrays, get another array with one rate per line
    foreach (array_keys($x_coll) as $my_coll_thirdpartyid)
    {
        $x_both[$my_coll_thirdpartyid]['coll']['totalht'] = $x_coll[$my_coll_thirdpartyid]['totalht'];
        $x_both[$my_coll_thirdpartyid]['coll']['vat'] = $x_coll[$my_coll_thirdpartyid]['vat'];
        $x_both[$my_coll_thirdpartyid]['paye']['totalht'] = 0;
        $x_both[$my_coll_thirdpartyid]['paye']['vat'] = 0;
        $x_both[$my_coll_thirdpartyid]['coll']['links'] = '';
        $x_both[$my_coll_thirdpartyid]['coll']['detail'] = array();
        foreach ($x_coll[$my_coll_thirdpartyid]['facid'] as $id=>$dummy) {
            $invoice_customer->id = $x_coll[$my_coll_thirdpartyid]['facid'][$id];
            $invoice_customer->ref = $x_coll[$my_coll_thirdpartyid]['facnum'][$id];
            $invoice_customer->type = $x_coll[$my_coll_thirdpartyid]['type'][$id];
            $company_static->fetch($x_coll[$my_coll_thirdpartyid]['company_id'][$id]);
            $x_both[$my_coll_thirdpartyid]['coll']['detail'][] = array(
                'id'        =>$x_coll[$my_coll_thirdpartyid]['facid'][$id],
                'descr'     =>$x_coll[$my_coll_thirdpartyid]['descr'][$id],
                'pid'       =>$x_coll[$my_coll_thirdpartyid]['pid'][$id],
                'pref'      =>$x_coll[$my_coll_thirdpartyid]['pref'][$id],
                'ptype'     =>$x_coll[$my_coll_thirdpartyid]['ptype'][$id],
                'payment_id'=>$x_coll[$my_coll_thirdpartyid]['payment_id'][$id],
                'payment_amount'=>$x_coll[$my_coll_thirdpartyid]['payment_amount'][$id],
                'ftotal_ttc'=>$x_coll[$my_coll_thirdpartyid]['ftotal_ttc'][$id],
                'dtotal_ttc'=>$x_coll[$my_coll_thirdpartyid]['dtotal_ttc'][$id],
                'dtype'     =>$x_coll[$my_coll_thirdpartyid]['dtype'][$id],
                'drate'     =>$x_coll[$my_coll_thirdpartyid]['drate'][$id],
                'datef'     =>$x_coll[$my_coll_thirdpartyid]['datef'][$id],
                'datep'     =>$x_coll[$my_coll_thirdpartyid]['datep'][$id],
                'company_link'=>$company_static->getNomUrl(1, '', 20),
                'ddate_start'=>$x_coll[$my_coll_thirdpartyid]['ddate_start'][$id],
                'ddate_end'  =>$x_coll[$my_coll_thirdpartyid]['ddate_end'][$id],
                'totalht'   =>$x_coll[$my_coll_thirdpartyid]['totalht_list'][$id],
                'vat'       =>$x_coll[$my_coll_thirdpartyid]['vat_list'][$id],
                'link'      =>$invoice_customer->getNomUrl(1, '', 12)
            );
        }
    }
    // tva paid
    foreach (array_keys($x_paye) as $my_paye_thirdpartyid) {
        $x_both[$my_paye_thirdpartyid]['paye']['totalht'] = $x_paye[$my_paye_thirdpartyid]['totalht'];
        $x_both[$my_paye_thirdpartyid]['paye']['vat'] = $x_paye[$my_paye_thirdpartyid]['vat'];
        if (!isset($x_both[$my_paye_thirdpartyid]['coll']['totalht'])) {
            $x_both[$my_paye_thirdpartyid]['coll']['totalht'] = 0;
            $x_both[$my_paye_thirdpartyid]['coll']['vat'] = 0;
        }
        $x_both[$my_paye_thirdpartyid]['paye']['links'] = '';
        $x_both[$my_paye_thirdpartyid]['paye']['detail'] = array();

        foreach ($x_paye[$my_paye_thirdpartyid]['facid'] as $id=>$dummy)
        {
            // ExpenseReport
            if ($x_paye[$my_paye_thirdpartyid]['ptype'][$id] == 'ExpenseReportPayment')
            {
                $expensereport->id = $x_paye[$my_paye_thirdpartyid]['facid'][$id];
                $expensereport->ref = $x_paye[$my_paye_thirdpartyid]['facnum'][$id];
                $expensereport->type = $x_paye[$my_paye_thirdpartyid]['type'][$id];

                $x_both[$my_paye_thirdpartyid]['paye']['detail'][] = array(
                    'id'				=>$x_paye[$my_paye_thirdpartyid]['facid'][$id],
                    'descr'				=>$x_paye[$my_paye_thirdpartyid]['descr'][$id],
                    'pid'				=>$x_paye[$my_paye_thirdpartyid]['pid'][$id],
                    'pref'				=>$x_paye[$my_paye_thirdpartyid]['pref'][$id],
                    'ptype'				=>$x_paye[$my_paye_thirdpartyid]['ptype'][$id],
                    'payment_id'		=>$x_paye[$my_paye_thirdpartyid]['payment_id'][$id],
                    'payment_amount'	=>$x_paye[$my_paye_thirdpartyid]['payment_amount'][$id],
                    'ftotal_ttc'		=>price2num($x_paye[$my_paye_thirdpartyid]['ftotal_ttc'][$id]),
                    'dtotal_ttc'		=>price2num($x_paye[$my_paye_thirdpartyid]['dtotal_ttc'][$id]),
                    'dtype'				=>$x_paye[$my_paye_thirdpartyid]['dtype'][$id],
                    'drate'             =>$x_paye[$my_coll_thirdpartyid]['drate'][$id],
                    'ddate_start'		=>$x_paye[$my_paye_thirdpartyid]['ddate_start'][$id],
                    'ddate_end'			=>$x_paye[$my_paye_thirdpartyid]['ddate_end'][$id],
                    'totalht'			=>price2num($x_paye[$my_paye_thirdpartyid]['totalht_list'][$id]),
                    'vat'				=>$x_paye[$my_paye_thirdpartyid]['vat_list'][$id],
                    'link'				=>$expensereport->getNomUrl(1)
                );
            }
            else
            {
                $invoice_supplier->id = $x_paye[$my_paye_thirdpartyid]['facid'][$id];
                $invoice_supplier->ref = $x_paye[$my_paye_thirdpartyid]['facnum'][$id];
                $invoice_supplier->type = $x_paye[$my_paye_thirdpartyid]['type'][$id];
                $company_static->fetch($x_paye[$my_paye_thirdpartyid]['company_id'][$id]);
                $x_both[$my_paye_thirdpartyid]['paye']['detail'][] = array(
                    'id'        =>$x_paye[$my_paye_thirdpartyid]['facid'][$id],
                    'descr'     =>$x_paye[$my_paye_thirdpartyid]['descr'][$id],
                    'pid'       =>$x_paye[$my_paye_thirdpartyid]['pid'][$id],
                    'pref'      =>$x_paye[$my_paye_thirdpartyid]['pref'][$id],
                    'ptype'     =>$x_paye[$my_paye_thirdpartyid]['ptype'][$id],
                    'payment_id'=>$x_paye[$my_paye_thirdpartyid]['payment_id'][$id],
                    'payment_amount'=>$x_paye[$my_paye_thirdpartyid]['payment_amount'][$id],
                    'ftotal_ttc'=>price2num($x_paye[$my_paye_thirdpartyid]['ftotal_ttc'][$id]),
                    'dtotal_ttc'=>price2num($x_paye[$my_paye_thirdpartyid]['dtotal_ttc'][$id]),
                    'dtype'     =>$x_paye[$my_paye_thirdpartyid]['dtype'][$id],
                    'drate'     =>$x_paye[$my_coll_thirdpartyid]['drate'][$id],
                    'datef'     =>$x_paye[$my_paye_thirdpartyid]['datef'][$id],
                    'datep'     =>$x_paye[$my_paye_thirdpartyid]['datep'][$id],
                    'company_link'=>$company_static->getNomUrl(1, '', 20),
                    'ddate_start'=>$x_paye[$my_paye_thirdpartyid]['ddate_start'][$id],
                    'ddate_end'  =>$x_paye[$my_paye_thirdpartyid]['ddate_end'][$id],
                    'totalht'   =>price2num($x_paye[$my_paye_thirdpartyid]['totalht_list'][$id]),
                    'vat'       =>$x_paye[$my_paye_thirdpartyid]['vat_list'][$id],
                    'link'      =>$invoice_supplier->getNomUrl(1, '', 12)
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

    $span = $columns;
    if ($modetax != 1) $span += 2;

    //print '<tr><td colspan="'.($span+1).'">'..')</td></tr>';

    // Customers invoices
    print '<tr class="liste_titre">';
    print '<td class="left">'.$elementcust.'</td>';
    print '<td class="left">'.$langs->trans("DateInvoice").'</td>';
    if ($conf->global->TAX_MODE_SELL_PRODUCT == 'payment' || $conf->global->TAX_MODE_SELL_SERVICE == 'payment') print '<td class="left">'.$langs->trans("DatePayment").'</td>';
    else print '<td></td>';
    print '<td class="right">'.$namerate.'</td>';
    print '<td class="left">'.$productcust.'</td>';
    if ($modetax != 1)
    {
        print '<td class="right">'.$amountcust.'</td>';
        print '<td class="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
    }
    print '<td class="right">'.$langs->trans("AmountHTVATRealReceived").'</td>';
    print '<td class="right">'.$vatcust.'</td>';
    print '</tr>';

    $action = "tvadetail";
    $parameters["mode"] = $modetax;
    $parameters["start"] = $date_start;
    $parameters["end"] = $date_end;
    $parameters["type"] = 'vat';

    $object = array(&$x_coll, &$x_paye, &$x_both);
    // Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
    $hookmanager->initHooks(array('externalbalance'));
    $reshook = $hookmanager->executeHooks('addVatLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

    foreach (array_keys($x_coll) as $thirdparty_id) {
        $subtot_coll_total_ht = 0;
        $subtot_coll_vat = 0;

        if ($min == 0 || ($min > 0 && $x_both[$thirdparty_id]['coll']['totalht'] > $min))
        {
            if (is_array($x_both[$thirdparty_id]['coll']['detail']))
            {
                // VAT Rate
                print "<tr>";
                print '<td class="tax_rate">';
                if (is_numeric($thirdparty_id)) {
                    $company_static->fetch($thirdparty_id);
                    print $langs->trans("ThirdParty").': '.$company_static->getNomUrl(1);
                } else {
                    $tmpid = preg_replace('/userid_/', '', $thirdparty_id);
                    $user_static->fetch($tmpid);
                    print $langs->trans("User").': '.$user_static->getNomUrl(1);
                }
                print '</td><td colspan="'.($span + 1).'"></td>';
                print '</tr>'."\n";

                foreach ($x_both[$thirdparty_id]['coll']['detail'] as $index => $fields) {
                    // Define type
                    // We MUST use dtype (type in line). We can use something else, only if dtype is really unknown.
                    $type = (isset($fields['dtype']) ? $fields['dtype'] : $fields['ptype']);
                    // Try to enhance type detection using date_start and date_end for free lines where type
                    // was not saved.
                    if (!empty($fields['ddate_start'])) {
                        $type = 1;
                    }
                    if (!empty($fields['ddate_end'])) {
                        $type = 1;
                    }

                    print '<tr class="oddeven">';

                    // Ref
                    print '<td class="nowrap left">'.$fields['link'].'</td>';

                    // Invoice date
                    print '<td class="left">'.dol_print_date($fields['datef'], 'day').'</td>';

                    // Payment date
                    if ($conf->global->TAX_MODE_SELL_PRODUCT == 'payment' || $conf->global->TAX_MODE_SELL_SERVICE == 'payment') {
                        print '<td class="left">'.dol_print_date($fields['datep'], 'day').'</td>';
                    } else {
                        print '<td></td>';
                    }

                    // Rate
                    print '<td class="right">'.$fields['drate'].'</td>';

                    // Description
                    print '<td class="left">';
                    if ($fields['pid']) {
                        $product_static->id = $fields['pid'];
                        $product_static->ref = $fields['pref'];
                        $product_static->type = $fields['dtype']; // We force with the type of line to have type how line is registered
                        print $product_static->getNomUrl(1);
                        if (dol_string_nohtmltag($fields['descr'])) {
                            print ' - '.dol_trunc(dol_string_nohtmltag($fields['descr']), 24);
                        }
                    } else {
                        if ($type) {
                            $text = img_object($langs->trans('Service'), 'service');
                        } else {
                            $text = img_object($langs->trans('Product'), 'product');
                        }
                        if (preg_match('/^\((.*)\)$/', $fields['descr'], $reg)) {
                            if ($reg[1] == 'DEPOSIT') {
                                $fields['descr'] = $langs->transnoentitiesnoconv('Deposit');
                            } elseif ($reg[1] == 'CREDIT_NOTE') {
                                $fields['descr'] = $langs->transnoentitiesnoconv('CreditNote');
                            } else {
                                $fields['descr'] = $langs->transnoentitiesnoconv($reg[1]);
                            }
                        }
                        print $text.' '.dol_trunc(dol_string_nohtmltag($fields['descr']), 24);

                        // Show range
                        print_date_range($fields['ddate_start'], $fields['ddate_end']);
                    }
                    print '</td>';

                    // Total HT
                    if ($modetax != 1) {
                        print '<td class="nowrap right">';
                        print price($fields['totalht']);
                        if (price2num($fields['ftotal_ttc'])) {
                            //print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
                            $ratiolineinvoice = ($fields['dtotal_ttc'] / $fields['ftotal_ttc']);
                            //print ' ('.round($ratiolineinvoice*100,2).'%)';
                        }
                        print '</td>';
                    }

                    // Payment
                    $ratiopaymentinvoice = 1;
                    if ($modetax != 1) {
                        print '<td class="nowrap right">';
                        //print $fields['totalht']."-".$fields['payment_amount']."-".$fields['ftotal_ttc'];
                        if ($fields['payment_amount'] && $fields['ftotal_ttc']) {
                            $payment_static->id = $fields['payment_id'];
                            print $payment_static->getNomUrl(2);
                        }
                        if (($type == 0 && $conf->global->TAX_MODE_SELL_PRODUCT == 'invoice')
                            || ($type == 1 && $conf->global->TAX_MODE_SELL_SERVICE == 'invoice')) {
                                print $langs->trans("NA");
                        } else {
                            if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) {
                                $ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
                            }
                            print price(price2num($fields['payment_amount'], 'MT'));
                            if (isset($fields['payment_amount'])) {
                                print ' ('.round($ratiopaymentinvoice * 100, 2).'%)';
                            }
                        }
                        print '</td>';
                    }

                    // Total collected
                    print '<td class="nowrap right">';
                    $temp_ht = $fields['totalht'] * $ratiopaymentinvoice;
                    print price(price2num($temp_ht, 'MT'), 1);
                    print '</td>';

                    // VAT
                    print '<td class="nowrap right">';
                    $temp_vat = $fields['vat'] * $ratiopaymentinvoice;
                    print price(price2num($temp_vat, 'MT'), 1);
                    //print price($fields['vat']);
                    print '</td>';
                    print '</tr>';

                    $subtot_coll_total_ht += $temp_ht;
                    $subtot_coll_vat += $temp_vat;
                    $x_coll_sum += $temp_vat;
                }
            }
            // Total customers for this vat rate
            print '<tr class="liste_total">';
            print '<td colspan="4"></td>';
            print '<td class="right">'.$langs->trans("Total").':</td>';
            if ($modetax != 1) {
                print '<td class="nowrap right">&nbsp;</td>';
                print '<td class="right">&nbsp;</td>';
            }
            print '<td class="right">'.price(price2num($subtot_coll_total_ht, 'MT')).'</td>';
            print '<td class="nowrap right">'.price(price2num($subtot_coll_vat, 'MT')).'</td>';
            print '</tr>';
        }
    }

    if (count($x_coll) == 0)   // Show a total ine if nothing shown
    {
        print '<tr class="liste_total">';
        print '<td colspan="4"></td>';
        print '<td class="right">'.$langs->trans("Total").':</td>';
        if ($modetax != 1) {
            print '<td class="nowrap right">&nbsp;</td>';
            print '<td class="right">&nbsp;</td>';
        }
        print '<td class="right">'.price(price2num(0, 'MT')).'</td>';
        print '<td class="nowrap right">'.price(price2num(0, 'MT')).'</td>';
        print '</tr>';
    }

    // Blank line
    print '<tr><td colspan="'.($span + 1).'">&nbsp;</td></tr>';

    // Print table headers for this quadri - expenses now
    print '<tr class="liste_titre liste_titre_topborder">';
    print '<td class="left">'.$elementsup.'</td>';
    print '<td class="left">'.$langs->trans("DateInvoice").'</td>';
    if ($conf->global->TAX_MODE_BUY_PRODUCT == 'payment' || $conf->global->TAX_MODE_BUY_SERVICE == 'payment') print '<td class="left">'.$langs->trans("DatePayment").'</td>';
    else print '<td></td>';
    print '<td class="left">'.$namesup.'</td>';
    print '<td class="left">'.$productsup.'</td>';
    if ($modetax != 1) {
        print '<td class="right">'.$amountsup.'</td>';
        print '<td class="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
    }
    print '<td class="right">'.$langs->trans("AmountHTVATRealPaid").'</td>';
    print '<td class="right">'.$vatsup.'</td>';
    print '</tr>'."\n";

    foreach (array_keys($x_paye) as $thirdparty_id)
    {
        $subtot_paye_total_ht = 0;
        $subtot_paye_vat = 0;

        if ($min == 0 || ($min > 0 && $x_both[$thirdparty_id]['paye']['totalht'] > $min)) {
            if (is_array($x_both[$thirdparty_id]['paye']['detail'])) {
                print "<tr>";
                print '<td class="tax_rate">';
                if (is_numeric($thirdparty_id)) {
                    $company_static->fetch($thirdparty_id);
                    print $langs->trans("ThirdParty").': '.$company_static->getNomUrl(1);
                } else {
                    $tmpid = preg_replace('/userid_/', '', $thirdparty_id);
                    $user_static->fetch($tmpid);
                    print $langs->trans("User").': '.$user_static->getNomUrl(1);
                }
                print '<td colspan="'.($span + 1).'"></td>';
                print '</tr>'."\n";

                foreach ($x_both[$thirdparty_id]['paye']['detail'] as $index => $fields) {
                    // Define type
                    // We MUST use dtype (type in line). We can use something else, only if dtype is really unknown.
                    $type = (isset($fields['dtype']) ? $fields['dtype'] : $fields['ptype']);
                    // Try to enhance type detection using date_start and date_end for free lines where type
                    // was not saved.
                    if (!empty($fields['ddate_start'])) {
                        $type = 1;
                    }
                    if (!empty($fields['ddate_end'])) {
                        $type = 1;
                    }


                    print '<tr class="oddeven">';

                    // Ref
                    print '<td class="nowrap left">'.$fields['link'].'</td>';

                    // Invoice date
                    print '<td class="left">'.dol_print_date($fields['datef'], 'day').'</td>';

                    // Payment date
                    if ($conf->global->TAX_MODE_BUY_PRODUCT == 'payment' || $conf->global->TAX_MODE_BUY_SERVICE == 'payment') {
                        print '<td class="left">'.dol_print_date($fields['datep'], 'day').'</td>';
                    } else {
                        print '<td></td>';
                    }

                    // Company name
                    print '<td class="left">'.$fields['company_link'].'</td>';

                    // Description
                    print '<td class="left">';
                    if ($fields['pid']) {
                        $product_static->id = $fields['pid'];
                        $product_static->ref = $fields['pref'];
                        $product_static->type = $fields['dtype']; // We force with the type of line to have type how line is registered
                        print $product_static->getNomUrl(1);
                        if (dol_string_nohtmltag($fields['descr'])) {
                            print ' - '.dol_trunc(dol_string_nohtmltag($fields['descr']), 24);
                        }
                    } else {
                        if ($type) {
                            $text = img_object($langs->trans('Service'), 'service');
                        } else {
                            $text = img_object($langs->trans('Product'), 'product');
                        }
                        if (preg_match('/^\((.*)\)$/', $fields['descr'], $reg)) {
                            if ($reg[1] == 'DEPOSIT') {
                                $fields['descr'] = $langs->transnoentitiesnoconv('Deposit');
                            } elseif ($reg[1] == 'CREDIT_NOTE') {
                                $fields['descr'] = $langs->transnoentitiesnoconv('CreditNote');
                            } else {
                                $fields['descr'] = $langs->transnoentitiesnoconv($reg[1]);
                            }
                        }
                        print $text.' '.dol_trunc(dol_string_nohtmltag($fields['descr']), 24);

                        // Show range
                        print_date_range($fields['ddate_start'], $fields['ddate_end']);
                    }
                    print '</td>';

                    // Total HT
                    if ($modetax != 1) {
                        print '<td class="nowrap right">';
                        print price($fields['totalht']);
                        if (price2num($fields['ftotal_ttc'])) {
                            //print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
                            $ratiolineinvoice = ($fields['dtotal_ttc'] / $fields['ftotal_ttc']);
                            //print ' ('.round($ratiolineinvoice*100,2).'%)';
                        }
                        print '</td>';
                    }

                    // Payment
                    $ratiopaymentinvoice = 1;
                    if ($modetax != 1)
                    {
                        print '<td class="nowrap right">';
                        if ($fields['payment_amount'] && $fields['ftotal_ttc'])
                        {
                            $paymentfourn_static->id = $fields['payment_id'];
                            print $paymentfourn_static->getNomUrl(2);
                        }

                        if (($type == 0 && $conf->global->TAX_MODE_BUY_PRODUCT == 'invoice')
                            || ($type == 1 && $conf->global->TAX_MODE_BUY_SERVICE == 'invoice'))
                        {
                            print $langs->trans("NA");
                        }
                        else
                        {
                            if (isset($fields['payment_amount']) && $fields['ftotal_ttc']) {
                                $ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
                            }
                            print price(price2num($fields['payment_amount'], 'MT'));
                            if (isset($fields['payment_amount'])) {
                                print ' ('.round($ratiopaymentinvoice * 100, 2).'%)';
                            }
                        }
                        print '</td>';
                    }

                    // VAT paid
                    print '<td class="nowrap right">';
                    $temp_ht = $fields['totalht'] * $ratiopaymentinvoice;
                    print price(price2num($temp_ht, 'MT'), 1);
                    print '</td>';

                    // VAT
                    print '<td class="nowrap right">';
                    $temp_vat = $fields['vat'] * $ratiopaymentinvoice;
                    print price(price2num($temp_vat, 'MT'), 1);
                    //print price($fields['vat']);
                    print '</td>';
                    print '</tr>';

                    $subtot_paye_total_ht += $temp_ht;
                    $subtot_paye_vat += $temp_vat;
                    $x_paye_sum += $temp_vat;
                }
            }
            // Total suppliers for this vat rate
            print '<tr class="liste_total">';
            print '<td colspan="4"></td>';
            print '<td class="right">'.$langs->trans("Total").':</td>';
            if ($modetax != 1) {
                print '<td class="nowrap right">&nbsp;</td>';
                print '<td class="right">&nbsp;</td>';
            }
            print '<td class="right">'.price(price2num($subtot_paye_total_ht, 'MT')).'</td>';
            print '<td class="nowrap right">'.price(price2num($subtot_paye_vat, 'MT')).'</td>';
            print '</tr>';
        }
    }

    if (count($x_paye) == 0) {  // Show a total line if nothing shown
        print '<tr class="liste_total">';
        print '<td colspan="4"></td>';
        print '<td class="right">'.$langs->trans("Total").':</td>';
        if ($modetax != 1) {
            print '<td class="nowrap right">&nbsp;</td>';
            print '<td class="right">&nbsp;</td>';
        }
        print '<td class="right">'.price(price2num(0, 'MT')).'</td>';
        print '<td class="nowrap right">'.price(price2num(0, 'MT')).'</td>';
        print '</tr>';
    }

    print '</table>';

    // Total to pay
    print '<br><br>';
    print '<table class="noborder centpercent">';
    $diff = $x_coll_sum - $x_paye_sum;
    print '<tr class="liste_total">';
    print '<td class="liste_total" colspan="'.$span.'">'.$langs->trans("TotalToPay").($q ? ', '.$langs->trans("Quadri").' '.$q : '').'</td>';
    print '<td class="liste_total nowrap right"><b>'.price(price2num($diff, 'MT'))."</b></td>\n";
    print "</tr>\n";

    $i++;
}

print '</table>';


llxFooter();

$db->close();
