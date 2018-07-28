<?php
/**
 * Test that all sub-classes of AbstractCapabilityProfile
 * are creating data in the right format.
 */
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\Printer;

class EscposCapabilityProfileTest extends PHPUnit_Framework_TestCase
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
            $check = $obj -> getSupportedCodePages();
            $this -> assertTrue(is_array($check) && isset($check[0]) && $check[0] == 'CP437');
            $custom = $obj -> getCustomCodePages();
            foreach ($check as $num => $page) {
                $this -> assertTrue(is_numeric($num) && ($page === false || is_string($page)));
                if ($page === false || strpos($page, ":") === false) {
                    continue;
                }
                $part = explode(":", $page);
                if (!array_shift($part) == "custom") {
                    continue;
                }
                $this -> assertTrue(isset($custom[implode(":", $part)]));
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
            $profileClass = explode("\\", get_class($obj));
            $profileName = array_pop($profileClass);
            $expected = "Characters-$profileName.ser.z";
            $filename = __DIR__ . "/../../src/Mike42/Escpos/PrintBuffers/cache/$expected";
            $this -> assertFileExists($filename);
        }
    }
    
    function testCustomCodePages()
    {
        foreach ($this -> checklist as $obj) {
            $check = $obj -> getCustomCodePages();
            $this -> assertTrue(is_array($check));
            foreach ($check as $name => $customMap) {
                $this -> assertTrue(is_string($name));
                $this -> assertTrue(is_string($customMap) && mb_strlen($customMap, 'UTF-8') == 128);
            }
        }
    }
    
    function testSupportsBitImage()
    {
        foreach ($this -> checklist as $obj) {
            $check = $obj -> getSupportsBitImage();
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
