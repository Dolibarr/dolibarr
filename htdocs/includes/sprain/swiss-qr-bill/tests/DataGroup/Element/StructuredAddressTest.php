<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;

final class StructuredAddressTest extends TestCase
{
    /**
     * @dataProvider nameProvider
     */
    public function testName($numberOfValidations, $value): void
    {
        $address = StructuredAddress::createWithoutStreet(
            $value,
            '1000',
            'Lausanne',
            'CH'
        );

        $this->assertSame($numberOfValidations, $address->getViolations()->count());
    }

    public function nameProvider(): array
    {
        return [
            [0, 'A'],
            [0, '123'],
            [0, 'Müller AG'],
            [0, 'Maria Bernasconi'],
            [0, '70 chars, character limit abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqr'],
            [1, ''],
            [1, '71 chars, above character limit abcdefghijklmnopqrstuvwxyzabcdefghijklm']
        ];
    }

    /**
     * @dataProvider streetProvider
     */
    public function testStreet(int $numberOfViolations, string $value): void
    {
        $address = StructuredAddress::createWithStreet(
            'Thomas Mustermann',
            $value,
            '22a',
            '1000',
            'Lausanne',
            'CH'
        );

        $this->assertSame($numberOfViolations, $address->getViolations()->count());
    }

    public function streetProvider(): array
    {
        return [
            [0, ''],
            [0, 'A'],
            [0, '123'],
            [0, 'Sonnenweg'],
            [0, '70 chars, character limit abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqr'],
            [1, '71 chars, above character limit abcdefghijklmnopqrstuvwxyzabcdefghijklm'],
        ];
    }

    /**
     * @dataProvider buildingNumberProvider
     */
    public function testBuildingNumber(int $numberOfViolations, ?string $value): void
    {
        $address = StructuredAddress::createWithStreet(
            'Thomas Mustermann',
            'Musterweg',
            $value,
            '1000',
            'Lausanne',
            'CH'
        );

        $this->assertSame($numberOfViolations, $address->getViolations()->count());
    }

    public function buildingNumberProvider(): array
    {
        return [
            [0, null],
            [0, ''],
            [0, '1'],
            [0, '123'],
            [0, '22a'],
            [0, '16 chars, -limit'],
            [1, '17 chars, ++limit']
        ];
    }

    /**
     * @dataProvider postalCodeProvider
     */
    public function testPostalCode(int $numberOfViolations, string $value): void
    {
        $address = StructuredAddress::createWithStreet(
            'Thomas Mustermann',
            'Musterweg',
            '22a',
            $value,
            'Lausanne',
            'CH'
        );

        $this->assertSame($numberOfViolations, $address->getViolations()->count());
    }

    public function postalCodeProvider(): array
    {
        return [
            [0, '1'],
            [0, '123'],
            [0, '22a'],
            [0, '16 chars, -limit'],
            [1, ''],
            [1, '17 chars, ++limit']
        ];
    }

    /**
     * @dataProvider cityProvider
     */
    public function testCity(int $numberOfViolations, string $value)
    {
        $address = StructuredAddress::createWithStreet(
            'Thomas Mustermann',
            'Musterweg',
            '22a',
            '1000',
            $value,
            'CH'
        );

        $this->assertSame($numberOfViolations, $address->getViolations()->count());
    }

    public function cityProvider(): array
    {
        return [
            [0, 'A'],
            [0, 'Zürich'],
            [0, '35 chars, character limit abcdefghi'],
            [1, ''],
            [1, '36 chars, above character limit abcd']
        ];
    }

    /**
     * @dataProvider countryProvider
     */
    public function testCountry($numberOfValidations, $value): void
    {
        $address = StructuredAddress::createWithoutStreet(
            'Thomas Mustermann',
            '1000',
            'Lausanne',
            $value
        );

        $this->assertSame($numberOfValidations, $address->getViolations()->count());
    }

    public function countryProvider(): array
    {
        return [
            [0, 'CH'],
            [0, 'ch'],
            [0, 'DE'],
            [0, 'LI'],
            [0, 'US'],
            [1, ''],
            [1, 'XX'],
            [1, 'SUI'],
            [1, '12']
        ];
    }

    public function testQrCodeData()
    {
        $address = StructuredAddress::createWithStreet(
            'Thomas Mustermann',
            'Musterweg',
            '22a',
            '1000',
            'Lausanne',
            'CH'
        );

        $expected = [
            'S',
            'Thomas Mustermann',
            'Musterweg',
            '22a',
            '1000',
            'Lausanne',
            'CH',
        ];

        $this->assertSame($expected, $address->getQrCodeData());
    }

    /**
     * @dataProvider addressProvider
     */
    public function testFullAddressString(StructuredAddress $address, $expected): void
    {
        $this->assertSame($expected, $address->getFullAddress());
    }

    public function addressProvider(): array
    {
        return [
            [
                StructuredAddress::createWithStreet(
                    'Thomas Mustermann',
                    'Musterweg',
                    '22a',
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\nMusterweg 22a\n1000 Lausanne"
            ],
            [
                StructuredAddress::createWithStreet(
                    'Thomas Mustermann',
                    'Musterweg',
                    null,
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\nMusterweg\n1000 Lausanne"
            ],
            [
                StructuredAddress::createWithoutStreet(
                    'Thomas Mustermann',
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\n1000 Lausanne"
            ],
            [
                StructuredAddress::createWithoutStreet(
                    'Thomas Mustermann',
                    '9490',
                    'Vaduz',
                    'LI'
                ),
                "Thomas Mustermann\nLI-9490 Vaduz"
            ],
            [
                StructuredAddress::createWithoutStreet(
                    'Thomas Mustermann',
                    '80331',
                    'München',
                    'DE'
                ),
                "Thomas Mustermann\nDE-80331 München"
            ],
            [
                StructuredAddress::createWithStreet(
                    "Thomas\nMustermann",
                    "Musterweg\t\ram\rRhein",
                    '12',
                    '80331',
                    'München',
                    ' DE '
                ),
                "Thomas Mustermann\nMusterweg am Rhein 12\nDE-80331 München"
            ],
            [
                StructuredAddress::createWithStreet(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Street of the Mighty Long Names Where Heroes Live and Villans Die',
                    '75',
                    '1000',
                    'Lausanne au bord du lac, où le soleil brille encore la nuit',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH\nStreet of the Mighty Long Names Where Heroes Live and Villans Die 75\n1000 Lausanne au bord du lac, où le soleil brille encore la nuit"
            ],
            [
                StructuredAddress::createWithStreet(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Rue examplaire',
        '22a',
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH\nRue examplaire 22a\n1000 Lausanne"
            ],

        ];
    }

    /**
     * @dataProvider addressProviderReceipt
     */
    public function testFullAddressStringForReceipt(StructuredAddress $address, $expected): void
    {
        $this->assertSame($expected, $address->getFullAddress(true));
    }

    public function addressProviderReceipt(): array
    {
        return [
            [
                StructuredAddress::createWithStreet(
                    'Thomas Mustermann',
                    'Musterweg',
                    '22a',
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\nMusterweg 22a\n1000 Lausanne"
            ],
            [
                StructuredAddress::createWithStreet(
                    'Thomas Mustermann',
                    'Musterweg',
                    null,
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\nMusterweg\n1000 Lausanne"
            ],
            [
                StructuredAddress::createWithoutStreet(
                    'Thomas Mustermann',
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\n1000 Lausanne"
            ],
            [
                StructuredAddress::createWithoutStreet(
                    'Thomas Mustermann',
                    '9490',
                    'Vaduz',
                    'LI'
                ),
                "Thomas Mustermann\nLI-9490 Vaduz"
            ],
            [
                StructuredAddress::createWithoutStreet(
                    'Thomas Mustermann',
                    '80331',
                    'München',
                    'DE'
                ),
                "Thomas Mustermann\nDE-80331 München"
            ],
            [
                StructuredAddress::createWithStreet(
                    "Thomas\nMustermann",
                    "Musterweg\t\ram\rRhein",
                    '12',
                    '80331',
                    'München',
                    ' DE '
                ),
                "Thomas Mustermann\nMusterweg am Rhein 12\nDE-80331 München"
            ],
            [
                StructuredAddress::createWithStreet(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Street of the Mighty Long Names Where Heroes Live and Villans Die',
                    '75',
                    '1000',
                    'Lausanne au bord du lac, où le soleil brille encore la nuit',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH"
            ],
            [
                StructuredAddress::createWithStreet(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Rue examplaire',
                    '22a',
                    '1000',
                    'Lausanne',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH\n1000 Lausanne"
            ],
        ];
    }
}