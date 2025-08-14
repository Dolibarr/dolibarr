<?php

use Mike42\GfxPhp\Util\LzwCompression;

/**
 * @BeforeMethods({"init"})
 * @Revs(10)
 */
class LzwEncodeBenchmark
{
    private $blank;
    private $random;
    private $repeating;

    public function init()
    {
        $this -> blank = str_repeat("\0", 65536);
        $this -> random = $this -> random_str(65536);
        $this -> repeating = str_repeat($this -> random_str(256), 256);
    }

    /**
     * @Subject
     */
    public function lzwEncodeBlank() {
        LzwCompression::compress($this -> blank, 0x08);
    }

    /**
     * @Subject
     */
    public function lzwEncodeRandom() {
        LzwCompression::compress($this -> random, 0x08);
    }

    /**
     * @Subject
     */
    public function lzwEncodeRepeating() {
        LzwCompression::compress($this -> repeating, 0x08);
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