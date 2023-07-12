<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Matrix\MatrixInterface;

class GdResult extends AbstractResult
{
    public function __construct(
        MatrixInterface $matrix,
        protected \GdImage $image
    ) {
        parent::__construct($matrix);
    }

    public function getImage(): \GdImage
    {
        return $this->image;
    }

    public function getString(): string
    {
        throw new \Exception('You can only use this method in a concrete implementation');
    }

    public function getMimeType(): string
    {
        throw new \Exception('You can only use this method in a concrete implementation');
    }
}
