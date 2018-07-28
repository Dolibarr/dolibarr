<?php
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;

class CupsPrintConnectorTest extends PHPUnit_Framework_TestCase
{
    private $connector;
    public function testPrinterExists()
    {
        $connector = $this->getMockConnector("FooPrinter", array("FooPrinter"));
        $connector->expects($this->once())->method('getCmdOutput')->with($this->stringContains("lp -d 'FooPrinter' "));
        $connector->finalize();
    }
    public function testPrinterDoesntExist()
    {
        $this -> setExpectedException('BadMethodCallException');
        $connector = $this->getMockConnector("FooPrinter", array("OtherPrinter"));
        $connector->expects($this->once())->method('getCmdOutput')->with($this->stringContains("lp -d 'FooPrinter' "));
        $connector->finalize();
    }
    public function testNoPrinter()
    {
        $this -> setExpectedException('BadMethodCallException');
        $connector = $this->getMockConnector("FooPrinter", array(""));
    }
    private function getMockConnector($path, array $printers)
    {
        $stub = $this->getMockBuilder('Mike42\Escpos\PrintConnectors\CupsPrintConnector')->setMethods(array (
                'getCmdOutput',
                'getLocalPrinters'
        ))->disableOriginalConstructor()->getMock();
        $stub->method('getCmdOutput')->willReturn("");
        $stub->method('getLocalPrinters')->willReturn($printers);
        $stub->__construct($path);
        return $stub;
    }
}
