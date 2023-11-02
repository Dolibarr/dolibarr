<?php

namespace Mike42\Escpos\Experimental\Unifont;

use Mike42\Escpos\PrintBuffers\PrintBuffer;
use Mike42\Escpos\Printer;

class UnifontPrintBuffer implements PrintBuffer
{
    private $printer;
    private $fontMap;
    private $started;
    private $unifont;

    public function __construct(string $unifontFilename)
    {
        // Create UnifontGlyphFactory by reading from file
        $unifont = file_get_contents($unifontFilename);
        if ($unifont === false) {
            throw new \Exception("Could not read $unifontFilename");
        }
        $unifontFileLines = explode("\n", $unifont);
        $this -> unifont = new UnifontGlyphFactory($unifontFileLines);
        // Everything else is null
        $this -> printer = null;
        $this -> fontMap = null;
        $this -> started = false;
    }

    public function writeChar(int $codePoint)
    {
        if ($codePoint == 10) {
            $this -> write("\n");
        } else if ($codePoint == 13) {
            // Ignore CR char
        } else {
            // Straight column-format prints
            $this -> fontMap -> writeChar($codePoint);
        }
    }
    
    public function writeText(string $text)
    {
        if (!$this -> started) {
            $mode = Printer::MODE_FONT_B | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH;
            $this -> printer -> getPrintConnector() -> write(Printer::ESC . "!" . chr($mode));
            $this -> printer -> selectUserDefinedCharacterSet(true);
        }
        // Normalize text - this replaces combining characters with composed glyphs, and also helps us eliminated bad UTF-8 early
        $text = \Normalizer::normalize($text);
        if ($text === false) {
            throw new \Exception("Input must be UTF-8");
        }
        // Iterate code points
        $codePointIterator = \IntlBreakIterator::createCodePointInstance();
        $codePointIterator->setText($text);
        while ($codePointIterator->next() > 0) {
            $codePoint = $codePointIterator->getLastCodePoint();
            $this->writeChar($codePoint);
        }
    }
    
    public function flush()
    {
    }
    
    public function setPrinter(Printer $printer = null)
    {
        $this -> printer = $printer;
        $this -> fontMap = new FontMap($this -> unifont, $this -> printer);
    }
    
    public function writeTextRaw(string $text)
    {
    }
    
    public function getPrinter()
    {
        return $this -> printer;
    }
    
    /**
     * Write data to the underlying connector.
     *
     * @param string $data
     */
    private function write($data)
    {
        $this -> printer -> getPrintConnector() -> write($data);
    }
}
