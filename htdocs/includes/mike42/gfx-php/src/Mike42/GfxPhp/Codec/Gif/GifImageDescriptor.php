<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifImageDescriptor
{

    private $left;
    private $top;
    private $width;
    private $height;
    private $hasLocalColorTable;
    private $isInterlaced;
    private $hasSortedLocalColorTable;
    private $localColorTableSize;

    public function __construct(int $left, int $top, int $width, int $height, bool $hasLocalColorTable, bool $isInterlaced, bool $hasSortedLocalColorTable, int $localColorTableSize)
    {

        $this->left = $left;
        $this->top = $top;
        $this->width = $width;
        $this->height = $height;
        $this->hasLocalColorTable = $hasLocalColorTable;
        $this->isInterlaced = $isInterlaced;
        $this->hasSortedLocalColorTable = $hasSortedLocalColorTable;
        $this->localColorTableSize = $localColorTableSize;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function hasLocalColorTable(): bool
    {
        return $this->hasLocalColorTable;
    }

    public function isInterlaced(): bool
    {
        return $this->isInterlaced;
    }

    public function hasSortedLocalColorTable(): bool
    {
        return $this->hasSortedLocalColorTable;
    }

    public function getLocalColorTableSize(): int
    {
        return $this->localColorTableSize;
    }

    public static function fromBin(DataInputStream $in): GifImageDescriptor
    {
        $imageSep = $in->read(1);
        if ($imageSep != GifData::GIF_IMAGE_SEPARATOR) {
            throw new \Exception("Not a GIF image descriptor block");
        }
        $sizeData = $in -> read(8);
        $size = unpack("v4", $sizeData);
        $left = $size[1];
        $top = $size[2];
        $width = $size[3];
        $height = $size[4];
        $packedFieldData = $in->read(1);
        $packedFields = unpack("C", $packedFieldData)[1];
        $hasLocalColorTable = ($packedFields >> 7) == 1;
        $isInterlaced = (($packedFields >> 6) & 0x01) == 1;
        $hasSortedLocalColorTable = (($packedFields >> 5) & 0x01) == 1;
        // 2 bits are reserved here and not parsed
        $localColorTableSize = $packedFields & 0x07;
        return new GifImageDescriptor($left, $top, $width, $height, $hasLocalColorTable, $isInterlaced, $hasSortedLocalColorTable, $localColorTableSize);
    }
}
