<?php declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

require __DIR__ . '/../../vendor/autoload.php';

// 1. Let's load the base example to define the qr bill contents
require __DIR__ . '/../example.php';

// 2. Create a full payment part in HTML
$output = new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput($qrBill, 'en');

$html = $output
    ->setPrintable(false)
    ->getPaymentPart();

// 3. For demo purposes, let's save the generated example in a file
$examplePath = __DIR__ . '/html-example.htm';
file_put_contents($examplePath, $html);

print 'HTML example created here: ' . $examplePath;