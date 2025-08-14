<?php
namespace Mike42\GfxPhp\Codec;

use Mike42\GfxPhp\RasterImage;

interface ImageDecoder
{
    public function getDecodeFormats() : array;

    public function identify(string $blob) : string;

    public function decode(string $blob) : RasterImage;
}
