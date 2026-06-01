<?php
/* Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026		MDW	<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/ai/assistant/download_pdf.php
 * \ingroup    ai
 * \brief      Script to generate and download a PDF from AI assistant data.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOCSRFCHECK')) {		// TODO Enable the CSRF check
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}

global $user, $langs;
$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies', 'margins', 'reports@reports'));

// Input validation and sanitization
$json = GETPOST('data', 'restricthtml');
if (empty($json)) {
	$json = file_get_contents('php://input');
}

// Validate JSON structure
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
	dol_syslog("Invalid JSON data in PDF generation: " . json_last_error_msg(), LOG_ERR);
	print "Error: Invalid data format provided.";
	exit;
}

// Get title and filename
$title = GETPOST('title', 'restricthtml');
if (empty($title)) {
	$title = "AI Report";
}

$filename = GETPOST('filename', 'restricthtml');
if (empty($filename)) {
	$filename = 'Report.pdf';
}

// Remove control characters and special filesystem characters
$filename = preg_replace('/[\x00-\x1F\x7F\/\\:*?"<>|]/', '', $filename);
// Ensure filename has .pdf extension
if (!preg_match('/\.pdf$/i', $filename)) {
	$filename .= '.pdf';
}

if (!$data) {
	print "No data provided for PDF generation.";
	exit;
}

try {
	$outputlangs = $langs;

	// Get format - use landscape orientation for better column display
	$format = pdf_getFormat($outputlangs);

	$pdf_dimensions = [$format['width'], $format['height']];
	$pdf = pdf_getInstance($pdf_dimensions, $format['unit'], 'l');
	$default_font_size = pdf_getPDFFontSize($outputlangs);
	$pdf->SetCreator("Dolibarr AI");
	$pdf->SetAuthor($user->getFullName($langs));

	$pdf->SetTitle($title);
	$pdf->SetMargins(15, 15, 15);
	$pdf->SetAutoPageBreak(true, 15);
	if (class_exists('TCPDF')) {
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	}
	$pdf->AddPage();
	$pdf->SetFont('dejavusans', '', 10); // Important for non-Latin languages

	// Header
	$pdf->SetFont('', 'B', 16);
	$pdf->Cell(0, 10, $title, 0, 1, 'C');
	$pdf->SetFont('', '', 9);
	$pdf->SetTextColor(100, 100, 100);
	$pdf->Cell(0, 5, dol_print_date(dol_now(), 'dayhour', 'tzuser'), 0, 1, 'R');
	$pdf->Ln(5);
	$pdf->SetTextColor(0, 0, 0);

	$html = '<style>
		h3 { color: #333; border-bottom: 1px solid #ccc; font-size: 12pt; margin-top: 15px; }
		table { width: 100%; border-collapse: collapse; }
		th { background-color: #f0f0f0; font-weight: bold; padding: 5px; border: 1px solid #ccc; }
		td { padding: 5px; border: 1px solid #ccc; }
		.key { font-weight: bold; width: 30%; background-color: #f9f9f9; }
		.val { width: 70%; }
	</style>';

	// Overview / Dashboard (complex object)
	if (isset($data['customer']) && isset($data['details'])) {
		// Customer Info
		$html .= '<h3>' . $langs->trans('ThirdpartyDetails') . '</h3>';
		$html .= '<table cellpadding="4">';
		foreach ($data['customer'] as $k => $v) {
			// Skip internal fields
			if ($k === 'url' || $k === 'id' || $k === 'rowid') {
				continue;
			}
			$html .= '<tr><td class="key">' . dol_escape_htmltag(ucfirst($k)) . '</td><td class="val">' . dol_escape_htmltag($v) . '</td></tr>';
		}
		$html .= '</table>';

		// Sections (Invoices, Orders, etc)
		foreach ($data['details'] as $section => $rows) {
			$sectionTitle = dol_escape_htmltag(ucwords((string) str_replace('_', ' ', $section)));
			$html .= '<h3>' . $sectionTitle . '</h3>';

			if (empty($rows) || !is_array($rows)) {
				$html .= '<p>' . $langs->trans('NoDataAvailable') . '</p>';
			} else {
				// Table Builder for List
				$keys = array_keys($rows[0]);
				$keys = array_filter(
					$keys,
					/**
					 * @return bool Select only fields other than url and rowid
					 */
					static function (string $k) {
						return $k !== 'url' && $k !== 'rowid';
					}
				);

				$html .= '<table cellpadding="4"><thead><tr>';
				foreach ($keys as $k) {
					$html .= '<th>' . dol_escape_htmltag(ucfirst($k)) . '</th>';
				}
				$html .= '</tr></thead><tbody>';
				foreach ($rows as $row) {
					$html .= '<tr>';
					foreach ($keys as $k) {
						$val = isset($row[$k]) ? $row[$k] : '-';
						if (is_array($val)) {
							$val = count($val);
						}

						if (is_string($val) && strpos($val, '<a href') !== false) {
							$html .= '<td>' . $val . '</td>';
						} else {
							$html .= '<td>' . dol_escape_htmltag($val) . '</td>';
						}
					}
					$html .= '</tr>';
				}
				$html .= '</tbody></table>';
			}
		}
	} elseif (isset($data[0]) && is_array($data[0])) {
		// Simple list (e.g. search invoices)
		$keys = array_keys($data[0]);
		$keys = array_filter(
			$keys,
			/**
			 * @return bool Select only fields other than url and rowid
			 */
			static function (string $k) {
				return $k !== 'url' && $k !== 'rowid';
			}
		);

		$html .= '<table cellpadding="4"><thead><tr nobr="true">';
		foreach ($keys as $key) {
			$html .= '<th>' . dol_escape_htmltag(strtoupper(str_replace('_', ' ', $key))) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ($data as $row) {
			$html .= '<tr nobr="true">';
			foreach ($keys as $key) {
				$val = isset($row[$key]) ? $row[$key] : '';
				if (is_array($val)) {
					$val = count($val) . ' items';
				}
				if (is_string($val) && strpos($val, '<a href') !== false) {
					$html .= '<td>' . $val . '</td>';
				} else {
					$html .= '<td>' . dol_escape_htmltag($val) . '</td>';
				}
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
	} else {
		// Simple object
		$html .= '<table cellpadding="5">';
		foreach ($data as $key => $val) {
			// Skip internal fields
			if ($key === 'url') {
				continue;
			}
			$valStr = is_array($val) ? json_encode($val) : $val;
			$html .= '<tr><td class="key">' . dol_escape_htmltag(ucfirst($key)) . '</td><td class="val">' . dol_escape_htmltag($valStr) . '</td></tr>';
		}
		$html .= '</table>';
	}

	$pdf->writeHTML($html, true, false, true, false, '');
	$pdf->Output($filename, 'D');
} catch (Exception $e) {
	dol_syslog("Error generating PDF: " . $e->getMessage(), LOG_ERR);
	print "PDF Error: Unable to generate PDF. Please contact administrator.";
	exit;
}
