<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\PaymentPart\Output\FpdfOutput;

use Fpdf\Fpdf;
use Fpdf\Traits\MemoryImageSupport\MemImageTrait;
use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\Exception\InvalidFpdfImageFormat;
use Sprain\SwissQrBill\PaymentPart\Output\FpdfOutput\FpdfOutput;
use Sprain\SwissQrBill\PaymentPart\Output\FpdfOutput\UnsupportedEnvironmentException;
use Sprain\SwissQrBill\QrBill;
use Sprain\SwissQrBill\QrCode\QrCode;
use Sprain\Tests\SwissQrBill\TestQrBillCreatorTrait;

final class FpdfOutputTest extends TestCase
{
    use TestQrBillCreatorTrait;

    /**
     * @dataProvider validQrBillsProvider
     */
    public function testValidQrBills(string $name, QrBill $qrBill): void
    {
        $variations = [
            [
                'printable' => false,
                'format' => QrCode::FILE_FORMAT_PNG,
                'file' => dirname(dirname(dirname(__DIR__))) . '/TestData/FpdfOutput/' . $name . '.pdf'
            ],
            [
                'printable' => true,
                'format' => QrCode::FILE_FORMAT_PNG,
                'file' => dirname(dirname(dirname(__DIR__))) . '/TestData/FpdfOutput/' . $name . '.print.pdf'
            ]
        ];

        foreach ($variations as $variation) {
            $file = $variation['file'];

            $fpdf = $this->instantiateFpdf();
            $fpdf->AddPage();

            $output = new FpdfOutput($qrBill, 'en', $fpdf);
            $output
                ->setPrintable($variation['printable'])
                ->setQrCodeImageFormat($variation['format'])
                ->getPaymentPart();

            if ($this->regenerateReferenceFiles) {
                $fpdf->Output($file, 'F');
            }
            
            $contents = $this->getActualPdfContents($fpdf->Output($file, 'S'));

            $this->assertNotNull($contents);
            $this->assertSame($this->getActualPdfContents(file_get_contents($file)), $contents);
        }
    }

    public function testItThrowsSvgNotSupportedException(): void
    {
        $this->expectException(InvalidFpdfImageFormat::class);

        $qrBill = $this->createQrBill([
            'header',
            'creditorInformationQrIban',
            'creditor',
            'paymentAmountInformation',
            'paymentReferenceQr'
        ]);

        $fpdf = $this->instantiateFpdf();
        $fpdf->AddPage();

        $output = new FpdfOutput($qrBill, 'en', $fpdf);
        $output
            ->setQrCodeImageFormat(QrCode::FILE_FORMAT_SVG)
            ->getPaymentPart();
    }

    public function testItThrowsUnsupportedEnvironmentException(): void
    {
        if ((bool)ini_get('allow_url_fopen')) {
            $this->markTestSkipped("This test only works in hardened environment.");
        }

        $this->expectException(UnsupportedEnvironmentException::class);

        $qrBill = $this->createQrBill([
            'header',
            'creditorInformationQrIban',
            'creditor',
            'paymentAmountInformation',
            'paymentReferenceQr'
        ]);

        $fpdf = $this->instantiateFpdf(false);
        $fpdf->AddPage();

        $output = new FpdfOutput($qrBill, 'en', $fpdf);
        $output
            ->setQrCodeImageFormat(QrCode::FILE_FORMAT_PNG)
            ->getPaymentPart();
    }

    private function getActualPdfContents(string $fileContents): ?string
    {
        // Extract actual pdf content and ignore all meta data which may differ in different versions of Fpdf
        $pattern = '/stream(.*?)endstream/s';
        preg_match($pattern, $fileContents, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }
        return null;
    }

    private function instantiateFpdf($withMemImageSupport = null): Fpdf
    {
        if ($withMemImageSupport === null) {
            $withMemImageSupport = !ini_get('allow_url_fopen');
        }

        if ($withMemImageSupport) {
            return new class('P', 'mm', 'A4') extends Fpdf {
                use MemImageTrait;
            };
        }

        return new Fpdf('P', 'mm', 'A4');
    }
}
