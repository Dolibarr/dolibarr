<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\PdfResult;
use Endroid\QrCode\Writer\Result\ResultInterface;

final class PdfWriter implements WriterInterface
{
    public const WRITER_OPTION_UNIT = 'unit';
    public const WRITER_OPTION_PDF = 'fpdf';
    public const WRITER_OPTION_X = 'x';
    public const WRITER_OPTION_Y = 'y';

    public function write(QrCodeInterface $qrCode, LogoInterface|null $logo = null, LabelInterface|null $label = null, array $options = []): ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        $unit = 'mm';
        if (isset($options[self::WRITER_OPTION_UNIT])) {
            $unit = $options[self::WRITER_OPTION_UNIT];
        }

        $allowedUnits = ['mm', 'pt', 'cm', 'in'];
        if (!in_array($unit, $allowedUnits)) {
            throw new \Exception(sprintf('PDF Measure unit should be one of [%s]', implode(', ', $allowedUnits)));
        }

        $labelSpace = 0;
        if ($label instanceof LabelInterface) {
            $labelSpace = 30;
        }

        if (!class_exists(\FPDF::class)) {
            throw new \Exception('Unable to find FPDF: check your installation');
        }

        $foregroundColor = $qrCode->getForegroundColor();
        if ($foregroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels');
        }
        $backgroundColor = $qrCode->getBackgroundColor();
        if ($backgroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels');
        }

        if (isset($options[self::WRITER_OPTION_PDF])) {
            $fpdf = $options[self::WRITER_OPTION_PDF];
            if (!$fpdf instanceof \FPDF) {
                throw new \Exception('pdf option must be an instance of FPDF');
            }
        } else {
            // @todo Check how to add label height later
            $fpdf = new \FPDF('P', $unit, [$matrix->getOuterSize(), $matrix->getOuterSize() + $labelSpace]);
            $fpdf->AddPage();
        }

        $x = 0;
        if (isset($options[self::WRITER_OPTION_X])) {
            $x = $options[self::WRITER_OPTION_X];
        }
        $y = 0;
        if (isset($options[self::WRITER_OPTION_Y])) {
            $y = $options[self::WRITER_OPTION_Y];
        }

        $fpdf->SetFillColor($backgroundColor->getRed(), $backgroundColor->getGreen(), $backgroundColor->getBlue());
        $fpdf->Rect($x, $y, $matrix->getOuterSize(), $matrix->getOuterSize(), 'F');
        $fpdf->SetFillColor($foregroundColor->getRed(), $foregroundColor->getGreen(), $foregroundColor->getBlue());

        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); ++$columnIndex) {
                if (1 === $matrix->getBlockValue($rowIndex, $columnIndex)) {
                    $fpdf->Rect(
                        $x + $matrix->getMarginLeft() + ($columnIndex * $matrix->getBlockSize()),
                        $y + $matrix->getMarginLeft() + ($rowIndex * $matrix->getBlockSize()),
                        $matrix->getBlockSize(),
                        $matrix->getBlockSize(),
                        'F'
                    );
                }
            }
        }

        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $fpdf, $x, $y, $matrix->getOuterSize());
        }

        if ($label instanceof LabelInterface) {
            $fpdf->SetXY($x, $y + $matrix->getOuterSize() + $labelSpace - 25);
            $fpdf->SetFont('Helvetica', '', $label->getFont()->getSize());
            $fpdf->Cell($matrix->getOuterSize(), 0, $label->getText(), 0, 0, 'C');
        }

        return new PdfResult($matrix, $fpdf);
    }

    private function addLogo(LogoInterface $logo, \FPDF $fpdf, float $x, float $y, float $size): void
    {
        $logoPath = $logo->getPath();
        $logoHeight = $logo->getResizeToHeight();
        $logoWidth = $logo->getResizeToWidth();

        if (null === $logoHeight || null === $logoWidth) {
            $imageSize = \getimagesize($logoPath);
            if (!$imageSize) {
                throw new \Exception(sprintf('Unable to read image size for logo "%s"', $logoPath));
            }
            [$logoSourceWidth, $logoSourceHeight] = $imageSize;

            if (null === $logoWidth) {
                $logoWidth = (int) $logoSourceWidth;
            }

            if (null === $logoHeight) {
                $aspectRatio = $logoWidth / $logoSourceWidth;
                $logoHeight = (int) ($logoSourceHeight * $aspectRatio);
            }
        }

        $logoX = $x + $size / 2 - $logoWidth / 2;
        $logoY = $y + $size / 2 - $logoHeight / 2;

        $fpdf->Image($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
    }
}
