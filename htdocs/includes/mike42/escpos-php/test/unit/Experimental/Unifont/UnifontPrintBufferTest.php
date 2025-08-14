<?php

namespace Mike42\Escpos\Experimental\Unifont;

use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\Printer;
use PHPUnit\Framework\TestCase;

class UnifontPrintBufferTest extends TestCase
{
    protected $printer;
    protected $outputConnector;

    protected function setup()
    {
        $this -> outputConnector = new DummyPrintConnector();
        $this -> printer = new Printer($this -> outputConnector);
        $filename = tempnam(sys_get_temp_dir(), "escpos-php-");
        $glyphs = [
            "0020:00000000000000000000000000000000", // space is guessed
            "0041:0000000018242442427E424242420000" // Letter "A" from Wikipedia
        ];
        file_put_contents($filename, implode("\n", $glyphs));
        $printBuffer = new UnifontPrintBuffer($filename);
        $this -> printer -> setPrintBuffer($printBuffer);
    }

    protected function checkOutput($expected = null)
    {
        /* Check those output strings */
        $outp = $this -> outputConnector -> getData();
        if ($expected === null) {
            echo "\nOutput was:\n\"" . friendlyBinary($outp) . "\"\n";
        }
        $this -> assertEquals($expected, $outp);
    }

    protected function tearDown()
    {
        $this -> outputConnector -> finalize();
    }

    public function testString()
    {
        // Render the text "AA A" rendered via used-defined font.
        $this -> printer -> text("AA A\r\n");
        $this -> checkOutput("\x1b@\x1b!1\x1b%\x01\x1b&\x03  \x08\x00\x00\x00\x01\xfc\x00\x06@\x00\x08@\x00\x08@\x00\x06@\x00\x01\xfc\x00\x00\x00\x00  \x1b&\x03!!\x08\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00! \x0a");
    }
}
