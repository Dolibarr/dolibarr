<?php
/**
 * Test that the old API for capability profiles can still be used.
 */
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\Printer;

class LegacyCapabilityProfileTest extends PHPUnit_Framework_TestCase
{
    private $profiles;
    private $checklist;
    
    function setup()
    {
        $this -> profiles = array(
                'Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile',
                'Mike42\Escpos\CapabilityProfiles\EposTepCapabilityProfile',
                'Mike42\Escpos\CapabilityProfiles\SimpleCapabilityProfile',
                'Mike42\Escpos\CapabilityProfiles\StarCapabilityProfile',
                'Mike42\Escpos\CapabilityProfiles\P822DCapabilityProfile');
        $this -> checklist = array();
        foreach ($this -> profiles as $profile) {
            $this-> checklist[] = $profile::getInstance();
        }
    }
    
    function testSupportedCodePages()
    {
        foreach ($this -> checklist as $obj) {
            $check = $obj -> getCodePages();
            $this -> assertTrue(is_array($check) && isset($check[0]));
            foreach ($check as $num => $page) {
                $this -> assertTrue(is_numeric($num));
            }
        }
    }

    function testText() {
        /* Smoke test over text rendering with each profile.
         * Just makes sure we can attempt to print 'hello world' and a non-ASCII
         * char without anything blowing up */
        foreach ($this -> checklist as $obj) {
            $connector = new DummyPrintConnector();
            $printer = new Printer($connector, $obj);
            $printer -> text("Hello world €\n");
            $printer -> close();
            // Check for character cache
            $profileName = $obj -> getId();
            $expected = "Characters-$profileName.ser.z";
            $filename = __DIR__ . "/../../src/Mike42/Escpos/PrintBuffers/cache/$expected";
            $this -> assertFileExists($filename);
        }
    }

    function testSupportsBitImageRaster()
    {
        foreach ($this -> checklist as $obj) {
            $check = $obj -> getSupportsBitImageRaster();
            $this -> assertTrue(is_bool($check));
        }
    }
    
    function testSupportsGraphics()
    {
        foreach ($this -> checklist as $obj) {
            $check = $obj -> getSupportsGraphics();
            $this -> assertTrue(is_bool($check));
        }
    }
    
    function testSupportsQrCode()
    {
        foreach ($this -> checklist as $obj) {
            $check = $obj -> getSupportsQrCode();
            $this -> assertTrue(is_bool($check));
        }
    }
}
