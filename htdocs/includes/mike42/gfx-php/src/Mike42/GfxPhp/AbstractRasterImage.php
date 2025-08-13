<?php

namespace Mike42\GfxPhp;

use Mike42\GfxPhp\Codec\ImageCodec;

abstract class AbstractRasterImage implements RasterImage
{
    /**
     * Produce a rectangle with the given properties.
     *
     * @param int $fill
     */
    public function rect($startX, $startY, $width, $height, $filled = false, $outline = 1, $fill = 1)
    {
        $this -> horizontalLine($startY, $startX, $startX + $width - 1, $outline);
        $this -> horizontalLine($startY + $height - 1, $startX, $startX + $width - 1, $outline);
        $this -> verticalLine($startX, $startY, $startY + $height - 1, $outline);
        $this -> verticalLine($startX + $width - 1, $startY, $startY + $height - 1, $outline);
        if ($filled) {
            // Fill center of the rectangle
            for ($y = $startY + 1; $y < $startY + $height - 1; $y++) {
                for ($x = $startX + 1; $x < $startX + $width - 1; $x++) {
                    $this -> setPixel($x, $y, $fill);
                }
            }
        }
    }
 
    protected function horizontalLine($y, $startX, $endX, $outline)
    {
        for ($x = $startX; $x <= $endX; $x++) {
            $this -> setPixel($x, $y, $outline);
        }
    }
    
    protected function verticalLine($x, $startY, $endY, $outline)
    {
        for ($y = $startY; $y <= $endY; $y++) {
            $this -> setPixel($x, $y, $outline);
        }
    }
    
    public function write(string $filename)
    {
        // Use file extension to decide output codec
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === null) {
            throw new \Exception("Cannot write '$filename': No file extension.");
        }
        $encoder = ImageCodec::getInstance() -> getEncoderForFormat($ext);
        $blob = $encoder -> encode($this, $ext);
        file_put_contents($filename, $blob);
    }

    protected function createCanvas(int $width, int $height) : RasterImage
    {
        return $this::create($width, $height);
    }

    public function scale(int $width, int $height) : RasterImage
    {
        $img = $this -> createCanvas($width, $height);
        $thisWidth = $this -> getWidth();
        $thisHeight = $this -> getHeight();
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $srcX = intdiv($x * $thisWidth, $width);
                $srcY = intdiv($y * $thisHeight, $height);
                $srcColor = $this -> getPixel($srcX, $srcY);
                $destColor = $this -> mapColor($srcColor, $img);
                $img -> setPixel($x, $y, $destColor);
            }
        }
        return $img;
    }

    public function subImage(int $startX, int $startY, int $width, int $height) : RasterImage
    {
        $ret = $this::create($width, $height);
        $ret -> compose($this, $startX, $startY, 0, 0, $width, $height);
        return $ret;
    }

    public function compose(RasterImage $source, int $startX, int $startY, int $destStartX, int $destStartY, int $width, int $height)
    {
        for ($y = 0; $y < $height; $y++) {
            $srcY = $y + $startY;
            $destY = $y + $destStartY;
            for ($x = 0; $x < $width; $x++) {
                $srcX = $x + $startX;
                $destX = $x + $destStartX;
                $this -> setPixel($destX, $destY, $source -> getPixel($srcX, $srcY));
            }
        }
    }
}
