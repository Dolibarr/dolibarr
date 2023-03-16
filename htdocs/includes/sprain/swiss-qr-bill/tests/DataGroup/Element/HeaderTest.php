<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\Header;

final class HeaderTest extends TestCase
{
    /**
     * @dataProvider qrTypeProvider
     */
    public function testQrType(int $numberOfViolations, string $value): void
    {
        $header = Header::create(
            $value,
            '0200',
            1
        );

        $this->assertSame($numberOfViolations, $header->getViolations()->count());
    }

    public function qrTypeProvider(): array
    {
        return [
            [0, 'SPC'],
            [0, 'foo'],
            [0, '123'],
            [0, '000'],
            [0, 'A1B'],
            [0, '1AB'],
            [0, 'AB1'],
            [1, 'SP'],
            [1, 'SPCC'],
            [1, 'fo'],
            [1, 'fooo'],
            [1, '12'],
            [1, '00'],
            [1, 'SP*'],
            [1, '*SP'],
        ];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testVersionIsValid(int $numberOfViolations, string $value): void
    {
        $header = Header::create(
            'SPC',
            $value,
            1
        );

        $this->assertSame($numberOfViolations, $header->getViolations()->count());
    }

    public function versionProvider(): array
    {
        return [
            [0, '0200'],
            [0, '1234'],
            [0, '0000'],
            [0, '9999'],
            [1, '010'],
            [1, '234'],
            [1, 'ABCD'],
            [1, 'abcd'],
            [1, 'a1b2'],
            [1, '1a2b'],
            [1, '010*'],
            [1, '*010']
        ];
    }

    /**
     * @dataProvider codingProvider
     */
    public function testCodingIsValid(int $numberOfViolations, int $value): void
    {
        $header = Header::create(
            'SPC',
            '0200',
            $value
        );

        $this->assertSame($numberOfViolations, $header->getViolations()->count());
    }

    public function codingProvider()
    {
        return [
            [0, 0],
            [0, 1],
            [0, 2],
            [0, 3],
            [0, 4],
            [0, 5],
            [0, 6],
            [0, 7],
            [0, 8],
            [0, 9],
            [1, 11],
            [1, 222],
        ];
    }

    public function testQrCodeData(): void
    {
        $header = Header::create(
            'SPC',
            '0200',
            1
        );

        $expected = [
            'SPC',
            '0200',
            1
        ];

        $this->assertSame($expected, $header->getQrCodeData());
    }
}