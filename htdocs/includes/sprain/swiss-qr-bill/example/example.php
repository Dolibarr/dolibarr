<?php declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

require __DIR__ . '/../vendor/autoload.php';

// This is an example how to create a typical qr bill:
// - with reference number
// - with known debtor
// - with specified amount
// - with human-readable additional information
// - using your QR-IBAN
//
// Likely the most common use-case in the business world.

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
        'CH4431999123000889012' // This is a special QR-IBAN. Classic IBANs will not be valid here.
    ));

// Add debtor information
// Who has to pay the invoice? This part is optional.
//
// Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress.
// They are interchangeable for creditor as well as debtor.
$qrBill->setUltimateDebtor(
    QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
        'Pia-Maria Rutschmann-Schnyder',
        'Grosse Marktgasse',
        '28',
        '9400',
        'Rorschach',
        'CH'
    ));

// Add payment amount information
// What amount is to be paid?
$qrBill->setPaymentAmountInformation(
    QrBill\DataGroup\Element\PaymentAmountInformation::create(
        'CHF',
        2500.25
    ));

// Add payment reference
// This is what you will need to identify incoming payments.
$referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
    '210000',  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
    '313947143000901' // A number to match the payment with your internal data, e.g. an invoice number
);

$qrBill->setPaymentReference(
    QrBill\DataGroup\Element\PaymentReference::create(
        QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
        $referenceNumber
    ));

// Optionally, add some human-readable information about what the bill is for.
$qrBill->setAdditionalInformation(
    QrBill\DataGroup\Element\AdditionalInformation::create(
        'Invoice 123456, Gardening work'
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
