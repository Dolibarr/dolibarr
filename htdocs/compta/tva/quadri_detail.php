<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2015  Yannick Warnier         <ywarnier@beeznest.org>
 * Copyright (C) 2014       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2019       Eric Seigne             <eric.seigne@cap-rel.fr>
 * Copyright (C) 2021-2022  Open-Dsi                <support@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *        \file       htdocs/compta/tva/quadri_detail.php
 *        \ingroup    tax
 *        \brief      VAT by rate
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/localtax/class/localtax.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("other", "compta", "banks", "bills", "companies", "product", "trips", "admin"));

$refresh = (GETPOSTISSET('submit') || GETPOSTISSET('vat_rate_show') || GETPOSTISSET('invoice_type'));
$invoice_type = GETPOSTISSET('invoice_type') ? GETPOST('invoice_type', 'alpha') : '';
$vat_rate_show = GETPOSTISSET('vat_rate_show') ? GETPOST('vat_rate_show', 'alphanohtml') : -1;

// Set $date_start_xxx and $date_end_xxx...
include DOL_DOCUMENT_ROOT . '/compta/tva/initdatesforvat.inc.php';
// Variables provided by include:
'
@phan-var-force int $date_start
@phan-var-force int $date_end
@phan-var-force string $date_start_month
@phan-var-force string $date_start_year
@phan-var-force string $date_start_day
@phan-var-force string $date_end_month
@phan-var-force string $date_end_year
@phan-var-force string $date_end_day
';

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

$object = new Tva($db);

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

$morequerystring = '';
$listofparams = array('date_startmonth', 'date_startyear', 'date_startday', 'date_endmonth', 'date_endyear', 'date_endday');
foreach ($listofparams as $param) {
	if (GETPOST($param) != '') {
		$morequerystring .= ($morequerystring ? '&' : '') . $param . '=' . GETPOST($param);
	}
}

$title = $langs->trans("VATReport") . " " . dol_print_date($date_start, '', 'tzserver') . " -> " . dol_print_date($date_end, '', 'tzserver');
llxHeader('', $title, '', '', 0, 0, '', '', $morequerystring);


//print load_fiche_titre($langs->trans("VAT"),"");

//$fsearch.='<br>';
$fsearch = '<!-- hidden fields for form -->';
$fsearch .= '<input type="hidden" name="token" value="' . newToken() . '">';
$fsearch .= '<input type="hidden" name="modetax" value="' . $modetax . '">';
//$fsearch.='  '.$langs->trans("SalesTurnoverMinimum").': ';
//$fsearch.='  <input type="text" name="min" value="'.$min.'">';


// Show report header
$name = $langs->trans("VATReportByRates");
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
$calcmode .= ' <span class="opacitymedium">(' . $langs->trans("TaxModuleSetupToModifyRules", DOL_URL_ROOT . '/admin/taxes.php') . ')</span>';
// Set period
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
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
$description = $fsearch;
$builddate = dol_now();

if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice') {
	$description .= $langs->trans("RulesVATDueProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment') {
	$description .= $langs->trans("RulesVATInProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice') {
	$description .= '<br>' . $langs->trans("RulesVATDueServices");
}
if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
	$description .= '<br>' . $langs->trans("RulesVATInServices");
}
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= '<br>' . $langs->trans("DepositsAreNotIncluded");
}
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= $langs->trans("SupplierDepositsAreNotIncluded");
}
if (isModEnabled('accounting')) {
	$description .= '<br>' . $langs->trans("ThisIsAnEstimatedValue");
}

// Customers invoices
$elementcust = $langs->trans("CustomersInvoices");
$productcust = $langs->trans("ProductOrService");
$amountcust = $langs->trans("AmountHT");
$vatcust = $langs->trans("VATReceived");
$namecust = $langs->trans("Name");
if ($mysoc->tva_assuj) {
	$vatcust .= ' (' . $langs->trans("VATToPay") . ')';
}

// Suppliers invoices
$elementsup = $langs->trans("SuppliersInvoices");
$productsup = $productcust;
$amountsup = $amountcust;
$vatsup = $langs->trans("VATPaid");
$namesup = $namecust;
if ($mysoc->tva_assuj) {
	$vatsup .= ' (' . $langs->trans("ToGetBack") . ')';
}

$optioncss = GETPOST('optioncss', 'alpha');
$periodlink = '';
$exportlink = '';

if ($optioncss != "print") {
	report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);
}

$vatcust = $langs->trans("VATReceived");
$vatsup = $langs->trans("VATPaid");
$vatexpensereport = $langs->trans("VATPaid");


// VAT Received and paid
print '<div class="div-table-responsive">';
print '<table class="liste noborder centpercent">';

$y = $year_current;
$i = 0;

$columns = 7;
$span = $columns;
if ($modetax != 1) {
	$span += 2;
}

// Load arrays of datas
$x_coll = tax_by_rate('vat', $db, 0, 0, $date_start, $date_end, $modetax, 'sell');
$x_paye = tax_by_rate('vat', $db, 0, 0, $date_start, $date_end, $modetax, 'buy');

if (!is_array($x_coll) || !is_array($x_paye)) {
	$langs->load("errors");
	if ($x_coll == -1) {
		print '<tr><td colspan="' . $columns . '">' . $langs->trans("ErrorNoAccountancyModuleLoaded") . '</td></tr>';
	} elseif ($x_coll == -2) {
		print '<tr><td colspan="' . $columns . '">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
	} else {
		print '<tr><td colspan="' . $columns . '">' . $langs->trans("Error") . '</td></tr>';
	}
} else {
	$x_both = array();
	//now, from these two arrays, get another array with one rate per line
	foreach (array_keys($x_coll) as $my_coll_rate) {
		$x_both[$my_coll_rate] = array(
			'coll' => array(),
			'paye' => array(),
		);
		$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
		$x_both[$my_coll_rate]['coll']['vat'] = $x_coll[$my_coll_rate]['vat'];
		$x_both[$my_coll_rate]['paye']['totalht'] = 0;
		$x_both[$my_coll_rate]['paye']['vat'] = 0;
		$x_both[$my_coll_rate]['coll']['links'] = '';
		$x_both[$my_coll_rate]['coll']['detail'] = array();
		foreach ($x_coll[$my_coll_rate]['facid'] as $id => $dummy) {
			$invoice_customer->id = $x_coll[$my_coll_rate]['facid'][$id];
			$invoice_customer->ref = $x_coll[$my_coll_rate]['facnum'][$id];
			$invoice_customer->type = $x_coll[$my_coll_rate]['type'][$id];

			//$company_static->fetch($x_coll[$my_coll_rate]['company_id'][$id]);
			$company_static->id = $x_coll[$my_coll_rate]['company_id'][$id];
			$company_static->name = $x_coll[$my_coll_rate]['company_name'][$id];
			$company_static->name_alias = $x_coll[$my_coll_rate]['company_alias'][$id];
			$company_static->email = $x_coll[$my_coll_rate]['company_email'][$id];
			$company_static->tva_intra = isset($x_coll[$my_coll_rate]['tva_intra'][$id]) ? $x_coll[$my_coll_rate]['tva_intra'][$id] : '0';  // @phan-suppress-current-line PhanTypeArraySuspiciousNull,PhanTypeInvalidDimOffset
			$company_static->client = $x_coll[$my_coll_rate]['company_client'][$id];
			$company_static->fournisseur = $x_coll[$my_coll_rate]['company_fournisseur'][$id];
			$company_static->status = $x_coll[$my_coll_rate]['company_status'][$id];
			$company_static->code_client = $x_coll[$my_coll_rate]['company_customer_code'][$id];
			$company_static->code_compta_client = $x_coll[$my_coll_rate]['company_customer_accounting_code'][$id];
			$company_static->code_fournisseur = $x_coll[$my_coll_rate]['company_supplier_code'][$id];
			$company_static->code_compta_fournisseur = $x_coll[$my_coll_rate]['company_supplier_accounting_code'][$id];

			$x_both[$my_coll_rate]['coll']['detail'][] = array(
				'id' => $x_coll[$my_coll_rate]['facid'][$id],
				'descr' => $x_coll[$my_coll_rate]['descr'][$id],
				'pid' => $x_coll[$my_coll_rate]['pid'][$id],
				'pref' => $x_coll[$my_coll_rate]['pref'][$id],
				'ptype' => $x_coll[$my_coll_rate]['ptype'][$id],
				'payment_id' => $x_coll[$my_coll_rate]['payment_id'][$id],
				'payment_ref' => $x_coll[$my_coll_rate]['payment_ref'][$id],
				'payment_amount' => $x_coll[$my_coll_rate]['payment_amount'][$id],
				'ftotal_ttc' => $x_coll[$my_coll_rate]['ftotal_ttc'][$id],
				'dtotal_ttc' => $x_coll[$my_coll_rate]['dtotal_ttc'][$id],
				'dtype' => $x_coll[$my_coll_rate]['dtype'][$id],
				'datef' => $x_coll[$my_coll_rate]['datef'][$id],
				'datep' => $x_coll[$my_coll_rate]['datep'][$id],

				'company_link' => $company_static->getNomUrl(1, '', 20),

				'ddate_start' => $x_coll[$my_coll_rate]['ddate_start'][$id],
				'ddate_end' => $x_coll[$my_coll_rate]['ddate_end'][$id],
				'totalht' => $x_coll[$my_coll_rate]['totalht_list'][$id],
				'vat' => $x_coll[$my_coll_rate]['vat_list'][$id],
				'link' => $invoice_customer->getNomUrl(1, '', 12)
			);
		}
	}
	// tva paid
	foreach (array_keys($x_paye) as $my_paye_rate) {
		$x_both[$my_paye_rate]['paye']['totalht'] = $x_paye[$my_paye_rate]['totalht'];
		$x_both[$my_paye_rate]['paye']['vat'] = $x_paye[$my_paye_rate]['vat'];
		if (!isset($x_both[$my_paye_rate]['coll']['totalht'])) {
			$x_both[$my_paye_rate]['coll']['totalht'] = 0;
			$x_both[$my_paye_rate]['coll']['vat'] = 0;
		}
		$x_both[$my_paye_rate]['paye']['links'] = '';
		$x_both[$my_paye_rate]['paye']['detail'] = array();

		foreach ($x_paye[$my_paye_rate]['facid'] as $id => $dummy) {
			// ExpenseReport
			if ($x_paye[$my_paye_rate]['ptype'][$id] === 'ExpenseReportPayment') {
				$expensereport->id = $x_paye[$my_paye_rate]['facid'][$id];
				$expensereport->ref = $x_paye[$my_paye_rate]['facnum'][$id];
				$expensereport->type = $x_paye[$my_paye_rate]['type'][$id];

				$x_both[$my_paye_rate]['paye']['detail'][] = array(
					'id' => $x_paye[$my_paye_rate]['facid'][$id],
					'descr' => $x_paye[$my_paye_rate]['descr'][$id],
					'pid' => $x_paye[$my_paye_rate]['pid'][$id],
					'pref' => $x_paye[$my_paye_rate]['pref'][$id],
					'ptype' => $x_paye[$my_paye_rate]['ptype'][$id],
					'payment_id' => $x_paye[$my_paye_rate]['payment_id'][$id],
					'payment_ref' => $x_paye[$my_paye_rate]['payment_ref'][$id],
					'payment_amount' => $x_paye[$my_paye_rate]['payment_amount'][$id],
					'ftotal_ttc' => price2num($x_paye[$my_paye_rate]['ftotal_ttc'][$id]),
					'dtotal_ttc' => price2num($x_paye[$my_paye_rate]['dtotal_ttc'][$id]),
					'dtype' => $x_paye[$my_paye_rate]['dtype'][$id],
					'ddate_start' => $x_paye[$my_paye_rate]['ddate_start'][$id],
					'ddate_end' => $x_paye[$my_paye_rate]['ddate_end'][$id],
					'totalht' => price2num($x_paye[$my_paye_rate]['totalht_list'][$id]),
					'vat' => $x_paye[$my_paye_rate]['vat_list'][$id],
					'link' => $expensereport->getNomUrl(1)
				);
			} else {
				$invoice_supplier->id = $x_paye[$my_paye_rate]['facid'][$id];
				$invoice_supplier->ref = $x_paye[$my_paye_rate]['facnum'][$id];
				$invoice_supplier->type = $x_paye[$my_paye_rate]['type'][$id];

				$company_static->id = $x_paye[$my_paye_rate]['company_id'][$id];
				$company_static->name = $x_paye[$my_paye_rate]['company_name'][$id];
				$company_static->name_alias = $x_paye[$my_paye_rate]['company_alias'][$id];
				$company_static->email = $x_paye[$my_paye_rate]['company_email'][$id];
				$company_static->tva_intra = $x_paye[$my_paye_rate]['tva_intra'][$id];
				$company_static->client = $x_paye[$my_paye_rate]['company_client'][$id];
				$company_static->fournisseur = $x_paye[$my_paye_rate]['company_fournisseur'][$id];
				$company_static->status = $x_paye[$my_paye_rate]['company_status'][$id];
				$company_static->code_client = $x_paye[$my_paye_rate]['company_customer_code'][$id];
				$company_static->code_compta_client = $x_paye[$my_paye_rate]['company_customer_accounting_code'][$id];
				$company_static->code_fournisseur = $x_paye[$my_paye_rate]['company_supplier_code'][$id];
				$company_static->code_compta_fournisseur = $x_paye[$my_paye_rate]['company_supplier_accounting_code'][$id];

				$x_both[$my_paye_rate]['paye']['detail'][] = array(
					'id' => $x_paye[$my_paye_rate]['facid'][$id],
					'descr' => $x_paye[$my_paye_rate]['descr'][$id],
					'pid' => $x_paye[$my_paye_rate]['pid'][$id],
					'pref' => $x_paye[$my_paye_rate]['pref'][$id],
					'ptype' => $x_paye[$my_paye_rate]['ptype'][$id],
					'payment_id' => $x_paye[$my_paye_rate]['payment_id'][$id],
					'payment_ref' => $x_paye[$my_paye_rate]['payment_ref'][$id],
					'payment_amount' => $x_paye[$my_paye_rate]['payment_amount'][$id],
					'ftotal_ttc' => price2num($x_paye[$my_paye_rate]['ftotal_ttc'][$id]),
					'dtotal_ttc' => price2num($x_paye[$my_paye_rate]['dtotal_ttc'][$id]),
					'dtype' => $x_paye[$my_paye_rate]['dtype'][$id],
					'datef' => $x_paye[$my_paye_rate]['datef'][$id],
					'datep' => $x_paye[$my_paye_rate]['datep'][$id],

					'company_link' => $company_static->getNomUrl(1, '', 20),

					'ddate_start' => $x_paye[$my_paye_rate]['ddate_start'][$id],
					'ddate_end' => $x_paye[$my_paye_rate]['ddate_end'][$id],
					'totalht' => price2num($x_paye[$my_paye_rate]['totalht_list'][$id]),
					'vat' => $x_paye[$my_paye_rate]['vat_list'][$id],
					'link' => $invoice_supplier->getNomUrl(1, '', 12)
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
	print '<td class="left">' . $elementcust . '</td>';
	print '<td class="left">' . $langs->trans("DateInvoice") . '</td>';
	if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
		print '<td class="left">' . $langs->trans("DatePayment") . '</td>';
	} else {
		print '<td></td>';
	}
	print '<td class="left">' . $namecust . '</td>';
	print '<td class="left">' . $productcust . '</td>';
	if ($modetax != 1) {
		print '<td class="right">' . $amountcust . '</td>';
		print '<td class="right">' . $langs->trans("Payment") . ' (' . $langs->trans("PercentOfInvoice") . ')</td>';
	}
	print '<td class="right">' . $langs->trans("AmountHTVATRealReceived") . '</td>';
	print '<td class="right">' . $vatcust . '</td>';
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

	foreach (array_keys($x_coll) as $rate) {
		$subtot_coll_total_ht = 0;
		$subtot_coll_vat = 0;

		if (is_array($x_both[$rate]['coll']['detail'])) {
			// VAT Rate
			print "<tr>";
			print '<td class="tax_rate" colspan="' . ($span + 1) . '">';
			print $langs->trans('Rate') . ' : ' . vatrate($rate) . '%';
			print ' - <a class="reposition" href="' . DOL_URL_ROOT . '/compta/tva/quadri_detail.php?invoice_type=customer';
			if ($invoice_type != 'customer' || !GETPOSTISSET('vat_rate_show') || GETPOST('vat_rate_show') != $rate) {
				print '&amp;vat_rate_show=' . urlencode($rate);
			}
			print '&amp;date_startyear=' . urlencode($date_start_year) . '&amp;date_startmonth=' . urlencode($date_start_month) . '&amp;date_startday=' . urlencode($date_start_day) . '&amp;date_endyear=' . urlencode($date_end_year) . '&amp;date_endmonth=' . urlencode($date_end_month) . '&amp;date_endday=' . urlencode($date_end_day) . '">' . img_picto('', 'chevron-down', 'class="paddingrightonly"') . $langs->trans('VATReportShowByRateDetails') . '</a>';
			print '</td>';
			print '</tr>' . "\n";

			foreach ($x_both[$rate]['coll']['detail'] as $index => $fields) {
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

				// Payment
				$ratiopaymentinvoice = 1;
				if ($modetax != 1) {
					if (($type == 0 && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
						|| ($type == 1 && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')) {
					} else {
						if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) {
							$ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
						}
					}
				}

				// Total collected
				$temp_ht = (float) $fields['totalht'] * $ratiopaymentinvoice;

				// VAT
				$temp_vat = $fields['vat'] * $ratiopaymentinvoice;

				$subtot_coll_total_ht += $temp_ht;
				$subtot_coll_vat += $temp_vat;
				$x_coll_sum += $temp_vat;
			}
		}

		if ($invoice_type == 'customer' && $vat_rate_show == $rate) {
			if (is_array($x_both[$rate]['coll']['detail'])) {
				foreach ($x_both[$rate]['coll']['detail'] as $index => $fields) {
					/*$company_static->id = $fields['company_id'];
					$company_static->name = $fields['company_name'];
					$company_static->name_alias = $fields['company_alias'];
					$company_static->email = $fields['company_email'];
					$company_static->tva_intra = $fields['tva_intra'];
					$company_static->client = $fields['company_client'];
					$company_static->fournisseur = $fields['company_fournisseur'];
					$company_static->status = $fields['company_status'];
					$company_static->code_client = $fields['company_client'];
					$company_static->code_compta_client = $fields['company_customer_code'];
					$company_static->code_fournisseur = $fields['company_customer_accounting_code'];
					$company_static->code_compta_fournisseur = $fields['company_supplier_accounting_code'];*/

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
					print '<td class="nowrap left">' . $fields['link'] . '</td>';

					// Invoice date
					print '<td class="left">' . dol_print_date($fields['datef'], 'day') . '</td>';

					// Payment date
					if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
						print '<td class="left">' . dol_print_date($fields['datep'], 'day') . '</td>';
					} else {
						print '<td></td>';
					}

					// Company name
					print '<td class="tdmaxoverflow150">';
					//print $company_static->getNomUrl(1);
					print $fields['company_link'];
					print '</td>';

					// Description
					print '<td class="left">';
					if ($fields['pid']) {
						$product_static->id = $fields['pid'];
						$product_static->ref = $fields['pref'];
						$product_static->type = $fields['dtype'];        // We force with the type of line to have type how line is registered
						print $product_static->getNomUrl(1);
						if (dol_string_nohtmltag($fields['descr'])) {
							print ' - ' . dol_trunc(dol_string_nohtmltag($fields['descr']), 24);
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
						print $text . ' ' . dol_trunc(dol_string_nohtmltag($fields['descr']), 24);

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
							$payment_static->ref = $fields['payment_ref'];
							print $payment_static->getNomUrl(2, '', '', 0) . ' ';
						}
						if (($type == 0 && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
							|| ($type == 1 && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')) {
							print $langs->trans("NA");
						} else {
							if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) {
								$ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
							}
							print price(price2num($fields['payment_amount'], 'MT'));
							if (isset($fields['payment_amount'])) {
								print ' (' . round($ratiopaymentinvoice * 100, 2) . '%)';
							}
						}
						print '</td>';
					}

					// Total collected
					print '<td class="nowrap right">';
					$temp_ht = (float) $fields['totalht'] * $ratiopaymentinvoice;
					print price(price2num($temp_ht, 'MT'), 1);
					print '</td>';

					// VAT
					print '<td class="nowrap right">';
					$temp_vat = $fields['vat'] * $ratiopaymentinvoice;
					print price(price2num($temp_vat, 'MT'), 1);
					//print price($fields['vat']);
					print '</td>';
					print '</tr>';

					//$subtot_coll_total_ht += $temp_ht;
					//$subtot_coll_vat      += $temp_vat;
					//$x_coll_sum           += $temp_vat;
				}
			}
		}
		// Total customers for this vat rate
		print '<tr class="liste_total">';
		print '<td colspan="4"></td>';
		print '<td class="right">' . $langs->trans("Total") . ':</td>';
		if ($modetax != 1) {
			print '<td class="nowrap right">&nbsp;</td>';
			print '<td class="right">&nbsp;</td>';
		}
		print '<td class="right"><span class="amount">' . price(price2num($subtot_coll_total_ht, 'MT')) . '</span></td>';
		print '<td class="nowrap right"><span class="amount">' . price(price2num($subtot_coll_vat, 'MT')) . '</span></td>';
		print '</tr>';
	}

	if (count($x_coll) == 0) {   // Show a total line if nothing shown
		print '<tr class="liste_total">';
		print '<td colspan="4"></td>';
		print '<td class="right">' . $langs->trans("Total") . ':</td>';
		if ($modetax != 1) {
			print '<td class="nowrap right">&nbsp;</td>';
			print '<td class="right">&nbsp;</td>';
		}
		print '<td class="right">' . price(price2num(0, 'MT')) . '</td>';
		print '<td class="nowrap right">' . price(price2num(0, 'MT')) . '</td>';
		print '</tr>';
	}

	// Blank line
	print '<tr><td colspan="' . ($span + 2) . '">&nbsp;</td></tr>';

	// Print table headers for this quadri - expenses
	print '<tr class="liste_titre liste_titre_topborder">';
	print '<td class="left">' . $elementsup . '</td>';
	print '<td class="left">' . $langs->trans("DateInvoice") . '</td>';
	if (getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'payment') {
		print '<td class="left">' . $langs->trans("DatePayment") . '</td>';
	} else {
		print '<td></td>';
	}
	print '<td class="left">' . $namesup . '</td>';
	print '<td class="left">' . $productsup . '</td>';
	if ($modetax != 1) {
		print '<td class="right">' . $amountsup . '</td>';
		print '<td class="right">' . $langs->trans("Payment") . ' (' . $langs->trans("PercentOfInvoice") . ')</td>';
	}
	print '<td class="right">' . $langs->trans("AmountHTVATRealPaid") . '</td>';
	print '<td class="right">' . $vatsup . '</td>';
	print '</tr>' . "\n";

	foreach (array_keys($x_paye) as $rate) {
		$subtot_paye_total_ht = 0;
		$subtot_paye_vat = 0;

		if (is_array($x_both[$rate]['paye']['detail'])) {  // @phpstan-ignore-line
			print "<tr>";
			print '<td class="tax_rate" colspan="' . ($span + 1) . '">';
			print $langs->trans('Rate') . ' : ' . vatrate($rate) . '%';
			print ' - <a class="reposition" href="' . DOL_URL_ROOT . '/compta/tva/quadri_detail.php?invoice_type=supplier';
			if ($invoice_type != 'supplier' || !GETPOSTISSET('vat_rate_show') || GETPOST('vat_rate_show') != $rate) {
				print '&amp;vat_rate_show=' . urlencode($rate);
			}
			print '&amp;date_startyear=' . urlencode($date_start_year) . '&amp;date_startmonth=' . urlencode($date_start_month) . '&amp;date_startday=' . urlencode($date_start_day) . '&amp;date_endyear=' . urlencode($date_end_year) . '&amp;date_endmonth=' . urlencode($date_end_month) . '&amp;date_endday=' . urlencode($date_end_day) . '">' . img_picto('', 'chevron-down', 'class="paddingrightonly"') . $langs->trans('VATReportShowByRateDetails') . '</a>';
			print '</td>';
			print '</tr>' . "\n";

			foreach ($x_both[$rate]['paye']['detail'] as $index => $fields) {
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

				// Payment
				$ratiopaymentinvoice = 1;
				if ($modetax != 1) {
					if (($type == 0 && getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'invoice')
						|| ($type == 1 && getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'invoice')) {
					} else {
						if (isset($fields['payment_amount']) && $fields['ftotal_ttc']) {
							$ratiopaymentinvoice = ($fields['payment_amount'] / (float) $fields['ftotal_ttc']);
						}
					}
				}

				// VAT paid
				$temp_ht = (float) $fields['totalht'] * $ratiopaymentinvoice;

				// VAT
				$temp_vat = $fields['vat'] * $ratiopaymentinvoice;

				$subtot_paye_total_ht += $temp_ht;
				$subtot_paye_vat += $temp_vat;
				$x_paye_sum += $temp_vat;
			}

			if ($invoice_type == 'supplier' && $vat_rate_show == $rate) {
				foreach ($x_both[$rate]['paye']['detail'] as $index => $fields) {
					/*$company_static->id = $fields['company_id'];
					$company_static->name = $fields['company_name'];
					$company_static->name_alias = $fields['company_alias'];
					$company_static->email = $fields['company_email'];
					$company_static->tva_intra = $fields['tva_intra'];
					$company_static->client = $fields['company_client'];
					$company_static->fournisseur = $fields['company_fournisseur'];
					$company_static->status = $fields['company_status'];
					$company_static->code_client = $fields['company_client'];
					$company_static->code_compta_client = $fields['company_customer_code'];
					$company_static->code_fournisseur = $fields['company_customer_accounting_code'];
					$company_static->code_compta_fournisseur = $fields['company_supplier_accounting_code'];*/

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
					print '<td class="nowrap left">' . $fields['link'] . '</td>';

					// Invoice date
					print '<td class="left">' . dol_print_date($fields['datef'], 'day') . '</td>';

					// Payment date
					if (getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'payment' || getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'payment') {
						print '<td class="left">' . dol_print_date($fields['datep'], 'day') . '</td>';
					} else {
						print '<td></td>';
					}

					// Company name
					print '<td class="tdmaxoverflow150">';
					//print $company_static->getNomUrl(1);
					print $fields['company_link'];
					print '</td>';

					// Description
					print '<td class="left">';
					if ($fields['pid']) {
						$product_static->id = $fields['pid'];
						$product_static->ref = $fields['pref'];
						$product_static->type = $fields['dtype'];        // We force with the type of line to have type how line is registered
						print $product_static->getNomUrl(1);
						if (dol_string_nohtmltag($fields['descr'])) {
							print ' - ' . dol_trunc(dol_string_nohtmltag($fields['descr']), 24);
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
						print $text . ' ' . dol_trunc(dol_string_nohtmltag($fields['descr']), 24);

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
							$ratiolineinvoice = ((float) $fields['dtotal_ttc'] / (float) $fields['ftotal_ttc']);
							//print ' ('.round($ratiolineinvoice*100,2).'%)';
						}
						print '</td>';
					}

					// Payment
					$ratiopaymentinvoice = 1;
					if ($modetax != 1) {
						print '<td class="nowrap right">';
						if ($fields['payment_amount'] && $fields['ftotal_ttc']) {
							$paymentfourn_static->id = $fields['payment_id'];
							$paymentfourn_static->ref = $fields['payment_ref'];
							print $paymentfourn_static->getNomUrl(2, '', '', 0) . ' ';
						}

						if (($type == 0 && getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'invoice')
							|| ($type == 1 && getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'invoice')) {
							print $langs->trans("NA");
						} else {
							if (isset($fields['payment_amount']) && $fields['ftotal_ttc']) {
								$ratiopaymentinvoice = ($fields['payment_amount'] / (float) $fields['ftotal_ttc']);
							}
							print price(price2num($fields['payment_amount'], 'MT'));
							if (isset($fields['payment_amount'])) {
								print ' (' . round($ratiopaymentinvoice * 100, 2) . '%)';
							}
						}
						print '</td>';
					}

					// VAT paid
					print '<td class="nowrap right">';
					$temp_ht = (float) $fields['totalht'] * $ratiopaymentinvoice;
					print price(price2num($temp_ht, 'MT'), 1);
					print '</td>';

					// VAT
					print '<td class="nowrap right">';
					$temp_vat = $fields['vat'] * $ratiopaymentinvoice;
					print price(price2num($temp_vat, 'MT'), 1);
					//print price($fields['vat']);
					print '</td>';
					print '</tr>';

					//$subtot_paye_total_ht += $temp_ht;
					//$subtot_paye_vat += $temp_vat;
					//$x_paye_sum += $temp_vat;
				}
			}
		}

		// Total suppliers for this vat rate
		print '<tr class="liste_total">';
		print '<td colspan="4"></td>';
		print '<td class="right">' . $langs->trans("Total") . ':</td>';
		if ($modetax != 1) {
			print '<td class="nowrap right">&nbsp;</td>';
			print '<td class="right">&nbsp;</td>';
		}
		print '<td class="right"><span class="amount">' . price(price2num($subtot_paye_total_ht, 'MT')) . '</span></td>';
		print '<td class="nowrap right"><span class="amount">' . price(price2num($subtot_paye_vat, 'MT')) . '</span></td>';
		print '</tr>';
	}

	if (count($x_paye) == 0) {  // Show a total line if nothing shown
		print '<tr class="liste_total">';
		print '<td colspan="4"></td>';
		print '<td class="right">' . $langs->trans("Total") . ':</td>';
		if ($modetax != 1) {
			print '<td class="nowrap right">&nbsp;</td>';
			print '<td class="right">&nbsp;</td>';
		}
		print '<td class="right"><span class="amount">' . price(price2num(0, 'MT')) . '</span></td>';
		print '<td class="nowrap right"><span class="amount">' . price(price2num(0, 'MT')) . '</span></td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	// Total to pay
	print '<br><br>';
	print '<table class="noborder centpercent">';
	$diff = $x_coll_sum - $x_paye_sum;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="' . $span . '">' . $langs->trans("TotalToPay") . ($q ? ', ' . $langs->trans("Quadri") . ' ' . $q : '') . '</td>';
	print '<td class="liste_total nowrap right"><b>' . price(price2num($diff, 'MT')) . "</b></td>\n";
	print "</tr>\n";

	$i++;
}
print '</table>';

llxFooter();
$db->close();
