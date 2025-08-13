<?php

use Mike42\GfxPhp\Image;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 */
class GifDecodeBenchmark {

    private static $blob;

    public function init()
    {
        self::$blob = file_get_contents(__DIR__ . "/../../../resources/pygif/high-color.gif");
    }

    /**
     * @Subject
     */
    public function gifDecode()
    {
        Image::fromBlob(self::$blob, "foo.gif");
    }

}