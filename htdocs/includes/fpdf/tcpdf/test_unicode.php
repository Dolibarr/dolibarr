<?php
//============================================================+
// File name   : test_unicode.php                                      
// Begin       : 2004-07-14                                    
// Last Update : 2006-05-18                                    
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

/**
 * Create PDF document -test for unicode
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - unicode test.
 * @author Nicola Asuni
 * @copyright 2004-2006 Tecnick.com S.r.l (www.tecnick.com) Via Ugo Foscolo n.19 - 09045 Quartu Sant'Elena (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.sourceforge.net
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2004-07-14
 */

$doc_title = "test title";
$doc_subject = "test description";
$doc_keywords = "test keywords";
$htmlcontent = "&lt; € &euro; &#8364; &amp; è &egrave; &copy; &gt;<br /><h1>heading 1</h1><h2>heading 2</h2><h3>heading 3</h3><h4>heading 4</h4><h5>heading 5</h5><h6>heading 6</h6>ordered list:<br /><ol><li><b>bold text</b></li><li><i>italic text</i></li><li><u>underlined text</u></li><li><a href=\"http://www.tecnick.com\">link to http://www.tecnick.com</a></li><li>test break<br />second line<br />third line</li><li><font size=\"+3\">font + 3</font></li><li><small>small text</small></li><li>normal <sub>subscript</sub> <sup>superscript</sup></li></ul><hr />table:<br /><table border=\"1\" cellspacing=\"1\" cellpadding=\"1\"><tr><th>#</th><th>A</th><th>B</th></tr><tr><th>1</th><td bgcolor=\"#cccccc\">A1</td><td>B1</td></tr><tr><th>2</th><td>A2 € &euro; &#8364; &amp; è &egrave; </td><td>B2</td></tr><tr><th>3</th><td>A3</td><td><font color=\"#FF0000\">B3</font></td></tr></table><hr />image:<br /><img src=\"images/logo_example.png\" alt=\"test alt attribute\" width=\"100\" height=\"100\" border=\"0\" />";

require_once('config/lang/eng.php');
require_once('tcpdf.php');

//create new PDF document (document units are set by default to millimeters)
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true); 

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
$pdf->WriteHTML($htmlcontent, true, 0);

// output some content
$pdf->SetFont("vera", "BI", 20);
$pdf->Cell(0,10,"TEST Bold-Italic Cell",1,1,'C');

// output some UTF-8 test content
$pdf->AddPage();
$pdf->SetFont("FreeSerif", "", 12);
$utf8text = file_get_contents("utf8test.txt", false); // get utf-8 text form file
$pdf->SetFillColor(230, 240, 255, true);
$pdf->Write(5,$utf8text, '', 1);


//Close and output PDF document
$pdf->Output();

//============================================================+
// END OF FILE                                                 
//============================================================+
?>