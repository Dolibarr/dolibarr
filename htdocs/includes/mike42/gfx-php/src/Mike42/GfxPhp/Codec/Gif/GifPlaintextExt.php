<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifPlaintextExt
{

    private $header;

    public function getHeader(): string
    {
        return $this->header;
    }

    public function getData(): array
    {
        return $this->data;
    }
    private $data;

    public function __construct(string $header, array $data)
    {
        $this -> header = $header;
        $this -> data = $data;
    }

    public static function fromBin(DataInputStream $in) : GifPlaintextExt
    {
        $introducer = $in->read(1);
        $label = $in->read(1);
        if ($introducer != GifData::GIF_EXTENSION || $label != GifData::GIF_EXTENSION_PLAINTEXT) {
            throw new \Exception("Not a GIF plaintext block");
        }
        $lenData = $in->read(1);
        $len = unpack("C", $lenData)[1];
        if ($len != 12) {
            throw new \Exception("Incorrect size on plain text block");
        }
        // these 12 bytes have meaning, but we are more interested in correctly skipping past this info rather than parsing it, since the feature is quite obscure.
        $header = $in -> read($len);
        $data = GifData::readDataSubBlocks($in);
        return new GifPlaintextExt($header, $data);
    }
}
