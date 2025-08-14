<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifGraphicControlExt
{
    private $disposalMethod;
    private $hasUserInputFlag;
    private $hasTransparentColor;
    private $delayTime;
    private $transparentColorIndex;

    public function __construct(int $disposalMethod, bool $hasUserInputFlag, bool $hasTransparentColor, int $delayTime, int $transparentColorIndex)
    {
        $this->disposalMethod = $disposalMethod;
        $this->hasUserInputFlag = $hasUserInputFlag;
        $this->hasTransparentColor = $hasTransparentColor;
        $this->delayTime = $delayTime;
        $this->transparentColorIndex = $transparentColorIndex;
    }

    public function getDisposalMethod(): int
    {
        return $this->disposalMethod;
    }

    public function hasUserInputFlag(): bool
    {
        return $this->hasUserInputFlag;
    }

    public function hasTransparentColor(): bool
    {
        return $this->hasTransparentColor;
    }

    public function getDelayTime(): int
    {
        return $this->delayTime;
    }

    public function getTransparentColorIndex(): int
    {
        return $this->transparentColorIndex;
    }

    public static function fromBin(DataInputStream $in) : GifGraphicControlExt
    {
        $extIntroducer = $in->read(1);
        $extLabel = $in->read(1);
        if ($extIntroducer != GifData::GIF_EXTENSION || $extLabel != GifData::GIF_EXTENSION_GRAPHIC_CONTROL) {
            throw new \Exception("Not a GIF application extension block");
        }
        $lenData = $in->read(1);
        $len = unpack("C", $lenData)[1];
        if ($len != 4) {
            throw new \Exception("Incorrect size on application extension block");
        }
        $packedFieldData = $in -> read(1);
        $packedFields = unpack("C", $packedFieldData)[1]; // Note 3 most-significant bits are reserved
        $disposalMethod = ($packedFields >> 2) & 0x07;
        $hasUserInputFlag = (($packedFields >> 1) & 0x01) == 1;
        $hasTransparentColor = ($packedFields & 0x01) == 1;
        $delayTimeData = $in -> read(2);
        $delayTime = unpack("v", $delayTimeData)[1];
        $transparentColorIndexData = $in -> read(1);
        $transparentColorIndex = unpack("C", $transparentColorIndexData)[1];
        $end = $in -> read(1);
        if ($end != "\x00") {
            throw new \Exception("GIF graphic control block not correctly terminated");
        }
        return new GifGraphicControlExt($disposalMethod, $hasUserInputFlag, $hasTransparentColor, $delayTime, $transparentColorIndex);
    }
}
