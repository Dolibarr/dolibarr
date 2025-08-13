<?php
namespace Mike42\GfxPhp\Codec\Png;

use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\IndexedRasterImage;
use Mike42\GfxPhp\RasterImage;
use Mike42\GfxPhp\RgbRasterImage;
use Mike42\GfxPhp\Codec\Common\DataInputStream;

class PngImage
{
    const PNG_SIGNATURE="\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";

    private $header;
    private $imageData;
    private $chunkPalette;
    
    private function __construct(PngHeader $header, array $imageData, PngChunk $chunkPalette = null)
    {
        $this -> header = $header;
        $this -> imageData = $imageData;
        $this -> chunkPalette = $chunkPalette;
    }

    public static function fromBinary(DataInputStream $data) : PngImage
    {
        // Check signature
        $signature = $data -> read(8);
        if ($signature != PngImage::PNG_SIGNATURE) {
            throw new \Exception("Bad PNG signature");
        }
        
        // Iterate chunks
        $chunk_header = PngChunk::fromBin($data);
        $header = PngHeader::fromChunk($chunk_header);
        if ($chunk_header == null || $chunk_header -> getType() !== "IHDR") {
            throw new \Exception("File does not begin with IHDR chunk");
        }
        $chunk_palette = null;
        $chunk_data = [];
        $chunk_end = null;
        
        while (( $chunk = PngChunk::fromBin($data) ) !== null) {
            if ($chunk -> getType() === "IEND") {
                $chunk_end = $chunk;
                break;
            }
            if ($chunk -> getType() === "PLTE") {
                if (!$header -> allowsPalette()) {
                    throw new \Exception("Palette not allowed for this image type");
                } else if ($chunk_palette !== null) {
                    throw new \Exception("Multiple palette entries");
                } else if (count($chunk_data) > 0) {
                    throw new \Exception("Palette must be issued before first data chunk");
                }
                $paletteLen = strlen($chunk -> getData());
                if ($paletteLen === 0 || $paletteLen > (256 * 3) || $paletteLen % 3 !== 0) {
                    throw new \Exception("Palette length is invalid");
                }
                $chunk_palette = $chunk;
            }
            if ($chunk -> getType() === "IDAT") {
                $chunk_data[] = $chunk;
            }
        }
        
        if ($header -> requiresPalette() && $chunk_palette === null) {
            throw new \Exception("Missing palette, required for this image type");
        }
        
        if (count($chunk_data) === 0) {
            throw new \Exception("No data received");
        }
        
        if ($chunk_end === null) {
            throw new \Exception("File does not end with IEND chunk");
        }
        
        if (!$data -> isEof()) {
            throw new \Exception("Data extends past end of file");
        }
        
        // Extract, join and decompress chunks
        $imageDataCompressed = '';
        foreach ($chunk_data as $chunk) {
            $imageDataCompressed .= $chunk -> getData();
        }
        // TODO maximum decoded data size can be determined from image size and bit depth
        $binData = zlib_decode($imageDataCompressed);
        if ($binData === false) {
            throw new \Exception("DEFLATE decompression failed");
        }
        
        // Turn into array of scan-lines based on filtering
        $filterDecoder = new FilterDecoder();
        $interlaceDecoder = new InterlaceDecoder($filterDecoder);
        $imageData = $interlaceDecoder -> decode($header, $binData);
        return new PngImage($header, $imageData, $chunk_palette);
    }
    
    public function toRasterImage() : RasterImage
    {
        // Further processing depends on image type
        $bitDepth = $this -> header -> getBitDepth();
        $width = $this -> header -> getWidth();
        $height = $this -> header -> getHeight();
        $channels = $this -> header -> getChannels();
        $imageData = $this -> imageData;
        switch ($this -> header -> getColorType()) {
            case PngHeader::COLOR_TYPE_MONOCHROME:
                switch ($bitDepth) {
                    case 1:
                        $im = BlackAndWhiteRasterImage::create($width, $height, $imageData);
                        $im -> invert(); // Difference in meaning for set/unset pixels.
                        break;
                    case 2:
                        // Re-interpret data with lower depth (2 bits per sample);
                        $expandedData = self::expandBytes2Bpp($imageData, $width);
                        $im = GrayscaleRasterImage::create($width, $height, $expandedData, 0x03);
                        break;
                    case 4:
                        // Re-interpret data with lower depth (4 bits per sample);
                        $expandedData = self::expandBytes4Bpp($imageData, $width);
                        $im = GrayscaleRasterImage::create($width, $height, $expandedData, 0x0F);
                        break;
                    case 8:
                        $im = GrayscaleRasterImage::create($width, $height, $imageData);
                        break;
                    case 16:
                        // Re-interpret data with higher depth.
                        $combinedData = self::combineBytes16Bpp($imageData);
                        $im = GrayscaleRasterImage::create($width, $height, $combinedData, 65535);
                        break;
                    default:
                        throw new \Exception("COLOR_TYPE_MONOCHROME at bit depth $bitDepth not supported");
                }
                break;
            case PngHeader::COLOR_TYPE_RGB:
                switch ($bitDepth) {
                    case 8:
                        $im = RgbRasterImage::create($width, $height, $imageData);
                        break;
                    case 16:
                        // Re-interpret data with higher depth.
                        $combinedData = self::combineBytes16Bpp($imageData);
                        $im = RgbRasterImage::create($width, $height, $combinedData, 0xFFFF);
                        break;
                    default:
                        throw new \Exception("COLOR_TYPE_RGB at bit depth $bitDepth not supported");
                }
                break;
            case PngHeader::COLOR_TYPE_INDEXED:
                switch ($bitDepth) {
                    case 1:
                        $imageData = self::expandBytes1Bpp($imageData, $width);
                        break;
                    case 2:
                        $imageData = self::expandBytes2Bpp($imageData, $width);
                        break;
                    case 4:
                        $imageData = self::expandBytes4Bpp($imageData, $width);
                        break;
                    case 8:
                        // Data is all good.
                        break;
                    default:
                        throw new \Exception("COLOR_TYPE_INDEXED at bit depth $bitDepth not supported");
                }
                $paletteArr = array_values(unpack("C*", $this -> chunkPalette -> getData()));
                $palette = array_chunk($paletteArr, 3);
                $im = IndexedRasterImage::create($width, $height, $imageData, $palette, 0xFF);
                break;
            case PngHeader::COLOR_TYPE_MONOCHROME_ALPHA:
                // Mix out Alpha and load as Grayscale.
                switch ($bitDepth) {
                    case 8:
                        $mixedData = $this -> alphaMix($imageData, 2);
                        $im = GrayscaleRasterImage::create($width, $height, $mixedData, 0xFF);
                        break;
                    case 16:
                        $mixedData = $this -> alphaMix(self::combineBytes16Bpp($imageData), 2);
                        $im = GrayscaleRasterImage::create($width, $height, $mixedData, 0xFFFF);
                        break;
                    default:
                        throw new \Exception("COLOR_TYPE_MONOCHROME_ALPHA at bit depth $bitDepth not supported");
                }
                break;
            case PngHeader::COLOR_TYPE_RGBA:
                // Mix out Alpha and load as RGB.
                switch ($bitDepth) {
                    case 8:
                        $mixedData = $this -> alphaMix($imageData, 4);
                        $im = RgbRasterImage::create($width, $height, $mixedData, 0xFF);
                        break;
                    case 16:
                        $mixedData = $this -> alphaMix(self::combineBytes16Bpp($imageData), 4);
                        $im = RgbRasterImage::create($width, $height, $mixedData, 0xFFFF);
                        break;
                    default:
                        throw new \Exception("COLOR_TYPE_RGBA at bit depth $bitDepth not supported");
                }
                break;
            default:
                throw new \Exception("Unsupported image type");
        }
        return $im;
    }
    
    /**
     * Takes 8-bit samples, and produces eight times as many 1-bit samples,
     * dropping padding bits along the way.
     */
    public static function expandBytes1Bpp(array $in, int $width)
    {
        $res =  [];
        $scanlineBytes = intdiv($width + 7, 8);
        $scanlines = array_chunk($in, $scanlineBytes);
        foreach ($scanlines as $line) {
            for ($x = 0; $x < $width; $x++) {
                $srcByte = intdiv($x, 8);
                $part = $x % 8;
                $shift = 7 - $part;
                $res[] = ($line[$srcByte] >> $shift) & 0x01;
            }
        }
        return $res;
    }
    
    /**
     * Takes 8-bit samples, and produces four times as many 2-bit samples,
     * dropping padding bits along the way.
     */
    public static function expandBytes2Bpp(array $in, int $width)
    {
        $res =  [];
        $scanlineBytes = intdiv($width + 3, 4);
        $scanlines = array_chunk($in, $scanlineBytes);
        foreach ($scanlines as $line) {
            for ($x = 0; $x < $width; $x++) {
                $srcByte = intdiv($x, 4);
                $part = $x % 4;
                $shift = 6 - (2 * $part);
                $res[] = ($line[$srcByte] >> $shift) & 0x03;
            }
        }
        return $res;
    }
    
    /**
     * Takes 8-bit samples, and produces twice as many 4-bit samples,
     * dropping padding bits along the way.
     */
    public static function expandBytes4Bpp(array $in, int $width)
    {
        $scanlineBytes = intdiv($width + 1, 2);
        $scanlines = array_chunk($in, $scanlineBytes);
        $res = [];
        foreach ($scanlines as $line) {
            for ($x = 0; $x < $width; $x++) {
                $srcByte = intdiv($x, 2);
                $part = $x % 2;
                $shift = 4 - (4 * $part);
                $res[] = ($line[$srcByte] >> $shift) & 0x0F;
            }
        }
        return $res;
    }
    
    /**
     * Takes 8-bit samples, and produces half as many 16-bit samples.
     */
    public static function combineBytes16Bpp(array $in)
    {
        $data = array_values(unpack("n*", pack("C*", ...$in)));
        return $data;
    }
    
    /**
     * We'll use this to mix with a background color.
     */
    private function alphaMix(array $data, $chunkSize)
    {
        // Will need to change to "alphaMixPixel" to [$this, "alphaMixPixel"] once we are in a class.
        $noAlphaPixels = array_map([$this, "alphaMixPixel"], array_chunk($data, $chunkSize, false));
        return array_merge(...$noAlphaPixels);
    }
    
    private function alphaMixPixel(array $channels)
    {
        // Mix alpha to white
        $maxLevel = 2 ** $this -> header -> getBitDepth() - 1;
        $backGround = $maxLevel;
        $alpha = array_pop($channels) / $maxLevel;
        foreach ($channels as $id => $channel) {
            $pixels[$id] = ($maxLevel - ($maxLevel - $channel) * $alpha);
        }
        return $pixels;
    }
}
