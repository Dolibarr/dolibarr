<?php
//============================================================+
// File name   : example_055.php
// Begin       : 2009-10-21
// Last Update : 2010-08-08
//
// Description : Example 055 for TCPDF class
//               Display all characters available on core fonts.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com s.r.l.
//               Via Della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Display all characters available on core fonts.
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: XHTML Forms
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2009-10-21
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 055');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 055', PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

// array of core font names
$core_fonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');

$html = '<h1>Core Fonts Dump</h1>';

// create one HTML table for each core font
foreach($core_fonts as $font) {
	// create HTML content
	$html .= '<table cellpadding="1" cellspacing="0" border="1" nobr="true" style="font-family:'.$font.';text-align:center;">';
	$html .= '<tr style="background-color:yellow;"><td colspan="16" style="font-family:helvetica;font-weight:bold">'.strtoupper($font).'</td></tr><tr>';
	// print each character
	for ($i = 0; $i < 256; ++$i) {
		if (($i > 0) AND (($i % 16) == 0)) {
			$html .= '</tr><tr>';
		}
		$chr = $pdf->unichr($i);
		// replace special characters
		$trans = array('<' => '&lt;', '>' => '&gt;');
		$chr = strtr($chr, $trans);
		$html .= '<td>'.$chr.'</td>';
	}
	$html .= '</tr></table><br />&nbsp;<br />';
}

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_055.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
