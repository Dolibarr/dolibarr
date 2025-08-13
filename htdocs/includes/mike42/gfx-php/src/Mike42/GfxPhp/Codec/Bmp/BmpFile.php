<?php
namespace Mike42\GfxPhp\Codec\Bmp;

use Exception;
use Mike42\GfxPhp\Codec\Common\DataInputStream;
use Mike42\GfxPhp\Codec\Png\PngImage;
use Mike42\GfxPhp\IndexedRasterImage;
use Mike42\GfxPhp\RasterImage;
use Mike42\GfxPhp\RgbRasterImage;

class BmpFile
{
    const BMP_SIGNATURE = "BM";

    private $fileHeader;
    private $infoHeader;
    private $palette;
    private $uncompressedData;

    public function __construct(BmpFileHeader $fileHeader, BmpInfoHeader $infoHeader, array $data, array $palette)
    {
        $this -> fileHeader = $fileHeader;
        $this -> infoHeader = $infoHeader;
        $this -> uncompressedData = $data;
        $this -> palette = $palette;
    }

    public static function fromBinary(DataInputStream $data) : BmpFile
    {
        // Read two different headers
        $fileHeader = BmpFileHeader::fromBinary($data);
        $infoHeader = BmpInfoHeader::fromBinary($data);
        if ($infoHeader -> bpp != 0 &&
            $infoHeader -> bpp != 1 &&
            $infoHeader -> bpp != 2 &&
            $infoHeader -> bpp != 4 &&
            $infoHeader -> bpp != 8 &&
            $infoHeader -> bpp != 16 &&
            $infoHeader -> bpp != 24 &&
            $infoHeader -> bpp != 32) {
            throw new Exception("Bit depth " . $infoHeader -> bpp . " not valid.");
        } else if ($infoHeader -> bpp === 0) {
            // Fail early to give a clearer error: bit depth 0 is used for embedding PNG/JPEG in a bitmap
            throw new Exception("Bit depth " . $infoHeader -> bpp . " not supported.");
        }
        // See how many colors we expect. 2^n colors in table for bpp <= 8, 0 for higher color depths
        $colorCount = $infoHeader -> bpp <= 8 ? 2 **  $infoHeader -> bpp : 0;
        $colorTable = [];
        if (self::isOs21XBitmap($fileHeader, $infoHeader, $colorCount)) {
            // This type of image may only be 1, 4, 8 or 24 bit
            if ($infoHeader -> bpp != 1 &&
                $infoHeader -> bpp != 4 &&
                $infoHeader -> bpp != 8 &&
                $infoHeader -> bpp != 24) {
                throw new Exception("Bit depth " . $infoHeader->bpp . " not valid for OS/2 1.x bitmap.");
            }
            $calculatedTableSize = intdiv($fileHeader -> offset - (BmpInfoHeader::OS21XBITMAPHEADER_SIZE + BmpFileHeader::FILE_HEADER_SIZE), 3);
            if ($calculatedTableSize < $colorCount) {
                // Downsize the palette based on observed offset: only non-standard files do this.
                $colorCount = $calculatedTableSize;
            }
            // OS/2 1.x bitmaps use 3-bytes per color
            for ($i = 0; $i < $colorCount; $i++) {
                $entryData = $data->read(3);
                $color = unpack("C*", $entryData);
                $colorTable[] = [$color[3], $color[2], $color[1]];
            }
            // In the case of 1bpp or small palettes, it is possible that we are not aligned to a multiple of 4 bytes now.
        } else {
            if ($infoHeader -> colors > 0) {
                // .. override color count when specified
                $colorCount = $infoHeader -> colors;
            }
            for ($i = 0; $i < $colorCount; $i++) {
                $entryData = $data->read(4);
                $color = unpack("C*", $entryData);
                $colorTable[] = [$color[3], $color[2], $color[1]];
            }
            // May need to skip here if header shows pixel data later than we expect
            $calculatedOffset = $colorCount * 4 + $infoHeader -> headerSize + BmpFileHeader::FILE_HEADER_SIZE;
            if ($calculatedOffset > $fileHeader -> offset) {
                // Apparently we have read into the image data already, better to fail now before we run out of data later in the file
                throw new Exception("Header extends into image data. File is likely to be corrupt.");
            } else if ($calculatedOffset < $fileHeader -> offset) {
                // Skip up to the data.
                $data -> advance($fileHeader -> offset - $calculatedOffset);
            }
        }
        if ($infoHeader -> headerSize == BmpInfoHeader::OS22XBITMAPHEADER_FULL_SIZE || $infoHeader -> headerSize == BmpInfoHeader::OS22XBITMAPHEADER_MIN_SIZE) {
            // Some compression modes in OS/2 V2 bitmaps use the same numeric ID' as unrelated Windows BMP compression modes, but are not supported.
            if ($infoHeader -> compression != BmpInfoHeader::B1_RGB &&
                $infoHeader -> compression != BmpInfoHeader::B1_RLE4 &&
                $infoHeader -> compression != BmpInfoHeader::B1_RLE8) {
                throw new Exception("Compression method not implemented for OS/2 V2 bitmaps");
            }
        }
        // Determine compressed & uncompressed size
        $topDown = false;
        $height = $infoHeader -> height;
        if ($infoHeader -> height < 0 && $infoHeader -> compression == BmpInfoHeader::B1_RGB) {
            // negative heights are valid for uncompressed images, and indicate reversed row order
            $height = -$infoHeader -> height;
            $topDown = true;
        }
        if ($infoHeader -> width > 65535 || $height > 65535 || $infoHeader -> width < 0 || $height < 0) {
            // Limit height to prevent insane allocations during decompression if file is corrupt
            throw new Exception("Image size " . $infoHeader -> width . "x" . $height . " is outside the supported range.");
        }
        $rowSizeBytes = intdiv(($infoHeader -> bpp * $infoHeader -> width + 31), 32) * 4;
        $uncompressedImgSizeBytes = $rowSizeBytes * $height;
        if ($infoHeader -> compression == BmpInfoHeader::B1_RGB) {
            $compressedImgSizeBytes = $uncompressedImgSizeBytes;
        } else {
            $compressedImgSizeBytes = $infoHeader -> compressedSize;
        }
        $compressedImgData = $data -> read($compressedImgSizeBytes);
        // De-compress if necessary
        switch ($infoHeader -> compression) {
            case BmpInfoHeader::B1_RGB:
            case BmpInfoHeader::B1_BITFILEDS:
            case BmpInfoHeader::B1_ALPHABITFIELDS:
                $uncompressedImgData = $compressedImgData;
                break;
            case BmpInfoHeader::B1_RLE8:
                if ($infoHeader -> bpp !== 8) {
                    throw new Exception("RLE8 compression only valid for 8-bit images");
                }
                $decoder = new Rle8Decoder();
                $uncompressedImgData = $decoder -> decode($compressedImgData, $infoHeader -> width, $height);
                $actualSize = strlen($uncompressedImgData);
                if ($uncompressedImgSizeBytes !== $actualSize) {
                    throw new Exception("RLE8 decode failed. Expected $uncompressedImgSizeBytes bytes uncompressed, got $actualSize");
                }
                break;
            case BmpInfoHeader::B1_RLE4:
                if ($infoHeader -> bpp !== 4) {
                    throw new Exception("RLE4 compression only valid for 4-bit images");
                }
                $decoder = new Rle4Decoder();
                $uncompressedImgData = $decoder -> decode($compressedImgData, $infoHeader -> width, $height);
                $actualSize = strlen($uncompressedImgData);
                if ($uncompressedImgSizeBytes !== $actualSize) {
                    throw new Exception("RLE4 decode failed. Expected $uncompressedImgSizeBytes bytes uncompressed, got $actualSize");
                }
                break;
            case BmpInfoHeader::B1_JPEG:
            case BmpInfoHeader::B1_PNG:
            case BmpInfoHeader::B1_CMYK:
            case BmpInfoHeader::B1_CMYKRLE8:
            case BmpInfoHeader::B1_CMYKRLE4:
                throw new Exception("Compression method not implemented");
            default:
                throw new Exception("Bad compression method");
        }
        // Account for padding, row order
        $paddedLines = str_split($uncompressedImgData, $rowSizeBytes);
        $dataLines = [];
        $rowDataBytes = intdiv($infoHeader -> bpp * $infoHeader -> width + 7, 8); // Excludes padding bytes
        for ($i = count($paddedLines) - 1; $i >= 0; $i--) { // Iterate lines backwards
            $dataLines[] = substr($paddedLines[$i], 0, $rowDataBytes);
        }
        if ($topDown) {
            // Top-down storage (not as common), reverse them again.
            $dataLines = array_reverse($dataLines);
        }
        $uncompressedImgData = implode("", $dataLines);
        // Account for RGB vs BGR in file format
        if ($infoHeader -> bpp == 24) {
            $pixels = str_split($uncompressedImgData, 3);
            array_walk($pixels, ["\\Mike42\\GfxPhp\\Codec\\Bmp\\BmpFile", "transformRevString"]);
            $uncompressedImgData = implode("", $pixels);
        }
        // Convert to array of numbers 0-255.
        $dataArray = array_values(unpack("C*", $uncompressedImgData));
        if ($infoHeader -> profileSize > 0) {
            // Skip color profile if present after the image
            $imgEnd = $compressedImgSizeBytes + $fileHeader -> offset - BmpFileHeader::FILE_HEADER_SIZE;
            $profileStart = $infoHeader -> profileData;
            if ($profileStart >= $imgEnd) { // Profile may be before image data, in which case it's already been skipped
                $padding = $profileStart - $imgEnd;
                $data -> read($infoHeader -> profileSize + $padding);
            }
        }
        if (!$data -> isEof()) {
            throw new Exception("BMP image has unexpected trailing data");
        }
        return new BmpFile($fileHeader, $infoHeader, $dataArray, $colorTable);
    }

    private static function isOs21XBitmap(BmpFileHeader $fileHeader, BmpInfoHeader $infoHeader, int $colorCount)
    {
        // OS/2 1.x bitmaps use 24 bits per entry in the color palette, rather than 32, but share the same 12-byte
        // header as original Windows bitmaps. If the header size, color count and offset to the bitmap data are
        // consistent with 24-bit color table, then this function returns true.
        if ($infoHeader -> headerSize !== BmpInfoHeader::OS21XBITMAPHEADER_SIZE) {
            // Wrong header size
            return false;
        }
        if ($fileHeader -> offset > $colorCount * 3 + BmpInfoHeader::OS21XBITMAPHEADER_SIZE + BmpFileHeader::FILE_HEADER_SIZE) {
            // Data starts later than we expect
            return false;
        }
        return true;
    }

    public function toRasterImage() : RasterImage
    {
        $height = abs($this -> infoHeader -> height);
        $width = $this -> infoHeader -> width;
        if ($this -> infoHeader -> bpp == 1) {
            $expandedData = PngImage::expandBytes1Bpp($this -> uncompressedData, $width);
            return IndexedRasterImage::create($width, $height, $expandedData, $this -> palette);
        } else if ($this -> infoHeader -> bpp == 2) {
            $expandedData = PngImage::expandBytes2Bpp($this -> uncompressedData, $width);
            return IndexedRasterImage::create($this->infoHeader -> width, $height, $expandedData, $this -> palette);
        } else if ($this -> infoHeader -> bpp == 4) {
            $expandedData = PngImage::expandBytes4Bpp($this -> uncompressedData, $width);
            return IndexedRasterImage::create($width, $height, $expandedData, $this -> palette);
        } else if ($this -> infoHeader -> bpp == 8) {
            return IndexedRasterImage::create($width, $height, $this -> uncompressedData, $this -> palette);
        } else if ($this -> infoHeader -> bpp == 16) {
            $masks = BmpColorBitfield::from16bitDefaults();
            if ($this -> infoHeader -> redMask !== 0 || $this -> infoHeader -> greenMask !== 0 || $this -> infoHeader -> blueMask || $this -> infoHeader -> alphaMask !== 0) {
                // Any non-default values?
                $masks = BmpColorBitfield::fromRgba($this -> infoHeader -> redMask, $this -> infoHeader -> greenMask, $this -> infoHeader -> blueMask, $this -> infoHeader -> alphaMask);
            }
            // Map 16-bit raster data to 24-bit
            $decoder = new BmpBitfieldDecoder($masks);
            $expandedData = $decoder -> read16bit($this -> uncompressedData);
            return RgbRasterImage::create($width, $height, $expandedData);
        } else if ($this -> infoHeader -> bpp == 24) {
            return RgbRasterImage::create($this->infoHeader->width, $this->infoHeader->height, $this->uncompressedData);
        } else if ($this -> infoHeader -> bpp == 32) {
            $masks = BmpColorBitfield::from32bitDefaults();
            if ($this -> infoHeader -> redMask !== 0 || $this -> infoHeader -> greenMask !== 0 || $this -> infoHeader -> blueMask || $this -> infoHeader -> alphaMask !== 0) {
                // Any non-default values?
                $masks = BmpColorBitfield::fromRgba($this -> infoHeader -> redMask, $this -> infoHeader -> greenMask, $this -> infoHeader -> blueMask, $this -> infoHeader -> alphaMask);
            }
            // Map 32-bit raster data to 24-bit
            $decoder = new BmpBitfieldDecoder($masks);
            $expandedData = $decoder -> read32bit($this -> uncompressedData);
            return RgbRasterImage::create($width, $height, $expandedData);
        }
        throw new Exception("Unknown bit depth " . $this -> infoHeader -> bpp);
    }

    public static function transformRevString(&$item, $key)
    {
        // Convert RGB to BGR
        $item = strrev($item);
    }
}
