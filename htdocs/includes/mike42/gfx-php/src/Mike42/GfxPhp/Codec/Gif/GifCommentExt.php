<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifCommentExt
{
    private $data;

    public function getData(): array
    {
        return $this->data;
    }

    public function __construct(array $data)
    {
        $this -> data = $data;
    }

    public static function fromBin(DataInputStream $in)
    {
        $extIntroducer = $in->read(1);
        $extLabel = $in->read(1);
        if ($extIntroducer != GifData::GIF_EXTENSION || $extLabel != GifData::GIF_EXTENSION_COMMENT) {
            throw new \Exception("Not a GIF comment extension block");
        }
        $data = GifData::readDataSubBlocks($in);
        return new GifCommentExt($data);
    }
}
