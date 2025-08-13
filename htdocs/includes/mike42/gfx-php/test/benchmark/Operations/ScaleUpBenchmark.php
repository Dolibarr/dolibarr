<?php

use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\IndexedRasterImage;
use Mike42\GfxPhp\RgbRasterImage;

/**
 * @BeforeMethods({"init"})
 * @Revs(10)
 */
class ScaleUpBenchmark
{
    private static $bw10;
    private static $rgb10;
    private static $indexed10;
    private static $gray10;

    public function init()
    {
        self::$bw10 = BlackAndWhiteRasterImage::create(10, 10);
        self::$rgb10 = RgbRasterImage::create(10, 10);
        self::$indexed10 = IndexedRasterImage::create(10, 10);
        self::$gray10 = GrayscaleRasterImage::create(10, 10);
    }

    /**
     * @Subject
     */
    public function scaleUpBw()
    {
        self::$bw10->scale(100, 100);
    }

    /**
     * @Subject
     */
    public function scaleUpGrayscale()
    {
        self::$gray10->scale(100, 100);
    }

    /**
     * @Subject
     */
    public function scaleUpIndexed()
    {
        self::$indexed10->scale(100, 100);
    }

    /**
     * @Subject
     */
    public function scaleUpRgb()
    {
        self::$rgb10->scale(100, 100);
    }
}
