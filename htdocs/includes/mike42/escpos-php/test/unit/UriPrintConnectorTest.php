<?php
use Mike42\Escpos\PrintConnectors\UriPrintConnector;

class UriPrintConnectorTest extends PHPUnit_Framework_TestCase
{
    public function testFile()
    {
        $filename = tempnam(sys_get_temp_dir(), "escpos-php-");
        // Make connector, write some data
        $connector = UriPrintConnector::get("file://" . $filename);
        $connector -> write("AAA");
        $connector -> finalize();
        $this -> assertEquals("AAA", file_get_contents($filename));
        $this -> assertEquals('Mike42\Escpos\PrintConnectors\FilePrintConnector', get_class($connector));
        unlink($filename);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage not finalized
     */
    public function testSmb()
    {
        $connector = UriPrintConnector::get("smb://windows/printer");
        $this -> assertEquals('Mike42\Escpos\PrintConnectors\WindowsPrintConnector', get_class($connector));
        // We expect that this will throw an exception, we can't
        // realistically print to a real printer in this test though... :)
        $connector -> __destruct();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Malformed connector URI
     */
    public function testBadUri()
    {
        $connector = UriPrintConnector::get("foooooo");
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Connection refused
     */
    public function testNetwork()
    {
        // Port should be closed so we can catch an error and move on
        $connector = UriPrintConnector::get("tcp://localhost:45987/");
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage URI sheme is not supported: ldap://
     */
    public function testUnsupportedUri()
    {
        // Try to print to something silly
        $connector = UriPrintConnector::get("ldap://host:1234/");
    }
}
