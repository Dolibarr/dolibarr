<?php

namespace Mike42\GfxPhp\Codec\Gif;

class GifColorTable
{
    private $palette;

    public function __construct(array $palette)
    {
        $this -> palette = $palette;
    }

    public static function fromBin(\Mike42\GfxPhp\Codec\Common\DataInputStream $in, int $globalColorTableSize)
    {
        $tableData = $in -> read($globalColorTableSize * 3);
        $paletteArr = array_values(unpack("C*", $tableData));
        $palette = array_chunk($paletteArr, 3);
        return new GifColorTable($palette);
    }

    public function getPalette(): array
    {
        return $this->palette;
    }
}
