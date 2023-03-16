<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\AlternativeScheme;

final class AlternativeSchemeTest extends TestCase
{
    /**
     * @dataProvider parameterProvider
     */
    public function testParameter(int $numberOfValidations, string $value): void
    {
        $alternativeScheme = AlternativeScheme::create($value);

        $this->assertSame($numberOfValidations, $alternativeScheme->getViolations()->count());
    }

    public function parameterProvider(): array
    {
        return [
            [0, '1'],
            [0, 'foo'],
            [0, '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890'],

            // examples as shown in https://www.paymentstandards.ch/dam/downloads/qrcodegenerator.java
            [0, '1;1.1;1278564;1A-2F-43-AC-9B-33-21-B0-CC-D4-28-56;TCXVMKC22;2017-02-10T15:12:39;2017-02-10T15:18:16'],
            [0, '2;2a-2.2r;_R1-CH2_ConradCH-2074-1_3350_2017-03-13T10:23:47_16,99_0,00_0,00_0,00_0,00_+8FADt/DQ=_1=='],

            [1, ''],
            [1, '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901'] // too long
        ];
    }
}