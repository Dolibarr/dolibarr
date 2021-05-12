<?php
class ExampleTest extends PHPUnit_Framework_TestCase {
	/* Verify that the examples don't fizzle out with fatal errors */
	private $exampleDir;
	
	public function setup() {
		$this -> exampleDir = dirname(__FILE__) . "/../../example/";
	}
	
	public function testBitImage() {
		$this -> requireGraphicsLibrary();
		$outp = $this -> runExample("bit-image.php");
		$this -> outpTest($outp, "bit-image.bin");
	}
	
	public function testCharacterEncodings() {
		$outp = $this -> runExample("character-encodings.php");
		$this -> outpTest($outp, "character-encodings.bin");
	}
	
	public function testCharacterTables() {
		$outp = $this -> runExample("character-tables.php");
		$this -> outpTest($outp, "character-tables.bin");
	}
	
	private function outpTest($outp, $fn) {
		$file = dirname(__FILE__) . "/resources/output/".$fn;
		if(!file_exists($file)) {
			file_put_contents($file, $outp);
		}
		$this -> assertEquals($outp, file_get_contents($file));
	}
	
	public function testDemo() {
		$this -> requireGraphicsLibrary();
		$outp = $this -> runExample("demo.php");
		$this -> outpTest($outp, "demo.bin");
	}
	
	public function testGraphics() {
		$this -> requireGraphicsLibrary();
		$outp = $this -> runExample("graphics.php");
		$this -> outpTest($outp, "graphics.bin");
	}
	
	public function testReceiptWithLogo() {
		$this -> requireGraphicsLibrary();
		$outp = $this -> runExample("receipt-with-logo.php");
		$this -> outpTest($outp, "receipt-with-logo.bin");
	}
	
	public function testQrCode() {
		$outp = $this -> runExample("qr-code.php");
		$this -> outpTest($outp, "qr-code.bin");
	}

	public function testBarcode() {
		$outp = $this -> runExample("barcode.php");
		$this -> outpTest($outp, "barcode.bin");
	}
	
	public function testTextSize() {
		$outp = $this -> runExample("text-size.php");
		$this -> outpTest($outp, "text-size.bin");
	}

	/**
	 * @large
	 */
	public function testPrintFromPdf() {
		if(!EscposImage::isImagickLoaded()) {
			$this -> markTestSkipped("imagick plugin required for this test");
		}
		$outp = $this -> runExample("print-from-pdf.php");
		$this -> outpTest(gzcompress($outp, 9), "print-from-pdf.bin.z"); // Compressing output because it's ~1MB
	}

	public function testInterfaceEthernet() {
		// Test attempts DNS lookup on some machine
		$outp = $this -> runExample("interface/ethernet.php");
		$this -> outpTest($outp, "interface.bin");
	}
	
	public function testInterfaceLinuxUSB() {
		$outp = $this -> runExample("interface/linux-usb.php");
		$this -> outpTest($outp, "interface.bin");
	}
	
	public function testInterfaceWindowsUSB() {
		// Output varies between platforms, not checking.
		$outp = $this -> runExample("interface/windows-usb.php");
		$this -> outpTest($outp, "interface.bin");
	}
	
	public function testInterfaceSMB() {
		// Output varies between platforms, not checking.
		$outp = $this -> runExample("interface/smb.php");
		$this -> outpTest($outp, "interface.bin");
	}
	
	public function testInterfaceWindowsLPT() {
		// Output varies between platforms, not checking.
		$outp = $this -> runExample("interface/windows-lpt.php");
		$this -> outpTest($outp, "interface.bin");
	}
	
	private function runExample($fn) {
		// Change directory and check script
		chdir($this -> exampleDir);
		$this -> assertTrue(file_exists($fn), "Script $fn not found.");
		// Run command and save output
		ob_start();
		passthru("php " . escapeshellarg($fn), $retval);
		$outp = ob_get_contents();
		ob_end_clean();
		// Check return value
		$this -> assertEquals(0, $retval, "Example $fn exited with status $retval");
		return $outp;
	}
	
	protected function requireGraphicsLibrary() {
		if(!EscposImage::isGdLoaded() && !EscposImage::isImagickLoaded()) {
			$this -> markTestSkipped("This test requires a graphics library.");
		}
	}
}
