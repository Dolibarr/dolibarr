<?php
/* Copyright (C) 2025 Florian Hoedl <florian@hoedl.co>
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
 * \file       htdocs/custom/earechnungat/report.php
 * \ingroup    earechnungat
 * \brief      Main E/A-Rechnung report page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once __DIR__ . '/lib/earechnungat.lib.php';
require_once __DIR__ . '/class/ear_calculator.class.php';

// Load translation files
$langs->loadLangs(array("earechnungat@earechnungat", "compta", "bills", "companies"));

// Security check
if (!$user->hasRight('earechnungat', 'report', 'read')) {
	accessforbidden();
}

// Parameters
$year = GETPOSTINT('year');
if (empty($year)) {
	$year = (int) date('Y');
}
$period = GETPOST('period', 'alpha');
$action = GETPOST('action', 'aZ09');

// Calculate date range
$dateRange = earechnungatGetDateRange($year, $period);
$dateStart = $dateRange['start'];
$dateEnd = $dateRange['end'];

// Build report
$calculator = new EARCalculator($db);
$report = $calculator->getFullReport($dateStart, $dateEnd);

// CSV Export
if ($action === 'export_csv') {
	if (!$user->hasRight('earechnungat', 'report', 'export')) {
		accessforbidden();
	}

	$filename = dol_sanitizeFileName('EA-Rechnung_' . $year . ($period ? '_' . $period : '') . '.csv');

	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	// BOM for Excel UTF-8
	echo "\xEF\xBB\xBF";
	echo $calculator->generateCSV($report, $year);
	exit;
}

/*
 * View
 */

$title = $langs->trans('EARechnungATTitle');
$help_url = '';

llxHeader('', $title, $help_url);

// Period label
$periodLabel = $langs->trans('FullYear') . ' ' . $year;
if ($period && preg_match('/^Q([1-4])$/', $period)) {
	$periodLabel = $langs->trans($period) . ' ' . $year;
} elseif ($period && is_numeric($period)) {
	$periodLabel = dol_print_date(dol_mktime(0, 0, 0, (int) $period, 1, $year), '%B %Y');
}

print load_fiche_titre($title, '', 'fa-file-invoice');

// Filter form
print '<form method="GET" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '">';
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<table class="noborder centpercent">';

// Year selector
print '<tr class="oddeven">';
print '<td>' . $langs->trans('SelectYear') . '</td>';
print '<td><select name="year" class="flat minwidth100">';
$currentYear = (int) date('Y');
for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
	print '<option value="' . $y . '"' . ($y == $year ? ' selected' : '') . '>' . $y . '</option>';
}
print '</select></td>';
print '</tr>';

// Period selector
print '<tr class="oddeven">';
print '<td>' . $langs->trans('SelectPeriod') . '</td>';
print '<td><select name="period" class="flat minwidth150">';
print '<option value=""' . (empty($period) ? ' selected' : '') . '>' . $langs->trans('FullYear') . '</option>';
print '<option value="Q1"' . ($period === 'Q1' ? ' selected' : '') . '>' . $langs->trans('Q1') . '</option>';
print '<option value="Q2"' . ($period === 'Q2' ? ' selected' : '') . '>' . $langs->trans('Q2') . '</option>';
print '<option value="Q3"' . ($period === 'Q3' ? ' selected' : '') . '>' . $langs->trans('Q3') . '</option>';
print '<option value="Q4"' . ($period === 'Q4' ? ' selected' : '') . '>' . $langs->trans('Q4') . '</option>';
for ($m = 1; $m <= 12; $m++) {
	$mStr = sprintf('%02d', $m);
	$mLabel = dol_print_date(dol_mktime(0, 0, 0, $m, 1, $year), '%B');
	print '<option value="' . $mStr . '"' . ($period === $mStr ? ' selected' : '') . '>' . $mLabel . '</option>';
}
print '</select></td>';
print '</tr>';

print '</table>';
print '</div>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans('Refresh') . '">';
print ' &nbsp; ';
print '<a class="button" href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?action=export_csv&year=' . $year . '&period=' . urlencode($period) . '">' . $langs->trans('ExportCSV') . '</a>';
print '</div>';
print '</form>';

print '<br>';

// Company info header
$taxModeLabel = ($report['tax_mode'] === 'payment') ? $langs->trans('TaxModePayment') : $langs->trans('TaxModeInvoice');

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

// Title row
print '<tr class="liste_titre">';
print '<td colspan="2" class="center"><strong>' . $langs->trans('EARechnungATTitle') . ' - ' . $periodLabel . '</strong></td>';
print '</tr>';
print '<tr class="liste_titre">';
print '<td colspan="2" class="center">' . $mysoc->name . ' | ' . $mysoc->tva_intra . ' | ' . $taxModeLabel . '</td>';
print '</tr>';

// ========== INCOME SECTION ==========
print '<tr class="liste_titre">';
print '<td colspan="2"><strong>' . $langs->trans('Income') . '</strong></td>';
print '</tr>';

// Income by VAT rate
$rateLabels = array('20' => 'CustomerIncome20', '10' => 'CustomerIncome10', '13' => 'CustomerIncome13', '0' => 'CustomerIncome0');
foreach ($report['income']['by_rate'] as $rate => $data) {
	$rateKey = (string) $rate;
	$label = isset($rateLabels[$rateKey]) ? $langs->trans($rateLabels[$rateKey]) : sprintf($langs->trans('CustomerIncome20'), $rate);
	// Fallback for unknown rates
	if (!isset($rateLabels[$rateKey])) {
		$label = 'Erloese ' . $rate . '% USt';
	}
	print '<tr class="oddeven">';
	print '<td class="tdoverflowmax200">&nbsp;&nbsp;' . dol_escape_htmltag($label) . '</td>';
	print '<td class="right nowraponall"><span class="amount">' . price($data['ht'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
	print '</tr>';
}

// Misc income
if ($report['misc']['income'] > 0) {
	print '<tr class="oddeven">';
	print '<td>&nbsp;&nbsp;' . $langs->trans('MiscIncome') . '</td>';
	print '<td class="right nowraponall"><span class="amount">' . price($report['misc']['income'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
	print '</tr>';
}

// Total income
print '<tr class="liste_total">';
print '<td><strong>' . $langs->trans('TotalIncome') . '</strong></td>';
print '<td class="right nowraponall"><strong>' . price($report['total_income'], 0, $langs, 1, -1, 2, $conf->currency) . '</strong></td>';
print '</tr>';

// Spacer
print '<tr><td colspan="2">&nbsp;</td></tr>';

// ========== EXPENSES SECTION ==========
print '<tr class="liste_titre">';
print '<td colspan="2"><strong>' . $langs->trans('Expenses') . '</strong></td>';
print '</tr>';

// Supplier expenses
print '<tr class="oddeven">';
print '<td>&nbsp;&nbsp;' . $langs->trans('SupplierExpenses') . '</td>';
print '<td class="right nowraponall"><span class="amount">' . price($report['expenses']['total_ht'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
print '</tr>';

// Salaries
print '<tr class="oddeven">';
print '<td>&nbsp;&nbsp;' . $langs->trans('Salaries') . '</td>';
print '<td class="right nowraponall"><span class="amount">' . price($report['salaries']['total'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
print '</tr>';

// Social charges
if (!empty($report['social_charges']['by_type'])) {
	print '<tr class="oddeven">';
	print '<td>&nbsp;&nbsp;<strong>' . $langs->trans('SocialCharges') . '</strong></td>';
	print '<td class="right nowraponall"><span class="amount">' . price($report['social_charges']['total'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
	print '</tr>';

	foreach ($report['social_charges']['by_type'] as $code => $data) {
		print '<tr class="oddeven">';
		print '<td>&nbsp;&nbsp;&nbsp;&nbsp;' . sprintf($langs->trans('SocialChargesDetail'), dol_escape_htmltag($data['label'])) . '</td>';
		print '<td class="right nowraponall"><span class="amount">' . price($data['total'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
		print '</tr>';
	}
}

// Misc expenses
if ($report['misc']['expense'] > 0) {
	print '<tr class="oddeven">';
	print '<td>&nbsp;&nbsp;' . $langs->trans('MiscExpenses') . '</td>';
	print '<td class="right nowraponall"><span class="amount">' . price($report['misc']['expense'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
	print '</tr>';
}

// Total expenses
print '<tr class="liste_total">';
print '<td><strong>' . $langs->trans('TotalExpenses') . '</strong></td>';
print '<td class="right nowraponall"><strong>' . price($report['total_expenses'], 0, $langs, 1, -1, 2, $conf->currency) . '</strong></td>';
print '</tr>';

// Spacer
print '<tr><td colspan="2">&nbsp;</td></tr>';

// ========== PROFIT/LOSS ==========
$plClass = ($report['profit_loss'] >= 0) ? 'amountremaintopay' : 'amountpaymentcomplete';
print '<tr class="liste_total">';
print '<td><strong>' . $langs->trans('ProfitLoss') . '</strong></td>';
print '<td class="right nowraponall"><strong><span class="' . $plClass . '">' . price($report['profit_loss'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></strong></td>';
print '</tr>';

// Spacer
print '<tr><td colspan="2">&nbsp;</td></tr>';

// ========== VAT OVERVIEW ==========
print '<tr class="liste_titre">';
print '<td colspan="2"><strong>' . $langs->trans('VATOverview') . '</strong></td>';
print '</tr>';

// USt collected by rate
foreach ($report['income']['by_rate'] as $rate => $data) {
	if ($data['vat'] > 0) {
		print '<tr class="oddeven">';
		print '<td>&nbsp;&nbsp;' . sprintf($langs->trans('VATCollected'), $rate) . '</td>';
		print '<td class="right nowraponall"><span class="amount">' . price($data['vat'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
		print '</tr>';
	}
}

// VSt deducted
print '<tr class="oddeven">';
print '<td>&nbsp;&nbsp;' . $langs->trans('VATDeducted') . '</td>';
print '<td class="right nowraponall"><span class="amount">' . price($report['vat']['vst_deducted'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></td>';
print '</tr>';

// Zahllast
$zlClass = ($report['vat']['zahllast'] >= 0) ? 'amountremaintopay' : 'amountpaymentcomplete';
print '<tr class="liste_total">';
print '<td><strong>' . $langs->trans('VATPayable') . '</strong></td>';
print '<td class="right nowraponall"><strong><span class="' . $zlClass . '">' . price($report['vat']['zahllast'], 0, $langs, 1, -1, 2, $conf->currency) . '</span></strong></td>';
print '</tr>';

print '</table>';
print '</div>';

// Note
print '<br>';
print '<div class="opacitymedium">';
print $langs->trans('ReportNote');
print '</div>';

llxFooter();
$db->close();
