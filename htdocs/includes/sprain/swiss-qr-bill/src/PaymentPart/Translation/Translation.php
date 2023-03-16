<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\PaymentPart\Translation;

final class Translation
{
    private const TRANSLATIONS = [
        'de' => [
            'paymentPart' => 'Zahlteil',
            'creditor' => 'Konto / Zahlbar an',
            'reference' => 'Referenz',
            'additionalInformation' => 'Zusätzliche Informationen',
            'currency' => 'Währung',
            'amount' => 'Betrag',
            'receipt' => 'Empfangsschein',
            'acceptancePoint' => 'Annahmestelle',
            'separate' => 'Vor der Einzahlung abzutrennen',
            'payableBy' => 'Zahlbar durch',
            'payableByName' => 'Zahlbar durch (Name/Adresse)',
            'inFavorOf' => 'Zugunsten',
            'doNotUseForPayment' => 'NICHT ZUR ZAHLUNG VERWENDEN',
        ],

        'fr' => [
            'paymentPart' => 'Section paiement',
            'creditor' => 'Compte / Payable à',
            'reference' => 'Référence',
            'additionalInformation' => 'Informations supplémentaires',
            'currency' => 'Monnaie',
            'amount' => 'Montant',
            'receipt' => 'Récépissé',
            'acceptancePoint' => 'Point de dépôt',
            'separate' => 'A détacher avant le versement',
            'payableBy' => 'Payable par',
            'payableByName' => 'Payable par (nom/adresse)',
            'inFavorOf' => 'En faveur de',
            'doNotUseForPayment' => 'NE PAS UTILISER POUR LE PAIEMENT',
        ],

        'it' => [
            'paymentPart' => 'Sezione pagamento',
            'creditor' => 'Conto / Pagabile a',
            'reference' => 'Riferimento',
            'additionalInformation' => 'Informazioni supplementari',
            'currency' => 'Valuta',
            'amount' => 'Importo',
            'receipt' => 'Ricevuta',
            'acceptancePoint' => 'Punto di accettazione',
            'separate' => 'Da staccare prima del versamento',
            'payableBy' => 'Pagabile da',
            'payableByName' => 'Pagabile da (nome/indirizzo)',
            'inFavorOf' => 'A favore di',
            'doNotUseForPayment' => 'NON UTILIZZARE PER IL PAGAMENTO',
        ],

        'en' => [
            'paymentPart' => 'Payment part',
            'creditor' => 'Account / Payable to',
            'reference' => 'Reference',
            'additionalInformation' => 'Additional information',
            'currency' => 'Currency',
            'amount' => 'Amount',
            'receipt' => 'Receipt',
            'acceptancePoint' => 'Acceptance point',
            'separate' => 'Separate before paying in',
            'payableBy' => 'Payable by',
            'payableByName' => 'Payable by (name/address)',
            'inFavorOf' => 'In favour of',
            'doNotUseForPayment' => 'DO NOT USE FOR PAYMENT'
        ]
    ];

    public static function getAllByLanguage($language): ?array
    {
        return self::TRANSLATIONS[$language] ?? null;
    }

    public static function get(string $key, string $language): ?string
    {
        $translations = self::getAllByLanguage($language);
        if (! is_array($translations) || ! array_key_exists($key, $translations)) {
            return null;
        }

        return $translations[$key];
    }
}
