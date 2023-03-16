<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill;

use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\CombinedAddress;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\DataGroup\Element\AlternativeScheme;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\Header;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\PaymentPart\Translation\Translation;
use Sprain\SwissQrBill\QrBill;

trait TestQrBillCreatorTrait
{
    protected $regenerateReferenceFiles = false;

    public function validQrBillsProvider()
    {
        return [
            ['qr-minimal-setup',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformation',
                    'paymentReferenceQr'
                ])
            ],
            ['qr-payment-information-without-amount',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformationWithoutAmount',
                    'paymentReferenceQr'
                ])
            ],
            ['qr-payment-information-without-amount-but-debtor',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformationWithoutAmount',
                    'paymentReferenceQr',
                    'ultimateDebtor'
                ])
            ],
            ['qr-payment-information-without-amount-and-long-addresses',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditorLong',
                    'paymentAmountInformationWithoutAmount',
                    'paymentReferenceQr',
                    'ultimateDebtorLong'
                ])
            ],
            ['qr-payment-information-with-mediumlong-creditor-and-unknown-debtor',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditorMediumLong',
                    'paymentAmountInformationWithoutAmount',
                    'paymentReferenceQr'
                ])
            ],
            ['qr-payment-information-zero-amount',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformationZeroAmount',
                    'paymentReferenceQr',
                    'additionalInformationZeroPayment'
                ])
            ],
            ['qr-payment-reference-scor',
                $this->createQrBill([
                    'header',
                    'creditorInformationIban',
                    'creditor',
                    'paymentAmountInformation',
                    'paymentReferenceScor'
                ])
            ],
            ['qr-payment-reference-non',
                $this->createQrBill([
                    'header',
                    'creditorInformationIban',
                    'creditor',
                    'paymentAmountInformation',
                    'paymentReferenceNon'
                ])
            ],
            ['qr-ultimate-debtor',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformation',
                    'paymentReferenceQr',
                    'ultimateDebtor'
                ])
            ],
            ['qr-international-ultimate-debtor',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformation',
                    'paymentReferenceQr',
                    'internationalUltimateDebtor'
                ])
            ],
            ['qr-additional-information',
                $this->createQrBill([
                    'header',
                    'creditorInformationQrIban',
                    'creditor',
                    'paymentAmountInformation',
                    'paymentReferenceQr',
                    'additionalInformation'
                ])
            ],
            ['qr-full-set',
                $this->getQrBillFullSet()
            ],
            ['qr-alternative-schemes',
                $this->getQrBillWithAdditonalSchemes()
            ]
        ];
    }

    protected function getQrBillWithAdditonalSchemes()
    {
        $qrBill = $this->createQrBill([
            'header',
            'creditorInformationQrIban',
            'creditor',
            'paymentAmountInformation',
            'paymentReferenceQr',
        ]);

        $qrBill->addAlternativeScheme(AlternativeScheme::create('foo'));
        $qrBill->addAlternativeScheme(AlternativeScheme::create('foo'));

        return $qrBill;
    }

    protected function getQrBillFullSet()
    {
        $qrBill = $this->createQrBill([
            'header',
            'creditorInformationQrIban',
            'creditor',
            'paymentAmountInformation',
            'ultimateDebtor',
            'paymentReferenceQr',
            'additionalInformation'
        ]);

        $qrBill->addAlternativeScheme(AlternativeScheme::create('foo'));
        $qrBill->addAlternativeScheme(AlternativeScheme::create('foo'));

        return $qrBill;
    }

    public function createQrBill(array $elements)
    {
        $qrBill = QrBill::create();

        foreach ($elements as $element) {
            $this->$element($qrBill);
        }

        return $qrBill;
    }

    public function header(QrBill &$qrBill)
    {
        $header = Header::create(
            Header::QRTYPE_SPC,
            Header::VERSION_0200,
            Header::CODING_LATIN
        );
        $qrBill->setHeader($header);
    }

    public function invalidHeader(QrBill &$qrBill)
    {
        // INVALID EMPTY HEADER
        $qrBill->setHeader(Header::create('', '', 5));
    }

    public function creditorInformationIban(QrBill &$qrBill)
    {
        $creditorInformation = CreditorInformation::create('CH9300762011623852957');
        $qrBill->setCreditorInformation($creditorInformation);
    }

    public function creditorInformationQrIban(QrBill &$qrBill)
    {
        $creditorInformation = CreditorInformation::create('CH4431999123000889012');
        $qrBill->setCreditorInformation($creditorInformation);
    }

    public function inValidCreditorInformation(QrBill &$qrBill)
    {
        $creditorInformation = CreditorInformation::create('INVALIDIBAN');
        $qrBill->setCreditorInformation($creditorInformation);
    }

    public function creditor(QrBill &$qrBill)
    {
        $qrBill->setCreditor($this->structuredAddress());
    }

    public function creditorMediumLong(QrBill &$qrBill)
    {
        $qrBill->setCreditor($this->mediumLongAddress());
    }

    public function creditorLong(QrBill &$qrBill)
    {
        $qrBill->setCreditor($this->longAddress());
    }

    public function invalidCreditor(QrBill &$qrBill)
    {
        $qrBill->setCreditor($this->invalidAddress());
    }

    public function paymentAmountInformation(QrBill &$qrBill)
    {
        $paymentAmountInformation = PaymentAmountInformation::create(
            'CHF',
            25.90
        );
        $qrBill->setPaymentAmountInformation($paymentAmountInformation);
    }

    public function paymentAmountInformationWithoutAmount(QrBill &$qrBill)
    {
        $paymentAmountInformation = PaymentAmountInformation::create('EUR');
        $qrBill->setPaymentAmountInformation($paymentAmountInformation);
    }

    public function paymentAmountInformationZeroAmount(QrBill &$qrBill)
    {
        $paymentAmountInformation = PaymentAmountInformation::create('EUR', 0);
        $qrBill->setPaymentAmountInformation($paymentAmountInformation);
    }

    public function invalidPaymentAmountInformation(QrBill &$qrBill)
    {
        $paymentAmountInformation = PaymentAmountInformation::create(
            'USD', // invalid currency
            25.90
        );
        $qrBill->setPaymentAmountInformation($paymentAmountInformation);
    }

    public function paymentReferenceQr(QrBill &$qrBill)
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_QR,
            '123456789012345678901234567'
        );
        $qrBill->setPaymentReference($paymentReference);
    }

    public function paymentReferenceScor(QrBill &$qrBill)
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_SCOR,
            'RF18539007547034'
        );
        $qrBill->setPaymentReference($paymentReference);
    }

    public function paymentReferenceNon(QrBill &$qrBill)
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_NON
        );

        $qrBill->setPaymentReference($paymentReference);
    }

    public function invalidPaymentReference(QrBill &$qrBill)
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_QR,
            'INVALID REFERENCE'
        );
        $qrBill->setPaymentReference($paymentReference);
    }

    public function ultimateDebtor(QrBill &$qrBill)
    {
        $qrBill->setUltimateDebtor($this->combinedAddress());
    }

    public function ultimateDebtorLong(QrBill &$qrBill)
    {
        $qrBill->setUltimateDebtor($this->longAddress());
    }

    public function internationalUltimateDebtor(QrBill &$qrBill)
    {
        $qrBill->setUltimateDebtor(CombinedAddress::create(
            'Joachim Kraut',
            'Ewigermeisterstrasse 20',
            '80331 München',
            'DE'
        ));
    }

    public function invalidUltimateDebtor(QrBill &$qrBill)
    {
        $qrBill->setUltimateDebtor($this->invalidAddress());
    }

    public function alternativeScheme(QrBill &$qrBill)
    {
        $alternativeScheme = AlternativeScheme::create('alternativeSchemeParameter');

        $qrBill->addAlternativeScheme($alternativeScheme);
    }

    public function invalidAlternativeScheme(QrBill &$qrBill)
    {
        $alternativeScheme = (AlternativeScheme::create(''));

        $qrBill->addAlternativeScheme($alternativeScheme);
    }

    public function additionalInformation(QrBill &$qrBill)
    {
        $additionalInformation = AdditionalInformation::create("Invoice 1234568\nGardening work", 'Bill Information');
        $qrBill->setAdditionalInformation($additionalInformation);
    }

    public function additionalInformationZeroPayment(QrBill &$qrBill)
    {
        $additionalInformation = AdditionalInformation::create(Translation::get('doNotUseForPayment', 'en'));
        $qrBill->setAdditionalInformation($additionalInformation);
    }

    public function structuredAddress()
    {
        return StructuredAddress::createWithStreet(
            'Thomas LeClaire',
            'Rue examplaire',
            '22a',
            '1000',
            'Lausanne',
            'CH'
        );
    }

    public function combinedAddress()
    {
        return CombinedAddress::create(
            'Thomas LeClaire',
            'Rue examplaire 22a',
            '1000 Lausanne',
            'CH'
        );
    }

    public function mediumLongAddress()
    {
        return CombinedAddress::create(
            'Heaps of Characters International Trading Company of Switzerland GmbH',
            'Rue examplaire 22a',
            '1000 Lausanne',
            'CH'
        );
    }

    public function longAddress()
    {
        return CombinedAddress::create(
            'Heaps of Characters International Trading Company of Switzerland GmbH',
            'Street of the Mighty Long Names Where Heroes Live and Villans Die 75',
            '1000 Lausanne au bord du lac, où le soleil brille encore la nuit',
            'CH'
        );
    }

    public function invalidAddress()
    {
        return CombinedAddress::create(
            '',
            '',
            '',
            ''
        );
    }
}
