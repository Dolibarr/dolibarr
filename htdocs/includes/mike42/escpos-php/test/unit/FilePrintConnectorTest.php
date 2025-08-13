<?php
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

class FilePrintConnectorTest extends PHPUnit\Framework\TestCase
{
    public function testTmpfile()
    {
        // Should attempt to send data to the local printer by writing to it
        $tmpfname = tempnam("/tmp", "php");
        $connector = new FilePrintConnector($tmpfname);
        $connector -> finalize();
        $connector -> finalize(); // Silently do nothing if printer already closed
        $this -> assertEquals("", file_get_contents($tmpfname));
        unlink($tmpfname);
    }
    
    public function testReadAfterClose()
    {
        // Should attempt to send data to the local printer by writing to it
        $this -> expectException(Exception::class);
        $tmpfname = tempnam("/tmp", "php");
        $connector = new FilePrintConnector($tmpfname);
        $connector -> finalize();
        $connector -> write("Test");
        unlink($tmpfname);
    }
}
