<?php
namespace Mike42\GfxPhp\Codec\Bmp;

use Exception;

class Rle8Decoder
{
    const RLE_ESCAPE = 0;
    const RLE_END_LINE = 0;
    const RLE_END_BITMAP = 1;
    const RLE_JUMP = 2;

    public function decode(string $compressedImgData, int $width, int $height) : string
    {
        // Read into numeric input.
        $inpNum = array_values(unpack("C*", $compressedImgData));
        $outpNum = $this -> decodeNumbers($inpNum, $width, $height);
        // Back to string
        $outStringArr = [];
        $rowWidth = intdiv((8 * $width + 31), 32) * 4; // Padding to 4-byte boundary: Can this be simplified?
        $padding = str_repeat("\0", $rowWidth - $width);
        foreach ($outpNum as $row) {
            $outStringArr[] = pack("C*", ...$row);
        }
        return implode($padding, $outStringArr) . $padding;
    }

    public function decodeNumbers(array $inpNum, int $width, int $height) : array
    {
        // Initialize canvas
        $buffer = new RleCanvas($width, $height);
        // read input data, 2 bytes at a time
        $i = 0;
        $len = intdiv(count($inpNum), 2) * 2;
        while ($i < $len) {
            $firstByte = $inpNum[$i];
            $secondByte = $inpNum[$i + 1];
            $i += 2;
            if ($firstByte === self::RLE_ESCAPE) {
                if ($secondByte === self::RLE_END_LINE) {
                    $buffer -> endOfLine();
                } else if ($secondByte === self::RLE_END_BITMAP) {
                    $buffer -> endOfBitmap();
                } else if ($secondByte === self::RLE_JUMP) {
                    // "Delta".
                    if ($i + 2 > $len) {  // Need 2 more bytes to find out how far to jump
                        throw new Exception("Unexpected EOF");
                    }
                    $deltaX = $inpNum[$i];
                    $deltaY = $inpNum[$i + 1];
                    $i += 2;
                    $buffer -> delta($deltaX, $deltaY);
                } else {
                    // "Absolute run". Paste the requested number of bytes onto the canvas
                    $absoluteLen = $secondByte;
                    if ($i + $absoluteLen > $len) {
                        throw new Exception("Unexpected EOF");
                    }
                    $bytesToPaste = array_slice($inpNum, $i, $absoluteLen);
                    $i += $absoluteLen;
                    if ($absoluteLen % 2 != 0) {
                        // skip a padding byte too
                        $i++;
                    }
                    $buffer -> absolute($bytesToPaste);
                }
            } else {
                $buffer -> repeat($secondByte, $firstByte);
            }
        }
        return $buffer -> getContents();
    }
}
