<?php declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

require __DIR__ . '/../../vendor/autoload.php';

// 1. Let's load the base example to define the qr bill contents
require __DIR__ . '/../example.php';

// 2. Create a TCPDF instance (or use an existing one from your project)
// – alternatively, an instance of \setasign\Fpdi\Tcpdf\Fpdi() is also accepted by TcPdfOutput.
$tcPdf = new TCPDF('P', 'mm', 'A4', true, 'ISO-8859-1');
$tcPdf->setPrintHeader(false);
$tcPdf->setPrintFooter(false);
$tcPdf->AddPage();

// 3. Create a full payment part for TcPDF
$output = new QrBill\PaymentPart\Output\TcPdfOutput\TcPdfOutput($qrBill, 'en', $tcPdf);
$output
    ->setPrintable(false)
    ->getPaymentPart();

// 4. For demo purposes, let's save the generated example in a file
$examplePath = __DIR__ . "/tcpdf_example.pdf";
$tcPdf->Output($examplePath, 'F');

print "PDF example created here : ".$examplePath;