<?php

namespace Mike42\GfxPhp\Codec;

use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\RasterImage;
use Mike42\GfxPhp\RgbRasterImage;
use Exception;

class PnmCodec implements ImageDecoder, ImageEncoder
{
    protected static $instance = null;

    public function identify(string $blob): string
    {
        $pnmMagic = substr($blob, 0, 2);
        if ($pnmMagic == "P1" || $pnmMagic == "P4") {
            // Portable BitMap
            return "pbm";
        } else if ($pnmMagic == "P2" || $pnmMagic == "P5") {
            // Portable GrayMap
            return "pgm";
        } else if ($pnmMagic == "P3" || $pnmMagic == "P6") {
            // Portable PixMap
            return "ppm";
        }
        return "";
    }

    public function decode(string $blob): RasterImage
    {
        // Read header line
        $im_hdr_line = substr($blob, 0, 3);
        if ($im_hdr_line !== "P4\n" &&
            $im_hdr_line !== "P5\n" &&
            $im_hdr_line !== "P6\n") {
            throw new Exception("Format not supported. Expected PNM bitmap.");
        }
        $pnmMagicNumber = substr($im_hdr_line, 0, 2);
        // Skip comments
        $line_end = self::skipComments($blob, 2);
        // Read image size
        $next_line_end = strpos($blob, "\n", $line_end + 1);
        if ($next_line_end === false) {
            throw new Exception("Unexpected end of file, probably corrupt.");
        }
        $size_line = substr($blob, $line_end + 1, ($next_line_end - $line_end) - 1);
        $sizes = explode(" ", $size_line);
        if (count($sizes) != 2 || !is_numeric($sizes[0]) || !is_numeric($sizes[1])) {
            throw new Exception("Image size is bogus, file probably corrupt.");
        }
        $width = $sizes[0];
        $height = $sizes[1];
        $line_end = $next_line_end;
        // Extract data and return differently based on each magic number.
        switch ($pnmMagicNumber) {
            case "P4":
                $bytesPerRow = intdiv($width + 7, 8);
                $expectedBytes = $bytesPerRow * $height;
                $data = substr($blob, $line_end + 1);
                $actualBytes = strlen($data);
                if ($expectedBytes != $actualBytes) {
                    throw new Exception("Expected $expectedBytes data, but got $actualBytes, file probably corrupt.");
                }
                $dataUnpacked = unpack("C*", $data);
                $dataValues = array_values($dataUnpacked);
                return BlackAndWhiteRasterImage::create($width, $height, $dataValues);
            case "P5":
                // Determine color depth
                $line_end = self::skipComments($blob, $line_end);
                $next_line_end = strpos($blob, "\n", $line_end + 1);
                $maxValLine = substr($blob, $line_end + 1, ($next_line_end - $line_end) - 1);
                $maxVal = (int)$maxValLine;
                $depth = $maxVal > 255 ? 2 : 1;
                $line_end = $next_line_end;
                // Extract data
                $expectedBytes = $width * $height * $depth;
                $data = substr($blob, $line_end + 1);
                $actualBytes = strlen($data);
                if ($expectedBytes != $actualBytes) {
                    throw new Exception("Expected $expectedBytes data, but got $actualBytes, file probably corrupt.");
                }
                if ($depth == 2) {
                    $dataUnpacked = unpack("n*", $data);
                } else {
                    $dataUnpacked = unpack("C*", $data);
                }
                $dataValues = array_values($dataUnpacked);
                return GrayscaleRasterImage::create($width, $height, $dataValues, $maxVal);
            case "P6":
                $line_end = self::skipComments($blob, $line_end);
                $next_line_end = strpos($blob, "\n", $line_end + 1);
                $maxValLine = substr($blob, $line_end + 1, ($next_line_end - $line_end) - 1);
                $maxVal = (int)$maxValLine;
                $depth = $maxVal > 255 ? 2 : 1;
                $line_end = $next_line_end;
                $expectedBytes = $width * $height * $depth * 3;
                $data = substr($blob, $line_end + 1);
                $actualBytes = strlen($data);
                if ($expectedBytes != $actualBytes) {
                    throw new Exception("Expected $expectedBytes data, but got $actualBytes, file probably corrupt.");
                }
                if ($depth == 2) {
                    $dataUnpacked = unpack("n*", $data);
                } else {
                    $dataUnpacked = unpack("C*", $data);
                }
                $dataValues = array_values($dataUnpacked);
                return RgbRasterImage::create($width, $height, $dataValues, $maxVal);
        }
        // TODO handle formats in a way that lets us remove this fallthrough.
        throw new Exception("Format not supported.");
    }

    public function getDecodeFormats(): array
    {
        return ["ppm", "pgm", "pbm"];
    }

    protected static function skipComments(string $im_data, int $line_end) : int
    {
        while ($line_end !== false && substr($im_data, $line_end + 1, 1) == "#") {
            $line_end = strpos($im_data, "\n", $line_end + 1);
        }
        if ($line_end === false) {
            throw new Exception("Unexpected end of file, probably corrupt.");
        }
        return $line_end;
    }

    public function encode(RasterImage $image, string $format): string
    {
        if ($format === "pnm") {
            // PNM extension can hold PBM, PGM or PPM.
            // Auto-select based on type of image
            if ($image instanceof BlackAndWhiteRasterImage) {
                $format = "pbm";
            } else if ($image instanceof GrayscaleRasterImage) {
                $format = "pgm";
            } else {
                $format = "ppm";
            }
        }
        // Encode based on extension
        switch ($format) {
            case "pbm":
                if (!($image instanceof BlackAndWhiteRasterImage)) {
                    // Convert if necessary
                    $image = $image -> toBlackAndWhite();
                }
                $dimensions = $image -> getWidth() . " " . $image -> getHeight();
                $data = $image -> getRasterData();
                $contents = "P4\n$dimensions\n$data";
                return $contents;
            case "pgm":
                if (!($image instanceof GrayscaleRasterImage)) {
                    // Convert if necessary
                    $image = $image -> toGrayscale();
                }
                $dimensions = $image -> getWidth() . " " . $image -> getHeight();
                $maxVal = $image -> getMaxVal();
                $data = $image -> getRasterData();
                $contents = "P5\n$dimensions\n$maxVal\n$data";
                return $contents;
            case "ppm":
                if (!($image instanceof RgbRasterImage)) {
                    // Convert if necessary
                    $image = $image -> toRgb();
                }
                $dimensions = $image -> getWidth() . " " . $image -> getHeight();
                $maxVal = $image -> getMaxVal();
                $data = $image -> getRasterData();
                $contents = "P6\n$dimensions\n$maxVal\n$data";
                return $contents;
        }
        throw new Exception("Unsupported image type: $format");
    }

    public function getEncodeFormats(): array
    {
        return ["ppm", "pgm", "pbm"];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new PnmCodec();
        }
        return self::$instance;
    }
}
