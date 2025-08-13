<?php

namespace Mike42\GfxPhp;

use Mike42\GfxPhp\Codec\ImageCodec;

class Image
{
    // Color depths
    const IMAGE_BLACK_WHITE = 1;
    const IMAGE_GRAY = 2;
    const IMAGE_RGB = 3;
    const IMAGE_RGBA = 4;

    protected static $codecs = null;
    
    public static function fromFile(string $filename) : RasterImage
    {
        // Attempt to catch the cause of any errors
        self::clearLastError();
        $blob = @file_get_contents($filename);
        if ($blob === false) {
            $error = self::getLastErrorOrDefault("Check that the file exists and can be read.");
            throw new \Exception("Could not retrieve image data from '$filename'. $error");
        }
        return self::fromBlob($blob, $filename);
    }

    public static function fromBlob(string $blob, string $filename = null) : RasterImage
    {
        if (self::$codecs === null) {
            self::$codecs = ImageCodec::getInstance();
        }
        $format = self::$codecs -> identify($blob);
        if ($format === null) {
            throw new \Exception("Unknown format for image '$filename'.");
        }
        $decoder = self::$codecs ->getDecoderForFormat($format);
        if ($decoder === null) {
            throw new \Exception("Format $format not supported, reading '$filename'.");
        }
        return $decoder -> decode($blob);
    }
    
    public static function create(int $width, int $height, int $impl = self::IMAGE_BLACK_WHITE)
    {
        return BlackAndWhiteRasterImage::create($width, $height);
    }

    /**
     * Call error_clear_last() if it exists. This is dependent on which PHP runtime is used.
     */
    private static function clearLastError()
    {
        if (function_exists('error_clear_last')) {
            error_clear_last();
        }
    }

    /**
     * Retrieve the message from error_get_last() if possible. This is very useful for debugging, but it will not
     * always exist or return anything useful.
     */
    private static function getLastErrorOrDefault(string $default)
    {
        if (function_exists('error_clear_last')) {
            $e = error_get_last();
            if (isset($e) && isset($e['message']) && $e['message'] != "") {
                return $e['message'];
            }
        }
        return $default;
    }
}
