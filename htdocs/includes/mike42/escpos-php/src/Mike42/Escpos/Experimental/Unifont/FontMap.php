<?php

namespace Mike42\Escpos\Experimental\Unifont;

use InvalidArgumentException;
use Mike42\Escpos\Printer;

class FontMap
{
    protected $printer;

    const MIN = 0x20;
    const MAX = 0x7E;
    const FONT_A_WIDTH = 12;
    const FONT_B_WIDTH = 9;

    // Map memory locations to code points
    protected $memory;

    // Map unicode code points to bytes
    protected $chars;

    // next available slot
    protected $next = 0;

    public function __construct(ColumnFormatGlyphFactory $glyphFactory, Printer $printer)
    {
        $this -> printer = $printer;
        $this -> glyphFactory = $glyphFactory;
        $this -> reset();
    }

    public function cacheChars(array $codePoints)
    {
        // TODO flush existing cache to fill with these chars.
    }

    public function writeChar(int $codePoint)
    {
        if (!$this -> addChar($codePoint, true)) {
            throw new InvalidArgumentException("Code point $codePoint not available");
        }
        $data = implode($this -> chars[$codePoint]);
        $this -> printer -> getPrintConnector() -> write($data);
    }

    public function reset()
    {
        $this -> chars = [];
        $this -> memory = array_fill(0, (\Mike42\Escpos\Experimental\Unifont\FontMap::MAX - FontMap::MIN) + 1, -1);
    }

    public function occupied($id)
    {
        return $this -> memory[$id] !== -1;
    }

    public function evict($id)
    {
        if (!$this -> occupied($id)) {
            return true;
        }
        unset($this -> chars[$this -> memory[$id]]);
        $this -> memory[$id] = -1;
        return true;
    }

    public function addChar(int $codePoint, $evict = true)
    {
        if (isset($this -> chars[$codePoint])) {
            // Char already available
            return true;
        }
        // Get glyph
        $glyph = $this -> glyphFactory -> getGlyph($codePoint);
        $glyphParts = $glyph -> segment(self::FONT_B_WIDTH);
        //print_r($glyphParts);
        //
        // Clear count($glyphParts) of space from $start
        $start = $this -> next;
        $chars = [];
        $submit = [];
        for ($i = 0; $i < count($glyphParts); $i++) {
            $id = ($this -> next + $i) % count($this -> memory);
            if ($this -> occupied($id)) {
                if ($evict) {
                    $this -> evict($id);
                } else {
                    return false;
                }
            }
            $thisChar = $id + self::MIN;
            $chars[] = chr($thisChar);
            $submit[$thisChar] = $glyphParts[$i];
        }

        // Success in locating memory space, move along counters
        $this -> next = ($this -> next + count($glyphParts)) % count($this -> memory);
        $this -> submitCharsToPrinterFont($submit);
        $this -> memory[$start] = $codePoint;
        $this -> chars[$codePoint] = $chars;

        return true;
    }

    public function submitCharsToPrinterFont(array $chars)
    {
        ksort($chars);
        // TODO We can sort into batches of contiguous characters here.
        foreach ($chars as $char => $glyph) {
            $verticalBytes = 3;
            $data = Printer::ESC . "&" . chr($verticalBytes) . chr($char) . chr($char) . chr($glyph -> width) . $glyph -> data;
            $this -> printer -> getPrintConnector() -> write($data);
        }
    }
}
