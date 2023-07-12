<?php

declare(strict_types=1);

namespace Endroid\QrCode\Matrix;

use Endroid\QrCode\QrCodeInterface;

interface MatrixFactoryInterface
{
    public function create(QrCodeInterface $qrCode): MatrixInterface;
}
