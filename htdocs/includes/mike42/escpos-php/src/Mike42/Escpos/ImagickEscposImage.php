<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-18 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

use Exception;
use Imagick;
use Mike42\Escpos\EscposImage;

/**
 * Implementation of EscposImage using the Imagick PHP plugin.
 */
class ImagickEscposImage extends EscposImage
{
    /**
     * Load actual image pixels from Imagick object
     *
     * @param Imagick $im Image to load from
     */
    public function readImageFromImagick(\Imagick $im)
    {
        /* Strip transparency */
        $im = self::alphaRemove($im);
        /* Threshold */
        $im -> setImageType(\Imagick::IMGTYPE_TRUECOLOR); // Remove transparency (good for PDF's)
        $max = $im->getQuantumRange();
        $max = $max["quantumRangeLong"];
        $im -> thresholdImage(0.5 * $max);
        /* Make a string of 1's and 0's */
        $imgHeight = $im -> getimageheight();
        $imgWidth = $im -> getimagewidth();
        $imgData = str_repeat("\0", $imgHeight * $imgWidth);
        for ($y = 0; $y < $imgHeight; $y++) {
            for ($x = 0; $x < $imgWidth; $x++) {
                /* Faster to average channels, blend alpha and negate the image here than via filters (tested!) */
                $cols = $im -> getImagePixelColor($x, $y);
                $cols = $cols -> getcolor();
                $greyness = (int)(($cols['r'] + $cols['g'] + $cols['b']) / 3) >> 7;  // 1 for white, 0 for black
                $imgData[$y * $imgWidth + $x] = (1 - $greyness); // 1 for black, 0 for white
            }
        }
        $this -> setImgWidth($imgWidth);
        $this -> setImgHeight($imgHeight);
        $this -> setImgData($imgData);
    }

    /**
     * @param string $filename
     *  Filename to load from
     * @param boolean $highDensityVertical
     *  True for high density output (24px lines), false for regular density (8px)
     * @return string[]|NULL
     *  Column format data as array, or NULL if optimised renderer isn't
     *  available in this implementation.
     */
    protected function getColumnFormatFromFile($filename = null, $highDensityVertical = true)
    {
        if ($filename === null) {
            return null;
        }
        $im = $this -> getImageFromFile($filename);
        $this -> setImgWidth($im -> getimagewidth());
        $this -> setImgHeight($im -> getimageheight());
        
        /* Strip transparency */
        $im = self::alphaRemove($im);
        $im -> setformat('pbm');
        $im -> getimageblob(); // Forces 1-bit rendering now, so that subsequent operations are faster
        $im -> rotateImage('#fff', 90.0);
        $im -> flopImage();
        $lineHeight = $highDensityVertical ? 3 : 1;
        $blobs = $this -> getColumnFormatFromImage($im, $lineHeight * 8);
        return $blobs;
    }

    /**
     * Load an image from disk, into memory, using Imagick.
     *
     * @param string|null $filename The filename to load from
     * @throws Exception if the image format is not supported,
     *  or the file cannot be opened.
     */
    protected function loadImageData($filename = null)
    {
        if ($filename === null) {
            /* Set to blank image */
            return parent::loadImageData($filename);
        }
    
        $im = $this -> getImageFromFile($filename);
        $this -> readImageFromImagick($im);
    }

    /**
     * Return data in column format as array of slices.
     * Operates recursively to save cloning larger image many times.
     *
     * @param Imagick $im
     * @param int $lineHeight
     *          Height of printed line in dots. 8 or 24.
     * @return string[]
     */
    private function getColumnFormatFromImage(Imagick $im, $lineHeight)
    {
        $imgWidth = $im->getimagewidth();
        if ($imgWidth == $lineHeight) {
            // Return glob of this panel
            return [$this -> getRasterBlobFromImage($im)];
        } elseif ($imgWidth > $lineHeight) {
            // Calculations
            $slicesLeft = ceil($imgWidth / $lineHeight / 2);
            $widthLeft = $slicesLeft * $lineHeight;
            $widthRight = $imgWidth - $widthLeft;
            // Slice up (left)
            $left = clone $im;
            $left -> extentimage($widthLeft, $left -> getimageheight(), 0, 0);
            // Slice up (right - ensure width is divisible by lineHeight also)
            $right = clone $im;
            $widthRightRounded = $widthRight < $lineHeight ? $lineHeight : $widthRight;
            $right -> extentimage($widthRightRounded, $right -> getimageheight(), $widthLeft, 0);
            // Recurse
            $leftBlobs = $this -> getColumnFormatFromImage($left, $lineHeight);
            $rightBlobs = $this -> getColumnFormatFromImage($right, $lineHeight);
            return array_merge($leftBlobs, $rightBlobs);
        } else {
            /* Image is smaller than full width */
            $im -> extentimage($lineHeight, $im -> getimageheight(), 0, 0);
            return [$this -> getRasterBlobFromImage($im)];
        }
    }

    /**
     * Load Imagick file from image
     *
     * @param string $filename Filename to load
     * @throws Exception Wrapped Imagick error if image can't be loaded
     * @return Imagick Loaded image
     */
    private function getImageFromFile($filename)
    {
        $im = new Imagick();
        try {
            $im->setResourceLimit(6, 1); // Prevent libgomp1 segfaults, grumble grumble.
            $im -> readimage($filename);
        } catch (\ImagickException $e) {
            /* Re-throw as normal exception */
            throw new Exception($e);
        }
        return $im;
    }

    /**
     * Pull blob (from PBM-formatted image only!), and spit out a blob or raster data.
     * Will crash out on anything which is not a valid 'P4' file.
     *
     * @param Imagick $im Image which has format PBM.
     * @return string raster data from the image
     */
    private function getRasterBlobFromImage(Imagick $im)
    {
        $blob = $im -> getimageblob();
        /* Find where header ends */
        $i = strpos($blob, "P4\n") + 2;
        while ($blob[$i + 1] == '#') {
            $i = strpos($blob, "\n", $i + 1);
        }
        $i = strpos($blob, "\n", $i + 1);
        /* Return raster data only */
        $subBlob = substr($blob, $i + 1);
        return $subBlob;
    }

    /**
     * @param string $filename
     *  Filename to load from
     * @return string|NULL
     *  Raster format data, or NULL if no optimised renderer is available in
     *  this implementation.
     */
    protected function getRasterFormatFromFile($filename = null)
    {
        if ($filename === null) {
            return null;
        }
        $im = $this -> getImageFromFile($filename);
        $this -> setImgWidth($im -> getimagewidth());
        $this -> setImgHeight($im -> getimageheight());
        /* Convert to PBM and extract raster portion */
        $im = self::alphaRemove($im);
        $im -> setFormat('pbm');
        return $this -> getRasterBlobFromImage($im);
    }

    /**
     * Load a PDF for use on the printer
     *
     * @param string $pdfFile
     *  The file to load
     * @param int $pageWidth
     *  The width, in pixels, of the printer's output. The first page of the
     *  PDF will be scaled to approximately fit in this area.
     * @throws Exception Where Imagick is not loaded, or where a missing file
     *  or invalid page number is requested.
     * @return array Array of images, retrieved from the PDF file.
     */
    public static function loadPdf($pdfFile, $pageWidth = 550)
    {
        if (!EscposImage::isImagickLoaded()) {
            throw new Exception(__FUNCTION__ . " requires imagick extension.");
        }
        /*
         * Load first page at very low density (resolution), to figure out what
         * density to use to achieve $pageWidth
         */
        try {
            $image = new \Imagick();
            $testRes = 2; // Test resolution
            $image -> setresolution($testRes, $testRes);
            /* Load document just to measure geometry */
            $image -> readimage($pdfFile);
            $geo = $image -> getimagegeometry();
            $image -> destroy();
            $width = $geo['width'];
            $newRes = $pageWidth / $width * $testRes;
            /* Load entire document in */
            $image -> setresolution($newRes, $newRes);
            $image -> readImage($pdfFile);
            $pages = $image -> getNumberImages();
            /* Convert images to Escpos objects */
            $ret = [];
            for ($i = 0; $i < $pages; $i++) {
                $image -> setIteratorIndex($i);
                $ep = new ImagickEscposImage();
                $ep -> readImageFromImagick($image);
                $ret[] = $ep;
            }
            return $ret;
        } catch (\ImagickException $e) {
            /* Wrap in normal exception, so that classes which call this do not
             * themselves require imagick as a dependency. */
            throw new Exception($e);
        }
    }

    /**
     * Paste image over white canvas to stip transparency reliably on different
     * versions of ImageMagick.
     *
     * There are other methods for this:
     * - flattenImages() is deprecated
     * - setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE) is not available on
     *      ImageMagick < 6.8.
     *
     * @param Imagick $im Image to flatten
     * @return Imagick Flattened image
     */
    private static function alphaRemove(Imagick $im)
    {
        $flat = new \Imagick();
        $flat -> newImage($im -> getimagewidth(), $im -> getimageheight(), "white", $im -> getimageformat());
        $flat -> compositeimage($im, \Imagick::COMPOSITE_OVER, 0, 0);
        return $flat;
    }
}
