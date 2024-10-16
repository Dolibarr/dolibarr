<?php
/* Copyright (C) 2018       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *		\file       htdocs/compta/stats/byratecountry.php
 *		\brief      VAT by rate
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
$langs->loadLangs(array("other", "compta", "banks", "bills", "companies", "product", "trips", "admin", "accountancy"));

$modecompta = (GETPOST('modecompta', 'alpha') ? GETPOST('modecompta', 'alpha') : $conf->global->ACCOUNTING_MODE);

// Date range
$year = GETPOSTINT("year");
$month = GETPOSTINT("month");
if (empty($year)) {
	$year_current = dol_print_date(dol_now(), '%Y');
	$month_current = dol_print_date(dol_now(), '%m');
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = dol_print_date(dol_now(), '%m');
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"), 'tzserver');	// We use timezone of server so report is same from everywhere
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"), 'tzserver');		// We use timezone of server so report is same from everywhere

// Quarter
$q = '';
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOSTINT("q");
	if (empty($q)) {
		// We define date_start and date_end
		$month_start = GETPOST("month") ? GETPOST("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$year_end = $year_start;
		$month_end = $month_start;
		if (!GETPOST("month")) {	// If month not forced
			if (!GETPOST('year') && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
			} else {
				$year_end++;
			}
		}
		$date_start = dol_get_first_day($year_start, $month_start, false);
		$date_end = dol_get_last_day($year_end, $month_end, false);
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

// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps = dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];

$tmp_date_end = dol_time_plus_duree($date_start, 1, 'y') - 1;
if ($tmp_date_end < $date_end || $date_end < $date_start) {
	$date_end = $tmp_date_end;
}

$min = price2num(GETPOST("min", "alpha"));
if (empty($min)) {
	$min = 0;
}

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = !getDolGlobalString('TAX_MODE') ? 0 : $conf->global->TAX_MODE;
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
foreach ($listofparams as $param) {
	if (GETPOST($param) != '') {
		$morequerystring .= ($morequerystring ? '&' : '').$param.'='.GETPOST($param);
	}
}

llxHeader('', $langs->trans("TurnoverReport"), '', '', 0, 0, '', '', $morequerystring);

$exportlink = "";
$namelink = "";
//print load_fiche_titre($langs->trans("VAT"),"");

//$fsearch.='<br>';
$fsearch = '';
$fsearch .= '  <input type="hidden" name="year" value="'.$year.'">';
$fsearch .= '  <input type="hidden" name="modetax" value="'.$modetax.'">';
//$fsearch.='  '.$langs->trans("SalesTurnoverMinimum").': ';
//$fsearch.='  <input type="text" name="min" value="'.$min.'">';


// Show report header
$name = $langs->trans("xxx");
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
$calcmode .= '<br>('.$langs->trans("TaxModuleSetupToModifyRules", DOL_URL_ROOT.'/admin/taxes.php').')';
// Set period
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
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
$description = $fsearch;
$builddate = dol_now();

if (isModEnabled('comptabilite')) {
	$description .= '<br>'.$langs->trans("ThisIsAnEstimatedValue");
}
if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice') {
	$description .= '<br>'.$langs->trans("RulesVATDueProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment') {
	$description .= '<br>'.$langs->trans("RulesVATInProducts");
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
// Customers invoices
$elementcust = $langs->trans("CustomersInvoices");
$productcust = $langs->trans("ProductOrService");
$amountcust = $langs->trans("AmountHT");

// Suppliers invoices
$elementsup = $langs->trans("SuppliersInvoices");
$productsup = $productcust;
$amountsup = $amountcust;

// TODO Report from bookkeeping not yet available, so we switch on report on business events
if ($modecompta == "BOOKKEEPING") {
	$modecompta = "CREANCES-DETTES";
}
if ($modecompta == "BOOKKEEPINGCOLLECTED") {
	$modecompta = "RECETTES-DEPENSES";
}

// Show report header
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("Turnover").', '.$langs->trans("ByVatRate");
	$calcmode = $langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';

	$description .= '<br>'.$langs->trans("RulesCADue");
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}
	if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("SupplierDepositsAreNotIncluded");
	}

	$builddate = dol_now();
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("TurnoverCollected").', '.$langs->trans("ByVatRate");
	$calcmode = $langs->trans("CalcModePayment");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';

	$description .= $langs->trans("RulesCAIn");
	$description .= $langs->trans("DepositsAreIncluded");

	$builddate = dol_now();
} elseif ($modecompta == "BOOKKEEPING") {
} elseif ($modecompta == "BOOKKEEPINGCOLLECTED") {
}
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0);
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
if ($date_end == dol_time_plus_duree($date_start, 1, 'y') - 1) {
	$periodlink = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start - 1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start + 1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
} else {
	$periodlink = '';
}

$description .= '  <input type="hidden" name="modecompta" value="'.$modecompta.'">';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
}


if ($modecompta == 'CREANCES-DETTES') {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td width="6%" class="right">'.$langs->trans("TurnoverbyVatrate").'</td>';
	print '<td class="left">'.$langs->trans("ProductOrService").'</td>';
	print '<td class="left">'.$langs->trans("Country").'</td>';
	$i = 0;
	while ($i < 12) {
		$j = $i + (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START);
		if ($j > 12) {
			$j -= 12;
		}
		print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT)).'</td>';
		$i++;
	}
	print '<td width="60" class="right"><b>'.$langs->trans("TotalHT").'</b></td></tr>';

	// Sales invoices
	$sql = "SELECT fd.tva_tx AS vatrate,";
	$sql .= " fd.product_type AS product_type,";
	$sql .= " cc.code, cc.label AS country,";
	for ($i = 1; $i <= 12; $i++) {
		$sql .= " SUM(".$db->ifsql("MONTH(f.datef)=".$i, "fd.total_ht", "0").") AS month".str_pad((string) $i, 2, "0", STR_PAD_LEFT).",";
	}
	$sql .= "  SUM(fd.total_ht) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
	$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = f.fk_soc";
	$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = soc.fk_pays";
	$sql .= " WHERE f.datef >= '".$db->idate($date_start)."'";
	$sql .= "  AND f.datef <= '".$db->idate($date_end)."'";
	$sql .= " AND f.fk_statut in (1,2)";
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$sql .= " AND f.type IN (0,1,2,5)";
	} else {
		$sql .= " AND f.type IN (0,1,2,3,5)";
	}
	$sql .= " AND f.entity IN (".getEntity('invoice', 0).")";
	$sql .= " GROUP BY fd.tva_tx,fd.product_type, cc.label, cc.code ";
	$sql .= " ORDER BY country, product_type, vatrate";

	dol_syslog("htdocs/compta/tva/index.php", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$totalpermonth = array();
		while ($obj = $db->fetch_object($resql)) {
			print '<tr class="oddeven"><td class="right">'.vatrate($obj->vatrate).'</td>';
			if ($obj->product_type == 0) {
				print '<td class="left">'.$langs->trans("Product").'</td>';
			} else {
				print '<td class="left">'.$langs->trans("Service").'</td>';
			}
			// Country
			print '<td>';
			print $langs->trans("Country".$obj->code) != "Country".$obj->code ? $langs->trans("Country".$obj->code) : $obj->country;
			print '</td>';
			for ($i = 0; $i < 12; $i++) {
				$j = $i + (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START);
				if ($j > 12) {
					$j -= 12;
				}
				$monthj = 'month'.str_pad((string) $j, 2, '0', STR_PAD_LEFT);
				print '<td class="right" width="6%">'.price($obj->$monthj).'</td>';
				$totalpermonth[$j] = (empty($totalpermonth[$j]) ? 0 : $totalpermonth[$j]) + $obj->$monthj;
			}
			print '<td class="right" width="6%"><b>'.price($obj->total).'</b></td>';
			$totalpermonth['total'] = (empty($totalpermonth['total']) ? 0 : $totalpermonth['total']) + $obj->total;
			print '</tr>';
		}
		$db->free($resql);

		// Total
		print '<tr class="liste_total"><td class="right"></td>';
		print '<td class="left"></td>';
		print '<td></td>';
		for ($i = 0; $i < 12; $i++) {
			$j = $i + (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START);
			if ($j > 12) {
				$j -= 12;
			}
			$monthj = 'month'.str_pad((string) $j, 2, '0', STR_PAD_LEFT);
			print '<td class="right" width="6%">'.price((empty($totalpermonth[$j]) ? 0 : $totalpermonth[$j])).'</td>';
		}
		print '<td class="right" width="6%"><b>'.price((empty($totalpermonth['total']) ? 0 : $totalpermonth['total'])).'</b></td>';
		print '</tr>';
	} else {
		print $db->lasterror(); // Show last sql error
	}

	print '<tr class="liste_titre"><td width="6%" class="right">'.$langs->trans("PurchasebyVatrate").'</td>';
	print '<td class="left">'.$langs->trans("ProductOrService").'</td>';
	print '<td class="left">'.$langs->trans("Country").'</td>';
	$i = 0;
	while ($i < 12) {
		$j = $i + (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START);
		if ($j > 12) {
			$j -= 12;
		}
		print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT)).'</td>';
		$i++;
	}
	print '<td width="60" class="right"><b>'.$langs->trans("TotalHT").'</b></td></tr>';

	// Purchase invoices
	$sql2 = "SELECT ffd.tva_tx AS vatrate,";
	$sql2 .= " ffd.product_type AS product_type,";
	$sql2 .= " cc.code, cc.label AS country,";
	for ($i = 1; $i <= 12; $i++) {
		$sql2 .= " SUM(".$db->ifsql("MONTH(ff.datef)=".$i, "ffd.total_ht", "0").") AS month".str_pad((string) $i, 2, "0", STR_PAD_LEFT).",";
	}
	$sql2 .= "  SUM(ffd.total_ht) as total";
	$sql2 .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as ffd";
	$sql2 .= "  INNER JOIN ".MAIN_DB_PREFIX."facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
	$sql2 .= "  INNER JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = ff.fk_soc";
	$sql2 .= "  LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = soc.fk_pays";
	$sql2 .= " WHERE ff.datef >= '".$db->idate($date_start)."'";
	$sql2 .= "  AND ff.datef <= '".$db->idate($date_end)."'";
	$sql .= " AND ff.fk_statut in (1,2)";
	if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$sql .= " AND ff.type IN (0,1,2,5)";
	} else {
		$sql .= " AND ff.type IN (0,1,2,3,5)";
	}
	$sql2 .= " AND ff.entity IN (".getEntity("facture_fourn", 0).")";
	$sql2 .= " GROUP BY ffd.tva_tx, ffd.product_type, cc.label, cc.code ";
	$sql2 .= " ORDER BY country, product_type, vatrate";

	//print $sql2;
	dol_syslog("htdocs/compta/tva/index.php", LOG_DEBUG);
	$resql2 = $db->query($sql2);
	if ($resql2) {
		$num = $db->num_rows($resql2);
		$totalpermonth = array();
		while ($obj = $db->fetch_object($resql2)) {
			print '<tr class="oddeven"><td class="right">'.vatrate($obj->vatrate).'</td>';
			if ($obj->product_type == 0) {
				print '<td class="left">'.$langs->trans("Product").'</td>';
			} else {
				print '<td class="left">'.$langs->trans("Service").'</td>';
			}
			print '<td>';
			print $langs->trans("Country".$obj->code) != "Country".$obj->code ? $langs->trans("Country".$obj->code) : $obj->country;
			print '</td>';
			for ($i = 0; $i < 12; $i++) {
				$j = $i + (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START);
				if ($j > 12) {
					$j -= 12;
				}
				$monthj = 'month'.str_pad((string) $j, 2, '0', STR_PAD_LEFT);
				print '<td class="right" width="6%">'.price($obj->$monthj).'</td>';
				$totalpermonth[$j] = (empty($totalpermonth[$j]) ? 0 : $totalpermonth[$j]) + $obj->$monthj;
			}
			print '<td class="right" width="6%"><b>'.price($obj->total).'</b></td>';
			$totalpermonth['total'] = (empty($totalpermonth['total']) ? 0 : $totalpermonth['total']) + $obj->total;
			print '</tr>';
		}
		$db->free($resql2);

		// Total
		print '<tr class="liste_total"><td class="right"></td>';
		print '<td class="left"></td>';
		print '<td></td>';
		for ($i = 0; $i < 12; $i++) {
			$j = $i + (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 1 : $conf->global->SOCIETE_FISCAL_MONTH_START);
			if ($j > 12) {
				$j -= 12;
			}
			$monthj = 'month'.str_pad((string) $j, 2, '0', STR_PAD_LEFT);
			print '<td class="right" width="6%">'.price(empty($totalpermonth[$j]) ? 0 : $totalpermonth[$j]).'</td>';
		}
		print '<td class="right" width="6%"><b>'.price(empty($totalpermonth['total']) ? 0 : $totalpermonth['total']).'</b></td>';
		print '</tr>';
	} else {
		print $db->lasterror(); // Show last sql error
	}
	print "</table>\n";
} else {
	// $modecompta != 'CREANCES-DETTES'
	// "Calculation of part of each product for accountancy in this mode is not possible. When a partial payment (for example 5 euros) is done on an
	// invoice with 2 product (product A for 10 euros and product B for 20 euros), what is part of paiment for product A and part of paiment for product B ?
	// Because there is no way to know this, this report is not relevant.
	print '<br>'.$langs->trans("TurnoverPerSaleTaxRateInCommitmentAccountingNotRelevant").'<br>';
}

// End of page
llxFooter();
$db->close();
