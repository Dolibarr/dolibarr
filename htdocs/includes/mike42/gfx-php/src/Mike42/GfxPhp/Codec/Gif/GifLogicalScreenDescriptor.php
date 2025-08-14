<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifLogicalScreenDescriptor
{
    private $width;
    private $height;
    private $hasGlobalColorTable;
    private $colorResolution;
    private $hasSortedGlobalColorTable;
    private $globalColorTableSize;
    private $backgroundColorIndex;
    private $pixelAspectRatio;

    public function __construct(int $width, int $height, bool $hasGlobalColorTable, int $colorResolution, bool $hasSortedGlobalColorTable, int $globalColorTableSize, int $backgroundColorIndex, int $pixelAspectRatio)
    {
        $this -> width = $width;
        $this -> height = $height;
        $this -> hasGlobalColorTable = $hasGlobalColorTable;
        $this -> colorResolution = $colorResolution;
        $this -> hasSortedGlobalColorTable = $hasSortedGlobalColorTable;
        $this -> globalColorTableSize = $globalColorTableSize;
        $this -> backgroundColorIndex = $backgroundColorIndex;
        $this -> pixelAspectRatio = $pixelAspectRatio;
    }

    public static function fromBin(DataInputStream $in) : GifLogicalScreenDescriptor
    {
        $sizeData = $in -> read(4);
        $size = unpack("v2", $sizeData);
        $width = $size[1];
        $height = $size[2];
        $packedFieldData = $in -> read(1);
        $packedFields = unpack("C", $packedFieldData)[1];
        $hasGlobalColorTable = ($packedFields >> 7) == 1;
        $colorResolution = ($packedFields >> 4) & 0x0F;
        $hasSortedGlobalColorTable = (($packedFields >> 3) & 0x01) == 1;
        $globalColorTableSize = $packedFields & 0x07;
        // Everything else
        $otherFieldData = $in -> read(2);
        $otherFields = unpack("C2", $otherFieldData);
        $pixelAspectRatio = $otherFields[1];
        $backgroundColorIndex = $otherFields[2];
        return new GifLogicalScreenDescriptor($width, $height, $hasGlobalColorTable, $colorResolution, $hasSortedGlobalColorTable, $globalColorTableSize, $backgroundColorIndex, $pixelAspectRatio);
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function hasGlobalColorTable(): bool
    {
        return $this->hasGlobalColorTable;
    }

    public function getColorResolution(): int
    {
        return $this->colorResolution;
    }

    public function hasSortedGlobalColorTabled(): bool
    {
        return $this->hasSortedGlobalColorTable;
    }

    public function getGlobalColorTableSize(): int
    {
        return $this->globalColorTableSize;
    }

    public function getBackgroundColorIndex(): int
    {
        return $this->backgroundColorIndex;
    }

    public function getPixelAspectRatio(): int
    {
        return $this->pixelAspectRatio;
    }

    public function getWidth(): int
    {
        return $this->width;
    }
}
