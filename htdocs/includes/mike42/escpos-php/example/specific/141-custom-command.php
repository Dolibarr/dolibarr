<?php
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;

/**
 * This example shows how to send a custom command to the printer
 *
 * "ESC ( B" is the barcode function for Epson LX300 series.
 * This is not part of standard ESC/POS, but it's a good example
 * of how to send some binary to the driver.
 */

/* Barcode type is used in this script */
const EAN13 = 0;

/* Barcode properties */
$type = EAN13;
$content = "0075678164125";

/*
 * Make the command.
 * This is documented on page A-14 of:
 * https://files.support.epson.com/pdf/lx300p/lx300pu1.pdf
 */
$m = chr(EAN13);
$n = intLowHigh(strlen($content), 2);
$barcodeCommand = Printer::ESC . "G(" . $m . $n . $content;

/* Send it off as usual */
$connector = new FilePrintConnector("php://output");
$printer = new Printer($connector);
$printer->getPrintConnector()->write($barcodeCommand);
$printer->cut();
$printer->close();

/**
 * Generate two characters for a number: In lower and higher parts, or more parts as needed.
 *
 * @param int $input
 *            Input number
 * @param int $length
 *            The number of bytes to output (1 - 4).
 */
function intLowHigh($input, $length)
{
    $outp = "";
    for ($i = 0; $i < $length; $i ++) {
        $outp .= chr($input % 256);
        $input = (int) ($input / 256);
    }
    return $outp;
}
?>