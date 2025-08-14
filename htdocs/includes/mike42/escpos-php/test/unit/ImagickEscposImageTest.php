<?php
use Mike42\Escpos\ImagickEscposImage;
use Mike42\Escpos\EscposImage;

class ImagickEscposImageTest extends PHPUnit\Framework\TestCase
{

    /**
     * Imagick tests - Load tiny images and check how they are printed
     * These are skipped if you don't have imagick
     */
    public function testImagickBadFilename()
    {
        $this -> expectException(Exception::class);
        $this -> loadAndCheckImg('not a real file.png', 1, 1, null, null);
    }
    
    /**
     * @medium
     */
    public function testImagickEmpty()
    {
        $this -> loadAndCheckImg(null, 0, 0, "", array());
    }
    
    /**
     * @medium
     */
    public function testImagickBlack()
    {
        foreach (array('png', 'jpg', 'gif') as $format) {
            $this -> loadAndCheckImg('canvas_black.' . $format, 1, 1, "\x80", array("\x80"));
        }
    }
    
    /**
     * @medium
     */
    public function testImagickBlackTransparent()
    {
        foreach (array('png', 'gif') as $format) {
            $this -> loadAndCheckImg('black_transparent.' . $format, 2, 2, "\xc0\x00", array("\x80\x80"));
        }
    }
    
    /**
     * @medium
     */
    public function testImagickBlackWhite()
    {
        foreach (array('png', 'jpg', 'gif') as $format) {
            $this -> loadAndCheckImg('black_white.' . $format, 2, 2, "\xc0\x00", array("\x80\x80"));
        }
    }

    /**
     * @medium
     */
    public function testImagickBlackWhiteTall()
    {
        // We're very interested in correct column format chopping here at 8 pixels
        $this -> loadAndCheckImg('black_white_tall.png', 2, 16,
            "\xc0\xc0\xc0\xc0\xc0\xc0\xc0\xc0\x00\x00\x00\x00\x00\x00\x00\x00", array("\xff\xff", "\x00\x00"));
    }

    /**
     * @medium
     */
    public function testImagickWhite()
    {
        foreach (array('png', 'jpg', 'gif') as $format) {
            $this -> loadAndCheckImg('canvas_white.' . $format, 1, 1, "\x00", array("\x00"));
        }
    }
    
    /**
     * PDF test - load tiny PDF and check for well-formedness
     * These are also skipped if you don't have imagick
     * @medium
     */
    public function testPdfAllPages()
    {
        $this -> loadAndCheckPdf('doc.pdf', 1, 1, array("\x00", "\x80"), array(array("\x00"), array("\x80")));
    }
    
    public function testPdfBadFilename()
    {
        $this -> expectException(Exception::class);
        $this -> loadAndCheckPdf('not a real file', 1, 1, array(), array());
    }
    
    /**
     * Load an EscposImage and run a check.
     */
    private function loadAndCheckImg($fn, $width, $height, $rasterFormat = null, $columnFormat = null)
    {
        if (!EscposImage::isImagickLoaded()) {
            $this -> markTestSkipped("imagick plugin is required for this test");
        }
        $onDisk = ($fn === null ? null : (dirname(__FILE__) . "/resources/$fn"));
        // With optimisations
        $imgOptimised = new ImagickEscposImage($onDisk, true);
        $this -> checkImg($imgOptimised, $width, $height, $rasterFormat, $columnFormat);
        // ... and without
        $imgUnoptimised = new ImagickEscposImage($onDisk, false);
        $this -> checkImg($imgUnoptimised, $width, $height, $rasterFormat, $columnFormat);
    }
    
    /**
     * Same as above, loading document and checking pages against some expected values.
     */
    private function loadAndCheckPdf($fn, $width, $height, array $rasterFormat = null, array $columnFormat = null)
    {
        if (!EscposImage::isImagickLoaded()) {
            $this -> markTestSkipped("imagick plugin required for this test");
        }
        $pdfPages = ImagickEscposImage::loadPdf(dirname(__FILE__) . "/resources/$fn", $width);
        $this -> assertTrue(count($pdfPages) == count($rasterFormat), "Got back wrong number of pages");
        foreach ($pdfPages as $id => $img) {
            $this -> checkImg($img, $width, $height, $rasterFormat[$id], $columnFormat[$id]);
        }
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
