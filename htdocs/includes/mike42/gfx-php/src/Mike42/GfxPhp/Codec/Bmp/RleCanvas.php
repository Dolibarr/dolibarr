<?php

namespace Mike42\GfxPhp\Codec\Bmp;

use Exception;

/**
 * 2D canvas for writing RLE-encoded BMP data into. A cursor is moved based on the commands issued, and an Exception
 * is thrown if the data goes outside the canvas boundary, or if the data continues after endOfBitmap is called.
 */
class RleCanvas
{
    private $buffer;
    private $cursorX;
    private $cursorY;
    private $width;
    private $height;
    private $complete;

    public function __construct(int $width, int $height)
    {
        $this -> cursorX = 0;
        $this -> cursorY = 0;
        $this -> width = $width;
        $this -> height = $height;
        $this -> complete = false;
        // Initialise empty space
        $tmp = [];
        for ($y = 0; $y < $height; $y++) {
            $tmp[] = array_fill(0, $width, 0);
        }
        $this->buffer = $tmp;
    }

    public function delta(int $deltaX, int $deltaY)
    {
        $this -> cursorX += $deltaX;
        $this -> cursorY += $deltaY;
    }

    public function endOfLine()
    {
        $this -> cursorY++;
        $this -> cursorX = 0;
    }

    public function endOfBitmap()
    {
        $this -> complete = true;
    }

    public function set(int $val)
    {
        // Range check when we attempt to write the pixel.
        if ($this -> cursorY < 0 || $this -> cursorY >= $this -> height) {
            throw new Exception("Bitmap compressed data exceeds image boundary; file is not valid. Y-overflow");
        }
        if ($this -> cursorX < 0 || $this -> cursorX >= $this -> width) {
            throw new Exception("Bitmap compressed data exceeds image boundary; file is not valid. X-overflow");
        }
        if ($this -> complete) {
            throw new Exception("Bitmap compressed data continued after end-of-bitmap code was found; file is not valid.");
        }
        // Write the pixel
        $this -> buffer[$this -> cursorY][$this -> cursorX] = $val;
        $this -> cursorX++;
    }

    public function absolute(array $values)
    {
        for ($i = 0; $i < count($values); $i++) {
            $this -> set($values[$i]);
        }
    }

    public function repeat(int $val, int $times)
    {
        for ($j = 0; $j < $times; $j++) {
            $this -> set($val);
        }
    }

    public function getContents()
    {
        return $this -> buffer;
    }
}
