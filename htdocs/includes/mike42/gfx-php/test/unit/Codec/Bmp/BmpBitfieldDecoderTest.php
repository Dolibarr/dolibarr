<?php

namespace Mike42\GfxPhp\Codec\Bmp;

use PHPUnit\Framework\TestCase;

class BmpBitfieldDecoderTest extends TestCase
{
    /**
     * Decode various 16-bit  pixels with the default 5-5-5 bit depths
     */
    public function testReadDefaults()
    {
        // 5-5-5, little-endian encoded as 2 bytes.
        $decoder = new BmpBitfieldDecoder(BmpColorBitfield::from16bitDefaults());
        $this -> assertEquals([0, 0, 0], $decoder -> read16bit([0x00, 0x00])); // Black
        $this -> assertEquals([0, 0, 0], $decoder -> read16bit([0x00, 0x80])); // Also black (MSB ignored)
        $this -> assertEquals([255, 255, 255], $decoder -> read16bit([0xff, 0xff])); // White
        $this -> assertEquals([0, 0, 255], $decoder -> read16bit([0x1f, 0])); // Blue
        $this -> assertEquals([0, 255, 0], $decoder -> read16bit([0xe0, 0x03])); // Green
        $this -> assertEquals([255, 0, 0], $decoder -> read16bit([0x00, 0x7c])); // Red
    }


}
