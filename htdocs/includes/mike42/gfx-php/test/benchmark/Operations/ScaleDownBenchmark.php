<?php

use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\IndexedRasterImage;
use Mike42\GfxPhp\RgbRasterImage;

/**
 * @BeforeMethods({"init"})
 * @Revs(10000)
 */
class ScaleDownBenchmark
{
    private static $bw100;
    private static $rgb100;
    private static $indexed100;
    private static $gray100;

    public function init()
    {
        self::$bw100 = BlackAndWhiteRasterImage::create(100, 100);
        self::$rgb100 = RgbRasterImage::create(100, 100);
        self::$indexed100 = IndexedRasterImage::create(100, 100);
        self::$gray100 = GrayscaleRasterImage::create(100, 100);
    }

    /**
     * @Subject
     */
    public function scaleDownBw()
    {
        self::$bw100->scale(10, 10);
    }

    /**
     * @Subject
     */
    public function scaleDownGrayscale()
    {
        self::$gray100->scale(10, 10);
    }

    /**
     * @Subject
     */
    public function scaleDownIndexed()
    {
        self::$indexed100->scale(10, 10);
    }

    /**
     * @Subject
     */
    public function scaleDownRgb()
    {
        self::$rgb100->scale(10, 10);
    }
}
