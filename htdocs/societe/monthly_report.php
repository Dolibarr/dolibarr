<?php
/* Copyright (C) 2026		Nick Fragoulis
 * Copyright (C) 2026		Christos Kanotidis		<christoskanotidis@gmail.com>
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
 *  \file       htdocs/societe/monthly_report.php
 *  \ingroup    societe
 *  \brief      Tab with monthly invoiced amounts of a thirdparty, compared over several years
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "compta", "accountancy"));

$action = GETPOST('action', 'aZ09');
$socid = GETPOSTINT('socid');
$type = GETPOST('type', 'aZ09');

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('thirdpartymonthlyreport', 'globalcard'));

$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);
if ($socid > 0) {
	$object->fetch($socid);
}

$permissiontoreadcustomer = (isModEnabled('invoice') && $user->hasRight('facture', 'lire'));
$permissiontoreadsupplier = ((isModEnabled("fournisseur") && $user->hasRight("fournisseur", "facture", "lire") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_invoice") && $user->hasRight("supplier_invoice", "lire")));

// Invoice type: customer or supplier, default from the thirdparty nature
$typesallowed = array();
if ($permissiontoreadcustomer) {
	$typesallowed['customer'] = $langs->trans("BillsCustomers");
}
if ($permissiontoreadsupplier) {
	$typesallowed['supplier'] = $langs->trans("BillsSuppliers");
}
if (!array_key_exists($type, $typesallowed)) {
	$type = (($object->client == 1 || $object->client == 3 || !$object->fournisseur) ? 'customer' : 'supplier');
	if (!array_key_exists($type, $typesallowed)) {
		$type = (string) key($typesallowed);
	}
}
$permissiontoreadinvoice = !empty($typesallowed);

// Fiscal years shown: the current one first, then the 2 previous ones
$nbofyear = 3;
$monthstart = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
if ($monthstart < 1 || $monthstart > 12) {
	$monthstart = 1;
}
$nowarray = dol_getdate(dol_now(), true);
$year = (int) $nowarray['year'] - ((int) $nowarray['mon'] < $monthstart ? 1 : 0);


/**
 * Return amounts excl. tax and number of validated invoices of a thirdparty, by fiscal year and month.
 * One year more than $nbofyear is loaded, to compute the delta of the oldest year shown.
 *
 * @param	DoliDB		$db				Database handler
 * @param	int			$socid			Thirdparty id
 * @param	string		$type			'customer' or 'supplier'
 * @param	int			$year			Start year of the most recent fiscal year
 * @param	int			$nbofyear		Number of fiscal years shown
 * @param	int			$monthstart		First month of fiscal year
 * @param	string[]	$errors			Errors found while loading (output)
 * @return	array<int,array<int,array{amount:float,nb:int}>>	[fiscal year][month] => amount and count
 */
function thirdpartyMonthlyGetData($db, $socid, $type, $year, $nbofyear, $monthstart, &$errors)
{
	$data = array();

	$datestart = dol_get_first_day($year - $nbofyear, $monthstart, false);
	$dateend = dol_get_last_day(($monthstart > 1 ? $year + 1 : $year), ($monthstart > 1 ? $monthstart - 1 : 12), false);

	if ($type == 'supplier') {
		$sql = "SELECT date_format(f.datef, '%Y-%m') as dm, SUM(f.total_ht) as amount, COUNT(f.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " WHERE f.entity IN (".getEntity('supplier_invoice').")";
		$sql .= " AND f.fk_statut IN (".((int) FactureFournisseur::STATUS_VALIDATED).", ".((int) FactureFournisseur::STATUS_CLOSED).")";
	} else {
		$sql = "SELECT date_format(f.datef, '%Y-%m') as dm, SUM(f.total_ht) as amount, COUNT(f.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.fk_statut IN (".((int) Facture::STATUS_VALIDATED).", ".((int) Facture::STATUS_CLOSED).")";
	}
	$sql .= " AND f.fk_soc = ".((int) $socid);
	$sql .= " AND f.datef BETWEEN '".$db->idate($datestart)."' AND '".$db->idate($dateend)."'";
	$sql .= " GROUP BY dm";

	$resql = $db->query($sql);
	if (!$resql) {
		$errors[] = $db->lasterror();
		dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
		return $data;
	}
	while ($obj = $db->fetch_object($resql)) {
		$cy = (int) substr((string) $obj->dm, 0, 4);
		$m = (int) substr((string) $obj->dm, 5, 2);
		$fy = ($m >= $monthstart ? $cy : $cy - 1);
		$data[$fy][$m] = array('amount' => (float) price2num($obj->amount, 'MT'), 'nb' => (int) $obj->nb);
	}
	$db->free($resql);

	return $data;
}

/**
 * Return the delta in percent between two amounts, same rules as the turnover report.
 *
 * @param	float	$current	Current amount
 * @param	float	$previous	Previous amount
 * @return	float|string|null	Delta in percent, '-' if nothing to compare with, null if both are zero
 */
function thirdpartyMonthlyDelta($current, $previous)
{
	if ((float) price2num($previous, 'MT') == 0) {
		return ((float) price2num($current, 'MT') == 0 ? null : '-');
	}

	return (float) price2num(($current - $previous) / abs($previous) * 100, 1);
}

/**
 * Return the label of a fiscal year.
 *
 * @param	int		$year			Start year
 * @param	int		$monthstart		First month of fiscal year
 * @return	string					Label
 */
function thirdpartyMonthlyYearLabel($year, $monthstart)
{
	return ($monthstart > 1 ? $year.'-'.($year + 1) : (string) $year);
}

/**
 * Return the rows of the report, ready to display: one per month then the total.
 *
 * @param	array<int,array<int,array{amount:float,nb:int}>>	$data			Data from thirdpartyMonthlyGetData()
 * @param	int[]												$years			Fiscal years shown, most recent first
 * @param	int													$monthstart		First month of fiscal year
 * @param	int													$now			Current date, no delta for months after it
 * @return	array<int,array{month:int,cells:array<int,array{amount:float,nb:int,delta:float|string|null}>}>	Rows, month 0 is the total
 */
function thirdpartyMonthlyRows($data, $years, $monthstart, $now)
{
	$rows = array();
	$totals = array();
	foreach ($years as $fy) {
		$totals[$fy] = array('amount' => 0.0, 'nb' => 0, 'previous' => 0.0);
	}

	for ($i = 0; $i < 12; $i++) {
		$m = (($monthstart - 1 + $i) % 12) + 1;
		$cells = array();
		foreach ($years as $fy) {
			$amount = (isset($data[$fy][$m]) ? $data[$fy][$m]['amount'] : 0.0);
			$nb = (isset($data[$fy][$m]) ? $data[$fy][$m]['nb'] : 0);
			$previous = (isset($data[$fy - 1][$m]) ? $data[$fy - 1][$m]['amount'] : 0.0);
			$future = (dol_mktime(0, 0, 0, $m, 1, ($m >= $monthstart ? $fy : $fy + 1)) > $now);
			$cells[$fy] = array('amount' => $amount, 'nb' => $nb, 'delta' => ($future ? null : thirdpartyMonthlyDelta($amount, $previous)));
			$totals[$fy]['amount'] = (float) price2num($totals[$fy]['amount'] + $amount, 'MT');
			$totals[$fy]['nb'] += $nb;
			if (!$future) {
				// Total delta compares the same months of the previous year
				$totals[$fy]['previous'] = (float) price2num($totals[$fy]['previous'] + $previous, 'MT');
			}
		}
		$rows[] = array('month' => $m, 'cells' => $cells);
	}

	$cells = array();
	foreach ($years as $fy) {
		$cells[$fy] = array('amount' => $totals[$fy]['amount'], 'nb' => $totals[$fy]['nb'], 'delta' => thirdpartyMonthlyDelta($totals[$fy]['amount'], $totals[$fy]['previous']));
	}
	$rows[] = array('month' => 0, 'cells' => $cells);

	return $rows;
}

/**
 * Return a delta formatted for output.
 *
 * @param	float|string|null	$delta			Delta in percent, or a label
 * @param	Translate			$outputlangs	Output language
 * @return	string								Formatted delta, '' if none
 */
function thirdpartyMonthlyDeltaLabel($delta, $outputlangs)
{
	if ($delta === null) {
		return '';
	}
	if (is_string($delta)) {
		return $delta;
	}

	return ($delta > 0 ? '+' : '').price($delta, 0, $outputlangs, 0, -1, 1).' %';
}

/**
 * Print the table header rows into the PDF.
 *
 * @param	TCPDF		$pdf			PDF instance
 * @param	Translate	$outputlangs	Output language
 * @param	int[]		$years			Fiscal years shown
 * @param	int			$monthstart		First month of fiscal year
 * @param	float		$wmonth			Width of month column
 * @param	float[]		$wyear			Widths of amount, count and delta columns
 * @param	int			$fontsize		Default font size
 * @return	void
 */
function thirdpartyMonthlyPdfTableHeader($pdf, $outputlangs, $years, $monthstart, $wmonth, $wyear, $fontsize)
{
	$pdf->SetFont('', 'B', $fontsize - 1);
	$pdf->SetFillColor(230, 230, 230);
	$x = $pdf->GetX();
	$pdf->Cell($wmonth, 12, $outputlangs->transnoentities("Month"), 1, 0, 'L', true);
	foreach ($years as $fy) {
		$pdf->Cell($wyear[0] + $wyear[1] + $wyear[2], 6, thirdpartyMonthlyYearLabel($fy, $monthstart), 1, 0, 'C', true);
	}
	$pdf->Ln();
	$pdf->SetX($x + $wmonth);
	foreach ($years as $fy) {
		$pdf->Cell($wyear[0], 6, $outputlangs->transnoentities("AmountHTShort"), 1, 0, 'R', true, '', 1);
		$pdf->Cell($wyear[1], 6, $outputlangs->transnoentities("NumberOfBills"), 1, 0, 'R', true, '', 1);
		$pdf->Cell($wyear[2], 6, $outputlangs->transnoentities("Delta"), 1, 0, 'R', true, '', 1);
	}
	$pdf->Ln();
	$pdf->SetFont('', '', $fontsize - 1);
}


/*
 * Actions
 */

$parameters = array('socid' => $socid, 'type' => $type, 'year' => $year);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook) && in_array($action, array('exportpdf', 'exportxlsx')) && !$permissiontoreadinvoice) {
	accessforbidden();
}

$years = array();
for ($i = 0; $i < $nbofyear; $i++) {
	$years[] = $year - $i;
}
$typelabel = (isset($typesallowed[$type]) ? $langs->transnoentitiesnoconv($type == 'supplier' ? "BillsSuppliers" : "BillsCustomers") : '');

if (empty($reshook) && $action == 'exportpdf' && $socid > 0 && $permissiontoreadinvoice) {
	$errors = array();
	$rows = thirdpartyMonthlyRows(thirdpartyMonthlyGetData($db, $socid, $type, $year, $nbofyear, $monthstart, $errors), $years, $monthstart, dol_now());
	foreach ($errors as $err) {
		dol_syslog('monthly_report.php exportpdf '.$err, LOG_WARNING);
	}

	$outputlangs = $langs;
	$reporttitle = $outputlangs->transnoentities("MonthlyReport");
	$pdfinit = reportPdfInit($outputlangs, $reporttitle.' - '.$object->name, $reporttitle, 'L');
	$pdf = $pdfinit['pdf'];
	$margin = $pdfinit['margin'];
	$pageheight = $pdfinit['pageheight'];
	$usablewidth = $pdfinit['usablewidth'];
	$fontsize = $pdfinit['fontsize'];
	$wmonth = round($usablewidth * 0.10, 2);
	$wy = ($usablewidth - $wmonth) / $nbofyear;
	$wyear = array(round($wy * 0.40, 2), round($wy * 0.32, 2), round($wy * 0.28, 2));

	$posy = reportPdfCompanyHeader($pdf, $outputlangs, $mysoc, $margin, $fontsize);

	// Title block
	$pdf->SetXY($margin, $posy);
	$pdf->SetFont('', 'B', $fontsize + 3);
	$pdf->Cell($usablewidth, 8, $outputlangs->convToOutputCharset($reporttitle), 0, 1, 'L');
	$pdf->SetFont('', 'B', $fontsize);
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("ThirdParty").': '.$outputlangs->convToOutputCharset((string) $object->name), 0, 1, 'L');
	$pdf->SetFont('', '', $fontsize - 1);
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("Type").': '.$outputlangs->convToOutputCharset($typelabel), 0, 1, 'L');
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("Date").': '.dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputlangs), 0, 1, 'L');
	$pdf->Ln(3);

	thirdpartyMonthlyPdfTableHeader($pdf, $outputlangs, $years, $monthstart, $wmonth, $wyear, $fontsize);

	$rowheight = 5;
	foreach ($rows as $row) {
		if ($pdf->GetY() + $rowheight > $pageheight - $margin) {
			$pdf->AddPage();
			thirdpartyMonthlyPdfTableHeader($pdf, $outputlangs, $years, $monthstart, $wmonth, $wyear, $fontsize);
		}
		$istotal = ($row['month'] == 0);
		$pdf->SetFont('', ($istotal ? 'B' : ''), $fontsize - 1);
		$pdf->Cell($wmonth, $rowheight, $outputlangs->transnoentities($istotal ? "Total" : 'Month'.sprintf('%02d', $row['month'])), 1, 0, 'L');
		foreach ($years as $fy) {
			$cell = $row['cells'][$fy];
			$pdf->Cell($wyear[0], $rowheight, ($cell['nb'] || $istotal ? price($cell['amount'], 0, $outputlangs) : ''), 1, 0, 'R');
			$pdf->Cell($wyear[1], $rowheight, ($cell['nb'] || $istotal ? (string) $cell['nb'] : ''), 1, 0, 'R');
			$pdf->Cell($wyear[2], $rowheight, thirdpartyMonthlyDeltaLabel($cell['delta'], $outputlangs), 1, 0, 'R');
		}
		$pdf->Ln();
	}

	$filename = reportFileName($reporttitle, ((string) $object->name !== '' ? (string) $object->name : (string) $object->id), 'pdf');
	$pdf->Output($filename, 'I');

	$db->close();
	exit;
}

if (empty($reshook) && $action == 'exportxlsx' && $socid > 0 && $permissiontoreadinvoice) {
	if (!class_exists('ZipArchive')) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorPHPNeedModule', 'zip'), null, 'errors');
		header('Location: '.$_SERVER["PHP_SELF"].'?socid='.((int) $socid).'&type='.urlencode($type));
		exit;
	}

	$errors = array();
	$rows = thirdpartyMonthlyRows(thirdpartyMonthlyGetData($db, $socid, $type, $year, $nbofyear, $monthstart, $errors), $years, $monthstart, dol_now());
	foreach ($errors as $err) {
		dol_syslog('monthly_report.php exportxlsx '.$err, LOG_WARNING);
	}

	$reporttitle = $langs->transnoentitiesnoconv("MonthlyReport");
	$spreadsheet = reportXlsxInit($langs, $reporttitle.' - '.$object->name, (string) $object->name);
	$sheet = $spreadsheet->getActiveSheet();
	$lastcol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1 + 3 * $nbofyear);

	// Header: logo on the left, company info on the right (same as the PDF)
	$headrows = reportXlsxCompanyHeader($sheet, $langs, $mysoc, 'E', $lastcol);

	// Title block
	$row = $headrows + 2;
	$sheet->setCellValue('A'.$row, $reporttitle);
	$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
	$row++;
	$sheet->setCellValueExplicit('A'.$row, $langs->transnoentitiesnoconv("ThirdParty").': '.$object->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$sheet->getStyle('A'.$row)->getFont()->setBold(true);
	$row++;
	$sheet->setCellValueExplicit('A'.$row, $langs->transnoentitiesnoconv("Type").': '.$typelabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$row++;
	$sheet->setCellValueExplicit('A'.$row, $langs->transnoentitiesnoconv("Date").': '.dol_print_date(dol_now(), 'dayhour', 'tzuser', $langs), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$row += 2;

	// Table header: years, then amount, count and delta per year
	$headrow = $row;
	$sheet->setCellValue('A'.$headrow, $langs->transnoentitiesnoconv("Month"));
	$sheet->mergeCells('A'.$headrow.':A'.($headrow + 1));
	foreach ($years as $i => $fy) {
		$col = 2 + 3 * $i;
		$c0 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
		$c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2);
		$sheet->setCellValueExplicit($c0.$headrow, thirdpartyMonthlyYearLabel($fy, $monthstart), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->mergeCells($c0.$headrow.':'.$c2.$headrow);
		$sheet->getStyle($c0.$headrow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		$sheet->setCellValue($c0.($headrow + 1), $langs->transnoentitiesnoconv("AmountHTShort"));
		$sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1).($headrow + 1), $langs->transnoentitiesnoconv("NumberOfBills"));
		$sheet->setCellValue($c2.($headrow + 1), $langs->transnoentitiesnoconv("Delta"));
	}
	$sheet->getStyle('A'.$headrow.':'.$lastcol.($headrow + 1))->getFont()->setBold(true);
	$sheet->getStyle('B'.($headrow + 1).':'.$lastcol.($headrow + 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
	$sheet->freezePane('B'.($headrow + 2));

	$firstrow = $headrow + 2;
	$row = $firstrow;
	foreach ($rows as $datarow) {
		$istotal = ($datarow['month'] == 0);
		$sheet->setCellValue('A'.$row, $langs->transnoentitiesnoconv($istotal ? "Total" : 'Month'.sprintf('%02d', $datarow['month'])));
		foreach ($years as $i => $fy) {
			$col = 2 + 3 * $i;
			$cell = $datarow['cells'][$fy];
			$camount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
			$cnb = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
			if ($istotal) {
				$sheet->setCellValue($camount.$row, '=SUM('.$camount.$firstrow.':'.$camount.($row - 1).')');
				$sheet->setCellValue($cnb.$row, '=SUM('.$cnb.$firstrow.':'.$cnb.($row - 1).')');
			} elseif ($cell['nb']) {
				$sheet->setCellValue($camount.$row, $cell['amount']);
				$sheet->setCellValue($cnb.$row, $cell['nb']);
			}
			$cdelta = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2);
			if (is_string($cell['delta'])) {
				$sheet->setCellValueExplicit($cdelta.$row, $cell['delta'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$sheet->getStyle($cdelta.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
			} elseif ($cell['delta'] !== null) {
				$sheet->setCellValue($cdelta.$row, $cell['delta'] / 100);
			}
		}
		if ($istotal) {
			$sheet->getStyle('A'.$row.':'.$lastcol.$row)->getFont()->setBold(true);
		}
		$row++;
	}
	$last = $row - 1;

	foreach ($years as $i => $fy) {
		$col = 2 + 3 * $i;
		$sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col).$firstrow.':'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col).$last)->getNumberFormat()->setFormatCode('#,##0.00');
		$sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2).$firstrow.':'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2).$last)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
		$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col))->setWidth(14);
		$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1))->setWidth(10);
		$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2))->setWidth(10);
	}
	$sheet->getColumnDimension('A')->setWidth(14);

	// Print landscape on one page width
	$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
	$sheet->getPageSetup()->setFitToPage(true);
	$sheet->getPageSetup()->setFitToWidth(1);
	$sheet->getPageSetup()->setFitToHeight(0);

	$filename = reportFileName($reporttitle, ((string) $object->name !== '' ? (string) $object->name : (string) $object->id), 'xlsx');
	reportXlsxOutput($spreadsheet, $filename);

	$db->close();
	exit;
}


/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("MonthlyReport");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name.' - '.$langs->trans("MonthlyReport");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-monthly-report');

if ($socid > 0 && $object->id > 0) {
	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'monthlyreport', $langs->trans("ThirdParty"), -1, 'company');
	dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');
	print dol_get_fiche_end();

	if ($permissiontoreadinvoice) {
		$param = '&socid='.((int) $socid).'&type='.urlencode($type);

		$morehtmlright = reportExportButtons($param, 'thirdpartymonthlyreport');
		print load_fiche_titre($langs->trans("MonthlyReport"), $morehtmlright);

		// Filter
		print '<form method="GET" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'" name="thirdpartymonthlyreportfilter">';
		print '<input type="hidden" name="socid" value="'.((int) $socid).'">';
		print '<div class="inline-block valignmiddle marginbottomonly">';
		print $form->selectarray('type', $typesallowed, $type, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
		print ' <input type="submit" class="button small" value="'.dol_escape_htmltag($langs->trans("Refresh")).'">';
		print '</div>';
		print '</form>';

		$errors = array();
		$rows = thirdpartyMonthlyRows(thirdpartyMonthlyGetData($db, $socid, $type, $year, $nbofyear, $monthstart, $errors), $years, $monthstart, dol_now());

		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td rowspan="2">'.$langs->trans("Month").'</td>';
		foreach ($years as $fy) {
			print '<td colspan="3" class="center borderrightlight">'.dol_escape_htmltag(thirdpartyMonthlyYearLabel($fy, $monthstart)).'</td>';
		}
		print '</tr>';
		print '<tr class="liste_titre">';
		foreach ($years as $fy) {
			print '<td class="right">'.$langs->trans("AmountHTShort").'</td>';
			print '<td class="right">'.$langs->trans("NumberOfBills").'</td>';
			print '<td class="right borderrightlight">'.$langs->trans("Delta").'</td>';
		}
		print '</tr>';

		foreach ($errors as $err) {
			print '<tr class="oddeven"><td colspan="'.(1 + 3 * $nbofyear).'"><span class="error">'.dol_escape_htmltag($err).'</span></td></tr>';
		}

		foreach ($rows as $row) {
			$istotal = ($row['month'] == 0);
			print '<tr class="'.($istotal ? 'liste_total' : 'oddeven').'">';
			print '<td>'.$langs->trans($istotal ? "Total" : 'Month'.sprintf('%02d', $row['month'])).'</td>';
			foreach ($row['cells'] as $cell) {
				print '<td class="right nowraponall"><span class="amount">'.($cell['nb'] || $istotal ? price($cell['amount']) : '').'</span></td>';
				print '<td class="right">'.($cell['nb'] || $istotal ? $cell['nb'] : '').'</td>';
				print '<td class="right nowraponall borderrightlight">'.dol_escape_htmltag(thirdpartyMonthlyDeltaLabel($cell['delta'], $langs)).'</td>';
			}
			print '</tr>';
		}

		print '</table>';
		print '</div>';
	}
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
