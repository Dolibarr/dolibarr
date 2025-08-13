<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifTableBasedImage
{
    private $imageDescriptor;
    private $lzqMinSize;
    private $dataSubBlocks;
    private $localColorTable;

    public function getImageDescriptor(): GifImageDescriptor
    {
        return $this->imageDescriptor;
    }

    public function getLzqMinSize(): int
    {
        return $this->lzqMinSize;
    }

    public function getDataSubBlocks(): array
    {
        return $this->dataSubBlocks;
    }

    public function getLocalColorTable()
    {
        return $this->localColorTable;
    }

    public function __construct(GifImageDescriptor $imageDescriptor, int $lzqMinSize, array $dataSubBlocks, GifColorTable $localColorTable = null)
    {

        $this->imageDescriptor = $imageDescriptor;
        $this->lzqMinSize = $lzqMinSize;
        $this->dataSubBlocks = $dataSubBlocks;
        $this->localColorTable = $localColorTable;
    }

    public static function fromBin(DataInputStream $in) : GifTableBasedImage
    {
        $imageDescriptor = GifImageDescriptor::fromBin($in);
        $localColorTable = null;
        if ($imageDescriptor->hasLocalColorTable()) {
            $localColorTableSize = 1 << ($imageDescriptor->getLocalColorTableSize() + 1);
            $localColorTable = GifColorTable::fromBin($in, $localColorTableSize);
        }
        $lzwMinSizeData = $in -> read(1);
        $lzwMinSize = unpack("C", $lzwMinSizeData)[1];
        $dataSubBlocks = GifData::readDataSubBlocks($in);
        return new GifTableBasedImage($imageDescriptor, $lzwMinSize, $dataSubBlocks, $localColorTable);
    }
}
