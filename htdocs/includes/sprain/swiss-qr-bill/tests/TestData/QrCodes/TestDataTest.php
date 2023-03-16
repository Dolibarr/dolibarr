<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\TestData\QrCodes;

use PHPUnit\Framework\TestCase;

/**
 * These tests make sure that the qr code examples are the unchanged reference files to be used in other tests.
 */
class TestDataTest extends TestCase
{
    /**
     * @dataProvider qrFileProvider
     */
    public function testQrFile(string $file, string $hash): void
    {
        $this->assertSame(
            $hash,
            hash_file('md5', $file)
        );
    }

    public function qrFileProvider(): array
    {
        return [
            [__DIR__ . '/qr-additional-information.png', 'c690b3c552cb31057a34d1bbe1e3a158'],
            [__DIR__ . '/qr-alternative-schemes.png', 'ca22587f45609486ec9128f8bfb9ef83'],
            [__DIR__ . '/qr-full-set.png', 'ae3aa21373bb4b6ad61a8df96995f06b'],
            [__DIR__ . '/qr-international-ultimate-debtor.png', '3178b54237dbbf43df99ea98bba82aaa'],
            [__DIR__ . '/qr-minimal-setup.png', '246e856c5c75e92ad9e70298e870d957'],
            [__DIR__ . '/qr-payment-information-with-mediumlong-creditor-and-unknown-debtor.png', 'c347c35996eee781942ee2fa35da0a88'],
            [__DIR__ . '/qr-payment-information-without-amount-and-long-addresses.png', 'c5d23d3fe94aeed310bdf3b9349ce2f9'],
            [__DIR__ . '/qr-payment-information-without-amount.png', 'd21e7106158945a52c7b2be00fbd5369'],
            [__DIR__ . '/qr-payment-information-without-amount-but-debtor.png', '67b382fdaa8cd69eb328862d8393fb9f'],
            [__DIR__ . '/qr-payment-information-zero-amount.png', '66c1373bac50b98705d94b33462a72c6'],
            [__DIR__ . '/qr-payment-reference-non.png', '5843f882b1883f8202c43c17fa07ae86'],
            [__DIR__ . '/qr-payment-reference-scor.png', '4ef959e7b428650ec4198491a6d91f1c'],
            [__DIR__ . '/qr-ultimate-debtor.png', '9d1d257c2b65d9d04d4d7a20ced6ef1a'],

            [__DIR__ . '/proof-of-validation.png', '5089538f592679b5cd69130b7f16fe24'],
        ];
    }
}