<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

use Mike42\Escpos\EscposImage;
use Exception;

/**
 * Implementation of EscposImage using the GD PHP plugin.
 */
class GdEscposImage extends EscposImage
{
    /**
     * Load an image from disk, into memory, using GD.
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
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($ext) {
            case "png":
                $im = @imagecreatefrompng($filename);
                break;
            case "jpg":
                $im = @imagecreatefromjpeg($filename);
                break;
            case "gif":
                $im = @imagecreatefromgif($filename);
                break;
            default:
                throw new Exception("Image format not supported in GD");
        }
        $this -> readImageFromGdResource($im);
    }

    /**
     * Load actual image pixels from GD resource.
     *
     * @param resource $im GD resource to use
     * @throws Exception Where the image can't be read.
     */
    public function readImageFromGdResource($im)
    {
        if (!is_resource($im)) {
            throw new Exception("Failed to load image.");
        } elseif (!EscposImage::isGdLoaded()) {
            throw new Exception(__FUNCTION__ . " requires 'gd' extension.");
        }
        /* Make a string of 1's and 0's */
        $imgHeight = imagesy($im);
        $imgWidth = imagesx($im);
        $imgData = str_repeat("\0", $imgHeight * $imgWidth);
        for ($y = 0; $y < $imgHeight; $y++) {
            for ($x = 0; $x < $imgWidth; $x++) {
                /* Faster to average channels, blend alpha and negate the image here than via filters (tested!) */
                $cols = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                // 1 for white, 0 for black, ignoring transparency
                $greyness = (int)(($cols['red'] + $cols['green'] + $cols['blue']) / 3) >> 7;
                // 1 for black, 0 for white, taking into account transparency
                $black = (1 - $greyness) >> ($cols['alpha'] >> 6);
                $imgData[$y * $imgWidth + $x] = $black;
            }
        }
        $this -> setImgWidth($imgWidth);
        $this -> setImgHeight($imgHeight);
        $this -> setImgData($imgData);
    }
}
