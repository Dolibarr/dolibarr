<?php

namespace Mike42\GfxPhp\Codec\Bmp;

class BmpBitfieldDecoder
{
    private $bitfields;

    public function __construct(BmpColorBitfield $bitfields)
    {
        $this -> bitfields = $bitfields;
    }

    public function read16bit(array $inpBytes) : array
    {
        $red = $this -> bitfields -> getRed();
        $green = $this -> bitfields -> getGreen();
        $blue = $this -> bitfields -> getBlue();
        $alpha = $this -> bitfields -> getAlpha();
        $hasAlpha = $alpha -> getMaxValue() > 0;
        // Fill output array to 1.5 times the size of the input array
        $pixelCount = intdiv(count($inpBytes), 2);
        $outpBytes = array_fill(0, $pixelCount * 3, 0);
        for ($i = 0; $i < $pixelCount; $i++) {
            // Extract little-endian color code in 16 bit space
            $inpColor = ($inpBytes[$i * 2 + 1] << 8) + ($inpBytes[$i * 2]);
            $outpBytes[$i * 3] = $red -> getNormalisedValue($inpColor);
            $outpBytes[$i * 3 + 1] = $green -> getNormalisedValue($inpColor);
            $outpBytes[$i * 3 + 2] = $blue -> getNormalisedValue($inpColor);
            $alphaLevel = $alpha -> getNormalisedValue($inpColor);
            if ($hasAlpha && $alphaLevel < 255) {
                // Mix alpha to white if necessary
                $outpBytes[$i * 3] = $this -> mixChannel(255, $outpBytes[$i * 3], $alphaLevel);
                $outpBytes[$i * 3 + 1] = $this -> mixChannel(255, $outpBytes[$i * 3 + 1], $alphaLevel);
                $outpBytes[$i * 3 + 2] = $this -> mixChannel(255, $outpBytes[$i * 3 + 2], $alphaLevel);
            }
        }
        return $outpBytes;
    }

    public function read32bit(array $inpBytes) : array
    {
        $red = $this -> bitfields -> getRed();
        $green = $this -> bitfields -> getGreen();
        $blue = $this -> bitfields -> getBlue();
        $alpha = $this -> bitfields -> getAlpha();
        $hasAlpha = $alpha -> getMaxValue() > 0;
        // Fill output array to 0.75 times the size of the input array
        $pixelCount = intdiv(count($inpBytes), 4);
        $outpBytes = array_fill(0, $pixelCount * 3, 0);
        for ($i = 0; $i < $pixelCount; $i++) {
            // Extract little-endian color code in 32 bit space
            $inpColor = ($inpBytes[$i * 4 + 3] << 24) + ($inpBytes[$i * 4 + 2] << 16) + ($inpBytes[$i * 4 + 1] << 8) + ($inpBytes[$i * 4]);
            $outpBytes[$i * 3] = $red -> getNormalisedValue($inpColor);
            $outpBytes[$i * 3 + 1] = $green -> getNormalisedValue($inpColor);
            $outpBytes[$i * 3 + 2] = $blue -> getNormalisedValue($inpColor);
            $alphaLevel = $alpha -> getNormalisedValue($inpColor);
            if ($hasAlpha && $alphaLevel < 255) {
                // Mix alpha to white if necessary
                $outpBytes[$i * 3] = $this -> mixChannel(255, $outpBytes[$i * 3], $alphaLevel);
                $outpBytes[$i * 3 + 1] = $this -> mixChannel(255, $outpBytes[$i * 3 + 1], $alphaLevel);
                $outpBytes[$i * 3 + 2] = $this -> mixChannel(255, $outpBytes[$i * 3 + 2], $alphaLevel);
            }
        }
        return $outpBytes;
    }

    public function mixChannel(int $value1, int $value2, int $amount)
    {
        $amountMultiplier = $amount / 255.0;
        $result = (($value1 / 255.0) * $amountMultiplier) + (($value2 / 255.0) * (1.0 - $amountMultiplier));
        return (int)($result * 255.0);
    }
}
