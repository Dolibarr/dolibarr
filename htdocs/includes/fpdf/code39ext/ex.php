<?php
define('FPDF_FONTPATH','font/');
require('code39.php');

$pdf=new PDF_Code39();
$pdf->AddPage();
$pdf->Code39(60, 30, 'Code 39');
$pdf->Output();
?>
