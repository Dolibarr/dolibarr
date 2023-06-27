<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Matrix\MatrixInterface;

final class EpsResult extends AbstractResult
{
    public function __construct(
        MatrixInterface $matrix,
        /** @var array<string> $lines */
        private array $lines
    ) {
        parent::__construct($matrix);
    }

    public function getString(): string
    {
        return implode("\n", $this->lines);
    }

    public function getMimeType(): string
    {
        return 'image/eps';
    }
}
