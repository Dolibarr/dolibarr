<?php
use Mike42\GfxPhp\Image;
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\RgbRasterImage;
use Mike42\GfxPhp\Codec\PngCodec;

class PngCodecTest extends TestCase
{
    // Example file that we compare encoder results against, and decode.
    const SMALL_FILE = "\x89PNG\x0d\x0a\x1a\x0a\x00\x00\x00\x0dIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde\x00\x00\x00\x0cIDATx\x9cc\xf8\xff\xff?\x00\x05\xfe\x02\xfe\x0d\xefF\xb8\x00\x00\x00\x00IEND\xaeB`\x82";
    
    public function testEncode() {
        // Simplest image I can come up with
        $img = RgbRasterImage::create(1, 1);
        $codec = new PngCodec();
        // Encode
        $pngStr = $codec -> encode($img, "png");
        // Compare to known-good PNG
        $this -> assertEquals(self::SMALL_FILE, $pngStr);
    }

    public function testDecode() {
        $codec = new PngCodec();
        $img = $codec -> decode(self::SMALL_FILE);
        $this -> assertTrue($img instanceof RgbRasterImage);
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    public function testBlackAndWhiteImageLoad() {
        // Simple test of a black-and-white, interlaced & non-interlaced image, since
        // we can do a text-based assertion on the actual image content.
        $expected = "                              ▄█\n" .
                    "                            ▄███\n" .
                    "    ██      ██            ▄█████\n" .
                    "    ██  ▄▄  ██          ▄███████\n" .
                    "    ██  ██  ██        ▄█████████\n" .
                    "     ████████       ▄███████████\n" .
                    "      ██  ██      ▄█████████████\n" .
                    "                ▄███████████████\n" .
                    "              ▄█████████████████\n" .
                    "            ▄███████       █████\n" .
                    "          ▄█████████  ████  ████\n" .
                    "        ▄███████████       █████\n" .
                    "      ▄█████████████  ████  ████\n" .
                    "    ▄███████████████       █████\n" .
                    "  ▄█████████████████████████████\n" .
                    "▄███████████████████████████████\n";
        // Load interlaced & non-interlaced
        $interlacedImage = Image::fromFile(__DIR__ . "/../../resources/pngsuite/basi0g01.png");
        $interlacedResult = $interlacedImage -> toString();
        $nonInterlacedImage = Image::fromFile(__DIR__ . "/../../resources/pngsuite/basn0g01.png");
        $nonInterlacedResult = $nonInterlacedImage -> toString();
        // These should both match the expected output
        $this -> assertEquals($expected, $interlacedResult);
        $this -> assertEquals($expected, $nonInterlacedResult);
    }
    
    public function testIndexedImageLoad() {
        // Load an interlaced 8-bit image, which follows a very different code path to
        // lower bit depths.
        $img = Image::fromFile(__DIR__ . "/../../resources/pngsuite/basi3p08.png") -> toIndexed();
        $this -> assertEquals(255, $img -> getMaxVal());
        $this -> assertEquals(32, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }

    public function testFiltering() {
        // Expected value is sensitive to the grayscale -> B&W conversion
        $expected = "████████████████████████████████\n" .
                    "███████████▀▀▀▀▀▀▀▀▀▀███████████\n" .
                    "████████▀              ▀████████\n" .
                    "██████▀                  ▀██████\n" .
                    "████▀                      ▀████\n" .
                    "███▀                        ▀███\n" .
                    "███                          ███\n" .
                    "███                          ███\n" .
                    "███                          ███\n" .
                    "███                          ███\n" .
                    "███▄                        ▄███\n" .
                    "████▄                      ▄████\n" .
                    "██████▄                  ▄██████\n" .
                    "████████▄              ▄████████\n" .
                    "███████████▄▄▄▄▄▄▄▄▄▄███████████\n" .
                    "████████████████████████████████\n";
        // Image with different filters on each scanline
        $img = Image::fromFile(__DIR__ . "/../../resources/pngsuite/f99n0g04.png") -> toBlackAndWhite();
        $result = $img -> toString();
        $this -> assertEquals($expected, $result);
    }
}