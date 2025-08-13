<?php
namespace Mike42\GfxPhp;

class PaletteGenerator
{
    public static function monochromePalette()
    {
        // 256 even levels of grey
        $colorTable = [];
        for ($i = 0; $i < 256; $i++) {
            $colorTable[] = [$i, $i, $i];
        }
        return $colorTable;
    }
    
    public static function blackAndWhitePalette()
    {
        // 2 color levels
        return [[255, 255, 255], [0, 0, 0]];
    }
    
    public static function colorPalette()
    {
        // Three bits of red, three bits of green, two bits of blue.
        $colorTable = [];
        for ($i = 0; $i < 256; $i++) {
            // Unscaled 8, 8, 4 level values
            $r = ($i >> 5) & 0x07;
            $g = ($i >> 2) & 0x07;
            $b = $i & 0x03;

            // Scaled 256-level values
            $rScaled = (int)round($r * (255 / 7));
            $gScaled = (int)round($g * (255 / 7));
            $bScaled = (int)round($b * (255 / 3));

            $colorTable[] = [$rScaled, $gScaled, $bScaled];
        }
        return $colorTable;
    }
    
    public static function whitePalette()
    {
        return [[255, 255, 255]];
    }
}
