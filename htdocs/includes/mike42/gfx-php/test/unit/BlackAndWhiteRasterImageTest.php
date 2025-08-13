<?php
use Mike42\GfxPhp\BlackAndWhiteRasterImage;

use PHPUnit\Framework\TestCase;

/*
 * Many of these tests use an example image like this-
 *╭────╮
 *│████│   ██ = 1
 *│░░██│   ░░ = 0
 *╰────╯
 */
class BlackAndWhiteRasterImageTest extends TestCase
{
    protected function createBlackAndWhiteTestImage() {
        $image = BlackAndWhiteRasterImage::create(2, 2);
        $image -> setPixel(0, 0, 1);
        $image -> setPixel(1, 0, 1);
        $image -> setPixel(0, 1, 0);
        $image -> setPixel(1, 1, 1);
        return $image;
    }

    public function testCreate()
    {
        $img = $this -> createBlackAndWhiteTestImage();
        $this -> assertEquals("▀█\n", $img -> toString());
    }

    public function testInvert() {
        $img = $this -> createBlackAndWhiteTestImage();
        $img -> invert();
        $this -> assertEquals("▄ \n", $img -> toString());
    }

    public function testToRgb()
    {
        $img = $this -> createBlackAndWhiteTestImage() -> toRgb() -> toBlackAndWhite();
        $this -> assertEquals("▀█\n", $img -> toString());
    }
    
    public function testToIndexed()
    {
        $img = $this -> createBlackAndWhiteTestImage() -> toIndexed() -> toBlackAndWhite();
        $this -> assertEquals("▀█\n", $img -> toString());
    }
    
    public function testToGrayscale()
    {
        $img = $this -> createBlackAndWhiteTestImage() -> toGrayscale() -> toBlackAndWhite();
        $this -> assertEquals("▀█\n", $img -> toString());
    }
    
    public function testClear()
    {
        $img = $this -> createBlackAndWhiteTestImage();
        $img -> clear();
        $this -> assertEquals("  \n", $img -> toString());
    }
    
    public function testScale()
    {
        $img = $this -> createBlackAndWhiteTestImage() -> scale(4, 2);
        $this -> assertEquals("▀▀██\n", $img -> toString());
    }
    
    public function testRectEmpty()
    {
        $img = BlackAndWhiteRasterImage::create(5, 5);
        $img -> rect(1, 1, 3, 3);
        $expected = " ▄▄▄ \n" .
                    " █▄█ \n" .
                    "     \n";
        $this -> assertEquals($expected, $img -> toString());
    }
    
    public function testSubImageEmpty()
    {
        $img = BlackAndWhiteRasterImage::create(5, 5);
        $img -> rect(1, 1, 3, 3);
        $img = $img -> subImage(1, 1, 3, 3);
        $expected = "█▀█\n" .
                    "▀▀▀\n";
        $this -> assertEquals($expected, $img -> toString());
    }
    
    public function testRectFilled()
    {
        $img = BlackAndWhiteRasterImage::create(5, 5);
        $img -> rect(1, 1, 3, 3, true);
        $expected = " ▄▄▄ \n" .
                    " ███ \n" .
                    "     \n";
        $this -> assertEquals($expected, $img -> toString());
    }
}