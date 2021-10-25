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

namespace Mike42\Escpos\PrintBuffers;

use Exception;
use LogicException;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\ImagickEscposImage;

/**
 * This class renders text to small images on-the-fly. It attempts to mimic the
 * behaviour of text output, whilst supporting any fonts & character encodings
 * which your system can handle. This class currently requires Imagick.
 */
class ImagePrintBuffer implements PrintBuffer
{
    private $printer;

    /**
     * @var string|null font to use
     */
    private $font;

    private $fontSize;

    public function __construct()
    {
        if (!EscposImage::isImagickLoaded()) {
            throw new Exception("ImagePrintBuffer requires the imagick extension");
        }
        $this -> font = null;
        $this -> fontSize = 24;
    }

    public function flush()
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
    }

    public function getPrinter()
    {
        return $this -> printer;
    }

    public function setPrinter(Printer $printer = null)
    {
        $this -> printer = $printer;
    }

    public function writeText($text)
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
        if ($text == null) {
            return;
        }
        $text = trim($text, "\n");
        /* Create Imagick objects */
        $image = new \Imagick();
        $draw = new \ImagickDraw();
        $color = new \ImagickPixel('#000000');
        $background = new \ImagickPixel('white');
            
        /* Create annotation */
        if ($this->font !== null) {
            // Allow fallback on defaults as necessary
            $draw->setFont($this->font);
        }
        /* In Arial, size 21 looks good as a substitute for FONT_B, 24 for FONT_A */
        $draw -> setFontSize($this -> fontSize);
        $draw -> setFillColor($color);
        $draw -> setStrokeAntialias(true);
        $draw -> setTextAntialias(true);
        $metrics = $image -> queryFontMetrics($draw, $text);
        $draw -> annotation(0, $metrics['ascender'], $text);

        /* Create image & draw annotation on it */
        $image -> newImage($metrics['textWidth'], $metrics['textHeight'], $background);
        $image -> setImageFormat('png');
        $image -> drawImage($draw);
        // debugging if you want to view the images yourself
        //$image -> writeImage("test.png");

        /* Save image */
        $escposImage = new ImagickEscposImage();
        $escposImage -> readImageFromImagick($image);
        $size = Printer::IMG_DEFAULT;
        $this -> printer -> bitImage($escposImage, $size);
    }

    public function writeTextRaw($text)
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
        $this -> printer -> getPrintConnector() -> write($text);
    }

    /**
     * Set path on disk to TTF font that will be used to render text to image,
     * or 'null' to use a default.
     *
     * ImageMagick will also accept a font name, but this will not port as well
     * between systems.
     *
     * @param string $font
     *            Font name or a filename
     */
    public function setFont($font)
    {
        $this->font = $font;
    }

    /**
     * Numeric font size for rendering text to image
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;
    }
}
