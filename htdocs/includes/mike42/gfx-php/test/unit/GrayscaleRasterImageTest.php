<?php

/*
 * Many of these tests use an example image like this-
 *╭────╮
 *│░░▒▒│   ░░ = 255   ▓▓ = 80
 *│▓▓██│   ▒▒ = 160   ██ = 0
 *╰────╯
 */
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\RgbRasterImage;

class GrayscaleRasterImageTest extends TestCase {
    protected function createGrayscaleTestImage() {
        $image = GrayscaleRasterImage::create(2, 2);
        $image -> setPixel(0, 0, 255);
        $image -> setPixel(1, 0, 160);
        $image -> setPixel(0, 1, 80);
        $image -> setPixel(1, 1, 0);
        return $image;
    }

    public function testCreate()
    {
        $img = $this -> createGrayscaleTestImage();
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
        $this -> assertEquals(255, $img -> getMaxVal());
        $this -> assertEquals("▄▄\n", $img -> toBlackAndWhite() -> toString());
    }
    
    
    public function testToRgb()
    {
        $white = RgbRasterImage::rgbToInt(255, 255, 255);
        $lightGray = RgbRasterImage::rgbToInt(160, 160, 160);
        $darkGray = RgbRasterImage::rgbToInt(80, 80, 80);
        $black = RgbRasterImage::rgbToInt(0, 0, 0);
        $img = $this -> createGrayscaleTestImage() -> toRgb();
        $this -> assertEquals($white, $img -> getPixel(0, 0));
        $this -> assertEquals($lightGray, $img -> getPixel(1, 0));
        $this -> assertEquals($darkGray, $img -> getPixel(0, 1));
        $this -> assertEquals($black, $img -> getPixel(1, 1));
    }
    
    public function testToIndexed()
    {
        // Same raster data is used, with grayscale 'palette'
        $img = $this -> createGrayscaleTestImage() -> toIndexed();
        $this -> assertEquals(255, $img -> getPixel(0, 0));
        $this -> assertEquals(160, $img -> getPixel(1, 0));
        $this -> assertEquals(80, $img -> getPixel(0, 1));
        $this -> assertEquals(0, $img -> getPixel(1, 1));
    }
}