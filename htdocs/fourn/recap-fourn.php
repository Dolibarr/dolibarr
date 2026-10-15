<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019 Pierre Ardoin <mapiolca@me.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Nick Fragoulis
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
 *  	\file       htdocs/fourn/recap-fourn.php
 *		\ingroup    fournisseur
 *		\brief      Supplier statement page (invoices, payments and running balance)
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';


// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'compta'));

$action = GETPOST('action', 'aZ09');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');
$search_date_start = GETPOSTDATE('search_date_start', 'getpost'); // Use tzserver because invoice date is a date without hour
$search_date_end = GETPOSTDATE('search_date_end', 'getpostend');
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_date_start = '';
	$search_date_end = '';
}

// Security check
$socid = GETPOSTINT("socid");
if ($user->isExternalUser()) {
	$action = '';
	$socid = $user->isExternalUser();
}
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('supplierbalencelist', 'globalcard'));

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "f.datef,f.rowid"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "DESC";
}

// Export of the statement. Test on permission already done
$permissiontoreadinvoice = ((isModEnabled("fournisseur") && $user->hasRight("fournisseur", "facture", "lire") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_invoice") && $user->hasRight("supplier_invoice", "lire")));

// Date filter as url parameters
$dateparam = '';
foreach (array('search_date_start' => $search_date_start, 'search_date_end' => $search_date_end) as $dateprefix => $datets) {
	if ($datets) {
		$dateparam .= '&' . $dateprefix . 'day=' . dol_print_date($datets, '%d') . '&' . $dateprefix . 'month=' . dol_print_date($datets, '%m') . '&' . $dateprefix . 'year=' . dol_print_date($datets, '%Y');
	}
}


/**
 * Build the supplier statement lines (invoices and payments) with running balance.
 *
 * @param	DoliDB		$db				Database handler
 * @param	Societe		$societe		Thirdparty
 * @param	string		$sortfield		Sort field
 * @param	string		$sortorder		Sort order
 * @param	int|''		$datestart		Start date filter (timestamp), '' for none
 * @param	int|''		$dateend		End date filter (timestamp), '' for none
 * @param	float		$opening		Balance before $datestart (output)
 * @return	array<int,array<string,mixed>>	Lines sorted on $sortorder, with an opening balance line when $datestart is set
 */
function recapFournGetData($db, $societe, $sortfield, $sortorder, $datestart, $dateend, &$opening)
{
	global $langs;

	$userstatic = new User($db);

	/** @var array<string|int,mixed> $TData */
	$TData = array();

	$sql = "SELECT s.nom, s.rowid as socid, f.ref_supplier, f.total_ttc, f.datef as df,";
	$sql .= " f.paye as paye, f.fk_statut as statut, f.rowid as facid,";
	$sql .= " u.login, u.rowid as userid";
	$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s," . MAIN_DB_PREFIX . "facture_fourn as f," . MAIN_DB_PREFIX . "user as u";
	$sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = " . ((int) $societe->id);
	$sql .= " AND f.entity IN (" . getEntity("facture_fourn") . ")"; // Recognition of the entity attributed to this invoice for Multicompany
	$sql .= " AND f.fk_user_valid = u.rowid";
	$sql .= $db->order($sortfield, $sortorder);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		// Loop over each invoice
		for ($i = 0; $i < $num; $i++) {
			$objf = $db->fetch_object($resql);

			$fac = new FactureFournisseur($db);
			$ret = $fac->fetch($objf->facid);
			if ($ret < 0) {
				print $fac->error . "<br>";
				continue;
			}
			$totalpaid = $fac->getSommePaiement();

			$userstatic->id = $objf->userid;
			$userstatic->login = $objf->login;

			$values = array(
				'fk_facture' => $objf->facid,
				'date' => $fac->date,
				'datefieldforsort' => $fac->date . '-' . $fac->ref,
				'link' => $fac->getNomUrl(1),
				'status' => $fac->getLibStatut(2, $totalpaid),
				'amount' => $fac->total_ttc,
				'author' => $userstatic->getLoginUrl(1),
				'ref' => $fac->ref . ($fac->ref_supplier ? ' (' . $fac->ref_supplier . ')' : ''),
				'statuslabel' => dol_string_nohtmltag($fac->getLibStatut(1, $totalpaid))
			);

			$TData[] = $values;

			// Payments
			$sql = "SELECT p.rowid, p.ref, p.datep as dp, pf.amount, p.statut,";
			$sql .= " p.fk_user_author, u.login, u.rowid as userid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as pf,";
			$sql .= " " . MAIN_DB_PREFIX . "paiementfourn as p";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON p.fk_user_author = u.rowid";
			$sql .= " WHERE pf.fk_paiementfourn = p.rowid";
			$sql .= " AND pf.fk_facturefourn = " . ((int) $fac->id);
			$sql .= " ORDER BY p.datep ASC, p.rowid ASC";

			$resqlp = $db->query($sql);
			if ($resqlp) {
				$nump = $db->num_rows($resqlp);
				$j = 0;

				while ($j < $nump) {
					$objp = $db->fetch_object($resqlp);

					$paymentstatic = new PaiementFourn($db);
					$paymentstatic->id = $objp->rowid;
					$paymentstatic->ref = $objp->ref;

					$userstatic->id = $objp->userid;
					$userstatic->login = $objp->login;

					$values = array(
						'fk_paiement' => $objp->rowid,
						'date' => $db->jdate($objp->dp),
						'datefieldforsort' => $db->jdate($objp->dp) . '-' . $fac->ref,
						'link' => $langs->trans("Payment") . ' ' . $paymentstatic->getNomUrl(1),
						'status' => '',
						'amount' => -$objp->amount,
						'author' => $userstatic->getLoginUrl(1),
						'ref' => $langs->transnoentitiesnoconv("Payment") . ' ' . $objp->ref . ' (' . $fac->ref . ')',
						'statuslabel' => ''
					);

					$TData[] = $values;

					$j++;
				}

				$db->free($resqlp);
			} else {
				dol_print_error($db);
			}
		}
	} else {
		dol_print_error($db);
	}

	$opening = 0.0;
	if (empty($TData)) {
		return $TData;
	}

	// Sort array by date ASC to calculate balance
	$TData = dol_sort_array($TData, 'datefieldforsort', 'ASC');

	// Balance calculation
	$balance = 0;
	foreach (array_keys($TData) as $key) {
		$balance += $TData[$key]['amount'];
		if (!array_key_exists('balance', $TData[$key])) {
			$TData[$key]['balance'] = 0;
		}
		$TData[$key]['balance'] += $balance;
	}

	// Date filter. The balance was computed on the full history, so rows keep their real balance.
	if ($datestart || $dateend) {
		$filtered = array();
		foreach ($TData as $row) {
			if ($datestart && (int) $row['date'] < $datestart) {
				$opening = (float) $row['balance'];
				continue;
			}
			if ($dateend && (int) $row['date'] > $dateend) {
				continue;
			}
			$filtered[] = $row;
		}
		if ($datestart && (!empty($filtered) || (float) price2num($opening, 'MT') != 0)) {
			$filtered[] = array(
				'isopening' => true,
				'date' => $datestart,
				'datefieldforsort' => '',
				'amount' => 0,
				'ref' => $langs->transnoentitiesnoconv("PreviousBalance"),
				'balance' => $opening
			);
		}
		$TData = $filtered;
	}

	// Resorte array to have elements on the required $sortorder
	$TData = dol_sort_array($TData, 'datefieldforsort', $sortorder);

	return $TData;
}

/**
 * Print the statement table header row into the PDF.
 *
 * @param	TCPDF		$pdf			PDF instance
 * @param	Translate	$outputlangs	Output language
 * @param	float[]		$widths			Column widths
 * @param	int			$fontsize		Default font size
 * @return	void
 */
function recapFournPdfTableHeader($pdf, $outputlangs, $widths, $fontsize)
{
	$pdf->SetFont('', 'B', $fontsize - 1);
	$pdf->SetFillColor(230, 230, 230);
	$pdf->Cell($widths[0], 6, $outputlangs->transnoentities("Date"), 1, 0, 'C', true);
	$pdf->Cell($widths[1], 6, $outputlangs->transnoentities("Element"), 1, 0, 'L', true);
	$pdf->Cell($widths[2], 6, $outputlangs->transnoentities("Status"), 1, 0, 'L', true);
	$pdf->Cell($widths[3], 6, $outputlangs->transnoentities("Debit"), 1, 0, 'R', true);
	$pdf->Cell($widths[4], 6, $outputlangs->transnoentities("Credit"), 1, 0, 'R', true);
	$pdf->Cell($widths[5], 6, $outputlangs->transnoentities("Balance"), 1, 1, 'R', true);
	$pdf->SetFont('', '', $fontsize - 1);
}


/*
 * Actions
 */

if ($action == 'exportpdf' && $socid > 0 && $permissiontoreadinvoice) {
	$societe = new Societe($db);
	$societe->fetch($socid);
	$opening = 0.0;
	$TData = recapFournGetData($db, $societe, $sortfield, $sortorder, $search_date_start, $search_date_end, $opening);

	$outputlangs = $langs;
	$pdfinit = reportPdfInit($outputlangs, (string) $societe->name, $outputlangs->transnoentities("SupplierPreview"));
	$pdf = $pdfinit['pdf'];
	$margin = $pdfinit['margin'];
	$pageheight = $pdfinit['pageheight'];
	$usablewidth = $pdfinit['usablewidth'];
	$fontsize = $pdfinit['fontsize'];
	// Date, Element, Status, Debit, Credit, Balance
	$ratios = array(0.12, 0.30, 0.16, 0.14, 0.14, 0.14);
	$widths = array();
	foreach ($ratios as $ratio) {
		$widths[] = round($usablewidth * $ratio, 2);
	}

	$posy = reportPdfCompanyHeader($pdf, $outputlangs, $mysoc, $margin, $fontsize);

	// Title block
	$pdf->SetXY($margin, $posy);
	$pdf->SetFont('', 'B', $fontsize + 3);
	$pdf->Cell($usablewidth, 8, $outputlangs->convToOutputCharset($outputlangs->transnoentities("SupplierPreview")), 0, 1, 'L');
	$supplierlabel = $societe->name . ($societe->code_fournisseur ? ' (' . $societe->code_fournisseur . ')' : '');
	$pdf->SetFont('', 'B', $fontsize);
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("ThirdParty") . ': ' . $outputlangs->convToOutputCharset($supplierlabel), 0, 1, 'L');
	$pdf->SetFont('', '', $fontsize - 1);
	$periodlabel = reportPeriodLabel($outputlangs, $search_date_start, $search_date_end);
	if ($periodlabel !== '') {
		$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("Period") . ': ' . $outputlangs->convToOutputCharset($periodlabel), 0, 1, 'L');
	}
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("Date") . ': ' . dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputlangs), 0, 1, 'L');
	$pdf->Ln(3);

	recapFournPdfTableHeader($pdf, $outputlangs, $widths, $fontsize);

	$totalDebit = 0;
	$totalCredit = 0;
	$rowheight = 5;
	if (empty($TData)) {
		$pdf->Cell($usablewidth, $rowheight, $outputlangs->transnoentities("NoInvoice"), 1, 1, 'L');
	}
	foreach ($TData as $data) {
		if ($pdf->GetY() + $rowheight > $pageheight - $margin) {
			$pdf->AddPage();
			recapFournPdfTableHeader($pdf, $outputlangs, $widths, $fontsize);
		}
		if (!empty($data['isopening'])) {
			$pdf->SetFont('', 'B', $fontsize - 1);
			$pdf->Cell($widths[0], $rowheight, dol_print_date($data['date'], 'day', 'tzserver', $outputlangs), 1, 0, 'C');
			$pdf->Cell($widths[1] + $widths[2], $rowheight, $outputlangs->convToOutputCharset((string) $data['ref']), 1, 0, 'L', false, '', 1);
			$pdf->Cell($widths[3], $rowheight, '', 1, 0, 'R');
			$pdf->Cell($widths[4], $rowheight, '', 1, 0, 'R');
			$pdf->Cell($widths[5], $rowheight, price(price2num((float) $data['balance'], 'MT'), 0, $outputlangs), 1, 1, 'R');
			$pdf->SetFont('', '', $fontsize - 1);
			continue;
		}
		$amount = (float) price2num($data['amount'], 'MT');
		$debit = ($amount > 0) ? $amount : 0;
		$credit = ($amount < 0) ? abs($amount) : 0;
		$totalDebit += $debit;
		$totalCredit += $credit;

		$pdf->Cell($widths[0], $rowheight, dol_print_date($data['date'], 'day', 'auto', $outputlangs), 1, 0, 'C');
		$pdf->Cell($widths[1], $rowheight, $outputlangs->convToOutputCharset((string) ($data['ref'] ?? '')), 1, 0, 'L', false, '', 1);
		$pdf->Cell($widths[2], $rowheight, $outputlangs->convToOutputCharset((string) ($data['statuslabel'] ?? '')), 1, 0, 'L', false, '', 1);
		$pdf->Cell($widths[3], $rowheight, ($debit ? price($debit, 0, $outputlangs) : ''), 1, 0, 'R');
		$pdf->Cell($widths[4], $rowheight, ($credit ? price($credit, 0, $outputlangs) : ''), 1, 0, 'R');
		$pdf->Cell($widths[5], $rowheight, price(price2num((float) $data['balance'], 'MT'), 0, $outputlangs), 1, 1, 'R');
	}

	if (!empty($TData)) {
		if ($pdf->GetY() + $rowheight > $pageheight - $margin) {
			$pdf->AddPage();
		}
		$pdf->SetFont('', 'B', $fontsize - 1);
		$pdf->Cell($widths[0] + $widths[1] + $widths[2], $rowheight, $outputlangs->transnoentities("Total"), 1, 0, 'L');
		$pdf->Cell($widths[3], $rowheight, price($totalDebit, 0, $outputlangs), 1, 0, 'R');
		$pdf->Cell($widths[4], $rowheight, price($totalCredit, 0, $outputlangs), 1, 0, 'R');
		$pdf->Cell($widths[5], $rowheight, price(price2num($opening + $totalDebit - $totalCredit, 'MT'), 0, $outputlangs), 1, 1, 'R');
	}

	$filename = reportFileName($outputlangs->transnoentitiesnoconv("SupplierPreview"), ((string) $societe->name !== '' ? (string) $societe->name : (string) $societe->id), 'pdf');
	$pdf->Output($filename, 'I');

	$db->close();
	exit;
}


if ($action == 'exportxlsx' && $socid > 0 && $permissiontoreadinvoice) {
	if (!class_exists('ZipArchive')) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorPHPNeedModule', 'zip'), null, 'errors');
		header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . ((int) $socid) . ($dol_openinpopup ? '&dol_openinpopup=' . urlencode($dol_openinpopup) : ''));
		exit;
	}

	$societe = new Societe($db);
	$societe->fetch($socid);
	$opening = 0.0;
	$TData = recapFournGetData($db, $societe, $sortfield, $sortorder, $search_date_start, $search_date_end, $opening);

	$spreadsheet = reportXlsxInit($langs, $langs->transnoentitiesnoconv("SupplierPreview") . ' - ' . $societe->name, (string) $societe->name);
	$sheet = $spreadsheet->getActiveSheet();

	// Header: logo on the left, company info on the right (same as the PDF)
	$headrows = reportXlsxCompanyHeader($sheet, $langs, $mysoc, 'C', 'F');

	// Title block
	$row = $headrows + 2;
	$sheet->setCellValue('A' . $row, $langs->transnoentitiesnoconv("SupplierPreview"));
	$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
	$row++;
	$supplierlabel = $societe->name . ($societe->code_fournisseur ? ' (' . $societe->code_fournisseur . ')' : '');
	$sheet->setCellValueExplicit('A' . $row, $langs->transnoentitiesnoconv("ThirdParty") . ': ' . $supplierlabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$sheet->getStyle('A' . $row)->getFont()->setBold(true);
	$row++;
	$periodlabel = reportPeriodLabel($langs, $search_date_start, $search_date_end);
	if ($periodlabel !== '') {
		$sheet->setCellValueExplicit('A' . $row, $langs->transnoentitiesnoconv("Period") . ': ' . $periodlabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$row++;
	}
	$sheet->setCellValueExplicit('A' . $row, $langs->transnoentitiesnoconv("Date") . ': ' . dol_print_date(dol_now(), 'dayhour', 'tzuser', $langs), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$row += 2;

	// Table
	$headrow = $row;
	$headers = array('A' => "Date", 'B' => "Element", 'C' => "Status", 'D' => "Debit", 'E' => "Credit", 'F' => "Balance");
	foreach ($headers as $colletter => $key) {
		$sheet->setCellValue($colletter . $headrow, $langs->transnoentitiesnoconv($key));
	}
	$sheet->getStyle('A' . $headrow . ':F' . $headrow)->getFont()->setBold(true);
	$sheet->getStyle('D' . $headrow . ':F' . $headrow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
	$sheet->freezePane('A' . ($headrow + 1));

	$firstrow = $headrow + 1;
	$row = $firstrow;
	$openrow = 0;
	foreach ($TData as $data) {
		if (!empty($data['isopening'])) {
			$openrow = $row;
			$sheet->setCellValue('A' . $row, \PhpOffice\PhpSpreadsheet\Shared\Date::formattedPHPToExcel((int) dol_print_date($data['date'], '%Y'), (int) dol_print_date($data['date'], '%m'), (int) dol_print_date($data['date'], '%d')));
			$sheet->setCellValueExplicit('B' . $row, (string) $data['ref'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValue('F' . $row, (float) price2num((float) $data['balance'], 'MT'));
			$sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
			$row++;
			continue;
		}
		$amount = (float) price2num($data['amount'], 'MT');
		if (!empty($data['date'])) {
			$sheet->setCellValue('A' . $row, \PhpOffice\PhpSpreadsheet\Shared\Date::formattedPHPToExcel((int) dol_print_date($data['date'], '%Y'), (int) dol_print_date($data['date'], '%m'), (int) dol_print_date($data['date'], '%d')));
		}
		$sheet->setCellValueExplicit('B' . $row, (string) ($data['ref'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->setCellValueExplicit('C' . $row, (string) ($data['statuslabel'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		if ($amount > 0) {
			$sheet->setCellValue('D' . $row, $amount);
		} elseif ($amount < 0) {
			$sheet->setCellValue('E' . $row, abs($amount));
		}
		$sheet->setCellValue('F' . $row, (float) price2num((float) $data['balance'], 'MT'));
		$row++;
	}

	if ($row > $firstrow) {
		$last = $row - 1;
		$sheet->setCellValue('C' . $row, $langs->transnoentitiesnoconv("Total"));
		$sheet->setCellValue('D' . $row, '=SUM(D' . $firstrow . ':D' . $last . ')');
		$sheet->setCellValue('E' . $row, '=SUM(E' . $firstrow . ':E' . $last . ')');
		$sheet->setCellValue('F' . $row, ($openrow ? '=F' . $openrow . '+' : '=') . 'D' . $row . '-E' . $row);
		$sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
	}

	$sheet->getStyle('A' . $firstrow . ':A' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD);
	$sheet->getStyle('D' . $firstrow . ':F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
	foreach (array('A' => 12, 'B' => 34, 'C' => 20, 'D' => 14, 'E' => 14, 'F' => 14) as $colletter => $colwidth) {
		$sheet->getColumnDimension($colletter)->setWidth($colwidth);
	}

	// Print on one page width
	$sheet->getPageSetup()->setFitToPage(true);
	$sheet->getPageSetup()->setFitToWidth(1);
	$sheet->getPageSetup()->setFitToHeight(0);

	$filename = reportFileName($langs->transnoentitiesnoconv("SupplierPreview"), ((string) $societe->name !== '' ? (string) $societe->name : (string) $societe->id), 'xlsx');
	reportXlsxOutput($spreadsheet, $filename);

	$db->close();
	exit;
}


/*
 * View
 */

$form = new Form($db);
$userstatic = new User($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-fourn page-recap-fourn');

if ($socid > 0) {
	$societe = new Societe($db);
	$societe->fetch($socid);

	/*
	 * Show tabs
	 */
	if (empty($dol_openinpopup)) {
		$head = societe_prepare_head($societe);

		print dol_get_fiche_head($head, 'supplier', $langs->trans("ThirdParty"), 0, 'company');
		dol_banner_tab($societe, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');
		print dol_get_fiche_end();
	}

	if ((isModEnabled("fournisseur") && $user->hasRight("fournisseur", "facture", "lire") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_invoice") && $user->hasRight("supplier_invoice", "lire"))) {
		// Invoice list
		$morehtmlright = reportExportButtons('&socid=' . ((int) $socid) . '&sortfield=' . urlencode($sortfield) . '&sortorder=' . urlencode($sortorder) . $dateparam, 'recapfourn');
		print load_fiche_titre($langs->trans("SupplierPreview"), $morehtmlright);

		// Add parameter for sorting
		$param = '';
		if ($socid > 0) {
			$param .= '&socid=' . $socid;
		}
		if ($dol_openinpopup) {
			$param .= '&dol_openinpopup=' . urlencode($dol_openinpopup);
		}
		$param .= $dateparam;

		// Date filter
		print '<form method="GET" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '" name="recapfournfilter">';
		print '<input type="hidden" name="socid" value="' . ((int) $socid) . '">';
		if ($dol_openinpopup) {
			print '<input type="hidden" name="dol_openinpopup" value="' . dol_escape_htmltag($dol_openinpopup) . '">';
		}
		print '<input type="hidden" name="sortfield" value="' . dol_escape_htmltag($sortfield) . '">';
		print '<input type="hidden" name="sortorder" value="' . dol_escape_htmltag($sortorder) . '">';
		print '<div class="div-table-responsive-no-min">';
		print '<div class="inline-block valignmiddle nowrapfordate">';
		print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div> ';
		print '<div class="inline-block valignmiddle nowrapfordate">';
		print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div> ';
		print '<div class="inline-block valignmiddle">' . $form->showFilterButtons() . '</div>';
		print '</div>';
		print '</form>';

		print '<table class="noborder tagtable liste centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
		print '<td>' . $langs->trans("Element") . '</td>';
		print '<td>' . $langs->trans("Status") . '</td>';
		print '<td class="right">' . $langs->trans("Debit") . '</td>';
		print '<td class="right">' . $langs->trans("Credit") . '</td>';
		print '<td class="right">' . $langs->trans("Balance") . '</td>';
		print '<td class="right">' . $langs->trans("Author") . '</td>';
		print '</tr>';

		$opening = 0.0;
		$TData = recapFournGetData($db, $societe, $sortfield, $sortorder, $search_date_start, $search_date_end, $opening);

		if (empty($TData)) {
			print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoInvoice") . '</span></td></tr>';
		} else {
			$totalDebit = 0;
			$totalCredit = 0;

			// Display array
			foreach ($TData as $data) {
				if (!empty($data['isopening'])) {
					print '<tr class="oddeven">';
					print '<td class="center">' . dol_print_date($data['date'], 'day', 'tzserver') . '</td>';
					print '<td colspan="2"><b>' . dol_escape_htmltag((string) $data['ref']) . '</b></td>';
					print '<td></td><td></td>';
					print '<td class="right"><span class="amount"><b>' . price($data['balance']) . '</b></span></td>';
					print '<td></td>';
					print "</tr>\n";
					continue;
				}
				$html_class = '';
				if (!empty($data['fk_facture'])) {
					$html_class = 'facid-' . $data['fk_facture'];
				} elseif (!empty($data['fk_paiement'])) {
					$html_class = 'payid-' . $data['fk_paiement'];
				}

				print '<tr class="oddeven ' . $html_class . '">';

				$datedetail = dol_print_date($data['date'], 'dayhour');
				if (!empty($data['fk_facture'])) {
					$datedetail = dol_print_date($data['date'], 'day');
				}
				print '<td class="center" title="' . dol_escape_htmltag($datedetail) . '">';
				print dol_print_date($data['date'], 'day');
				print "</td>\n";

				print '<td>' . $data['link'] . "</td>\n";

				print '<td class="left">' . $data['status'] . '</td>';

				print '<td class="right">' . (($data['amount'] > 0) ? price(abs($data['amount'])) : '') . "</td>\n";

				$totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;

				print '<td class="right">' . (($data['amount'] > 0) ? '' : price(abs($data['amount']))) . "</td>\n";
				$totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);

				// Balance
				print '<td class="right"><span class="amount">' . price($data['balance']) . "</span></td>\n";

				// Author
				print '<td class="nowrap right">';
				print $data['author'];
				print '</td>';

				print "</tr>\n";
			}

			print '<tr class="liste_total">';
			print '<td colspan="3">&nbsp;</td>';
			print '<td class="right">' . price($totalDebit) . '</td>';
			print '<td class="right">' . price($totalCredit) . '</td>';
			print '<td class="right">' . price(price2num($opening + $totalDebit - $totalCredit, 'MT')) . '</td>';
			print '<td></td>';
			print "</tr>\n";
		}

		print "</table>";
	}
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
