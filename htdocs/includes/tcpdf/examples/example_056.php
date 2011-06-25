<?php
//============================================================+
// File name   : example_056.php
// Begin       : 2010-03-26
// Last Update : 2010-08-08
//
// Description : Example 056 for TCPDF class
//               Crop marks and color registration bars
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
 * @abstract TCPDF - Example: Crop marks and color registration bars
 * @author Nicola Asuni
 * @since 2010-03-26
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 056');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 056', PDF_HEADER_STRING);

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

$pdf->Write(0, 'Example of Crop Marks and Color Registration Bars', '', 0, 'L', true, 0, false, false, 0);

$pdf->Ln(5);

// color registration bars

$pdf->colorRegistrationBar($x=50, $y=70, $w=40, $h=40, $transition=true, $vertical=false, $colors='A,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar($x=90, $y=70, $w=40, $h=40, $transition=true, $vertical=true, $colors='A,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar($x=50, $y=115, $w=80, $h=5, $transition=false, $vertical=true, $colors='A,W,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar($x=135, $y=70, $w=5, $h=50, $transition=false, $vertical=false, $colors='A,W,R,G,B,C,M,Y,K');

// corner crop marks

$pdf->cropMark($x=50, $y=70, $w=10, $h=10, $type='A', $color=array(0,0,0));
$pdf->cropMark($x=140, $y=70, $w=10, $h=10, $type='B', $color=array(0,0,0));
$pdf->cropMark($x=50, $y=120, $w=10, $h=10, $type='C', $color=array(0,0,0));
$pdf->cropMark($x=140, $y=120, $w=10, $h=10, $type='D', $color=array(0,0,0));

// various crop marks

$pdf->cropMark($x=95, $y=65, $w=5, $h=5, $type='A,B', $color=array(255,0,0));
$pdf->cropMark($x=95, $y=125, $w=5, $h=5, $type='C,D', $color=array(255,0,0));

$pdf->cropMark($x=45, $y=95, $w=5, $h=5, $type='A,C', $color=array(0,255,0));
$pdf->cropMark($x=145, $y=95, $w=5, $h=5, $type='B,D', $color=array(0,255,0));

$pdf->cropMark($x=95, $y=140, $w=5, $h=5, $type='A,D', $color=array(0,0,255));

// registration marks

$pdf->registrationMark($x=40, $y=60, $r=5, $double=false, $cola=array(0,0,0), $colb=array(255,255,255));
$pdf->registrationMark($x=150, $y=60, $r=5, $double=true, $cola=array(0,0,0), $colb=array(255,255,0));
$pdf->registrationMark($x=40, $y=130, $r=5, $double=true, $cola=array(0,0,0), $colb=array(255,255,0));
$pdf->registrationMark($x=150, $y=130, $r=5, $double=false, $cola=array(0,0,0), $colb=array(255,255,255));

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_056.pdf', 'I');

//============================================================+
// END OF FILE                                             
//============================================================+
