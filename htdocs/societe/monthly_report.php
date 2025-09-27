<?php
/* Copyright (C) 2024 Monthly Report Module
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
 *	\file       htdocs/societe/monthly_report.php
 *  \ingroup    societe
 *  \brief      Monthly Report for a specific third party
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "other"));

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('monthlyreportcompany'));

$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);

// Get parameters
$action = GETPOST('action', 'alpha');
$report_type = GETPOST('report_type', 'alpha');
if (empty($report_type)) $report_type = 'both';

// Initialize variables
$form = new Form($db);
$object = new Societe($db);

if ($socid > 0) {
	$object->fetch($socid);
}

// Actions
if ($action == 'export_pdf') {
	// PDF export - redirect to print view
	$print_url = $_SERVER["PHP_SELF"] . '?action=print_content&socid=' . $socid;
	if (!empty($report_type)) $print_url .= '&report_type=' . urlencode($report_type);
	header('Location: ' . $print_url);
	exit;
}

// Handle print action for PDF
if ($action == 'print' || $action == 'print_content') {
	if ($action == 'print_content') {
		// Print content only - no navigation, just the content
		print '<!DOCTYPE html><html><head>';
		print '<meta charset="utf-8">';
		print '<title>Monthly Report - ' . strip_tags($object->name) . '</title>';
		print '<style type="text/css">';
		print 'body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }';
		print 'h2 { margin: 0 0 10px 0; }';
		print 'p { margin: 5px 0; }';
		print 'table { width: 100%; border-collapse: collapse; border: 1px solid #ddd; min-width: 800px; }';
		print 'th, td { border: 1px solid #ddd; padding: 5px; text-align: left; min-width: 80px; }';
		print 'th { background-color: #f0f0f0; font-weight: bold; }';
		print '.right { text-align: right; }';
		print '.center { text-align: center; }';
		print '.change-positive { color: #006600; font-weight: bold; }';
		print '.change-negative { color: #990000; font-weight: bold; }';
		print '.close-btn { position: fixed; top: 10px; right: 10px; background: #007cba; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 3px; z-index: 1000; }';
		print '.close-btn:hover { background: #005a87; }';
		print '.year-separator { border-left: 1px solid #ddd; }';
		print '.first-year-separator { border-left: 1px solid #ddd; }';
		print '@media print { body { margin: 0; } .close-btn { display: none !important; } }';
		print '</style>';
		print '<script>';
		print 'var printExecuted = false;';
		print 'window.onload = function() {';
		print '  if (!printExecuted) {';
		print '    printExecuted = true;';
		print '    setTimeout(function() { window.print(); }, 500);';
		print '  }';
		print '};';
		print 'function closePrintView() {';
		print '  window.close();';
		print '}';
		print '</script>';
		print '</head><body>';
		print '<button class="close-btn" onclick="closePrintView()">&times; Close</button>';
	} else {
		llxHeader('', 'Monthly Report');
		print '<div class="no-print" style="margin-bottom: 20px;">';
		print '<button onclick="window.print();" class="butAction"><span class="fa fa-print"></span> Print</button> ';
		print '<a href="' . $_SERVER["PHP_SELF"] . '?socid=' . $socid . '" class="butAction">Back</a>';
		print '</div>';
	}

	// Display the monthly report for printing
	print '<h2>' . $langs->trans("MonthlyReport") . ' - ' . dol_escape_htmltag($object->name) . '</h2>';

	// Show applied filters in print view
	$filters_applied = array();
	if (!empty($report_type)) {
		if ($report_type == 'client') $filters_applied[] = $langs->trans("ReportType") . ': ' . $langs->trans("ClientInvoicesOnly");
		elseif ($report_type == 'supplier') $filters_applied[] = $langs->trans("ReportType") . ': ' . $langs->trans("SupplierInvoicesOnly");
		else $filters_applied[] = $langs->trans("ReportType") . ': ' . $langs->trans("ClientAndSupplierInvoices");
	}
	if (!empty($filters_applied)) {
		print '<p><strong>' . $langs->trans("AppliedFilters") . ':</strong> ' . implode(', ', $filters_applied) . '</p>';
	}

	// Generate data for print view
	$current_year = (int) date('Y');
	$years = array($current_year, $current_year - 1, $current_year - 2);
	$data_by_year = array();
	foreach ($years as $year) {
		// Build SQL based on report type
		if ($report_type == 'client') {
			$sql = "SELECT";
			$sql .= " MONTH(f.datef) as month,";
			$sql .= " SUM(f.total_ht) as total_ht,";
			$sql .= " SUM(f.total_ttc) as total_ttc,";
			$sql .= " COUNT(f.rowid) as nb_invoices";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
			$sql .= " WHERE f.fk_statut IN (1,2,3)";
			$sql .= " AND f.fk_soc = " . (int) $socid;
			$sql .= " AND YEAR(f.datef) = " . (int) $year;
			$sql .= " AND f.entity IN (" . getEntity('invoice') . ")";
			$sql .= " GROUP BY MONTH(f.datef)";
			$sql .= " ORDER BY month ASC";
		} elseif ($report_type == 'supplier') {
			$sql = "SELECT";
			$sql .= " MONTH(f.datef) as month,";
			$sql .= " SUM(f.total_ht) as total_ht,";
			$sql .= " SUM(f.total_ttc) as total_ttc,";
			$sql .= " COUNT(f.rowid) as nb_invoices";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
			$sql .= " WHERE f.fk_statut IN (1,2,3)";
			$sql .= " AND f.fk_soc = " . (int) $socid;
			$sql .= " AND YEAR(f.datef) = " . (int) $year;
			$sql .= " AND f.entity IN (" . getEntity('supplier_invoice') . ")";
			$sql .= " GROUP BY MONTH(f.datef)";
			$sql .= " ORDER BY month ASC";
		} else { // both
			$sql = "SELECT";
			$sql .= " MONTH(datef) as month,";
			$sql .= " SUM(total_ht) as total_ht,";
			$sql .= " SUM(total_ttc) as total_ttc,";
			$sql .= " COUNT(rowid) as nb_invoices";
			$sql .= " FROM (";
			$sql .= " SELECT datef, total_ht, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture";
			$sql .= " WHERE fk_statut IN (1,2,3) AND fk_soc = " . (int) $socid . " AND YEAR(datef) = " . (int) $year . " AND entity IN (" . getEntity('invoice') . ")";
			$sql .= " UNION ALL";
			$sql .= " SELECT datef, total_ht, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture_fourn";
			$sql .= " WHERE fk_statut IN (1,2,3) AND fk_soc = " . (int) $socid . " AND YEAR(datef) = " . (int) $year . " AND entity IN (" . getEntity('supplier_invoice') . ")";
			$sql .= " ) as combined";
			$sql .= " GROUP BY MONTH(datef)";
			$sql .= " ORDER BY month ASC";
		}

		$resql = $db->query($sql);
		if ($resql) {
			$data_by_year[$year] = array();
			while ($obj = $db->fetch_object($resql)) {
				$data_by_year[$year][$obj->month] = $obj;
			}
			$db->free($resql);
		}
	}

	// Display the table for printing
	if (count($years) > 0) {
		print '<table>';

		// Main header
		print '<tr>';
		print '<th rowspan="2">' . $langs->trans("Month") . '</th>';
		foreach ($years as $year_index => $year) {
			$separator_class = ($year_index == 0) ? ' first-year-separator' : ' year-separator';
			print '<th colspan="3" class="center' . $separator_class . '">' . $year . '</th>';
		}
		print '</tr>';

		// Sub-header
		print '<tr>';
		foreach ($years as $year_index => $year) {
			$separator_class = ($year_index == 0) ? ' first-year-separator' : ' year-separator';
			print '<th class="right' . $separator_class . '">' . $langs->trans("AmountTTC") . '</th>';
			print '<th class="right">' . $langs->trans("NbOfInvoices") . '</th>';
			print '<th class="right">' . $langs->trans("Change") . ' (%)</th>';
		}
		print '</tr>';

		// Data rows for each month
		for ($month = 1; $month <= 12; $month++) {
			$month_name = $langs->trans(dol_print_date(dol_mktime(0, 0, 0, $month, 1, 2024), "%B"));
			print '<tr>';
			print '<td>' . $month_name . '</td>';

			foreach ($years as $year_index => $year) {
				$separator_class = ($year_index == 0) ? ' first-year-separator' : ' year-separator';

				// Get current and previous year data for percentage calculation
				$current_amount = 0;
				$previous_amount = 0;

				if (isset($data_by_year[$year][$month])) {
					$data = $data_by_year[$year][$month];
					$current_amount = $data->total_ttc;
					print '<td class="right' . $separator_class . '">' . price($data->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
					print '<td class="right">' . $data->nb_invoices . '</td>';
				} else {
					print '<td class="right' . $separator_class . '">-</td>';
					print '<td class="right">-</td>';
				}

				// Get previous year amount for comparison
				if (isset($years[$year_index + 1])) {
					$prev_year = $years[$year_index + 1];
					if (isset($data_by_year[$prev_year][$month])) {
						$previous_amount = $data_by_year[$prev_year][$month]->total_ttc;
					}
				}

				// Calculate percentage change
				$change_text = '-';
				$change_class = '';
				if ($previous_amount > 0 && $current_amount > 0) {
					$change = (($current_amount - $previous_amount) / $previous_amount) * 100;
					$change_text = sprintf("%+.0f%%", $change);
					$change_class = $change > 0 ? 'change-positive' : 'change-negative';
				} elseif ($previous_amount == 0 && $current_amount > 0) {
					$change_text = 'NEW';
					$change_class = 'change-positive';
				} elseif ($previous_amount > 0 && $current_amount == 0) {
					$change_text = '-100%';
					$change_class = 'change-negative';
				}
				print '<td class="right ' . $change_class . '">' . $change_text . '</td>';
			}
			print '</tr>';
		}

		// Total row
		print '<tr class="liste_total">';
		print '<td><strong>' . $langs->trans("Total") . '</strong></td>';
		foreach ($years as $year_index => $year) {
			$separator_class = ($year_index == 0) ? ' first-year-separator' : ' year-separator';

			$total_ttc = 0;
			$total_invoices = 0;
			for ($month = 1; $month <= 12; $month++) {
				if (isset($data_by_year[$year][$month])) {
					$total_ttc += $data_by_year[$year][$month]->total_ttc;
					$total_invoices += $data_by_year[$year][$month]->nb_invoices;
				}
			}
			print '<td class="right' . $separator_class . '"><strong>' . price($total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</strong></td>';
			print '<td class="right"><strong>' . $total_invoices . '</strong></td>';

			// Calculate total percentage change
			$total_change_text = '-';
			$total_change_class = '';
			if (isset($years[$year_index + 1])) {
				$prev_year = $years[$year_index + 1];
				$prev_total = 0;
				for ($month = 1; $month <= 12; $month++) {
					if (isset($data_by_year[$prev_year][$month])) {
						$prev_total += $data_by_year[$prev_year][$month]->total_ttc;
					}
				}
				if ($prev_total > 0 && $total_ttc > 0) {
					$total_change = (($total_ttc - $prev_total) / $prev_total) * 100;
					$total_change_text = sprintf("%+.1f%%", $total_change);
					$total_change_class = $total_change > 0 ? 'change-positive' : 'change-negative';
				} elseif ($prev_total == 0 && $total_ttc > 0) {
					$total_change_text = 'NEW';
					$total_change_class = 'change-positive';
				} elseif ($prev_total > 0 && $total_ttc == 0) {
					$total_change_text = '-100%';
					$total_change_class = 'change-negative';
				}
			}
			print '<td class="right ' . $total_change_class . '"><strong>' . $total_change_text . '</strong></td>';
		}
		print '</tr>';

		print '</table>';
	} else {
		print '<p>' . $langs->trans("NoDataFound") . '</p>';
	}

	if ($action == 'print_content') {
		print '</body></html>';
	} else {
		llxFooter();
	}
	$db->close();
	exit;
}

/*
 * View
 */

$title = $langs->trans("MonthlyReport") . ' - ' . $object->name;
$helpurl = '';
llxHeader("", $title, $helpurl);

// Show tab header
$head = societe_prepare_head($object);
print dol_get_fiche_head($head, 'monthlyreport', $langs->trans("ThirdParty"), -1, 'company');

$linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print dol_get_fiche_end();

// Filter form and PDF export
print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="socid" value="' . $socid . '">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans("ReportType") . '</th>';
print '<th class="right">' . $langs->trans("Action") . '</th>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>';
print $form->selectarray('report_type', array(
	'both' => $langs->trans('ClientAndSupplierInvoices'),
	'client' => $langs->trans('ClientInvoicesOnly'),
	'supplier' => $langs->trans('SupplierInvoicesOnly')
), $report_type, 0, 0, 0, 'report-filter-select', 0, 0, 0, '', '');
print '</td>';
print '<td class="right">';
// Search button
print '<button type="submit" class="button button_search" title="' . $langs->trans('Search') . '" style="font-size: 14px; padding: 4px 8px; margin-right: 5px;"><span class="fa fa-search"></span></button>';
// PDF export button  
print '<a href="' . $_SERVER["PHP_SELF"] . '?socid=' . $socid . '&action=export_pdf&report_type=' . urlencode($report_type) . '" class="butAction" style="font-size: 14px; padding: 4px 8px;" target="_blank" title="' . $langs->trans('ExportToPDF') . '"><span class="fa fa-file-pdf-o"></span> PDF</a>';
print '</td>';
print '</tr>';
print '</table>';
print '</form>';
print '<br>';

print '<div class="fichecenter">';

// CSS styling for the monthly report table
print '<style>
.monthly-report-table {
    border-collapse: collapse;
    border: 1px solid #ddd;
}
.monthly-report-table td, .monthly-report-table th {
    padding: 8px;
}
.monthly-report-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
}
.year-separator {
    border-left: 1px solid #ddd;
}
.first-year-separator {
    border-left: 1px solid #ddd;
}
.month-header-separator {
    border-bottom: 1px solid #ddd;
}
.change-positive { color: #4CAF50; }
.change-negative { color: #f44336; }
.report-filter-select {
    width: 350px;
    min-width: 350px;
}
.select2-container {
    width: 350px;
    min-width: 350px;
}
.noborder td {
    white-space: nowrap;
    overflow: visible;
}
</style>';

// Get monthly data for the last 3 years
$current_year = (int) date('Y');
$years = array($current_year, $current_year - 1, $current_year - 2);

// Build data array for all years
$data_by_year = array();
foreach ($years as $year) {
	// Build SQL based on report type
	if ($report_type == 'client') {
		$sql = "SELECT";
		$sql .= " MONTH(f.datef) as month,";
		$sql .= " SUM(f.total_ht) as total_ht,";
		$sql .= " SUM(f.total_ttc) as total_ttc,";
		$sql .= " COUNT(f.rowid) as nb_invoices";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " WHERE f.fk_statut IN (1,2,3)";
		$sql .= " AND f.fk_soc = " . (int) $socid;
		$sql .= " AND YEAR(f.datef) = " . (int) $year;
		$sql .= " AND f.entity IN (" . getEntity('invoice') . ")";
		$sql .= " GROUP BY MONTH(f.datef)";
		$sql .= " ORDER BY month ASC";
	} elseif ($report_type == 'supplier') {
		$sql = "SELECT";
		$sql .= " MONTH(f.datef) as month,";
		$sql .= " SUM(f.total_ht) as total_ht,";
		$sql .= " SUM(f.total_ttc) as total_ttc,";
		$sql .= " COUNT(f.rowid) as nb_invoices";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
		$sql .= " WHERE f.fk_statut IN (1,2,3)";
		$sql .= " AND f.fk_soc = " . (int) $socid;
		$sql .= " AND YEAR(f.datef) = " . (int) $year;
		$sql .= " AND f.entity IN (" . getEntity('supplier_invoice') . ")";
		$sql .= " GROUP BY MONTH(f.datef)";
		$sql .= " ORDER BY month ASC";
	} else { // both
		$sql = "SELECT";
		$sql .= " MONTH(datef) as month,";
		$sql .= " SUM(total_ht) as total_ht,";
		$sql .= " SUM(total_ttc) as total_ttc,";
		$sql .= " COUNT(rowid) as nb_invoices";
		$sql .= " FROM (";
		$sql .= " SELECT datef, total_ht, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture";
		$sql .= " WHERE fk_statut IN (1,2,3) AND fk_soc = " . (int) $socid . " AND YEAR(datef) = " . (int) $year . " AND entity IN (" . getEntity('invoice') . ")";
		$sql .= " UNION ALL";
		$sql .= " SELECT datef, total_ht, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture_fourn";
		$sql .= " WHERE fk_statut IN (1,2,3) AND fk_soc = " . (int) $socid . " AND YEAR(datef) = " . (int) $year . " AND entity IN (" . getEntity('supplier_invoice') . ")";
		$sql .= " ) as combined";
		$sql .= " GROUP BY MONTH(datef)";
		$sql .= " ORDER BY month ASC";
	}

	$resql = $db->query($sql);
	if ($resql) {
		$data_by_year[$year] = array();
		while ($obj = $db->fetch_object($resql)) {
			$data_by_year[$year][$obj->month] = $obj;
		}
		$db->free($resql);
	}
}

// Display results
if (count($years) > 0) {
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent monthly-report-table">';

	// Main header
	print '<tr class="liste_titre month-header-separator">';
	print '<th rowspan="2" class="month-header-separator">' . $langs->trans("Month") . '</th>';
	foreach ($years as $year_index => $year) {
		$separator_class = ' first-year-separator'; // Always add separator for first year
		if ($year_index > 0) $separator_class = ' year-separator';
		print '<th colspan="3" class="center' . $separator_class . '">' . $year . '</th>';
	}
	print '</tr>';

	// Sub-header
	print '<tr class="liste_titre month-header-separator">';
	foreach ($years as $year_index => $year) {
		$separator_class = ' first-year-separator'; // Always add separator for first year
		if ($year_index > 0) $separator_class = ' year-separator';
		print '<th class="right' . $separator_class . '">' . $langs->trans("AmountTTC") . '</th>';
		print '<th class="right">' . $langs->trans("NbOfInvoices") . '</th>';
		print '<th class="right">' . $langs->trans("Change") . ' (%)</th>';
	}
	print '</tr>';

	// Data rows for each month
	for ($month = 1; $month <= 12; $month++) {
		$month_name = $langs->trans(dol_print_date(dol_mktime(0, 0, 0, $month, 1, 2024), "%B"));
		print '<tr class="oddeven">';
		print '<td>' . $month_name . '</td>';

		foreach ($years as $year_index => $year) {
			$separator_class = ' first-year-separator';
			if ($year_index > 0) $separator_class = ' year-separator';

			// Get current and previous year data for percentage calculation
			$current_amount = 0;
			$previous_amount = 0;

			if (isset($data_by_year[$year][$month])) {
				$data = $data_by_year[$year][$month];
				$current_amount = $data->total_ttc;
				print '<td class="right' . $separator_class . '">' . price($data->total_ttc) . '</td>';
				print '<td class="right">' . $data->nb_invoices . '</td>';
			} else {
				print '<td class="right' . $separator_class . '">-</td>';
				print '<td class="right">-</td>';
			}

			// Get previous year amount for comparison
			if (isset($years[$year_index + 1])) {
				$prev_year = $years[$year_index + 1];
				if (isset($data_by_year[$prev_year][$month])) {
					$previous_amount = $data_by_year[$prev_year][$month]->total_ttc;
				}
			}

			// Calculate percentage change
			$change_text = '-';
			$change_class = '';
			if ($previous_amount > 0 && $current_amount > 0) {
				$change = (($current_amount - $previous_amount) / $previous_amount) * 100;
				$change_text = sprintf("%+.0f%%", $change);
				$change_class = $change > 0 ? 'change-positive' : 'change-negative';
			} elseif ($previous_amount == 0 && $current_amount > 0) {
				$change_text = '+∞%';
				$change_class = 'change-positive';
			} elseif ($previous_amount > 0 && $current_amount == 0) {
				$change_text = '-100%';
				$change_class = 'change-negative';
			}
			print '<td class="right ' . $change_class . '">' . $change_text . '</td>';
		}
		print '</tr>';
	}

	// Total row
	print '<tr class="liste_total">';
	print '<td><strong>' . $langs->trans("Total") . '</strong></td>';
	foreach ($years as $year_index => $year) {
		$separator_class = ' first-year-separator'; // Always add separator for first year
		if ($year_index > 0) $separator_class = ' year-separator';

		$total_ttc = 0;
		$total_invoices = 0;
		for ($month = 1; $month <= 12; $month++) {
			if (isset($data_by_year[$year][$month])) {
				$total_ttc += $data_by_year[$year][$month]->total_ttc;
				$total_invoices += $data_by_year[$year][$month]->nb_invoices;
			}
		}
		print '<td class="right' . $separator_class . '"><strong>' . price($total_ttc) . '</strong></td>';
		print '<td class="right"><strong>' . $total_invoices . '</strong></td>';

		// Calculate total percentage change
		$total_change_text = '-';
		$total_change_class = '';
		if (isset($years[$year_index + 1])) {
			$prev_year = $years[$year_index + 1];
			$prev_total = 0;
			for ($month = 1; $month <= 12; $month++) {
				if (isset($data_by_year[$prev_year][$month])) {
					$prev_total += $data_by_year[$prev_year][$month]->total_ttc;
				}
			}
			if ($prev_total > 0 && $total_ttc > 0) {
				$total_change = (($total_ttc - $prev_total) / $prev_total) * 100;
				$total_change_text = sprintf("%+.1f%%", $total_change);
				$total_change_class = $total_change > 0 ? 'change-positive' : 'change-negative';
			} elseif ($prev_total == 0 && $total_ttc > 0) {
				$total_change_text = '+∞%';
				$total_change_class = 'change-positive';
			} elseif ($prev_total > 0 && $total_ttc == 0) {
				$total_change_text = '-100%';
				$total_change_class = 'change-negative';
			}
		}
		print '<td class="right ' . $total_change_class . '"><strong>' . $total_change_text . '</strong></td>';
	}
	print '</tr>';

	print '</table>';
	print '</div>';
} else {
	print '<div class="opacitymedium">' . $langs->trans("NoDataFound") . '</div>';
}

print '</div>'; // End fichecenter

// End of page
llxFooter();
$db->close();
