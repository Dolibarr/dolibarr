<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\ResultInterface;

interface WriterInterface
{
    /** @param array<string, mixed> $options */
    public function write(QrCodeInterface $qrCode, LogoInterface|null $logo = null, LabelInterface|null $label = null, array $options = []): ResultInterface;
}
