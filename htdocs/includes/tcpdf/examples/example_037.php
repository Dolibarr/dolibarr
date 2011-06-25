<?php
//============================================================+
// File name   : example_037.php
// Begin       : 2008-09-12
// Last Update : 2010-08-08
//
// Description : Example 037 for TCPDF class
//               Spot colors
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
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Spot colors.
 * @author Nicola Asuni
 * @since 2008-09-12
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 037');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 037', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 20);

// add a page
$pdf->AddPage();


$pdf->Write(0, 'Example of Spot Colors', '', 0, 'L', true, 0, false, false, 0);

$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 8);

// Define some new spot colors
// $c, $m, $y and $k (2nd, 3rd, 4th and 5th parameter) are the CMYK color components.
// AddSpotColor($name, $c, $m, $y, $k)
$pdf->AddSpotColor('Pantone 116 C', 0, 20, 100, 0);
$pdf->AddSpotColor('HKS 16 K', 30, 100, 90, 10);
$pdf->AddSpotColor('Pantone 505 C', 57, 100, 85, 55);
$pdf->AddSpotColor('Pantone 440 C', 50, 60, 80, 70);
$pdf->AddSpotColor('Pantone 288 C', 100, 60, 10, 5);
$pdf->AddSpotColor('Pantone 289 C', 100, 78, 50, 0);
$pdf->AddSpotColor('Pantone 356 C', 100, 30, 100, 0);
$pdf->AddSpotColor('Pantone 567 C', 100, 50, 80, 45);
$pdf->AddSpotColor('Pantone 9060 C', 0, 0, 7, 0);
$pdf->AddSpotColor('Pantone 420 C', 22, 14, 22, 0);
$pdf->AddSpotColor('Pantone 422 C', 39, 24, 34, 0);
$pdf->AddSpotColor('Pantone 433 C', 34, 0, 0, 94);
$pdf->AddSpotColor('NovaSpace-Black', 50, 0, 0, 100);
$pdf->AddSpotColor('Pantone 601 C', 0, 0, 55, 0);
$pdf->AddSpotColor('Pantone 659 C', 50, 20, 0, 10);

// Select the spot color
// $tint (the second parameter) is the intensity of the color (0-100).
// SetTextSpotColor($name, $tint=100)
// SetDrawSpotColor($name, $tint=100)
// SetFillSpotColor($name, $tint=100)

$pdf->SetTextSpotColor('NovaSpace-Black', 100);
$pdf->SetDrawSpotColor('NovaSpace-Black', 100);

$starty = 50;

// print some spot colors
$pdf->SetFillSpotColor('Pantone 116 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 116 C');

$starty += 8;

$pdf->SetFillSpotColor('HKS 16 K', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'HKS 16 K');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 505 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 505 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 440 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 440 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 288 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 288 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 289 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 289 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 356 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 356 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 567 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 567 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 9060 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 9060 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 420 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 420 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 422 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 422 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 433 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 433 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 601 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 601 C');

$starty += 8;

$pdf->SetFillSpotColor('Pantone 659 C', 100);
$pdf->Rect(30, $starty, 20, 6, 'DF');
$pdf->Text(53, $starty + 1, 'Pantone 659 C');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_037.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
