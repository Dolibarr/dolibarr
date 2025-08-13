<?php

namespace Mike42\GfxPhp\Codec;

use Mike42\GfxPhp\Codec\Bmp\BmpFile;
use Mike42\GfxPhp\Codec\Common\DataBlobInputStream;
use Mike42\GfxPhp\Codec\Gif\GifDataStream;
use Mike42\GfxPhp\RasterImage;
use Mike42\GfxPhp\RgbRasterImage;

class BmpCodec implements ImageEncoder, ImageDecoder
{
    protected static $instance = null;
    const INFO_HEADER_SIZE = 40;
    const FILE_HEADER_SIZE = 14;

    public function encode(RasterImage $image, string $format): string
    {
        if (!($image instanceof RgbRasterImage)) {
            // Convert if necessary
            $image = $image -> toRgb();
        }
        $width = $image -> getWidth();
        $height = $image -> getHeight();
        $infoHeader = pack(
            "V3v2V6",
            self::INFO_HEADER_SIZE,
            $width, // Width
            $height, // Height
            1, // Planes
            24, // bpp
            0, // Compression (none)
            0, // Image size compressed
            1, // Horizontal res
            1, // Vertical res
            0, // Number of colors
            0 // Number of important colors
        );
        $colorTable = "";
        // Transform RGB ordering to BGR ordering
        $pixels = str_split($image -> getRasterData(), 3);
        array_walk($pixels, [$this, "transformRevString"]);
        $rasterData = implode("", $pixels);
        // Transform top-down unpadded lines to bottom-up padded lines
        $originalWidth = $width * 3;
        $paddingLength = (4 - ($originalWidth & 3)) & 3;
        $padding = str_repeat("\x00", $paddingLength);
        $lines = str_split($rasterData, $originalWidth);
        $lines = array_reverse($lines, false);
        // Uncompressed 24 bit BMP file
        $pixelData = implode($padding, $lines) . $padding;
        // Return bitmap & header
        $fileSize = strlen($pixelData) + strlen($colorTable) + self::INFO_HEADER_SIZE + self::FILE_HEADER_SIZE;
        $header = pack(
            "C2Vv2V",
            0x42, // 'BM' magic number
            0x4d,
            $fileSize, // file size
            0, // Reserved
            0, // Reserved
            self::INFO_HEADER_SIZE + self::FILE_HEADER_SIZE // Offset
        );
        return $header . $infoHeader . $colorTable . $pixelData;
    }

    protected function transformRevString(&$item, $key)
    {
        // Convert RGB to BGR
        $item = strrev($item);
    }
    
    public function getEncodeFormats(): array
    {
        return ["bmp", "dib"];
    }

    public function decode(string $blob): RasterImage
    {
        $data = DataBlobInputStream::fromBlob($blob);
        $bmp = BmpFile::fromBinary($data);
        return $bmp -> toRasterImage();
    }

    public function identify(string $blob): string
    {
        if (substr($blob, 0, 2) == BmpFile::BMP_SIGNATURE) {
            return "bmp";
        }
        return "";
    }

    public function getDecodeFormats(): array
    {
        return ["bmp", "dib"];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new BmpCodec();
        }
        return self::$instance;
    }
}
