<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Matrix\MatrixInterface;

class ConsoleResult extends AbstractResult
{
    private const TWO_BLOCKS = [
        0 => ' ',
        1 => "\xe2\x96\x80",
        2 => "\xe2\x96\x84",
        3 => "\xe2\x96\x88",
    ];

    private string $colorEscapeCode;

    public function __construct(
        MatrixInterface $matrix,
        ColorInterface $foreground,
        ColorInterface $background
    ) {
        parent::__construct($matrix);

        $this->colorEscapeCode = sprintf(
            "\e[38;2;%d;%d;%dm\e[48;2;%d;%d;%dm",
            $foreground->getRed(),
            $foreground->getGreen(),
            $foreground->getBlue(),
            $background->getRed(),
            $background->getGreen(),
            $background->getBlue()
        );
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }

    public function getString(): string
    {
        $matrix = $this->getMatrix();

        $side = $matrix->getBlockCount();
        $marginLeft = $this->colorEscapeCode.self::TWO_BLOCKS[0].self::TWO_BLOCKS[0];
        $marginRight = self::TWO_BLOCKS[0].self::TWO_BLOCKS[0]."\e[0m".PHP_EOL;
        $marginVertical = $marginLeft.str_repeat(self::TWO_BLOCKS[0], $side).$marginRight;

        $qrCodeString = $marginVertical;
        for ($rowIndex = 0; $rowIndex < $side; $rowIndex += 2) {
            $qrCodeString .= $marginLeft;
            for ($columnIndex = 0; $columnIndex < $side; ++$columnIndex) {
                $combined = $matrix->getBlockValue($rowIndex, $columnIndex);
                if ($rowIndex + 1 < $side) {
                    $combined |= $matrix->getBlockValue($rowIndex + 1, $columnIndex) << 1;
                }
                $qrCodeString .= self::TWO_BLOCKS[$combined];
            }
            $qrCodeString .= $marginRight;
        }
        $qrCodeString .= $marginVertical;

        return $qrCodeString;
    }
}
