<?php
namespace Mike42\GfxPhp;

class GrayscaleRasterImage extends AbstractRasterImage
{
    protected $width;

    protected $height;

    protected $data;
    
    protected $maxVal;

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
        // Cut off at max and min
        if ($value < 0) {
            $value = 0;
        } else if ($value > $this -> maxVal) {
            $value = $this -> maxVal;
        }
        $byte = $y * $this -> width + $x;
        $this -> data[$byte] = $value;
    }

    public function getPixel(int $x, int $y) : int
    {
        if ($x < 0 || $x >= $this -> width) {
            return 0;
        }
        if ($y < 0 || $y >= $this -> height) {
            return 0;
        }
        $byte = $y * $this -> width + $x;
        return $this -> data[$byte];
    }

    protected function __construct($width, $height, array $data, int $maxVal)
    {
        $this -> width = $width;
        $this -> height = $height;
        $this -> data = $data;
        $this -> maxVal = $maxVal;
    }
    
    public function getMaxVal()
    {
        return $this -> maxVal;
    }

    public static function create($width, $height, array $data = null, $maxVal = 255) : GrayscaleRasterImage
    {
        $expectedBytes = $width * $height;
        if ($data === null) {
            $data = array_values(array_fill(0, $expectedBytes, $maxVal));
        }
        return new GrayscaleRasterImage($width, $height, $data, $maxVal);
    }

    public function getRasterData(): string
    {
        if ($this -> maxVal > 255) {
            return pack("n*", ... $this -> data);
        }
        return pack("C*", ... $this -> data);
    }
    
    public function mapColor(int $srcColor, RasterImage $destImage)
    {
        if ($destImage instanceof GrayscaleRasterImage) {
            if ($destImage -> maxVal == $this -> maxVal) {
                return $srcColor;
            }
            $destVal =  intdiv($srcColor * $destImage -> maxVal, $this -> maxVal);
            return $destVal;
        }
        throw new \Exception("Cannot map colors");
    }
    
    public function toRgb() : RgbRasterImage
    {
        $img = RgbRasterImage::create($this -> width, $this -> height);
        for ($y = 0; $y < $this -> height; $y++) {
            for ($x = 0; $x < $this -> width; $x++) {
                $lightness = intdiv($this -> getPixel($x, $y) * 255, $this -> maxVal);
                $rgb = ($lightness << 16) | ($lightness << 8) | ($lightness);
                $img -> setPixel($x, $y, $rgb);
            }
        }
        return $img;
    }
    
    public function toGrayscale() : GrayscaleRasterImage
    {
        return clone $this;
    }
    
    public function toBlackAndWhite() : BlackAndWhiteRasterImage
    {
        $img = BlackAndWhiteRasterImage::create($this -> width, $this -> height);
        $threshold = intdiv($this -> maxVal, 2);
        for ($y = 0; $y < $this -> height; $y++) {
            for ($x = 0; $x < $this -> width; $x++) {
                $original = $this -> getPixel($x, $y);
                $img -> setPixel($x, $y, $original > $threshold ? 0 : 1);
            }
        }
        return $img;
    }
    
    public function toIndexed(): IndexedRasterImage
    {
        if ($this -> maxVal > 255) {
            // Making use of how scale() uses default values to make a new canvas, which has the
            // side-effect of creating an 8-bit image.
            return $this -> scale($this -> width, $this -> height) -> toIndexed();
        }
        $data = $this -> data;
        $colorTable = PaletteGenerator::monochromePalette();
        return IndexedRasterImage::create($this -> width, $this -> height, $data, $colorTable, 255);
    }
}
