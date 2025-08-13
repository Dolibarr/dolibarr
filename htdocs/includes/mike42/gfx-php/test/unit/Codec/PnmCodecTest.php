<?php
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\RgbRasterImage;
use Mike42\GfxPhp\Codec\PngCodec;
use Mike42\GfxPhp\Codec\PnmCodec;
use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;

/*
 * Tiny example image used throughout this test looks like this:
 *╭────╮
 *│██░░│   ██ = 0x00
 *│░░██│   ░░ = 0xFF
 *╰────╯
 */
class PnmCodecTest extends TestCase
{
    const IMAGE_TEXT = "▀▄\n";

    const PBM_EXAMPLE = "P4\x0a2 2\x0a\x80@";
    const PGM_EXAMPLE = "P5\x0a2 2\x0a255\x0a\x00\xff\xff\x00";
    const PPM_EXAMPLE = "P6\x0a2 2\x0a255\x0a\x00\x00\x00\xff\xff\xff\xff\xff\xff\x00\x00\x00";

    protected function createRgbTestImage() {
        $image = RgbRasterImage::create(2, 2);
        $black = $image -> rgbToInt(0, 0, 0);
        $white = $image -> rgbToInt(255, 255, 255);
        $image -> setPixel(0, 0, $black);
        $image -> setPixel(1, 0, $white);
        $image -> setPixel(0, 1, $white);
        $image -> setPixel(1, 1, $black);
        return $image;
    }
    
    public function testPnmEncodeRgb() {
        // Test image as RGB
        $encoder = new PnmCodec();
        $image = $this -> createRgbTestImage();
        // Encode
        $blob = $encoder -> encode($image, 'pnm');
        // Should have auto-selected PPM based on RgbTestImage input
        $this -> assertEquals(self::PPM_EXAMPLE, $blob);
    }
    
    public function testPbmEncodeRgb() {
        // Test image as RGB
        $encoder = new PnmCodec();
        $image = $this -> createRgbTestImage();
        // Encode
        $blob = $encoder -> encode($image, 'pbm');
        // Check against known-good encoding of PBM
        $this -> assertEquals(self::PBM_EXAMPLE, $blob);
    }
    
    public function testPbmDecode() {
        $decoder = new PnmCodec();
        $image = $decoder -> decode(self::PBM_EXAMPLE);
        $this -> assertTrue($image instanceof BlackAndWhiteRasterImage);
        $this -> assertEquals(self::IMAGE_TEXT, $image -> toString());
        
    }
    
    public function testPgmEncodeRgb() {
        // Test image as RGB
        $encoder = new PnmCodec();
        $image = $this -> createRgbTestImage();
        // Encode
        $blob = $encoder -> encode($image, 'pgm');
        // Check against known-good encoding of PGM
        $this -> assertEquals(self::PGM_EXAMPLE, $blob);
    }
    
    public function testPgmDecode() {
        $decoder = new PnmCodec();
        $image = $decoder -> decode(self::PGM_EXAMPLE);
        $this -> assertTrue($image instanceof GrayscaleRasterImage);
        $this -> assertEquals(self::IMAGE_TEXT, $image -> toBlackAndWhite() -> toString());
    }
    
    public function testPpmEncodeRgb() {
        // Test image as RGB
        $encoder = new PnmCodec();
        $image = $this -> createRgbTestImage();
        // Encode
        $blob = $encoder -> encode($image, 'ppm');
        // Check against known-good encoding of PPM
        $this -> assertEquals(self::PPM_EXAMPLE, $blob);
    }
    
    public function testPpmDecode() {
        $decoder = new PnmCodec();
        $image = $decoder -> decode(self::PPM_EXAMPLE);
        $this -> assertTrue($image instanceof RgbRasterImage);
        $this -> assertEquals(self::IMAGE_TEXT, $image -> toBlackAndWhite() -> toString());
    }
}
