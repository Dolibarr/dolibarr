<?php
//============================================================+
// File name   : test_old.php                                      
// Begin       : 2004-07-14                                    
// Last Update : 2005-03-31                                    
//                                                             
// Description : Test page fot TCPDF class                     
//                                                             
// Author: Nicola Asuni                                        
//                                                             
// (c) Copyright:                                              
//               Tecnick.com S.r.l.                            
//               Via Ugo Foscolo n.19                          
//               09045 Quartu Sant'Elena (CA)                  
//               ITALY                                         
//               www.tecnick.com                               
//               info@tecnick.com                              
//============================================================+

// NOTE: change the FPDF_FONTPATH constant on config/tcpdf_config.php file to K_PATH_MAIN."fonts/old"
//       to use the Type1 fonts.

/**
 * Create PDF document - test for byte charset
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - unicode test.
 * @author Nicola Asuni
 * @copyright 2004 Tecnick.com S.r.l (www.tecnick.com) Via Ugo Foscolo n.19 - 09045 Quartu Sant'Elena (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.sourceforge.net
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2004-07-14
 */

$doc_title = "test title";
$doc_subject = "test description";
$doc_keywords = "test keywords";
$htmlcontent = "<h1>heading 1</h1><h2>heading 2</h2><h3>heading 3</h3><h4>heading 4</h4><h5>heading 5</h5><h6>heading 6</h6>ordered list:<br /><ol><li><b>bold text</b></li><li><i>italic text</i></li><li><u>underlined text</u></li><li><a href=\"http://www.tecnick.com\">link to http://www.tecnick.com</a></li><li>test break<br />second line<br />third line</li><li><font size=\"+3\">font + 3</font></li><li><small>small text</small></li><li>normal <sub>subscript</sub> <sup>superscript</sup></li></ul><hr />table:<br /><table border=\"1\" cellspacing=\"1\" cellpadding=\"1\"><tr><th>#</th><th>A</th><th>B</th></tr><tr><th>1</th><td>A1</td><td>B1</td></tr><tr><th>2</th><td>A2</td><td>B2</td></tr><tr><th>3</th><td>A3</td><td>B3</td></tr></table><hr />image:<br /><img src=\"images/logo_example.png\" alt=\"\" width=\"100\" height=\"100\" border=\"0\" />";

require_once('config/lang/eng.php');
require_once('tcpdf.php');

//create new PDF document (document units are set by default to millimeters)
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(PDF_AUTHOR);
$pdf->SetTitle($doc_title);
$pdf->SetSubject($doc_subject);
$pdf->SetKeywords($doc_keywords);

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); //set image scale factor

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$pdf->setLanguageArray($l); //set language items

//initialize document
$pdf->AliasNbPages();

$pdf->AddPage();

// set barcode
$pdf->SetBarcode(date("Y-m-d H:i:s", time()));

// output some HTML code
$pdf->WriteHTML($htmlcontent, true);

// output some content
$pdf->SetFont("helvetica", "BI", 20);
$pdf->Cell(0,10,'TEST Bold-Italic Cell',1,0,'C');

//Close and output PDF document
$pdf->Output();

//============================================================+
// END OF FILE                                                 
//============================================================+
?>