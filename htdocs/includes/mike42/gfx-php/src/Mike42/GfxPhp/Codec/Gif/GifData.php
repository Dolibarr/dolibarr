<?php


namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;

class GifData
{
    const GIF_IMAGE_SEPARATOR="\x2C";
    const GIF_EXTENSION="\x21";
    const GIF_EXTENSION_GRAPHIC_CONTROL="\xF9";
    const GIF_EXTENSION_PLAINTEXT="\x01";
    const GIF_EXTENSION_APPLICATION="\xFF";
    const GIF_EXTENSION_COMMENT="\xFE";

    private $graphicsBlock;
    private $specialPurposeBlock;
    private $unrecognisedBlock;

    public function __construct(GifGraphicsBlock $graphicsBlock = null, GifSpecialPurposeBlock $specialPurposeBlock = null, GifUnknownExt $unrecognisedBlock = null)
    {
        $this->graphicsBlock = $graphicsBlock;
        $this->specialPurposeBlock = $specialPurposeBlock;
        $this->unrecognisedBlock = $unrecognisedBlock;
    }

    public function getUnrecognisedBlock()
    {
        return $this->unrecognisedBlock;
    }

    public function getSpecialPurposeBlock()
    {
        return $this->specialPurposeBlock;
    }

    public function getGraphicsBlock()
    {
        return $this->graphicsBlock;
    }

    public static function fromBin(DataInputStream $in) : GifData
    {
        $peek = $in -> peek(2);
        $blockId = $peek[0];
        $extensionId = $peek[1];
        if ($blockId == GifData::GIF_EXTENSION) {
            // Special-purpose blocks
            if ($extensionId == GifData::GIF_EXTENSION_APPLICATION) {
                $applicationExt = GifApplicationExt::fromBin($in);
                $specialPurposeBlock = new GifSpecialPurposeBlock($applicationExt, null);
                return new GifData(null, $specialPurposeBlock, null);
            } else if ($extensionId == GifData::GIF_EXTENSION_COMMENT) {
                $commentExt = GifCommentExt::fromBin($in);
                $specialPurposeBlock = new GifSpecialPurposeBlock(null, $commentExt);
                return new GifData(null, $specialPurposeBlock, null);
            }
        }
        // Unknown extension blocks
        if ($blockId == GifData::GIF_EXTENSION && $extensionId != GifData::GIF_EXTENSION_GRAPHIC_CONTROL &&  $extensionId != GifData::GIF_EXTENSION_PLAINTEXT) {
            $unrecognisedBlock = GifUnknownExt::fromBin($in);
            return new GifData(null, null, $unrecognisedBlock);
        }
        $graphicsBlock = GifGraphicsBlock::fromBin($in);
        return new GifData($graphicsBlock, null, null);
    }

    public static function readDataSubBlocks(DataInputStream $in) : array
    {
        $blocks = [];
        while ($in -> peek(1) != "\x00") {
            $blockSizeData = $in -> read(1);
            $blockSize = unpack("C", $blockSizeData)[1];
            $blocks[] = $in -> read($blockSize);
        }
        $in -> read(1); // Discard terminating byte
        return $blocks;
    }
}
