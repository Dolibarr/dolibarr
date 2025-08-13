<?php

use Mike42\GfxPhp\Codec\BmpCodec;
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\RgbRasterImage;

class BmpCodecTest extends TestCase
{

    const BMP_IMAGE = "BM:\x00\x00\x00\x00\x00\x00\x006\x00\x00\x00(\x00\x00" .
            "\x00\x01\x00\x00\x00\x01\x00\x00\x00\x01\x00\x18\x00" .
            "\x00\x00\x00\x00\x00\x00\x00\x00\x01\x00\x00\x00\x01".
            "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" .
            "\xff\x00";

    public function testBmpEncode()
    {
        $encoder = new BmpCodec();
        $image = RgbRasterImage::create(1, 1);
        $imageStr = $encoder -> encode($image, 'bmp');
        $this -> assertEquals(self::BMP_IMAGE, $imageStr);
    }

    public function testBmpDecode()
    {
        $decoder = new BmpCodec();
        $image = $decoder -> decode(self::BMP_IMAGE);
        $this -> assertEquals(1, $image -> getWidth());
        $this -> assertEquals(1, $image -> getHeight());
    }
}
