<?php

declare(strict_types=1);

namespace Endroid\QrCode\Color;

final class Color implements ColorInterface
{
    public function __construct(
        private int $red,
        private int $green,
        private int $blue,
        private int $alpha = 0
    ) {
    }

    public function getRed(): int
    {
        return $this->red;
    }

    public function getGreen(): int
    {
        return $this->green;
    }

    public function getBlue(): int
    {
        return $this->blue;
    }

    public function getAlpha(): int
    {
        return $this->alpha;
    }

    public function getOpacity(): float
    {
        return 1 - $this->alpha / 127;
    }

    public function getHex(): string
    {
        return sprintf('#%02x%02x%02x', $this->red, $this->green, $this->blue);
    }

    public function toArray(): array
    {
        return [
            'red' => $this->red,
            'green' => $this->green,
            'blue' => $this->blue,
            'alpha' => $this->alpha,
        ];
    }
}
