<?php

declare(strict_types=1);

namespace Endroid\QrCode\Encoding;

interface EncodingInterface
{
    public function __toString(): string;
}
