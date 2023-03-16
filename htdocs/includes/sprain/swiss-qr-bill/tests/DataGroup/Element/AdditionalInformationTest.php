<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;

final class AdditionalInformationTest extends TestCase
{
    /**
     * @dataProvider messageProvider
     */
    public function testMessage(int $numberOfValidations, ?string $value): void
    {
        $additionalInformation = AdditionalInformation::create($value);

        $this->assertSame($numberOfValidations, $additionalInformation->getViolations()->count());
    }

    public function messageProvider(): array
    {
        return [
            [0, '012345678901234567890123456'],
            [0, null],
            [0, '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890'],
            [1, '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901'], // too long
        ];
    }

    /**
     * @dataProvider billInformationProvider
     */
    public function testBillInformation(int $numberOfValidations, ?string $value)
    {
        $additionalInformation = AdditionalInformation::create(null, $value);

        $this->assertSame($numberOfValidations, $additionalInformation->getViolations()->count());
    }

    public function billInformationProvider(): array
    {
        return [
            [0, '012345678901234567890123456'],
            [0, null],
            [0, '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890'],
            [1, '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901'], // too long
        ];
    }

    public function testFormattedString(): void
    {
        $additionalInformation = AdditionalInformation::create('message');
        $this->assertSame("message", $additionalInformation->getFormattedString());

        $additionalInformation = AdditionalInformation::create('message', 'billInformation');
        $this->assertSame("message\nbillInformation", $additionalInformation->getFormattedString());
    }

    public function testQrCodeData(): void
    {
        $additionalInformation = AdditionalInformation::create('message', 'billInformation');

        $expected = [
            'message',
            AdditionalInformation::TRAILER_EPD,
            'billInformation'
        ];

        $this->assertSame($expected, $additionalInformation->getQrCodeData());
    }
}