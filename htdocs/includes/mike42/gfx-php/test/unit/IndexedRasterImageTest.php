<?php

/*
 * Many of these tests use an example image like this-
 *╭────╮
 *│░░▒▒│   ░░ = 0   ▓▓ = 2
 *│▓▓██│   ▒▒ = 1   ██ = 3
 *╰────╯   
 */
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\Image;
use Mike42\GfxPhp\IndexedRasterImage;
use Mike42\GfxPhp\RgbRasterImage;

class IndexedRasterImageTest extends TestCase
{
    
    protected function createIndexedTestImage() {
        $image = IndexedRasterImage::create(2, 2, null, [
            [255, 255, 255],
            [160, 160, 160],
            [80, 80, 80],
            [0, 0, 0]]);
        $image -> setPixel(0, 0, 0);
        $image -> setPixel(1, 0, 1);
        $image -> setPixel(0, 1, 2);
        $image -> setPixel(1, 1, 3);
        return $image;
    }

    public function testCreate()
    {
        $img = $this -> createIndexedTestImage();
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
        $this -> assertEquals(255, $img -> getMaxVal());
        $this -> assertEquals("▄▄\n", $img -> toBlackAndWhite() -> toString());
    }

    public function testReduceDepthTo1Bit()
    {
        $img = $this -> createIndexedTestImage();
        $img -> setMaxVal(1);
        $this -> assertEquals(0, $img -> getPixel(0, 0));
        $this -> assertEquals(0, $img -> getPixel(1, 0));
        $this -> assertEquals(1, $img -> getPixel(0, 1));
        $this -> assertEquals(1, $img -> getPixel(1, 1));
    }

    public function testToGrayscale() {
        $img = $this -> createIndexedTestImage() -> toGrayscale();
        $this -> assertEquals(255, $img -> getPixel(0, 0));
        $this -> assertEquals(160, $img -> getPixel(1, 0));
        $this -> assertEquals(80, $img -> getPixel(0, 1));
        $this -> assertEquals(0, $img -> getPixel(1, 1));
    }

    public function testToRgb() {
        $white = RgbRasterImage::rgbToInt(255, 255, 255);
        $lightGray = RgbRasterImage::rgbToInt(160, 160, 160);
        $darkGray = RgbRasterImage::rgbToInt(80, 80, 80);
        $black = RgbRasterImage::rgbToInt(0, 0, 0);
        $img = $this -> createIndexedTestImage() -> toRgb();
        $this -> assertEquals($white, $img -> getPixel(0, 0));
        $this -> assertEquals($lightGray, $img -> getPixel(1, 0));
        $this -> assertEquals($darkGray, $img -> getPixel(0, 1));
        $this -> assertEquals($black, $img -> getPixel(1, 1));
    }
    
    public function testRgbToIndexExists() {
        $img = $this -> createIndexedTestImage();
        $idx = $img -> rgbToIndex([80, 80, 80]);
        $this -> assertEquals(2, $idx);
    }
    
    public function testQuantizeGrayscale() {
        // Produce 8-bit grayscale image via high color-count RGBA
        $img = Image::fromFile(__DIR__ . "/../resources/pngsuite/basn2c08.png") -> toIndexed() -> toGrayscale();
        $this -> assertEquals(255, $img -> getMaxVal());
        $this -> assertEquals(255, $img -> getPixel(0, 0)); // White
        $this -> assertEquals(0, $img -> getPixel(31, 31)); // Black
    }
    
    public function testQuantizeRgb() {
        // Reduce depth of image with more than 16 colors.
        $img = Image::fromFile(__DIR__ . "/../resources/pngsuite/basn2c08.png") -> toIndexed();
        $this -> assertEquals(16777215, $img -> getMaxVal());
        $img -> setMaxVal(255);
        // Check new range.
        $this -> assertEquals(255, $img -> getMaxVal());
        $this -> assertEquals(255, $img -> getPixel(0, 0)); // White
        $this -> assertEquals(0, $img -> getPixel(31, 31)); // Black
    }
}

