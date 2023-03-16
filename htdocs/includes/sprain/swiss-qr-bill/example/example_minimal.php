<?php declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

require __DIR__ . '/../vendor/autoload.php';

// This is an example how to create a minimal qr bill:
// - no defined amount
// - no defined debtor
// - no reference number

// Create a new instance of QrBill, containing default headers with fixed values
$qrBill = QrBill\QrBill::create();

// Add creditor information
// Who will receive the payment and to which bank account?
$qrBill->setCreditor(
    QrBill\DataGroup\Element\CombinedAddress::create(
        'Robert Schneider AG',
        'Rue du Lac 1268',
        '2501 Biel',
        'CH'
    ));

$qrBill->setCreditorInformation(
    QrBill\DataGroup\Element\CreditorInformation::create(
        'CH9300762011623852957' // This is a classic iban. QR-IBANs will not be valid in this minmal setup.
    ));

// Add payment amount information
// The currency must be defined.
$qrBill->setPaymentAmountInformation(
    QrBill\DataGroup\Element\PaymentAmountInformation::create(
        'CHF'
    ));

// Add payment reference
// Explicitly define that no reference number will be used by setting TYPE_NON.
$qrBill->setPaymentReference(
    QrBill\DataGroup\Element\PaymentReference::create(
        QrBill\DataGroup\Element\PaymentReference::TYPE_NON
    ));

// Time to output something!
//
// Get the QR code image  …
try {
    $qrBill->getQrCode()->writeFile(__DIR__ . '/qr.png');
    $qrBill->getQrCode()->writeFile(__DIR__ . '/qr.svg');
} catch (Exception $e) {
	foreach($qrBill->getViolations() as $violation) {
		print $violation->getMessage()."\n";
	}
	exit;
}

// Next: Output full payment parts, depending on the format you want to use:
//
// - FpdfOutput/fpdf-example.php
// - HtmlOutput/html-example.php
// - TcPdfOutput/tcpdf-example.php