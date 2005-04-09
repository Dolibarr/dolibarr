<?php
define('FPDF_FONTPATH','font/');
require('i25.php');

$pdf=new PDF_i25();
$pdf->AddPage();
$pdf->i25(90,40,'12345678');
$pdf->Output();
?>
