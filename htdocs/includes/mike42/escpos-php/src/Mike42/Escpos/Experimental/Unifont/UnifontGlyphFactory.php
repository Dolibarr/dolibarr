<?php


namespace Mike42\Escpos\Experimental\Unifont;

class UnifontGlyphFactory implements ColumnFormatGlyphFactory
{
    protected $unifontFile;

    public static function colFormat16(array $in)
    {
        // Map 16 x 16 bit unifont (32 bytes) to 16 x 24 ESC/POS column format image (48 bytes).
        return UnifontGlyphFactory::colFormat8($in, 2, 1) . UnifontGlyphFactory::colFormat8($in, 2, 2);
    }

    public static function colFormat8(array $in, $chars = 1, $idx = 1)
    {
        // Map 8 x 16 bit unifont (32 bytes) to 8 x 24 ESC/POS column format image (24 bytes).
        return implode([
            chr(
                (($in[0 * $chars + $idx] & 0x80)) |
                (($in[1 * $chars + $idx] & 0x80) >> 1) |
                (($in[2 * $chars + $idx] & 0x80) >> 2) |
                (($in[3 * $chars + $idx] & 0x80) >> 3) |
                (($in[4 * $chars + $idx] & 0x80) >> 4) |
                (($in[5 * $chars + $idx] & 0x80) >> 5) |
                (($in[6 * $chars + $idx] & 0x80) >> 6) |
                (($in[7 * $chars + $idx] & 0x80) >> 7)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x80)) |
                (($in[9 * $chars + $idx] & 0x80) >> 1) |
                (($in[10 * $chars + $idx] & 0x80) >> 2) |
                (($in[11 * $chars + $idx] & 0x80) >> 3) |
                (($in[12 * $chars + $idx] & 0x80) >> 4) |
                (($in[13 * $chars + $idx] & 0x80) >> 5) |
                (($in[14 * $chars + $idx] & 0x80) >> 6) |
                (($in[15 * $chars + $idx] & 0x80) >> 7)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x40) << 1) |
                (($in[1 * $chars + $idx] & 0x40)) |
                (($in[2 * $chars + $idx] & 0x40) >> 1) |
                (($in[3 * $chars + $idx] & 0x40) >> 2) |
                (($in[4 * $chars + $idx] & 0x40) >> 3) |
                (($in[5 * $chars + $idx] & 0x40) >> 4) |
                (($in[6 * $chars + $idx] & 0x40) >> 5) |
                (($in[7 * $chars + $idx] & 0x40) >> 6)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x40) << 1) |
                (($in[9 * $chars + $idx] & 0x40) >> 0) |
                (($in[10 * $chars + $idx] & 0x40) >> 1) |
                (($in[11 * $chars + $idx] & 0x40) >> 2) |
                (($in[12 * $chars + $idx] & 0x40) >> 3) |
                (($in[13 * $chars + $idx] & 0x40) >> 4) |
                (($in[14 * $chars + $idx] & 0x40) >> 5) |
                (($in[15 * $chars + $idx] & 0x40) >> 6)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x20) << 2) |
                (($in[1 * $chars + $idx] & 0x20) << 1) |
                (($in[2 * $chars + $idx] & 0x20)) |
                (($in[3 * $chars + $idx] & 0x20) >> 1) |
                (($in[4 * $chars + $idx] & 0x20) >> 2) |
                (($in[5 * $chars + $idx] & 0x20) >> 3) |
                (($in[6 * $chars + $idx] & 0x20) >> 4) |
                (($in[7 * $chars + $idx] & 0x20) >> 5)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x20) << 2) |
                (($in[9 * $chars + $idx] & 0x20) << 1) |
                (($in[10 * $chars + $idx] & 0x20)) |
                (($in[11 * $chars + $idx] & 0x20) >> 1) |
                (($in[12 * $chars + $idx] & 0x20) >> 2) |
                (($in[13 * $chars + $idx] & 0x20) >> 3) |
                (($in[14 * $chars + $idx] & 0x20) >> 4) |
                (($in[15 * $chars + $idx] & 0x20) >> 5)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x10) << 3) |
                (($in[1 * $chars + $idx] & 0x10) << 2) |
                (($in[2 * $chars + $idx] & 0x10) << 1) |
                (($in[3 * $chars + $idx] & 0x10)) |
                (($in[4 * $chars + $idx] & 0x10) >> 1) |
                (($in[5 * $chars + $idx] & 0x10) >> 2) |
                (($in[6 * $chars + $idx] & 0x10) >> 3) |
                (($in[7 * $chars + $idx] & 0x10) >> 4)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x10) << 3) |
                (($in[9 * $chars + $idx] & 0x10) << 2) |
                (($in[10 * $chars + $idx] & 0x10) << 1) |
                (($in[11 * $chars + $idx] & 0x10)) |
                (($in[12 * $chars + $idx] & 0x10) >> 1) |
                (($in[13 * $chars + $idx] & 0x10) >> 2) |
                (($in[14 * $chars + $idx] & 0x10) >> 3) |
                (($in[15 * $chars + $idx] & 0x10) >> 4)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x08) << 4) |
                (($in[1 * $chars + $idx] & 0x08) << 3) |
                (($in[2 * $chars + $idx] & 0x08) << 2) |
                (($in[3 * $chars + $idx] & 0x08) << 1) |
                (($in[4 * $chars + $idx] & 0x08)) |
                (($in[5 * $chars + $idx] & 0x08) >> 1) |
                (($in[6 * $chars + $idx] & 0x08) >> 2) |
                (($in[7 * $chars + $idx] & 0x08) >> 3)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x08) << 4) |
                (($in[9 * $chars + $idx] & 0x08) << 3) |
                (($in[10 * $chars + $idx] & 0x08) << 2) |
                (($in[11 * $chars + $idx] & 0x08) << 1) |
                (($in[12 * $chars + $idx] & 0x08)) |
                (($in[13 * $chars + $idx] & 0x08) >> 1) |
                (($in[14 * $chars + $idx] & 0x08) >> 2) |
                (($in[15 * $chars + $idx] & 0x08) >> 3)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x04) << 5) |
                (($in[1 * $chars + $idx] & 0x04) << 4) |
                (($in[2 * $chars + $idx] & 0x04) << 3) |
                (($in[3 * $chars + $idx] & 0x04) << 2) |
                (($in[4 * $chars + $idx] & 0x04) << 1) |
                (($in[5 * $chars + $idx] & 0x04)) |
                (($in[6 * $chars + $idx] & 0x04) >> 1) |
                (($in[7 * $chars + $idx] & 0x04) >> 2)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x04) << 5) |
                (($in[9 * $chars + $idx] & 0x04) << 4) |
                (($in[10 * $chars + $idx] & 0x04) << 3) |
                (($in[11 * $chars + $idx] & 0x04) << 2) |
                (($in[12 * $chars + $idx] & 0x04) << 1) |
                (($in[13 * $chars + $idx] & 0x04)) |
                (($in[14 * $chars + $idx] & 0x04) >> 1) |
                (($in[15 * $chars + $idx] & 0x04) >> 2)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x02) << 6) |
                (($in[1 * $chars + $idx] & 0x02) << 5) |
                (($in[2 * $chars + $idx] & 0x02) << 4) |
                (($in[3 * $chars + $idx] & 0x02) << 3) |
                (($in[4 * $chars + $idx] & 0x02) << 2) |
                (($in[5 * $chars + $idx] & 0x02) << 1) |
                (($in[6 * $chars + $idx] & 0x02)) |
                (($in[7 * $chars + $idx] & 0x02) >> 1)
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x02) << 6) |
                (($in[9 * $chars + $idx] & 0x02) << 5) |
                (($in[10 * $chars + $idx] & 0x02) << 4) |
                (($in[11 * $chars + $idx] & 0x02) << 3) |
                (($in[12 * $chars + $idx] & 0x02) << 2) |
                (($in[13 * $chars + $idx] & 0x02) << 1) |
                (($in[14 * $chars + $idx] & 0x02)) |
                (($in[15 * $chars + $idx] & 0x02) >> 1)
            ),
            chr(0),
            chr(
                (($in[0 * $chars + $idx] & 0x01) << 7) |
                (($in[1 * $chars + $idx] & 0x01) << 6) |
                (($in[2 * $chars + $idx] & 0x01) << 5) |
                (($in[3 * $chars + $idx] & 0x01) << 4) |
                (($in[4 * $chars + $idx] & 0x01) << 3) |
                (($in[5 * $chars + $idx] & 0x01) << 2) |
                (($in[6 * $chars + $idx] & 0x01) << 1) |
                (($in[7 * $chars + $idx] & 0x01))
            ),
            chr(
                (($in[8 * $chars + $idx] & 0x01) << 7) |
                (($in[9 * $chars + $idx] & 0x01) << 6) |
                (($in[10 * $chars + $idx] & 0x01) << 5) |
                (($in[11 * $chars + $idx] & 0x01) << 4) |
                (($in[12 * $chars + $idx] & 0x01) << 3) |
                (($in[13 * $chars + $idx] & 0x01) << 2) |
                (($in[14 * $chars + $idx] & 0x01) >> 1) |
                (($in[15 * $chars + $idx] & 0x01))
            ),
            chr(0)
        ]);
    }

    public function __construct(array $unifontFile)
    {
        $this -> unifontFile = $unifontFile;
    }

    public function getGlyph($codePoint)
    {
        // Binary search for correct line.
        $min = 0;
        $max = count($this -> unifontFile) - 1;
        $foundId = 0;
        // Bias toward low side if file is > 255.
        $m = min(count($this -> unifontFile) - 1, 255);
        while ($min <= $max) {
            $thisCodePoint = hexdec(substr($this -> unifontFile[$m], 0, 4));
            if ($codePoint === $thisCodePoint) {
                $foundId = $m;
                break;
            } else if ($codePoint < $thisCodePoint) {
                $max = $m - 1;
            } else {
                $min = $m + 1;
            }
            $m = floor(($min + $max) / 2);
        }
        $unifontLine = $this -> unifontFile[$foundId];

        // Convert to column format
        $binStr = unpack("C*", pack("H*", substr($unifontLine, 5)));
        $bytes = count($binStr);
        if ($bytes == 32) {
            $width = 16;
            $colFormat = UnifontGlyphFactory::colFormat16($binStr);
        } else if ($bytes == 16) {
            $width = 8;
            $colFormat = UnifontGlyphFactory::colFormat8($binStr);
        }
        // Write to obj
        $glyph = new ColumnFormatGlyph();
        $glyph -> width = $width;
        $glyph -> data = $colFormat;
        return $glyph;
    }
}
