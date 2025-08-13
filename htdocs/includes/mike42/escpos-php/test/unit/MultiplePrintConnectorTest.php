<?php

use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\PrintConnectors\MultiplePrintConnector;
use Mike42\Escpos\Printer;

class MultiplePrintConnectorTest extends PHPUnit\Framework\TestCase
{
    public function testOnePrinter()
    {
        // Set up connector which goes to multiple printers
        $kitchenPrinter = new DummyPrintConnector();
        $barPrinter = new DummyPrintConnector();
        $connector = new MultiplePrintConnector($kitchenPrinter, $barPrinter);
        // Print something
        $printer = new Printer($connector);
        $printer->text("Hello World\n");
        $printer->cut();
        // Get data out and close the printer
        $kitchenText = $kitchenPrinter->getData();
        $barText = $barPrinter->getData();
        $printer->close();
        // Should have matching prints to each printer
        $this->assertEquals("\x1b@Hello World\x0a\x1dVA\x03", $kitchenText);
        $this->assertEquals("\x1b@Hello World\x0a\x1dVA\x03", $barText);
    }
}
