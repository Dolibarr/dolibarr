<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifUnknownExt
{
    private $label;
    private $header;
    private $data;

    public function __construct(string $label, string $header, array $data)
    {

        $this->label = $label;
        $this->header = $header;
        $this->data = $data;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function fromBin(DataInputStream $in)
    {
        $introducer = $in->read(1);
        if ($introducer != GifData::GIF_EXTENSION) {
            throw new \Exception("Not a GIF extension block");
        }
        $label = $in->read(1);
        $lenData = $in->read(1);
        $len = unpack("C", $lenData)[1];
        $header = $in -> read($len);
        $data = GifData::readDataSubBlocks($in);
        return new GifUnknownExt($label, $header, $data);
    }
}
