<?php

namespace Mike42\GfxPhp\Codec\Bmp;

class BmpColorMask
{
    private $mask;
    private $len;
    private $offset;

    public function __construct(int $mask)
    {
        $this->mask = $mask;
        $offset = 0;
        $len = 0;
        if ($mask != 0x00) {
            // Count 0's on the right of the mask
            while ((($mask >> $offset) & 0x01) == 0x00) {
                $offset++;
            }
            // Count length until nothing is left
            while ((($mask >> ($offset + $len)) & 0x01) == 0x01) {
                $len++;
            }
            // Report issues if there are other 1's, indicating a non-contiguous mask
            if (($mask >> ($offset + $len)) !== 0x00) {
                throw new \RuntimeException("Bad mask value, should be contiguous");
            }
        }
        $this->offset = $offset;
        $this->len = $len;
    }

    public function getLen()
    {
        return $this->len;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getMask()
    {
        return $this->mask;
    }

    public function getValue(int $input)
    {
        // Read value 0 to max value
        return ($input & $this -> mask) >> $this -> offset;
    }

    public function getMaxValue()
    {
        // Max value for size of this channel
        return (2 ** $this->len)- 1;
    }

    /**
     * Mask out a value for this channel, and return its value in a 0-255 range
     */
    public function getNormalisedValue(int $input)
    {
        // Get raw value, range depends on mask length
        $rawValue = ($input & ($this -> mask)) >> ($this -> offset);
        // Out of 255
        $normalisedValue = $rawValue;
        if ($this -> len < 8) {
            $normalisedValue = $rawValue << (8 - ($this->len));
        } else if ($this -> len > 8) {
            $normalisedValue = $rawValue >> (($this->len) - 8);
        }
        // Correct scaling up
        $repeat = ($normalisedValue >> ($this -> len));
        while ($repeat > 0) {
            $normalisedValue |= $repeat;
            $repeat >>= ($this -> len);
        }
        return $normalisedValue;
    }
}
