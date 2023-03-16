<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;

final class PaymentReferenceTest extends TestCase
{
    /**
     * @dataProvider qrReferenceProvider
     */
    public function testQrReference(int $numberOfViolations, ?string $value): void
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_QR,
            $value
        );

        $this->assertSame($numberOfViolations, $paymentReference->getViolations()->count());
    }

    public function qrReferenceProvider(): array
    {
        return [
            [0, '012345678901234567890123456'],
            [0, ' 01 23456 78901 23456 78901 23456 '],
            [1, null],
            [1, ''],
            [1, ' '],
            [1, '01234567890123456789012345'],   // too short
            [1, '0123456789012345678901234567'], // too long
            [1, 'Ä12345678901234567890123456']   // invalid characters
        ];
    }

    /**
     * @dataProvider scorReferenceProvider
     */
    public function testScorReference(int $numberOfViolations, ?string $value): void
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_SCOR,
            $value
        );

        $this->assertSame($numberOfViolations, $paymentReference->getViolations()->count());
    }

    public function scorReferenceProvider(): array
    {
        return [
            [0, 'RF18539007547034'],
            [0, ' RF18 5390 0754 7034 '],
            [1, null],
            [1, ''],
            [1, ' '],
            [1, 'RF12'],// too short
            [1, 'RF181234567890123456789012'], // too long
            [1, 'RF1853900754703Ä']  // invalid characters
        ];
    }

    /**
     * @dataProvider nonReferenceProvider
     */
    public function testNonReference(int $numberOfViolations, ?string $value): void
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_NON,
            $value
        );

        $this->assertSame($numberOfViolations, $paymentReference->getViolations()->count());
    }

    public function nonReferenceProvider()
    {
        return [
            [0, null],
            [0, ''],
            [0, ' '],
            [1, 'anything-non-empty']
        ];
    }

    /**
     * @dataProvider formattedReferenceProvider
     */
    public function testFormattedReference(string $type, ?string  $reference, ?string  $formattedReference): void
    {
        $paymentReference = PaymentReference::create(
            $type,
            $reference
        );

        $this->assertSame($formattedReference, $paymentReference->getFormattedReference());
    }

    public function formattedReferenceProvider(): array
    {
        return [
            [PaymentReference::TYPE_QR, '012345678901234567890123456', '01 23456 78901 23456 78901 23456'],
            [PaymentReference::TYPE_QR, ' 0123456789 0123456789 0123456 ', '01 23456 78901 23456 78901 23456'],
            [PaymentReference::TYPE_SCOR, 'RF18539007547034', 'RF18 5390 0754 7034'],
            [PaymentReference::TYPE_SCOR, ' R F1853900754703 4 ', 'RF18 5390 0754 7034'],
            [PaymentReference::TYPE_NON, null, null],
            [PaymentReference::TYPE_NON, '', null],
            [PaymentReference::TYPE_NON, ' ', null],
        ];
    }

    public function testQrCodeData(): void
    {
        $paymentReference = PaymentReference::create(
            PaymentReference::TYPE_QR,
            '012345678901234567890123456'
        );

        $expected = [
            PaymentReference::TYPE_QR,
            '012345678901234567890123456'
        ];

        $this->assertSame($expected, $paymentReference->getQrCodeData());
    }
}