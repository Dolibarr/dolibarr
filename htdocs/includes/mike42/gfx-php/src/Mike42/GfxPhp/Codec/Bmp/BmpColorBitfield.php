<?php

namespace Mike42\GfxPhp\Codec\Bmp;

/**
 * Hold color mask information
 */
class BmpColorBitfield
{
    private $red;
    private $green;
    private $blue;
    private $alpha;

    public function __construct(BmpColorMask $red, BmpColorMask $green, BmpColorMask $blue, BmpColorMask $alpha)
    {
        // Reject overlapping chanel masks, probably indicating corrupt files.
        $tmp = 0;
        foreach ([$red, $green, $blue, $alpha] as $channel) {
            $mask = $channel->getMask();
            if (($tmp & $mask) != 0x00) {
                throw new \Exception("Color channel masks must not overlap");
            }
            $tmp |= $mask;
        }
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->alpha = $alpha;
    }

    public function getRed()
    {
        return $this->red;
    }

    public function getGreen()
    {
        return $this->green;
    }

    public function getBlue()
    {
        return $this->blue;
    }

    public function getAlpha()
    {
        return $this->alpha;
    }

    public static function fromRgba(int $r, int $g, int $b, int $a)
    {
        return new BmpColorBitfield(new BmpColorMask($r), new BmpColorMask($g), new BmpColorMask($b), new BmpColorMask($a));
    }

    public static function from16bitDefaults()
    {
        // If not specified, we use XRRRRRGG GGGBBBBB
        return self::fromRgba(0x7c00, 0x03e0, 0x001f, 0x0000);
    }

    public static function from32bitDefaults()
    {
        // If not specified, we use XXXXXXXX RRRRRRRR GGGGGGGG BBBBBBBB
        return self::fromRgba(0x00ff0000, 0x0000ff00, 0x000000ff, 0x00000000);
    }
}
