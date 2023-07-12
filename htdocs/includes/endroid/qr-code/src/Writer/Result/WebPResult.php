<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Matrix\MatrixInterface;

final class WebPResult extends GdResult
{
    private int $quality;

    public function __construct(MatrixInterface $matrix, \GdImage $image, int $quality = -1)
    {
        parent::__construct($matrix, $image);
        $this->quality = $quality;
    }

    public function getString(): string
    {
        if (!function_exists('imagewebp')) {
            throw new \Exception('WebP support is not available in your GD installation');
        }

        ob_start();
        imagewebp($this->image, quality: $this->quality);

        return strval(ob_get_clean());
    }

    public function getMimeType(): string
    {
        return 'image/webp';
    }
}
