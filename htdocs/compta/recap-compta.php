<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2017       Pierre-Henry Favre      <support@atm-consulting.fr>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/compta/recap-compta.php
 *	\ingroup    compta
 *  \brief      Customer statement page (invoices, payments and running balance)
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "compta", "accountancy"));

$action = GETPOST('action', 'aZ09');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');
$search_date_start = GETPOSTDATE('search_date_start', 'getpost'); // Use tzserver because invoice date is a date without hour
$search_date_end = GETPOSTDATE('search_date_end', 'getpostend');
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_date_start = '';
	$search_date_end = '';
}

$id = GETPOST('id') ? GETPOSTINT('id') : GETPOSTINT('socid');

// Security check
if ($user->socid > 0) {
	$id = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('recapcomptacard', 'globalcard'));

$result = restrictedArea($user, 'societe', $id, '&societe');

$object = new Societe($db);
if ($id > 0) {
	$object->fetch($id);
}


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


$arrayfields = array(
	'f.datef' => array('label' => "Date", 'checked' => 1),
	//...
);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('supplierbalencelist', 'globalcard'));

$permissiontoreadinvoice = (isModEnabled('invoice') && $user->hasRight('facture', 'lire'));

// Date filter as URL params, to keep it on sort links and exports
$dateparam = '';
foreach (array('search_date_start' => $search_date_start, 'search_date_end' => $search_date_end) as $dateprefix => $datets) {
	if ($datets) {
		$dateparam .= '&'.$dateprefix.'day='.dol_print_date($datets, '%d').'&'.$dateprefix.'month='.dol_print_date($datets, '%m').'&'.$dateprefix.'year='.dol_print_date($datets, '%Y');
	}
}


/**
 * Build the customer statement lines (invoices and payments) with running balance.
 *
 * @param	DoliDB		$db				Database handler
 * @param	Societe		$object			Thirdparty
 * @param	HookManager	$hookmanager	Hook manager
 * @param	int			$id				Thirdparty id
 * @param	string		$sortfield		Sort field
 * @param	string		$sortorder		Sort order
 * @param	string[]	$errors			Errors found while loading (output)
 * @param	int|''		$datestart		Start date filter (timestamp), '' for none
 * @param	int|''		$dateend		End date filter (timestamp), '' for none
 * @param	float		$opening		Balance before $datestart (output)
 * @return	array<int,array<string,mixed>>	Lines sorted on $sortorder, preceded by an opening balance line when $datestart is set
 */
function recapComptaGetData($db, $object, $hookmanager, $id, $sortfield, $sortorder, &$errors, $datestart = '', $dateend = '', &$opening = 0.0)
{
	global $conf, $langs;

	$userstatic = new User($db);

	/** @var array<int,array<string,mixed>> $TData */
	$TData = array();

	$sql = "SELECT s.nom, s.rowid as socid, f.ref, f.total_ttc, f.datef as df,";
	$sql .= " f.paye as paye, f.fk_statut as statut, f.rowid as facid,";
	$sql .= " u.login, u.rowid as userid";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON f.fk_user_valid = u.rowid";
	$sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = ".((int) $object->id);
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	// Validated or closed only: no draft, and no abandoned or replaced invoice (they are not owed, like in the thirdparty outstanding amount)
	$sql .= " AND f.fk_statut IN (".((int) Facture::STATUS_VALIDATED).", ".((int) Facture::STATUS_CLOSED).")";
	$sql .= $db->order($sortfield, $sortorder);

	$resql = $db->query($sql);
	if (!$resql) {
		$errors[] = $db->lasterror();
		dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
		return $TData;
	}

	$num = $db->num_rows($resql);

	// Loop over each invoice
	for ($i = 0; $i < $num; $i++) {
		$objf = $db->fetch_object($resql);

		$fac = new Facture($db);
		$ret = $fac->fetch($objf->facid);
		if ($ret < 0) {
			$errors[] = $fac->error;
			continue;
		}

		$alreadypaid = $fac->getSommePaiement();
		$alreadypaid += $fac->getSumDepositsUsed();
		$alreadypaid += $fac->getSumCreditNotesUsed();

		$userstatic->id = (int) $objf->userid;
		$userstatic->login = (string) $objf->login;

		$values = array(
			'fk_facture' => (int) $objf->facid,
			'date' => $fac->date,
			'datefieldforsort' => $fac->date.'-'.$fac->ref,
			'link' => $fac->getNomUrl(1),
			'status' => $fac->getLibStatut(2, $alreadypaid),
			'amount' => $fac->total_ttc,
			'author' => ($userstatic->id > 0 ? $userstatic->getLoginUrl(1) : ''),
			'ref' => $fac->ref,
			'statuslabel' => dol_string_nohtmltag($fac->getLibStatut(1, $alreadypaid))
		);

		$parameters = array('socid' => $id, 'values' => &$values, 'fac' => $fac, 'userstatic' => $userstatic);
		$reshook = $hookmanager->executeHooks('facdao', $parameters, $object); // Note that $parameters['values'] and $object may have been modified by some hooks
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		$TData[] = $values;

		// Payments
		$sql = "SELECT p.rowid, p.ref, p.datep as dp, pf.amount, p.statut,";
		$sql .= " p.fk_user_creat, u.login, u.rowid as userid";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf,";
		$sql .= " ".MAIN_DB_PREFIX."paiement as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_creat = u.rowid";
		$sql .= " WHERE pf.fk_paiement = p.rowid";
		$sql .= " AND p.entity = ".((int) $conf->entity);
		$sql .= " AND pf.fk_facture = ".((int) $fac->id);
		$sql .= " ORDER BY p.datep ASC, p.rowid ASC";

		$resqlp = $db->query($sql);
		if ($resqlp) {
			$nump = $db->num_rows($resqlp);
			$j = 0;

			while ($j < $nump) {
				$objp = $db->fetch_object($resqlp);

				$paymentstatic = new Paiement($db);
				$paymentstatic->id = $objp->rowid;

				$userstatic->id = $objp->userid;
				$userstatic->login = $objp->login;

				$values = array(
					'fk_paiement' => (int) $objp->rowid,
					'date' => $db->jdate($objp->dp),
					'datefieldforsort' => $db->jdate($objp->dp).'-'.$fac->ref,
					'link' => $langs->trans("Payment").' '.$paymentstatic->getNomUrl(1),
					'status' => '',
					'amount' => -$objp->amount,
					'author' => $userstatic->getLoginUrl(1),
					'ref' => $langs->transnoentitiesnoconv("Payment").' '.($objp->ref ? $objp->ref : $objp->rowid).' ('.$fac->ref.')',
					'statuslabel' => ''
				);

				$parameters = array('socid' => $id, 'values' => &$values, 'fac' => $fac, 'userstatic' => $userstatic, 'paymentstatic' => $paymentstatic);
				$reshook = $hookmanager->executeHooks('paydao', $parameters, $object); // Note that $parameters['values'] and $object may have been modified by some hooks
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				$TData[] = $values;

				$j++;
			}

			$db->free($resqlp);
		} else {
			$errors[] = $db->lasterror();
			dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
		}
	}
	$db->free($resql);

	if (empty($TData)) {
		return $TData;
	}

	// Sort array by date ASC to calculate balance
	$TData = dol_sort_array($TData, 'datefieldforsort', 'ASC');

	// Balance calculation
	$balance = 0.0;
	foreach (array_keys($TData) as $key) {
		$balance = (float) price2num($balance + (float) $TData[$key]['amount'], 'MT'); // Rounded at each step to avoid float drift
		$hookbalance = array_key_exists('balance', $TData[$key]) ? (float) $TData[$key]['balance'] : 0.0; // A hook may have set a balance
		$TData[$key]['balance'] = (float) price2num($hookbalance + $balance, 'MT');
	}

	// Date filter. The balance was computed on the full history, so rows keep their real balance.
	$opening = 0.0;
	if ($datestart || $dateend) {
		$filtered = array();
		foreach ($TData as $row) {
			$rowdate = (int) $row['date'];
			if ($datestart && $rowdate < $datestart) {
				$opening = (float) $row['balance'];
				continue;
			}
			if ($dateend && $rowdate > $dateend) {
				continue;
			}
			$filtered[] = $row;
		}
		if ($datestart && (!empty($filtered) || (float) price2num($opening, 'MT') != 0)) {
			$label = $langs->transnoentitiesnoconv("PreviousBalance");
			array_unshift($filtered, array(
				'isopening' => true,
				'date' => $datestart,
				'datefieldforsort' => '',
				'link' => dol_escape_htmltag($label),
				'status' => '',
				'amount' => 0.0,
				'author' => '',
				'ref' => $label,
				'statuslabel' => '',
				'balance' => $opening
			));
		}
		$TData = $filtered;
	}

	// DESC is the exact reverse of the ASC order used for the balance (rows with same sort key keep a consistent order)
	if (strtoupper($sortorder) == 'DESC') {
		$TData = array_reverse($TData);
	}

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
function recapComptaPdfTableHeader($pdf, $outputlangs, $widths, $fontsize)
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

$parameters = array('socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook) && in_array($action, array('exportpdf', 'exportxlsx')) && !$permissiontoreadinvoice) {
	accessforbidden();
}

if (empty($reshook) && $action == 'exportpdf' && $id > 0 && $permissiontoreadinvoice) {
	$errors = array();
	$opening = 0.0;
	$TData = recapComptaGetData($db, $object, $hookmanager, $id, $sortfield, $sortorder, $errors, $search_date_start, $search_date_end, $opening);
	foreach ($errors as $err) {
		dol_syslog('recap-compta.php exportpdf '.$err, LOG_WARNING);
	}

	$outputlangs = $langs;
	$pdfinit = reportPdfInit($outputlangs, (string) $object->name, $outputlangs->transnoentities("CustomerPreview"));
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
	$pdf->Cell($usablewidth, 8, $outputlangs->convToOutputCharset($outputlangs->transnoentities("CustomerPreview")), 0, 1, 'L');
	$customerlabel = $object->name.($object->code_client ? ' ('.$object->code_client.')' : '');
	$pdf->SetFont('', 'B', $fontsize);
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("ThirdParty").': '.$outputlangs->convToOutputCharset($customerlabel), 0, 1, 'L');
	$pdf->SetFont('', '', $fontsize - 1);
	$periodlabel = reportPeriodLabel($outputlangs, $search_date_start, $search_date_end);
	if ($periodlabel !== '') {
		$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("Period").': '.$outputlangs->convToOutputCharset($periodlabel), 0, 1, 'L');
	}
	$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("Date").': '.dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputlangs), 0, 1, 'L');
	$pdf->Ln(3);

	recapComptaPdfTableHeader($pdf, $outputlangs, $widths, $fontsize);

	$totalDebit = 0;
	$totalCredit = 0;
	$rowheight = 5;
	if (empty($TData)) {
		$pdf->Cell($usablewidth, $rowheight, $outputlangs->transnoentities("NoInvoice"), 1, 1, 'L');
	}
	foreach ($TData as $data) {
		if ($pdf->GetY() + $rowheight > $pageheight - $margin) {
			$pdf->AddPage();
			recapComptaPdfTableHeader($pdf, $outputlangs, $widths, $fontsize);
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

	$filename = reportFileName($outputlangs->transnoentitiesnoconv("CustomerPreview"), ((string) $object->name !== '' ? (string) $object->name : (string) $object->id), 'pdf');
	$pdf->Output($filename, 'I');

	$db->close();
	exit;
}


if (empty($reshook) && $action == 'exportxlsx' && $id > 0 && $permissiontoreadinvoice) {
	if (!class_exists('ZipArchive')) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorPHPNeedModule', 'zip'), null, 'errors');
		header('Location: '.$_SERVER["PHP_SELF"].'?socid='.((int) $id).($dol_openinpopup ? '&dol_openinpopup='.urlencode($dol_openinpopup) : ''));
		exit;
	}

	$errors = array();
	$opening = 0.0;
	$TData = recapComptaGetData($db, $object, $hookmanager, $id, $sortfield, $sortorder, $errors, $search_date_start, $search_date_end, $opening);
	foreach ($errors as $err) {
		dol_syslog('recap-compta.php exportxlsx '.$err, LOG_WARNING);
	}

	$spreadsheet = reportXlsxInit($langs, $langs->transnoentitiesnoconv("CustomerPreview").' - '.$object->name, (string) $object->name);
	$sheet = $spreadsheet->getActiveSheet();

	// Header: logo on the left, company info on the right (same as the PDF)
	$headrows = reportXlsxCompanyHeader($sheet, $langs, $mysoc, 'C', 'F');

	// Title block
	$row = $headrows + 2;
	$sheet->setCellValue('A'.$row, $langs->transnoentitiesnoconv("CustomerPreview"));
	$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
	$row++;
	$customerlabel = $object->name.($object->code_client ? ' ('.$object->code_client.')' : '');
	$sheet->setCellValueExplicit('A'.$row, $langs->transnoentitiesnoconv("ThirdParty").': '.$customerlabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$sheet->getStyle('A'.$row)->getFont()->setBold(true);
	$row++;
	$periodlabel = reportPeriodLabel($langs, $search_date_start, $search_date_end);
	if ($periodlabel !== '') {
		$sheet->setCellValueExplicit('A'.$row, $langs->transnoentitiesnoconv("Period").': '.$periodlabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$row++;
	}
	$sheet->setCellValueExplicit('A'.$row, $langs->transnoentitiesnoconv("Date").': '.dol_print_date(dol_now(), 'dayhour', 'tzuser', $langs), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
	$row += 2;

	// Table
	$headrow = $row;
	$headers = array('A' => "Date", 'B' => "Element", 'C' => "Status", 'D' => "Debit", 'E' => "Credit", 'F' => "Balance");
	foreach ($headers as $colletter => $key) {
		$sheet->setCellValue($colletter.$headrow, $langs->transnoentitiesnoconv($key));
	}
	$sheet->getStyle('A'.$headrow.':F'.$headrow)->getFont()->setBold(true);
	$sheet->getStyle('D'.$headrow.':F'.$headrow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
	$sheet->freezePane('A'.($headrow + 1));

	$firstrow = $headrow + 1;
	$row = $firstrow;
	$openrow = 0;
	foreach ($TData as $data) {
		if (!empty($data['isopening'])) {
			$openrow = $row;
			$sheet->setCellValue('A'.$row, \PhpOffice\PhpSpreadsheet\Shared\Date::formattedPHPToExcel((int) dol_print_date($data['date'], '%Y'), (int) dol_print_date($data['date'], '%m'), (int) dol_print_date($data['date'], '%d')));
			$sheet->setCellValueExplicit('B'.$row, (string) $data['ref'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValue('F'.$row, (float) price2num((float) $data['balance'], 'MT'));
			$sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
			$row++;
			continue;
		}
		$amount = (float) price2num($data['amount'], 'MT');
		if (!empty($data['date'])) {
			$sheet->setCellValue('A'.$row, \PhpOffice\PhpSpreadsheet\Shared\Date::formattedPHPToExcel((int) dol_print_date($data['date'], '%Y'), (int) dol_print_date($data['date'], '%m'), (int) dol_print_date($data['date'], '%d')));
		}
		$sheet->setCellValueExplicit('B'.$row, (string) ($data['ref'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->setCellValueExplicit('C'.$row, (string) ($data['statuslabel'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		if ($amount > 0) {
			$sheet->setCellValue('D'.$row, $amount);
		} elseif ($amount < 0) {
			$sheet->setCellValue('E'.$row, abs($amount));
		}
		$sheet->setCellValue('F'.$row, (float) price2num((float) $data['balance'], 'MT'));
		$row++;
	}

	if ($row > $firstrow) {
		$last = $row - 1;
		$sheet->setCellValue('C'.$row, $langs->transnoentitiesnoconv("Total"));
		$sheet->setCellValue('D'.$row, '=SUM(D'.$firstrow.':D'.$last.')');
		$sheet->setCellValue('E'.$row, '=SUM(E'.$firstrow.':E'.$last.')');
		$sheet->setCellValue('F'.$row, ($openrow ? '=F'.$openrow.'+' : '=').'D'.$row.'-E'.$row);
		$sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
	}

	$sheet->getStyle('A'.$firstrow.':A'.$row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD);
	$sheet->getStyle('D'.$firstrow.':F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
	foreach (array('A' => 12, 'B' => 34, 'C' => 20, 'D' => 14, 'E' => 14, 'F' => 14) as $colletter => $colwidth) {
		$sheet->getColumnDimension($colletter)->setWidth($colwidth);
	}

	// Print on one page width
	$sheet->getPageSetup()->setFitToPage(true);
	$sheet->getPageSetup()->setFitToWidth(1);
	$sheet->getPageSetup()->setFitToHeight(0);

	$filename = reportFileName($langs->transnoentitiesnoconv("CustomerPreview"), ((string) $object->name !== '' ? (string) $object->name : (string) $object->id), 'xlsx');
	reportXlsxOutput($spreadsheet, $filename);

	$db->close();
	exit;
}


/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Summary");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Summary");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url);

if ($id > 0) {
	$param = '';
	if ($id > 0) {
		$param .= '&socid='.$id;
	}
	if ($dol_openinpopup) {
		$param .= '&dol_openinpopup='.urlencode($dol_openinpopup);
	}
	$param .= $dateparam;

	if (empty($dol_openinpopup)) {
		$head = societe_prepare_head($object);

		print dol_get_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');
		dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 1);
		print dol_get_fiche_end();
	}

	if ($permissiontoreadinvoice) {
		// Invoice list
		$morehtmlright = reportExportButtons('&socid='.((int) $id).'&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder).$dateparam, 'recapcompta');
		print load_fiche_titre($langs->trans("CustomerPreview"), $morehtmlright);

		// Date filter
		print '<form method="GET" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'" name="recapcomptafilter">';
		print '<input type="hidden" name="socid" value="'.((int) $id).'">';
		if ($dol_openinpopup) {
			print '<input type="hidden" name="dol_openinpopup" value="'.dol_escape_htmltag($dol_openinpopup).'">';
		}
		print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
		print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
		print '<div class="div-table-responsive-no-min">';
		print '<div class="inline-block valignmiddle nowrapfordate">';
		print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div> ';
		print '<div class="inline-block valignmiddle nowrapfordate">';
		print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div> ';
		print '<div class="inline-block valignmiddle">'.$form->showFilterButtons().'</div>';
		print '</div>';
		print '</form>';

		print '<table class="noborder tagtable liste centpercent">';
		print '<tr class="liste_titre">';
		if (!empty($arrayfields['f.datef']['checked'])) {
			print_liste_field_titre($arrayfields['f.datef']['label'], $_SERVER["PHP_SELF"], "f.datef", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
		}
		print '<td>'.$langs->trans("Element").'</td>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td class="right">'.$langs->trans("Debit").'</td>';
		print '<td class="right">'.$langs->trans("Credit").'</td>';
		print '<td class="right">'.$langs->trans("Balance").'</td>';
		print '<td class="right">'.$langs->trans("Author").'</td>';
		print '</tr>';

		$errors = array();
		$opening = 0.0;
		$TData = recapComptaGetData($db, $object, $hookmanager, $id, $sortfield, $sortorder, $errors, $search_date_start, $search_date_end, $opening);
		foreach ($errors as $err) {
			print '<tr class="oddeven"><td colspan="7"><span class="error">'.dol_escape_htmltag($err).'</span></td></tr>';
		}

		if (empty($TData)) {
			print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("NoInvoice").'</span></td></tr>';
		} else {
			$totalDebit = 0;
			$totalCredit = 0;

			// Display array
			foreach ($TData as $data) {
				if (!empty($data['isopening'])) {
					print '<tr class="oddeven">';
					print '<td class="center">'.dol_print_date($data['date'], 'day', 'tzserver').'</td>';
					print '<td colspan="2"><b>'.dol_escape_htmltag((string) $data['ref']).'</b></td>';
					print '<td></td><td></td>';
					print '<td class="right"><span class="amount"><b>'.price($data['balance']).'</b></span></td>';
					print '<td></td>';
					print "</tr>\n";
					continue;
				}
				$html_class = '';
				if (!empty($data['fk_facture'])) {
					$html_class = 'facid-'.$data['fk_facture'];
				} elseif (!empty($data['fk_paiement'])) {
					$html_class = 'payid-'.$data['fk_paiement'];
				}

				print '<tr class="oddeven '.$html_class.'">';

				$datedetail = dol_print_date($data['date'], 'dayhour');
				if (!empty($data['fk_facture'])) {
					$datedetail = dol_print_date($data['date'], 'day');
				}
				print '<td class="center" title="'.dol_escape_htmltag($datedetail).'">';
				print dol_print_date($data['date'], 'day');
				print "</td>\n";

				print '<td>'.$data['link']."</td>\n";

				print '<td class="left">'.$data['status'].'</td>';

				$amount = (float) price2num($data['amount'], 'MT');
				print '<td class="right">'.(($amount > 0) ? price($amount) : '')."</td>\n";
				$totalDebit += ($amount > 0) ? $amount : 0;

				print '<td class="right">'.(($amount < 0) ? price(abs($amount)) : '')."</td>\n";
				$totalCredit += ($amount < 0) ? abs($amount) : 0;

				// Balance
				print '<td class="right"><span class="amount">'.price($data['balance'])."</span></td>\n";

				// Author
				print '<td class="nowrap right">';
				print $data['author'];
				print '</td>';

				print "</tr>\n";
			}

			print '<tr class="liste_total">';
			print '<td colspan="3">&nbsp;</td>';
			print '<td class="right">'.price($totalDebit).'</td>';
			print '<td class="right">'.price($totalCredit).'</td>';
			print '<td class="right">'.price(price2num($opening + $totalDebit - $totalCredit, 'MT')).'</td>';
			print '<td></td>';
			print "</tr>\n";
		}

		print "</table>";
	}
} else {
	dol_print_error($db);
}

llxFooter();

$db->close();
