<?php
use Mike42\GfxPhp\Image;
use Mike42\GfxPhp\RgbRasterImage;
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\IndexedRasterImage;

/**
 * Simple check that all files in pngsuite are correctly accepted or rejected, and contain
 * correct width.
 */
class PngsuiteTest extends TestCase
{
    function loadImage(string $filename) {
        return Image::fromFile(__DIR__ . "/../resources/pngsuite/$filename");
    }
    
    function test_basi0g01() {
        $img = $this -> loadImage("basi0g01.png");
        $this -> assertTrue($img instanceOf BlackAndWhiteRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi0g02() {
        $img = $this -> loadImage("basi0g02.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi0g04() {
        $img = $this -> loadImage("basi0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi0g08() {
        $img = $this -> loadImage("basi0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi0g16() {
        $img = $this -> loadImage("basi0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi2c08() {
        $img = $this -> loadImage("basi2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi2c16() {
        $img = $this -> loadImage("basi2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi3p01() {
        $img = $this -> loadImage("basi3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi3p02() {
        $img = $this -> loadImage("basi3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi3p04() {
        $img = $this -> loadImage("basi3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi3p08() {
        $img = $this -> loadImage("basi3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi4a08() {
        $img = $this -> loadImage("basi4a08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi4a16() {
        $img = $this -> loadImage("basi4a16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi6a08() {
        $img = $this -> loadImage("basi6a08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basi6a16() {
        $img = $this -> loadImage("basi6a16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn0g01() {
        $img = $this -> loadImage("basn0g01.png");
        $this -> assertTrue($img instanceOf BlackAndWhiteRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn0g02() {
        $img = $this -> loadImage("basn0g02.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn0g04() {
        $img = $this -> loadImage("basn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn0g08() {
        $img = $this -> loadImage("basn0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn0g16() {
        $img = $this -> loadImage("basn0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn2c08() {
        $img = $this -> loadImage("basn2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn2c16() {
        $img = $this -> loadImage("basn2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn3p01() {
        $img = $this -> loadImage("basn3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn3p02() {
        $img = $this -> loadImage("basn3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn3p04() {
        $img = $this -> loadImage("basn3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn3p08() {
        $img = $this -> loadImage("basn3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn4a08() {
        $img = $this -> loadImage("basn4a08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn4a16() {
        $img = $this -> loadImage("basn4a16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn6a08() {
        $img = $this -> loadImage("basn6a08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_basn6a16() {
        $img = $this -> loadImage("basn6a16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgai4a08() {
        $img = $this -> loadImage("bgai4a08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgai4a16() {
        $img = $this -> loadImage("bgai4a16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgan6a08() {
        $img = $this -> loadImage("bgan6a08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgan6a16() {
        $img = $this -> loadImage("bgan6a16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgbn4a08() {
        $img = $this -> loadImage("bgbn4a08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bggn4a16() {
        $img = $this -> loadImage("bggn4a16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgwn6a08() {
        $img = $this -> loadImage("bgwn6a08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_bgyn6a16() {
        $img = $this -> loadImage("bgyn6a16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ccwn2c08() {
        $img = $this -> loadImage("ccwn2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ccwn3p08() {
        $img = $this -> loadImage("ccwn3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cdfn2c08() {
        $img = $this -> loadImage("cdfn2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(8, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cdhn2c08() {
        $img = $this -> loadImage("cdhn2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }
    
    function test_cdsn2c08() {
        $img = $this -> loadImage("cdsn2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(8, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }
    
    function test_cdun2c08() {
        $img = $this -> loadImage("cdun2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ch1n3p04() {
        $img = $this -> loadImage("ch1n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ch2n3p08() {
        $img = $this -> loadImage("ch2n3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cm0n0g04() {
        $img = $this -> loadImage("cm0n0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cm7n0g04() {
        $img = $this -> loadImage("cm7n0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cm9n0g04() {
        $img = $this -> loadImage("cm9n0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cs3n2c16() {
        $img = $this -> loadImage("cs3n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cs3n3p08() {
        $img = $this -> loadImage("cs3n3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cs5n2c08() {
        $img = $this -> loadImage("cs5n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cs5n3p08() {
        $img = $this -> loadImage("cs5n3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cs8n2c08() {
        $img = $this -> loadImage("cs8n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cs8n3p08() {
        $img = $this -> loadImage("cs8n3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ct0n0g04() {
        $img = $this -> loadImage("ct0n0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ct1n0g04() {
        $img = $this -> loadImage("ct1n0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cten0g04() {
        $img = $this -> loadImage("cten0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ctfn0g04() {
        $img = $this -> loadImage("ctfn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ctgn0g04() {
        $img = $this -> loadImage("ctgn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_cthn0g04() {
        $img = $this -> loadImage("cthn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ctjn0g04() {
        $img = $this -> loadImage("ctjn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ctzn0g04() {
        $img = $this -> loadImage("ctzn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_exif2c08() {
        $img = $this -> loadImage("exif2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f00n0g08() {
        $img = $this -> loadImage("f00n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f00n2c08() {
        $img = $this -> loadImage("f00n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f01n0g08() {
        $img = $this -> loadImage("f01n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f01n2c08() {
        $img = $this -> loadImage("f01n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f02n0g08() {
        $img = $this -> loadImage("f02n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f02n2c08() {
        $img = $this -> loadImage("f02n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f03n0g08() {
        $img = $this -> loadImage("f03n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f03n2c08() {
        $img = $this -> loadImage("f03n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f04n0g08() {
        $img = $this -> loadImage("f04n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f04n2c08() {
        $img = $this -> loadImage("f04n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_f99n0g04() {
        $img = $this -> loadImage("f99n0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g03n0g16() {
        $img = $this -> loadImage("g03n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g03n2c08() {
        $img = $this -> loadImage("g03n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g03n3p04() {
        $img = $this -> loadImage("g03n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g04n0g16() {
        $img = $this -> loadImage("g04n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g04n2c08() {
        $img = $this -> loadImage("g04n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g04n3p04() {
        $img = $this -> loadImage("g04n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g05n0g16() {
        $img = $this -> loadImage("g05n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g05n2c08() {
        $img = $this -> loadImage("g05n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g05n3p04() {
        $img = $this -> loadImage("g05n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g07n0g16() {
        $img = $this -> loadImage("g07n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g07n2c08() {
        $img = $this -> loadImage("g07n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g07n3p04() {
        $img = $this -> loadImage("g07n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g10n0g16() {
        $img = $this -> loadImage("g10n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g10n2c08() {
        $img = $this -> loadImage("g10n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g10n3p04() {
        $img = $this -> loadImage("g10n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g25n0g16() {
        $img = $this -> loadImage("g25n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g25n2c08() {
        $img = $this -> loadImage("g25n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_g25n3p04() {
        $img = $this -> loadImage("g25n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi1n0g16() {
        $img = $this -> loadImage("oi1n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi1n2c16() {
        $img = $this -> loadImage("oi1n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi2n0g16() {
        $img = $this -> loadImage("oi2n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi2n2c16() {
        $img = $this -> loadImage("oi2n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi4n0g16() {
        $img = $this -> loadImage("oi4n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi4n2c16() {
        $img = $this -> loadImage("oi4n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi9n0g16() {
        $img = $this -> loadImage("oi9n0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_oi9n2c16() {
        $img = $this -> loadImage("oi9n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_pp0n2c16() {
        $img = $this -> loadImage("pp0n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_pp0n6a08() {
        $img = $this -> loadImage("pp0n6a08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ps1n0g08() {
        $img = $this -> loadImage("ps1n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ps1n2c16() {
        $img = $this -> loadImage("ps1n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ps2n0g08() {
        $img = $this -> loadImage("ps2n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_ps2n2c16() {
        $img = $this -> loadImage("ps2n2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_s01i3p01() {
        $img = $this -> loadImage("s01i3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }
    
    function test_s01n3p01() {
        $img = $this -> loadImage("s01n3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }
    
    function test_s02i3p01() {
        $img = $this -> loadImage("s02i3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }
    
    function test_s02n3p01() {
        $img = $this -> loadImage("s02n3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }
    
    function test_s03i3p01() {
        $img = $this -> loadImage("s03i3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(3, $img -> getWidth());
        $this -> assertEquals(3, $img -> getHeight());
    }
    
    function test_s03n3p01() {
        $img = $this -> loadImage("s03n3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(3, $img -> getWidth());
        $this -> assertEquals(3, $img -> getHeight());
    }
    
    function test_s04i3p01() {
        $img = $this -> loadImage("s04i3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(4, $img -> getWidth());
        $this -> assertEquals(4, $img -> getHeight());
    }
    
    function test_s04n3p01() {
        $img = $this -> loadImage("s04n3p01.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(4, $img -> getWidth());
        $this -> assertEquals(4, $img -> getHeight());
    }
    
    function test_s05i3p02() {
        $img = $this -> loadImage("s05i3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(5, $img -> getWidth());
        $this -> assertEquals(5, $img -> getHeight());
    }
    
    function test_s05n3p02() {
        $img = $this -> loadImage("s05n3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(5, $img -> getWidth());
        $this -> assertEquals(5, $img -> getHeight());
    }
    
    function test_s06i3p02() {
        $img = $this -> loadImage("s06i3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(6, $img -> getWidth());
        $this -> assertEquals(6, $img -> getHeight());
    }
    
    function test_s06n3p02() {
        $img = $this -> loadImage("s06n3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(6, $img -> getWidth());
        $this -> assertEquals(6, $img -> getHeight());
    }
    
    function test_s07i3p02() {
        $img = $this -> loadImage("s07i3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(7, $img -> getWidth());
        $this -> assertEquals(7, $img -> getHeight());
    }
    
    function test_s07n3p02() {
        $img = $this -> loadImage("s07n3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(7, $img -> getWidth());
        $this -> assertEquals(7, $img -> getHeight());
    }
    
    function test_s08i3p02() {
        $img = $this -> loadImage("s08i3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(8, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }
    
    function test_s08n3p02() {
        $img = $this -> loadImage("s08n3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(8, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }
    
    function test_s09i3p02() {
        $img = $this -> loadImage("s09i3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(9, $img -> getWidth());
        $this -> assertEquals(9, $img -> getHeight());
    }
    
    function test_s09n3p02() {
        $img = $this -> loadImage("s09n3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(9, $img -> getWidth());
        $this -> assertEquals(9, $img -> getHeight());
    }
    
    function test_s32i3p04() {
        $img = $this -> loadImage("s32i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_s32n3p04() {
        $img = $this -> loadImage("s32n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_s33i3p04() {
        $img = $this -> loadImage("s33i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(33, $img -> getWidth());
        $this -> assertEquals(33, $img -> getHeight());
    }
    
    function test_s33n3p04() {
        $img = $this -> loadImage("s33n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(33, $img -> getWidth());
        $this -> assertEquals(33, $img -> getHeight());
    }
    
    function test_s34i3p04() {
        $img = $this -> loadImage("s34i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(34, $img -> getWidth());
        $this -> assertEquals(34, $img -> getHeight());
    }
    
    function test_s34n3p04() {
        $img = $this -> loadImage("s34n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(34, $img -> getWidth());
        $this -> assertEquals(34, $img -> getHeight());
    }
    
    function test_s35i3p04() {
        $img = $this -> loadImage("s35i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(35, $img -> getWidth());
        $this -> assertEquals(35, $img -> getHeight());
    }
    
    function test_s35n3p04() {
        $img = $this -> loadImage("s35n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(35, $img -> getWidth());
        $this -> assertEquals(35, $img -> getHeight());
    }
    
    function test_s36i3p04() {
        $img = $this -> loadImage("s36i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(36, $img -> getWidth());
        $this -> assertEquals(36, $img -> getHeight());
    }
    
    function test_s36n3p04() {
        $img = $this -> loadImage("s36n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(36, $img -> getWidth());
        $this -> assertEquals(36, $img -> getHeight());
    }
    
    function test_s37i3p04() {
        $img = $this -> loadImage("s37i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(37, $img -> getWidth());
        $this -> assertEquals(37, $img -> getHeight());
    }
    
    function test_s37n3p04() {
        $img = $this -> loadImage("s37n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(37, $img -> getWidth());
        $this -> assertEquals(37, $img -> getHeight());
    }
    
    function test_s38i3p04() {
        $img = $this -> loadImage("s38i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(38, $img -> getWidth());
        $this -> assertEquals(38, $img -> getHeight());
    }
    
    function test_s38n3p04() {
        $img = $this -> loadImage("s38n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(38, $img -> getWidth());
        $this -> assertEquals(38, $img -> getHeight());
    }
    
    function test_s39i3p04() {
        $img = $this -> loadImage("s39i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(39, $img -> getWidth());
        $this -> assertEquals(39, $img -> getHeight());
    }
    
    function test_s39n3p04() {
        $img = $this -> loadImage("s39n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(39, $img -> getWidth());
        $this -> assertEquals(39, $img -> getHeight());
    }
    
    function test_s40i3p04() {
        $img = $this -> loadImage("s40i3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(40, $img -> getWidth());
        $this -> assertEquals(40, $img -> getHeight());
    }
    
    function test_s40n3p04() {
        $img = $this -> loadImage("s40n3p04.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(40, $img -> getWidth());
        $this -> assertEquals(40, $img -> getHeight());
    }
    
    function test_tbbn0g04() {
        $img = $this -> loadImage("tbbn0g04.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbbn2c16() {
        $img = $this -> loadImage("tbbn2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbbn3p08() {
        $img = $this -> loadImage("tbbn3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbgn2c16() {
        $img = $this -> loadImage("tbgn2c16.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbgn3p08() {
        $img = $this -> loadImage("tbgn3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbrn2c08() {
        $img = $this -> loadImage("tbrn2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbwn0g16() {
        $img = $this -> loadImage("tbwn0g16.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbwn3p08() {
        $img = $this -> loadImage("tbwn3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tbyn3p08() {
        $img = $this -> loadImage("tbyn3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tm3n3p02() {
        $img = $this -> loadImage("tm3n3p02.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tp0n0g08() {
        $img = $this -> loadImage("tp0n0g08.png");
        $this -> assertTrue($img instanceOf GrayscaleRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tp0n2c08() {
        $img = $this -> loadImage("tp0n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tp0n3p08() {
        $img = $this -> loadImage("tp0n3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_tp1n3p08() {
        $img = $this -> loadImage("tp1n3p08.png");
        $this -> assertTrue($img instanceOf IndexedRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_xc1n0g08() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xc1n0g08.png");
    }
    
    function test_xc9n2c08() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xc9n2c08.png");
    }
    
    function test_xcrn0g04() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xcrn0g04.png");
    }
    
    function test_xcsn0g01() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xcsn0g01.png");
    }
    
    function test_xd0n2c08() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xd0n2c08.png");
    }
    
    function test_xd3n2c08() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xd3n2c08.png");
    }
    
    function test_xd9n2c08() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xd9n2c08.png");
    }
    
    function test_xdtn0g01() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xdtn0g01.png");
    }
    
    function test_xhdn0g08() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xhdn0g08.png");
    }
    
    function test_xlfn0g04() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xlfn0g04.png");
    }
    
    function test_xs1n0g01() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xs1n0g01.png");
    }
    
    function test_xs2n0g01() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xs2n0g01.png");
    }
    
    function test_xs4n0g01() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xs4n0g01.png");
    }
    
    function test_xs7n0g01() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("xs7n0g01.png");
    }
    
    function test_z00n2c08() {
        $img = $this -> loadImage("z00n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_z03n2c08() {
        $img = $this -> loadImage("z03n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_z06n2c08() {
        $img = $this -> loadImage("z06n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
    
    function test_z09n2c08() {
        $img = $this -> loadImage("z09n2c08.png");
        $this -> assertTrue($img instanceOf RgbRasterImage);
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }
}

