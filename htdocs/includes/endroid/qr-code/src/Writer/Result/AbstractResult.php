<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Matrix\MatrixInterface;

abstract class AbstractResult implements ResultInterface
{
    public function __construct(
        private MatrixInterface $matrix
    ) {
    }

    public function getMatrix(): MatrixInterface
    {
        return $this->matrix;
    }

    public function getDataUri(): string
    {
        return 'data:'.$this->getMimeType().';base64,'.base64_encode($this->getString());
    }

    public function saveToFile(string $path): void
    {
        $string = $this->getString();
        file_put_contents($path, $string);
    }
}
