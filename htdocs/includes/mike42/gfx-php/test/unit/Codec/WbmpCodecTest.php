<?php

use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\Codec\Common\DataBlobInputStream;
use Mike42\GfxPhp\Codec\WbmpCodec;
use PHPUnit\Framework\TestCase;

class WbmpCodecTest extends TestCase {
    const WBMP_IMAGE = "\x00\x00\x0c\x06\x24\x90\xff\xf0\x49\x20\xff\xf0\x92\x40\xff\xf0";

    public function testIdentify() {
        $codec = new WbmpCodec();
        $this -> assertEquals("wbmp", $codec -> identify(self::WBMP_IMAGE));
        $this -> assertEquals("", $codec -> identify("HELLO"));
    }

    public function testDecode() {
        $codec = new WbmpCodec();
        $image = $codec -> decode(self::WBMP_IMAGE) -> toBlackAndWhite();
        $this -> assertEquals(12, $image -> getWidth());
        $this -> assertEquals(6, $image -> getHeight());
        $content =  "▀▀ ▀▀ ▀▀ ▀▀ \n" .
                    "▀ ▀▀ ▀▀ ▀▀ ▀\n" .
                    " ▀▀ ▀▀ ▀▀ ▀▀\n";
        $this -> assertEquals($content, $image -> toString());
    }

    public function testEncode() {
        // Raster representation is inverse to WBMP format.
        $image = BlackAndWhiteRasterImage::create(12, 6, [0xdb, 0x6f, 0x00, 0x0f, 0xb6, 0xdf, 0x00, 0x0f, 0x6d, 0xbf, 0x00, 0x0f]);
        $codec = new WbmpCodec();
        $data = $codec -> encode($image, "wbmp");
        $this -> assertEquals(self::WBMP_IMAGE, $data);
    }

    public function testReadOneByte() {
        $data = DataBlobInputStream::fromBlob("\x60");
        $codec = new WbmpCodec();
        $val = $codec -> readInt($data);
        $this -> assertEquals(0x60, $val);
    }

    public function testReadMultibyte() {
        $data = DataBlobInputStream::fromBlob("\x81\x20");
        $codec = new WbmpCodec();
        $val = $codec -> readInt($data);
        $this -> assertEquals(0xA0, $val);
    }

    public function testReadMax() {
        $data = DataBlobInputStream::fromBlob("\xFF\xFF\xFF\x7F\x00"); // Final byte not used
        $codec = new WbmpCodec();
        $val = $codec -> readInt($data);
        $this -> assertEquals(268435455, $val);
    }

    public function testReadMultibyteOverflow() {
        // Appears to be no limit in WBMP to image dimensions, but we stop reading the multibyte-ints after 28 bits.
        $this -> expectException(Exception::class);
        $data = DataBlobInputStream::fromBlob("\xFF\xFF\xFF\x80\x00"); // (value in testMax()) + 1
        $codec = new WbmpCodec();
        $codec -> readInt($data);
    }

    public function testWriteOneByte() {
        $codec = new WbmpCodec();
        $val = $codec -> writeInt(0x60);
        $this -> assertEquals("\x60", $val);
    }

    public function testWriteMultibyte() {
        $codec = new WbmpCodec();
        $val = $codec -> writeInt(0xA0);
        $this -> assertEquals("\x81\x20", $val);
    }

    public function testWriteMax() {
        $codec = new WbmpCodec();
        $val = $codec -> writeInt(268435455);
        $this -> assertEquals("\xFF\xFF\xFF\x7F", $val);

    }

    public function testWriteMultibyteOverflow() {
        $this -> expectException(Exception::class);
        $codec = new WbmpCodec();
        $val = $codec -> writeInt(268435456); // As testWriteMax(), +1
    }
}