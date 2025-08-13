<?php
// This file is part of mike42/gfx-php, which is released under LGPL-2.1-or-later.
// See file LICENSE or go to https://github.com/mike42/gfx-php for full license details

/**
 * Top-level namespace for Mike42\GfxPhp, containing the classes and
 * interfaces that a provide an entry-point into the library.
 */
namespace Mike42\GfxPhp;

/**
 * Generic interface to raster images.
 */
interface RasterImage
{
    /**
     * Get the width of the image in pixels.
     *
     * @return int The width of the image in pixels.
     */
    public function getWidth(): int;

    /**
     * Get the height of the image in pixels.
     *
     * @return int The height of the image in pixels.
     */
    public function getHeight(): int;

    /**
     * Get a binary string representing the underlying image data. The formatting of this data is implementation-dependent.
     * @return string A binary string representation of the raster data for this image.
     */
    public function getRasterData(): string;

    /**
     * Produce a new RasterImage based on this one. The new image will be scaled to the requested dimensions via resampling.
     *
     * @param int $width The width of the returned image.
     * @param int $height The height of the returned image.
     * @return RasterImage A scaled version of the image.
     */
    public function scale(int $width, int $height): RasterImage;
    
    /**
     * Write the image to a file. The output format is determined by the file extension.
     *
     * @param string $filename Filename to write to.
     */
    public function write(string $filename);

    /**
     * Produce a copy of this RasterImage in the RGB colorspace.
     *
     * @return RgbRasterImage An RGB version of the image.
     */
    public function toRgb() : RgbRasterImage;

    /**
     * Get the value of a given pixel. The meaning of the integer value of this pixel is implementation-dependent.
     *
     * @param int $x X co-ordinate
     * @param int $y Y co-ordinate
     * @return int The value of the pixel at ($x, $y).
     */
    public function getPixel(int $x, int $y) : int;

    /**
     * Set the value of a given pixel.
     * @param int $x X co-ordinate
     * @param int $y Y co-ordinate
     * @param int $value Value to set
     */
    public function setPixel(int $x, int $y, int $value);

    /**
     * Produce a copy of this RasterImage in a monochrome colorspace.
     *
     * @return GrayscaleRasterImage A monochrome version of the image.
     */
    public function toGrayscale() : GrayscaleRasterImage;

    /**
     * Produce a copy of this RasterImage in a pure black-and-white colorspace.
     * @return BlackAndWhiteRasterImage a black and white version of the image.
     */
    public function toBlackAndWhite() : BlackAndWhiteRasterImage;

    /**
     * Produce a copy of this RasterImage as an indexed image with an associated palette of unique colors.
     *
     * @return IndexedRasterImage An paletted version of the image.
     */
    public function toIndexed() : IndexedRasterImage;
}
