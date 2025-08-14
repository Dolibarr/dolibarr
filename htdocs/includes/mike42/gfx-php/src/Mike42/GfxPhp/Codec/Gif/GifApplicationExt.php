<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifApplicationExt
{
    private $appIdentifer;
    private $appAuthCode;
    private $data;

    public function __construct(string $appIdentifer, string $appAuthCode, array $data)
    {
        $this->appIdentifer = $appIdentifer;
        $this->appAuthCode = $appAuthCode;
        $this->data = $data;
    }

    public function getAppIdentifer(): string
    {
        return $this->appIdentifer;
    }

    public function getAppAuthCode(): string
    {
        return $this->appAuthCode;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function fromBin(DataInputStream $in) : GifApplicationExt
    {
        $extIntroducer = $in->read(1);
        $extLabel = $in->read(1);
        if ($extIntroducer != GifData::GIF_EXTENSION || $extLabel != GifData::GIF_EXTENSION_APPLICATION) {
            throw new \Exception("Not a GIF application extension block");
        }
        $lenData = $in->read(1);
        $len = unpack("C", $lenData)[1];
        if ($len != 11) {
            throw new \Exception("Incorrect size on application extension block");
        }
        $appIdentifier = $in->read(8);
        $appAuthCode = $in->read(3);
        $data = GifData::readDataSubBlocks($in);
        return new GifApplicationExt($appIdentifier, $appAuthCode, $data);
    }
}
