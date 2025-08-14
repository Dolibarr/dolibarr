<?php

namespace Mike42\GfxPhp\Codec;

use Exception;
use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\Codec\Common\DataBlobInputStream;
use Mike42\GfxPhp\Codec\ImageDecoder;
use Mike42\GfxPhp\Codec\ImageEncoder;
use Mike42\GfxPhp\RasterImage;

class WbmpCodec implements ImageDecoder, ImageEncoder
{
    protected static $instance = null;

    public function identify(string $blob): string
    {
        $wbmpMagic = substr($blob, 0, 2);
        if ($wbmpMagic == "\x00\x00") {
            // Wireless Application Protocol Bitmap
            return "wbmp";
        }
        return "";
    }

    public function decode(string $blob): RasterImage
    {
        $data = DataBlobInputStream::fromBlob($blob);
        $header = $data -> read(2);
        if ($header != "\x00\x00") {
            throw new Exception("Not a WBMP file");
        }
        $width = $this -> readInt($data);
        $height = $this -> readInt($data);
        $bytesPerRow = intdiv($width + 7, 8);
        $expectedBytes = $bytesPerRow * $height;
        $binaryData = $data -> read($expectedBytes);
        $dataUnpacked = unpack("C*", $binaryData);
        $dataValues = array_values($dataUnpacked);
        // 1 for white, 0 for black (opposite)
        $image = BlackAndWhiteRasterImage::create($width, $height, $dataValues);
        $image -> invert();
        return $image;
    }

    public function readInt(DataBlobInputStream $data) : int
    {
        $i = 0;
        $ret = 0;
        do {
            $byte = ord($data -> read(1));
            $ret = ($ret << 7) | ($byte & 0x7F);
            $continuation = $byte >> 7 == 1;
            $i++;
        } while ($continuation && $i < 4); // Limit to 4 bytes to avoid overflow.
        if ($continuation) {
            throw new Exception("WBMP image size too large, file may be corrupt");
        }
        return $ret;
    }

    public function writeInt(int $val) : string
    {
        $i = 0;
        $ret = chr($val & 0x7F);
        $val >>= 7;
        while ($val > 0 && $i < 3) {
            $byteVal = ($val & 0x7F) | 0x80;
            $ret = chr($byteVal) . $ret;
            $val >>= 7;
            $i++;
        }
        if ($val > 0) {
            throw new Exception("WBMP image size too large.");
        }
        return $ret;
    }

    public function getDecodeFormats(): array
    {
        return ["wbmp"];
    }

    public function encode(RasterImage $image, string $format): string
    {
        $image = $image = $image -> toBlackAndWhite();
        $image -> invert();
        return "\x00\x00" . $this -> writeInt($image -> getWidth()) . $this -> writeInt($image -> getHeight()) . $image -> getRasterData();
    }

    public function getEncodeFormats(): array
    {
        return ["wbmp"];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new WbmpCodec();
        }
        return self::$instance;
    }
}
