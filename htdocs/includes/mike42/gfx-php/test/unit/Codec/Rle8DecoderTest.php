<?php

namespace Mike42\GfxPhp\Codec\Bmp;

use Exception;
use PHPUnit\Framework\TestCase;

class Rle8DecoderTest extends TestCase
{
    public function testDecodeEmpty()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([], 0, 0);
        $this -> assertEquals([], $result);
    }

    public function testDecodeEmpty2()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            0, 1 // End of bitmap
        ], 0, 0);
        $this -> assertEquals([], $result);
    }

    public function testDecodeRun()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            3, 1, // 3 1's
            0, 0, // Line break
            3, 3, // 3 3's
            0, 0, // Line break
            3, 5, // 3 5's
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
          [1, 1, 1],
          [3, 3, 3],
          [5, 5, 5]
        ];
        $this -> assertEquals($expected, $result);
    }

    public function testRunTooWide()
    {
        $this -> expectException(Exception::class);
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            4, 1, // 4 1's (x-direction overflow)
            0, 0, // Line break
            3, 3, // 3 3's
            0, 0, // Line break
            3, 5, // 3 5's
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
            [1, 1, 1],
            [3, 3, 3],
            [5, 5, 5]
        ];
        $this -> assertEquals($expected, $result);
    }

    public function testRunTooTall()
    {
        $this -> expectException(Exception::class);
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            3, 1, // 3 1's
            0, 0, // Line break
            3, 3, // 3 3's
            0, 0, // Line break
            3, 5, // 3 5's
            0, 0, // Line break
            3, 6, // 3 6's (y-direction overflow)
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
            [1, 1, 1],
            [3, 3, 3],
            [5, 5, 5]
        ];
        $this -> assertEquals($expected, $result);
    }


    public function testDeltaDiagonal()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            0, 2, // Jump..
            2, 2, // .. diagonally
            1, 1, // Print 1
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
            [0, 0, 0],
            [0, 0, 0],
            [0, 0, 1]
        ];
        $this -> assertEquals($expected, $result);
    }

    public function testDeltaRight()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            0, 2, // Jump..
            2, 0, // .. right
            1, 1, // Print 1
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
            [0, 0, 1],
            [0, 0, 0],
            [0, 0, 0]
        ];
        $this -> assertEquals($expected, $result);
    }

    public function testDeltaDown()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            0, 2, // Jump..
            0, 2, // .. down
            1, 1, // Print 1
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
            [0, 0, 0],
            [0, 0, 0],
            [1, 0, 0]
        ];
        $this -> assertEquals($expected, $result);
    }

    public function testAbsolute()
    {
        $decoder = new Rle8Decoder();
        $result = $decoder -> decodeNumbers([
            0, 3, 1, 2, 3, 0, // Absolute run of 3 pixels + 1 pixel of padding
            0, 0, // Line break
            0, 3, 4, 5, 6, 0, // 3 pixels
            0, 0, // Line break
            0, 3, 7, 8, 9, 0, // 3 pixels
            0, 0, // Line break
            0, 1 // End of bitmap
        ], 3, 3);
        $expected = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9]
        ];
        $this -> assertEquals($expected, $result);
    }
}
