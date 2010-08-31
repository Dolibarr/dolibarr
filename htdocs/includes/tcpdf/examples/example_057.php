<?php
//============================================================+
// File name   : example_057.php
// Begin       : 2010-04-03
// Last Update : 2010-08-08
//
// Description : Example 057 for TCPDF class
//               Cell vertical alignments
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
 * @abstract TCPDF - Example: Cell vertical alignments
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-03-04
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 057');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 057', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', 'B', 20);

// add a page
$pdf->AddPage();

$pdf->Write(0, 'Example of alignment options for Cell()', '', 0, 'L', true, 0, false, false, 0);

$pdf->SetFont('helvetica', '', 11);

// set border width
$pdf->SetLineWidth(0.7);

// set color for cell border
$pdf->SetDrawColor(0,128,255);

$pdf->setCellHeightRatio(3);

$pdf->SetXY(15, 60);

// text on center
$pdf->Cell(30, 0, 'Top-Center', 1, $ln=0, 'C', 0, '', 0, false, 'T', 'C');
$pdf->Cell(30, 0, 'Center-Center', 1, $ln=0, 'C', 0, '', 0, false, 'C', 'C');
$pdf->Cell(30, 0, 'Bottom-Center', 1, $ln=0, 'C', 0, '', 0, false, 'B', 'C');
$pdf->Cell(30, 0, 'Ascent-Center', 1, $ln=0, 'C', 0, '', 0, false, 'A', 'C');
$pdf->Cell(30, 0, 'Baseline-Center', 1, $ln=0, 'C', 0, '', 0, false, 'L', 'C');
$pdf->Cell(30, 0, 'Descent-Center', 1, $ln=0, 'C', 0, '', 0, false, 'D', 'C');


$pdf->SetXY(15, 90);

// text on top
$pdf->Cell(30, 0, 'Top-Top', 1, $ln=0, 'C', 0, '', 0, false, 'T', 'T');
$pdf->Cell(30, 0, 'Center-Top', 1, $ln=0, 'C', 0, '', 0, false, 'C', 'T');
$pdf->Cell(30, 0, 'Bottom-Top', 1, $ln=0, 'C', 0, '', 0, false, 'B', 'T');
$pdf->Cell(30, 0, 'Ascent-Top', 1, $ln=0, 'C', 0, '', 0, false, 'A', 'T');
$pdf->Cell(30, 0, 'Baseline-Top', 1, $ln=0, 'C', 0, '', 0, false, 'L', 'T');
$pdf->Cell(30, 0, 'Descent-Top', 1, $ln=0, 'C', 0, '', 0, false, 'D', 'T');


$pdf->SetXY(15, 120);

// text on bottom
$pdf->Cell(30, 0, 'Top-Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'T', 'B');
$pdf->Cell(30, 0, 'Center-Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'C', 'B');
$pdf->Cell(30, 0, 'Bottom-Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'B', 'B');
$pdf->Cell(30, 0, 'Ascent-Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'A', 'B');
$pdf->Cell(30, 0, 'Baseline-Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'L', 'B');
$pdf->Cell(30, 0, 'Descent-Bottom', 1, $ln=0, 'C', 0, '', 0, false, 'D', 'B');


// draw some reference lines
$linestyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => '', 'phase' => 0, 'color' => array(255, 0, 0));
$pdf->Line(15, 60, 195, 60, $linestyle);
$pdf->Line(15, 90, 195, 90, $linestyle);
$pdf->Line(15, 120, 195, 120, $linestyle);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// CELL BORDERS

// add a page
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 20);

$pdf->Write(0, 'Example of borders for Cell()', '', 0, 'L', true, 0, false, false, 0);

$pdf->SetFont('helvetica', '', 11);

// set border width
$pdf->SetLineWidth(1);

// set color for cell border
$pdf->SetDrawColor(0,128,255);

// set filling color
$pdf->SetFillColor(255,255,128);

$pdf->Cell(30, 0, '1', 1, 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LTRB', 'LTRB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LTR', 'LTR', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'TRB', 'TRB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LRB', 'LRB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LTB', 'LTB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LT', 'LT', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'TR', 'TR', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'RB', 'RB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LB', 'LB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'LR', 'LR', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'TB', 'TB', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'L', 'L', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'T', 'T', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'R', 'R', 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(2);
$pdf->Cell(30, 0, 'B', 'B', 1, 'C', 1, '', 0, false, 'T', 'C');

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// ADVANCED SETTINGS FOR CELL BORDERS

// add a page
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 20);

$pdf->Write(0, 'Example of advanced border settings for Cell()', '', 0, 'L', true, 0, false, false, 0);

$pdf->SetFont('helvetica', '', 11);

// set border width
$pdf->SetLineWidth(1);

// set color for cell border
$pdf->SetDrawColor(0,128,255);

// set filling color
$pdf->SetFillColor(255,255,128);

$border = array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0)));
$pdf->Cell(30, 0, 'LTRB', $border, 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(5);

$border = array(
'L' => array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0)),
'R' => array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 255)),
'T' => array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 255, 0)),
'B' => array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 255)));
$pdf->Cell(30, 0, 'LTRB', $border, 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(5);

$border = array('mode' => 'ext', 'LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0)));
$pdf->Cell(30, 0, 'LTRB EXT', $border, 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(5);

$border = array('mode' => 'int', 'LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0)));
$pdf->Cell(30, 0, 'LTRB INT', $border, 1, 'C', 1, '', 0, false, 'T', 'C');
$pdf->Ln(5);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_057.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
