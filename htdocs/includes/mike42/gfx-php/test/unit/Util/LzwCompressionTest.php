<?php
use Mike42\GfxPhp\Util\LzwCompression;
use Mike42\GfxPhp\Util\LzwDecodeBuffer;

use PHPUnit\Framework\TestCase;

class LzwCompressionTest extends TestCase
{
    /*
     * Known-good GIF-LZW strings from Wikipedia
     * for 3x5 image like the one shown below.
     *
     * https://en.wikipedia.org/wiki/GIF
     *╭──────╮
     *│██░░░░│   ██ = 0x40
     *│░░██░░│   ░░ = 0xFF
     *│░░░░░░│
     *│░░░░░░│
     *│░░░░░░│
     *╰──────╯
     */
    const compressedChars = [0x00, 0x51, 0xFC, 0x1B, 0x28, 0x70, 0xA0, 0xC1, 0x83, 0x01, 0x01];
    const uncompressedChars = [0x28, 0xFF, 0xFF, 0xFF, 0x28, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF];

    public function testLzwDecodeBufferBytes()
    {
        // Ensure we can extract numbers from input stream
        $contents = "ABCD";
        $buffer = new LzwDecodeBuffer($contents);
        $this -> assertEquals(65,  $buffer -> read(8));
        $this -> assertEquals(66,  $buffer -> read(8));
        $this -> assertEquals(67,  $buffer -> read(8));
        $this -> assertEquals(68,  $buffer -> read(8));
        $this -> assertEquals(false,  $buffer -> read(8));
    }
    
    public function testLzwDecodeBufferBits()
    {
        // Ensure we can extract numbers of strange lengths from input stream
        $inp = pack("C*", ... LzwCompressionTest::compressedChars);
        $buffer = new LzwDecodeBuffer($inp);
        $this -> assertEquals(0x100,  $buffer -> read(9));
        $this -> assertEquals(0x028,  $buffer -> read(9));
        $this -> assertEquals(0x0FF,  $buffer -> read(9));
        $this -> assertEquals(0x103,  $buffer -> read(9));
        $this -> assertEquals(0x102,  $buffer -> read(9));
        $this -> assertEquals(0x103,  $buffer -> read(9));
        $this -> assertEquals(0x106,  $buffer -> read(9));
        $this -> assertEquals(0x107,  $buffer -> read(9));
        $this -> assertEquals(0x101,  $buffer -> read(9));
        $this -> assertEquals(false,  $buffer -> read(9));
    }
    
    public function testLzwDecompression()
    {
        // Test de-compression
        $compressedStr = pack("C*", ... LzwCompressionTest::compressedChars);
        $uncompressedStr = LzwCompression::decompress($compressedStr, 8);
        $this -> assertEquals(LzwCompressionTest::uncompressedChars, array_values(unpack("C*", $uncompressedStr)));
    }
    
    public function testLzwCompression()
    {
        // Test de-compression
        $uncompressedStr = pack("C*", ... LzwCompressionTest::uncompressedChars);
        $compressedStr = LzwCompression::compress($uncompressedStr, 8);
        $this -> assertEquals(LzwCompressionTest::compressedChars, array_values(unpack("C*", $compressedStr)));
    }

}