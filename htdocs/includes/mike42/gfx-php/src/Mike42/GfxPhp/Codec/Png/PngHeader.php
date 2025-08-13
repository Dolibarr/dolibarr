<?php
namespace Mike42\GfxPhp\Codec\Png;

use Mike42\GfxPhp\Codec\Png\PngHeader;

class PngHeader
{
    const HEADER_SIZE = 13;
    const COLOR_TYPE_MONOCHROME = 0;
    const COLOR_TYPE_RGB = 2;
    const COLOR_TYPE_INDEXED = 3;
    const COLOR_TYPE_MONOCHROME_ALPHA = 4;
    const COLOR_TYPE_RGBA = 6;

    const COMPRESSION_DEFLATE = 0;

    const INTERLACE_NONE = 0;
    const INTERLACE_ADAM7 = 1;

    private $width;
    private $height;
    private $bitDepth;
    private $colorType;
    private $compression;
    private $filter;
    private $interlace;
    
    public function __construct(int $width, int $height, int $bitDepth, int $colorType, int $compression, int $filter, int $interlace)
    {
        // Image dimensions
        if ($width < 1 || $width > 2147483647 ||
            $height < 1 || $height > 2147483647) {
                throw new \Exception("Invalid image dimensions");
        }
        $this -> width = $width;
        $this -> height = $height;
        // Color type & bit depth - Only some combinations of bit depth and colorType are valid.
        // I'm sure you could abbreviate this code, but it's written for comparison with the PNG standard.
        if ($colorType === 0 && ($bitDepth === 1 || $bitDepth === 2 || $bitDepth === 4 || $bitDepth === 8 || $bitDepth === 16)) {
            $this -> bitDepth = $bitDepth;
            $this -> colorType = $colorType;
        } else if ($colorType === 2 && ($bitDepth === 8 || $bitDepth === 16)) {
            $this -> bitDepth = $bitDepth;
            $this -> colorType = $colorType;
        } else if ($colorType === 3 && ($bitDepth === 1 || $bitDepth === 2 || $bitDepth === 4 || $bitDepth === 8)) {
            $this -> bitDepth = $bitDepth;
            $this -> colorType = $colorType;
        } else if ($colorType === 4 && ($bitDepth === 8 || $bitDepth === 16)) {
            $this -> bitDepth = $bitDepth;
            $this -> colorType = $colorType;
        } else if ($colorType === 6 && ($bitDepth === 8 || $bitDepth === 16)) {
            $this -> bitDepth = $bitDepth;
            $this -> colorType = $colorType;
        } else {
            throw new \Exception("Invalid color type / bit depth combination.");
        }
        // Compression
        if ($compression != PngHeader::COMPRESSION_DEFLATE) {
            throw new \Exception("Compression type not supported");
        }
            $this -> compression = $compression;
            // Filter type set
        if ($filter != 0) {
            throw new \Exception("Filter type set not supported");
        }
            $this -> filter = $filter;
            // Interlace method
        if ($interlace != PngHeader::INTERLACE_NONE &&
                $interlace != PngHeader::INTERLACE_ADAM7) {
                throw new \Exception("Interlace method not supported");
        }
                $this -> interlace = $interlace;
    }
    
    public static function fromChunk(PngChunk $chunk)
    {
        $chunkData = $chunk -> getData();
        $chunkLen = strlen($chunkData);
        if ($chunkLen !== PngHeader::HEADER_SIZE) {
            throw new \Exception("Header must be " . PngHeader::HEADER_SIZE . " bytes, but got $chunkLen bytes.");
        }
        // Unpack binary
        $dataItems = unpack("Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace", $chunkData);
        // Construct
        return new PngHeader($dataItems['width'], $dataItems['height'], $dataItems['bitDepth'], $dataItems['colorType'], $dataItems['compression'], $dataItems['filter'], $dataItems['interlace']);
    }
    
    public function toString()
    {
        return "Image dimensions " . $this -> width . " x " . $this -> height .
        ", bitDepth " . $this -> bitDepth .
        ", colorType " . $this -> colorType .
        ", compression " . $this -> compression .
        ", filter " . $this -> filter .
        ", interlace " . $this -> interlace;
    }
    
    public function allowsPalette()
    {
        return $this -> requiresPalette() ||
        $this -> colorType === PngHeader::COLOR_TYPE_RGB ||
        $this -> colorType === PngHeader::COLOR_TYPE_RGBA;
    }
    
    public function requiresPalette()
    {
        return $this -> colorType === PngHeader::COLOR_TYPE_INDEXED;
    }
    
    public function getWidth()
    {
        return $this -> width;
    }
    
    public function getHeight()
    {
        return $this -> height;
    }
    
    public function getBitDepth()
    {
        return $this -> bitDepth;
    }
    
    public function getColorType()
    {
        return $this -> colorType;
    }
    
    public function getCompresssion()
    {
        return $this -> compresssion;
    }
    
    public function getChannels()
    {
        // Return number of channels
        $channelLookup = [
            PngHeader::COLOR_TYPE_MONOCHROME => 1,
            PngHeader::COLOR_TYPE_RGB => 3,
            PngHeader::COLOR_TYPE_INDEXED => 1,
            PngHeader::COLOR_TYPE_MONOCHROME_ALPHA => 2,
            PngHeader::COLOR_TYPE_RGBA => 4,
        ];
        return $channelLookup[$this -> getColorType()];
    }
    
    public function getFilter()
    {
        return $this -> filter;
    }
    
    public function getInterlace()
    {
        return $this -> interlace;
    }
}
