<?php
use Mike42\Escpos\NativeEscposImage;
use Mike42\Escpos\EscposImage;

class NativeEscposImageTest extends PHPUnit\Framework\TestCase
{
    /**
     * Native tests - Load tiny images and check how they are printed
     * These are skipped if you don't have Native
     */
    public function testBadFilename()
    {
        $this -> expectException(Exception::class);
        $this -> loadAndCheckImg('not a real file.png', 1, 1, null, null);
    }

    /**
     * @medium
     */
    public function testBlack()
    {
        foreach (array('bmp', 'gif', 'png') as $format) {
            $this -> loadAndCheckImg('canvas_black.' . $format, 1, 1, "\x80", array("\x80"));
        }
    }

    /**
     * @medium
     */
    public function testBlackTransparent()
    {
        foreach (array('gif', 'png') as $format) {
            $this -> loadAndCheckImg('black_transparent.' . $format, 2, 2, "\xc0\x00", array("\x80\x80"));
        }
    }
    
    /**
     * @medium
     */
    public function testBlackWhite()
    {
        foreach (array('bmp', 'png', 'gif') as $format) {
            $this -> loadAndCheckImg('black_white.' . $format, 2, 2, "\xc0\x00", array("\x80\x80"));
        }
    }

    /**
     * @medium
     */
    public function testBlackWhiteTall()
    {
        // We're very interested in correct column format chopping here at 8 pixels
        $this -> loadAndCheckImg('black_white_tall.png', 2, 16,
            "\xc0\xc0\xc0\xc0\xc0\xc0\xc0\xc0\x00\x00\x00\x00\x00\x00\x00\x00", array("\xff\xff", "\x00\x00"));
    }

    /**
     * @medium
     */
    public function testWhite()
    {
        foreach (array('bmp', 'png', 'gif') as $format) {
            $this -> loadAndCheckImg('canvas_white.' . $format, 1, 1, "\x00", array("\x00"));
        }
    }

    /**
     * Load an EscposImage and run a check.
     */
    private function loadAndCheckImg($fn, $width, $height, $rasterFormat = null, $columnFormat = null)
    {
        $onDisk = ($fn === null ? null : (dirname(__FILE__) . "/resources/$fn"));
        // With optimisations
        $imgOptimised = new NativeEscposImage($onDisk, true);
        $this -> checkImg($imgOptimised, $width, $height, $rasterFormat, $columnFormat);
        // ... and without
        $imgUnoptimised = new NativeEscposImage($onDisk, false);
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
        $this -> assertEquals($height , $img -> getHeight());
        $this -> assertEquals($width, $img -> getWidth());
        $this -> assertEquals($rasterFormatExpected, $rasterFormatActual, "Raster format did not match expected");
        $this -> assertEquals($columnFormatExpected, $columnFormatActual, "Column format did not match expected");
    }
}
