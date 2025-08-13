<?php

use Mike42\Escpos\Devices\AuresCustomerDisplay;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\CapabilityProfile;

class AuresCustomerDisplayTest extends PHPUnit\Framework\TestCase
{
    protected $printer;
    protected $outputConnector;
    
    protected function setup()
    {
        /* Print to nowhere- for testing which inputs are accepted */
        $this -> outputConnector = new DummyPrintConnector();
        $profile = CapabilityProfile::load('OCD-300');
        $this -> printer = new AuresCustomerDisplay($this -> outputConnector, $profile);
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

    public function testInitializeOutput()
    {
        $this -> checkOutput("\x02\x05C1\x03\x1b@\x1bt\x00\x1f\x02");
    }
    
    public function testselectTextScrollMode() {
        $this -> outputConnector -> clear();
        $this -> printer -> selectTextScrollMode(AuresCustomerDisplay::TEXT_OVERWRITE);
        $this -> checkOutput("\x1f\x01");
    }
    
    public function testClear() {
        $this -> outputConnector -> clear();
        $this -> printer -> clear();
        $this -> checkOutput("\x0c");
    }
    
    public function testShowFirmwareVersion() {
        $this -> outputConnector -> clear();
        $this -> printer -> showFirmwareVersion();
        $this -> checkOutput("\x02\x05V\x01\x03");
    }
    
    public function testSelfTest() {
        $this -> outputConnector -> clear();
        $this -> printer -> selfTest();
        $this -> checkOutput("\x02\x05D\x08\x03");
    }
    
    public function testShowLogo() {
        $this -> outputConnector -> clear();
        $this -> printer -> showLogo();
        $this -> checkOutput("\x02\xfcU\xaaU\xaa");
    }
    
    public function testTest() {
        $this -> outputConnector -> clear();
        // Handling of line-endings differs to regular printers, need to use \r\n
        $this -> printer -> text("Hello\nWorld\n");
        $this -> checkOutput("Hello\x0d\x0aWorld\x0d\x0a");
    }
}