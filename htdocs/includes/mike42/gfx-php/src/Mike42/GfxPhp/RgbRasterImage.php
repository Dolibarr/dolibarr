<?php
namespace Mike42\GfxPhp;

class RgbRasterImage extends AbstractRasterImage
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

    public function getRasterData(): string
    {
        if ($this -> maxVal > 255) {
            return pack("n*", ... $this -> data);
        }
        return pack("C*", ... $this -> data);
    }

    public function getMaxVal()
    {
        return $this -> maxVal;
    }

    public function getPixel(int $x, int $y) : int
    {
        if ($x < 0 || $x >= $this -> width) {
            return 0;
        }
        if ($y < 0 || $y >= $this -> height) {
            return 0;
        }
        $byte = ($y * $this -> width + $x) * 3;
        return self::rgbToInt($this -> data[$byte], $this -> data[$byte + 1], $this -> data[$byte + 2], $this -> maxVal);
    }

    public function indexToRgb(int $val)
    {
        return self::intToRgb($val);
    }
    
    public function rgbToIndex(array $val)
    {
        return self::rgbToInt($val[0], $val[1], $val[2]);
    }
    
    public static function rgbToInt(int $r, int $g, int $b)
    {
        return ($r << 16) | ($g << 8) | $b;
    }

    public static function intToRgb($in)
    {
        return [
            ($in >> 16) & 0xFF,
            ($in >> 8) & 0xFF,
            ($in) & 0xFF
        ];
    }

    public function setPixel(int $x, int $y, int $value)
    {
        if ($x < 0 || $x >= $this -> width) {
            return;
        }
        if ($y < 0 || $y >= $this -> height) {
            return;
        }
        $rgb = self::intToRgb($value);
        for ($i = 0; $i < 3; $i++) {
            $value = $rgb[$i] & 0xFF;
            $byte = ($y * $this -> width + $x) * 3;
            $this -> data[$byte + $i] = $value;
        }
    }

    public function mapColor(int $srcColor, RasterImage $destImage)
    {
        if ($destImage instanceof RgbRasterImage) {
            return $srcColor;
        }
        throw new \Exception("Cannot map colors");
    }

    protected function __construct($width, $height, array $data, int $maxVal)
    {
        $this -> width = $width;
        $this -> height = $height;
        $this -> data = $data;
        $this -> maxVal = $maxVal;
    }

    public static function create($width, $height, array $data = null, $maxVal = 255) : RgbRasterImage
    {
        $expectedBytes = $width * $height * 3;
        if ($data === null) {
            $data = array_values(array_fill(0, $expectedBytes, $maxVal));
        }
        if ($maxVal > 255) {
            array_walk($data, array('self', 'convertDepth'), [$maxVal, 255]);
            $maxVal = 255;
        }
        return new RgbRasterImage($width, $height, $data, $maxVal);
    }

    public static function convertDepth(&$item, $key, array $data)
    {
        $maxVal = $data[0];
        $newMaxVal = $data[1];
        $item = intdiv($item * $newMaxVal, $maxVal);
    }

    public function toRgb() : RgbRasterImage
    {
        return clone $this;
    }

    public function toGrayscale() : GrayscaleRasterImage
    {
        $img = GrayscaleRasterImage::create($this -> width, $this -> height);
        for ($y = 0; $y < $this -> height; $y++) {
            for ($x = 0; $x < $this -> width; $x++) {
                $original = $this -> intToRgb($this -> getPixel($x, $y));
                $lightness = intdiv($original[0] + $original[1] + $original[2], 3);
                $img -> setPixel($x, $y, $lightness);
            }
        }
        return $img;
    }

    public function toBlackAndWhite() : BlackAndWhiteRasterImage
    {
        $img = BlackAndWhiteRasterImage::create($this -> width, $this -> height);
        for ($y = 0; $y < $this -> height; $y++) {
            for ($x = 0; $x < $this -> width; $x++) {
                $original = $this -> intToRgb($this -> getPixel($x, $y));
                $lightness = intdiv($original[0] + $original[1] + $original[2], 3);
                $img -> setPixel($x, $y, $lightness > 128 ? 0 : 1);
            }
        }
        return $img;
    }

    public function toIndexed(): IndexedRasterImage
    {
        // NB: It might be possible to speed this up with array_fill_keys and array_replace.
        // Each pixel as a numeric value
        $pixels = array_map([$this, "rgbToIndex"], array_chunk($this -> data, 3, false));
        // List of unique colors
        $colorValues = array_values(array_unique($pixels, SORT_NUMERIC));
        $paletteSize = count($colorValues);
        // Use 24-bit color number as key, palette index as value
        $lookup = array_flip($colorValues);
        // Replace palette values w/ expanded [r, g, b] values for use in IndexedRasterImage
        for ($i = 0; $i < $paletteSize; $i++) {
            $colorValues[$i] = $this -> intToRgb($colorValues[$i]);
        }
        // Replace pixel values with color ID's
        $imageSize = count($pixels);
        for ($i = 0; $i < $imageSize; $i++) {
            $pixels[$i] = $lookup[$pixels[$i]];
        }
        // Max value round-off to 256 colors if possible, otherwise leave unlimited
        $maxVal = $paletteSize > 256 ? 16777215 : 255;
        return IndexedRasterImage::create($this -> width, $this -> height, $pixels, $colorValues, $maxVal);
    }
}
