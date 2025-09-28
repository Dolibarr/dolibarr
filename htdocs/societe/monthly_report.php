<?php
/* Copyright (C) 2024 Christos Kanotidis
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
 *
 *  Features:
 *  - All texts are translatable using \$langs->trans()
 *  - PDF export
 *  - Xls export
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
$langs->loadLangs(array("companies", "bills", "cashdesk", "main"));

// Initialize a technical object to manage hooks
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
if ($action == 'export_xls') {
	// Generate XLS using Dolibarr's native Excel export functions
	require_once DOL_DOCUMENT_ROOT . '/core/modules/export/export_excel2007.modules.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

	// Set output language
	$outputlangs = $langs;

	// Create Excel export instance
	$objmodel = new ExportExcel2007($db);

	// Generate filename
	$filename = 'monthly_report_' . dol_sanitizeFileName($object->name) . '_' . dol_print_date(dol_now(), '%Y%m%d_%H%M%S') . '.xlsx';
	$dirname = $conf->societe->dir_temp;

	// Create directory if not exists
	dol_mkdir($dirname);

	// Open Excel file
	$result = $objmodel->open_file($dirname . "/" . $filename, $outputlangs);

	if ($result >= 0) {
		// Write header
		$objmodel->write_header($outputlangs);

		// Generate data (same logic as PDF)
		$current_year = (int) date('Y');
		$years = array($current_year, $current_year - 1, $current_year - 2);
		$data_by_year = array();

		// Prepare title array with dynamic years
		$array_export_fields_label = array(
			'month' => $langs->trans("Month")
		);

		// Add columns for each year
		foreach ($years as $year) {
			$array_export_fields_label['amount_' . $year] = $langs->trans("AmountTTC") . ' ' . $year;
			$array_export_fields_label['nb_' . $year] = $langs->trans("NbOfInvoices") . ' ' . $year;
			$array_export_fields_label['change_' . $year] = $langs->trans("ChangeFromLastYear") . ' ' . $year;
		}

		$array_selected_sorted = array_keys($array_export_fields_label);

		foreach ($years as $year) {
			// Build SQL based on report type (same as PDF logic)
			if ($report_type == 'client') {
				$sql = "SELECT";
				$sql .= " MONTH(f.datef) as month,";
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
				$sql .= " SUM(total_ttc) as total_ttc,";
				$sql .= " COUNT(rowid) as nb_invoices";
				$sql .= " FROM (";
				$sql .= " SELECT datef, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture";
				$sql .= " WHERE fk_statut IN (1,2,3) AND fk_soc = " . (int) $socid . " AND YEAR(datef) = " . (int) $year . " AND entity IN (" . getEntity('invoice') . ")";
				$sql .= " UNION ALL";
				$sql .= " SELECT datef, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture_fourn";
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

		// Add title and filter information to the Excel sheet
		// Row 1: Main title
		$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow(1, 1, $langs->trans("MonthlyReport") . ' - ' . $object->name);
		$objmodel->workbook->getActiveSheet()->mergeCells('A1:' . $objmodel->column2Letter(count($array_export_fields_label)) . '1');
		$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow(1, 1)->getCoordinate();
		$objmodel->workbook->getActiveSheet()->getStyle($coord)->getFont()->setBold(true)->setSize(16);
		$objmodel->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		// Add border to title
		$title_range = 'A1:' . $objmodel->column2Letter(count($array_export_fields_label)) . '1';
		$objmodel->workbook->getActiveSheet()->getStyle($title_range)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Row 2: Filter information
		$filter_text = $langs->trans("Filter") . ': ';
		if ($report_type == 'client') $filter_text .= $langs->trans("ClientInvoicesOnly");
		elseif ($report_type == 'supplier') $filter_text .= $langs->trans("SupplierInvoicesOnly");
		else $filter_text .= $langs->trans("ClientAndSupplierInvoices");

		$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow(1, 2, $filter_text);
		$objmodel->workbook->getActiveSheet()->mergeCells('A2:' . $objmodel->column2Letter(count($array_export_fields_label)) . '2');
		$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow(1, 2)->getCoordinate();
		$objmodel->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		// Add border to filter row
		$filter_range = 'A2:' . $objmodel->column2Letter(count($array_export_fields_label)) . '2';
		$objmodel->workbook->getActiveSheet()->getStyle($filter_range)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		// Row 3: Empty row for spacing

		// Row 4: Column headers
		$col = 1;
		foreach ($array_export_fields_label as $key => $label) {
			$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, 4, $outputlangs->transnoentities($label));
			// Make headers bold
			$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, 4)->getCoordinate();
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getFont()->setBold(true);
			// Set smaller column width and enable text wrapping
			$objmodel->workbook->getActiveSheet()->getColumnDimension($objmodel->column2Letter($col))->setWidth(15);
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setWrapText(true);
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
			// Add border to header
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$col++;
		}
		// Set row height for headers to accommodate wrapped text
		$objmodel->workbook->getActiveSheet()->getRowDimension(4)->setRowHeight(40);

		// Write data rows using direct cell access (starting from row 5)
		$row = 5; // Start after title, filter, empty row, and header row
		for ($month = 1; $month <= 12; $month++) {
			$month_name = $langs->trans(dol_print_date(dol_mktime(0, 0, 0, $month, 1, 2024), "%B"));

			$col = 1;
			// Write month name
			$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $month_name);
			// Add border to month cell
			$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$col++;

			foreach ($years as $year_index => $year) {
				// Get current and previous year data
				$current_amount = 0;
				$previous_amount = 0;
				$nb_invoices = 0;

				if (isset($data_by_year[$year][$month])) {
					$data = $data_by_year[$year][$month];
					$current_amount = $data->total_ttc;
					$nb_invoices = $data->nb_invoices;
				}

				// Get previous year for comparison
				if (isset($years[$year_index + 1])) {
					$prev_year = $years[$year_index + 1];
					if (isset($data_by_year[$prev_year][$month])) {
						$previous_amount = $data_by_year[$prev_year][$month]->total_ttc;
					}
				}

				// Write amount
				$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $current_amount > 0 ? $current_amount : '');
				// Add border to amount cell
				$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
				$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
				$col++;

				// Write number of invoices
				$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $nb_invoices > 0 ? $nb_invoices : '');
				// Add border to invoices cell
				$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
				$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
				$col++;

				// Calculate and write percentage change
				$change_text = '';
				if ($previous_amount > 0 && $current_amount > 0) {
					$change = (($current_amount - $previous_amount) / $previous_amount) * 100;
					$change_text = sprintf("%.1f", $change);
				} elseif ($previous_amount == 0 && $current_amount > 0) {
					$change_text = $langs->trans("New");
				} elseif ($previous_amount > 0 && $current_amount == 0) {
					$change_text = '-100';
				}
				$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $change_text);
				// Add border to change cell
				$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
				$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
				$col++;
			}
			$row++;
		}

		// Write total row
		$col = 1;
		$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $langs->trans("Total"));
		// Add border and bold formatting to total label
		$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
		$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
		$objmodel->workbook->getActiveSheet()->getStyle($coord)->getFont()->setBold(true);
		$col++;

		foreach ($years as $year_index => $year) {
			$total_ttc = 0;
			$total_invoices = 0;
			for ($m = 1; $m <= 12; $m++) {
				if (isset($data_by_year[$year][$m])) {
					$total_ttc += $data_by_year[$year][$m]->total_ttc;
					$total_invoices += $data_by_year[$year][$m]->nb_invoices;
				}
			}

			// Write total amount
			$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $total_ttc > 0 ? $total_ttc : '');
			// Add border and bold formatting to total amount
			$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getFont()->setBold(true);
			$col++;

			// Write total invoices
			$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $total_invoices > 0 ? $total_invoices : '');
			// Add border and bold formatting to total invoices
			$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getFont()->setBold(true);
			$col++;

			// Calculate total percentage change
			$total_change_text = '';
			if (isset($years[$year_index + 1])) {
				$prev_year = $years[$year_index + 1];
				$prev_total = 0;
				for ($m = 1; $m <= 12; $m++) {
					if (isset($data_by_year[$prev_year][$m])) {
						$prev_total += $data_by_year[$prev_year][$m]->total_ttc;
					}
				}
				if ($prev_total > 0 && $total_ttc > 0) {
					$total_change = (($total_ttc - $prev_total) / $prev_total) * 100;
					$total_change_text = sprintf("%.1f", $total_change);
				} elseif ($prev_total == 0 && $total_ttc > 0) {
					$total_change_text = $langs->trans("New");
				} elseif ($prev_total > 0 && $total_ttc == 0) {
					$total_change_text = '-100';
				}
			}
			$objmodel->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $total_change_text);
			// Add border and bold formatting to total change
			$coord = $objmodel->workbook->getActiveSheet()->getCellByColumnAndRow($col, $row)->getCoordinate();
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getBorders()->getAllBorders()->setBorderStyle(PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$objmodel->workbook->getActiveSheet()->getStyle($coord)->getFont()->setBold(true);
			$col++;
		}

		// Write footer and close file
		$objmodel->write_footer($outputlangs);
		$objmodel->close_file();

		// Download file
		$mime_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		$file_path = $dirname . "/" . $filename;

		if (file_exists($file_path)) {
			header('Content-Type: ' . $mime_type);
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($file_path));
			readfile($file_path);
			unlink($file_path); // Clean up temp file
			exit;
		}
	}

	// If we get here, there was an error
	setEventMessages($langs->trans("ErrorCanNotCreateFile", $filename), null, 'errors');
}

if ($action == 'export_pdf') {
	// Generate PDF using Dolibarr's native PDF functions
	require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

	// Ensure PDF constants are defined
	if (!defined('TCPDF_PATH')) {
		if (file_exists(DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/')) {
			define('TCPDF_PATH', DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/');
		}
	}

	// Set output language
	$outputlangs = $langs;

	// Override any problematic font configurations
	if (isset($conf->global->MAIN_PDF_FORCE_FONT) && strtolower($conf->global->MAIN_PDF_FORCE_FONT) == 'dejavusans') {
		unset($conf->global->MAIN_PDF_FORCE_FONT);
	}

	// Get format - use landscape orientation for better layout
	$format = pdf_getFormat($outputlangs);

	// Create PDF instance with landscape orientation
	$pdf = pdf_getInstance(array($format['height'], $format['width']), $format['unit'], 'L');
	$default_font_size = pdf_getPDFFontSize($outputlangs);

	if (class_exists('TCPDF')) {
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	}

	// Use Dolibarr's standard font function with comprehensive error handling
	$font = pdf_getPDFFont($outputlangs);

	// List of valid TCPDF fonts available in Dolibarr
	$valid_fonts = array(
		'helvetica',
		'times',
		'courier',
		'dejavusans',
		'freemono',
		'freeserif',
		'stsongstdlight',
		'msungstdlight',
		'cid0ct',
		'cid0cs',
		'cid0jp',
		'cid0kr'
	);

	// Validate and sanitize font name
	if (empty($font) || $font == 'dejavusans' || !in_array(strtolower($font), $valid_fonts)) {
		$font = 'helvetica'; // Safe fallback font that's always available
	}

	// Force lowercase for consistency
	$font = strtolower($font);

	try {
		$pdf->SetFont($font, '', $default_font_size);
	} catch (Exception $e) {
		// If font still fails, force helvetica
		$font = 'helvetica';
		$pdf->SetFont($font, '', $default_font_size);
	}
	$pdf->SetAutoPageBreak(true, 10);

	// Set PDF properties
	$pdf->SetTitle($langs->trans("MonthlyReport") . ' - ' . $object->name);
	$pdf->SetSubject($langs->trans("MonthlyReport"));
	$pdf->SetCreator("Dolibarr " . DOL_VERSION);
	$pdf->SetAuthor($user->getFullName($outputlangs));

	// Start PDF
	$pdf->Open();
	$pdf->AddPage();

	// Set margins
	$pdf->SetMargins(15, 15, 15);

	// Title - use safe font with error handling
	try {
		$pdf->SetFont($font, 'B', 18);
	} catch (Exception $e) {
		$pdf->SetFont('helvetica', 'B', 18);
	}
	$pdf->Cell(0, 12, $langs->trans("MonthlyReport") . ' - ' . $object->name, 0, 1, 'C');
	$pdf->Ln(8);

	// Show applied filters with better formatting
	if (!empty($report_type)) {
		try {
			$pdf->SetFont($font, '', 11);
		} catch (Exception $e) {
			$pdf->SetFont('helvetica', '', 11);
		}
		$filter_text = $langs->trans("Filter") . ': ';
		if ($report_type == 'client') $filter_text .= $langs->trans("ClientInvoicesOnly");
		elseif ($report_type == 'supplier') $filter_text .= $langs->trans("SupplierInvoicesOnly");
		else $filter_text .= $langs->trans("ClientAndSupplierInvoices");

		$pdf->Cell(0, 8, $filter_text, 0, 1, 'C');
		$pdf->Ln(5);
	}

	// Generate data
	$current_year = (int) date('Y');
	$years = array($current_year, $current_year - 1, $current_year - 2);
	$data_by_year = array();

	foreach ($years as $year) {
		// Build SQL based on report type
		if ($report_type == 'client') {
			$sql = "SELECT";
			$sql .= " MONTH(f.datef) as month,";
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
			$sql .= " SUM(total_ttc) as total_ttc,";
			$sql .= " COUNT(rowid) as nb_invoices";
			$sql .= " FROM (";
			$sql .= " SELECT datef, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture";
			$sql .= " WHERE fk_statut IN (1,2,3) AND fk_soc = " . (int) $socid . " AND YEAR(datef) = " . (int) $year . " AND entity IN (" . getEntity('invoice') . ")";
			$sql .= " UNION ALL";
			$sql .= " SELECT datef, total_ttc, rowid FROM " . MAIN_DB_PREFIX . "facture_fourn";
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

	// Create table header with proper merged cells and no gaps
	try {
		$pdf->SetFont($font, 'B', 10);
	} catch (Exception $e) {
		$pdf->SetFont('helvetica', 'B', 10);
	}
	$pdf->SetFillColor(240, 240, 240);

	// Create table header matching HTML structure exactly
	try {
		$pdf->SetFont($font, 'B', 10);
	} catch (Exception $e) {
		$pdf->SetFont('helvetica', 'B', 10);
	}
	$pdf->SetFillColor(240, 240, 240);

	// Calculate responsive column widths based on content needs
	$page_width = $pdf->getPageWidth() - 30; // Account for margins

	// Month column width
	$month_col_width = 50;
	$available_width = $page_width - $month_col_width;
	$year_col_width = $available_width / 3; // Equal width for each year
	$sub_col_width = $year_col_width / 3; // Each year has 3 sub-columns

	// Main header row - Month column (properly merged) and Year columns
	$pdf->Cell($month_col_width, 18, $langs->trans("Month"), 1, 0, 'C', true); // Merged cell spanning both rows (9+9=18)

	// Year headers
	foreach ($years as $year) {
		$pdf->Cell($year_col_width, 9, $year, 1, 0, 'C', true);
	}
	$pdf->Ln();

	// Sub-header row - no cell for month (already merged), only sub-columns with text wrapping
	try {
		$pdf->SetFont($font, 'B', 7); // Smaller font for better fit
	} catch (Exception $e) {
		$pdf->SetFont('helvetica', 'B', 7);
	}

	// Position after the month column for sub-headers
	$pdf->SetX(15 + $month_col_width);

	// Sub-headers for each year with exact 9-unit height to match the remaining row space
	foreach ($years as $year) {
		$pdf->Cell($sub_col_width, 9, $langs->trans("AmountTTC"), 1, 0, 'C', true);
		$pdf->Cell($sub_col_width, 9, $langs->trans("NbOfInvoices"), 1, 0, 'C', true);
		$pdf->Cell($sub_col_width, 9, $langs->trans("ChangeFromLastYear"), 1, 0, 'C', true);
	}
	$pdf->Ln();

	// Table data - safe font handling with improved layout and larger cells
	try {
		$pdf->SetFont($font, '', 10);
	} catch (Exception $e) {
		$pdf->SetFont('helvetica', '', 10);
	}
	$pdf->SetFillColor(255, 255, 255);

	for ($month = 1; $month <= 12; $month++) {
		$month_name = $langs->trans(dol_print_date(dol_mktime(0, 0, 0, $month, 1, 2024), "%B"));
		$pdf->Cell($month_col_width, 9, $month_name, 1, 0, 'L');

		foreach ($years as $year_index => $year) {
			// Get current and previous year data
			$current_amount = 0;
			$previous_amount = 0;
			$nb_invoices = 0;

			if (isset($data_by_year[$year][$month])) {
				$data = $data_by_year[$year][$month];
				$current_amount = $data->total_ttc;
				$nb_invoices = $data->nb_invoices;
			}

			// Get previous year for comparison
			if (isset($years[$year_index + 1])) {
				$prev_year = $years[$year_index + 1];
				if (isset($data_by_year[$prev_year][$month])) {
					$previous_amount = $data_by_year[$prev_year][$month]->total_ttc;
				}
			}

			// Display amount with standard column width
			$amount_text = $current_amount > 0 ? price($current_amount, 0, $outputlangs, 1, -1, -1, $conf->currency) : '-';
			$pdf->Cell($sub_col_width, 9, $amount_text, 1, 0, 'R');

			// Display number of invoices with standard column width
			$invoices_text = $nb_invoices > 0 ? $nb_invoices : '-';
			$pdf->Cell($sub_col_width, 9, $invoices_text, 1, 0, 'R');

			// Calculate percentage change with standard column width
			$change_text = '-';
			if ($previous_amount > 0 && $current_amount > 0) {
				$change = (($current_amount - $previous_amount) / $previous_amount) * 100;
				$change_text = sprintf("%+.0f%%", $change);
			} elseif ($previous_amount == 0 && $current_amount > 0) {
				$change_text = $langs->trans("New");
			} elseif ($previous_amount > 0 && $current_amount == 0) {
				$change_text = '-100%';
			}
			$pdf->Cell($sub_col_width, 9, $change_text, 1, 0, 'R');
		}
		$pdf->Ln();
	}

	// Total row - safe font handling with improved layout and larger cells
	try {
		$pdf->SetFont($font, 'B', 10);
	} catch (Exception $e) {
		$pdf->SetFont('helvetica', 'B', 10);
	}
	$pdf->SetFillColor(230, 230, 230);
	$pdf->Cell($month_col_width, 10, $langs->trans("Total"), 1, 0, 'L', true);

	foreach ($years as $year_index => $year) {
		$total_ttc = 0;
		$total_invoices = 0;
		for ($month = 1; $month <= 12; $month++) {
			if (isset($data_by_year[$year][$month])) {
				$total_ttc += $data_by_year[$year][$month]->total_ttc;
				$total_invoices += $data_by_year[$year][$month]->nb_invoices;
			}
		}

		$pdf->Cell($sub_col_width, 10, price($total_ttc, 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 0, 'R', true);
		$pdf->Cell($sub_col_width, 10, $total_invoices, 1, 0, 'R', true);

		// Calculate total percentage change
		$total_change_text = '-';
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
			} elseif ($prev_total == 0 && $total_ttc > 0) {
				$total_change_text = $langs->trans("New");
			} elseif ($prev_total > 0 && $total_ttc == 0) {
				$total_change_text = '-100%';
			}
		}
		$pdf->Cell($sub_col_width, 10, $total_change_text, 1, 0, 'R', true);
	}

	// Generate filename
	$filename = 'monthly_report_' . dol_sanitizeFileName($object->name) . '_' . dol_print_date(dol_now(), '%Y%m%d_%H%M%S') . '.pdf';

	// Output PDF for download
	$pdf->Output($filename, 'D');
	exit;
}

// Handle print action for PDF (kept for compatibility)
if ($action == 'print' || $action == 'print_content') {
	if ($action == 'print_content') {
		// Print content only - no navigation, just the content
		print '<!DOCTYPE html><html><head>';
		print '<meta charset="utf-8">';
		print '<title>' . $langs->trans("MonthlyReport") . ' - ' . strip_tags($object->name) . '</title>';
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
		print '@media print { body { margin: 0; background: white !important; } .close-btn { display: none !important; } }';
		print '@media screen { body { background: white; margin: 0; padding: 20px; } }';
		print 'body { font-family: Arial, sans-serif; font-size: 12px; }';
		print '</style>';
		print '<script>';
		print 'var printExecuted = false;';
		print 'window.onload = function() {';
		print '  if (!printExecuted) {';
		print '    printExecuted = true;';
		print '    // Immediately hide page content';
		print '    document.body.style.visibility = "hidden";';
		print '    // Trigger print dialog with minimal delay';
		print '    setTimeout(function() {';
		print '      document.body.style.visibility = "visible";';
		print '      window.print();';
		print '    }, 50);';
		print '    // Listen for print completion';
		print '    if (window.matchMedia) {';
		print '      var mediaQueryList = window.matchMedia("print");';
		print '      mediaQueryList.addListener(function(mql) {';
		print '        if (!mql.matches) {';
		print '          setTimeout(function() { window.close(); }, 100);';
		print '        }';
		print '      });';
		print '    }';
		print '    // Primary afterprint event';
		print '    window.addEventListener("afterprint", function() {';
		print '      window.close();';
		print '    });';
		print '    // Fallback timeout';
		print '    setTimeout(function() {';
		print '      window.close();';
		print '    }, 3000);';
		print '  }';
		print '};';
		print 'function closePrintView() {';
		print '  window.close();';
		print '}';;
		print '</script>';
		print '</head><body>';
		print '<button class="close-btn" onclick="closePrintView()">&times; ' . $langs->trans("Close") . '</button>';
	} else {
		llxHeader('', 'Monthly Report');
		print '<div class="no-print" style="margin-bottom: 20px;">';
		print '<button onclick="window.print();" class="butAction"><span class="fa fa-print"></span> ' . $langs->trans("Print") . '</button> ';
		print '<a href="' . $_SERVER["PHP_SELF"] . '?socid=' . $socid . '" class="butAction">' . $langs->trans("Back") . '</a>';
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
		print '<p><strong>' . $langs->trans("Filter") . ':</strong> ' . implode(', ', $filters_applied) . '</p>';
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
			print '<th class="right">' . $langs->trans("ChangeFromLastYear") . ' </th>';
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
					$change_text = $langs->trans("New");
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
					$total_change_text = $langs->trans("New");
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
// XLS export button
print '<a href="' . $_SERVER["PHP_SELF"] . '?socid=' . $socid . '&action=export_xls&report_type=' . urlencode($report_type) . '" class="butAction" style="font-size: 14px; padding: 4px 8px; margin-right: 5px;" title="' . $langs->trans('ExportToXLS') . '"><span class="fa fa-file-excel-o"></span> XLS</a>';
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
		print '<th class="right">' . $langs->trans("ChangeFromLastYear") . '</th>';
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
				$change_text = $langs->trans("New");
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
				$total_change_text = $langs->trans("New");
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
	print '<div class="opacitymedium">' . $langs->trans("NoRecordFound") . '</div>';
}

print '</div>'; // End fichecenter

// End of page
llxFooter();
$db->close();
