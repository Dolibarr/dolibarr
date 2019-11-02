<?php
use Mike42\Escpos\EscposImage;

class ExampleTest extends PHPUnit_Framework_TestCase
{
    /* Verify that the examples don't fizzle out with fatal errors */
    private $exampleDir;
    
    public function setup()
    {
        $this -> exampleDir = dirname(__FILE__) . "/../../example/";
    }
    
    /**
     * @medium
     */
    public function testBitImage()
    {
        $this->markTestSkipped('Not repeatable on Travis CI.');
        $this -> requireGraphicsLibrary();
        $outp = $this -> runExample("bit-image.php");
        $this -> outpTest($outp, "bit-image.bin");
    }
    
    /**
     * @medium
     */
    public function testCharacterEncodings()
    {
        $outp = $this -> runExample("character-encodings.php");
        $this -> outpTest($outp, "character-encodings.bin");
    }
    
    /**
     * @medium
     */
    public function testCharacterTables()
    {
        $outp = $this -> runExample("character-tables.php");
        $this -> outpTest($outp, "character-tables.bin");
    }
    
    private function outpTest($outp, $fn)
    {
        $file = dirname(__FILE__) . "/resources/output/".$fn;
        if (!file_exists($file)) {
            file_put_contents($file, $outp);
        }
        $this -> assertEquals($outp, file_get_contents($file));
    }
    
    /**
     * @medium
     */
    public function testDemo()
    {
        $this->markTestSkipped('Not repeatable on Travis CI.');
        $this -> requireGraphicsLibrary();
        $outp = $this -> runExample("demo.php");
        $this -> outpTest($outp, "demo.bin");
    }
    
    /**
     * @medium
     */
    public function testGraphics()
    {
        $this->markTestSkipped('Not repeatable on Travis CI.');
        $this -> requireGraphicsLibrary();
        $outp = $this -> runExample("graphics.php");
        $this -> outpTest($outp, "graphics.bin");
    }
    
    /**
     * @medium
     */
    public function testReceiptWithLogo()
    {
        $this->markTestSkipped('Not repeatable on Travis CI.');
        $this -> requireGraphicsLibrary();
        $outp = $this -> runExample("receipt-with-logo.php");
        $this -> outpTest($outp, "receipt-with-logo.bin");
    }
    
    /**
     * @medium
     */
    public function testQrCode()
    {
        $outp = $this -> runExample("qr-code.php");
        $this -> outpTest($outp, "qr-code.bin");
    }

    /**
     * @medium
     */
    public function testBarcode()
    {
        $outp = $this -> runExample("barcode.php");
        $this -> outpTest($outp, "barcode.bin");
    }
    
    /**
     * @medium
     */
    public function testTextSize()
    {
        $outp = $this -> runExample("text-size.php");
        $this -> outpTest($outp, "text-size.bin");
    }

    /**
     * @medium
     */
    public function testMarginsAndSpacing()
    {
        $outp = $this -> runExample("margins-and-spacing.php");
        $this -> outpTest($outp, "margins-and-spacing.bin");
    }

    /**
     * @medium
     */
    public function testPdf417Code()
    {
        $outp = $this -> runExample("pdf417-code.php");
        $this -> outpTest($outp, "pdf417-code.bin");
    }

    public function testInterfaceCups()
    {
        $outp = $this -> runSyntaxCheck("interface/cups.php");
    }
    
    public function testInterfaceEthernet()
    {
        $outp = $this -> runSyntaxCheck("interface/ethernet.php");
    }
    
    public function testInterfaceLinuxUSB()
    {
        $outp = $this -> runSyntaxCheck("interface/linux-usb.php");
    }
    
    public function testInterfaceWindowsUSB()
    {
        $outp = $this -> runSyntaxCheck("interface/windows-usb.php");
    }
    
    public function testInterfaceSMB()
    {
        $outp = $this -> runSyntaxCheck("interface/smb.php");
    }
    
    public function testInterfaceWindowsLPT()
    {
        $outp = $this -> runSyntaxCheck("interface/windows-lpt.php");
    }
    
    private function runSyntaxCheck($fn)
    {
        $this -> runExample($fn, true);
    }
    
    private function runExample($fn, $syntaxCheck = false)
    {
        // Change directory and check script
        chdir($this -> exampleDir);
        $this -> assertTrue(file_exists($fn), "Script $fn not found.");
            // Run command and save output
        $php = "php" . ($syntaxCheck ? " -l" : "");
        ob_start();
        passthru($php . " " . escapeshellarg($fn), $retval);
        $outp = ob_get_contents();
        ob_end_clean();
        // Check return value
        $this -> assertEquals(0, $retval, "Example $fn exited with status $retval");
        return $outp;
    }
    
    protected function requireGraphicsLibrary()
    {
        if (!EscposImage::isGdLoaded() && !EscposImage::isImagickLoaded()) {
            $this -> markTestSkipped("This test requires a graphics library.");
        }
    }
}
