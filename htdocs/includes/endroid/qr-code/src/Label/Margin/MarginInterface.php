<?php

declare(strict_types=1);

namespace Endroid\QrCode\Label\Margin;

interface MarginInterface
{
    public function getTop(): int;

    public function getRight(): int;

    public function getBottom(): int;

    public function getLeft(): int;

    /** @return array<string, int> */
    public function toArray(): array;
}
