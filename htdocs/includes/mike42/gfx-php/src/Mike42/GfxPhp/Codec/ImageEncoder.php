<?php
namespace Mike42\GfxPhp\Codec;

use Mike42\GfxPhp\RasterImage;

interface ImageEncoder
{
    public function getEncodeFormats() : array;

    public function encode(RasterImage $image, string $format) : string;
}
