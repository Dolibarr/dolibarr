<?php
namespace Mike42\GfxPhp\Codec\Png;

class FilterDecoder
{
    /*
     * Unfilter entire image, or a pass of an interlaced image.
     */
    public function unfilterImage(string $binData, int $scanlineBytes, int $channels, int $bitDepth)
    {
        // Extract filtered data
        $scanlinesWithFiltering = str_split($binData, $scanlineBytes + 1);
        $filterType = [];
        $filteredData = [];
        foreach ($scanlinesWithFiltering as $scanline) {
            $filterType[] = ord($scanline[0]);
            $filteredData[] = array_values(unpack("C*", substr($scanline, 1)));
        }
        
        // Transform back to raw data
        $rawData = [];
        $bytesPerPixel = intdiv($bitDepth + 7, 8) * $channels;
        $prior = array_fill(0, $scanlineBytes, 0);
        foreach ($filteredData as $key => $currentFiltered) {
            $current = $this -> unfilterScanline($currentFiltered, $prior, $filterType[$key], $bytesPerPixel);
            $imgScanlineData[] = $current;
            $prior = $current;
        }
        return array_merge(...$imgScanlineData);
    }

    /**
     * Unfilter an individual scanline
     */
    public function unfilterScanline(array $currentFiltered, array $prior, int $filterType, int $bpp)
    {
        $lw = count($currentFiltered);
        if ($filterType === 0) {
            // None
            return $currentFiltered;
        } elseif ($filterType === 1) {
            $ret = array_fill(0, $lw, 128);
            for ($i = 0; $i < $lw; $i++) {
                $rawLeft = ($i < $bpp ? 0 : $ret[$i-$bpp]);
                $subX = $currentFiltered[$i];
                $ret[$i] = ($subX + $rawLeft) % 256;
            }
            return $ret;
        } elseif ($filterType === 2) {
            $ret = array_fill(0, $lw, 0);
            for ($i = 0; $i < $lw; $i++) {
                $ret[$i] = ($currentFiltered[$i] + $prior[$i]) % 256;
            }
            return $ret;
        } elseif ($filterType === 3) {
            $ret = array_fill(0, $lw, 0);
            for ($i = 0; $i < $lw; $i++) {
                $prevX = $i < $bpp ? 0 : $ret[$i-$bpp];
                $priorX = $prior[$i];
                $avgX = intdiv($prevX + $priorX, 2);
                $prediction = $currentFiltered[$i] - $avgX;
                $ret[$i] = ($avgX + $currentFiltered[$i]) % 256;
            }
            return $ret;
        } elseif ($filterType === 4) {
            $ret = array_fill(0, $lw, 0);
            for ($i = 0; $i < $lw; $i++) {
                $upperLeft = $i < $bpp ? 0 : $prior[$i-$bpp];
                $left = $i < $bpp ? 0 : $ret[$i-$bpp];
                $upper = $prior[$i];
                $ret[$i] = ($this -> paethPredictor($left, $upper, $upperLeft) + $currentFiltered[$i]) % 256;
            }
            return $ret;
        }
        throw new \Exception("Filter type $filterType not valid");
    }

    private function paethPredictor(int $a, int $b, int $c)
    {
        // Nearest-neighbor, based on pseudocode from the PNG spec.
        $p = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);
        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        } else if ($pb <= $pc) {
            return $b;
        }
        return $c;
    }
}
