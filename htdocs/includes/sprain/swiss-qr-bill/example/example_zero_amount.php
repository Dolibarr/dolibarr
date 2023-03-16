<?php declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

require __DIR__ . '/../vendor/autoload.php';

// This is an example how to create a qr bill with an amount of 0.00 and
// the note "do not use for payment". This is used for "Avisierungen".
//
// The specifics in this case are:
// - set an amount of 0.00
// - add the "do not use for payment" text as additional information. Translations are provided by this library.

// Create a new instance of QrBill, containing default headers with fixed values
$qrBill = QrBill\QrBill::create();

// Add creditor information
$qrBill->setCreditor(
    QrBill\DataGroup\Element\CombinedAddress::create(
        'Robert Schneider AG',
        'Rue du Lac 1268',
        '2501 Biel',
        'CH'
    ));

$qrBill->setCreditorInformation(
    QrBill\DataGroup\Element\CreditorInformation::create(
        'CH4431999123000889012' // This is a special QR-IBAN. Classic IBANs will not be valid here.
    ));

// Add payment amount information of 0.00
$qrBill->setPaymentAmountInformation(
    QrBill\DataGroup\Element\PaymentAmountInformation::create(
        'CHF',
        0.00
    ));

// Add payment reference
$referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
    '210000',  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
    '313947143000901' // A number to match the payment with your internal data, e.g. an invoice number
);

$qrBill->setPaymentReference(
    QrBill\DataGroup\Element\PaymentReference::create(
        QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
        $referenceNumber
    ));

// Add do-not-use-for-payment information
$qrBill->setAdditionalInformation(
    QrBill\DataGroup\Element\AdditionalInformation::create(
        QrBill\PaymentPart\Translation\Translation::get('doNotUseForPayment', 'en')
    )
);

// Now get the QR code image and save it as a file.
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
