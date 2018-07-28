<?php
/* Change to the correct path if you copy this example! */
require __DIR__ . '/../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
use Mike42\Escpos\PrintBuffers\EscposPrintBuffer;
use Mike42\Escpos\PrintBuffers\ImagePrintBuffer;

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
    $printer = new Printer($connector, $profile);
    $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED | Printer::MODE_DOUBLE_WIDTH);
    $printer -> text("Implemented languages\n");
    $printer -> selectPrintMode();
    foreach ($inputsOk as $label => $str) {
        $printer -> setEmphasis(true);
        $printer -> text($label . ":\n");
        $printer -> setEmphasis(false);
        foreach ($buffers as $buffer) {
            $printer -> setPrintBuffer($buffer);
            $printer -> text($str);
        }
        $printer -> setPrintBuffer($buffers[0]);
    }
    $printer -> feed();
    
    $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED | Printer::MODE_DOUBLE_WIDTH);
    $printer -> text("Works in progress\n");
    $printer -> selectPrintMode();
    foreach ($inputsNotOk as $label => $str) {
        $printer -> setEmphasis(true);
        $printer -> text($label . ":\n");
        $printer -> setEmphasis(false);
        foreach ($buffers as $buffer) {
            $printer -> setPrintBuffer($buffer);
            $printer -> text($str);
        }
        $printer -> setPrintBuffer($buffers[0]);
    }
    $printer -> cut();

    /* Close printer */
    $printer -> close();
} catch (Exception $e) {
    echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}
