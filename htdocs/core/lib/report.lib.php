<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       	htdocs/core/lib/report.lib.php
 *  \brief      	Set of functions for reporting
 */


/**
 *	Show header of a report
 *
 *	@param	string				$reportname     Name of report
 *	@param 	string				$notused        Not used
 *	@param 	string				$period         Period of report
 *	@param 	string				$periodlink     Link to switch period
 *	@param 	string				$description    Description
 *	@param 	integer	            $builddate      Date generation
 *	@param 	string				$exportlink     Link for export or ''
 *	@param	array<string,mixed>	$moreparam		Array with list of params to add into form
 *	@param	string				$calcmode		Calculation mode
 *  @param  string              $varlink        Add a variable into the address of the page
 *	@return	void
 */
function report_header($reportname, $notused, $period, $periodlink, $description, $builddate, $exportlink = '', $moreparam = array(), $calcmode = '', $varlink = '')
{
	global $langs;

	print "\n\n<!-- start banner of report -->\n";

	if (!empty($varlink)) {
		$varlink = '?'.$varlink;
	}

	$title = $langs->trans("Report");

	print_barre_liste($title, 0, '', '', '', '', '', -1, '', 'generic', 0, '', '', -1, 1, 1);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].$varlink.'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">'."\n";

	print dol_get_fiche_head();

	foreach ($moreparam as $key => $value) {
		print '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
	}

	print '<table class="border tableforfield centpercent">'."\n";

	$variant = ($periodlink || $exportlink);

	// Title line
	print '<tr>';
	print '<td width="150">'.$langs->trans("ReportName").'</td>';
	print '<td>';
	print $reportname;
	print '</td>';
	if ($variant) {
		print '<td></td>';
	}
	print '</tr>'."\n";

	// Calculation mode
	if ($calcmode) {
		print '<tr>';
		print '<td width="150">'.$langs->trans("CalculationMode").'</td>';
		print '<td>';
		print $calcmode;
		if ($variant) {
			print '<td></td>';
		}
		print '</td>';
		print '</tr>'."\n";
	}

	// Report analysis period row
	print '<tr>';
	print '<td>'.$langs->trans("ReportPeriod").'</td>';
	print '<td>';
	if ($period) {
		print $period;
	}
	if ($variant) {
		print '<td class="nowraponall">'.$periodlink.'</td>';
	}
	print '</td>';
	print '</tr>'."\n";

	// Description row
	print '<tr>';
	print '<td>'.$langs->trans("ReportDescription").'</td>';
	print '<td>'.$description.'</td>';
	if ($variant) {
		print '<td></td>';
	}
	print '</tr>'."\n";

	// Export row
	print '<tr>';
	print '<td>'.$langs->trans("GeneratedOn").'</td>';
	print '<td>';
	print dol_print_date($builddate, 'dayhour');
	print '</td>';
	if ($variant) {
		print '<td>'.$exportlink.'</td>';
	}
	print '</tr>'."\n";

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';

	print '</form>';
	print '<br>';

	print "\n<!-- end banner of report -->\n\n";
}

/**
 * Return the readable path of our company logo for a report, or '' if none.
 *
 * @param	Societe	$mysoc	Our company
 * @return	string			Logo path, '' if disabled, not set or not readable
 */
function reportGetLogoPath($mysoc)
{
	global $conf;

	if (getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') || empty($mysoc->logo)) {
		return '';
	}

	$logodir = $conf->mycompany->dir_output;
	if (!empty($conf->mycompany->multidir_output[$conf->entity])) {
		$logodir = $conf->mycompany->multidir_output[$conf->entity];
	}
	if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') && !empty($mysoc->logo_small)) {
		$logo = $logodir.'/logos/thumbs/'.$mysoc->logo_small;
	} else {
		$logo = $logodir.'/logos/'.$mysoc->logo;
	}
	if (!is_readable($logo)) {
		dol_syslog(__FUNCTION__.' logo not readable '.$logo, LOG_WARNING);
		return '';
	}

	return $logo;
}

/**
 * Return our company info lines (without the name) for a report header.
 * Content follows the same setup as the source address of PDF documents.
 * Hook reportBuildCompanyLines can replace the lines: return 1 with results['lines'].
 *
 * @param	Translate	$outputlangs	Output language
 * @param	Societe		$mysoc			Our company
 * @return	string[]					Non empty lines
 */
function reportGetCompanyLines($outputlangs, $mysoc)
{
	global $hookmanager;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

	$outputlangs->loadLangs(array("main", "companies"));

	$lines = explode("\n", pdf_build_address($outputlangs, $mysoc, '', '', 0, 'source'));

	if (is_object($hookmanager)) {
		$parameters = array('outputlangs' => $outputlangs, 'lines' => $lines);
		$action = '';
		$reshook = $hookmanager->executeHooks('reportBuildCompanyLines', $parameters, $mysoc, $action);
		if ($reshook < 0) {
			dol_syslog(__FUNCTION__.' hook error '.$hookmanager->error, LOG_ERR);
		} elseif ($reshook > 0 && isset($hookmanager->resArray['lines']) && is_array($hookmanager->resArray['lines'])) {
			$lines = $hookmanager->resArray['lines'];
		}
	}

	$result = array();
	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ($line !== '') {
			$result[] = $line;
		}
	}

	return $result;
}

/**
 * Create a PDF instance for a report, with first page added.
 *
 * @param	Translate		$outputlangs	Output language
 * @param	string			$title			Document title
 * @param	string			$subject		Document subject
 * @param	'P'|'L'			$orientation	'P' for portrait, 'L' for landscape
 * @return	array{pdf:TCPDF,margin:float,pagewidth:float,pageheight:float,usablewidth:float,fontsize:int}
 */
function reportPdfInit($outputlangs, $title, $subject, $orientation = 'P')
{
	global $mysoc, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

	$format = pdf_getFormat($outputlangs);
	$pdf = pdf_getInstance(array($format['width'], $format['height']), 'mm', ($orientation == 'L' ? 'l' : 'P'));
	$fontsize = (int) pdf_getPDFFontSize($outputlangs);
	$margin = 10.0;

	$pdf->SetFont(pdf_getPDFFont($outputlangs));
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->SetMargins($margin, $margin, $margin);
	$pdf->SetAutoPageBreak(false);
	$pdf->Open();
	$pdf->SetDrawColor(128, 128, 128);
	$pdf->SetTitle($outputlangs->convToOutputCharset($title));
	$pdf->SetSubject($outputlangs->convToOutputCharset($subject));
	$pdf->SetCreator("Dolibarr ".DOL_VERSION);
	$author = (is_object($mysoc) ? (string) $mysoc->name : '');
	if (is_object($user) && $user->id > 0) {
		$author .= ($author !== '' ? ' - ' : '').$user->getFullName($outputlangs);
	}
	$pdf->SetAuthor($outputlangs->convToOutputCharset($author));
	if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
		$pdf->SetCompression(false);
	}
	$pdf->AddPage();

	// Real page size, after orientation is applied
	$pagewidth = (float) $pdf->getPageWidth();
	$pageheight = (float) $pdf->getPageHeight();

	return array(
		'pdf' => $pdf,
		'margin' => $margin,
		'pagewidth' => $pagewidth,
		'pageheight' => $pageheight,
		'usablewidth' => $pagewidth - 2 * $margin,
		'fontsize' => $fontsize
	);
}

/**
 * Print our company logo (left) and company info (right) at top of a report PDF page.
 *
 * @param	TCPDF		$pdf			PDF instance
 * @param	Translate	$outputlangs	Output language
 * @param	Societe		$mysoc			Our company
 * @param	float		$margin			Page margin
 * @param	int			$fontsize		Default font size
 * @return	float						Y position below the header
 */
function reportPdfCompanyHeader($pdf, $outputlangs, $mysoc, $margin, $fontsize)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

	$bottom = $margin;

	$logo = reportGetLogoPath($mysoc);
	if ($logo !== '') {
		$height = pdf_getHeightForLogo($logo);
		$pdf->Image($logo, $margin, $margin, 0, $height);
		$bottom = $margin + $height;
	}

	$w = 100;
	$posx = (float) $pdf->getPageWidth() - $margin - $w;
	$pdf->SetTextColor(0, 0, 0);
	$pdf->SetXY($posx, $margin);
	if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
		$pdf->SetFont('', 'B', $fontsize);
		$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($mysoc->name), 0, 'L');
	}
	$pdf->SetFont('', '', $fontsize - 1);
	foreach (reportGetCompanyLines($outputlangs, $mysoc) as $line) {
		$pdf->SetX($posx);
		$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($line), 0, 'L');
	}

	return max($bottom, (float) $pdf->GetY()) + 4;
}

/**
 * Return a report export file name.
 *
 * @param	string	$title		Report title (translated)
 * @param	string	$name		Subject of the report (thirdparty name...), '' for none
 * @param	string	$ext		File extension
 * @return	string				File name
 */
function reportFileName($title, $name, $ext)
{
	$prefix = trim((string) preg_replace('/_+/', '_', dol_sanitizeFileName(str_replace(' ', '_', $title), '_', 0)), '_');
	$name = trim((string) preg_replace('/_+/', '_', dol_sanitizeFileName(str_replace(' ', '_', $name), '_', 0)), '_.');

	return $prefix.($name !== '' ? '-'.$name : '').'.'.$ext;
}

/**
 * Return the label of a filtered period.
 *
 * @param	Translate	$outputlangs	Output language
 * @param	int|''		$datestart		Start date (timestamp), '' for none
 * @param	int|''		$dateend		End date (timestamp), '' for none
 * @return	string						Label, '' if no filter
 */
function reportPeriodLabel($outputlangs, $datestart, $dateend)
{
	if ($datestart && $dateend) {
		return $outputlangs->transnoentitiesnoconv("DateFromTo", dol_print_date($datestart, 'day', 'tzserver', $outputlangs), dol_print_date($dateend, 'day', 'tzserver', $outputlangs));
	} elseif ($datestart) {
		return $outputlangs->transnoentitiesnoconv("DateFrom", dol_print_date($datestart, 'day', 'tzserver', $outputlangs));
	} elseif ($dateend) {
		return $outputlangs->transnoentitiesnoconv("DateUntil", dol_print_date($dateend, 'day', 'tzserver', $outputlangs));
	}

	return '';
}

/**
 * Return a valid worksheet title from a free label.
 *
 * @param	string	$label		Label
 * @return	string				Title of max 31 chars without forbidden chars
 */
function reportSheetTitle($label)
{
	$title = trim((string) preg_replace('/\s+/u', ' ', str_replace(array('[', ']', ':', '*', '?', '/', '\\'), ' ', $label)), " '");
	$title = trim(dol_substr($title, 0, 31, 'UTF-8'), " '");

	return ($title === '' ? 'Sheet1' : $title);
}

/**
 * Create a spreadsheet for a report. Caller must check class ZipArchive exists before.
 *
 * @param	Translate	$outputlangs	Output language
 * @param	string		$title			Document title
 * @param	string		$sheetlabel		Label used for the worksheet title
 * @return	\PhpOffice\PhpSpreadsheet\Spreadsheet
 */
function reportXlsxInit($outputlangs, $title, $sheetlabel)
{
	global $user;

	require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
	require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
	require_once PHPEXCELNEW_PATH.'Spreadsheet.php';

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$spreadsheet->getProperties()->setCreator($user->getFullName($outputlangs).' - '.DOL_APPLICATION_TITLE.' '.DOL_VERSION);
	$spreadsheet->getProperties()->setTitle($title);
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle(reportSheetTitle($sheetlabel));
	$sheet->getDefaultRowDimension()->setRowHeight(16);

	return $spreadsheet;
}

/**
 * Write logo (left) and company info (right) at top of a report worksheet.
 *
 * @param	\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet	$sheet			Worksheet
 * @param	Translate										$outputlangs	Output language
 * @param	Societe											$mysoc			Our company
 * @param	string											$firstcol		First column of the info block
 * @param	string											$lastcol		Last column of the info block
 * @return	int																Number of rows used
 */
function reportXlsxCompanyHeader($sheet, $outputlangs, $mysoc, $firstcol, $lastcol)
{
	$infolines = array();
	if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
		$infolines[] = array((string) $mysoc->name, true);
	}
	foreach (reportGetCompanyLines($outputlangs, $mysoc) as $line) {
		$infolines[] = array($line, false);
	}

	$logo = reportGetLogoPath($mysoc);
	if ($logo !== '') {
		$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
		$drawing->setPath($logo);
		$drawing->setCoordinates('A1');
		$drawing->setOffsetX(2);
		$drawing->setOffsetY(2);
		$drawing->setHeight(70);
		$drawing->setWorksheet($sheet);
	}

	foreach ($infolines as $i => $infoline) {
		$cell = $firstcol.($i + 1);
		$sheet->setCellValueExplicit($cell, $infoline[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->mergeCells($cell.':'.$lastcol.($i + 1));
		$sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
		if ($infoline[1]) {
			$sheet->getStyle($cell)->getFont()->setBold(true);
		}
	}

	return max(count($infolines), ($logo !== '' ? 5 : 0));
}

/**
 * Send a report spreadsheet to the browser as an xlsx download.
 *
 * @param	\PhpOffice\PhpSpreadsheet\Spreadsheet	$spreadsheet	Spreadsheet
 * @param	string									$filename		File name (UTF-8)
 * @return	void
 */
function reportXlsxOutput($spreadsheet, $filename)
{
	// ASCII fallback for old browsers, non ASCII chars become '_'
	$asciiname = (string) preg_replace('/[^\x20-\x7e]/', '_', dol_sanitizeFileName($filename));
	$asciiname = trim((string) preg_replace(array('/_+/', '/_?-_?/', '/_?\._?/'), array('_', '-', '.'), $asciiname), '_-');
	if ($asciiname === '' || $asciiname[0] === '.') {
		$asciiname = 'export'.$asciiname;
	}

	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$asciiname.'"; filename*=UTF-8\'\''.rawurlencode($filename));
	header('Cache-Control: max-age=0');

	$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
	$writer->save('php://output');
	$spreadsheet->disconnectWorksheets();
}

/**
 * Return the PDF and XLSX export buttons of a report page.
 * They call the current page with action exportpdf and exportxlsx.
 *
 * @param	string	$param		URL parameters to keep, starting with '&'
 * @param	string	$idprefix	Prefix of the button ids
 * @param	int		$enabled	1 to enable buttons, 0 to show them disabled
 * @return	string				HTML
 */
function reportExportButtons($param, $idprefix, $enabled = 1)
{
	global $langs;

	$langs->load("accountancy");

	$url = $_SERVER["PHP_SELF"].'?action=exportpdf&token='.newToken().$param;
	$out = dolGetButtonTitle($langs->trans("ExportToPdf"), '', 'fa fa-file-pdf', $url, $idprefix.'exportpdf', $enabled, array('attr' => array('target' => '_blank', 'rel' => 'noopener')));
	$url = $_SERVER["PHP_SELF"].'?action=exportxlsx&token='.newToken().$param;
	$out .= dolGetButtonTitle($langs->trans("Export").' (XLSX)', '', 'fa fa-file-excel', $url, $idprefix.'exportxlsx', $enabled);

	return $out;
}
