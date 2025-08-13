<?php
namespace Mike42\GfxPhp\Codec\Gif;

use Mike42\GfxPhp\Codec\Common\DataInputStream;
use Mike42\GfxPhp\IndexedRasterImage;
use Mike42\GfxPhp\Util\LzwCompression;

class GifDataStream
{
    const GIF87_SIGNATURE="GIF87a";
    const GIF89_SIGNATURE="GIF89a";
    const GIF_TRAILER="\x3B";

    private $header;
    private $logicalScreen;
    private $data;
    private $trailer;

    private function __construct(string $header, GifLogicalScreen $logicalScreen, array $data, string $trailer)
    {
        $this -> header = $header;
        $this -> logicalScreen = $logicalScreen;
        $this -> data = $data;
        $this -> trailer = $trailer;
    }

    public static function fromBinary(DataInputStream $data) : GifDataStream
    {
        // Check header
        $header = $data -> read(6);
        if ($header != GifDataStream::GIF87_SIGNATURE && $header != GifDataStream::GIF89_SIGNATURE) {
            throw new \Exception("Bad GIF header");
        }
        $logicalScreen = GifLogicalScreen::fromBin($data);
        $imageData = [];
        while ($data -> peek(1) != GifDataStream::GIF_TRAILER) {
            $imageData[] = GifData::fromBin($data);
        }
        $trailer = $data -> read(1); // Discard trailer byte
        if (!$data -> isEof()) {
            throw new \Exception("The GIF file is corrupt; data found after the GIF trailer");
        }
        return new GifDataStream($header, $logicalScreen, $imageData, $trailer);
    }

    public function toRasterImage(int $imageIndex = 0) : IndexedRasterImage
    {
        // Extract an image from the GIF
        $currentIndex = 0;
        foreach ($this -> data as $dataBlock) {
            if ($dataBlock -> getGraphicsBlock() !== null && $dataBlock -> getGraphicsBlock() -> getTableBasedImage() != null) {
                // This is a raster image
                if ($currentIndex == $imageIndex) {
                    return GifDataStream::extractImage($this -> logicalScreen, $dataBlock -> getGraphicsBlock() -> getTableBasedImage(), $dataBlock -> getGraphicsBlock() -> getGraphicControlExt());
                }
                $currentIndex++;
            }
        }
        throw new \Exception("Could not find image #$imageIndex in GIF file");
    }

    private static function extractImage(GifLogicalScreen $logicalScreen, GifTableBasedImage $tableBasedImage, GifGraphicControlExt $graphicControlExt = null) : IndexedRasterImage
    {

        $width = $tableBasedImage->getImageDescriptor()->getWidth();
        $height = $tableBasedImage->getImageDescriptor()->getHeight();
        $colorTable = $tableBasedImage->getLocalColorTable() == null ? $logicalScreen->getGlobalColorTable() : $tableBasedImage->getLocalColorTable();
        if ($colorTable == null) {
            throw new \Exception("GIF contains no color table for the image. Loading this type of file is not supported.");
        }
        if ($width == 0 || $height == 0) {
            throw new \Exception("GIF contains no pixels. Loading this type of file is not supported.");
        }
        // De-compress the actual image data
        $compressedData = join($tableBasedImage->getDataSubBlocks());
        $decompressedData = LzwCompression::decompress($compressedData, $tableBasedImage->getLzqMinSize());
        // Discard extra bytes here, since IndexedRasterImage will reject it
        $actualLen = strlen($decompressedData);
        $expectedLen = $width * $height;
        if ($actualLen > $expectedLen) {
            $decompressedData = substr($decompressedData, 0, $expectedLen);
        } else if ($actualLen < $expectedLen) {
            throw new \Exception("GIF corrupt or truncated. Expexted $expectedLen pixels for $width x $height image, but only $actualLen pixels were encoded.");
        }
        if ($tableBasedImage -> getImageDescriptor() -> isInterlaced()) {
            $decompressedData = self::deinterlace($width, $decompressedData);
        }
        // Array of ints for IndexedRasterImage
        $dataArr = array_values(unpack("C*", $decompressedData));
        $image = IndexedRasterImage::create($width, $height, $dataArr, $colorTable -> getPalette());
        // Lastly, transparency handling
        if ($graphicControlExt != null && $graphicControlExt -> hasTransparentColor()) {
            $image -> setTransparentColor($graphicControlExt -> getTransparentColorIndex());
        }
        return $image;
    }

    private static function deinterlace(int $width, string $data) : string
    {
        // Four-pass GIF de-interlace. Reads input in order.
        $old = str_split($data, $width);
        $height = count($old);
        $new = array_fill(0, $height, "");
        $j = 0;
        for ($i = 0; $i < $height; $i += 8) {
            $new[$i] = $old[$j];
            $j++;
        }
        for ($i = 4; $i < $height; $i += 8) {
            $new[$i] = $old[$j];
            $j++;
        }
        for ($i = 2; $i < $height; $i += 4) {
            $new[$i] = $old[$j];
            $j++;
        }
        for ($i = 1; $i < $height; $i += 2) {
            $new[$i] = $old[$j];
            $j++;
        }
        return join($new);
    }
}
