<?php
/* Copyright (C) 2011-2014	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014	    Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2025  Frédéric France         <frederic.france@free.fr>
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
 *	    \file       htdocs/compta/localtax/clients.php
 *      \ingroup    tax
 *		\brief      Third parties localtax report
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

// Load translation files required by the page
$langs->loadLangs(array("other", "compta", "banks", "bills", "companies", "product", "trips", "admin"));

include DOL_DOCUMENT_ROOT.'/compta/tva/initdatesforvat.inc.php';
/**
 * @var	int	$date_start
 * @var int $date_end
 * @var int $date_start_month
 * @var int $date_start_year
 * @var int $date_start_day
 * @var int $date_end_month
 * @var int $date_end_year
 * @var int $date_end_day
 * @var int $year_current
 */
'
@phan-var-force int $date_start
@phan-var-force int $date_end
@phan-var-force int $date_start_month
@phan-var-force int $date_start_year
@phan-var-force int $date_start_day
@phan-var-force int $date_end_month
@phan-var-force int $date_end_year
@phan-var-force int $date_end_day
@phan-var-force int $year_current
';

$local = GETPOSTINT('localTaxType');

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

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(['customerlocaltaxlist']);

$result = restrictedArea($user, 'tax', '', '', 'charges');

if (empty($local)) {
	accessforbidden('Parameter localTaxType is missing');
}

$builddate = dol_now();
$calc = 0;
$calcmode = "Unknown";
$find = '';
$replace = '';
$period = '';


/*
 * View
 */

$form = new Form($db);
$company_static = new Societe($db);

$morequerystring = '';
$listofparams = array('date_startmonth', 'date_startyear', 'date_startday', 'date_endmonth', 'date_endyear', 'date_endday');
foreach ($listofparams as $param) {
	if (GETPOST($param) != '') {
		$morequerystring .= ($morequerystring ? '&' : '').$param.'='.GETPOST($param);
	}
}

$name = $langs->transcountry($local == 1 ? "LT1ReportByCustomers" : "LT2ReportByCustomers", $mysoc->country_code);

llxHeader('', $name, '', '', 0, 0, '', '', $morequerystring);


$fsearch = '<!-- hidden fields for form -->';
$fsearch .= '<input type="hidden" name="token" value="'.newToken().'">';
$fsearch .= '<input type="hidden" name="modetax" value="'.$modetax.'">';
$fsearch .= '<input type="hidden" name="localTaxType" value="'.$local.'">';
$fsearch .= $langs->trans("SalesTurnoverMinimum").': ';
$fsearch .= '<input type="text" name="min" id="min" value="'.$min.'" class="width75 right">';

// Show report header
$calc = getDolGlobalString('MAIN_INFO_LOCALTAX_CALC').$local;
$description = '';
if ($calc == 0 || $calc == 1) {	// Calculate on invoice for goods and services
	$calcmode = $calc == 0 ? $langs->trans("CalcModeLT".$local) : $langs->trans("CalcModeLT".$local."Rec");
	$calcmode .= ' <span class="opacitymedium">('.$langs->trans("TaxModuleSetupToModifyRulesLT", DOL_URL_ROOT.'/admin/company.php').')</span>';
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	if (isModEnabled('comptabilite')) {
		$description .= '<br>'.$langs->trans("WarningDepositsNotIncluded");
	}
	$description .= $fsearch;
	$description .= ' <span class="opacitymedium">('.$langs->trans("TaxModuleSetupToModifyRulesLT", DOL_URL_ROOT.'/admin/company.php').')</span>';

	$elementcust = $langs->trans("CustomersInvoices");
	$productcust = $langs->trans("Description");
	$amountcust = $langs->trans("AmountHT");
	$elementsup = $langs->trans("SuppliersInvoices");
	$productsup = $langs->trans("Description");
	$amountsup = $langs->trans("AmountHT");
}
if ($calc == 2) { 	// Invoice for goods, payment for services
	$calcmode = $langs->trans("CalcModeLT2Debt");
	$calcmode .= ' <span class="opacitymedium">('.$langs->trans("TaxModuleSetupToModifyRulesLT", DOL_URL_ROOT.'/admin/company.php').')</span>';
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	if (isModEnabled('comptabilite')) {
		$description .= '<br>'.$langs->trans("WarningDepositsNotIncluded");
	}
	$description .= $fsearch;
	$description .= '<span class="opacitymedium">('.$langs->trans("TaxModuleSetupToModifyRulesLT", DOL_URL_ROOT.'/admin/company.php').')</span>';

	$elementcust = $langs->trans("CustomersInvoices");
	$productcust = $langs->trans("Description");
	$amountcust = $langs->trans("AmountHT");
	$elementsup = $langs->trans("SuppliersInvoices");
	$productsup = $langs->trans("Description");
	$amountsup = $langs->trans("AmountHT");
}
// Set period
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);

$builddate = dol_now();

$periodlink = '';
$exportlink = '';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

$vatcust = $langs->transcountry($local == 1 ? "LT1" : "LT2", $mysoc->country_code);
$vatsup = $langs->transcountry($local == 1 ? "LT1" : "LT2", $mysoc->country_code);

// VAT Received
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

$x_coll_sum = 0;  // Initialize value
$x_paye_sum = 0;  // Initialize value

// IRPF that the customer has retained me
if ($calc == 0 || $calc == 2) {
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Num").'</td>';
	print '<td>'.$langs->trans("Customer").'</td>';
	print '<td>'.$langs->trans("VATIntraShort").'</td>';
	print '<td class="right">'.$langs->trans("TotalHT").'</td>';
	print '<td class="right">'.$vatcust.'</td>';
	print "</tr>\n";

	$coll_list = tax_by_thirdparty('localtax'.$local, $db, 0, $date_start, $date_end, $modetax, 'sell');

	$action = "tvaclient";
	$object = &$coll_list;
	$parameters["mode"] = $modetax;
	$parameters["start"] = $date_start;
	$parameters["end"] = $date_end;
	$parameters["direction"] = 'sell';
	$parameters["type"] = 'localtax'.$local;

	// Initialize a technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
	$hookmanager->initHooks(array('externalbalance'));
	$reshook = $hookmanager->executeHooks('addVatLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

	if (is_array($coll_list)) {
		$total = 0;
		$totalamount = 0;
		$i = 1;
		foreach ($coll_list as $coll_key => $coll_obj) {
			if (($min == 0 || ($min > 0 && $coll_obj['totalht'] > $min)) && ($local == 1 ? $coll_obj['localtax1'] : $coll_obj['localtax2']) != 0) {
				$company_static->fetch($coll_key);

				$intra = str_replace($find, $replace, $company_static->tva_intra);

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$i."</td>";
				print '<td class="nowrap">'.$company_static->getNomUrl(1).'</td>';
				$find = array(' ', '.');
				$replace = array('', '');
				print '<td class="nowrap">'.$intra.'</td>';
				print '<td class="nowrap right">'.price($coll_obj['totalht']).'</td>';
				print '<td class="nowrap right">'.price($local == 1 ? $coll_obj['localtax1'] : $coll_obj['localtax2']).'</td>';
				$totalamount += $coll_obj['totalht'];
				$total += ($local == 1 ? $coll_obj['localtax1'] : $coll_obj['localtax2']);
				print "</tr>\n";
				$i++;
			}
		}
		$x_coll_sum = $total;

		print '<tr class="liste_total"><td class="right" colspan="3">'.$langs->trans("Total").':</td>';
		print '<td class="nowrap right">'.price($totalamount).'</td>';
		print '<td class="nowrap right">'.price($total).'</td>';
		print '</tr>';
	} else {
		$langs->load("errors");
		if ($coll_list == -1) {
			print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
		} elseif ($coll_list == -2) {
			print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
		} else {
			print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
		}
	}
}

// IRPF I retained my supplier
if ($calc == 0 || $calc == 1) {
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Num")."</td>";
	print '<td>'.$langs->trans("Supplier")."</td>";
	print '<td>'.$langs->trans("VATIntraShort").'</td>';
	print '<td class="right">'.$langs->trans("TotalHT").'</td>';
	print '<td class="right">'.$vatsup.'</td>';
	print "</tr>\n";

	$company_static = new Societe($db);

	$coll_list = tax_by_thirdparty('localtax'.$local, $db, 0, $date_start, $date_end, $modetax, 'buy');
	$parameters["direction"] = 'buy';
	$parameters["type"] = 'localtax'.$local;

	$reshook = $hookmanager->executeHooks('addVatLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if (is_array($coll_list)) {
		$total = 0;
		$totalamount = 0;
		$i = 1;
		foreach ($coll_list as $coll_key => $coll_obj) {
			if (($min == 0 || ($min > 0 && $coll_obj['totalht'] > $min)) && ($local == 1 ? $coll_obj['localtax1'] : $coll_obj['localtax2']) != 0) {
				$company_static->fetch($coll_key);

				$intra = str_replace($find, $replace, $company_static->tva_intra);

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$i."</td>";
				print '<td class="nowrap">'.$company_static->getNomUrl(1).'</td>';
				$find = array(' ', '.');
				$replace = array('', '');
				print '<td class="nowrap">'.$intra."</td>";
				print '<td class="nowrap right">'.price($coll_obj['totalht']).'</td>';
				print '<td class="nowrap right">'.price($local == 1 ? $coll_obj['localtax1'] : $coll_obj['localtax2']).'</td>';
				$totalamount += $coll_obj['totalht'];
				$total += ($local == 1 ? $coll_obj['localtax1'] : $coll_obj['localtax2']);
				print "</tr>\n";
				$i++;
			}
		}
		$x_paye_sum = $total;

		print '<tr class="liste_total"><td class="right" colspan="3">'.$langs->trans("Total").':</td>';
		print '<td class="nowrap right">'.price($totalamount).'</td>';
		print '<td class="nowrap right">'.price($total).'</td>';
		print '</tr>';
	} else {
		$langs->load("errors");
		if ($coll_list == -1) {
			print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
		} elseif ($coll_list == -2) {
			print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
		} else {
			print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
		}
	}
}

if ($calc == 0) {
	// Total to pay
	print '<tr><td colspan="5"></td></tr>';

	$diff = $x_coll_sum - $x_paye_sum;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="4">'.$langs->trans("TotalToPay").'</td>';
	print '<td class="liste_total nowrap right"><b>'.price(price2num($diff, 'MT'))."</b></td>\n";
	print "</tr>\n";
}

print '</table>';
print '</div>';


// End of page
llxFooter();
$db->close();
