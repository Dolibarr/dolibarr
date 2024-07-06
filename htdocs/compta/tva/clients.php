<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Yannick Warnier      <ywarnier@beeznest.org>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

// Load Dolibarr environment
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

include DOL_DOCUMENT_ROOT.'/compta/tva/initdatesforvat.inc.php';

$min = price2num(GETPOST("min", "alpha"));
if (empty($min)) {
	$min = 0;
}

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = getDolGlobalInt('TAX_MODE');
if (GETPOSTISSET("modetax")) {
	$modetax = GETPOSTINT("modetax");
}
if (empty($modetax)) {
	$modetax = 0;
}

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'tva', 'charges');



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
$user_static = new User($db);

$morequerystring = '';
$listofparams = array('date_startmonth', 'date_startyear', 'date_startday', 'date_endmonth', 'date_endyear', 'date_endday');
foreach ($listofparams as $param) {
	if (GETPOST($param) != '') {
		$morequerystring .= ($morequerystring ? '&' : '').$param.'='.GETPOST($param);
	}
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
if ($modetax == 0) {
	$calcmode = $langs->trans('OptionVATDefault');
}
if ($modetax == 1) {
	$calcmode = $langs->trans('OptionVATDebitOption');
}
if ($modetax == 2) {
	$calcmode = $langs->trans('OptionPaymentForProductAndServices');
}
$calcmode .= ' <span class="opacitymedium">('.$langs->trans("TaxModuleSetupToModifyRules", DOL_URL_ROOT.'/admin/taxes.php').')</span>';
// Set period
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
$prevyear = $date_start_year;
$q = 0;
$prevquarter = $q;
if ($prevquarter > 1) {
	$prevquarter--;
} else {
	$prevquarter = 4;
	$prevyear--;
}
$nextyear = $date_start_year;
$nextquarter = $q;
if ($nextquarter < 4) {
	$nextquarter++;
} else {
	$nextquarter = 1;
	$nextyear++;
}
$builddate = dol_now();

if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice') {
	$description = $langs->trans("RulesVATDueProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment') {
	$description .= $langs->trans("RulesVATInProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice') {
	$description .= '<br>'.$langs->trans("RulesVATDueServices");
}
if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
	$description .= '<br>'.$langs->trans("RulesVATInServices");
}
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= '<br>'.$langs->trans("DepositsAreNotIncluded");
}
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= $langs->trans("SupplierDepositsAreNotIncluded");
}
if (isModEnabled('accounting')) {
	$description .= '<br>'.$langs->trans("ThisIsAnEstimatedValue");
}

//$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
$description .= ($description ? '<br>' : '').$fsearch;
if (getDolGlobalString('TAX_REPORT_EXTRA_REPORT')) {
	$description .= '<br>';
	$description .= '<input type="radio" name="extra_report" value="0" '.($special_report ? '' : 'checked="checked"').'> ';
	$description .= $langs->trans('SimpleReport');
	$description .= '</input>';
	$description .= '<br>';
	$description .= '<input type="radio" name="extra_report" value="1" '.($special_report ? 'checked="checked"' : '').'> ';
	$description .= $langs->trans('AddExtraReport');
	$description .= '</input>';
	$description .= '<br>';
}

$elementcust = $langs->trans("CustomersInvoices");
$productcust = $langs->trans("Description");
$namerate = $langs->trans("VATRate");
$amountcust = $langs->trans("AmountHT");
if ($mysoc->tva_assuj) {
	$vatcust = ' ('.$langs->trans("StatusToPay").')';
}
$elementsup = $langs->trans("SuppliersInvoices");
$productsup = $langs->trans("Description");
$amountsup = $langs->trans("AmountHT");
if ($mysoc->tva_assuj) {
	$vatsup = ' ('.$langs->trans("ToGetBack").')';
}
$periodlink = '';
$exportlink = '';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

$vatcust = $langs->trans("VATReceived");
$vatsup = $langs->trans("VATPaid");


// VAT Received
print '<div class="div-table-responsive">';
print "<table class=\"noborder\" width=\"100%\">";

$y = $year_current;
$total = 0;
$i = 0;
$columns = 5;
$span = $columns;
if ($modetax != 1) {
	$span += 2;
}

// Load arrays of datas
$x_coll = tax_by_thirdparty('vat', $db, 0, $date_start, $date_end, $modetax, 'sell');
$x_paye = tax_by_thirdparty('vat', $db, 0, $date_start, $date_end, $modetax, 'buy');

if (!is_array($x_coll) || !is_array($x_paye)) {
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
	foreach (array_keys($x_coll) as $my_coll_thirdpartyid) {
		$x_both[$my_coll_thirdpartyid]['coll']['totalht'] = $x_coll[$my_coll_thirdpartyid]['totalht'];
		$x_both[$my_coll_thirdpartyid]['coll']['vat'] = $x_coll[$my_coll_thirdpartyid]['vat'];
		$x_both[$my_coll_thirdpartyid]['paye']['totalht'] = 0;
		$x_both[$my_coll_thirdpartyid]['paye']['vat'] = 0;
		$x_both[$my_coll_thirdpartyid]['coll']['links'] = '';
		$x_both[$my_coll_thirdpartyid]['coll']['detail'] = array();
		foreach ($x_coll[$my_coll_thirdpartyid]['facid'] as $id => $dummy) {
			$invoice_customer->id = $x_coll[$my_coll_thirdpartyid]['facid'][$id];
			$invoice_customer->ref = $x_coll[$my_coll_thirdpartyid]['facnum'][$id];
			$invoice_customer->type = $x_coll[$my_coll_thirdpartyid]['type'][$id];

			//$company_static->fetch($x_coll[$my_coll_thirdpartyid]['company_id'][$id]);
			$company_static->id = $x_coll[$my_coll_thirdpartyid]['company_id'][$id];
			$company_static->name = $x_coll[$my_coll_thirdpartyid]['company_name'][$id];
			$company_static->name_alias = $x_coll[$my_coll_thirdpartyid]['company_alias'][$id];
			$company_static->email = $x_coll[$my_coll_thirdpartyid]['company_email'][$id];
			$company_static->tva_intra = isset($x_coll[$my_coll_thirdpartyid]['tva_intra'][$id]) ? $x_coll[$my_coll_thirdpartyid]['tva_intra'][$id] : 0;
			$company_static->client = $x_coll[$my_coll_thirdpartyid]['company_client'][$id];
			$company_static->fournisseur = $x_coll[$my_coll_thirdpartyid]['company_fournisseur'][$id];
			$company_static->status = $x_coll[$my_coll_thirdpartyid]['company_status'][$id];
			$company_static->code_client = $x_coll[$my_coll_thirdpartyid]['company_customer_code'][$id];
			$company_static->code_compta_client = $x_coll[$my_coll_thirdpartyid]['company_customer_accounting_code'][$id];
			$company_static->code_fournisseur = $x_coll[$my_coll_thirdpartyid]['company_supplier_code'][$id];
			$company_static->code_compta_fournisseur = $x_coll[$my_coll_thirdpartyid]['company_supplier_accounting_code'][$id];

			$x_both[$my_coll_thirdpartyid]['coll']['detail'][] = array(
				'id'        => $x_coll[$my_coll_thirdpartyid]['facid'][$id],
				'descr'     => $x_coll[$my_coll_thirdpartyid]['descr'][$id],

				'pid'       => $x_coll[$my_coll_thirdpartyid]['pid'][$id],
				'pref'      => isset($x_coll[$my_coll_thirdpartyid]['pref'][$id]) ? $x_coll[$my_coll_thirdpartyid]['pref'][$id] : '',
				'ptype'     => $x_coll[$my_coll_thirdpartyid]['ptype'][$id],
				'pstatus'   => isset($x_paye[$my_coll_thirdpartyid]['pstatus'][$id]) ? $x_paye[$my_coll_thirdpartyid]['pstatus'][$id] : '',
				'pstatusbuy' => isset($x_paye[$my_coll_thirdpartyid]['pstatusbuy'][$id]) ? $x_paye[$my_coll_thirdpartyid]['pstatusbuy'][$id] : '',

				'payment_id' => $x_coll[$my_coll_thirdpartyid]['payment_id'][$id],
				'payment_ref' => isset($x_coll[$my_coll_thirdpartyid]['payment_ref'][$id]) ? $x_coll[$my_coll_thirdpartyid]['payment_ref'][$id] : '',
				'payment_amount' => $x_coll[$my_coll_thirdpartyid]['payment_amount'][$id],
				'ftotal_ttc' => $x_coll[$my_coll_thirdpartyid]['ftotal_ttc'][$id],
				'dtotal_ttc' => $x_coll[$my_coll_thirdpartyid]['dtotal_ttc'][$id],
				'dtype'     => $x_coll[$my_coll_thirdpartyid]['dtype'][$id],
				'drate'     => $x_coll[$my_coll_thirdpartyid]['drate'][$id],
				'datef'     => $x_coll[$my_coll_thirdpartyid]['datef'][$id],
				'datep'     => $x_coll[$my_coll_thirdpartyid]['datep'][$id],

				'company_link' => $company_static->getNomUrl(1, '', 20),

				'ddate_start' => $x_coll[$my_coll_thirdpartyid]['ddate_start'][$id],
				'ddate_end'  => $x_coll[$my_coll_thirdpartyid]['ddate_end'][$id],
				'totalht'   => $x_coll[$my_coll_thirdpartyid]['totalht_list'][$id],
				'vat'       => $x_coll[$my_coll_thirdpartyid]['vat_list'][$id],
				'link'      => $invoice_customer->getNomUrl(1, '', 12)
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

		foreach ($x_paye[$my_paye_thirdpartyid]['facid'] as $id => $dummy) {
			// ExpenseReport
			if ($x_paye[$my_paye_thirdpartyid]['ptype'][$id] == 'ExpenseReportPayment') {
				$expensereport->id = $x_paye[$my_paye_thirdpartyid]['facid'][$id];
				$expensereport->ref = $x_paye[$my_paye_thirdpartyid]['facnum'][$id];
				$expensereport->type = $x_paye[$my_paye_thirdpartyid]['type'][$id];

				$x_both[$my_paye_thirdpartyid]['paye']['detail'][] = array(
					'id'				=> $x_paye[$my_paye_thirdpartyid]['facid'][$id],
					'descr'				=> $x_paye[$my_paye_thirdpartyid]['descr'][$id],

					'pid'				=> $x_paye[$my_paye_thirdpartyid]['pid'][$id],
					'pref'				=> $x_paye[$my_paye_thirdpartyid]['pref'][$id],
					'ptype'				=> $x_paye[$my_paye_thirdpartyid]['ptype'][$id],
					'pstatus'           => $x_paye[$my_paye_thirdpartyid]['pstatus'][$id],
					'pstatusbuy'        => $x_paye[$my_paye_thirdpartyid]['pstatusbuy'][$id],

					'payment_id'		=> $x_paye[$my_paye_thirdpartyid]['payment_id'][$id],
					'payment_ref'		=> $x_paye[$my_paye_thirdpartyid]['payment_ref'][$id],
					'payment_amount'	=> $x_paye[$my_paye_thirdpartyid]['payment_amount'][$id],
					'ftotal_ttc'		=> price2num($x_paye[$my_paye_thirdpartyid]['ftotal_ttc'][$id]),
					'dtotal_ttc'		=> price2num($x_paye[$my_paye_thirdpartyid]['dtotal_ttc'][$id]),
					'dtype'				=> $x_paye[$my_paye_thirdpartyid]['dtype'][$id],
					'drate'             => $x_paye[$my_coll_thirdpartyid]['drate'][$id],
					'ddate_start'		=> $x_paye[$my_paye_thirdpartyid]['ddate_start'][$id],
					'ddate_end'			=> $x_paye[$my_paye_thirdpartyid]['ddate_end'][$id],
					'totalht'			=> price2num($x_paye[$my_paye_thirdpartyid]['totalht_list'][$id]),
					'vat'				=> $x_paye[$my_paye_thirdpartyid]['vat_list'][$id],
					'link'				=> $expensereport->getNomUrl(1)
				);
			} else {
				$invoice_supplier->id = $x_paye[$my_paye_thirdpartyid]['facid'][$id];
				$invoice_supplier->ref = $x_paye[$my_paye_thirdpartyid]['facnum'][$id];
				$invoice_supplier->type = $x_paye[$my_paye_thirdpartyid]['type'][$id];

				//$company_static->fetch($x_paye[$my_paye_thirdpartyid]['company_id'][$id]);
				$company_static->id = $x_paye[$my_paye_thirdpartyid]['company_id'][$id];
				$company_static->name = $x_paye[$my_paye_thirdpartyid]['company_name'][$id];
				$company_static->name_alias = $x_paye[$my_paye_thirdpartyid]['company_alias'][$id];
				$company_static->email = $x_paye[$my_paye_thirdpartyid]['company_email'][$id];
				$company_static->tva_intra = $x_paye[$my_paye_thirdpartyid]['tva_intra'][$id];
				$company_static->client = $x_paye[$my_paye_thirdpartyid]['company_client'][$id];
				$company_static->fournisseur = $x_paye[$my_paye_thirdpartyid]['company_fournisseur'][$id];
				$company_static->status = $x_paye[$my_paye_thirdpartyid]['company_status'][$id];
				$company_static->code_client = $x_paye[$my_paye_thirdpartyid]['company_customer_code'][$id];
				$company_static->code_compta_client = $x_paye[$my_paye_thirdpartyid]['company_customer_accounting_code'][$id];
				$company_static->code_fournisseur = $x_paye[$my_paye_thirdpartyid]['company_supplier_code'][$id];
				$company_static->code_compta_fournisseur = $x_paye[$my_paye_thirdpartyid]['company_supplier_accounting_code'][$id];

				$x_both[$my_paye_thirdpartyid]['paye']['detail'][] = array(
					'id'        => $x_paye[$my_paye_thirdpartyid]['facid'][$id],
					'descr'     => $x_paye[$my_paye_thirdpartyid]['descr'][$id],

					'pid'       => $x_paye[$my_paye_thirdpartyid]['pid'][$id],
					'pref'      => $x_paye[$my_paye_thirdpartyid]['pref'][$id],
					'ptype'     => $x_paye[$my_paye_thirdpartyid]['ptype'][$id],
					'pstatus'   => $x_paye[$my_paye_thirdpartyid]['pstatus'][$id],
					'pstatusbuy' => $x_paye[$my_paye_thirdpartyid]['pstatusbuy'][$id],

					'payment_id' => $x_paye[$my_paye_thirdpartyid]['payment_id'][$id],
					'payment_ref' => $x_paye[$my_paye_thirdpartyid]['payment_ref'][$id],
					'payment_amount' => $x_paye[$my_paye_thirdpartyid]['payment_amount'][$id],
					'ftotal_ttc' => price2num($x_paye[$my_paye_thirdpartyid]['ftotal_ttc'][$id]),
					'dtotal_ttc' => price2num($x_paye[$my_paye_thirdpartyid]['dtotal_ttc'][$id]),
					'dtype'     => $x_paye[$my_paye_thirdpartyid]['dtype'][$id],
					'drate'     => $x_paye[$my_coll_thirdpartyid]['drate'][$id],
					'datef'     => $x_paye[$my_paye_thirdpartyid]['datef'][$id],
					'datep'     => $x_paye[$my_paye_thirdpartyid]['datep'][$id],

					'company_link' => $company_static->getNomUrl(1, '', 20),

					'ddate_start' => $x_paye[$my_paye_thirdpartyid]['ddate_start'][$id],
					'ddate_end'  => $x_paye[$my_paye_thirdpartyid]['ddate_end'][$id],
					'totalht'   => price2num($x_paye[$my_paye_thirdpartyid]['totalht_list'][$id]),
					'vat'       => $x_paye[$my_paye_thirdpartyid]['vat_list'][$id],
					'link'      => $invoice_supplier->getNomUrl(1, '', 12)
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

	//print '<tr><td colspan="'.($span+1).'">'..')</td></tr>';

	// Customers invoices
	print '<tr class="liste_titre">';
	print '<td class="left">'.$elementcust.'</td>';
	print '<td class="left">'.$langs->trans("DateInvoice").'</td>';
	if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
		print '<td class="left">'.$langs->trans("DatePayment").'</td>';
	} else {
		print '<td></td>';
	}
	print '<td class="right"></td>';
	print '<td class="left">'.$productcust.'</td>';
	if ($modetax != 1) {
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
	// Initialize a technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
	$hookmanager->initHooks(array('externalbalance'));
	$reshook = $hookmanager->executeHooks('addVatLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

	foreach (array_keys($x_coll) as $thirdparty_id) {
		$subtot_coll_total_ht = 0;
		$subtot_coll_vat = 0;

		if ($min == 0 || ($min > 0 && $x_both[$thirdparty_id]['coll']['totalht'] > $min)) {
			if (is_array($x_both[$thirdparty_id]['coll']['detail'])) {
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
					if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
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
						$product_static->status = $fields['pstatus'];
						$product_static->status_buy = $fields['pstatusbuy'];

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
						print '<td class="nowrap right"><span class="amount">';
						print price($fields['totalht']);
						if (price2num($fields['ftotal_ttc'])) {
							//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
							$ratiolineinvoice = ($fields['dtotal_ttc'] / $fields['ftotal_ttc']);
							//print ' ('.round($ratiolineinvoice*100,2).'%)';
						}
						print '</span></td>';
					}

					// Payment
					$ratiopaymentinvoice = 1;
					if ($modetax != 1) {
						print '<td class="nowrap right">';
						//print $fields['totalht']."-".$fields['payment_amount']."-".$fields['ftotal_ttc'];
						if ($fields['payment_amount'] && $fields['ftotal_ttc']) {
							$payment_static->id = $fields['payment_id'];
							$payment_static->ref = $fields['payment_ref'];
							print $payment_static->getNomUrl(2, '', '', 0).' ';
						}
						if (($type == 0 && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
							|| ($type == 1 && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')) {
							print $langs->trans("NA");
						} else {
							if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) {
								$ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
							}
							print '<span class="amount">'.price(price2num($fields['payment_amount'], 'MT')).'</span>';
							if (isset($fields['payment_amount'])) {
								print ' ('.round($ratiopaymentinvoice * 100, 2).'%)';
							}
						}
						print '</td>';
					}

					// Total collected
					print '<td class="nowrap right"><span class="amount">';
					$temp_ht = $fields['totalht'] * $ratiopaymentinvoice;
					print price(price2num($temp_ht, 'MT'), 1);
					print '</span></td>';

					// VAT
					print '<td class="nowrap right"><span class="amount">';
					$temp_vat = $fields['vat'] * $ratiopaymentinvoice;
					print price(price2num($temp_vat, 'MT'), 1);
					//print price($fields['vat']);
					print '</span></td>';
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
			print '<td class="right"><span class="amount">'.price(price2num($subtot_coll_total_ht, 'MT')).'</span></td>';
			print '<td class="nowrap right"><span class="amount">'.price(price2num($subtot_coll_vat, 'MT')).'</span></td>';
			print '</tr>';
		}
	}

	if (count($x_coll) == 0) {   // Show a total line if nothing shown
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
	if (getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'payment') {
		print '<td class="left">'.$langs->trans("DatePayment").'</td>';
	} else {
		print '<td></td>';
	}
	print '<td class="left"></td>';
	print '<td class="left">'.$productsup.'</td>';
	if ($modetax != 1) {
		print '<td class="right">'.$amountsup.'</td>';
		print '<td class="right">'.$langs->trans("Payment").' ('.$langs->trans("PercentOfInvoice").')</td>';
	}
	print '<td class="right">'.$langs->trans("AmountHTVATRealPaid").'</td>';
	print '<td class="right">'.$vatsup.'</td>';
	print '</tr>'."\n";

	foreach (array_keys($x_paye) as $thirdparty_id) {
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
					if (getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'payment') {
						print '<td class="left">'.dol_print_date($fields['datep'], 'day').'</td>';
					} else {
						print '<td></td>';
					}

					// Company name
					print '<td class="tdmaxoverflow150">';
					print $fields['company_link'];
					print '</td>';

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
						print '<td class="nowrap right"><span class="amount">';
						print price($fields['totalht']);
						if (price2num($fields['ftotal_ttc'])) {
							//print $fields['dtotal_ttc']."/".$fields['ftotal_ttc']." - ";
							$ratiolineinvoice = ((float) $fields['dtotal_ttc'] / (float) $fields['ftotal_ttc']);
							//print ' ('.round($ratiolineinvoice*100,2).'%)';
						}
						print '</span></td>';
					}

					// Payment
					$ratiopaymentinvoice = 1;
					if ($modetax != 1) {
						print '<td class="nowrap right">';
						if ($fields['payment_amount'] && $fields['ftotal_ttc']) {
							$paymentfourn_static->id = $fields['payment_id'];
							$paymentfourn_static->ref = $fields['payment_ref'];
							print $paymentfourn_static->getNomUrl(2, '', '', 0);
						}

						if (($type == 0 && getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'invoice')
							|| ($type == 1 && getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'invoice')) {
							print $langs->trans("NA");
						} else {
							if (isset($fields['payment_amount']) && $fields['ftotal_ttc']) {
								$ratiopaymentinvoice = ($fields['payment_amount'] / (float) $fields['ftotal_ttc']);
							}
							print '<span class="amount">'.price(price2num($fields['payment_amount'], 'MT')).'</span>';
							if (isset($fields['payment_amount'])) {
								print ' ('.round($ratiopaymentinvoice * 100, 2).'%)';
							}
						}
						print '</td>';
					}

					// VAT paid
					print '<td class="nowrap right"><span class="amount">';
					$temp_ht = (float) $fields['totalht'] * $ratiopaymentinvoice;
					print price(price2num($temp_ht, 'MT'), 1);
					print '</span></td>';

					// VAT
					print '<td class="nowrap right"><span class="amount">';
					$temp_vat = $fields['vat'] * $ratiopaymentinvoice;
					print price(price2num($temp_vat, 'MT'), 1);
					//print price($fields['vat']);
					print '</span></td>';
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
			print '<td class="right"><span class="amount">'.price(price2num($subtot_paye_total_ht, 'MT')).'</span></td>';
			print '<td class="nowrap right"><span class="amount">'.price(price2num($subtot_paye_vat, 'MT')).'</span></td>';
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

	// Total to pay
	print '<tr><td colspan="'.($span + 2).'"></td></tr>';

	$diff = $x_coll_sum - $x_paye_sum;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="'.($span + 1).'">'.$langs->trans("TotalToPay").($q ? ', '.$langs->trans("Quadri").' '.$q : '').'</td>';
	print '<td class="liste_total nowrap right"><b>'.price(price2num($diff, 'MT'))."</b></td>\n";
	print "</tr>\n";

	$i++;
}

print '</table>';
print '</div>';

llxFooter();

$db->close();
