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
use InvalidArgumentException;
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\ImagickEscposImage;
use Mike42\Escpos\NativeEscposImage;

/**
 * This class deals with images in raster formats, and converts them into formats
 * which are suitable for use on thermal receipt printers. Currently, only PNG
 * images (in) and ESC/POS raster format (out) are implemeted.
 *
 * Input formats:
 *  - Currently, only PNG is supported.
 *  - Other easily read raster formats (jpg, gif) will be added at a later date, as this is not complex.
 *  - The BMP format can be directly read by some commands, but this has not yet been implemented.
 *
 * Output formats:
 *  - Currently, only ESC/POS raster format is supported
 *  - ESC/POS 'column format' support is partially implemented, but is not yet used by Escpos.php library.
 *  - Output as multiple rows of column format image is not yet in the works.
 *
 * Libraries:
 *  - Currently, php-gd is used to read the input. Support for imagemagick where gd is not installed is
 *    also not complex to add, and is a likely future feature.
 *  - Support for native use of the BMP format is a goal, for maximum compatibility with target environments.
 */
abstract class EscposImage
{
    /**
     * @var int $imgHeight
     *  height of the image.
     */
    protected $imgHeight = 0;
    
    /**
     * @var int $imgWidth
     *  width of the image
     */
    protected $imgWidth = 0;
    
    /**
     * @var string $imgData
     *  Image data in rows: 1 for black, 0 for white.
     */
    private $imgData = null;
    
    /**
     * @var array:string $imgColumnData
     *  Cached column-format data to avoid re-computation
     */
    private $imgColumnData = [];
    
    /**
     * @var string $imgRasterData
     *  Cached raster format data to avoid re-computation
     */
    private $imgRasterData = null;
    
    /**
     * @var string $filename
     *  Filename of image on disk - null if not loaded from disk.
     */
    private $filename = null;
    
    /**
     * @var boolean $allowOptimisations
     *  True to allow faster library-specific rendering shortcuts, false to always just use
     *  image libraries to read pixels (more reproducible between systems).
     */
    private $allowOptimisations = true;
    
    /**
     * Construct a new EscposImage.
     *
     * @param string $filename Path to image filename, or null to create an empty image.
     * @param boolean $allowOptimisations True (default) to use any library-specific tricks
     *  to speed up rendering, false to force the image to be read in pixel-by-pixel,
     *  which is easier to unit test and more reproducible between systems, but slower.
     */
    public function __construct($filename = null, $allowOptimisations = true)
    {
        $this -> filename = $filename;
        $this -> allowOptimisations = $allowOptimisations;
    }

    /**
     * @return int height of the image in pixels
     */
    public function getHeight()
    {
        return $this -> imgHeight;
    }
    
    /**
     * @return int Number of bytes to represent a row of this image
     */
    public function getHeightBytes()
    {
        return (int)(($this -> imgHeight + 7) / 8);
    }
    
    /**
     * @return int Width of the image
     */
    public function getWidth()
    {
        return $this -> imgWidth;
    }
    
    /**
     * @return int Number of bytes to represent a row of this image
     */
    public function getWidthBytes()
    {
        return (int)(($this -> imgWidth + 7) / 8);
    }

    /**
     * Output the image in raster (row) format. This can result in padding on the
     * right of the image, if its width is not divisible by 8.
     *
     * @throws Exception Where the generated data is unsuitable for the printer
     *  (indicates a bug or oversized image).
     * @return string The image in raster format.
     */
    public function toRasterFormat()
    {
        // Just wraps implementations for caching & lazy loading
        if ($this -> imgRasterData !== null) {
            /* Return cached value */
            return $this -> imgRasterData;
        }
        if ($this -> allowOptimisations) {
            /* Use optimised code if allowed */
            $this -> imgRasterData = $this -> getRasterFormatFromFile($this -> filename);
        }
        if ($this -> imgRasterData === null) {
            /* Load in full image and render the slow way if no faster implementation
             is available, or if we've been asked not to use it */
            if ($this -> imgData === null) {
                $this -> loadImageData($this -> filename);
            }
            $this -> imgRasterData = $this -> getRasterFormat();
        }
        return $this -> imgRasterData;
    }
    
    /**
     * Output the image in column format.
     *
     * @param boolean $doubleDensity True for double density (24px) lines, false for single-density (8px) lines.
     * @return string[] an array, one item per line of output. All lines will be of equal size.
     */
    public function toColumnFormat($doubleDensity = false)
    {
        $densityIdx = $doubleDensity ? 1 : 0;
        // Just wraps implementations for caching and lazy loading
        if (isset($this -> imgColumnData[$densityIdx])) {
            /* Return cached value */
            return $this -> imgColumnData[$densityIdx];
        }
        $this -> imgColumnData[$densityIdx] = null;
        if ($this -> allowOptimisations) {
            /* Use optimised code if allowed */
            $data = $this -> getColumnFormatFromFile($this -> filename, $doubleDensity);
            $this -> imgColumnData[$densityIdx] = $data;
        }
        if ($this -> imgColumnData[$densityIdx] === null) {
            /* Load in full image and render the slow way if no faster implementation
             is available, or if we've been asked not to use it */
            if ($this -> imgData === null) {
                $this -> loadImageData($this -> filename);
            }
            $this -> imgColumnData[$densityIdx] = $this -> getColumnFormat($doubleDensity);
        }
        return $this -> imgColumnData[$densityIdx];
    }

    /**
     * Load an image from disk. This default implementation always gives a zero-sized image.
     *
     * @param string|null $filename Filename to load from.
     */
    protected function loadImageData(string $filename = null)
    {
        // Load image in to string of 1's and 0's, also set width & height
        $this -> setImgWidth(0);
        $this -> setImgHeight(0);
        $this -> setImgData("");
    }
    
    /**
     * Set image data.
     *
     * @param string $data Image data to use, string of 1's (black) and 0's (white) in row-major order.
     */
    protected function setImgData($data)
    {
        $this -> imgData = $data;
    }
    
    /**
     * Set image width.
     *
     * @param int $width width of the image
     */
    protected function setImgWidth($width)
    {
        $this -> imgWidth = $width;
    }
    
    /**
     * Set image height.
     *
     * @param int $height height of the image.
     */
    protected function setImgHeight($height)
    {
        $this -> imgHeight = $height;
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
        // No optimised implementation to provide
        return null;
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
        // No optimised implementation to provide
        return null;
    }
    
    /**
     * Get column fromat from loaded image pixels, line by line.
     *
     * @throws Exception
     *  Where wrong number of bytes has been generated.
     * @return string
     *  Raster format data
     */
    private function getRasterFormat()
    {
        /* Loop through and convert format */
        $widthPixels = $this -> getWidth();
        $heightPixels = $this -> getHeight();
        $widthBytes = $this -> getWidthBytes();
        $heightBytes = $this -> getHeightBytes();
        $x = $y = $bit = $byte = $byteVal = 0;
        $data = str_repeat("\0", $widthBytes * $heightPixels);
        if (strlen($data) == 0) {
            return $data;
        }
        do {
            $byteVal |= (int)$this -> imgData[$y * $widthPixels + $x] << (7 - $bit);
            $x++;
            $bit++;
            if ($x >= $widthPixels) {
                $x = 0;
                $y++;
                $bit = 8;
                if ($y >= $heightPixels) {
                    $data[$byte] = chr($byteVal);
                    break;
                }
            }
            if ($bit >= 8) {
                $data[$byte] = chr($byteVal);
                $byteVal = 0;
                $bit = 0;
                $byte++;
            }
        } while (true);
        if (strlen($data) != ($this -> getWidthBytes() * $this -> getHeight())) {
            throw new Exception("Bug in " . __FUNCTION__ . ", wrong number of bytes.");
        }
        return $data;
    }
    
    /**
     * Get column fromat from loaded image pixels, line by line.
     *
     * @param boolean $highDensity
     *  True for high density output (24px lines), false for regular density (8px)
     * @return string[]
     *  Array of column format data, one item per row.
     */
    private function getColumnFormat(bool $highDensity)
    {
        $out = [];
        $i = 0;
        while (($line = $this -> getColumnFormatLine($i, $highDensity)) !== null) {
            $out[] = $line;
            $i++;
        }
        return $out;
    }
    
    /**
     * Output image in column format. Must be called once for each line of output.
     *
     * @param int $lineNo
     *  Line number to retrieve
     * @param bool $highDensity
     *  True for high density output (24px lines), false for regular density (8px)
     * @throws Exception
     *  Where wrong number of bytes has been generated.
     * @return NULL|string
     *  Column format data, or null if there is no more data (when iterating)
     */
    private function getColumnFormatLine(int $lineNo, bool $highDensity)
    {
        // Currently double density in both directions, very experimental
        $widthPixels = $this -> getWidth();
        $heightPixels = $this -> getHeight();
        $widthBytes = $this -> getWidthBytes();
        $heightBytes = $this -> getHeightBytes();
        $lineHeight = $highDensity ? 3 : 1; // Vertical density. 1 or 3 (for 8 and 24 pixel lines)
        // Initialise to zero
        $x = $y = $bit = $byte = $byteVal = 0;
        $data = str_repeat("\x00", $widthPixels * $lineHeight);
        $yStart = $lineHeight * 8 * $lineNo;
        if ($yStart >= $heightPixels) {
            return null;
        }
        if (strlen($data) == 0) {
            return $data;
        }
        do {
            $yReal = $y + $yStart;
            if ($yReal < $heightPixels) {
                $byteVal |= (int)$this -> imgData[$yReal * $widthPixels + $x] << (7 - $bit);
            }
            $y++;
            $bit++;
            if ($y >= $lineHeight * 8) {
                $y = 0;
                $x++;
                $bit = 8;
                if ($x >= $widthPixels) {
                    $data[$byte] = chr($byteVal);
                    break;
                }
            }
            if ($bit >= 8) {
                $data[$byte] = chr($byteVal);
                $byteVal = 0;
                $bit = 0;
                $byte++;
            }
        } while (true);
        if (strlen($data) != $widthPixels * $lineHeight) {
            throw new Exception("Bug in " . __FUNCTION__ . ", wrong number of bytes.");
        }
        return $data;
    }
    
    /**
     * @return boolean True if GD is loaded, false otherwise
     */
    public static function isGdLoaded()
    {
        return extension_loaded('gd');
    }
    
    /**
     * @return boolean True if Imagick is loaded, false otherwise
     */
    public static function isImagickLoaded()
    {
        return extension_loaded('imagick');
    }
    

    /**
     * This is a convinience method to load an image from file, auto-selecting
     * an EscposImage implementation which uses an available library.
     *
     * The sub-classes can be constructed directly if you know that you will
     * have Imagick or GD on the print server.
     *
     * @param string $filename
     *  File to load from
     * @param bool $allowOptimisations
     *  True to allow the fastest rendering shortcuts, false to force the library
     *  to read the image into an internal raster format and use PHP to render
     *  the image (slower but less fragile).
     * @param array $preferred
     *  Order to try to load libraries in- escpos-php supports pluggable image
     *  libraries. Items can be 'imagick', 'gd', 'native'.
     * @throws Exception
     *  Where no suitable library could be found for the type of file being loaded.
     * @return EscposImage
     *
     */
    public static function load(
        string $filename,
        bool $allowOptimisations = true,
        array $preferred = ['imagick', 'gd', 'native']
    ) {
        /* Fail early if file is not readble */
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new Exception("File '$filename' does not exist, or is not readable.");
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        /* Choose the first implementation which can handle this format */
        foreach ($preferred as $implementation) {
            if ($implementation === 'imagick') {
                if (!self::isImagickLoaded()) {
                    // Skip option if Imagick is not loaded
                    continue;
                }
                return new ImagickEscposImage($filename, $allowOptimisations);
            } elseif ($implementation === 'gd') {
                if (!self::isGdLoaded()) {
                    // Skip option if GD not loaded
                    continue;
                }
                return new GdEscposImage($filename, $allowOptimisations);
            } elseif ($implementation === 'native') {
                if (!in_array($ext, ['bmp', 'gif', 'pbm', 'png', 'ppm', 'pgm', 'wbmp'])) {
                    // Pure PHP may also be fastest way to generate raster output from wbmp and pbm formats.
                    continue;
                }
                return new NativeEscposImage($filename, $allowOptimisations);
            } else {
                // Something else on the 'preferred' list.
                throw new InvalidArgumentException("'$implementation' is not a known EscposImage implementation");
            }
        }
        throw new InvalidArgumentException("No suitable EscposImage implementation found for '$filename'.");
    }
}
