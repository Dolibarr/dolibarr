<?php


namespace Mike42\GfxPhp\Codec\Bmp;

use Exception;

/*
 * Similar to RLE8 decoder, but works on 4-bytes per pixel.
 */
class Rle4Decoder
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
        $rowWidth = intdiv((4 * $width + 31), 32) * 4; // Padding 4-bit data to a 4-byte boundary.
        foreach ($outpNum as $rowOrig) {
            // Combine two numbers 0-16 into one numeric value 0-256
            $row = array_fill(0, $rowWidth, 0);
            $chunks = array_chunk($rowOrig, 2);
            for ($i = 0; $i < count($chunks); $i++) {
                $chunk = $chunks[$i];
                if (count($chunk) == 2) {
                    $row[$i] = ($chunk[0] << 4) + $chunk[1];
                } else {
                    $row[$i] = $chunk[0];
                }
            }
            $outStringArr[] = pack("C*", ...$row);
        }
        return implode($outStringArr);
    }

    private function decodeNumbers(array $inpNum, int $width, int $height) : array
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
                    $absoluteLenPixels = $secondByte;
                    $absoluteLenBytes = intdiv($absoluteLenPixels + 1, 2);
                    if ($i + $absoluteLenBytes > $len) {
                        throw new Exception("Unexpected EOF");
                    }
                    $bytesToPaste = array_slice($inpNum, $i, $absoluteLenBytes);
                    $valuesToPaste = array_fill(0, $absoluteLenPixels, 0);
                    for ($j = 0; $j < $absoluteLenPixels; $j++) {
                        $sourceVal = $bytesToPaste[intdiv($j, 2)];
                        $valuesToPaste[$j] = ($j % 2 == 0) ? ($sourceVal >> 4) : ($sourceVal & 0x0F);
                    }
                    $i += $absoluteLenBytes;
                    if ($absoluteLenBytes % 2 != 0) {
                        // skip a padding byte too
                        $i++;
                    }
                    $buffer -> absolute($valuesToPaste);
                }
            } else {
                // Alternate between first and second index when repeating
                $col1 = ($secondByte >> 4);
                $col2 = ($secondByte & 0x0F);
                for ($j = 0; $j < $firstByte; $j++) {
                    $buffer -> set($j % 2 == 0 ? $col1 : $col2);
                }
            }
        }
        return $buffer -> getContents();
    }
}
