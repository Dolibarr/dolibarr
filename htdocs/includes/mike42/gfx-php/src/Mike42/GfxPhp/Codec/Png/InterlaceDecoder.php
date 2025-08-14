<?php
namespace Mike42\GfxPhp\Codec\Png;

class InterlaceDecoder
{
    private $filterDecoder;
    
    public function __construct(FilterDecoder $filterDecoder)
    {
        $this -> filterDecoder = $filterDecoder;
    }
    
    public function decode(PngHeader $header, string $binData)
    {
        if ($header -> getInterlace() === PngHeader::INTERLACE_NONE) {
            // No interlacing!
            return $this -> decodeNoInterlace($header, $binData);
        } else if ($header -> getInterlace() === PngHeader::INTERLACE_ADAM7) {
            return $this -> decodeAdam7Interlace($header, $binData);
        } else {
            throw new \Exception("Unknown interlace type");
        }
        return $imageData;
    }
    
    private function decodeAdam7Interlace(PngHeader $header, string $binData)
    {
        $bitDepth = $header -> getBitDepth();
        $width = $header -> getWidth();
        $height = $header -> getHeight();
        $channels = $header -> getChannels();
        $scanlineBytes = intdiv($width * $bitDepth + 7, 8) * $channels;
        // ADAM7 interlace.
        // Params for laying out pixels in each pass
        // (startX, stepX, startY, stepY)
        $passParams = [
            [0, 8, 0, 8],
            [4, 8, 0, 8],
            [0, 4, 4, 8],
            [2, 4, 0, 4],
            [0, 2, 2, 4],
            [1, 2, 0, 2],
            [0, 1, 1, 2]
        ];
        // Calculate width and height of each of the seven
        // sub-images.
        $passes = [
            [
                "width" => intdiv($width + 7, 8),
                "height" => intdiv($height + 7, 8)
            ],
            [
                "width" => intdiv($width + 3, 8),
                "height" => intdiv($height + 7, 8)
            ],
            [
                "width" => intdiv($width + 3, 4),
                "height" => intdiv($height + 3, 8)
            ],
            [
                "width" => intdiv($width + 1, 4),
                "height" => intdiv($height + 3, 4)
            ],
            [
                "width" => intdiv($width + 1, 2),
                "height" => intdiv($height + 1, 4)
            ],
            [
                "width" => intdiv($width, 2),
                "height" => intdiv($height + 1, 2)
            ],
            [
                "width" => $width,
                "height" => intdiv($height, 2)
            ],
        ];
        // Extract and unfilter each pass
        $position = 0;
        $imageData = array_fill(0, $scanlineBytes * $height, 0);
        foreach ($passes as $passId => $pass) {
            $passWidth = $pass['width'];
            $passHeight = $pass['height'];
            if ($passWidth == 0 || $passHeight == 0) {
                // No data in this scanline, proceed.
                continue;
            }
            $passScanlineWidth = intdiv($passWidth * $bitDepth + 7, 8) * $channels;
            $len = ($passScanlineWidth + 1) * $passHeight;
            // Extract and de-filter scanlines in this pass.
            $passScanlines = [];
            $passUnfiltered = substr($binData, $position, $len);
            if ($passUnfiltered === false || strlen($passUnfiltered) !== $len) {
                throw new \Exception("Incomplete image detected.");
            }
            $passData = $this -> filterDecoder -> unfilterImage($passUnfiltered, $passScanlineWidth, $channels, $bitDepth);
            $position += $len;
            // Paste this pass data over the original image.
            $startX = $passParams[$passId][0];
            $stepX = $passParams[$passId][1];
            $startY = $passParams[$passId][2];
            $stepY = $passParams[$passId][3];
            if (($bitDepth * $channels) % 8 == 0) {
                // Simple case: the pixels fill bytes and never cross byte boundaries.
                $pixelBytes = intdiv($bitDepth + 1, 8) * $channels;
                for ($srcY = 0; $srcY < $passHeight; $srcY++) {
                    for ($srcX = 0; $srcX < $passWidth; $srcX++) {
                        $destX = $startX + $stepX * $srcX;
                        $destY = $startY + $stepY * $srcY;
                        for ($i = 0; $i < $pixelBytes; $i++) {
                            // Map byte within pixel (eg. RGBA pixel can be 4 bytes).
                            $srcByte = $srcY * $passWidth * $pixelBytes + $srcX * $pixelBytes + $i;
                            $destByte = $destY * $width * $pixelBytes + $destX * $pixelBytes + $i;
                            $imageData[$destByte] = $passData[$srcByte];
                        }
                    }
                }
            } else {
                // More complex case: The pixels are 1, 2, or 4 bits wide
                $pixelBits = $bitDepth * $channels;
                for ($srcY = 0; $srcY < $passHeight; $srcY++) {
                    for ($srcX = 0; $srcX < $passWidth; $srcX++) {
                        $destX = $startX + $stepX * $srcX;
                        $destY = $startY + $stepY * $srcY;
                        $srcBit = $srcY * $passScanlineWidth * 8 + $srcX * $pixelBits;
                        $destBit = $destY * $scanlineBytes * 8 + $destX * $pixelBits;
                        $srcByte = intdiv($srcBit, 8);
                        $destByte = intdiv($destBit, 8);
                        $srcOffset = $srcBit % 8;
                        $destOffset = $destBit % 8;
                        $srcVal = (($passData[$srcByte] << $srcOffset) & 0xFF) >> (8 - $pixelBits);
                        $destVal = ($srcVal << (8 - $pixelBits - $destOffset));
                        // Logical OR the relevant bits in
                        $imageData[$destByte] |= $destVal;
                    }
                }
            }
        }
        return $imageData;
    }
    
    private function decodeNoInterlace(PngHeader $header, string $binData)
    {
        $bitDepth = $header -> getBitDepth();
        $width = $header -> getWidth();
        $height = $header -> getHeight();
        $channels = $header -> getChannels();
        $scanlineBytes = intdiv($width * $bitDepth + 7, 8) * $channels;
        return $this -> filterDecoder -> unfilterImage(
            $binData,
            $scanlineBytes,
            $channels,
            $bitDepth
        );
    }
}
