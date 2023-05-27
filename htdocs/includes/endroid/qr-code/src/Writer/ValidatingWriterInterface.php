<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Writer\Result\ResultInterface;

interface ValidatingWriterInterface
{
    public function validateResult(ResultInterface $result, string $expectedData): void;
}
