<?php
namespace Mike42\GfxPhp\Util;

/*
 * Write variable-width output in the GIF layout
 */
class LzwEncodeBuffer
{
    public function __construct()
    {
        $this -> textBuffer = "";
        $this -> bitBuffer = 0;
        $this -> bitPos = 0;
    }
    
    public function add(int $code, int $bits)
    {
        $mask = [];
        $byte = 0;
        for ($i = 0; $i <= 8; $i++) {
            $mask[] = $byte;
            $byte = ($byte << 1) | 1;
        }
        while ($bits > 0) {
            // Determine number of bits to write to this byte
            $capacity = (8 - $this -> bitPos);
            $writeBits = min($capacity, $bits);
            // Extract bits to use, shift and OR them on
            $maskedByte = ($code & $mask[$writeBits]);
            $shiftDir = ($this -> bitPos);
            $theBits = $maskedByte << $shiftDir;
            $this -> bitBuffer = $this -> bitBuffer | $theBits;
            // Truncate lower bits of code and repeat
            $code = $code >> $writeBits;
            $bits = $bits - $writeBits;
            $this -> bitPos += $writeBits;
            if ($this -> bitPos >= 8) {
                $this -> bitPos = 0;
                $this -> textBuffer .= chr($this -> bitBuffer);
                $this -> bitBuffer = 0;
            }
        }
    }

    public function asString()
    {
        if ($this -> bitPos !== 0) {
            // Flush
            $this -> textBuffer .= chr($this -> bitBuffer);
            $this -> bitPos = 0;
        }
        return $this -> textBuffer;
    }
}
