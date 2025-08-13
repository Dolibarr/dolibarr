<?php
/**
 * Compression and decompression of binary data in the LZW format, as
 * used in GIF.
 *
 * This is implemented based on these decriptions:
 *
 * - http://www.matthewflickinger.com/lab/whatsinagif/lzw_image_data.asp
 * - https://www2.cs.duke.edu/csed/curious/compression/lzw.html
 * - https://www.cs.cmu.edu/~cil/lzw.and.gif.txt
 * - https://rosettacode.org/wiki/LZW_compression
 */
namespace Mike42\GfxPhp\Util;

/**
 * Utility classes to decode or encode entire strings.
 */
class LzwCompression
{
    public static function compress(string $inp, int $minCodeSize)
    {
        $bits = $minCodeSize + 1;
        $dict = new LzwEncodeDictionary($minCodeSize);
        $outp = new LzwEncodeBuffer();
        $outp -> add($dict -> getClearCode(), $bits);
        $word = "";
        for ($i = 0; $i < strlen($inp); $i++) {
            $ch = $word . $inp[$i];
            if ($dict -> contains($ch)) {
                $word = $ch;
            } else {
                $outp -> add($dict -> get($word), $bits);
                $dict -> add($ch);
                if ($dict -> getSize() > (2 ** $bits)) {
                    // expand bit size
                    $bits++;
                }
                $word = $inp[$i];
            }
            // Separately to parsing the input stream, periodically reset to keep under 12 bits
            if ($dict -> getSize() >= AbstractLzwDictionary::MAX_SIZE) {
                if ($word !== "") {
                    $outp -> add($dict -> get($word), $bits);
                }
                $outp -> add($dict -> getClearCode(), $bits);
                $bits = $minCodeSize + 1;
                $dict -> clear();
                $word = "";
                continue;
            }
        }
        if ($word !== "") {
            $outp -> add($dict -> get($word), $bits);
        }
        $outp -> add($dict -> getEodCode(), $bits);
        return $outp -> asString();
    }

    public static function decompress(string $inp, int $minCodeSize)
    {
        $bits = $minCodeSize + 1;
        $dict = new LzwDecodeDictionary($minCodeSize);
        $buffer = new LzwDecodeBuffer($inp);
        $outp = "";
        // Output first code
        $prevcode = $buffer -> read($bits);
        while ($prevcode == $dict -> getClearCode()) {
            // Skip any 'clear' codes at the start
            $prevcode = $buffer -> read($bits);
        }
        if ($prevcode === false || $prevcode >= $dict -> getSize()) {
            throw new \Exception("Not LZW data");
        }
        $prevcodeStr = $dict -> get($prevcode);
        $outp .= $prevcodeStr;
        while (($code = $buffer -> read($bits)) !== false) {
            // Decode
            if ($code > $dict -> getSize()) {
                throw new \Exception("Bad LZW, code too large");
            }

            if ($code === $dict -> getClearCode()) {
                // got a clear code
                $bits = $minCodeSize + 1;
                $prevcodeStr = "";
                $dict -> clear();
                // Manually do an iteration with no dictionary modification.
                // This involves reading ahead 1 character, same as what we do above the
                // 'while' loop. There is probably some way to combine these
                $prevcode = $buffer -> read($bits);
                while ($prevcode == $dict -> getClearCode()) {
                    // In case of consecutive clear codes
                    $prevcode = $buffer -> read($bits);
                }
                if ($prevcode === false || $prevcode === $dict -> getEodCode()) {
                    // EOF
                    break;
                }
                if ($prevcode >= $dict -> getSize()) {
                    throw new \Exception("Bad LZW, code too large after reset");
                }
                $prevcodeStr = $dict -> get($prevcode);
                $outp .= $prevcodeStr;
                continue;
            } else if ($code === $dict -> getEodCode()) {
                // End the stream
                break;
            }

            if ($dict -> contains($code)) {
                $codeStr = $dict -> get($code);
            } else {
                $codeStr = $prevcodeStr . $prevcodeStr[0];
            }
            $outp .= $codeStr;
            $ch = $codeStr[0];
            if ($dict -> getSize() < 4096) { // Don't modify dictionary after a certain point
                $dict -> add($prevcodeStr . $ch);
            }
            // assign current code to prev code
            $prevcode = $code;
            $prevcodeStr = $codeStr;
            if ($dict -> getSize() >= (2 ** $bits)) {
                if (($bits + 1) > 12) {
                    // Don't exceed 12 bits. If table gets max'd out at
                    // 4096, the following RESET or END code remains 12-bit encoded.
                    continue;
                } else {
                    // Expand
                    $bits++;
                }
            }
        }
        return $outp;
    }
}
