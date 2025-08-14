<?php
namespace Mike42\GfxPhp\Codec;

use Mike42\GfxPhp\Codec\Common\DataBlobInputStream;
use Mike42\GfxPhp\Codec\Gif\GifDataStream;
use Mike42\GfxPhp\RasterImage;
use Mike42\GfxPhp\Util\LzwCompression;
use Mike42\GfxPhp\IndexedRasterImage;

class GifCodec implements ImageEncoder, ImageDecoder
{
    protected static $instance = null;

    public function encode(RasterImage $image, string $format): string
    {
        if (!($image instanceof IndexedRasterImage)) {
            // Convert if necessary
            $image = $image -> toIndexed();
        }
        $palette = $image -> getPalette();
        if ($image -> getMaxVal() > 256) {
            // Color count is too large, clone the image and drop the color depth
            $image = $image -> toIndexed();
            $image -> setMaxVal(255);
            $palette = $image -> getPalette();
        }
        // GIF signature
        $signature = pack("c6", 0x47, 0x49, 0x46, 0x38, 0x39, 0x61);
        // Header chunk
        $width = $image -> getWidth();
        $height = $image -> getHeight();
        $header = pack('v2c3', $width, $height, 0xF7, 0, 0);
        // Color table of grayscale
        $colorTable = [];
        $paletteSize = count($palette);
        for ($i = 0; $i < $paletteSize; $i++) {
            $entry = $palette[$i];
            $colorTable[] = $entry[0];
            $colorTable[] = $entry[1];
            $colorTable[] = $entry[2];
        }
        // Padding to 256 color entries
        for ($i = $paletteSize; $i < 256; $i++) {
            $colorTable[] = 0;
            $colorTable[] = 0;
            $colorTable[] = 0;
        }
       
        $gct = pack("C*", ... $colorTable);
        // Transparent color for graphic control
        $transparentColorFlag = 0x00;
        $transparentColor = 0;
        if ($image -> getTransparentColor() >= 0) {
            $transparentColor = $image -> getTransparentColor() & 0xFF;
            $transparentColorFlag = 0x01;
        }
        // Graphic control
        // TODO one of these flags does not do what you think it does.
        $gce = pack("C4vC2", 0x21, 0xF9, 0x04, 0x00 | $transparentColorFlag, 0x00, $transparentColor, 0x00);
        // Image
        $imageDescriptor = pack('Cv4C', 0x2C, 0, 0, $width, $height, 0);
        $raster = $image -> getRasterData();
        $compressedData = LzwCompression::compress($raster, 0x08);
        // Field testing the corresponding decoder, which we don't use anywhere else.
        // It's a good start for GIF decoding if we can at least read our own LZW back.
        $decompressedData = LzwCompression::decompress($compressedData, 0x08);
        if ($raster !== $decompressedData) {
            throw new \Exception("Failed to read back the generated LZW data.");
        }
         $slices = str_split($compressedData, 255);
         $imageData = chr(0x08);
        foreach ($slices as $slice) {
            $imageData .= chr(strlen($slice)) . $slice;
        }
         $imageData .= chr(0);
        $terminator = pack("C", 0x3B);
        return $signature . $header . $gct . $gce . $imageDescriptor . $imageData . $terminator;
    }

    public function getDecodeFormats(): array
    {
        return ["gif"];
    }

    public function identify(string $blob): string
    {
        if (substr($blob, 0, 6) == GifDataStream::GIF87_SIGNATURE) {
            return "gif";
        }
        if (substr($blob, 0, 6) == GifDataStream::GIF89_SIGNATURE) {
            return "gif";
        }
        return "";
    }

    public function decode(string $blob): RasterImage
    {
        $data = DataBlobInputStream::fromBlob($blob);
        $gif = GifDataStream::fromBinary($data);
        return $gif -> toRasterImage();
    }

    public function getEncodeFormats(): array
    {
        return ["gif"];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new GifCodec();
        }
        return self::$instance;
    }
}
