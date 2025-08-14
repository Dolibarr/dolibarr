<?php

use Mike42\GfxPhp\Image;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
*/
class PngDecodeBenchmark {

    private static $blob;

    public function init()
    {
        self::$blob = file_get_contents(__DIR__ . "/../../../resources/pngsuite/z09n2c08.png");
    }

    /**
     * @Subject
     */
    public function pngDecode()
    {
        Image::fromBlob(self::$blob, "foo.png");
    }

}