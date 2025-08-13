<?php

namespace Mike42\GfxPhp\Codec\Bmp;

use PHPUnit\Framework\TestCase;

class BmpColorMaskTest  extends TestCase
{
    public function testEmpty() {
        $mask = new BmpColorMask(0x00); // 00000000
        $this -> assertEquals(0, $mask -> getLen());
        $this -> assertEquals(0, $mask -> getOffset());
        $this -> assertEquals(0, $mask -> getMaxValue());
        $this -> assertEquals(0, $mask -> getValue(0xFF));
        $this -> assertEquals(0, $mask -> getNormalisedValue(0xFF));
    }

    public function testOne() {
        $mask = new BmpColorMask(0x01); // 00000001
        $this -> assertEquals(1, $mask -> getLen());
        $this -> assertEquals(0, $mask -> getOffset());
        $this -> assertEquals(1, $mask -> getMaxValue());
        // Regular
        $this -> assertEquals(0, $mask -> getValue(0xFE)); // 11111110
        $this -> assertEquals(1, $mask -> getValue(0x01)); // 00000001
        // Normalised
        $this -> assertEquals(0, $mask -> getNormalisedValue(0xFE));
        $this -> assertEquals(255, $mask -> getNormalisedValue(0x01));
    }

    public function testOffset() {
        $mask = new BmpColorMask(0x10); // 00010000
        $this -> assertEquals(1, $mask -> getLen());
        $this -> assertEquals(4, $mask -> getOffset());
        $this -> assertEquals(1, $mask -> getMaxValue());
        // Regular
        $this -> assertEquals(0, $mask -> getValue(0xEF)); // 11101111
        $this -> assertEquals(1, $mask -> getValue(0x10)); // 00010000
        // Normalised
        $this -> assertEquals(0, $mask -> getNormalisedValue(0xEF));
        $this -> assertEquals(255, $mask -> getNormalisedValue(0x10));
    }

    public function testLength() {
        $mask = new BmpColorMask(0x30); // 00110000
        $this -> assertEquals(2, $mask -> getLen());
        $this -> assertEquals(4, $mask -> getOffset());
        $this -> assertEquals(3, $mask -> getMaxValue());
        // Regular
        $this -> assertEquals(0, $mask -> getValue(0xCF)); // 11001111
        $this -> assertEquals(2, $mask -> getValue(0x20)); // 00100000 -> 10
        $this -> assertEquals(3, $mask -> getValue(0x30)); // 00110000
        // Normalised
        $this -> assertEquals(0, $mask -> getNormalisedValue(0xCF));
        $this -> assertEquals(170, $mask -> getNormalisedValue(0x20)); // 10 in binary shifted then repeated to use full space.
        $this -> assertEquals(255, $mask -> getNormalisedValue(0x30));
    }

    public function testLarge() {
        $mask = new BmpColorMask(0x03FF); // 00000011 11111111
        $this -> assertEquals(10, $mask -> getLen());
        $this -> assertEquals(0, $mask -> getOffset());
        $this -> assertEquals(1023, $mask -> getMaxValue());
        // Regular
        $this -> assertEquals(0, $mask -> getValue(0x0000));
        $this -> assertEquals(511, $mask -> getValue(0x01FF));
        $this -> assertEquals(1023, $mask -> getValue(0x03FF));
        // Normalised - note how raw value is scaled down this time
        $this -> assertEquals(0, $mask -> getNormalisedValue(0x0000));
        $this -> assertEquals(127, $mask -> getNormalisedValue(0x01FF));
        $this -> assertEquals(255, $mask -> getNormalisedValue(0x03FF));
    }

    public function testNonContiguous() {
        $this -> expectException(\Exception::class);
        $mask = new BmpColorMask(0x50); // 01010000
        $this -> assertEquals(3, $mask -> getLen());
        $this -> assertEquals(4, $mask -> getOffset());
    }
}
