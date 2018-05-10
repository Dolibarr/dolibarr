<?php
/* Change to the correct path if you copy this example! */
require_once(dirname(__FILE__) . "/../Escpos.php");

/**
 * This example builds on character-encodings.php, also providing an image-based rendering.
 * This is quite slow, since a) the buffers are changed dozens of
 * times in the example, and b) It involves sending very wide images, which printers don't like!
 * 
 * There are currently no test cases around the image printing, since it is an experimental feature.
 *
 * It does, however, illustrate the way that more encodings are available when image output is used.
 */
include(dirname(__FILE__) . '/resources/character-encoding-test-strings.inc');

try {
	// Enter connector and capability profile
	$connector = new FilePrintConnector("php://stdout");
	$profile = DefaultCapabilityProfile::getInstance();
	$buffers = array(new EscposPrintBuffer(), new ImagePrintBuffer());

	/* Print a series of receipts containing i18n example strings */
	$printer = new Escpos($connector, $profile);
	$printer -> selectPrintMode(Escpos::MODE_DOUBLE_HEIGHT | Escpos::MODE_EMPHASIZED | Escpos::MODE_DOUBLE_WIDTH);
	$printer -> text("Implemented languages\n");
	$printer -> selectPrintMode();
	foreach($inputsOk as $label => $str) {
		$printer -> setEmphasis(true);
		$printer -> text($label . ":\n");
		$printer -> setEmphasis(false);
		foreach($buffers as $buffer) {
			$printer -> setPrintBuffer($buffer);
			$printer -> text($str);
		}
		$printer -> setPrintBuffer($buffers[0]);
	}
	$printer -> feed();
	
	$printer -> selectPrintMode(Escpos::MODE_DOUBLE_HEIGHT | Escpos::MODE_EMPHASIZED | Escpos::MODE_DOUBLE_WIDTH);
	$printer -> text("Works in progress\n");
	$printer -> selectPrintMode();
	foreach($inputsNotOk as $label => $str) {
		$printer -> setEmphasis(true);
		$printer -> text($label . ":\n");
		$printer -> setEmphasis(false);
		foreach($buffers as $buffer) {
			$printer -> setPrintBuffer($buffer);
			$printer -> text($str);
		}
		$printer -> setPrintBuffer($buffers[0]);
	}
	$printer -> cut();

	/* Close printer */
	$printer -> close();
} catch(Exception $e) {
	echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}

