<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifLogicalScreen
{

    private $logicalScreenDescriptor;
    private $globalColorTable;

    public function __construct(GifLogicalScreenDescriptor $logicalScreenDescriptor, GifColorTable $globalColorTable = null)
    {
        $this->logicalScreenDescriptor = $logicalScreenDescriptor;
        $this->globalColorTable = $globalColorTable;
    }

    public function getLogicalScreenDescriptor(): GifLogicalScreenDescriptor
    {
        return $this->logicalScreenDescriptor;
    }

    public function getGlobalColorTable(): GifColorTable
    {
        return $this->globalColorTable;
    }

    public static function fromBin(DataInputStream $data): GifLogicalScreen
    {
        $logicalScreenDescriptor = GifLogicalScreenDescriptor::fromBin($data);
        $globalColorTable = null;
        if ($logicalScreenDescriptor->hasGlobalColorTable()) {
            $globalColorTableSize = 1 << ($logicalScreenDescriptor->getGlobalColorTableSize() + 1);
            $globalColorTable = GifColorTable::fromBin($data, $globalColorTableSize);
        }
        return new GifLogicalScreen($logicalScreenDescriptor, $globalColorTable);
    }
}
