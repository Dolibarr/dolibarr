<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\CombinedAddress;

final class CombinedAddressTest extends TestCase
{
    /**
     * @dataProvider nameProvider
     */
    public function testName($numberOfValidations, $value): void
    {
        $address = CombinedAddress::create(
            $value,
            'Musterweg 22a',
            '1000 Lausanne',
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
     * @dataProvider addressLine1Provider
     */
    public function testAddressLine1(int $numberOfValidations, ?string $value): void
    {
        $address = CombinedAddress::create(
            'Thomas Mustermann',
            $value,
            '1000 Lausanne',
            'CH'
        );

        $this->assertSame($numberOfValidations, $address->getViolations()->count());
    }

    public function addressLine1Provider(): array
    {
        return [
            [0, null],
            [0, ''],
            [0, 'A'],
            [0, '123'],
            [0, 'Sonnenweg'],
            [0, '70 chars, character limit abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqr'],
            [1, '71 chars, above character limit abcdefghijklmnopqrstuvwxyzabcdefghijklm']
        ];
    }

    /**
     * @dataProvider addressLine2Provider
     */
    public function testAddressLine2(int $numberOfValidations, string $value): void
    {
        $address = CombinedAddress::create(
            'Thomas Mustermann',
            'Musterweg 22a',
            $value,
            'CH'
        );

        $this->assertSame($numberOfValidations, $address->getViolations()->count());
    }

    public function addressLine2Provider(): array
    {
        return [
            [0, 'A'],
            [0, '123'],
            [0, 'Sonnenweg'],
            [0, '70 chars, character limit abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqr'],
            [1, ''],
            [1, '71 chars, above character limit abcdefghijklmnopqrstuvwxyzabcdefghijklm']
        ];
    }

    public function testQrCodeData(): void
    {
        $address = CombinedAddress::create(
            'Thomas Mustermann',
            'Musterweg 22a',
            '1000 Lausanne',
            'CH'
        );

        $expected = [
            'K',
            'Thomas Mustermann',
            'Musterweg 22a',
            '1000 Lausanne',
            '',
            '',
            'CH',
        ];

        $this->assertSame($expected, $address->getQrCodeData());
    }

    /**
     * @dataProvider countryProvider
     */
    public function testCountry(int $numberOfValidations, string $value): void
    {
        $address = CombinedAddress::create(
            'Thomas Mustermann',
            'Musterweg 22a',
            '1000 Lausanne',
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


    /**
     * @dataProvider addressProvider
     */
    public function testFullAddressString(CombinedAddress $address, string $expected): void
    {
        $this->assertSame($expected, $address->getFullAddress());
    }

    public function addressProvider(): array
    {
        return [
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    'Musterweg 22a',
                    '1000 Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\nMusterweg 22a\n1000 Lausanne"
            ],
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    null,
                    '1000 Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\n1000 Lausanne"
            ],
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    'Musterweg 22a',
                    '9490 Vaduz',
                    'LI'
                ),
                "Thomas Mustermann\nMusterweg 22a\nLI-9490 Vaduz"
            ],
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    'Musterweg 22a',
                    '80331 München',
                    'DE'
                ),
                "Thomas Mustermann\nMusterweg 22a\nDE-80331 München"
            ],
            [
                CombinedAddress::create(
                    "Thomas\nMustermann",
                    "Musterweg\t22a",
                    "80331\r München",
                    ' DE '
                ),
                "Thomas Mustermann\nMusterweg 22a\nDE-80331 München"
            ],
            [
            CombinedAddress::create(
                'Heaps of Characters International Trading Company of Switzerland GmbH',
                'Street of the Mighty Long Names Where Heroes Live and Villans Die 75',
                '1000 Lausanne au bord du lac, où le soleil brille encore la nuit',
                'CH'
            ),
                "Heaps of Characters International Trading Company of Switzerland GmbH\nStreet of the Mighty Long Names Where Heroes Live and Villans Die 75\n1000 Lausanne au bord du lac, où le soleil brille encore la nuit"
            ],
            [
                CombinedAddress::create(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Rue examplaire 22a',
                    '1000 Lausanne',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH\nRue examplaire 22a\n1000 Lausanne"
            ],
        ];
    }

    /**
     * @dataProvider addressProviderReceipt
     */
    public function testFullAddressStringForReceipt(CombinedAddress $address, string $expected): void
    {
        $this->assertSame($expected, $address->getFullAddress(true));
    }

    public function addressProviderReceipt(): array
    {
        return [
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    'Musterweg 22a',
                    '1000 Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\nMusterweg 22a\n1000 Lausanne"
            ],
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    null,
                    '1000 Lausanne',
                    'CH'
                ),
                "Thomas Mustermann\n1000 Lausanne"
            ],
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    'Musterweg 22a',
                    '9490 Vaduz',
                    'LI'
                ),
                "Thomas Mustermann\nMusterweg 22a\nLI-9490 Vaduz"
            ],
            [
                CombinedAddress::create(
                    'Thomas Mustermann',
                    'Musterweg 22a',
                    '80331 München',
                    'DE'
                ),
                "Thomas Mustermann\nMusterweg 22a\nDE-80331 München"
            ],
            [
                CombinedAddress::create(
                    "Thomas\nMustermann",
                    "Musterweg\t22a",
                    "80331\r München",
                    ' DE '
                ),
                "Thomas Mustermann\nMusterweg 22a\nDE-80331 München"
            ],
            [
                CombinedAddress::create(
                    "Thomas\nMustermann",
                    "Musterweg\t22a",
                    "80331\r München",
                    ' DE '
                ),
                "Thomas Mustermann\nMusterweg 22a\nDE-80331 München"
            ],
            [
                CombinedAddress::create(
                    "Thomas\nMustermann",
                    "Musterweg\t22a",
                    "80331\r München",
                    ' DE '
                ),
                "Thomas Mustermann\nMusterweg 22a\nDE-80331 München"
            ],
            [
                CombinedAddress::create(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Street of the Mighty Long Names Where Heroes Live and Villans Die 75',
                    '1000 Lausanne au bord du lac, où le soleil brille encore la nuit',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH"
            ],
            [
                CombinedAddress::create(
                    'Heaps of Characters International Trading Company of Switzerland GmbH',
                    'Rue examplaire 22a',
                    '1000 Lausanne',
                    'CH'
                ),
                "Heaps of Characters International Trading Company of Switzerland GmbH\n1000 Lausanne"
            ],
        ];
    }
}