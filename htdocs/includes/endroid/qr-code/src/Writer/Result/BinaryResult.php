<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Matrix\MatrixInterface;

final class BinaryResult extends AbstractResult
{
    public function __construct(MatrixInterface $matrix)
    {
        parent::__construct($matrix);
    }

    public function getString(): string
    {
        $matrix = $this->getMatrix();

        $binaryString = '';
        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); ++$columnIndex) {
                $binaryString .= $matrix->getBlockValue($rowIndex, $columnIndex);
            }
            $binaryString .= "\n";
        }

        return $binaryString;
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }
}
