<?php
/* Change to the correct path if you copy this example! */
require __DIR__ . '/../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;

/**
 * This demonstrates available character encodings. Escpos-php accepts UTF-8,
 * and converts this to lower-level data to the printer. This is a complex area, so be
 * prepared to code a model-specific hack ('CapabilityProfile') for your printer.
 *
 * If you run into trouble, please file an issue on GitHub, including at a minimum:
 * - A UTF-8 test string in the language you're working in, and
 * - A test print or link to a technical document which lists the available
 *      code pages ('character code tables') for your printer.
 *
 * The DefaultCapabilityProfile works for Espson-branded printers. For other models, you
 * must use/create a PrinterCapabilityProfile for your printer containing a list of code
 * page numbers for your printer- otherwise you will get mojibake.
 *
 * If you do not intend to use non-English characters, then use SimpleCapabilityProfile,
 * which has only the default encoding, effectively disabling code page changes.
 */

include(dirname(__FILE__) . '/resources/character-encoding-test-strings.inc');
try {
    // Enter connector and capability profile (to match your printer)
    $connector = new FilePrintConnector("php://stdout");
    $profile = CapabilityProfile::load("default");
    
    /* Print a series of receipts containing i18n example strings */
    $printer = new Printer($connector, $profile);
    $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED | Printer::MODE_DOUBLE_WIDTH);
    $printer -> text("Implemented languages\n");
    $printer -> selectPrintMode();
    foreach ($inputsOk as $label => $str) {
        $printer -> setEmphasis(true);
        $printer -> text($label . ":\n");
        $printer -> setEmphasis(false);
        $printer -> text($str);
    }
    $printer -> feed();
    
    $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED | Printer::MODE_DOUBLE_WIDTH);
    $printer -> text("Works in progress\n");
    $printer -> selectPrintMode();
    foreach ($inputsNotOk as $label => $str) {
        $printer -> setEmphasis(true);
        $printer -> text($label . ":\n");
        $printer -> setEmphasis(false);
        $printer -> text($str);
    }
    $printer -> cut();

    /* Close printer */
    $printer -> close();
} catch (Exception $e) {
    echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}
