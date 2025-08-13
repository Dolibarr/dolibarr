<?php
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\EscposImage;

class GdEscposImageTest extends PHPUnit\Framework\TestCase
{

    /**
     * Gd tests - Load tiny images and check how they are printed
     * These are skipped if you don't have imagick
     */
    public function testGdBadFilename()
    {
        $this -> expectException(Exception::class);
        $this -> loadAndCheckImg('not a real file.png', 1, 1, null, null);
    }
    
    /**
     * @medium
     */
    public function testGdEmpty()
    {
        $this -> loadAndCheckImg(null, 0, 0, "", array());
    }
    
    /**
     * @medium
     */
    public function testGdBlack()
    {
        foreach (array('png', 'jpg', 'gif') as $format) {
            $this -> loadAndCheckImg('canvas_black.' . $format, 1, 1, "\x80", array("\x80"));
        }
    }
    
    /**
     * @medium
     */
    public function testGdBlackTransparent()
    {
        foreach (array('png', 'gif') as $format) {
            $this -> loadAndCheckImg('black_transparent.' . $format, 2, 2, "\xc0\x00", array("\x80\x80"));
        }
    }
    
    /**
     * @medium
     */
    public function testGdBlackWhite()
    {
        foreach (array('png', 'jpg', 'gif') as $format) {
            $this -> loadAndCheckImg('black_white.' . $format, 2, 2, "\xc0\x00", array("\x80\x80"));
        }
    }
    
    /**
     * @medium
     */
    public function testGdWhite()
    {
        foreach (array('png', 'jpg', 'gif') as $format) {
            $this -> loadAndCheckImg('canvas_white.' . $format, 1, 1, "\x00", array("\x00"));
        }
    }

    /**
     * Load an EscposImage with (optionally) certain libraries disabled and run a check.
     */
    private function loadAndCheckImg($fn, $width, $height, $rasterFormat = null, $columnFormat = null)
    {
        if (!EscposImage::isGdLoaded()) {
            $this -> markTestSkipped("imagick plugin is required for this test");
        }
        $onDisk = ($fn === null ? null : (dirname(__FILE__) . "/resources/$fn"));
        // With optimisations
        $imgOptimised = new GdEscposImage($onDisk, true);
        $this -> checkImg($imgOptimised, $width, $height, $rasterFormat, $columnFormat);
        // ... and without
        $imgUnoptimised = new GdEscposImage($onDisk, false);
        $this -> checkImg($imgUnoptimised, $width, $height, $rasterFormat, $columnFormat);
    }
    
    /**
     * Check image against known width, height, output.
     */
    private function checkImg(EscposImage $img, $width, $height, $rasterFormatExpected = null, $columnFormatExpected = null)
    {
        $rasterFormatActual = $img -> toRasterFormat();
        $columnFormatActual = $img -> toColumnFormat();
        if ($rasterFormatExpected === null) {
            echo "\nImage was: " . $img -> getWidth() . "x" . $img -> getHeight() . ", raster data \"" . friendlyBinary($rasterFormatActual) . "\"";
        }
        if ($columnFormatExpected === null) {
            echo "\nImage was: " . $img -> getWidth() . "x" . $img -> getHeight() . ", column data \"" . friendlyBinary($columnFormatActual) . "\"";
        }
        $this -> assertTrue($img -> getHeight() == $height);
        $this -> assertTrue($img -> getWidth() == $width);
        $this -> assertTrue($rasterFormatExpected === $rasterFormatActual);
        $this -> assertTrue($columnFormatExpected === $columnFormatActual);
    }
}
