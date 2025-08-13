<?php
namespace Mike42\GfxPhp;

/**
 * Small implementation of basic raster operations on PBM files to support
 * creation of placeholder glyphs
 */
class BlackAndWhiteRasterImage extends AbstractRasterImage
{
    protected $width;

    protected $bytesPerRow;

    protected $height;

    protected $data;

    public function invert()
    {
        array_walk($this -> data, 'self::invertByte');
    }

    public function clear()
    {
        array_walk($this -> data, 'self::clearByte');
    }

    protected static function invertByte(int &$item, $key)
    {
        $item = ~ $item;
    }

    protected static function clearByte(int &$item, $key)
    {
        $item = 0;
    }

    public function getWidth() : int
    {
        return $this -> width;
    }

    public function getHeight() : int
    {
        return $this -> height;
    }


    public function setPixel(int $x, int $y, int $value)
    {
        if ($x < 0 || $x >= $this -> width) {
            return;
        }
        if ($y < 0 || $y >= $this -> height) {
            return;
        }
        $byte = $y * $this -> bytesPerRow + intdiv($x, 8);
        $bit = $x % 8;
        if ($value === 0) {
          // Clear
            $this -> data[$byte] &= ~(1 << (7 - $bit));
        } else {
          // Set
            $this -> data[$byte] |= (1 << (7 - $bit));
        }
    }

    public function getPixel(int $x, int $y) : int
    {
        if ($x < 0 || $x >= $this -> width) {
            return 0;
        }
        if ($y < 0 || $y >= $this -> height) {
            return 0;
        }
        $byte = $y * $this -> bytesPerRow + intdiv($x, 8);
        $bit = $x % 8;
        return ($this -> data[$byte] >> (7 - $bit)) & 0x01;
    }

    protected function __construct($width, $height, array $data)
    {
        $this -> width = $width;
        $this -> height = $height;
        $this -> data = $data;
        $this -> bytesPerRow = intdiv($width + 7, 8);
    }

    public static function create($width, $height, array $data = null) : BlackAndWhiteRasterImage
    {
        $bytesPerRow = intdiv($width + 7, 8);
        $expectedBytes = $bytesPerRow * $height;
        if ($data === null) {
            $data = array_values(array_fill(0, $expectedBytes, 0));
        }
        return new BlackAndWhiteRasterImage($width, $height, $data);
    }

    public function toString()
    {
        $out = "";
        for ($y = 0; $y < $this -> getHeight(); $y += 2) {
            for ($x = 0; $x < $this -> getWidth(); $x++) {
                $upper = $this -> getPixel($x, $y) == 1;
                $lower = $this -> getPixel($x, $y + 1) == 1;
                if ($upper && $lower) {
                    $char = "█";
                } else if ($upper) {
                    $char = "▀";
                } else if ($lower) {
                    $char = "▄";
                } else {
                    $char = " ";
                }
                $out .= $char;
            }
            $out .= "\n";
        }
        return $out;
    }

    public function getRasterData(): string
    {
        return pack("C*", ... $this -> data);
    }
    
    public function mapColor(int $srcColor, RasterImage $destImage)
    {
        if ($destImage instanceof BlackAndWhiteRasterImage) {
            return $srcColor;
        }
        throw new \Exception("Cannot map colors");
    }
    
    
    public function toRgb() : RgbRasterImage
    {
        $img = RgbRasterImage::create($this -> width, $this -> height);
        for ($y = 0; $y < $this -> height; $y++) {
            for ($x = 0; $x < $this -> width; $x++) {
                $original = $this -> getPixel($x, $y);
                $img -> setPixel($x, $y, $original == 0 ? 16777215 : 0);
            }
        }
        return $img;
    }
    
    public function toGrayscale() : GrayscaleRasterImage
    {
        $img = GrayscaleRasterImage::create($this -> width, $this -> height);
        for ($y = 0; $y < $this -> height; $y++) {
            for ($x = 0; $x < $this -> width; $x++) {
                $original = $this -> getPixel($x, $y);
                $img -> setPixel($x, $y, $original == 0 ? 255 : 0);
            }
        }
        return $img;
    }

    public function toBlackAndWhite() : BlackAndWhiteRasterImage
    {
         return clone $this;
    }

    public function toIndexed(): IndexedRasterImage
    {
        $width = $this -> width;
        $height = $this -> height;
        $pixelData = array_fill(0, $width * $height, 0);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixelData[$y * $width + $x] = $this -> getPixel($x, $y);
            }
        }
        $colorTable = PaletteGenerator::blackAndWhitePalette();
        return IndexedRasterImage::create($width, $height, $pixelData, $colorTable, 255);
    }
}
