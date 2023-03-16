<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\DataGroup\Element;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;

final class PaymentAmountInformationTest extends TestCase
{
    /**
     * @dataProvider amountProvider
     */
    public function testAmount(int $numberOfViolations, ?float $value): void
    {
        $paymentAmountInformation = PaymentAmountInformation::create(
            'CHF',
            $value
        );

        $this->assertSame($numberOfViolations, $paymentAmountInformation->getViolations()->count());
    }

    public function amountProvider(): array
    {
        return [
            [0, null],
            [0, 0],
            [0, 11.11],
            [0, 100.2],
            [0, 999999999.99],
            [1, -0.01],
            [1, 1999999999.99],
            // [1, 11.111], @todo: only two decimal places should be allowed
        ];
    }

    /**
     * @dataProvider currencyProvider
     */
    public function testCurrency(int $numberOfViolations, string $value): void
    {
        $paymentAmountInformation = PaymentAmountInformation::create(
            $value,
            25
        );

        $this->assertSame($numberOfViolations, $paymentAmountInformation->getViolations()->count());
    }

    public function currencyProvider(): array
    {
        return [
            [0, 'CHF'],
            [0, 'EUR'],
            [0, 'chf'],
            [0, 'eur'],
            [1, 'USD'],
            [1, 'PLN'],
            [1, ' chf '],
            [1, ' EUR']
        ];
    }

    /**
     * @dataProvider formattedAmountProvider
     */
    public function testFormattedAmount(float $amount, string $formattedAmount)
    {
        $paymentAmountInformation = PaymentAmountInformation::create(
            'CHF',
            $amount
        );

        $this->assertSame($formattedAmount, $paymentAmountInformation->getFormattedAmount());
    }

    public function formattedAmountProvider(): array
    {
        return [
            [0, '0.00'],
            [25, '25.00'],
            [1234.5, '1 234.50'],
            [1234.55, '1 234.55'],
            [12345.60, '12 345.60'],
            [1234567, '1 234 567.00'],
        ];
    }

    public function testQrCodeData(): void
    {
        $paymentAmountInformation = PaymentAmountInformation::create(
            'CHF',
            25
        );

        $expected = [
            '25.00',
            'CHF'
        ];

        $this->assertSame($expected, $paymentAmountInformation->getQrCodeData());
    }
}