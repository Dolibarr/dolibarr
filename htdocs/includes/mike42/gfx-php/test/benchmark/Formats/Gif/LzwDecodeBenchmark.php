<?php

use Mike42\GfxPhp\Util\LzwCompression;

/**
 * @BeforeMethods({"init"})
 * @Revs(100)
 */
class LzwDecodeBenchmark
{
    private $blank;
    private $random;
    private $repeating;

    public function init()
    {
        $this -> blank = LzwCompression::compress(str_repeat("\0", 65536), 0x08);
        $this -> random = LzwCompression::compress($this -> random_str(65536), 0x08);
        $this -> repeating = LzwCompression::compress(str_repeat($this -> random_str(256), 256), 0x08);
    }

    /**
     * @Subject
     */
    public function lzwDecodeBlank() {
        LzwCompression::decompress($this -> blank, 0x08);
    }

    /**
     * @Subject
     */
    public function lzwDecodeRandom() {
        LzwCompression::decompress($this -> random, 0x08);
    }

    /**
     * @Subject
     */
    public function lzwDecodeRepeating() {
        LzwCompression::decompress($this -> repeating, 0x08);
    }

    /** Random string, not cryptographically strong */
    public static function random_str(int $len)
    {
        $str = str_repeat("\0", $len);
        for($i = 0 ; $i < $len; $i++) {
            $str[$i] = chr(rand(0, 255));
        }
        return $str;
    }
}