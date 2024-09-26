<?php
/* Copyright (C) 2011-2014	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014	    Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

// Load translation files required by the page
$langs->loadLangs(array("other", "compta", "banks", "bills", "companies", "product", "trips", "admin"));

$local = GETPOSTINT('localTaxType');

// Date range
$year = GETPOSTINT("year");
if (empty($year)) {
	$year_current = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"));
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOST("q");
	if (empty($q)) {
		if (GETPOST("month")) {
			$date_start = dol_get_first_day($year_start, GETPOST("month"), false);
			$date_end = dol_get_last_day($year_start, GETPOSTINT("month"), false);
		} else {
			$date_start = dol_get_first_day($year_start, !getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START, false);
			if (!getDolGlobalString('MAIN_INFO_VAT_RETURN') || getDolGlobalInt('MAIN_INFO_VAT_RETURN') == 2) {
				$date_end = dol_time_plus_duree($date_start, 3, 'm') - 1;
			} elseif (getDolGlobalInt('MAIN_INFO_VAT_RETURN') == 3) {
				$date_end = dol_time_plus_duree($date_start, 1, 'y') - 1;
			} elseif (getDolGlobalInt('MAIN_INFO_VAT_RETURN') == 1) {
				$date_end = dol_time_plus_duree($date_start, 1, 'm') - 1;
			}
		}
	} else {
		if ($q == 1) {
			$date_start = dol_get_first_day($year_start, 1, false);
			$date_end = dol_get_last_day($year_start, 3, false);
		}
		if ($q == 2) {
			$date_start = dol_get_first_day($year_start, 4, false);
			$date_end = dol_get_last_day($year_start, 6, false);
		}
		if ($q == 3) {
			$date_start = dol_get_first_day($year_start, 7, false);
			$date_end = dol_get_last_day($year_start, 9, false);
		}
		if ($q == 4) {
			$date_start = dol_get_first_day($year_start, 10, false);
			$date_end = dol_get_last_day($year_start, 12, false);
		}
	}
}

$min = price2num(GETPOST("min", "alpha"));
if (empty($min)) {
	$min = 0;
}

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = getDolGlobalString('TAX_MODE');
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
	exit;
}

$calc = 0;
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

llxHeader('', '', '', '', 0, 0, '', '', $morequerystring);


$name = $langs->transcountry($local == 1 ? "LT1ReportByCustomers" : "LT2ReportByCustomers", $mysoc->country_code);

$fsearch = '<!-- hidden fields for form -->';
$fsearch .= '<input type="hidden" name="token" value="'.newToken().'">';
$fsearch .= '<input type="hidden" name="modetax" value="'.$modetax.'">';
$fsearch .= '<input type="hidden" name="localTaxType" value="'.$local.'">';
$fsearch .= $langs->trans("SalesTurnoverMinimum").': ';
$fsearch .= '<input type="text" name="min" id="min" value="'.$min.'" size="6">';

$calc = getDolGlobalString('MAIN_INFO_LOCALTAX_CALC').$local;
// Affiche en-tete du rapport
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
	$builddate = dol_now();

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
	$builddate = dol_now();

	$elementcust = $langs->trans("CustomersInvoices");
	$productcust = $langs->trans("Description");
	$amountcust = $langs->trans("AmountHT");
	$elementsup = $langs->trans("SuppliersInvoices");
	$productsup = $langs->trans("Description");
	$amountsup = $langs->trans("AmountHT");
}

$periodlink = '';
$exportlink = '';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

$vatcust = $langs->transcountry($local == 1 ? "LT1" : "LT2", $mysoc->country_code);
$vatsup = $langs->transcountry($local == 1 ? "LT1" : "LT2", $mysoc->country_code);

print '<div class="div-table-responsive">';
print '<table class="liste noborder centpercent">';

// IRPF that the customer has retained me
if ($calc == 0 || $calc == 2) {
	print '<tr class="liste_titre">';
	print '<td class="left">'.$langs->trans("Num").'</td>';
	print '<td class="left">'.$langs->trans("Customer").'</td>';
	print '<td>'.$langs->transcountry("ProfId1", $mysoc->country_code).'</td>';
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
		foreach ($coll_list as $coll) {
			if (($min == 0 || ($min > 0 && $coll->amount > $min)) && ($local == 1 ? $coll->localtax1 : $coll->localtax2) != 0) {
				$intra = str_replace($find, $replace, $coll->tva_intra);
				if (empty($intra)) {
					if ($coll->assuj == '1') {
						$intra = $langs->trans('Unknown');
					} else {
						$intra = '';
					}
				}
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$i."</td>";
				$company_static->id = $coll->socid;
				$company_static->name = $coll->name;
				print '<td class="nowrap">'.$company_static->getNomUrl(1).'</td>';
				$find = array(' ', '.');
				$replace = array('', '');
				print '<td class="nowrap">'.$intra.'</td>';
				print '<td class="nowrap right">'.price($coll->amount).'</td>';
				print '<td class="nowrap right">'.price($local == 1 ? $coll->localtax1 : $coll->localtax2).'</td>';
				$totalamount += $coll->amount;
				$total += ($local == 1 ? $coll->localtax1 : $coll->localtax2);
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
	print '<td class="left">'.$langs->trans("Num")."</td>";
	print '<td class="left">'.$langs->trans("Supplier")."</td>";
	print '<td>'.$langs->transcountry("ProfId1", $mysoc->country_code).'</td>';
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
		foreach ($coll_list as $coll) {
			if (($min == 0 || ($min > 0 && $coll->amount > $min)) && ($local == 1 ? $coll->localtax1 : $coll->localtax2) != 0) {
				$intra = str_replace($find, $replace, $coll->tva_intra);
				if (empty($intra)) {
					if ($coll->assuj == '1') {
						$intra = $langs->trans('Unknown');
					} else {
						$intra = '';
					}
				}
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$i."</td>";
				$company_static->id = $coll->socid;
				$company_static->name = $coll->name;
				print '<td class="nowrap">'.$company_static->getNomUrl(1).'</td>';
				$find = array(' ', '.');
				$replace = array('', '');
				print '<td class="nowrap">'.$intra."</td>";
				print '<td class="nowrap right">'.price($coll->amount).'</td>';
				print '<td class="nowrap right">'.price($local == 1 ? $coll->localtax1 : $coll->localtax2).'</td>';
				$totalamount += $coll->amount;
				$total += ($local == 1 ? $coll->localtax1 : $coll->localtax2);
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
	print '<td class="liste_total" colspan="4">'.$langs->trans("TotalToPay").($q ? ', '.$langs->trans("Quadri").' '.$q : '').'</td>';
	print '<td class="liste_total nowrap right"><b>'.price(price2num($diff, 'MT'))."</b></td>\n";
	print "</tr>\n";
}

print '</table>';
print '</div>';


// End of page
llxFooter();
$db->close();
