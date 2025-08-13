<?php

use Mike42\GfxPhp\Image;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 */
class ConvertRasterImageBenchmark
{
    private static $bw100;
    private static $gray100;
    private static $indexed100;
    private static $rgb100;

    public function init()
    {
        self::$rgb100 = Image::fromFile(__DIR__ . "/../../resources/pngsuite/z09n2c08.png");
        self::$bw100 = self::$rgb100 -> toBlackAndWhite();
        self::$indexed100 = self::$rgb100 -> toIndexed();
        self::$gray100 = self::$rgb100 -> toGrayscale();
    }

    /**
     * @Subject
     */
    public function convertBwToBw()
    {
        self::$bw100->toBlackAndWhite();
    }

    /**
     * @Subject
     */
    public function convertBwToGray()
    {
        self::$bw100->toGrayscale();
    }

    /**
     * @Subject
     */
    public function convertBwToIndexed()
    {
        self::$bw100->toIndexed();
    }

    /**
     * @Subject
     */
    public function convertBwToRgb()
    {
        self::$bw100->toRgb();
    }

    /**
     * @Subject
     */
    public function convertGrayToBw()
    {
        self::$gray100->toBlackAndWhite();
    }

    /**
     * @Subject
     */
    public function convertGrayToGray()
    {
        self::$gray100->toGrayscale();
    }

    /**
     * @Subject
     */
    public function convertGrayToIndexed()
    {
        self::$gray100->toIndexed();
    }

    /**
     * @Subject
     */
    public function convertGrayToRgb()
    {
        self::$gray100->toRgb();
    }

    /**
     * @Subject
     */
    public function convertIndexedToBw()
    {
        self::$indexed100->toBlackAndWhite();
    }

    /**
     * @Subject
     */
    public function convertIndexedToGray()
    {
        self::$indexed100->toGrayscale();
    }

    /**
     * @Subject
     */
    public function convertIndexedToIndexed()
    {
        self::$indexed100->toIndexed();
    }

    /**
     * @Subject
     */
    public function convertIndexedToRgb()
    {
        self::$indexed100->toRgb();
    }

    /**
     * @Subject
     */
    public function convertRgbToBw()
    {
        self::$rgb100->toBlackAndWhite();
    }

    /**
     * @Subject
     */
    public function convertRgbToGray()
    {
        self::$rgb100->toGrayscale();
    }

    /**
     * @Subject
     */
    public function convertRgbToIndexed()
    {
        self::$rgb100->toIndexed();
    }

    /**
     * @Subject
     */
    public function convertRgbToRgb()
    {
        self::$rgb100->toRgb();
    }
}
