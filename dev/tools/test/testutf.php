<?php
// This source file must be UTF-8 encoded
/*
$filename='filewithé';

print 'Test to create a file on disk with name '.$filename."<br>\n";
print 'ENV[LANG]='.$_ENV["LANG"]."<br>\n";
print 'ENV[LANGUAGE]='.$_ENV["LANGUAGE"]."<br>\n";

// Si LANG contient UTF8, system en UTF8, pas de conversion requise pour fopen
$s=fopen('/tmp/'.$filename,'w');
fclose($s);

print 'Files has been created. Check its name from your explorer'."\n";

<?php
*/

//============================================================+
// File name   : example_038.php
// Begin       : 2008-09-15
// Last Update : 2010-08-08
//
// Description : Example 038 for TCPDF class
//               CID-0 CJK unembedded font
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
 * @abstract TCPDF - Example: CID-0 CJK unembedded font
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-09-15
 */

require_once('../../htdocs/includes/tecnickcom/tcpdf/config/tcpdf_config.php');
require_once('../../htdocs/includes/tecnickcom/tcpdf/tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 038');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 038', PDF_HEADER_STRING);

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
//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

// set font
$pdf->SetFont('arialunicid0', '', 20);

// add a page
$pdf->AddPage();

$txt = 'Example of CID-0 CJK unembedded font.
To display extended text you must have CJK fonts for your PDF reader: こんにちは世界';

$pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example.pdf', 'F');

//============================================================+
// END OF FILE
//============================================================+
